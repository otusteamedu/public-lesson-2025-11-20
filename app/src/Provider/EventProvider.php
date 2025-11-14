<?php

namespace App\Provider;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;

readonly class EventProvider
{
    public const BUILTIN_EVENTS_KEY = 'builtinEvents';

    public function __construct(
        private RedisAdapter $redisAdapter
    ) {
    }

    /**
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getEvents(): array
    {
        $cachedEventsItem = $this->redisAdapter->getItem(self::BUILTIN_EVENTS_KEY);

        return (!$cachedEventsItem->isHit()) ? [] : $cachedEventsItem->get();
    }
}