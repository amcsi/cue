<?php

namespace Cue;

class TestCaseController {

    protected $state = [];

    public function __construct($state) {
        $this->state = $state;
    }

    public function helloWorld() {
        return "Hello world";
    }

    public function admin() {
        return "This is the admin section";
    }

	public function baz() {
		return $this->state['baz'];
	}

	public static function foo($applicationState) {
		return $applicationState['foo'];
	}

    public function error404() {
        http_response_code(404);
        return "404";
    }

    public function userProfile($username) {
        return $username." profile";
    }
}

function hypotheticalSecurityCheck($applicationState) {
	if(!$applicationState['user_authenticated']) {
		throw new \Exception("You are not authorized to do that.");
	}
}

class RouterTest extends \PHPUnit_Framework_TestCase {

    protected $uris = [];
    protected $router = null;
	protected $applicationState = [];

    public function setup() {

        $this->uris = array(
            '/' => [
                ['Cue\TestCaseController', 'helloWorld']
            ],
            '/user/*username/profile' => [
                ['Cue\TestCaseController', 'userProfile']
            ],
            '/admin' => [
                'Cue\hypotheticalSecurityCheck',
                ['Cue\TestCaseController', 'admin']
            ],
			'/foo' => [
				['Cue\TestCaseController', 'foo']
			],
			'/baz' => [
				['Cue\TestCaseController', 'baz']
			],
            '*' => [
                ['Cue\TestCaseController', 'error404']
            ]
        );

        $this->applicationState['user_authenticated'] = false;
		$this->applicationState['foo'] = 'bar';
		$this->applicationState['baz'] = 'quz';

        $this->router = new Router($this->uris, $this->applicationState);
    }

    public function testHelloWorld() {
        $this->assertEquals($this->router->match("/"), $this->uris["/"]);
        $this->assertEquals($this->router->invoke("/"), "Hello world");
		$this->assertEquals($this->router->getState(), $this->applicationState);
    }

    /**
     * @expectedException \Exception
     */
    public function testFailAdminAccess() {
        $this->router->invoke("/admin");
    }

    public function testSuccessAdminAccess() {
        $this->router->setState(['user_authenticated' => true]);
        $this->assertEquals($this->router->invoke("/admin"), "This is the admin section");
    }

    public function test404() {
        $response = $this->router->invoke("/this-url-does-not-exist");
        $this->assertEquals($this->router->getUriArgs(), ["this-url-does-not-exist"]);
        $this->assertEquals($response, "404");
        $this->assertEquals(http_response_code(), 404);
    }

    public function testCaptureVariable() {
        $this->assertEquals($this->router->invoke("/user/john/profile"), "john profile");
    }

	public function testNoMatch() {
		$router = new Router([]);
		$this->assertEquals([], $router->match("/"));
		$this->assertEquals("", $router->invoke("/"));
	}

	public function testStaticCall() {
		$this->assertEquals($this->applicationState['foo'], $this->router->invoke("/foo"));
	}
}
 