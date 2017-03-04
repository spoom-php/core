<?php namespace Framework;

use Framework\Helper\Enumerable;
use Framework\Helper;

/**
 * Interface ExceptionInterface
 * @package Framework
 */
interface ExceptionInterface extends \Throwable, \JsonSerializable, Helper\LogableInterface {

  const ID = '0#framework';

  /**
   * Unique identifier of the exception
   *
   * @return string
   */
  public function getId();
  /**
   * Exception danger level
   *
   * @return int
   */
  public function getSeverity();
  /**
   * Additional data for the exception
   *
   * @return array
   */
  public function getContext();
}

/**
 * Exception for public display, usually for the user. This can be a missfilled form field warning, bad request
 * parameter or a deeper exception (Strict or System) public version
 *
 * @package Framework
 *
 * @property-read string $id           The unique identifier
 * @property-read array  $data         The data attached to the exception
 * @property-read int    $severity     The log level
 */
class Exception extends \Exception implements ExceptionInterface {

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

  /**
   * @param \Throwable $throwable
   *
   * @return ExceptionInterface
   */
  public static function wrap( \Throwable $throwable ) {

    switch( true ) {
      case $throwable instanceof ExceptionInterface:
        return $throwable;

      case $throwable instanceof \LogicException:
        return new Exception\Strict( $throwable->getMessage(), Exception\Strict::ID, [], $throwable );

      case $throwable instanceof \RuntimeException:
        return new Exception\System( $throwable->getMessage(), Exception\System::ID, [], $throwable );
    }

    return new static( $throwable->getMessage(), static::ID, [], $throwable );
  }
}
