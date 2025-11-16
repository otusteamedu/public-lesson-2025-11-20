<?php

namespace App\Service;

use App\Dto\CreateOrderRequestDto;
use App\Dto\OrderItemDto;
use App\Dto\UpdateStatusOrderRequestDto;
use App\Entity\OrderEntity;
use App\Repository\ClientEntityRepository;
use App\Repository\OrderEntityRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class OrderService
{
    public function __construct(
        private ClientEntityRepository $clientEntityRepository,
        private OrderEntityRepository $orderEntityRepository
    ) {
    }

    public function findAllOrders(): array
    {
        $orders = $this->orderEntityRepository->findAll();

        return array_map(
            function (OrderEntity $order) {
                $updatedAt = empty($order->getUpdatedAt()) ? '' : $order->getUpdatedAt()->format('Y-m-d H:i:s');

                return new OrderItemDto(
                    id: $order->getId(),
                    createdAt: $order->getCreatedAt()->format('Y-m-d H:i:s'),
                    updatedAt: $updatedAt,
                    createdBy: $order->getCreatedBy()->getLogin(),
                    status: $order->getStatus(),
                    orderContent: json_encode($order->getOrderContent())
                );
            },
            $orders
        );
    }

    public function createOrder(CreateOrderRequestDto $dto): int
    {
        $client = $this->clientEntityRepository->find($dto->clientId);

        if (empty($client)) {
            throw new NotFoundHttpException('Клиент не найден');
        }

        $order = new OrderEntity();
        $order
            ->setStatus(OrderEntity::STATUS_NEW)
            ->setCreatedAt(new \DateTime())
            ->setCreatedBy($client)
            ->setOrderContent($dto->orderContent);

        $this->orderEntityRepository->createOrder($order);

        return $order->getId();
    }

    public function updateOrder(UpdateStatusOrderRequestDto $dto): void
    {
        $order = $this->orderEntityRepository->find($dto->orderId);

        if (empty($order)) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        $order->setStatus($dto->status);

        $this->orderEntityRepository->updateOrder($order);
    }
}