<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChargeRequest;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ChargeController extends Controller
{
    public function store(ChargeRequest $chargeRequest, StripeService $stripeService): JsonResponse
    {
        $charge = $stripeService->setUser($chargeRequest->user())
            ->charge($chargeRequest->amount, $chargeRequest->token);

        return response()->json($charge, Response::HTTP_CREATED);
    }
}
