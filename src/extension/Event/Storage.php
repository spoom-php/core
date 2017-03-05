<?php namespace Spoom\Framework\Event;

use Spoom\Framework\EventInterface;
use Spoom\Framework\Helper;

/**
 * Interface StorageInterface
 * @package Framework\Event
 */
interface StorageInterface {

  /**
   * Execute the event in this storage context
   *
   * @param EventInterface $event
   *
   * @return EventInterface
   */
  public function trigger( EventInterface $event );

  /**
   * Attach callback(s) for event(s)
   *
   * Events can be a simple string array, or an associative array in event => priority format. Empty (===null) event means the global
   * event, that will triggered before every event.
   *
   * @param callable          $callback
   * @param string|array|null $event
   * @param int|null          $priority
   *
   * @return static
   */
  public function attach( $callback, $event = null, $priority = null );
  /**
   * Detach callback(s) from event(s)
   *
   * Callback must be the exact same instance that attached previously. Empty (===null) callback means "remove all"
   *
   * @param string|array|null $event
   * @param callable|null     $callback
   *
   * @return static
   */
  public function detach( $event = null, $callback = null );
  /**
   * Detach every callable from the storage
   *
   * @return static
   */
  public function detachAll();

  /**
   * Get events that has attached callback(s)
   *
   * @return string[]
   */
  public function getEventList();
  /**
   * Get attached callbacks for an event
   *
   * @param string|null $event
   * @param array       $priority
   *
   * @return \callable[]
   */
  public function getCallbackList( $event = null, array &$priority = [] );

  /**
   * Storage (mostly unique) name
   *
   * @return string
   */
  public function getName();
}
/**
 * Class Storage
 * @package Framework\Event
 */
class Storage implements StorageInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Default priority for callbacks
   */
  const PRIORITY_DEFAULT = 100;
  /**
   * Global event name
   *
   * This event can't be triggered manually, but called before every event
   */
  const EVENT_GLOBAL = '.';

  /**
   * @var string
   */
  private $_name;

  /**
   * Stores callbacks by event
   *
   * @var array
   */
  private $callback = [];
  /**
   * Stores priorities for callbacks by event
   *
   * @var array
   */
  private $priority = [];

  /**
   * @param string $name
   */
  public function __construct( $name ) {
    $this->_name = $name;
  }

  //
  public function trigger( EventInterface $event ) {

    if( $event->getName() == static::EVENT_GLOBAL ) throw new \InvalidArgumentException( 'Global event is not triggerable' );
    else try {

      // call the global event handlers
      $list = $this->getCallbackList();
      foreach( $list as $callback ) {
        call_user_func_array( $callback, [ $event, $this ] );
      }

      // call the specific event handlers
      $list = $this->getCallbackList( $event->getName() );
      foreach( $list as $callback ) {
        call_user_func_array( $callback, [ $event, $this ] );
      }

    } catch( \Exception $e ) {
      $event->setException( $e );
    }

    return $event;
  }

  //
  public function attach( $callback, $event = null, $priority = self::PRIORITY_DEFAULT ) {

    if( !is_callable( $callback ) ) throw new \InvalidArgumentException( 'Only valid callable can be attached to an event' );
    else {

      // narmalize the input (global event handling, and non-array events)
      if( $event === null ) $event = [ static::EVENT_GLOBAL => $priority ];
      else if( !is_array( $event ) ) $event = [ $event => $priority ];

      foreach( $event as $index => $value ) {

        // add default priority
        if( is_numeric( $index ) ) {
          $index = $value;
          $value = $priority;
        }

        // prevent duplicate callback for an event (and remove from all if it's a global attach)
        $this->detach( $index == static::EVENT_GLOBAL ? array_keys( $this->callback ) : $index, $callback );

        // create empty event storage
        if( !isset( $this->callback[ $index ] ) ) {
          $this->callback[ $index ] = [];
          $this->priority[ $index ] = [];
        }

        // add callback with priority
        $this->callback[ $index ][] = $callback;
        $this->priority[ $index ][] = $value;

        // recalculate the callback list order
        $this->sort( $index );
      }

      return $this;
    }
  }
  //
  public function detach( $event = null, $callback = null ) {

    // handle global event callbacks and force array input
    if( $event === null ) $event = [ static::EVENT_GLOBAL ];
    else if( !is_array( $event ) ) $event = [ $event ];

    foreach( $event as $ev ) {

      // handle "remove all" callback
      if( $callback === null ) unset( $this->callback[ $ev ], $this->priority[ $ev ] );
      else if( !empty( $this->callback[ $ev ] ) ) {

        // find a specific callback
        foreach( $this->callback[ $ev ] as $index => $value ) {
          if( $value === $callback ) {
            array_splice( $this->callback[ $ev ], $index, 1 );
            array_splice( $this->priority[ $ev ], $index, 1 );

            break;
          }
        }
      }
    }

    return $this;
  }
  //
  public function detachAll() {

    $this->callback = $this->priority = [];
    return $this;
  }

  /**
   * Sort event callbacks based on their priority
   *
   * @param string $event
   */
  protected function sort( $event ) {
    if( !empty( $this->callback[ $event ] ) ) {

      // sort by the priorities, but keep the original indexes (we need it for the repopulation)
      $priority = $this->priority[ $event ];
      asort( $priority );

      // repopulate the callbacks based on the new priority "map"
      $tmp                      = $this->callback[ $event ];
      $this->callback[ $event ] = [];
      foreach( $priority as $index => $_ ) {
        $this->callback[ $event ][] = $tmp[ $index ];
      }

      // reset priority indexes (to match the repopulated array)
      $this->priority[ $event ] = array_values( $priority );
    }
  }

  //
  public function getEventList() {
    return array_values( array_filter( array_keys( $this->callback ), function ( $event ) {
      // filter out the global event handler
      return $event != static::EVENT_GLOBAL;
    } ) );
  }
  //
  public function getCallbackList( $event = null, array &$priority = [] ) {

    // handle global event callbacks
    if( $event === null ) {
      $event = static::EVENT_GLOBAL;
    }

    $priority = isset( $this->priority[ $event ] ) ? $this->priority[ $event ] : [];
    return isset( $this->callback[ $event ] ) ? $this->callback[ $event ] : [];
  }

  //
  public function getName() {
    return $this->_name;
  }
}
