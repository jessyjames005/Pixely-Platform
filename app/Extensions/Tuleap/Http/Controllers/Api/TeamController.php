<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Http\Support\TuleapDocumentRequest;
use App\Extensions\Tuleap\Http\Support\TuleapJsonApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Team roster management for Tuleap sprint planning.
 */
#[Group('Tuleap', weight: 10)]
final class TeamController
{
    public function __construct(private TuleapServiceInterface $service)
    {
    }

    /**
     * List team members (roster) for sprint planning.
     */
    public function getMembers(Server $server): DataResponse
    {
        $projectId = request()->query('project_id') ? (int) request()->query('project_id') : null;

        return TuleapJsonApiResponse::many(
            $server,
            'tuleap-team-members',
            $this->service->getMembers($projectId),
            $projectId === null ? [] : [$projectId],
        );
    }

    /**
     * Add a team member to the roster.
     */
    public function addMember(Request $request, Server $server): DataResponse
    {
        $attributes = TuleapDocumentRequest::attributes(
            $request,
            'tuleap-team-members',
            ['project_id', 'name', 'tuleap_username'],
            [
                'project_id' => ['nullable', 'integer'],
                'name' => ['required', 'string', 'max:255'],
                'tuleap_username' => ['nullable', 'string', 'max:255'],
            ],
        );
        $member = $this->service->addMember($attributes);

        return TuleapJsonApiResponse::one($server, 'tuleap-team-members', $member, $member->getKey(), status: 201);
    }

    /**
     * Remove a team member from the roster.
     */
    public function deleteMember(int $id): Response
    {
        $this->service->deleteMember($id);

        return response()->noContent();
    }
}
