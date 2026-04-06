<?php

namespace App\Http\Controllers\Entrance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entrance\OverrideRequest;
use App\Services\LanCoreValidationService;
use Illuminate\Http\JsonResponse;

class OverrideController extends Controller
{
    public function __construct(
        private readonly LanCoreValidationService $validation,
    ) {}

    public function __invoke(OverrideRequest $request): JsonResponse
    {
        $result = $this->validation->override(
            token: $request->validated('token'),
            validationId: $request->validated('validation_id'),
            reason: $request->validated('reason'),
            operator: $request->user(),
        );

        return response()->json($result);
    }
}
