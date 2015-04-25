<?php namespace Framework;

use Framework\Extension;
use Framework\Helper\LibraryInterface;
use Framework\Helper\Log;
use Framework\Storage\Single;

/**
 * Extend simple PHP \Exception with the power of code base text with language and insertion support
 *
 * @package Framework
 *
 * @property array       $data      The data attached to the exception
 * @property Extension   $extension The message source
 * @property string      $type      The "danger level". This can only be a self::TYPE_* constants
 * @property string      $id        The unique identifier. The format is '<extension>#<code><type>'
 */
abstract class Exception extends \Exception implements \JsonSerializable, LibraryInterface {

  /**
   * Type for impactless exception
   */
  const TYPE_NOTICE = 'N';
  /**
   * Type for exception that SHOULD NOT break the execution
   */
  const TYPE_WARNING = 'W';
  /**
   * Type for exception that MAY break the execution
   */
  const TYPE_ERROR = 'E';
  /**
   * Type for exception that MUST break the execution
   */
  const TYPE_CRITICAL = 'C';

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
    $this->_extension = new Extension( $tmp->extension );
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
   * @param Log|null $instance The logger instance use to create the log. If null, the Page::getLog() used
   *
   * @return $this
   * @throws Exception\Strict
   */
  public function log( $data = [ ], Log $instance = null ) {

    $instance = $instance ?: Page::getLog();
    if( $instance ) {

      // extend data
      $data = $data instanceof Single ? $data->getArray( '' ) : (array) $data;

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
      $instance->create( $this->message, $this->data + $data, $this->id, $type );
    }

    return $this;
  }

  /**
   * Make an associative array from the exception
   *
   * @return array
   */
  public function toArray() {
    return [
      'id'        => $this->id,
      'code'      => $this->code,
      'message'   => $this->getMessage(),
      'extension' => $this->_extension ? $this->_extension->id : null,
      'data'      => $this->_data
    ];
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
   * Specify data which should be serialized to JSON
   * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
   * @return mixed data which can be serialized by <b>json_encode</b>,
   * which is a value of any type other than a resource.
   */
  public function jsonSerialize() {
    return $this->toObject();
  }
}
