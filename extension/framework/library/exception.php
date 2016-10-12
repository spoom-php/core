<?php namespace Framework;

use Framework\Exception\Helper;
use Framework\Helper\Enumerable;
use Framework\Helper\Library;
use Framework\Helper\LibraryInterface;
use Framework\Helper\LogableInterface;
use Framework\Helper\LogInterface;
use Framework\Helper\Text;

/**
 * Extend simple PHP \Exception with the power of code base text with language and insertion support
 *
 * @package Framework
 *
 * @property-read array     $data      The data attached to the exception
 * @property-read Extension $extension The message localization source
 * @property-read int       $level     The log level
 * @property-read string    $id        The unique identifier. The format is '<extension>#<code>'
 */
abstract class Exception extends \Exception implements \JsonSerializable, LibraryInterface, LogableInterface {

  /**
   * The level of the exception based on the exception's type
   *
   * @var int
   */
  private $_level;

  /**
   * The unique identifier of the exception
   *
   * @var string
   */
  private $_id;
  /**
   * Insertion data
   *
   * @var array
   */
  private $_data = [];
  /**
   * Source of the error message if not null
   *
   * @var Extension
   */
  private $_extension = null;

  /**
   * Initialise the custom Exception object, with extension and code specified message or a simple string message
   *
   * @param string|\Exception $id
   * @param array|object      $data
   * @param \Exception        $exception
   */
  public function __construct( $id, $data = [], \Exception $exception = null ) {

    $tmp = Exception\Helper::parse( $id );
    if( $tmp ) $message = Exception\Helper::build( Extension::instance( $tmp->extension ), $tmp->code, $data );
    else {

      $message = Text::insert( $id, $data );
      $tmp     = Exception\Helper::parse( Helper::EXCEPTION_UNKNOWN );
    }

    $this->_id        = $tmp->extension . '#' . $tmp->code;
    $this->_extension = Extension::instance( $tmp->extension );
    $this->_data      = Enumerable::cast( $data );
    $this->_level     = $tmp->level ?: \Framework::LEVEL_ERROR;

    parent::__construct( $message, $tmp->code, $exception );
  }

  /**
   * @param $index
   *
   * @return mixed
   * @throws Exception\Strict
   */
  public function __get( $index ) {

    $method = Library::searchGetter( $index, $this );
    if( $method ) return $this->{$method}();
    else throw new Exception\Strict( Library::EXCEPTION_MISSING_PROPERTY, [ 'property' => $index ] );
  }
  /**
   * @param $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return Library::searchGetter( $index, $this ) !== null;
  }
  /**
   * @return string
   */
  public function __toString() {
    return $this->id . ": '" . $this->getMessage() . "'";
  }

  /**
   * Compare and exception' id to a valid exception id
   *
   * @param string|null $input
   *
   * @return bool
   */
  public function match( $input ) {
    return Exception\Helper::match( $this, $input );
  }
  /**
   * Log the exception
   *
   * @param array             $data     Additional data to the log
   * @param LogInterface|null $instance The logger instance use to create the log. If null, the Application::getLog() used
   *
   * @return $this
   * @throws Exception\Strict
   */
  public function log( $data = [], LogInterface $instance = null ) {

    $instance = $instance ?: Application::getLog();
    if( $instance ) {

      // extend data
      $data                = Enumerable::cast( $data );
      $data[ 'exception' ] = $this->toArray( true );

      $instance->create( $this->message, $this->_data + $data, 'framework:exception!' . $this->id, $this->_level );
    }

    return $this;
  }

  /**
   * Make an associative array from the exception
   *
   * @param bool $more Append additional data to the result
   *
   * @return array
   */
  public function toArray( $more = false ) {

    $tmp = [
      'id'        => $this->id,
      'code'      => $this->getCode(),
      'message'   => $this->getMessage(),
      'extension' => $this->_extension ? $this->_extension->id : null,
      'data'      => $this->_data
    ];

    if( $more ) {
      $tmp[ 'line' ]     = $this->getFile() . ':' . $this->getLine();
      $tmp[ 'trace' ]    = $this->getTrace();
      $tmp[ 'previous' ] = Helper::convert( $this->getPrevious(), $more );
    }

    return $tmp;
  }

  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getId() {
    return $this->_id;
  }
  /**
   * @since 0.6.0
   *
   * @return array
   */
  public function getData() {
    return $this->_data;
  }
  /**
   * @return int
   */
  public function getLevel() {
    return $this->_level;
  }

  /**
   * @since 0.6.0
   *
   * @return \Framework\Extension
   */
  public function getExtension() {
    return $this->_extension;
  }

  /**
   * Specify data which should be serialized to JSON
   * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
   * @return mixed data which can be serialized by <b>json_encode</b>,
   * which is a value of any type other than a resource.
   */
  public function jsonSerialize() {
    return (object) $this->toArray();
  }
}
