<?php namespace Spoom\Framework\Exception;

use Spoom\Framework\Application;
use Spoom\Framework\ExceptionInterface;
use Spoom\Framework\Helper;
use Spoom\Framework\Helper\Collection;

/**
 * Exception for unpredictable and unfixable problems. An offline database, missing file permission...something like that
 *
 * @package Framework\Exception
 *
 * @property-read string $id
 * @property-read int    $severity
 * @property-read array  $context
 */
class Runtime extends \RuntimeException implements ExceptionInterface, Helper\AccessableInterface {
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
  public function __construct( $message, $id, $context = [], \Throwable $previous = null, $severity = Application::SEVERITY_CRITICAL ) {
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
}
