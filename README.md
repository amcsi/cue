cue
===

A dead-simple PHP router.

The core of Cue's responsibilities include mapping a string identifier (URL), to an array of expressions.
Each expression must evaluate to [valid callable syntax](http://www.php.net/manual/en/language.types.callable.php).

Cue does not explicitly know or care about http status codes, authorization, filters, or any other popular features
provided by other router vendors. However, callables can be defined so that those features may be implemented by the
underlying framework. This allows for more internal cohesion in existing applications that wish to add routing
functionality, and to minimize the amount of work and assumptions that the routing code must make.

Usage
=====

This is the most stripped down, basic usage:

```php
<?php

function home() {
    return "Hello world!";
}

$routes = ['/' => 'home'];
$router = new Cue\Router($routes);
echo $router->invoke('/'); // "Hello world!"
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
"*":
    - ["ErrorController", "error404"]
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
        throw new Exception("not found", 404);
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