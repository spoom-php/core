<?php namespace Framework;

use Framework\Helper;

/**
 * Interface LogInterface
 * @package Framework\Helper
 *
 * @property-read string $namespace The default namespace for log entries
 * @property-read string $name      The name of the logger
 */
interface LogInterface {

  /**
   * This event MUST be called before every new log entry. This can prevent the log
   *
   * @param LogInterface     $instance    The Log instance that call this event
   * @param string           $namespace   *The log entry namespace
   * @param int              $level       The log entry type
   * @param string           $datetime    *'Y-m-d\TH:i:s.uO' format
   * @param string           $description *The message with the inserted data
   * @param string           $message     *The raw message
   * @param StorageInterface $data        *The raw data
   */
  const EVENT_CREATE = 'log.create';

  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   * @param int                  $level     The log level
   *
   * @return bool
   */
  public function create( $message, $data = [], $namespace = '', $level = \Framework::LEVEL_DEBUG );

  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function debug( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function info( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function notice( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function warning( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function error( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function critical( $message, $data = [], $namespace = '' );

  /**
   * The default namespace for the log entry
   *
   * @return string
   */
  public function getNamespace();
  /**
   * The name of the logger
   *
   * @return string
   */
  public function getName();
}

/**
 * Class Log
 * @package Framework\Helper
 *
 * @property-read string $file      The default log file
 * @property-read string $name
 * @property-read string $namespace Default namespace for the log entry
 */
class Log implements LogInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Pattern for one log entry for file based logging. This can be processed as a csv row
   */
  const PATTERN_MESSAGE = "{time};{level};{namespace};{description};{data};{message}\n";

  /**
   * Holds the instanced loggers by $name
   *
   * @var LogInterface[]
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
   * @var FileInterface
   */
  private $_file = null;

  /**
   * @param string $name      The instance identifier
   * @param string $namespace The default namespace for log entries
   */
  public function __construct( $name, $namespace = '' ) {

    $this->extension = Extension::instance( 'framework' );

    // save instance properties
    $this->_name      = $name;
    $this->_namespace = empty( $namespace ) ? $this->extension->getId() : $namespace;
  }

  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   * @param int                  $level     The log level
   *
   * @return bool
   */
  public function create( $message, $data = [], $namespace = '', $level = \Framework::LEVEL_DEBUG ) {

    // check type against reporting level
    if( $level <= \Framework::LEVEL_NONE || $level > \Framework::getLog() ) return true;
    else {

      // pre-process the data
      $data = $data instanceof StorageInterface ? $data : new Storage( $data );
      if( !$data->exist( 'backtrace' ) ) $data->set( 'backtrace', array_slice( debug_backtrace(), 1 ) );

      // define local variables and trigger event for external loggers
      list( $usec, $sec ) = explode( ' ', microtime() );
      $datetime    = date( 'Y-m-d\TH:i:s', $sec ) . '.' . substr( $usec, 2, 4 ) . date( 'O', $sec );
      $namespace   = empty( $namespace ) ? $this->_namespace : $namespace;
      $description = Helper\Text::insert( $message, $data, true );
      $event       = $this->extension->trigger( static::EVENT_CREATE, [
        'instance'    => $this,
        'namespace'   => $namespace,
        'level'       => $level,
        'datetime'    => $datetime,
        'description' => $description,
        'message'     => $message,
        'data'        => $data
      ] );

      // check if the external loggers done the work
      if( !$event->isPrevented() && $this->getFile() ) try {

        $message     = $event->getString( 'message', $message );
        $data        = $event->get( 'data', $data );
        $description = $event->getString( 'description', Helper\Text::insert( $message, $data, true ) );

        $this->getFile()->write( Helper\Text::insert( static::PATTERN_MESSAGE, [
          'time'        => $event->getString( 'datetime', $datetime ),
          'level'       => \Framework::getLevel( $level ),
          'namespace'   => str_replace( [ ';', "\n" ], [ ',', '' ], $event->getString( 'namespace', $namespace ) ),
          'message'     => str_replace( [ ';', "\n" ], [ ',', '' ], $message ),
          'data'        => str_replace( [ ';', "\n" ], [ ',', '' ], json_encode( $data ) ),
          'description' => str_replace( [ ';', "\n" ], [ ',', '' ], $description )
        ] ) );

        return true;

      } catch( \Exception $e ) {
        // suppress exceptions for the logger
      }

      return !$event->isPrevented() && !$event->getException();
    }
  }

  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function debug( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_DEBUG );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function info( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_INFO );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function notice( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_NOTICE );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function warning( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_WARNING );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function error( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, \Framework::LEVEL_ERROR );
  }
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   *
   * @return bool
   */
  public function critical( $message, $data = [], $namespace = '' ) {
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
   * @since ???
   * @since 0.6.0
   *
   * @return FileInterface
   */
  public function getFile() {

    // define the default log file
    if( $this->_file === null ) {
      $this->_file = false;

      $tmp = Application::getFile( \Framework::PATH_TMP . date( 'Ymd' ) . '-' . $this->_name . '.log' );
      try {
        $this->_file = $tmp->create();
      } catch( \Exception $e ) {
      }
    }

    return $this->_file;
  }

  /**
   * Instance factory (identified by the name)
   *
   * @param string $name The logger unique name
   *
   * @return LogInterface
   */
  public static function instance( $name ) {
    return isset( self::$instance[ $name ] ) ? self::$instance[ $name ] : ( self::$instance[ $name ] = new static( $name ) );
  }
}
