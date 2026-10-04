<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Http\Support\TuleapDocumentRequest;
use App\Extensions\Tuleap\Http\Support\TuleapJsonApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\DataResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprint retrospective management: action items and planning, per Tuleap sprint.
 */
#[Group('Tuleap', weight: 10)]
final class RetroController
{
    public function __construct(private TuleapServiceInterface $service)
    {
    }

    /**
     * List retrospective action items for a sprint.
     */
    public function getActions(int $sprintId, Server $server): DataResponse
    {
        return TuleapJsonApiResponse::many(
            $server,
            'tuleap-retro-actions',
            $this->service->getRetroActions($sprintId),
            [$sprintId],
        );
    }

    /**
     * List retrospective planning actions for a Tuleap project.
     */
    public function getPlanActions(int $projectId, Server $server): DataResponse
    {
        return TuleapJsonApiResponse::many(
            $server,
            'tuleap-retro-actions',
            $this->service->getRetroPlanActions($projectId),
            [$projectId],
        );
    }

    /**
     * Create a new retrospective action item for a sprint.
     */
    public function addAction(int $sprintId, Request $request, Server $server): DataResponse
    {
        $attributes = TuleapDocumentRequest::attributes(
            $request,
            'tuleap-retro-actions',
            ['project_id', 'member_id', 'category', 'text', 'status'],
            [
                'project_id' => ['nullable', 'integer'],
                'member_id' => ['nullable', 'integer'],
                'category' => ['required', 'string', 'max:255'],
                'text' => ['required', 'string'],
                'status' => ['nullable', 'string', 'max:255'],
            ],
        );
        $action = $this->service->addRetroAction(array_merge($attributes, ['sprint_id' => $sprintId]));

        return TuleapJsonApiResponse::one($server, 'tuleap-retro-actions', $action, $action->getKey(), status: 201);
    }

    /**
     * Update an existing retrospective action item.
     */
    public function updateAction(int $sprintId, int $id, Request $request, Server $server): DataResponse
    {
        $attributes = TuleapDocumentRequest::attributes($request, 'tuleap-retro-actions', [
            'project_id', 'member_id', 'category', 'text', 'status',
        ], [
            'project_id' => ['nullable', 'integer'],
            'member_id' => ['nullable', 'integer'],
            'category' => ['sometimes', 'string', 'max:255'],
            'text' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);
        $action = $this->service->updateRetroAction($sprintId, $id, $attributes);
        if ($action === null) {
            throw JsonApiException::error([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'title' => 'Not Found',
                'detail' => 'The requested retrospective action was not found.',
            ]);
        }

        return TuleapJsonApiResponse::one($server, 'tuleap-retro-actions', $action, $action->getKey());
    }

    /**
     * Delete a retrospective action item.
     */
    public function deleteAction(int $sprintId, int $id): Response
    {
        $this->service->deleteRetroAction($sprintId, $id);

        return response()->noContent();
    }
}
