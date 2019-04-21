<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

class EventTest extends TestCase {

  public function testEmitter() {

    $storage   = new Event\Emitter();
    $callback1 = $this->_callback( 1 );
    $callback2 = $this->_callback( 2 );
    $callback3 = $this->_callback( 3 );

    $storage->attach( $callback1, Event\Emitter::PRIORITY_NORMAL );
    $storage->attach( $callback2, Event\Emitter::PRIORITY_LOW2 );
    $storage->attach( $callback3, Event\Emitter::PRIORITY_HIGH2 );

    // test callback attach with priority
    $priority_list = [];
    $this->assertSame( [ $callback3, $callback1, $callback2 ], $storage->getCallbackList( $priority_list ) );
    $this->assertSame( [ Event\Emitter::PRIORITY_HIGH2, Event\Emitter::PRIORITY_NORMAL, Event\Emitter::PRIORITY_LOW2 ], $priority_list );

    // test callback detach
    $storage->detach( $callback2 );
    $this->assertSame( [ $callback3, $callback1 ], $storage->getCallbackList() );

    $storage->detachAll();
    $this->assertSame( [], $storage->getCallbackList() );

    //
    $this->assertFalse( EventTestEvent::emitter() === EventTestEvent2::emitter(), "Default emitter should be different for each event" );
  }

  /**
   * @depends testEmitter
   */
  public function testTrigger() {

    EventTestEvent::emitter()->attach( $this->_callback( 1 ), Event\Emitter::PRIORITY_LOW2 );
    EventTestEvent::emitter()->attach( $this->_callback( 2 ), Event\Emitter::PRIORITY_NORMAL );
    EventTestEvent::emitter()->attach( $this->_callback( 3 ), Event\Emitter::PRIORITY_HIGH2 );

    // test event triggering
    $event1 = new EventTestEvent( 1 );
    $this->assertTrue( $event1->isPrevented() );
    $this->assertEquals( 1, $event1->prevented );
    $this->assertEquals( 1, $event1->callback );

    $event2 = new EventTestEvent( 0 );
    $this->assertFalse( $event2->isPrevented() );
    $this->assertEquals( null, $event2->prevented );
    $this->assertEquals( 1, $event2->callback );
  }

  /**
   * Create a callback with specific name
   *
   * @param int $number
   *
   * @return callable
   */
  private function _callback( $number ) {
    return function ( EventTestEvent $event ) use ( $number ) {
      if( !$event->isStopped() ) {
        $event->callback = $number;

        if( $event->prevent == $number ) {
          $event->setPrevented();
          $event->prevented = $number;
        }
      }
    };
  }
}

class EventTestEvent extends Event {

  public $callback;
  public $prevent;
  public $prevented;

  public function __construct( int $prevent ) {
    $this->prevent = $prevent;

    $this->trigger();
  }
}
class EventTestEvent2 extends Event {

  public function __construct() {
    $this->trigger();
  }
}
