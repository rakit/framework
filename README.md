Rakit Framework
=================

Rakit Framework adalah _micro PHP framework_ yang dikembangkan untuk menangani website skala kecil - enterprise. 
Framework ini terinspirasi penuh oleh Laravel/Lumen framework, hanya saja dengan size yang terbilang cukup ringan (dibawah 500KB).
_Micro Framework_ sendiri berarti Rakit Framework tidak membatasi developer dalam membangun struktur aplikasinya, struktur dapat dibuat mengadaptasi MVC seperti gaya Codeigniter, Laravel/Lumen, dsb.

Saat ini Rakit Framework masih dalam tahap pengembangan.

## Features

* RESTful Routing
* Route Middleware (with params)
* Hook
* Automatic Resolution (Constructor Injection and Callable Injection)
* Lazy loading
* Easy file upload

## Basic Examples

#### Hello World

```php
<?php // index.php (at root project)

require("vendor/autoload.php");

use Rakit\Framework\App;

$app = new App('MyAwesomeApp');

$app->get('/', function() {
    return "Hello World!";
});

$app->run();

```

#### Json Response

Untuk mengirimkan JSON, cukup return sebuah array dari controller/middleware.

```php
<?php // index.php (at root project)

require("vendor/autoload.php");

use Rakit\Framework\App;

$app = new App('MyAwesomeApp');

$app->get('/', function() {
    return [
        'status' => 'ok',
        'message' => 'Hello World'
    ];
});

$app->run();

```

#### Route Parameter

```php
<?php // index.php (at root project)

require("vendor/autoload.php");

use Rakit\Framework\App;

$app = new App('MyAwesomeApp');

$app->get('/hello/:name', function($name) {
    return "Hello {$name}";
});

$app->run();

```

#### Route Optional Parameter

```php
<?php // index.php (at root project)

require("vendor/autoload.php");

use Rakit\Framework\App;

$app = new App('MyAwesomeApp');

$app->get('/hello/:name(/:age)', function($name, $age = 18) {
    return "My name is {$name}, i am {$age} years old";
});

$app->run();

```

#### Basic Middleware

Untuk mendaftarkan middleware, dapat menggunakan method `middleware($name, $callable)`.
Untuk menggunakannya, kamu dapat menyisipkan nama middleware tersebut diantara `path` dan `action controller`. 

```php
<?php // index.php (at root project)

require("vendor/autoload.php");

use Rakit\Framework\App;

$app = new App('MyAwesomeApp');

$app->middleware('auth', function($req, $res, $next) {
    if(!isset($_SESSION['user'])) {
        return $res->send("Mesti login dulu om", 403);
    }

    return $next();
});

$app->get('/admin', ['auth'], function() {
    return "Admin Page";
});

$app->run();
```


#### Using Middleware for Manipulate Response

Contoh dibawah ini adalah penggunaan middleware dalam memanipulasi response body.

```php
<?php // index.php (at root project)

require("vendor/autoload.php");

use Rakit\Framework\App;

$app = new App('MyAwesomeApp');

$app->middleware('uppercase', function($req, $res, $next) {
    $next();
    return strtoupper($res->body);
});

$app->get('/', ['uppercase'], function() {
    return "Hello World!";
});

$app->run();
```

Contoh diatas jika dijalankan akan menampilkan "HELLO WORLD!"


#### Middleware with parameters

```php
<?php // index.php (at root project)

require("vendor/autoload.php");

use Rakit\Framework\App;

$app = new App('MyAwesomeApp');

$app->middleware('auth', function($req, $res, $next, $tipe) {
    if(!isset($_SESSION['user']) OR $tipe !== $_SESSION['user']['tipe']) {
        return $res->send("Mesti login sebagai {$tipe} dulu om", 403);
    }

    return $next();
});

$app->get('/siswa', ['auth:siswa'], function() {
    return "Admin Page";
});

$app->run();
```

#### Callable injection

Pada dasarnya hampir semua class dan callable di Rakit Framework injectable. Dalam artian secara otomatis
Rakit Framework akan menginject dependency ke dalam parameter constructor(jika berupa class) atau callable tersebut.

Dibawah ini adalah contoh inject Request dan Response object ke dalam callable action controller.

```php
<?php // index.php (at root project)

require("vendor/autoload.php");

use Rakit\Framework\App;
use Rakit\Framework\Http\Request;
use Rakit\Framework\Http\Response;

$app = new App('MyAwesomeApp');

$app->post('/account', function(Request $request, Response $response) {
    $nama = $request->get("nama");
    $uploaded_foto = $request->file("foto");
    $uploaded_foto->move("./uploads");

    // ...

    return $response->json([
        'status' => 'ok',
        'message' => 'blah blah blah'
    ]);
});

$app->run();
```

> Object yang dapet diinject kedalam constructor atau callable adalah Object yang terdaftar dalam container aplikasi.
(baca: mendaftarkan object ke dalam container)


## Coming soon

Saat ini rakit framework masih dalam pengembangan, beberapa test belum ditambahkan. 