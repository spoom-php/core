<?php namespace Spoom\Core;

use Spoom\Core\Helper;
use Spoom\Core\Event\Emitter;
use Spoom\Core\Event\EmitterInterface;

//
interface EventInterface {

  /**
   * This flag doesn't stop the listener calls, but callbacks MUST respect it internally
   *
   * @return bool
   */
  public function isStopped(): bool;
  /**
   * This flag doesn't stop the listener calls, but callbacks MUST respect it internally
   *
   * @param bool $value
   *
   * @return static
   */
  public function setStopped( bool $value = true );

  /**
   * @return bool
   */
  public function isPrevented(): bool;
  /**
   * @param bool $value
   *
   * @return static
   */
  public function setPrevented( bool $value = true );
}

/**
 * @property bool $stopped
 * @property bool $prevented
 */
abstract class Event implements EventInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * "Singleton" emitter map for the subclasses
   *
   * @var array<string,EmitterInterface>
   */
  private static $emitter_map = [];

  /**
   * @var bool
   */
  private $_stopped = false;
  /**
   * @var bool
   */
  private $_prevented = false;

  /**
   * Trigger the event in the `static::emitter()` context
   *
   * @return static
   */
  protected function trigger() {
    static::emitter()->trigger( $this );

    return $this;
  }

  /**
   * Get the event's emitter, to manipulate callbacks for the event
   */
  public static function emitter(): EmitterInterface {
    return self::$emitter_map[ static::class ] ?? (self::$emitter_map[ static::class ] = new Emitter());
  }

  //
  public function isStopped(): bool {
    return $this->_stopped;
  }
  //
  public function setStopped( bool $value = true ) {
    $this->_stopped = $value;

    return $this;
  }

  //
  public function isPrevented(): bool {
    return $this->_prevented;
  }
  //
  public function setPrevented( bool $value = true ) {
    $this->_prevented = $value;

    return $this;
  }
}
