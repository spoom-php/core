<?php namespace Spoom\Core;

use Spoom\Core\Helper\Collection;
use Spoom\Core\Helper;

/**
 * Exception for public display, usually for the user. This can be a missfilled form field warning, bad request
 * parameter or a deeper exception (Logic or Runtime) public version
 *
 * @property-read string $id           The unique identifier
 * @property-read array  $data         The data attached to the exception
 * @property-read int    $severity     The log level
 */
class Exception extends \Exception implements Helper\ThrowableInterface, Helper\AccessableInterface {
  use Helper\Throwable;
  use Helper\Accessable;

  const ID = '0#spoom-core';

  /**
   * @param string          $message
   * @param string|int      $id
   * @param array           $context
   * @param \Throwable|null $previous
   * @param int             $severity
   */
  public function __construct( string $message, $id, $context = [], \Throwable $previous = null, int $severity = Severity::ERROR ) {
    parent::__construct( $message, (int) $id, $previous );

    $this->_id       = $id;
    $this->_context  = Collection::cast( $context, [] );
    $this->_severity = $severity;
  }

  /**
   * Logger the `\Throwable` with the proper severity and message
   *
   * @param \Throwable      $throwable
   * @param LoggerInterface $instance
   * @param array           $context
   *
   * @return \Throwable The input `\Throwable`
   */
  public static function log( \Throwable $throwable, LoggerInterface $instance, array $context = [] ): \Throwable {

    // extend data
    $context                = Collection::cast( $context, [] );
    $context[ 'exception' ] = $throwable;
    $context[ 'backtrace' ] = false;

    $severity  = $throwable instanceof Helper\ThrowableInterface ? $throwable->getSeverity() : Severity::CRITICAL;
    $namespace = get_class( $throwable ) . ':' . ( $throwable instanceof Helper\ThrowableInterface ? $throwable->getId() : $throwable->getCode() );
    $instance->create( $throwable->getMessage(), $context, $namespace, $severity );

    return $throwable;
  }
  /**
   * Wrap native exceptions/errors
   *
   * @param \Throwable $throwable
   *
   * @return Helper\ThrowableInterface
   */
  public static function wrap( \Throwable $throwable ): Helper\ThrowableInterface {

    switch( true ) {
      //
      case $throwable instanceof Helper\ThrowableInterface:
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
