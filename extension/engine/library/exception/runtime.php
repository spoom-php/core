<?php namespace Engine\Exception;

use Engine\Exception;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Exception for public display, usually for the user. This can be a missfilled form field warning, bad request
 * parameter or a deeper exception (Runtime or System) public version
 *
 * @package Engine\Exception
 */
class Runtime extends Exception {
}
