<?php

namespace App\Service;

use App\Dto\EventItemDto;
use App\Provider\EventProvider;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;

readonly class EventService
{
    public function __construct(private RedisAdapter $redisAdapter)
    {
    }

    /**
     * @param string $eventName
     * @param string $message
     * @param string $source
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function addBuiltInEvent(
        string $eventName,
        string $message,
        string $source
    ): void {
        $existingEventsItem = $this->redisAdapter->getItem(EventProvider::BUILTIN_EVENTS_KEY);

        if (!$existingEventsItem->isHit()) {
            $firstEvent = $this->createEventRecord($eventName, $message, $source);
            $existingEventsItem->set([$firstEvent]);

            $this->redisAdapter->save($existingEventsItem);

            return;
        }

        $eventsList = $existingEventsItem->get();
        $eventsList[] = $this->createEventRecord($eventName, $message, $source);

        $existingEventsItem->set($eventsList);

        $this->redisAdapter->save($existingEventsItem);
    }

    private function createEventRecord(
        string $eventName,
        string $message,
        string $source
    ): EventItemDto {
        return new EventItemDto(
            event: $eventName,
            addedAt: (new \DateTime())->format('Y-m-d H:i:s'),
            message: $message,
            source: $source
        );
    }
}