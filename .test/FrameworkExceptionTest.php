<?php

use Framework\Exception;
use Framework\Exception\Helper;
use Framework\Exception\Collector;

class FrameworkExceptionTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::setup( \Framework::ENVIRONMENT_DEVELOPMENT ) && \Framework::execute( function () { } );
    
    parent::__construct( $name, $data, $dataName );
  }

  public function testBasic() {

    $previous  = new \Exception( 'test2', 2 );
    $exception = new \Framework\Exception\Runtime( 'framework#1E', [
      'code'    => 1,
      'message' => 'test1'
    ], $previous );

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
    $this->assertEquals( "framework#1E: '#1: test1'", (string) $exception );

    // test conversion
    $this->assertEquals( (object) [
      'id'        => 'framework#1E',
      'code'      => 1,
      'extension' => 'framework',
      'message'   => '#1: test1',
      'data'      => [
        'code'    => 1,
        'message' => 'test1'
      ]
    ], $exception->toObject() );
  }

  public function testHelper() {

    $previous  = new \Exception( 'test2', 2 );
    $exception = new \Framework\Exception\Runtime( 'framework#1E', [
      'code'    => 1,
      'message' => 'test1'
    ], $previous );

    $this->assertEquals( $exception, Helper::wrap( $exception ) );
    $this->assertEquals( [ 'code' => 2, 'message' => 'test2' ], Helper::wrap( $previous )->data );
    $this->assertTrue( Helper::is( $exception ) );

    // test for id matching
    $this->assertTrue( Helper::match( $exception, 'framework' ) );
    $this->assertTrue( Helper::match( $exception, 'framework#1' ) );
    $this->assertTrue( Helper::match( $exception, 'framework#1E' ) );

    $this->assertFalse( Helper::match( $exception, 'otherextension' ) );
    $this->assertFalse( Helper::match( $exception, 'framework#2' ) );
    $this->assertFalse( Helper::match( $exception, '#2' ) );
    $this->assertFalse( Helper::match( $exception, 'framework#1N' ) );
  }

  public function testCollector() {

    // prepare the tests
    $collector = new Collector();
    $list      = [
      new Exception\Runtime( 'test#1W' ),
      new Exception\Strict( 'test#2N' ),
      new Exception\Runtime( 'test#3E' ),

      new Exception\Strict( 'test2#1E' ),
      new Exception\Runtime( 'test2#1N' )
    ];
    foreach( $list as $exception ) {
      $collector->add( $exception );
    }

    // test collector iterations
    $this->assertEquals( count( $list ), count( $collector ) );
    foreach( $collector as $i => $exception ) {
      $this->assertEquals( $list[ $i ], $exception );
    }

    // test the existance search
    $this->assertTrue( $collector->exist( 'test2' ) );                                 // namespace
    $this->assertFalse( $collector->exist( 'test3' ) );

    $this->assertTrue( $collector->exist( 'test#1' ) );                                // namespace with code
    $this->assertFalse( $collector->exist( 'test#6' ) );

    $this->assertTrue( $collector->exist( 'test#3', \Framework::LEVEL_WARNING ) );     // namespace and code with level
    $this->assertTrue( $collector->exist( 'test#3', \Framework::LEVEL_ERROR ) );
    $this->assertFalse( $collector->exist( 'test#2', \Framework::LEVEL_CRITICAL ) );
    $this->assertFalse( $collector->exist( null, \Framework::LEVEL_CRITICAL ) );

    // test exception getters
    $this->assertEquals( $list[ 4 ], $collector->get() );
    $this->assertEquals( $list[ 3 ], $collector->get( true ) );

    $this->assertEquals( [ $list[ 0 ], $list[ 1 ], $list[ 2 ] ], $collector->getList( 'test' ) );
    $this->assertEquals( [ $list[ 0 ] ], $collector->getList( 'test#1' ) );
    $this->assertEquals( [ $list[ 2 ], $list[ 3 ] ], $collector->getList( null, \Framework::LEVEL_ERROR ) );
  }
}
