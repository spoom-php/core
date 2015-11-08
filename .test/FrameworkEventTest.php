<?php

class FrameworkEventTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::setup( function () {
    } );

    $source    = \Framework::PATH_BASE . '.test/FrameworkEventTest/';
    $directory = \Framework::PATH_BASE . 'extension/framework/asset/event/';
    ( is_dir( $directory ) || mkdir( $directory, 0777, true ) ) && copy( $source . 'framework.json', $directory . 'framework.json' );

    $directory = \Framework::PATH_BASE . 'extension/framework/library/';
    copy( $source . 'test.php', $directory . 'test.php' );
    copy( $source . 'test2.php', $directory . 'test2.php' );

    parent::__construct( $name, $data, $dataName );
  }

  public function __destruct() {
    @unlink( \Framework::PATH_BASE . 'extension/framework/asset/event/framework.json' );
    @unlink( \Framework::PATH_BASE . 'extension/framework/library/test.php' );
    @unlink( \Framework::PATH_BASE . 'extension/framework/library/test2.php' );
  }

  public function testBasic() {
        
    // static event handler registration and execute
    $event = \Framework\Event::instance( 'framework', 'test.simple' );
    $result = $event->execute( [ 'test' => 1 ] );
    $this->assertEquals( [ 1, 'simple' ], $result->get( 'output' ) );

    // event stop and prevention
    $result = $event->execute( [ 'test' => 2 ] );
    $this->assertEquals( [ 'stopped', 'prevented' ], $result->get( 'output' ) );
  }
  
  public function testAdvanced() {

    $event = \Framework\Event::instance( 'framework', 'test.advance' );

    // dynamic event handler addition and run order
    $event->getStorage()->clear();
    $event->getStorage()->add( new \Framework\Event\Listener( 'framework:test2' ) );
    $result = $event->execute( [ 'test' => 1 ] );
    $this->assertEquals( 2, $result->get( 'output' ) );

    // listener iteration, modification and disabling
    $storage = $event->getStorage();
    foreach( $storage as $listener ) {
      /** @var \Framework\Event\Listener $listener */

      if( $listener->library == \Framework::library( 'framework:test2' ) ) {
        $listener->enable = false;
      }
    }
    $result = $event->execute( [ 'test' => 2 ] );
    $this->assertEquals( [ 2, 'advance' ], $result->get( 'output' ) );
    
    // listener removal
    $storage->clear();
    $storage->add( new \Framework\Event\Listener( 'framework:test2' ) );
    foreach( $storage as $listener ) {
      /** @var \Framework\Event\Listener $listener */

      if( $listener->library == \Framework::library( 'framework:test2' ) ) {
        $storage->remove( $listener );
      }
    }
    $result = $event->execute( [ 'test' => 2 ] );
    $this->assertEquals( [ 2, 'advance' ], $result->get( 'output' ) );
  }
}
