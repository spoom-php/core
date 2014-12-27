<?php namespace Engine\Exception;

use Engine\Extension\Extension;
use Engine\Extension\Helper as ExtensionHelper;
use Engine\Extension\Localization;
use Engine\Utility\String;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Extend simple PHP \Exception with the power of code base text with language and insertion support
 *
 * Class Exception
 * @package Engine\Exception
 *
 * @property string id
 * @property array  data
 */
class Exception extends \Exception {

  const EXCEPTION_UNKNOWN = 0;

  /**
   * Insertion data
   *
   * @var array
   */
  private $_data = array();

  /**
   * Soruce of the error message if it's not a string
   *
   * @var Extension
   */
  private $_extension = null;

  /**
   * Initialise the custom Exception object, with extension and code specified message or a simple string message
   *
   * @param string|Extension|Localization|null $extension
   * @param int                                $code
   * @param array                              $data
   * @param \Exception                         $previous
   */
  public function __construct( $extension = null, $code = self::EXCEPTION_UNKNOWN, array $data = array(), \Exception $previous = null ) {
    $message = null;

    // define the exception extension
    if( is_object( $extension ) && $extension instanceof Extension ) $this->_extension = $extension;
    if( is_object( $extension ) && $extension instanceof Localization ) $this->_extension = $extension->extension;
    else if( is_string( $extension ) && ExtensionHelper::validate( $extension ) ) $this->_extension = new Extension( $extension );
    else if( is_string( $extension ) ) $message = $extension;

    // set insertion to the exception instance
    $this->_data = is_array( $code ) ? $code : $data;

    // normalise message and construct the exception
    $code = is_numeric( $code ) ? (int) $code : self::EXCEPTION_UNKNOWN;
    parent::__construct( $this->build( $message, $code ), $code, $previous );
  }

  /**
   * @param string $index
   *
   * @return mixed
   */
  public function __get( $index ) {
    $iindex = '_' . $index;

    if( $index == 'id' ) return ( $this->_extension ? $this->_extension->id : '' ) . '#' . $this->getCode();
    else if( property_exists( $this, $iindex ) ) return $this->{$iindex};

    return null;
  }

  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index ) || $index == 'id';
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
      'code'      => $this->getCode(),
      'message'   => $this->getMessage(),
      'extension' => $this->_extension ? $this->_extension->id : null,
      'data'      => $this->_data
    );
  }

  /**
   * Like toArray() just object
   *
   * @return \StdClass
   */
  public function toObject() {
    return (object) $this->toArray();
  }

  /**
   * Build exception message with stored data, extension. Data used for insert variable to
   * the exception message, extension is for insertion source ( if message defined ) or the string
   * source. If extension defined is then use extension->text() method to find message with exception:code
   * index
   *
   * @param int $code
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