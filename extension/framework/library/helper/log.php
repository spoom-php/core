<?php namespace Framework\Helper;

use Framework\Exception\Strict;
use Framework\Extension;
use Framework\Storage;

/**
 * Class Log
 * @package Framework\Helper
 *
 * @property-read string namespace The default namespace for log entries
 * @property-read string name      The instance identifier
 */
class Log extends Library {

  /**
   * Exception throwed when an invalid type of log try to be created. Data:
   *  - type [int]: The invalid type
   */
  const EXCEPTION_NOTICE_INVALID_LEVEL = 'framework#7N';

  /**
   * Event called before every new log entry. This can prevent the default file log. Arguments:
   *  - instance [Log]: The Log instance that call this event
   *  - namespace [string]: The log entry namespace
   *  - type [int]: The log entry type (level)
   *  - description [string]: The message with the inserted data
   *  - &message [string]: The raw message
   *  - &data [Storage]: The raw data
   */
  const EVENT_CREATE = 'log.create';

  /**
   * Pattern for one log entry for file based logging. This can be processed as a csv row
   */
  const PATTERN_MESSAGE = "{time};{level};{namespace};{description};{data};{message}\n";

  /**
   * Holds the instanced loggers by $name
   *
   * @var Log[]
   */
  private static $instance = [ ];

  /**
   * @var Extension
   */
  private $extension;

  /**
   * Default namespace for the log instance
   *
   * @var string
   */
  private $_namespace;
  /**
   * Log instance name
   *
   * @var string
   */
  private $_name;
  /**
   * Default logger file output
   *
   * @var string
   */
  private $_file;

  /**
   * @param string $name      The instance identifier
   * @param string $namespace The default namespace for log entries
   */
  public function __construct( $name, $namespace = '' ) {

    $this->extension = Extension::instance( 'framework' );

    // save instance properties
    $this->_name      = $name;
    $this->_namespace = empty( $namespace ) ? (string) $this->extension : $namespace;

    // define the default log file
    if( is_dir( _PATH_BASE . \Framework::PATH_TMP ) || @mkdir( _PATH_BASE . \Framework::PATH_TMP, 0777, true ) ) {

      $date        = date( 'Ymd' );
      $this->_file = _PATH_BASE . \Framework::PATH_TMP . "{$name}-{$date}.log";
    }
  }

  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   * @param int                  $level     The log level
   *
   * @return bool
   * @throws Strict ::EXCEPTION_NOTICE_INVALID_TYPE on invalid type
   */
  public function create( $message, $data = [ ], $namespace = '', $level = \Framework::LEVEL_DEBUG ) {

    // check type against reporting level
    if( \Framework::logLevel() < $level ) return true;
    else if( !\Framework::getLevel( $level ) ) throw new Strict( self::EXCEPTION_NOTICE_INVALID_LEVEL, [ 'level' => $level ] );
    else {

      // define local variables and trigger event for external loggers
      $data        = $data instanceof Storage ? $data : new Storage( $data );
      $namespace   = empty( $namespace ) ? $this->_namespace : $namespace;
      $description = String::insert( $message, $data, String::TYPE_INSERT_LEAVE );
      $event       = $this->extension->trigger( self::EVENT_CREATE, [
        'instance'    => $this,
        'namespace'   => $namespace,
        'level'       => $level,
        'description' => $description,
        'message'     => &$message,
        'data'        => &$data
      ] );

      // check if the external loggers done the work
      if( !$event->prevented && $this->_file ) {

        list( $usec, $sec ) = explode( ' ', microtime() );
        file_put_contents( $this->_file, String::insert( self::PATTERN_MESSAGE, [
          'time'        => date( 'Y-m-d\TH:i:s.', $sec ) . substr( $usec, 2 ),
          'level'       => \Framework::getLevel( $level ),
          'namespace'   => str_replace( ';', ',', $namespace ),
          'message'     => str_replace( ';', ',', $message ),
          'data'        => str_replace( ';', ',', json_encode( $data ) ),
          'description' => str_replace( ';', ',', String::insert( $message, $data, String::TYPE_INSERT_LEAVE ) )
        ] ), FILE_APPEND );
      }

      return !$event->collector->contains();
    }
  }

  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function debug( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_DEBUG );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function info( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_INFO );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function notice( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_NOTICE );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function warning( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_WARNING );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function error( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_ERROR );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function critical( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_CRITICAL );
  }

  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getNamespace() {
    return $this->_namespace;
  }
  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getName() {
    return $this->_name;
  }
  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getFile() {
    return $this->_file;
  }

  /**
   * Instance factory (identified by the name)
   *
   * @param string $name The logger unique name
   *
   * @return Log
   */
  public static function instance( $name ) {

    if( !isset( self::$instance[ $name ] ) ) {
      self::$instance[ $name ] = new Log( $name );
    }

    return self::$instance[ $name ];
  }
}
