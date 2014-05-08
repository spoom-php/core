<?php namespace Engine\Event;

use Engine\Exception\Collector;
use Engine\Exception\Helper as ExceptionHelper;
use Engine\Extension\Extension;
use Engine\Utility\Feasible;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Event
 * @package Engine\Events
 *
 * @property bool   prevented
 * @property bool   stopped
 * @property string event
 * @property array  arguments
 */
class Event extends Collector {

  /**
   * Storage of the event and the attached listeners
   *
   * @var Storage
   */
  private static $storage = null;

  /**
   * Array of the instanced listeners. All listener only instanced once!
   *
   * @var array
   */
  private static $cache_listeners = array();

  /**
   * Attached listeners storage
   *
   * @var array
   */
  private $listeners = false;

  /**
   * The triggered event name
   *
   * @var string
   */
  private $_event = null;

  /**
   * @var bool
   */
  private $_stopped = false;

  /**
   * @var bool
   */
  private $_prevented = false;

  /**
   * The triggred event arguments
   *
   * @var array
   */
  private $_arguments = array();

  /**
   * @param string $event_name
   * @param array  $arguments
   */
  public function __construct( $event_name, $arguments = array() ) {

    // set default params
    $this->_event     = $event_name;
    $this->_arguments = $arguments;

    // create event storage if already not
    if( !self::$storage ) self::$storage = new Storage();

    // Execute event handler
    $this->execute();
  }

  /**
   * Getter for _ prefixed attributes
   *
   * @param string $index
   *
   * @return string|null
   */
  public function __get( $index ) {
    $index = '_' . $index;
    if( isset( $this->{$index} ) ) return $this->{$index};

    return null;
  }

  /**
   * Setter for stopped or prevent attribute
   *
   * @param string $index
   * @param mixed  $value
   */
  public function __set( $index, $value ) {

    switch( $index ) {
      case 'stopped':
        $this->_stopped = $value == true;
        break;
      case 'prevented':
        $this->_prevented = $value == true;
        break;
    }
  }

  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index );
  }

  /**
   * Get all ( or a specified ) result from the event
   * in an associative array
   *
   * @param bool $listener
   *
   * @return array|null
   */
  public function getResultList( $listener = false ) {

    if( $listener === false ) {
      $rs = array();
      foreach( $this->listeners as $l ) $rs[ $l[ 'name' ] ] = $l[ 'result' ];

      return $rs;
    }

    return isset( $this->listeners[ $listener ] ) ? $this->listeners[ $listener ][ 'result' ] : null;
  }

  /**
   * Execute the event. Collect and call listeners and store results.
   */
  private function execute() {
    $this->load();

    // Call attached listeners
    foreach( $this->listeners as &$l ) {

      /** @var Feasible $instance */
      $instance = $l[ 'instance' ];

      if( $instance instanceof Feasible ) {
        $l[ 'result' ] = $instance->execute( $this->_event, $this );

        if( $this->_stopped ) break;
      }
    }
  }

  /**
   * Load attached listeners to the listeners storage
   */
  private function load() {
    $this->listeners = array();
    $tmp             = self::$storage->geta( str_replace( '.', '-', $this->_event ) . ':' );

    // collect listeners if event exists and enabled
    foreach( $tmp as $listener => $options ) {
      $this->setListener( $listener, (object) $options );
    }
  }

  /**
   * Set a listener instance to the event listeners array
   * based on the given params.
   *
   * @param string $listener
   * @param object $options
   */
  private function setListener( $listener, $options ) {
    if( !$options->enabled ) return;

    $index     = $listener;
    $pieces    = explode( '.', $listener, 3 );
    $extension = new Extension( $pieces[ 0 ] . '.' . $pieces[ 1 ] );
    if( !isset( self::$cache_listeners[ $index ] ) ) {

      // get listener instance
      $listener = $extension->instance( $pieces[ 2 ] );

      if( ExceptionHelper::isException( $listener ) || !is_callable( array( $listener, 'execute' ) ) ) return;
      self::$cache_listeners[ $index ] = & $listener;
    }

    $this->listeners[ ] = array( 'name'      => $index,
                                 'extension' => $extension->id,
                                 'instance'  => &self::$cache_listeners[ $index ],
                                 'result'    => null );
  }
}