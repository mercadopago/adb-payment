<?php

  function helloWorld() {
    return 'Hello World';
  }

  use PHPUnit\Framework\TestCase;

  class HelloWorldTest extends TestCase {

    public function test_helloWorld_returns_value_as_expected() {
      $this->assertEquals('Hello World', helloWorld());
    }

  }


