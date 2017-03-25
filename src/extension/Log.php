<?php namespace Spoom\Framework;

use Spoom\Framework\Converter\Json;
use Spoom\Framework\Helper;
use Spoom\Framework\Helper\Enumerable;

/**
 * Interface LogInterface
 * @package Framework\Helper
 *
 * @property string $channel  The name of the logger
 * @property int    $severity Maximum severity level that will be logged
 */
interface LogInterface {

  /**
   * This event MUST be called before every new log entry. This can prevent the log
   *
   * @param LogInterface     $instance    The Log instance that call this event
   * @param string           $namespace   *The log entry namespace
   * @param int              $severity    The log entry type
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
   * @param int                  $severity  The log level
   */
  public function create( string $message, $data = [], string $namespace = '', int $severity = Application::SEVERITY_DEBUG );

  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function debug( string $message, $data = [], string $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function info( string $message, $data = [], string $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function notice( string $message, $data = [], string $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function warning( string $message, $data = [], string $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function error( string $message, $data = [], string $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function critical( string $message, $data = [], string $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function alert( string $message, $data = [], string $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function emergency( string $message, $data = [], string $namespace = '' );

  /**
   * The name of the logger
   *
   * @return string
   */
  public function getChannel(): string;
  /**
   * @param string $value
   */
  public function setChannel( string $value );
  /**
   * Maximum severity level that will be logged
   *
   * @return int
   */
  public function getSeverity(): int;
  /**
   * @param int $value
   */
  public function setSeverity( int $value );
}

/**
 * Class Log
 * @package Framework\Helper
 *
 * @property-read FileInterface $file The default log file
 */
class Log implements LogInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Protect against infinity loop, if someting loggable happens inside the log mechanism
   *
   * @var bool
   */
  private static $protect = false;

  /**
   * Directory for the log files
   *
   * @var FileInterface
   */
  private $_directory = null;
  /**
   * Filename base for the logs
   *
   * @var string
   */
  private $_channel;
  /**
   * @var int
   */
  private $_severity;

  /**
   * Event storage for triggering the LogInterface::EVENT_CREATE
   *
   * @var Event\StorageInterface
   */
  protected $event_storage;
  /**
   * @var ConverterInterface
   */
  protected $converter;

  /**
   * @param FileInterface $directory
   * @param string        $channel
   * @param int           $severity
   */
  public function __construct( FileInterface $directory, string $channel, int $severity ) {

    $this->_directory = $directory;
    $this->_channel   = $channel;
    $this->_severity  = $severity;

    //
    $this->converter = new Json();
  }

  //
  public function create( string $message, $data = [], string $namespace = '', int $severity = Application::SEVERITY_DEBUG ) {

    // check type against logging level
    if( !self::$protect && $severity > Application::SEVERITY_NONE && $severity <= $this->getSeverity() ) {
      self::$protect = true;

      // log MUST NOT throw any exception!
      try {

        // define the log entry datetime (with microsec!)
        list( $usec, $sec ) = explode( ' ', microtime() );
        $datetime = date( 'Y-m-d\TH:i:s', $sec ) . '.' . substr( $usec, 2, 4 ) . date( 'O', $sec );

        // add backtrace for the data, if needed
        $data = Enumerable::read( $data, [] );
        if( !isset( $data[ 'backtrace' ] ) ) $data[ 'backtrace' ] = array_slice( debug_backtrace(), 1 );

        // trigger event for the log entry
        $event = $this->getEventStorage()->trigger( new Event( static::EVENT_CREATE, [
          'instance'  => $this,
          'namespace' => $namespace,
          'severity'  => $severity,
          'datetime'  => $datetime,
          'message'   => $message,
          'data'      => $data
        ] ) );
        if( !$event->isPrevented() ) {

          $message     = $event->getString( 'message', $message );
          $data        = $event[ 'data' ] ?? $this->wrap( $data );
          $description = $event->getString( 'description', Helper\Text::insert( $message, $data, true ) );
          $datetime    = $event->getString( 'datetime', $datetime );

          // FIXME this could be done with streaming support
          $this->getFile( date( 'Ymd', $sec ) )->write( $this->converter->serialize( [
              'time'        => $datetime,
              'severity'    => $severity,
              'namespace'   => $event->getString( 'namespace', $namespace ),
              'message'     => $message,
              'description' => $description,
              'data'        => $data
            ] ) . "\n" );
        }

      } catch( \Throwable $e ) {
        // suppress exceptions for the logger
      }

      self::$protect = false;
    }
  }

  //
  public function debug( string $message, $data = [], string $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_DEBUG );
  }
  //
  public function info( string $message, $data = [], string $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_INFO );
  }
  //
  public function notice( string $message, $data = [], string $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_NOTICE );
  }
  //
  public function warning( string $message, $data = [], string $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_WARNING );
  }
  //
  public function error( string $message, $data = [], string $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_ERROR );
  }
  //
  public function critical( string $message, $data = [], string $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_CRITICAL );
  }
  //
  public function alert( string $message, $data = [], string $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_ALERT );
  }
  //
  public function emergency( string $message, $data = [], string $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_EMERGENCY );
  }

  /**
   * Preprocess the data before log it
   *
   * This will convert classes to array of properties with reflection
   *
   * @param mixed $data
   * @param int   $deep Max process recursion
   *
   * @return mixed
   */
  public function wrap( $data, $deep = 10 ) {

    if( --$deep < 0 || !Enumerable::is( $data ) ) return $data;
    else {

      // handle custom classes
      if( is_object( $data ) && !( $data instanceof \StdClass ) ) {

        $reflection = new \ReflectionClass( $data );
        $tmp        = [ '__CLASS__' => $reflection->getName() ];

        foreach( $reflection->getProperties() as $property ) {
          if( !$property->isStatic() ) {
            $property->setAccessible( true );

            $key                                = $property->isPrivate() ? '-' : ( $property->isProtected() ? '#' : '+' );
            $tmp[ $key . $property->getName() ] = $property->getValue( $data );
          }
        }

        $data = $tmp;
      }

      //
      foreach( $data as &$value ) $value = $this->wrap( $value );
      return $data;
    }
  }

  /**
   * @since ???
   * @since 0.6.0
   *
   * @param string|null $prefix
   *
   * @return FileInterface
   */
  public function getFile( ?string $prefix = null ): FileInterface {
    return $this->_directory->get( ( $prefix ? ( $prefix . '-' ) : '' ) . $this->getChannel() . '.log' );
  }
  /**
   * @return Event\StorageInterface
   */
  public function getEventStorage(): Event\StorageInterface {

    //
    if( empty( $this->event_storage ) ) {
      $this->event_storage = Extension::instance()->getEventStorage();
    }

    return $this->event_storage;
  }

  //
  public function getChannel(): string {
    return $this->_channel;
  }
  //
  public function setChannel( string $value ) {
    $this->_channel = $value;
  }
  //
  public function getSeverity(): int {
    return $this->_severity;
  }
  //
  public function setSeverity( int $severity ) {
    $this->_severity = $severity;
  }
}
