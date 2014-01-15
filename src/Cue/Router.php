<?php

namespace Cue;

use ReflectionMethod;

class Router {

    protected $routes = [];
    protected $uriArgs = [];
    protected $state = null;

    public function __construct($routes, $state = null) {
        $this->routes = $routes;
        $this->state = $state;
    }

    public function match($uri) {
        $parts = explode("/", substr($uri, 1));
        foreach($this->routes as $route => $callable) {
            $this->uriArgs = [];
            $tok = strtok($route, "/");
            $i = 0;
            $match = false;
            if(($route === "/" && $parts[$i] === "") || $tok !== false) {
                $match = true;
                while($tok !== false) {
                    if(substr($tok, 0, 1) === "*" && isset($parts[$i])) {
                        $this->uriArgs[] = $parts[$i];
                    } else if(!isset($parts[$i]) || $tok != $parts[$i]) {
                        $match = false;
                        break;
                    }
                    $i++;
                    $tok = strtok("/");
                }
            }
            if($match) {
                return $callable;
            }
        }
        return [];
    }

    public function invoke($uri) {
        $callables = $this->match($uri);
        $buffer = "";
        if($callables) {
            foreach($callables as $callable) {
                $args = $this->uriArgs;
                if(class_exists($callable[0])) {
                    $ref = new ReflectionMethod($callable[0], $callable[1]);
                    if(!$ref->isStatic()) {
                        $callable[0] = new $callable[0]($this->state);
                    } else {
                        array_unshift($args, $this->state);
                    }
                } else {
                    array_unshift($args, $this->state);
                }
                $buffer .= call_user_func_array($callable, $args);
            }
        }
        return $buffer;
    }

    public function getUriArgs() {
        return $this->uriArgs;
    }

    public function getState() {
        return $this->state;
    }

    public function setState($state) {
        $this->state = $state;
    }
}
