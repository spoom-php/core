<?php namespace Engine\Exception;

use Engine\Exception;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Exception for unfixable errors. An offline database, missing file...something like that
 *
 * @package Engine\Exception
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

    // system exceptions always logged
    $this->log();
  }
}