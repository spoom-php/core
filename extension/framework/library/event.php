<?php namespace Framework;

use Framework\Exception;
use Framework\Exception\Collector;
use Framework\Extension;
use Framework\Helper\Library;
use Framework\Storage;

/**
 * Class Event
 * @package Framework
 *
 * @property-read string        $name
 * @property-read string        $namespace
 * @property-read Event\Storage $storage
 */
class Event extends Library {

  /**
   * @var array[string]Event
   */
  private static $instance = [ ];

  /**
   * @var string
   */
  private $_name;
  /**
   * @var string
   */
  private $_namespace;

  /**
   * Registered listener source
   *
   * @var Event\Storage
   */
  private $_storage;

  /**
   * @param string $namespace
   * @param string $name
   */
  protected function __construct( $namespace, $name ) {

    $this->_namespace = $namespace;
    $this->_name      = $name;
    $this->_storage   = new Event\Storage( $this );
  }

  /**
   * @return string
   */
  public function __toString() {
    return $this->_namespace . ':' . $this->_name;
  }

  /**
   * Execute the event. This will execute all the registered listeners (which is enabled) from the event's storage
   *
   * @param array|object $arguments The input arguments for the event
   *
   * @return EventData The event result
   */
  public function execute( $arguments = [ ] ) {

    $data = new EventData( $this, $arguments );
    $list = $this->_storage->getList();

    // execute registered listeners
    foreach( $list as $listener ) try {

      $listener->execute( $data );

    } catch( \Exception $e ) {
      $data->collector->add( Exception\Helper::wrap( $e ) );
    }

    return $data;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->_name;
  }
  /**
   * @return string
   */
  public function getNamespace() {
    return $this->_namespace;
  }
  /**
   * @return Event\Storage
   */
  public function getStorage() {
    return $this->_storage;
  }

  /**
   * Get an event instance. This is the only way to get an event instance
   *
   * @param string $namespace
   * @param string $name
   *
   * @return Event
   */
  public static function instance( $namespace, $name ) {

    $index = $namespace . ':' . $name;
    if( !isset( self::$instance[ $index ] ) ) {

      self::$instance[ $index ] = new Event( $namespace, $name );
    }

    return self::$instance[ $index ];
  }
}
/**
 * Store an event arguments (the 'argument' namespace) and results ('result' namespace)
 *
 * @package Framework
 *
 * @property-read Event     $event
 * @property-read Collector $collector
 * @property bool           $stopped This flag doesn't stop the listener calls, but the listeners MUST respect it internally
 * @property bool           $prevented
 */
class EventData extends Storage {

  const NAMESPACE_RESULT   = 'result';
  const NAMESPACE_ARGUMENT = 'argument';

  /**
   * @var Event
   */
  private $_event;

  /**
   * @var Collector
   */
  private $_collector = null;

  /**
   * @var bool
   */
  private $_stopped = false;
  /**
   * @var bool
   */
  private $_prevented = false;

  /**
   * @param Event        $event
   * @param array|object $argument
   */
  public function __construct( Event $event, $argument = [ ] ) {
    parent::__construct( [ ], self::NAMESPACE_RESULT );

    $this->_event     = $event;
    $this->_collector = new Exception\Collector();

    $this->set( self::NAMESPACE_ARGUMENT . self::SEPARATOR_NAMESPACE, $argument );
  }

  /**
   * @return Event
   */
  public function getEvent() {
    return $this->_event;
  }
  /**
   * @return Collector
   */
  public function getCollector() {
    return $this->_collector;
  }

  /**
   * @return bool
   */
  public function isStopped() {
    return $this->_stopped;
  }
  /**
   * @param bool $value
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
   * @param bool $value
   */
  public function setPrevented( $value ) {
    $this->_prevented = (bool) $value;
  }
}
