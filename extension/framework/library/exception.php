<?php namespace Framework;

use Framework\Exception\Helper;
use Framework\Extension;
use Framework\Helper\Enumerable;
use Framework\Helper\Library;
use Framework\Helper\LibraryInterface;
use Framework\Helper\Log;

/**
 * Extend simple PHP \Exception with the power of code base text with language and insertion support
 *
 * @package Framework
 *
 * @property-read array     $data      The data attached to the exception
 * @property-read Extension $extension The message localization source
 * @property-read string    $type      The "danger level". This can only be a self::TYPE_* constants
 * @property-read int       $level     The exception's level based on the type
 * @property-read string    $id        The unique identifier. The format is '<extension>#<code><type>'
 */
abstract class Exception extends \Exception implements \JsonSerializable, LibraryInterface {

  /**
   * Type for exception that MUST break the execution
   */
  const TYPE_CRITICAL = 'C';
  /**
   * Type for exception that MAY break the execution
   */
  const TYPE_ERROR = 'E';
  /**
   * Type for exception that SHOULD NOT break the execution
   */
  const TYPE_WARNING = 'W';
  /**
   * Type for impactless exception
   */
  const TYPE_NOTICE = 'N';

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
  private $_data = [ ];

  /**
   * The exception's "danger level"
   *
   * @var string
   */
  private $_type = self::TYPE_ERROR;
  /**
   * The level of the exception based on the exception's type
   *
   * @var int
   */
  private $_level;

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
   * @param \Exception        $previous
   *
   * @throws Exception\Strict ::EXCEPTION_INVALID_ID when the ID format is wrong
   */
  public function __construct( $id, $data = [ ], \Exception $previous = null ) {

    // parse id to "properties"
    $tmp = Exception\Helper::parse( $id );
    if( empty( $tmp ) ) throw new Exception\Strict( Helper::EXCEPTION_INVALID_ID, [ 'id' => $id ] );
    else {

      $this->_extension = Extension::instance( $tmp->extension );
      $this->_type      = empty( $tmp->type ) ? static::TYPE_ERROR : $tmp->type;
      $this->_level     = Exception\Helper::getLevel( $this->_type );
      $this->_data      = Enumerable::cast( $data );
      $tmp->code        = $tmp->code < 0 ? Helper::EXCEPTION_UNKNOWN : $tmp->code;

      // init the parent object with custom data
      parent::__construct( Exception\Helper::build( $this->_extension, $tmp->code . $this->_type, $this->_data ), $tmp->code, $previous );

      $this->_id = ( $this->_extension ? $this->_extension->id : '' ) . '#' . $this->getCode() . $this->_type;
    }
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
   * @param string|null $filter
   *
   * @return bool
   */
  public function match( $filter ) {
    return Exception\Helper::match( $this, $filter );
  }
  /**
   * Log the exception
   *
   * @param array    $data     Additional data to the log
   * @param Log|null $instance The logger instance use to create the log. If null, the Request::getLog() used
   *
   * @return $this
   * @throws Exception\Strict
   */
  public function log( $data = [ ], Log $instance = null ) {

    $instance = $instance ?: Request::getLog();
    if( $instance ) {

      // extend data
      $data                = Enumerable::cast( $data );
      $data[ 'exception' ] = $this->toArray( true );

      $instance->create( $this->message, $this->_data + $data, 'framework:exception!' . $this->id, $this->level );
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
   * Like toArray() just object
   *
   * @param bool $more Append additional data to the result
   *
   * @return object
   */
  public function toObject( $more = false ) {
    return (object) $this->toArray( $more );
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
   * @since 0.6.0
   *
   * @return string
   */
  public function getType() {
    return $this->_type;
  }
  /**
   * @since 0.6.0
   *
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
    return $this->toObject();
  }
}
