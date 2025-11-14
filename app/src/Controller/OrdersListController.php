<?php

namespace App\Controller;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersListController extends AbstractController
{
    #[Route('/orders', name: 'app_orders_list')]
    public function index(OrderService $orderService): Response
    {
        $ordersList = $orderService->findAllOrders();

        return $this->render(
            view: 'orders_list/index.html.twig',
            parameters: [
                'orders' => $ordersList,
            ]
        );
    }
}
