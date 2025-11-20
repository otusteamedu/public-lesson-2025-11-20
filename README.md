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

### Событие kernel.exception

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

### Событие kernel.view

1. Создаём класс `App\Response\ApiResponse`
   ```php
   <?php
   
   namespace App\Response;
   
   readonly class ApiResponse
   {
       public function __construct(
           public bool $result,
           public mixed $data,
           public ?string $message,
           public int $code
       ) {
       }
   
       public static function createSuccess(mixed $data, ?string $message, int $code): ApiResponse
       {
           return new self(true, $data, $message, $code);
       }
   
       public static function createError(mixed $data, ?string $message, int $code): ApiResponse{
           return new self(false, $data, $message, $code);
       }
   }
   ```
2. Создаём класс-слушатель события `App\EventListener\KernelViewEventListener`
   ```php
   <?php
   
   namespace App\EventListener;
   
   use App\Response\ApiResponse;
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Symfony\Component\HttpFoundation\JsonResponse;
   use Symfony\Component\HttpKernel\Event\ViewEvent;
   use Symfony\Component\Serializer\Encoder\JsonEncoder;
   use Symfony\Component\Serializer\Exception\ExceptionInterface;
   use Symfony\Component\Serializer\SerializerInterface;
   
   final readonly class KernelViewEventListener
   {
       public function __construct(
           private EventService $eventService,
           private SerializerInterface $serializer
       ) {
       }
   
       /**
        * @param ViewEvent $event
        * @return void
        * 
        * @throws InvalidArgumentException
        * @throws ExceptionInterface
        */
       public function onKernelView(ViewEvent $event): void
       {
           $controllerResult = $event->getControllerResult();
   
           if ($controllerResult instanceof ApiResponse) {
               $jsonResponse = new JsonResponse(
                   data: $this->serializer->serialize($controllerResult, JsonEncoder::FORMAT),
                   status: $controllerResult->code,
                   json: true
               );
   
               $event->setResponse($jsonResponse);
           }
   
           $this->eventService->addBuiltInEvent('kernel.view', '', KernelViewEventListener::class);
       }
   }
   ```
3. Исправляем методы `__construct` и `onKernelException` класса `App\EventListener\KernelExceptionEventListener`:
   ```php
   public function __construct(
   private Environment $twig,
   private EventService $eventService,
   private SerializerInterface $serializer
   ) {
   }

    /**
     * @param ExceptionEvent $event
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ExceptionInterface
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $code = $this->resolveCode($exception);

        if ($this->isApiRequest($event->getRequest())) {
            $apiResponse = ApiResponse::createError(null, $exception->getMessage(), $code);

            $response = new JsonResponse(
                data: $this->serializer->serialize($apiResponse, JsonEncoder::FORMAT),
                status: $code,
                json: true
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
   ```
4. Исправляем контроллер `App\Controller\CreateOrderApiController`:
   ```php
   <?php
   
   namespace App\Controller;
   
   use App\Dto\CreateOrderRequestDto;
   use App\Response\ApiResponse;
   use App\Service\OrderService;
   use Symfony\Component\HttpFoundation\Response;
   use Symfony\Component\HttpKernel\Attribute\AsController;
   use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
   use Symfony\Component\Routing\Attribute\Route;
   
   #[AsController]
   final class CreateOrderApiController
   {
       #[Route(path: '/api/orders/create', methods: ['POST'])]
       public function __invoke(
           #[MapRequestPayload] CreateOrderRequestDto $createOrderRequestDto,
           OrderService $orderService
       ): ApiResponse {
           return ApiResponse::createSuccess(
               data: [
                   'orderId' => $orderService->createOrder($createOrderRequestDto)
               ],
               message: null,
               code: Response::HTTP_CREATED
           );
       }
   }
   ```
5. В файле `/config/packages/services.yaml` в секции `services` добавляем созданный Event Listener
   ```yaml
    App\EventListener\KernelViewEventListener:
        tags:
            - { name: kernel.event_listener, event: kernel.view }
   ```

### Событие kernel.request

1. Создаём перечисление `App\Request\RequestAttributesEnum`
   ```php
   <?php
   
   namespace App\Request;
   
   enum RequestAttributesEnum: string
   {
       case IS_API_REQUEST = 'is_api_request';
   }
   ```
2. Создаём трейт `App\Request\ApiRequestCheckTrait`
   ```php
   <?php
   
   namespace App\Request;
   
   use Symfony\Component\HttpFoundation\Request;
   
   trait ApiRequestCheckTrait
   {
       private function isApiRequest(Request $request):bool
       {
           return $request->attributes->get(RequestAttributesEnum::IS_API_REQUEST->value, false);
       }
   }
   ```
