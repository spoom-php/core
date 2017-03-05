<?php namespace Spoom\Framework\Exception;

use Spoom\Framework\Application;
use Spoom\Framework\ExceptionInterface;
use Spoom\Framework\LogInterface;
use Spoom\Framework\Helper\Enumerable;

/**
 * Exception for unpredictable and unfixable problems. An offline database, missing file permission...something like that
 *
 * @package Framework\Exception
 */
class Runtime extends \RuntimeException implements ExceptionInterface {

  const ID = '0#framework';

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
  public function __construct( $message, $id, $context = [], \Throwable $previous = null, $severity = Application::SEVERITY_CRITICAL ) {
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

    $instance = $instance ?: Application::instance()->getLog();
    if( $instance ) {

      // extend data
      $data                = Enumerable::read( $data, false, [] );
      $data[ 'exception' ] = $this;
      $data[ 'backtrace' ] = false;

      $instance->create( $this->message, $data, static::class . ':' . $this->getId(), $this->getSeverity() );
    }

    return $this;
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
    return (object) [
      'id'      => $this->getId(),
      'code'    => $this->getCode(),
      'message' => $this->getMessage(),
      'context' => $this->getContext(),

      'line'     => $this->getFile() . ':' . $this->getLine(),
      'trace'    => $this->getTrace(),
      'previous' => $this->getPrevious()
    ];
  }
}
