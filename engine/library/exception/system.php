<?php namespace Engine\Exception;

use Engine\Exception;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Exception for unfixable errors. An offline database, missing file...something like that
 *
 * @package Engine\Exception
 */
class System extends Exception {
}