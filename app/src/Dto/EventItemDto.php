<?php

namespace App\Dto;

readonly class EventItemDto
{
    public function __construct(
        public string $event,
        public string $addedAt,
        public string $message,
        public string $source
    ) {
    }
}