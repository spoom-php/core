<?php namespace Spoom\Core\Exception;

use Spoom\Core\Application;
use Spoom\Core\Helper;
use Spoom\Core\Helper\Collection;

/**
 * Exception for unpredictable and unfixable problems. An offline database, missing file permission...something like that
 *
 * @property-read string $id
 * @property-read int    $severity
 * @property-read array  $context
 */
class Runtime extends \RuntimeException implements Helper\ThrowableInterface, Helper\AccessableInterface {
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
  public function __construct( string $message, $id, $context = [], \Throwable $previous = null, int $severity = Application::SEVERITY_CRITICAL ) {
    parent::__construct( $message, (int) $id, $previous );

    $this->_id       = $id;
    $this->_context  = Collection::read( $context, [] );
    $this->_severity = $severity;
  }
}
