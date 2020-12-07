# yngc0der.rabbitmq

## О проекте
Модуль включает в себя обмен сообщениями в вашем php-приложении через [RabbitMQ](http://www.rabbitmq.com/) 
с использованием библиотеки [php-amqplib](http://github.com/php-amqplib/php-amqplib).

Пакет реализует шаблоны обмена сообщениями, приведенными в библиотеке [Thumper](https://github.com/php-amqplib/Thumper),
что позволяет сделать публикацию сообщений в RabbitMQ из вашего контроллера очень простой:

```php
use Bitrix\Main\DI\ServiceLocator;

$msg = ['user_id' => 1235, 'image_path' => '/path/to/new/pic.png'];
ServiceLocator::getInstance()->get('rabbitmq.upload_picture_producer')->publish(serialize($msg));
```

Для получения 50-ти сообщений из очереди `upload_pictures`, вы просто запускаете слушатель:

```php
use Bitrix\Main\DI\ServiceLocator;

$consumer = ServiceLocator::getInstance()->get('rabbitmq.upload_picture_consumer');
$consumer->consume(50);
```

Данные примеры требуют запущенного сервера RabbitMQ.

## Минимальные требования
* `php-7.1.3` или выше
* модуль `main v20.5.400` или выше

## Установка
Загрузите пакет, используя пакетный менеджер composer, либо вручную:

```bash
$ composer require yngc0der/bitrix-rabbitmq
```

Установите модуль "yngc0der.rabbitmq" в административном интерфейсе сайта `bitrix/admin/partner_modules.php`

Добавьте следующий код в ваш `init.php`:

```php
use Bitrix\Main\Loader;
use Bitrix\Main\DI\ServiceLocator;

if (Loader::includeModule('yngc0der.rabbitmq')) {
    ServiceLocator::getInstance()->get('rabbitmq.service_loader')->load();
}
```

## Использование
Конфигурация идентична родительскому пакету. Настройка производится посредством правки файлов `bitrix/.settings.php`
и `bitrix/.settings_extra.php`:

```php
return [
    'rabbitmq' => [
        'value' => [
            'connections' => [
                'default' => [
                    'host' => '172.17.0.2',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/',
                    'lazy' => false,
                    'connection_timeout' => 3.0,
                    'read_write_timeout' => 3.0,
                    'keepalive' => false,
                    'heartbeat' => 0,
                    'use_socket' => true,
                ],
            ],
            'producers' => [
                'upload_picture' => [
                    'connection' => 'default',
                    'exchange_options' => [
                        'name' => 'upload_picture',
                        'type' => 'direct',
                    ],
                ],
            ],
            'consumers' => [
                'upload_picture' => [
                    'connection' => 'default',
                    'exchange_options' => [
                        'name' => 'upload_picture',
                        'type' => 'direct',
                    ],
                    'queue_options' => [
                        'name' => 'upload_picture',
                    ],
                    'callback' => 'UploadPictureConsumer',
                ],
            ],
        ],
        'readonly' => false,
    ],
];
```

Пример обработчика сообщений:

```php
// UploadPictureConsumer.php

use Yngc0der\RabbitMq\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class UploadPictureConsumer implements ConsumerInterface
{
    public function execute(AMQPMessage $msg)
    {
        echo ' [x] Received ', $msg->body, "\n";
    }
}
```

## Интеграция с CLI
Если у вас установлен модуль [yngc0der.cli](https://github.com/yngc0der/bitrix-cli), вам будут доступны некоторые
команды, которые упрощают работу:

* `rabbitmq:consumer`        Executes a consumer
* `rabbitmq:delete`          Delete a consumer's queue
* `rabbitmq:purge`           Purge a consumer's queue
* `rabbitmq:setup-fabric`    Sets up the Rabbit MQ fabric
* `rabbitmq:stdin-producer`  Executes a producer that reads data from STDIN

## Адаптировано к Bitrix
- [x] Connection (Stream, Socket, Lazy, LazySocket)
- [x] Connection factory
- [x] Binding
- [x] Producer
- [x] Consumer
- [x] Parts holder
- [x] Fallback producer
- [ ] Multi-consumer
- [ ] Dynamic consumer
- [ ] Batch consumer
- [ ] Anon consumer
- [ ] Rpc client
- [ ] Rpc server
- [ ] Logged channel

## Credits
Модуль и документация базируется на [RabbitMqBundle](https://github.com/php-amqplib/RabbitMqBundle).
Там же вы можете найти подробную информацию о его использовании.
