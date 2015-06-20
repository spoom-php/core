<?php namespace Framework\Helper;

use Framework\Exception\Strict;
use Framework\Extension;
use Framework\Storage\Single;

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
  const EXCEPTION_NOTICE_INVALID_TYPE = 'framework#7N';

  /**
   * Event called before every new log entry. This can prevent the default file log. Arguments:
   *  - instance [Log]: The Log instance that call this event
   *  - namespace [string]: The log entry namespace
   *  - type [int]: The log entry type (level)
   *  - description [string]: The message with the inserted data
   *  - &message [string]: The raw message
   *  - &data [Single]: The raw data
   */
  const EVENT_CREATE = 'log.create';

  /**
   * Level of critical logs
   */
  const TYPE_CRITICAL = _LEVEL_CRITICAL;
  /**
   * Level of error logs
   */
  const TYPE_ERROR = _LEVEL_ERROR;
  /**
   * Level of warning logs
   */
  const TYPE_WARNING = _LEVEL_WARNING;
  /**
   * Level of notice logs
   */
  const TYPE_NOTICE = _LEVEL_NOTICE;
  /**
   * Level of info logs
   */
  const TYPE_INFO = _LEVEL_INFO;
  /**
   * Level of debug logs
   */
  const TYPE_DEBUG = _LEVEL_DEBUG;

  /**
   * Name of critical logs
   */
  const NAME_CRITICAL = 'critical';
  /**
   * Name of error logs
   */
  const NAME_ERROR = 'error';
  /**
   * Name of warning logs
   */
  const NAME_WARNING = 'warning';
  /**
   * Name of notice logs
   */
  const NAME_NOTICE = 'notice';
  /**
   * Name of info logs
   */
  const NAME_INFO = 'info';
  /**
   * Name of debug logs
   */
  const NAME_DEBUG = 'debug';

  /**
   * Pattern for one log entry for file based logging. This can be processed as a csv row
   */
  const PATTERN_MESSAGE = "{time};{type};{namespace};{message};{data};{description}\n";

  /**
   * Map log levels to log name
   *
   * @var array[int]string
   */
  private static $TYPE_NAME = [
    self::TYPE_CRITICAL => self::NAME_CRITICAL,
    self::TYPE_ERROR    => self::NAME_ERROR,
    self::TYPE_WARNING  => self::NAME_WARNING,
    self::TYPE_NOTICE   => self::NAME_NOTICE,
    self::TYPE_INFO     => self::NAME_INFO,
    self::TYPE_DEBUG    => self::NAME_DEBUG,
  ];

  /**
   * Holds the instanced loggers by $name
   *
   * @var Log[]
   */
  private static $instance = [];
  
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
    if( is_dir( _PATH_BASE . _PATH_TMP ) || mkdir( _PATH_BASE . _PATH_TMP, 0777, true ) ) {

      $date        = date( 'Ymd' );
      $this->_file = _PATH_BASE . _PATH_TMP . "{$name}-{$date}.log";
    }
  }

  /**
   * @param string $index
   *
   * @return mixed
   */
  public function __get( $index ) {

    $iindex = '_' . $index;
    if( property_exists( $this, $iindex ) ) return $this->{$iindex};
    else return null;
  }
  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index );
  }

  /**
   * @param string              $message   The log message pattern
   * @param array|object|Single $data      The pattern insertion or additional data
   * @param string              $namespace The namespace for the log entry
   * @param int                 $type      The log level
   *
   * @return bool
   * @throws Strict ::EXCEPTION_NOTICE_INVALID_TYPE on invalid type
   */
  public function create( $message, $data = [ ], $namespace = '', $type = self::TYPE_INFO ) {

    // check type against reporting level
    if( !_LOG_LEVEL || _LOG_LEVEL < $type ) return true;
    else if( !isset( self::$TYPE_NAME[ $type ] ) ) throw new Strict( self::EXCEPTION_NOTICE_INVALID_TYPE, [ 'type' => $type ] );
    else {

      // define local variables and trigger event for external loggers
      $data        = $data instanceof Single ? $data : new Single( $data );
      $namespace   = empty( $namespace ) ? $this->_namespace : $namespace;
      $description = String::insert( $message, $data, String::TYPE_INSERT_LEAVE );
      $event       = $this->extension->trigger( self::EVENT_CREATE, [
        'instance' => $this, 'namespace' => $namespace, 'type' => $type, 'description' => $description,
        'message'  => &$message, 'data' => &$data
      ] );

      // check if the external loggers done the work
      if( !$event->prevented && $this->_file ) {

        list( $usec, $sec ) = explode( ' ', microtime() );
        file_put_contents( $this->_file, String::insert( self::PATTERN_MESSAGE, [
          'time'        => date( 'Y-m-d\TH:i:s.', $sec ) . substr( $usec, 2 ),
          'type'        => self::$TYPE_NAME[ $type ],
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
   * @param string              $message   The log message pattern
   * @param array|object|Single $data      The pattern insertion or additional data
   * @param string              $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function debug( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, self::TYPE_DEBUG );
  }
  /**
   * @param string              $message   The log message pattern
   * @param array|object|Single $data      The pattern insertion or additional data
   * @param string              $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function info( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, self::TYPE_INFO );
  }
  /**
   * @param string              $message   The log message pattern
   * @param array|object|Single $data      The pattern insertion or additional data
   * @param string              $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function notice( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, self::TYPE_NOTICE );
  }
  /**
   * @param string              $message   The log message pattern
   * @param array|object|Single $data      The pattern insertion or additional data
   * @param string              $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function warning( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, self::TYPE_WARNING );
  }
  /**
   * @param string              $message   The log message pattern
   * @param array|object|Single $data      The pattern insertion or additional data
   * @param string              $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function error( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, self::TYPE_ERROR );
  }
  /**
   * @param string              $message   The log message pattern
   * @param array|object|Single $data      The pattern insertion or additional data
   * @param string              $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function critical( $message, $data = [ ], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, self::TYPE_CRITICAL );
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
