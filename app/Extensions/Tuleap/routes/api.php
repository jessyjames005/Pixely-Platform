<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Extensions\Tuleap\Http\Controllers\Api\TuleapController;
use App\Extensions\Tuleap\Http\Controllers\Api\SprintController;
use App\Extensions\Tuleap\Http\Controllers\Api\TeamController;
use App\Extensions\Tuleap\Http\Controllers\Api\RetroController;
use App\Extensions\Tuleap\Http\Controllers\Api\ConfigController;

Route::middleware('auth:sanctum')->group(function () {
    // Tuleap API proxy
    Route::get('/tuleap/ping', [TuleapController::class, 'ping']);
    Route::get('/tuleap/projects', [TuleapController::class, 'getProjects']);
    Route::get('/tuleap/projects/{projectId}', [TuleapController::class, 'getProject']);
    Route::get('/tuleap/projects/{projectId}/members', [TuleapController::class, 'getProjectMembers']);
    Route::get('/tuleap/projects/{projectId}/milestones', [TuleapController::class, 'getMilestones']);
    Route::get('/tuleap/milestones/{milestoneId}/stats', [TuleapController::class, 'getStats']);
    Route::get('/tuleap/milestones/{milestoneId}/burndown', [TuleapController::class, 'getBurndown']);
    Route::get('/tuleap/projects/{projectId}/sprint-history', [TuleapController::class, 'getSprintHistory']);

    // Sprint management
    Route::get('/sprint/config/{sprintId}', [SprintController::class, 'getConfig']);
    Route::put('/sprint/config/{sprintId}', [SprintController::class, 'saveConfig']);

    // Team members
    Route::get('/team/members', [TeamController::class, 'getMembers']);
    Route::post('/team/members', [TeamController::class, 'addMember']);
    Route::delete('/team/members/{id}', [TeamController::class, 'deleteMember']);

    // CAF
    Route::get('/caf/{sprintId}', [SprintController::class, 'getCaf']);
    Route::put('/caf/{sprintId}/{memberId}', [SprintController::class, 'saveCaf']);
    Route::get('/caf-history', [SprintController::class, 'getCafHistory']);

    // Burndown
    Route::get('/burndown/{sprintId}', [SprintController::class, 'getBurndown']);
    Route::put('/burndown/{sprintId}', [SprintController::class, 'saveBurndown']);

    // Retrospective
    Route::get('/retro/{sprintId}', [RetroController::class, 'getActions']);
    Route::get('/retro/project/{projectId}/plan-action', [RetroController::class, 'getPlanActions']);
    Route::post('/retro/{sprintId}', [RetroController::class, 'addAction']);
    Route::put('/retro/{sprintId}/{id}', [RetroController::class, 'updateAction']);
    Route::delete('/retro/{sprintId}/{id}', [RetroController::class, 'deleteAction']);

    // Config
    Route::get('/config', [ConfigController::class, 'getConfig']);
    Route::put('/config', [ConfigController::class, 'saveConfig']);
    Route::get('/cache-info', [ConfigController::class, 'getCacheInfo']);
    Route::delete('/cache', [ConfigController::class, 'clearCache']);
});