## Репозиторий к [открытому уроку](https://otus.ru/lessons/symfony/#event-6488) курса [Symfony Framework](https://otus.ru/lessons/symfony/)

Автор: [Сергей Петров](mailto:cl@coders-lair.com)

# События в Symfony

### Показаны

* EventListener и EventSubscriber
* Встроенные события во фреймворке / обработчики встроенных событий
* Реализация собственного события

### Сборка проекта и зависимостей

1. Создаём и запускаем контейнеры командой `docker-compose up -d`
2. Подключаемся к контейнеру `php`: `docker exec -it pl-php-symfony bash`

Дальнейшие команды выполняются в подключённом контейнере

1. Устанавливаем зависимости: выполняем команду `composer install`
2. Выполняем миграцию для БД: `php bin/console doctrine:migrations:migrate`

### Проверка работоспособности

1. В браузере работает `http://localhost:19999/orders`
2. Из Postman-коллекции успешно работает запрос `OK /api/orders/create`

### Предварительная подготовка для отображения вызванных событий

1. Подключаемся к контейнеру `php`: `docker exec -it pl-php-symfony bash`
2. Устанавливаем пакет `composer require predis/predis`
3. В файле `.env` добавляем строку `REDIS_DSN=redis://pl-redis:6379`
4. В файле `config/packages/cache.yaml`, в секции `cache` раскомментируем и исправим строки
   ```yaml
   app: cache.adapter.redis
   default_redis_provider: '%env(REDIS_DSN)%'
   ```
5. Исправляем шаблон Twig `/templates/orders_list/index.html.twig`
    ```html
   {% extends 'base.html.twig' %}
   
   {% block title %}Список заказов{% endblock %}
   
   {% block body %}

    <div class="container">

        <div class="row">
            <div class="col">
                <h2>Список заказов</h2>
                {% if orders is empty %}
                    <h3>Заказов нет</h3>
                {% else %}
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th scope="col">orderId</th>
                                <th scope="col">Создан</th>
                                <th scope="col">Изменён</th>
                                <th scope="col">Клиент</th>
                                <th scope="col">Статус</th>
                                <th scope="col">Состав заказа</th>
                            </tr>
                            <tbody class="table-group-divider">
                            {% for orderItem in orders %}
                                <tr>
                                    <td>{{ orderItem.id }}</td>
                                    <td>{{ orderItem.createdAt }}</td>
                                    <td>{{ orderItem.updatedAt }}</td>
                                    <td>{{ orderItem.createdBy }}</td>
                                    <td>{{ orderItem.status }}</td>
                                    <td>{{ orderItem.orderContent|join(';') }}</td>
                                </tr>
                            {% endfor %}
                            </tbody>
                        </table>
                    </div>
                {% endif %}
            </div>

            <div class="col">
                <h2>События</h2>
                {% if events is empty %}
                <h3>Нет зафиксированных событий</h3>
                {% else %}
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th scope="col">Событие</th>
                            <th scope="col">Сработало</th>
                            <th scope="col">Сообщение</th>
                            <th scope="col">Источник</th>
                        </tr>
                        </thead>
                        <tbody class="table-group-divider">
                        {% for eventItem in events %}
                            <tr>
                                <td>{{ eventItem.event }}</td>
                                <td>{{ eventItem.addedAt }}</td>
                                <td>{{ eventItem.message }}</td>
                                <td>{{ eventItem.source }}</td>
                            </tr>
                        {% endfor %}
                        </tbody>
                    </table>
                    {% endif %}
                </div>
            </div>
        </div>
    </div>
   {% endblock %}
   ```
6. Создаём провайдер `App\Provider\EventProvider`
   ```php
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
   ```
7. Создаём DTO для события `App\Dto\EventItemDto`
   ```php
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
   ```
8. Создаём сервис `App\Service\EventService`
   ```php
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
   ```
9. Исправляем контроллер `App\Controller\OrdersListController`
   ```php
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
   ```
