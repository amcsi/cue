cue
===

A dead-simple PHP router.

Usage
=====

```php
<?php

function home() {
    return "Hello world!";
}

$router = new Cue\Router(['/' => ['home']);
$router->invoke('/'); // "Hello world!"
```