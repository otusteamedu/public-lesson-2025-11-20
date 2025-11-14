<?php

namespace App\Controller;

use App\Provider\EventProvider;
use App\Service\OrderService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersListController extends AbstractController
{
    /**
     * @param OrderService $orderService
     * @param EventProvider $eventProvider
     * @return Response
     *
     * @throws InvalidArgumentException
     */
    #[Route('/orders', name: 'app_orders_list')]
    public function index(
        OrderService $orderService,
        EventProvider $eventProvider
    ): Response {
        $ordersList = $orderService->findAllOrders();
        $eventsList = $eventProvider->getEvents();

        return $this->render(
            view: 'orders_list/index.html.twig',
            parameters: [
                'orders' => $ordersList,
                'events' => $eventsList,
            ]
        );
    }
}
