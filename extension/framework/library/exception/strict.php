<?php namespace Framework\Exception;

use Framework\Application;
use Framework\ExceptionInterface;
use Framework\LogInterface;
use Framework\Helper\Enumerable;

/**
 * Exception for developers and indicates problems that can be fixed with (more careful) coding
 *
 * @package Framework\Exception
 */
class Strict extends \LogicException implements ExceptionInterface {

  /**
   * The unique identifier of the exception
   *
   * @var string
   */
  private $_id;

  /**
   * The severity level of the exception
   *
   * @var int
   */
  private $_severity = Application::LEVEL_ERROR;

  /**
   * @var array
   */
  private $_context = [];

  /**
   * @param string          $message
   * @param int             $id
   * @param array           $context
   * @param \Throwable|null $previous
   * @param int             $severity
   */
  public function __construct( $message, $id, $context = [], \Throwable $previous = null, $severity = Application::LEVEL_ERROR ) {
    parent::__construct( $message, (int) $id, $previous );

    $this->_id       = $id;
    $this->_context  = Enumerable::read( $context, false );
    $this->_severity = (int) $severity;
  }

  //
  public function __toString() {
    return $this->getId() . ": '" . $this->getMessage() . "'";
  }

  //
  public function log( $data = [], LogInterface $instance = null ) {
    // TODO implement
  }

  //
  public function getId() {
    return $this->_id;
  }
  //
  public function getContext() {
    return $this->_context;
  }
  //
  public function getSeverity() {
    return $this->_severity;
  }

  //
  public function jsonSerialize() {

    // TODO implement

    return [];
  }
}
