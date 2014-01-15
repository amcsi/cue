cue
===

A dead-simple PHP router.

Usage
=====

This is the most stripped down, basic usage:

```php
<?php

function home() {
    return "Hello world!";
}

$routes = ['/' => ['home']];
$router = new Cue\Router($routes);
$router->invoke('/'); // "Hello world!"
```

Here's a slightly more complete example -- first define your routes. This can be done with PHP arrays, or parsed JSON/YAML/etc:

```php
<?php

$routes = yaml_parse('
"/":
    - ["HomeController", "index"]
"/*user/profile":
    - ["UserController", "profile"]
"/admin":
    - ["authenticate"]
    - ["AdminController", "index"]
"/admin/logs":
    - ["authenticate"]
    - ["AdminController", "logs"]
');
```

Now, define your route callables:

```php
<?php

function authenticate($applicationState) {
    if(!$applicationState['isAdmin']) {
        throw new Exception("not allowed", 401);
    }
}

class HomeController {

    protected $foo = '';

    public function __construct($applicationState) {
        $this->foo = $applicationState['foo'];
    }

    public function index() {
        return "Home ".$this->foo;
    }
}

class AdminController {
    public function index() {
        return "Admin index";
    }

    public function logs() {
        return "Admin logs";
    }
}

class UserController {
    public function profile($user) {
        return $user."'s profile";
    }
}

class ErrorController {
    public function error404() {
        http_response_code(404);
        return "404 not found";
    }

    public static function exceptionHandler(Exception $e) {
        http_response_code($e->getCode());
        return $e->getMessage();
    }
}
```

Finally, invoke your URI:

```php
<?php

$applicationState = [
    'isAdmin' => false,
    'foo' => 'bar'
];

require_once("vendor/autoload.php");
$router = new Cue\Router($routes, $applicationState);
try {
    echo $router->invoke($_SERVER['REQUEST_URI']);
} catch(Exception $e) {
    echo ErrorController::exceptionHandler($e);
}
```