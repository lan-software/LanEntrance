<?php

namespace App\Http\Controllers\Entrance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entrance\CheckinRequest;
use App\Http\Requests\Entrance\ValidateTokenRequest;
use App\Services\LanCoreValidationService;
use Illuminate\Http\JsonResponse;

class EntranceController extends Controller
{
    public function __construct(
        private readonly LanCoreValidationService $validation,
    ) {}

    public function validate(ValidateTokenRequest $request): JsonResponse
    {
        $result = $this->validation->validate(
            token: $request->validated('token'),
            operator: $request->user(),
        );

        return response()->json($result);
    }

    public function checkin(CheckinRequest $request): JsonResponse
    {
        $result = $this->validation->checkin(
            token: $request->validated('token'),
            validationId: $request->validated('validation_id'),
            operator: $request->user(),
        );

        return response()->json($result);
    }

    public function verifyCheckin(CheckinRequest $request): JsonResponse
    {
        $result = $this->validation->verifyCheckin(
            token: $request->validated('token'),
            validationId: $request->validated('validation_id'),
            operator: $request->user(),
        );

        return response()->json($result);
    }
}
