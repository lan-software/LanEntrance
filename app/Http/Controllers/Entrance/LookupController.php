<?php

namespace App\Http\Controllers\Entrance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entrance\LookupRequest;
use App\Services\LanCoreValidationService;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function __construct(
        private readonly LanCoreValidationService $validation,
    ) {}

    public function __invoke(LookupRequest $request): JsonResponse
    {
        $results = $this->validation->search(
            query: $request->validated('q'),
            operator: $request->user(),
        );

        return response()->json(['results' => $results]);
    }
}