3. Создаём класс-слушатель события `App\EventListener\KernelRequestEventListener`
   ```php
   <?php
   
   namespace App\EventListener;
   
   use App\Request\RequestAttributesEnum;
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Symfony\Component\HttpFoundation\Request;
   use Symfony\Component\HttpKernel\Event\RequestEvent;
   
   final readonly class KernelRequestEventListener
   {
       public function __construct(private EventService $eventService)
       {
       }
   
       /**
        * @param RequestEvent $event
        * @return void
        *
        * @throws InvalidArgumentException
        */
       public function onKernelRequest(RequestEvent $event): void
       {
           $event->getRequest()->attributes->set(
               key: RequestAttributesEnum::IS_API_REQUEST->value,
               value: $this->isApiRequest($event->getRequest())
           );
   
           $this->eventService->addBuiltInEvent('kernel.request', '', KernelRequestEventListener::class);
       }
   
       private function isApiRequest(Request $request): bool
       {
           return str_contains($request->getRequestUri(), '/api');
       }
   }
   ```
4. В файле `/config/packages/services.yaml` в секции `services` добавляем созданный Event Listener
   ```yaml
    App\EventListener\KernelRequestEventListener:
        tags:
            - { name: kernel.event_listener, event: kernel.request }
   ```
5. Исправляем слушатель `App\EventListener\KernelExceptionEventListener`
   ```php
   <?php
   
   namespace App\EventListener;
   
   use App\Request\ApiRequestCheckTrait;
   use App\Response\ApiResponse;
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Symfony\Component\HttpFoundation\JsonResponse;
   use Symfony\Component\HttpFoundation\Response;
   use Symfony\Component\HttpKernel\Event\ExceptionEvent;
   use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
   use Symfony\Component\Serializer\Encoder\JsonEncoder;
   use Symfony\Component\Serializer\Exception\ExceptionInterface;
   use Symfony\Component\Serializer\SerializerInterface;
   use Twig\Environment;
   use Twig\Error\LoaderError;
   use Twig\Error\RuntimeError;
   use Twig\Error\SyntaxError;
   
   final readonly class KernelExceptionEventListener
   {
       use ApiRequestCheckTrait;
   
       public function __construct(
           private Environment $twig,
           private EventService $eventService,
           private SerializerInterface $serializer
       ) {
       }
   
       /**
        * @param ExceptionEvent $event
        * @return void
        *
        * @throws InvalidArgumentException
        * @throws LoaderError
        * @throws RuntimeError
        * @throws SyntaxError
        * @throws ExceptionInterface
        */
       public function onKernelException(ExceptionEvent $event): void
       {
           $exception = $event->getThrowable();
   
           $code = $this->resolveCode($exception);
   
           if ($this->isApiRequest($event->getRequest())) {
               $apiResponse = ApiResponse::createError(null, $exception->getMessage(), $code);
   
               $response = new JsonResponse(
                   data: $this->serializer->serialize($apiResponse, JsonEncoder::FORMAT),
                   status: $code,
                   json: true
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
   }
   ```

### Событие kernel.controller

1. Создаём класс-слушатель события `App\EventListener\KernelControllerEventListener`
   ```php
   <?php
   
   namespace App\EventListener;
   
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Symfony\Component\HttpKernel\Event\ControllerEvent;
   
   class KernelControllerEventListener
   {
       public function __construct(private EventService $eventService)
       {
       }
   
       /**
        * @param ControllerEvent $event
        * @return void
        *
        * @throws InvalidArgumentException
        */
       public function onKernelController(ControllerEvent $event): void
       {
           $controllerData = $event->getController();
   
           if (!is_array($controllerData)) {
               $message = (new \ReflectionClass($controllerData))->getShortName();
           }
           else {
               $message = implode(':', array_keys($controllerData));
           }
   
           $this->eventService->addBuiltInEvent(
               eventName: 'kernel.controller',
               message: $message,
               source: KernelControllerEventListener::class
           );
       }
   }
   ```
2. В файле `/config/packages/services.yaml` в секции `services` добавляем созданный Event Listener
   ```yaml
    App\EventListener\KernelControllerEventListener:
        tags:
            - { name: kernel.event_listener, event: kernel.controller }
   ```

### Событие kernel.controller_arguments

