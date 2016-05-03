<?php

class FrameworkExceptionTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::execute( function () {
    } );

    parent::__construct( $name, $data, $dataName );
  }

  public function testData() {

    $exception = new \Framework\Exception\Runtime( 'framework#1E', [
      'code'    => 1,
      'message' => 'test1'
    ], new \Exception( 'test2', 2 ) );

    $this->assertEquals( 'framework', $exception->extension->id );
    $this->assertEquals( 'framework#1E', $exception->id );
    $this->assertEquals( \Framework::LEVEL_ERROR, $exception->level );
    $this->assertEquals( '#1: test1', $exception->getMessage() );
    $this->assertEquals( 1, $exception->getCode() );
    $this->assertEquals( 'E', $exception->type );
    $this->assertEquals( [
      'code'    => 1,
      'message' => 'test1'
    ], $exception->data );

    $this->assertTrue( \Framework\Exception\Helper::is( $exception ) );
  }

  // TODO test uncovered `Exception\Collector` and `Exception\Helper` methods
}
