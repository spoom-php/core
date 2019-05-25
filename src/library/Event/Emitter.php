<?php namespace Spoom\Core\Event;

use Spoom\Core\EventInterface;
use Spoom\Core\Helper;

//
interface EmitterInterface {

  /**
   * This makes the emitter itself a callback to use
   *
   * @param EventInterface   $event
   * @param EmitterInterface $emitter
   */
  public function __invoke( EventInterface $event, EmitterInterface $emitter );

  /**
   * Execute the event in this callback context
   *
   * @param EventInterface $event
   *
   * @return EventInterface
   * @throws \InvalidArgumentException Calling with static::EVENT_GLOBAL event
   */
  public function trigger( EventInterface $event ): EventInterface;

  /**
   * Attach callback
   *
   * @param callable   $callback
   * @param float|null $priority
   *
   * @return static
   */
  public function attach( callable $callback, ?float $priority = null );
  /**
   * Detach callback
   *
   * Callback must be the exact same instance that attached previously
   *
   * @param callable $callback
   *
   * @return static
   */
  public function detach( callable $callback );
  /**
   * Detach every callback
   *
   * @return static
   */
  public function detachAll();

  /**
   * Get attached callback
   *
   * @param float[] $priority_list
   *
   * @return callable[]
   */
  public function getCallbackList( array &$priority_list = [] ): array;
}
/**
 * @property-read callable[] $callback_list
 */
class Emitter implements EmitterInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  const PRIORITY_HIGH8 = self::PRIORITY_NORMAL * 0.2;
  const PRIORITY_HIGH4 = self::PRIORITY_NORMAL * 0.4;
  const PRIORITY_HIGH2 = self::PRIORITY_NORMAL * 0.8;
  /**
   * Default priority for callbacks
   */
  const PRIORITY_NORMAL = 1.0;
  const PRIORITY_LOW2 = self::PRIORITY_NORMAL * 1.2;
  const PRIORITY_LOW4 = self::PRIORITY_NORMAL * 1.4;
  const PRIORITY_LOW8 = self::PRIORITY_NORMAL * 1.8;

  /**
   * Stores callbacks
   *
   * @var callable[]
   */
  private $_callback_list = [];
  /**
   * Stores priorities for callbacks
   *
   * @var float[]
   */
  private $_priority_list = [];

  //
  public function __invoke( EventInterface $event, EmitterInterface $_ ) {
    $this->trigger( $event );
  }

  //
  public function trigger( EventInterface $event ): EventInterface {

    // call the specific event handlers
    foreach( $this->_callback_list as $callback ) {
      call_user_func_array( $callback, [ $event, $this ] );
    }

    return $event;
  }

  //
  public function attach( callable $callback, ?float $priority = self::PRIORITY_NORMAL ) {

    // prevent duplicate callback
    $this->detach( $callback );

    // add callback with priority
    $this->_callback_list[] = $callback;
    $this->_priority_list[] = $priority;

    // recalculate the callback list order
    // TODO do not rebuild the entire list to insert only one item
    $this->sort();

    return $this;
  }
  //
  public function detach( callable $callback ) {

    // find a specific callback
    foreach( $this->_callback_list as $index => $_callback ) {
      if( $_callback === $callback ) {
        array_splice( $this->_callback_list, $index, 1 );
        array_splice( $this->_priority_list, $index, 1 );

        break;
      }
    }

    return $this;
  }
  //
  public function detachAll() {

    $this->_callback_list = $this->_priority_list = [];
    return $this;
  }

  /**
   * Sort callbacks based on their priority
   */
  protected function sort() {
    if( !empty( $this->_callback_list ) ) {

      // sort by the priorities, but keep the original indexes (we need it for the repopulation)
      $priority_list = $this->_priority_list;
      asort( $priority_list );

      // repopulate the callbacks based on the new priority "map"
      $tmp                  = $this->_callback_list;
      $this->_callback_list = [];
      foreach( $priority_list as $index => $_ ) {
        $this->_callback_list[] = $tmp[ $index ];
      }

      // reset priority indexes (to match the repopulated array)
      $this->_priority_list = array_values( $priority_list );
    }
  }

  //
  public function getCallbackList( array &$priority_list = [] ): array {
    $priority_list = $this->_priority_list;
    return $this->_callback_list;
  }
}
