<?php

namespace App\Controller;

use App\Dto\UpdateStatusOrderRequestDto;
use App\Response\ApiResponse;
use App\Service\OrderService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class UpdateOrderStatusApiController
{
    #[Route(path: '/api/orders/update', methods: ['PATCH'])]
    public function __invoke(
        #[ValueResolver('update_status_order_request')]
        UpdateStatusOrderRequestDto $updateStatusOrderRequestDto,
        OrderService $orderService
    ): ApiResponse {
        $orderService->updateOrder($updateStatusOrderRequestDto);

        return ApiResponse::createSuccess(
            data: null,
            message: null,
            code: Response::HTTP_OK
        );
    }
}