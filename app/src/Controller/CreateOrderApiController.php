<?php

namespace App\Controller;

use App\Dto\CreateOrderRequestDto;
use App\Response\ApiResponse;
use App\Service\OrderService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class CreateOrderApiController
{
    #[Route(path: '/api/orders/create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateOrderRequestDto $createOrderRequestDto,
        OrderService $orderService
    ): ApiResponse {
        return ApiResponse::createSuccess(
            data: [
                'orderId' => $orderService->createOrder($createOrderRequestDto)
            ],
            message: null,
            code: Response::HTTP_CREATED
        );
    }
}