10. В файле `/config/packages/services.yaml` в секции `services` добавляем описания клиента, адаптера и провайдера
   ```yaml
   redis_client:
      class: Redis
      factory: Symfony\Component\Cache\Adapter\RedisAdapter::createConnection
      arguments:
          - '%env(REDIS_DSN)%'
   
   redis_adapter:
      class: Symfony\Component\Cache\Adapter\RedisAdapter
      arguments:
          - '@redis_client'
          - 'pl_app'
   
   App\Provider\EventProvider:
      arguments:
          $redisAdapter: '@redis_adapter'
   
   App\Service\EventService:
     arguments:
         $redisAdapter: '@redis_adapter'
   ```
11. Перезагружаем страницу `http://localhost:19999/orders`. Видим, что всё работает и список событий пока пуст
12. Подключаемся к контейнеру `pl-redis`: `docker exec -it pl-redis sh`
13. Запускаем интерфейс командной строки Redis: `redis-cli`
14. Запрашиваем все ключи в кэше и видим отсутствие записей
   ```shell
   KEYS *
   ```

### Добавляем слушателя для kernel.exception

1. Создаём шаблон для Twig `/templates/error.twig.html`
   ```html
   {% extends 'base.html.twig' %}
   
   {% block title %}Ошибка{% endblock %}
   
   {% block body %}
   
       <div class="container flex-grow-1 text-center vh-100">
           <div class="row align-items-center vh-100">
               <div class="col">
                   <h3>Произошла ошибка.</h3>
                   <h4 class="font-monospace">{{ message }}</h4>
   
               </div>
           </div>
       </div>
   {% endblock %}
   ```
2. Добавляем Event Listener `App\EventListener\KernelExceptionEventListener`
   ```php
   <?php
   
   namespace App\EventListener;
   
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Symfony\Component\HttpFoundation\JsonResponse;
   use Symfony\Component\HttpFoundation\Request;
   use Symfony\Component\HttpFoundation\Response;
   use Symfony\Component\HttpKernel\Event\ExceptionEvent;
   use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
   use Twig\Environment;
   use Twig\Error\LoaderError;
   use Twig\Error\RuntimeError;
   use Twig\Error\SyntaxError;
   
   final readonly class KernelExceptionEventListener
   {
       public function __construct(
           private Environment $twig,
           private EventService $eventService
       ) {
       }
   
       /**
        * @param ExceptionEvent $event
        * @return void
        *
        * @throws LoaderError
        * @throws RuntimeError
        * @throws SyntaxError
        * @throws InvalidArgumentException
        */
       public function onKernelException(ExceptionEvent $event): void
       {
           $exception = $event->getThrowable();
   
           $code = $this->resolveCode($exception);
   
           if ($this->isApiRequest($event->getRequest())) {
               $response = new JsonResponse(
                   [
                       'result' => false,
                       'message' => $exception->getMessage(),
                   ],
                   $code
               );
           } else {
               $response = new Response(
                   $this->twig->render('error.html.twig', ['message' => $exception->getMessage()]),
                   $code
               );
           }
   
           $event->setResponse($response);
   
           $this->eventService->addBuiltInEvent(
               'kernel.exception',
               $exception->getMessage(),
               KernelExceptionEventListener::class
           );
       }
   
       private function resolveCode(\Throwable $exception): int
       {
           if ($exception instanceof HttpExceptionInterface) {
               return $exception->getStatusCode();
           }
   
           return Response::HTTP_INTERNAL_SERVER_ERROR;
       }
   
       private function isApiRequest(Request $request): bool
       {
           return str_contains($request->getRequestUri(), '/api');
       }
   }
   ```
3. В файле `/config/packages/services.yaml` в секции `services` добавляем созданный Event Listener
   ```yaml
    App\EventListener\KernelExceptionEventListener:
        tags:
            - { name: kernel.event_listener, event: kernel.exception }
   ```
4. Пробуем отправить запрос `OK` из Postman-коллекции, видим, что всё работает успешно, записей в событиях нет
5. Пробуем отправить любой запрос с ошибкой из Postman-коллекции, видим ответ в `JSON` и новые записи для событий
6. Пробуем зайти на несуществующую страницу в браузере, видим отрендеренный в Twig шаблон с текстом ошибки
7. В контейнере Redis по запросу `KEYS *` видим наш ключ `pl_app:builtinEvents`
8. По запросу `GET pl_app:builtinEvents` видим содержимое ключа со списком добавленных событий