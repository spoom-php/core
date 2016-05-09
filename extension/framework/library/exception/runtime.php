<?php namespace Framework\Exception;

use Framework\Exception;

/**
 * Exception for public display, usually for the user. This can be a missfilled form field warning, bad request
 * parameter or a deeper exception (Strict or System) public version
 *
 * @package Framework\Exception
 */
class Runtime extends Exception {
}