1. Создаём класс-слушатель события `App\EventListener\KernelControllerArgumentsEventListener`
   ```php
   <?php
   
   namespace App\EventListener;
   
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
   use Symfony\Component\Serializer\Encoder\JsonEncoder;
   use Symfony\Component\Serializer\Exception\ExceptionInterface;
   use Symfony\Component\Serializer\SerializerInterface;
   
   final readonly class KernelControllerArgumentsEventListener
   {
       public function __construct(
           private EventService $eventService,
           private SerializerInterface $serializer
       ) {
       }
   
       /**
        * @param ControllerArgumentsEvent $event
        * @return void
        *
        * @throws InvalidArgumentException
        * @throws ExceptionInterface
        */
       public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
       {
           $namedArguments = $event->getRequest()->attributes->all();
           $controllerArguments = $event->getArguments();
   
           $this->eventService->addBuiltInEvent(
               eventName: 'kernel.controller_arguments',
               message: $this->serializer->serialize($namedArguments, JsonEncoder::FORMAT),
               source: KernelControllerArgumentsEventListener::class
           );
       }
   }
   ```
2. В файле `/config/packages/services.yaml` в секции `services` добавляем созданный Event Listener
   ```yaml
    App\EventListener\KernelControllerArgumentsEventListener:
        tags:
            - { name: kernel.event_listener, event: kernel.controller_arguments }
   ```

### Реализация argument_value_resolver

1. Создаём DTO
   ```php
   <?php
   
   namespace App\Dto;
   
   use Symfony\Component\Validator\Constraints as Assert;
   
   readonly class UpdateStatusOrderRequestDto
   {
       public function __construct(
           #[Assert\Positive(message: 'Идентификатор заказа должен быть больше нуля')]
           public int $orderId,
   
           #[Assert\NotBlank]
           public string $status
       ) {
       }
   }
   ```
2. Добавляем метод `updateOrder` в репозиторий `App\Repository\OrderEntityRepository`
   ```php
    public function updateOrder(OrderEntity $order):void
    {
        /*
         * Здесь ещё какая-нибудь обработка
         */

        //  ...

        $this->getEntityManager()->flush();
    }
   ```
3. Добавляем метод `updateOrder` в репозиторий `App\Service\updateOrder`
   ```php
    public function updateOrder(UpdateStatusOrderRequestDto $dto): void
    {
        $order = $this->orderEntityRepository->find($dto->orderId);

        if (empty($order)) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        $order->setStatus($dto->status);

        $this->orderEntityRepository->updateOrder($order);
    }
   ```
4. Добавляем Resolver `App\ArgumentValueResolver\UpdateStatusOrderRequestDtoResolver`
   ```php
   <?php
   
   namespace App\ArgumentValueResolver;
   
   use App\Dto\UpdateStatusOrderRequestDto;
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Symfony\Component\HttpFoundation\Request;
   use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
   use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
   use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
   use Symfony\Component\Serializer\Encoder\JsonEncoder;
   use Symfony\Component\Serializer\Exception\ExceptionInterface;
   use Symfony\Component\Serializer\SerializerInterface;
   use Symfony\Component\Validator\Exception\ValidatorException;
   use Symfony\Component\Validator\Validator\ValidatorInterface;
   
   #[AsTargetedValueResolver('update_status_order_request')]
   final readonly class UpdateStatusOrderRequestDtoResolver implements ValueResolverInterface
   {
       public function __construct(
           private SerializerInterface $serializer,
           private ValidatorInterface $validator,
           private EventService $eventService
       ) {
       }
   
       /**
        * @param Request $request
        * @param ArgumentMetadata $argument
        * @return iterable
        *
        * @throws ExceptionInterface
        * @throws InvalidArgumentException
        */
       public function resolve(Request $request, ArgumentMetadata $argument): iterable
       {
           $this->eventService->addBuiltInEvent(
               eventName: 'kernel.argument_value_resolver',
               message: $argument->getType(),
               source: UpdateStatusOrderRequestDtoResolver::class
           );
   
           if ($argument->getType() !== UpdateStatusOrderRequestDto::class) {
               return [];
           }
   
           $dto = $this->serializer->deserialize(
               data: $request->getContent(),
               type: UpdateStatusOrderRequestDto::class,
               format: JsonEncoder::FORMAT
           );
   
           $validationErrors = $this->validator->validate($dto);
           if (count($validationErrors) > 0) {
               $violations = [];
               foreach ($validationErrors as $violation) {
                   $violations[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
               }
   
               throw new ValidatorException(implode("; ", $violations));
           }
   
           yield $dto;
       }
   }
   ```
5. Добавляем контроллер `App\Controller\UpdateOrderStatusApiController`
   ```php
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
   ```
   
