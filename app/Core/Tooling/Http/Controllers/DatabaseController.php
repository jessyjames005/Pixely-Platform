<?php

declare(strict_types=1);

namespace App\Core\Tooling\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Api\Response\ApiResponse;
use App\Core\Tooling\Services\ReadOnlyQueryValidator;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only database browsing and ad-hoc SELECT query execution.
 *
 * Always runs against the 'mysql_readonly' connection, whose
 * database user is granted SELECT only — this is a second,
 * database-enforced layer on top of ReadOnlyQueryValidator.
 */
#[Group('System Tooling', weight: 6)]
final class DatabaseController
{
    private const READONLY_CONNECTION = 'mysql_readonly';
    private const MAX_ROWS = 500;
    private const TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly ReadOnlyQueryValidator $validator,
    ) {
    }

    /**
     * List tables in the database.
     */
    public function tables(ApiCollectionResponse $apiResponse): JsonResponse
    {
        $tables = Schema::connection(self::READONLY_CONNECTION)->getTables();

        $names = array_map(
            static fn (array $table): array => [
                'name' => $table['name'],
                'rows' => $table['rows'] ?? null,
                'size' => $table['size'] ?? null,
            ],
            $tables,
        );

        return $apiResponse->response(
            data: $names,
            meta: ['total' => count($names)],
        );
    }

    /**
     * Display a table's columns.
     */
    public function columns(string $table, ApiCollectionResponse $apiResponse): JsonResponse
    {
        $this->validator->assertSafe("SELECT * FROM {$table} LIMIT 0"); // reuses table-name safety checks below
        $this->assertTableExists($table);

        $columns = Schema::connection(self::READONLY_CONNECTION)->getColumns($table);

        return $apiResponse->response(
            data: array_map(
                static fn (array $column): array => [
                    'name' => $column['name'],
                    'type' => $column['type'],
                    'nullable' => $column['nullable'],
                ],
                $columns,
            ),
        );
    }

    /**
     * Preview a table's rows (paginated), with sensitive columns redacted.
     */
    public function preview(Request $request, string $table, ApiCollectionResponse $apiResponse): JsonResponse
    {
        $this->assertTableExists($table);

        $page = max(1, (int) $request->integer('page', 1));
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        $total = DB::connection(self::READONLY_CONNECTION)->table($table)->count();

        $rows = DB::connection(self::READONLY_CONNECTION)
            ->table($table)
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->validator->redactRow((array) $row))
            ->all();

        return $apiResponse->response(
            data: $rows,
            meta: [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        );
    }

    /**
     * Execute an ad-hoc, validated, read-only SELECT query.
     */
    public function query(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'sql' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $this->validator->assertSafe($validated['sql']);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'UNSAFE_QUERY',
                        'message' => $exception->getMessage(),
                    ],
                ],
                422,
            );
        }

        $safeSql = $this->validator->enforceLimit($validated['sql'], self::MAX_ROWS);

        $connection = DB::connection(self::READONLY_CONNECTION);
        $connection->getPdo()->setAttribute(\PDO::ATTR_TIMEOUT, self::TIMEOUT_SECONDS);

        try {
            $rows = $connection->select($safeSql);
        } catch (\Throwable $exception) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'QUERY_EXECUTION_FAILED',
                        'message' => 'The query could not be executed.',
                        'details' => ['reason' => $exception->getMessage()],
                    ],
                ],
                422,
            );
        }

        $redacted = array_map(
            fn ($row) => $this->validator->redactRow((array) $row),
            $rows,
        );

        return $apiResponse->response(
            data: $redacted,
            meta: ['total' => count($redacted), 'limited_to' => self::MAX_ROWS],
        );
    }

    /**
     * Validates that the table name is a real table (prevents SQL
     * injection through the {table} route parameter itself, since
     * table/column identifiers cannot be parameter-bound).
     */
    private function assertTableExists(string $table): void
    {
        $tables = array_column(Schema::connection(self::READONLY_CONNECTION)->getTables(), 'name');

        if (! in_array($table, $tables, true)) {
            abort(404, 'Table not found.');
        }
    }
}
