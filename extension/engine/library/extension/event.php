<?php namespace Engine\Extension;

use Engine\Exception;
use Engine\Exception\Collector;
use Engine\Extension;
use Engine\Helper\Library;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Event
 * @package Engine\Extension
 *
 * @property bool        $prevented The default event action has been prevented or not
 * @property bool        $stopped   Stopped next handler call or not
 * @property string      $name      The event name
 * @property string      $namespace The event namespace
 * @property array       $argument  The arguments passed to the event call
 * @property array|null  $result    The handler's results in an array indexed by the handler names
 * @property Collector   $collector The exception collector
 */
class Event extends Library {

  /**
   * Array of the instanced listeners. All listener only instanced once!
   *
   * @var array
   */
  private static $cache = [ ];

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
  private $_name = null;

  /**
   * The event "namespace"
   *
   * @var string|null
   */
  private $_namespace = null;

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
  private $_argument = [ ];

  /**
   * Store the result array after the execution in a
   * 'listener index => result data' structure
   *
   * @var array|null
   */
  private $_result = null;

  /**
   * Exception collector
   *
   * @var Collector
   */
  private $_collector = null;

  /**
   * @param string $namespace
   * @param string $name
   * @param array  $arguments
   */
  public function __construct( $namespace, $name, $arguments = [ ] ) {

    // set default params
    $this->_namespace = $namespace;
    $this->_name      = $name;
    $this->_argument  = $arguments;
    $this->_collector = new Collector();
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
    return property_exists( $this, $index ) ? $this->{$index} : null;
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
    $this->_result = [ ];
    foreach( $this->listeners as &$listener ) {

      $this->_result[ $listener->name ] = $listener->instance->execute( $this->_namespace . '.' . $this->_name, [ $this, $listener->data ] );
      if( $this->_stopped ) break;
    }

    return $this;
  }

  /**
   * Load attached listeners to the listeners storage. The listeners is stored in one of the engine
   * configuration file named 'event-<package.name>' in a {
   *    event: [
   *      {
   *        extension: String (package-name),
   *        library: String (dot separated namespaces included),
   *        enabled: Boolean,
   *        data: Object
   *      }
   *    ],
   *    ...
   *  } structure. The order in the event handlers array is the execution order when the event is triggers
   */
  private function load() {
    $this->listeners = [ ];
    $extension       = new Extension( 'engine' );

    // collect listeners if event exists and enabled
    $tmp = $extension->configuration->geta( $this->_namespace . ':' . $this->_name );
    foreach( $tmp as $listener ) if( !empty( $listener->extension ) && !empty( $listener->library ) ) {
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
    if( empty( $options->enabled ) || !Extension\Helper::exist( $options->extension, true ) ) return;

    $index = $options->extension . ':' . $options->library;
    if( !isset( self::$cache[ $index ] ) ) {

      $extension = new Extension( $options->extension );
      $listener = $extension->instance( $options->library );

      if( !$listener || !is_callable( [ $listener, 'execute' ] ) ) return;
      self::$cache[ $index ] = $listener;
    }

    $this->listeners[ $index ] = (object) [
      'name'      => $index,
      'extension' => $options->extension,
      'instance'  => self::$cache[ $index ],
      'data'      => isset( $options->data ) ? $options->data : null
    ];
  }
}