### Событие kernel.response

1. Создаём класс-слушатель события `App\EventListener\KernelResponseEventListener`
   ```php
   <?php
   
   namespace App\EventListener;
   
   use App\Service\EventService;
   use Symfony\Component\HttpKernel\Event\ResponseEvent;
   
   final readonly class KernelResponseEventListener
   {
       public function __construct(private EventService $eventService)
       {
       }
        
       /**
        * @param ResponseEvent $event
        * @return void
        * 
        * @throws InvalidArgumentException
        */
       public function onKernelResponse(ResponseEvent $event): void
       {
           $event->getResponse()->headers->set('PL-App-Custom-Header', uniqid());
   
           $this->eventService->addBuiltInEvent(
               eventName: 'kernel.response',
               message: '',
               source: KernelResponseEventListener::class
           );
       }
   }
   ```
2. В файле `/config/packages/services.yaml` в секции `services` добавляем созданный Event Listener
   ```yaml
    App\EventListener\KernelResponseEventListener:
        tags:
            - { name: kernel.event_listener, event: kernel.response }
   ```
3. В заголовках ответов видим наш заголовок `PL-App-Custom-Header`

### Событие kernel.terminate

1. Создаём класс-слушатель события `App\EventListener\KernelTerminateEventListener`
   ```php
   <?php
   
   namespace App\EventListener;
   
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Psr\Log\LoggerInterface;
   use Symfony\Component\HttpKernel\Event\TerminateEvent;
   
   final readonly class KernelTerminateEventListener
   {
       public function __construct(
           private EventService $eventService,
           private LoggerInterface $logger
       ) {
       }
   
       /**
        * @param TerminateEvent $event
        * @return void
        *
        * @throws InvalidArgumentException
        */
       public function onKernelTerminate(TerminateEvent $event): void
       {
           $this->eventService->addBuiltInEvent(
               eventName: 'kernel.terminate',
               message: '',
               source: KernelTerminateEventListener::class
           );
   
           $this->logger->notice('kernel.terminate event triggered', ['eventName' => KernelTerminateEventListener::class]);
       }
   }
   ```
2. В файле `/config/packages/services.yaml` в секции `services` добавляем созданный Event Listener
   ```yaml
    App\EventListener\KernelTerminateEventListener:
        tags:
            - { name: kernel.event_listener, event: kernel.terminate }
   ```
3. Выполняем любой запрос и в лог-файле видим запись о событии
4. В контейнере Redis просматриваем события и видим, что так же появилась запись о событии

### Собственное событие

1. Создаём класс `App\Event\OrderCreatedEvent`
   ```php
   <?php
   
   namespace App\Event;
   
   use Doctrine\Common\Collections\Order;
   use Symfony\Contracts\EventDispatcher\Event;
   
   final class OrderCreatedEvent extends Event
   {
       public function __construct(private Order $order)
       {
       }
   
       public function getOrder(): Order
       {
           return $this->order;
       }
   }
   ```
2. Создаём класс `App\EventSubscriber\OrderEventsSubscriber`
   ```php
   <?php
   
   namespace App\EventSubscriber;
   
   use App\Event\OrderCreatedEvent;
   use App\Service\EventService;
   use Psr\Cache\InvalidArgumentException;
   use Symfony\Component\EventDispatcher\EventSubscriberInterface;
   
   class OrderEventsSubscriber implements EventSubscriberInterface
   {
       public function __construct(private EventService $eventService)
       {
       }
   
       public static function getSubscribedEvents(): array
       {
           return [
               OrderCreatedEvent::class => ['onOrderCreated'],
           ];
       }
   
       /**
        * @param OrderCreatedEvent $event
        * @return void
        *
        * @throws InvalidArgumentException
        */
       public function onOrderCreated(OrderCreatedEvent $event): void
       {
           $this->eventService->addBuiltInEvent(
               eventName: 'onOrderCreated',
               message: sprintf('order [%d] created', $event->getOrder()->getId()),
               source: OrderEventsSubscriber::class
           );
       }
   }
   ```
3. Добавляем инъекцию `Symfony\Component\EventDispatcher\EventDispatcherInterface` в сервис `App\Service\OrderService`:
   `private EventDispatcherInterface $eventDispatcher`
4. Исправляем метод `createOrder` в сервисе `App\Service\OrderService`:
   ```php
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

        $orderCreatedEvent = new OrderCreatedEvent($order);

        $this->eventDispatcher->dispatch($orderCreatedEvent);

        return $order->getId();
    }
   ```
   

