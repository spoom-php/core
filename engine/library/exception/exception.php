<?php namespace Engine\Exception;

use Engine\Extension;
use Engine\Utility\String;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Extend simple PHP \Exception with the power of code base text with language and insertion support
 *
 * @package    Engine\Exception
 *
 * @property string  $id   The unique identifier. The format is '<extension>#<code>'
 * @property array   $data The data attached to the exception
 * @property string  $type The "danger level". This can only be a self::TYPE_* constants
 * @property string  $code The ->getCode() returns the numeric part of the code but this also includes the type postfix
 *
 * @depricated Don't use as an instance. Use Common, Runtime, System instead
 */
class Exception extends \Exception {

  /**
   * Code for unknown exception
   */
  const EXCEPTION_UNKNOWN = 0;

  /**
   * Type for legacy (numeric) or invalid postfixes
   */
  const TYPE_UNKNOWN = '';
  /**
   * Type for impactless exception
   */
  const TYPE_NOTICE = 'N';
  /**
   * Type for exception that SHOULD NOT break the execution
   */
  const TYPE_WARNING = 'W';
  /**
   * Type for exception that MAY breaks the execution
   */
  const TYPE_ERROR = 'E';
  /**
   * Type for exception that MUST breaks the execution
   */
  const TYPE_CRITICAL = 'C';

  /**
   * Cache array for type checking
   *
   * @var array
   */
  private static $TYPE = [ self::TYPE_NOTICE, self::TYPE_WARNING, self::TYPE_ERROR, self::TYPE_CRITICAL ];

  /**
   * Insertion data
   *
   * @var array
   */
  private $_data = [ ];

  /**
   * The exception's "danger level"
   *
   * @var string
   */
  private $_type = self::TYPE_UNKNOWN;

  /**
   * Source of the error message if not null
   *
   * @var Extension\Extension
   */
  private $_extension = null;

  /**
   * Initialise the custom Exception object, with extension and code specified message or a simple string message
   *
   * @param string|Extension\Extension $extension
   * @param int                        $code
   * @param array                      $data
   * @param \Exception                 $previous
   */
  public function __construct( $extension, $code = self::EXCEPTION_UNKNOWN, array $data = [ ], \Exception $previous = null ) {

    // define the exception extension
    $message = null;
    if( is_object( $extension ) && $extension instanceof Extension\Extension ) $this->_extension = $extension;
    else if( is_string( $extension ) && Extension\Helper::validate( $extension ) ) $this->_extension = new Extension\Extension( $extension );
    else if( is_string( $extension ) ) $message = $extension;

    // save data
    $this->_data = $data;

    // find the exception type
    $type        = strtoupper( $code{strlen( $code ) - 1} );
    $this->_type = !in_array( $type, self::$TYPE ) ? self::TYPE_UNKNOWN : $type;

    $code = (int) $code;
    parent::__construct( $this->build( $message, $code . $this->_type ), $code, $previous );
  }

  /**
   * @param string $index
   *
   * @return mixed
   */
  public function __get( $index ) {

    switch( $index ) {
      case 'id':
        return ( $this->_extension ? $this->_extension->id : '' ) . '#' . $this->code;
      case 'code':
        return $this->getCode() . $this->_type;
      default:

        $iindex = '_' . $index;
        if( property_exists( $this, $iindex ) ) return $this->{$iindex};
    }

    return null;
  }

  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index ) || in_array( $index, [ 'id', 'code' ] );
  }

  /**
   * @return string
   */
  public function __toString() {
    return $this->getMessage();
  }

  /**
   * Make an associative array from the exception
   *
   * @return array
   */
  public function toArray() {
    return array(
      'id'        => $this->id,
      'code'      => $this->code,
      'message'   => $this->getMessage(),
      'extension' => $this->_extension ? $this->_extension->id : null,
      'data'      => $this->_data
    );
  }

  /**
   * Like toArray() just object
   *
   * @return object
   */
  public function toObject() {
    return (object) $this->toArray();
  }

  /**
   * Build exception message with stored data, extension. Data will be inserted to the exception message, extension is
   * for insertion source ( if message defined ) or the string source. If extension defined is then use
   * extension->text() method to find message with <exception>:#<code> index
   *
   * @param string      $code
   * @param string|null $message
   *
   * @return string
   */
  private function build( $message, $code ) {

    if( $this->_extension && $code != self::EXCEPTION_UNKNOWN ) $message = $this->_extension->text( 'exception:#' . $code, $this->_data );
    else if( !is_string( $message ) ) $message = ( $this->_extension ? $this->_extension->id : '' ) . '#' . $code;
    else $message = String::insert( $message, $this->_data );

    return $message;
  }
}