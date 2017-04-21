<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

class EventTest extends TestCase {

  public function testBasic() {

    $storage   = new Event\Emitter( 'test' );
    $callback1 = $this->_callback( 1 );
    $callback2 = $this->_callback( 2 );
    $callback3 = $this->_callback( 3 );

    $storage->attach( $callback1, [ 'test1', 'test2' => 1 ] );
    $storage->attach( $callback2, 'test1', Event\Emitter::PRIORITY_DEFAULT / 2 );
    $storage->attach( $callback3, [ 'test1' => 0, 'test2' => 0 ] );

    // test callback attach with priority
    $priority = [];
    $this->assertSame( [ $callback3, $callback2, $callback1 ], $storage->getCallbackList( 'test1', $priority ) );
    $this->assertSame( [ 0, Event\Emitter::PRIORITY_DEFAULT / 2, Event\Emitter::PRIORITY_DEFAULT ], $priority );
    $this->assertSame( [ $callback3, $callback1 ], $storage->getCallbackList( 'test2', $priority ) );
    $this->assertSame( [ 0, 1 ], $priority );
    $this->assertEquals( [ 'test1', 'test2' ], $storage->getEventList() );

    // test event triggering
    $event1 = $storage->trigger( new Event( 'test1', [ 'prevent' => 1 ] ) );

    $this->assertTrue( $event1->isPrevented() );
    $this->assertEquals( 1, $event1->get( 'prevented' ) );
    $this->assertEquals( 1, $event1->get( 'callback' ) );

    $event2 = $storage->trigger( new Event( 'test2', [ 'prevent' => 0 ] ) );

    $this->assertFalse( $event2->isPrevented() );
    $this->assertEquals( null, $event2->get( 'prevented' ) );
    $this->assertEquals( 1, $event2->get( 'callback' ) );

    // test callback detach
    $storage->detach( 'test1', $callback2 );
    $this->assertSame( [ $callback3, $callback1 ], $storage->getCallbackList( 'test1' ) );

    $storage->detach( 'test2' );
    $this->assertSame( [], $storage->getCallbackList( 'test2' ) );
    $this->assertSame( [ 'test1' ], $storage->getEventList() );
  }

  /**
   * Create a callback with specific name
   *
   * @param int $number
   *
   * @return callable
   */
  private function _callback( $number ) {
    return function ( EventInterface $event ) use ( $number ) {
      $event[ 'callback' ] = $number;

      if( $event->get( 'prevent' ) == $number ) {
        $event->setPrevented();
        $event[ 'prevented' ] = $number;
      }
    };
  }
}
