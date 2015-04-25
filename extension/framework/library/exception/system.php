<?php namespace Framework\Exception;

use Framework\Exception;

/**
 * Exception for unfixable errors. An offline database, missing file...something like that
 *
 * @package Framework\Exception
 */
class System extends Exception {

  /**
   * Initialise the custom Exception object, with extension and code specified message or a simple string message
   *
   * @param string|\Exception $id
   * @param array             $data
   * @param \Exception        $previous
   */
  public function __construct( $id, array $data = [ ], \Exception $previous = null ) {
    parent::__construct( $id, $data, $previous );

    // system exceptions are always logged
    $this->log();
  }
}
