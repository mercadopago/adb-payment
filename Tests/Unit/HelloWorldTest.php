<?php

  namespace Tests\Unit\Mocks;

  use PHPUnit\Framework\TestCase;

  function helloWorld() {
    return 'Hello World';
  }

  class HelloWorldTest extends TestCase {

    public function test_helloWorld_returns_value_as_expected() {
      $this->assertEquals('Hello World', helloWorld());
    }

  }


