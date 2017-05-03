<?php namespace Spoom\Core\Helper;

use Spoom\Core\Application;

/**
 * Interface ThrowableInterface
 */
interface ThrowableInterface extends \Throwable {

  /**
   * Unique identifier
   *
   * @return string
   */
  public function getId();
  /**
   * Danger level
   *
   * @return int
   */
  public function getSeverity();
  /**
   * Additional data
   *
   * @return array
   */
  public function getContext();
}

/**
 * Trait Throwable
 */
trait Throwable {

  //
  private $_id;
  //
  private $_severity = Application::SEVERITY_ERROR;
  //
  private $_context = [];

  //
  public function __toString() {
    return $this->getId() . ": '" . $this->getMessage() . "'";
  }

  //
  public function getId() {
    return $this->_id;
  }
  //
  public function getSeverity() {
    return $this->_severity;
  }
  //
  public function getContext() {
    return $this->_context;
  }

  /**
   * @return string
   */
  abstract protected function getMessage();
}
