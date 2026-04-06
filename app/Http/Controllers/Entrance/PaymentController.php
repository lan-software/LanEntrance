<?php

namespace App\Http\Controllers\Entrance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entrance\ConfirmPaymentRequest;
use App\Services\LanCoreValidationService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly LanCoreValidationService $validation,
    ) {}

    public function __invoke(ConfirmPaymentRequest $request): JsonResponse
    {
        $result = $this->validation->confirmPayment(
            token: $request->validated('token'),
            validationId: $request->validated('validation_id'),
            paymentMethod: $request->validated('payment_method'),
            amount: $request->validated('amount'),
            operator: $request->user(),
        );

        return response()->json($result);
    }
}
