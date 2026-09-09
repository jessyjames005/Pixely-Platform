<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeamController
{
    public function __construct(private TuleapServiceInterface $service) {}

    public function getMembers(): JsonResponse
    {
        $projectId = request()->query('project_id') ? (int) request()->query('project_id') : null;
        return response()->json($this->service->getMembers($projectId));
    }

    public function addMember(Request $request): JsonResponse
    {
        $member = $this->service->addMember($request->all());
        return response()->json($member, 201);
    }

    public function deleteMember(int $id): JsonResponse
    {
        $this->service->deleteMember($id);
        return response()->json(['ok' => true]);
    }
}