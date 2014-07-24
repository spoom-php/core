<?php namespace Engine\Extension;

use Engine\Exception\Collector;
use Engine\Exception\Helper as ExceptionHelper;
use Engine\Extension\Helper as ExtensionHelper;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Event
 * @package Engine\Extension
 *
 * @property Extension extension
 * @property bool   prevented
 * @property bool   stopped
 * @property string event
 * @property array  arguments
 */
class Event extends Collector {

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
   * The extension that triggered the event
   *
   * @var Extension|null
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
  private $_arguments = array();

  /**
   * @param Extension $extension
   * @param string $event_name
   * @param array $arguments
   */
  public function __construct( Extension $extension, $event_name, $arguments = array() ) {

    // set default params
    $this->_extension = $extension;
    $this->_event = $event_name;
    $this->_arguments = $arguments;
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
   * @param mixed $value
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
   * @param bool $name
   *
   * @return array|null
   */
  public function getResultList( $name = false ) {

    // retrive all result
    if( $name === false ) {

      $tmp = array();
      foreach( $this->listeners as $listener ) {
        $tmp[ $listener->name ] = $listener->result;
      }

      return $tmp;
    }

    return isset( $this->listeners[ $name ] ) ? $this->listeners[ $name ]->result : null;
  }

  /**
   * Execute the event. Collect and call listeners and store results.
   *
   * @return self
   */
  public function execute() {
    $this->load();

    // Call attached listeners
    foreach( $this->listeners as &$listener ) {

      $listener->result = $listener->instance->execute( $this->_event, $this, $listener->data );
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
    $extension = new Extension( '.engine' );

    $namespace = 'event-' . str_replace( '.', '-', trim( $this->extension->id, '.' ) ); // the trim is for the '.engine' extension id
    $index = str_replace( '.', '-', $this->_event );
    $tmp = $extension->configuration->geta( $namespace . ':' . $index );

    // collect listeners if event exists and enabled
    foreach( $tmp as $listener ) if( isset( $listener->extension ) && isset( $listener->library ) ) {
      $this->setListener( $listener );
    }
  }

  /**
   * Set a listener instance to the event listeners array
   * based on the given params.
   *
   * @param object $options
   */
  private function setListener( $options ) {
    if( !isset( $options->enabled ) || !$options->enabled || !ExtensionHelper::validate( $options->extension ) ) return;

    $index = $options->extension . '.' . $options->library;
    if( !isset( self::$cache[ $index ] ) ) {

      $extension = new Extension( $options->extension );
      $listener = $extension->instance( $options->library );

      if( !$listener || !is_callable( array( $listener, 'execute' ) ) ) return;
      self::$cache[ $index ] = $listener;
    }

    $this->listeners[ $index ] = (object) array(
        'name' => $index,
        'extension' => $options->extension,
        'instance' => self::$cache[ $index ],
        'data' => isset( $options->data ) ? $options->data : null,
        'result' => null
    );
  }
}