<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateOrderRequestDto
{
    public function __construct(
        #[Assert\Positive(message: 'Идентификатор клиента должен быть больше нуля')]
        public int $clientId,

        #[Assert\NotBlank(message: 'Заказ не может быть пустым')]
        public array $orderContent
    ) {
    }
}