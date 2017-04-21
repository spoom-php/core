<?php namespace Spoom\Core;

use Spoom\Core\Helper;

/**
 * Interface EventInterface
 */
interface EventInterface extends StorageInterface, Helper\FailableInterface {

  /**
   * @return string
   */
  public function getName(): string;

  /**
   * @return bool
   */
  public function isStopped(): bool;
  /**
   * @param bool $value
   */
  public function setStopped( bool $value = true );
  /**
   * @return bool
   */
  public function isPrevented(): bool;
  /**
   * @param bool $value
   */
  public function setPrevented( bool $value = true );
}

/**
 * Class Event
 *
 * @property      bool   $stopped This flag doesn't stop the listener calls, but the listeners MUST respect it internally
 * @property      bool   $prevented
 * @property-read string $name
 */
class Event extends Storage implements EventInterface, Helper\AccessableInterface {
  use Helper\Failable;
  use Helper\Accessable;

  /**
   * @var bool
   */
  private $_stopped = false;
  /**
   * @var bool
   */
  private $_prevented = false;

  /**
   * @var string
   */
  private $_name;

  /**
   * @param string     $name
   * @param mixed|null $data
   */
  public function __construct( string $name, $data = null ) {
    parent::__construct( $data );

    $this->_name = $name;
  }

  /**
   * @return string
   */
  public function getName(): string {
    return $this->_name;
  }
  /**
   * @return bool
   */
  public function isStopped(): bool {
    return $this->_stopped;
  }
  /**
   * @param bool $value
   */
  public function setStopped( bool $value = true ) {
    $this->_stopped = $value;
  }
  /**
   * @return bool
   */
  public function isPrevented(): bool {
    return $this->_prevented;
  }
  /**
   * @param bool $value
   */
  public function setPrevented( bool $value = true ) {
    $this->_prevented = $value;
  }
}
