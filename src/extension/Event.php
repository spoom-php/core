<?php namespace Spoom\Framework;

use Spoom\Framework\Helper;

interface EventInterface extends StorageInterface, Helper\FailableInterface {

  /**
   * @return string
   */
  public function getName();

  /**
   * @return bool
   */
  public function isStopped();
  /**
   * @param bool $value
   */
  public function setStopped( $value = true );
  /**
   * @return bool
   */
  public function isPrevented();
  /**
   * @param bool $value
   */
  public function setPrevented( $value = true );
}

/**
 * @package Framework
 *
 * @property bool $stopped This flag doesn't stop the listener calls, but the listeners MUST respect it internally
 * @property bool $prevented
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
  public function __construct( $name, $data = null ) {
    parent::__construct( $data, null );

    $this->_name = $name;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->_name;
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
  public function setStopped( $value = true ) {
    $this->_stopped = (bool) $value;
  }
  /**
   * @return bool
   */
  public function isPrevented() {
    return $this->_prevented;
  }
  /**
   * @param bool $value
   */
  public function setPrevented( $value = true ) {
    $this->_prevented = (bool) $value;
  }
}
