<?php namespace Framework\Extension;

use Framework\Exception;
use Framework\Exception\Collector;
use Framework\Extension;
use Framework\Helper\Library;
use Framework\Request;
use Framework\Storage;

/**
 * Class Event
 * @package Framework\Extension
 *
 * TODO rethink the event storage type
 *
 * @property      bool       $prevented The default event action has been prevented or not
 * @property      bool       $stopped   Stopped next handler call or not
 * @property-read string     $name      The event name
 * @property-read string     $namespace The event namespace
 * @property-read array      $argument  The arguments passed to the event call
 * @property-read array|null $result    The handler's results in an array indexed by the handler names
 * @property-read Collector  $collector The exception collector
 */
class Event extends Library implements \Countable, \Iterator, \ArrayAccess {

  /**
   * Array of the instanced listeners. All listener only instanced once!
   *
   * @var array
   */
  private static $cache = [ ];
  /**
   * Storage for the event handlers
   *
   * @var Storage\Permanent
   */
  private static $storage;

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
  private $_result = [ ];
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

    // define the default storage
    if( !self::$storage ) {
      
      $extension                             = Extension::instance( 'framework' );
      self::$storage                         = new Storage\File( $extension->directory() . Extension::DIRECTORY_CONFIGURATION );
      self::$storage->getConverter()->native = true;
    }

    // set default params
    $this->_namespace = $namespace;
    $this->_name      = $name;
    $this->_argument  = $arguments;
    $this->_collector = new Collector();
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

      try {

        $this->_result[ $listener->name ] = $listener->instance->execute( $this->_namespace . '.' . $this->_name, [ $this, $listener->data ] );

      } catch( \Exception $e ) {

        $this->_result[ $listener->name ] = null;
        $this->_collector->add( Exception\Helper::wrap( $e ) );
      }

      if( $this->_stopped ) break;
    }

    return $this;
  }

  /**
   * Load attached listeners to the listeners storage. The listeners is stored in one of the framework
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

    // collect listeners if event exists and enabled
    $listener_list = self::$storage->getArray( 'event-' . $this->_namespace . ':' . $this->_name );
    foreach( $listener_list as $listener ) {

      if( !empty( $listener->extension ) && !empty( $listener->library ) ) $this->add( $listener );
      else {

        // log: notice
        Request::getLog()->notice( 'Invalid event handler for \'{namespace}:{name}\'. Missing library or extension', [
          'listener'  => $listener,
          'name'      => $this->_name,
          'namespace' => $this->_namespace
        ], '\Framework\Extension\Event' );
      }
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

      $extension = Extension::instance( $options->extension );
      $listener  = $extension->create( $options->library );

      if( !$listener || !is_callable( [ $listener, 'execute' ] ) ) {

        // log: notice
        Request::getLog()->notice( 'Invalid event handler for \'{namespace}:{name}\'. Missing execute() method', [
          'listener'  => $listener,
          'name'      => $this->_name,
          'namespace' => $this->_namespace
        ], '\Framework\Extension\Event' );

        return;
      }

      self::$cache[ $index ] = $listener;
    }

    $this->listeners[ $index ] = (object) [
      'name'      => $index,
      'extension' => $options->extension,
      'instance'  => self::$cache[ $index ],
      'data'      => isset( $options->data ) ? $options->data : null
    ];
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->_name;
  }
  /**
   * @return null|string
   */
  public function getNamespace() {
    return $this->_namespace;
  }
  /**
   * @return array
   */
  public function getArgument() {
    return $this->_argument;
  }
  /**
   * @return array|null
   */
  public function getResult() {
    return $this->_result;
  }
  /**
   * @return Collector
   */
  public function getCollector() {
    return $this->_collector;
  }
  /**
   * @return boolean
   */
  public function isStopped() {
    return $this->_stopped;
  }
  /**
   * @param boolean $value
   */
  public function setStopped( $value ) {
    $this->_stopped = (bool) $value;
  }
  /**
   * @return boolean
   */
  public function isPrevented() {
    return $this->_prevented;
  }
  /**
   * @param boolean $value
   */
  public function setPrevented( $value ) {
    $this->_prevented = (bool) $value;
  }

  /**
   * @inheritdoc
   */
  public function current() {
    return current( $this->_result );
  }
  /**
   * @inheritdoc
   */
  public function next() {
    next( $this->_result );
  }
  /**
   * @inheritdoc
   */
  public function key() {
    next( $this->_result );
  }
  /**
   * @inheritdoc
   */
  public function valid() {
    return key( $this->_result ) !== null;
  }
  /**
   * @inheritdoc
   */
  public function rewind() {
    reset( $this->_result );
  }

  /**
   * @inheritdoc
   */
  public function offsetExists( $offset ) {
    return array_key_exists( $offset, $this->_result );
  }
  /**
   * @inheritdoc
   */
  public function offsetGet( $offset ) {
    return $this->offsetExists( $offset ) ? $this->_result[ $offset ] : null;
  }
  /**
   * @inheritdoc
   */
  public function offsetSet( $offset, $value ) {
    $this->_result[ $offset ] = $value;
  }
  /**
   * @inheritdoc
   */
  public function offsetUnset( $offset ) {
    unset( $this->_result[ $offset ] );
  }

  /**
   * @inheritdoc
   */
  public function count() {
    return count( $this->_result );
  }
}
