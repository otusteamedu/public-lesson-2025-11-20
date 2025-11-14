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

