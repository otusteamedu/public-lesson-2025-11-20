<?php

namespace App\Controller;

use App\Dto\CreateOrderRequestDto;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateOrderApiController extends AbstractController
{
    #[Route(path: '/api/orders/create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateOrderRequestDto $createOrderRequestDto,
        OrderService $orderService
    ): JsonResponse {
        return $this->json(
            [
                'result' => true,
                'data' => [
                    'orderId' => $orderService->createOrder($createOrderRequestDto)
                ]
            ],
            Response::HTTP_CREATED
        );
    }
}