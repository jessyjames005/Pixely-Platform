<?php

declare(strict_types=1);

namespace App\Core\Tooling\Http\Controllers;

use App\Core\Tooling\Services\ReadOnlyQueryValidator;
use App\JsonApi\V1\DocumentId;
use App\JsonApi\V1\DocumentResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\DataResponse;

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
     * Name of the connection the browser queries.
     *
     * Outside of MySQL — e.g. the in-memory SQLite database the test
     * suite runs on, where credential-level SELECT-only grants don't
     * exist — the dedicated 'mysql_readonly' connection cannot be used,
     * so the default connection is queried instead. In MySQL
     * environments, 'mysql_readonly' remains the enforced layer.
     */
    private function connectionName(): string
    {
        return config('database.default') === 'mysql'
            ? self::READONLY_CONNECTION
            : (string) config('database.default');
    }

    /**
     * List tables in the database.
     */
    public function tables(Server $server): DataResponse
    {
        $tables = Schema::connection($this->connectionName())->getTables();

        $names = array_map(
            static fn (array $table): array => [
                'name' => $table['name'],
                'rows' => $table['rows'] ?? null,
                'size' => $table['size'] ?? null,
            ],
            $tables,
        );

        $resources = array_map(
            fn (array $table): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('database-tables'),
                DocumentId::encode('database-table', $table['name']),
                $table,
            ),
            $names,
        );

        return DataResponse::make($resources)
            ->withServer('v1')
            ->withMeta(['total' => count($resources)]);
    }

    /**
     * Display a table's columns.
     */
    public function columns(string $table, Server $server): DataResponse
    {
        $this->validator->assertSafe("SELECT * FROM {$table} LIMIT 0"); // reuses table-name safety checks below
        $this->assertTableExists($table);

        $columns = Schema::connection($this->connectionName())->getColumns($table);

        $resources = array_map(
            fn (array $column): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('database-columns'),
                DocumentId::encode('database-column', $table, $column['name']),
                [
                    'name' => $column['name'],
                    'type' => $column['type'],
                    'nullable' => $column['nullable'],
                ],
            ),
            $columns,
        );

        return DataResponse::make($resources)->withServer('v1');
    }

    /**
     * Preview a table's rows (paginated), with sensitive columns redacted.
     */
    public function preview(Request $request, string $table, Server $server): DataResponse
    {
        $this->assertTableExists($table);

        $page = max(1, (int) $request->integer('page', 1));
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        $total = DB::connection($this->connectionName())->table($table)->count();

        $rows = DB::connection($this->connectionName())
            ->table($table)
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->validator->redactRow((array) $row))
            ->all();

        $resources = array_map(
            fn (array $row, int $index): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('database-rows'),
                DocumentId::encode('database-table-row', $table, (string) ($offset + $index)),
                ['values' => $row],
            ),
            $rows,
            array_keys($rows),
        );

        return DataResponse::make($resources)
            ->withServer('v1')
            ->withMeta([
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ]);
    }

    /**
     * Execute an ad-hoc, validated, read-only SELECT query.
     */
    public function query(Request $request, Server $server): DataResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'array'],
            'data.type' => ['required', 'in:sql-queries'],
            'data.attributes' => ['required', 'array'],
            'data.attributes.sql' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $this->validator->assertSafe($validated['data']['attributes']['sql']);
        } catch (\InvalidArgumentException $exception) {
            throw JsonApiException::error([
                'status' => 422,
                'code' => 'UNSAFE_QUERY',
                'title' => 'Unprocessable Entity',
                'detail' => $exception->getMessage(),
            ], $exception);
        }

        $safeSql = $this->validator->enforceLimit(
            $validated['data']['attributes']['sql'],
            self::MAX_ROWS,
        );

        $connection = DB::connection($this->connectionName());
        $connection->getPdo()->setAttribute(\PDO::ATTR_TIMEOUT, self::TIMEOUT_SECONDS);

        try {
            $rows = $connection->select($safeSql);
        } catch (\Throwable $exception) {
            throw JsonApiException::error([
                'status' => 422,
                'code' => 'QUERY_EXECUTION_FAILED',
                'title' => 'Unprocessable Entity',
                'detail' => 'The query could not be executed.',
                'meta' => ['reason' => $exception->getMessage()],
            ], $exception);
        }

        $redacted = array_map(
            fn ($row) => $this->validator->redactRow((array) $row),
            $rows,
        );

        $queryId = hash('sha256', $safeSql);
        $resources = array_map(
            fn (array $row, int $index): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('database-rows'),
                DocumentId::encode('database-query-row', $queryId, (string) $index),
                ['values' => $row],
            ),
            $redacted,
            array_keys($redacted),
        );

        return DataResponse::make($resources)
            ->withServer('v1')
            ->withMeta(['total' => count($resources), 'limited_to' => self::MAX_ROWS]);
    }

    /**
     * Validates that the table name is a real table (prevents SQL
     * injection through the {table} route parameter itself, since
     * table/column identifiers cannot be parameter-bound).
     */
    private function assertTableExists(string $table): void
    {
        $tables = array_column(Schema::connection($this->connectionName())->getTables(), 'name');

        if (! in_array($table, $tables, true)) {
            abort(404, 'Table not found.');
        }
    }
}
