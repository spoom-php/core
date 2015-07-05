<?php namespace Framework;

use Framework\Exception\Helper;
use Framework\Extension;
use Framework\Helper\LibraryInterface;
use Framework\Helper\Log;

/**
 * Extend simple PHP \Exception with the power of code base text with language and insertion support
 *
 * @package Framework
 *
 * @property-read array       $data      The data attached to the exception
 * @property-read Extension   $extension The message source
 * @property-read string      $type      The "danger level". This can only be a self::TYPE_* constants
 * @property-read string      $id        The unique identifier. The format is '<extension>#<code><type>'
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
   * Source of the error message if not null
   *
   * @var Extension
   */
  private $_extension = null;

  /**
   * Initialise the custom Exception object, with extension and code specified message or a simple string message
   *
   * @param string|\Exception $id
   * @param array             $data
   * @param \Exception        $previous
   */
  public function __construct( $id, array $data = [ ], \Exception $previous = null ) {

    // parse id to "properties"
    $tmp              = Exception\Helper::parse( $id );
    $this->_extension = Extension::instance( $tmp->extension );
    $this->_type      = $tmp->type;

    // save data
    $this->_data = $data;

    // init the parent object with custom data
    parent::__construct( Exception\Helper::build( $this->_extension, $tmp->code . $this->_type, $this->_data ), $tmp->code, $previous );
  }

  /**
   * @param string $index
   *
   * @return mixed
   */
  public function __get( $index ) {

    switch( $index ) {
      case 'id':
        return ( $this->_extension ? $this->_extension->id : '' ) . '#' . $this->getCode() . $this->_type;
      default:

        $index = '_' . $index;
        if( property_exists( $this, $index ) ) return $this->{$index};
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
    return $this->id . ': ' . $this->getMessage();
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
      $data = $data instanceof Storage ? $data->getArray( '' ) : (array) $data;

      // define the log type
      $type = Log::TYPE_DEBUG;
      switch( $this->type ) {
        case self::TYPE_CRITICAL:
          $type = Log::TYPE_CRITICAL;
          break;
        case self::TYPE_ERROR:
          $type = Log::TYPE_ERROR;
          break;
        case self::TYPE_WARNING:
          $type = Log::TYPE_WARNING;
          break;
        case self::TYPE_NOTICE:
          $type = Log::TYPE_NOTICE;
          break;
      }

      // create a new log entry
      $data['exception'] = $this->toArray( true );
      $instance->create( $this->message, $this->data + $data, $this->id, $type );
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
      $tmp[ 'line' ]  = $this->getFile() . ':' . $this->getLine();
      $tmp[ 'trace' ] = $this->getTrace();
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
   * Specify data which should be serialized to JSON
   * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
   * @return mixed data which can be serialized by <b>json_encode</b>,
   * which is a value of any type other than a resource.
   */
  public function jsonSerialize() {
    return $this->toObject();
  }
}
