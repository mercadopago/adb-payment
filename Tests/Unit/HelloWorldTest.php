<?php

  namespace Tests\Unit\Mocks;

  use PHPUnit\Framework\TestCase;

  function helloWorld() {
    return 'Hello World';
  }

  class HelloWorldTest extends TestCase {

    public function testHelloWorldReturnsValueAsExpected() {
      $this->assertEquals('Hello World', helloWorld());
    }

  }


