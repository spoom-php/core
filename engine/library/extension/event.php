<?php namespace Engine\Extension;

use Engine\Extension;
use Engine\Exception;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Event
 * @package Engine\Extension
 *
 * @property Extension   extension
 * @property bool        prevented
 * @property bool        stopped
 * @property string      event
 * @property string      base
 * @property array       argument
 * @property array|null  result
 */
class Event extends Exception\Collector {

  /**
   * Array of the instanced listeners. All listener only instanced once!
   *
   * @var array
   */
  private static $cache = array();

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
   * Event "namespace"
   *
   * @var null|string
   */
  private $_base = null;

  /**
   * The extension id that trigger the event
   *
   * @var string|null
   */
  private $_extension = null;

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
  private $_argument = array();

  /**
   * Store the result array after the execution in a
   * 'listener index => result data' structure
   *
   * @var array|null
   */
  private $_result = null;

  /**
   * @param string $extension
   * @param string    $event_name
   * @param array     $arguments
   */
  public function __construct( $extension, $event_name, $arguments = array() ) {

    // set default params
    $this->_extension = $extension;
    $this->_event = $event_name;
    $this->_base = $this->extension;
    $this->_argument = $arguments;
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

    if( $index == '_event' ) return $this->_base . ':' . $this->_event;
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
   * Execute the event. Collect and call listeners and store results.
   *
   * @return self
   */
  public function execute() {
    $this->load();

    // Call attached listeners
    $this->_result = array();
    foreach( $this->listeners as &$listener ) {

      $this->_result[ $listener->name ] = $listener->instance->execute( $this->event, array( $this, $listener->data ) );
      if( $this->_stopped ) break;
    }

    return $this;
  }

  /**
   * Load attached listeners to the listeners storage. The listeners is stored in one of the engine
   * configuration file named 'event-<package.name>' in a {
   *    event: [
   *      {
   *        extension: String (package.name),
   *        library: String (dot separated namespaces included),
   *        enabled: Boolean,
   *        data: Object
   *      }
   *    ],
   *    ...
   *  } structure. The order in the event handlers array is the execution order when the event is triggers
   */
  private function load() {
    $this->listeners = array();
    $extension = new Extension( 'engine' );
    
    // collect listeners if event exists and enabled
    $tmp = $extension->configuration->geta( $this->event );
    foreach( $tmp as $listener ) if( isset( $listener->extension ) && isset( $listener->library ) ) {
      $this->add( $listener );
    }
  }

  /**
   * Set a listener instance to the event listeners array
   * based on the given params.
   *
   * @param object $options
   */
  private function add( $options ) {
    if( !isset( $options->enabled ) || !$options->enabled || !Extension\Helper::exist( $options->extension, true ) ) return;

    $index = $options->extension . ':' . $options->library;
    if( !isset( self::$cache[ $index ] ) ) {

      $extension = new Extension( $options->extension );
      $listener = $extension->instance( $options->library );

      if( !$listener || !is_callable( array( $listener, 'execute' ) ) ) return;
      self::$cache[ $index ] = $listener;
    }

    $this->listeners[ $index ] = (object) array(
      'name'      => $index,
      'extension' => $options->extension,
      'instance'  => self::$cache[ $index ],
      'data'      => isset( $options->data ) ? $options->data : null
    );
  }
}