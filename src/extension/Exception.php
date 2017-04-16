<?php namespace Spoom\Framework;

use Spoom\Framework\Helper\Collection;
use Spoom\Framework\Helper;

/**
 * Interface ExceptionInterface
 * @package Framework
 */
interface ExceptionInterface extends \Throwable {

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
 * parameter or a deeper exception (Logic or Runtime) public version
 *
 * @package Framework
 *
 * @property-read string $id           The unique identifier
 * @property-read array  $data         The data attached to the exception
 * @property-read int    $severity     The log level
 */
class Exception extends \Exception implements ExceptionInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  const ID = '0#spoom-framework';

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
  private $_severity = Application::SEVERITY_ERROR;

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
  public function __construct( $message, $id, $context = [], \Throwable $previous = null, $severity = Application::SEVERITY_ERROR ) {
    parent::__construct( $message, (int) $id, $previous );

    $this->_id       = $id;
    $this->_context  = Collection::read( $context, [] );
    $this->_severity = (int) $severity;
  }

  //
  public function __toString() {
    return $this->getId() . ": '" . $this->getMessage() . "'";
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

  /**
   * Log the `\Throwable` with the proper severity and message
   *
   * @param \Throwable   $throwable
   * @param LogInterface $instance
   * @param array        $context
   *
   * @return \Throwable The input `\Throwable`
   */
  public static function log( \Throwable $throwable, ?LogInterface $instance = null, array $context = [] ): \Throwable {
    $instance = $instance ?? Application::instance()->getLog();

    // extend data
    $context                = Collection::read( $context, [] );
    $context[ 'exception' ] = $throwable;
    $context[ 'backtrace' ] = false;

    $severity  = $throwable instanceof ExceptionInterface ? $throwable->getSeverity() : Application::SEVERITY_CRITICAL;
    $namespace = get_class( $throwable ) . ':' . ( $throwable instanceof ExceptionInterface ? $throwable->getId() : $throwable->getCode() );
    $instance->create( $throwable->getMessage(), $context, $namespace, $severity );

    return $throwable;
  }
  /**
   * Wrap native exceptions/errors
   *
   * @param \Throwable $throwable
   *
   * @return ExceptionInterface
   */
  public static function wrap( \Throwable $throwable ): ExceptionInterface {

    switch( true ) {
      //
      case $throwable instanceof ExceptionInterface:
        return $throwable;

      //
      case $throwable instanceof \LogicException:
      case $throwable instanceof \Error:
        return new Exception\Logic( $throwable->getMessage(), Exception\Logic::ID, [], $throwable );

      //
      case $throwable instanceof \RuntimeException:
        return new Exception\Runtime( $throwable->getMessage(), Exception\Runtime::ID, [], $throwable );
    }

    return new static( $throwable->getMessage(), static::ID, [], $throwable );
  }
}
