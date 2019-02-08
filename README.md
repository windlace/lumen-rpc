# Install

``` bash
composer require cast/lumen-rpc
```

# Usage

Create config file `rpc.php` like this:

```php
<?php

return [
    'config' =>  [
        'my-method',
    ]
];

```

Start a server:

```php
Rpc::listen('my-method', function ($msg) {
    // do work...
    return $msg['number'] * 2;
});

```

Make some RPC-call from remote:

```php
$res = Rpc::get('my-method', ['number'=>5]);

var_dump($res);
```

Will gets:

```
int(10)
```

Make sure you have correct config for RabbitMQ, see it in config/queue.php
