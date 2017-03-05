<?php namespace Spoom\Framework;

use Spoom\Framework\Converter\Json;
use Spoom\Framework\Helper;

/**
 * Interface LogInterface
 * @package Framework\Helper
 *
 * TODO create Unittests
 *
 * @property-read string $channel  The name of the logger
 * @property int         $severity Maximum severity level that will be logged
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
  public function create( $message, $data = [], $namespace = '', $severity = Application::SEVERITY_DEBUG );

  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function debug( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function info( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function notice( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function warning( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string $namespace The namespace for the log entry
   */
  public function error( $message, $data = [], $namespace = '' );
  /**
   * @param string               $message   The log message pattern
   * @param array|object|Storage $data      The pattern insertion or additional data
   * @param string               $namespace The namespace for the log entry
   */
  public function critical( $message, $data = [], $namespace = '' );

  /**
   * The name of the logger
   *
   * @return string
   */
  public function getChannel();
  /**
   * Maximum severity level that will be logged
   *
   * @return int
   */
  public function getSeverity();
  /**
   * @param int $value
   */
  public function setSeverity( $value );
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
  protected $event;
  /**
   * @var ConverterInterface
   */
  protected $converter;

  public function __construct( FileInterface $directory, $channel, $severity ) {

    $this->_directory = $directory;
    $this->_channel   = $channel;
    $this->_severity  = $severity;

    $this->event     = Extension::instance()->getEvent();
    $this->converter = new Json();
  }

  //
  public function create( $message, $data = [], $namespace = '', $severity = Application::SEVERITY_DEBUG ) {

    // check type against logging level
    if( !self::$protect && $severity > Application::SEVERITY_NONE && $severity <= $this->getSeverity() ) {
      self::$protect = true;

      // log MUST NOT throw any exception!
      try {

        // define the log entry datetime (with microsec!)
        list( $usec, $sec ) = explode( ' ', microtime() );
        $datetime = date( 'Y-m-d\TH:i:s', $sec ) . '.' . substr( $usec, 2, 4 ) . date( 'O', $sec );

        // add backtrace for the data, if needed
        $data = $data instanceof StorageInterface ? $data : new Storage( $data );
        if( !$data->exist( 'backtrace' ) ) $data->set( 'backtrace', array_slice( debug_backtrace(), 1 ) );

        // trigger event for the log entry
        $event = $this->event->trigger( new Event( static::EVENT_CREATE, [
          'instance'    => $this,
          'namespace'   => $namespace,
          'severity'    => $severity,
          'datetime'    => $datetime,
          'description' => Helper\Text::insert( $message, $data, true ),
          'message'     => $message,
          'data'        => $data
        ] ) );
        if( !$event->isPrevented() ) {

          $message     = $event->getString( 'message', $message );
          $data        = $event->get( 'data', $data );
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
  public function debug( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_DEBUG );
  }
  //
  public function info( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_INFO );
  }
  //
  public function notice( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_NOTICE );
  }
  //
  public function warning( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_WARNING );
  }
  //
  public function error( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_ERROR );
  }
  //
  public function critical( $message, $data = [], $namespace = '' ) {
    return $this->create( $message, $data, $namespace, Application::SEVERITY_CRITICAL );
  }

  /**
   * @since ???
   * @since 0.6.0
   *
   * @param string|null $prefix
   *
   * @return FileInterface
   */
  protected function getFile( $prefix = null ) {
    return $this->_directory->get( ( $prefix ? ( $prefix . '-' ) : '' ) . $this->getChannel() );
  }

  //
  public function getChannel() {
    return $this->_channel;
  }
  //
  public function getSeverity() {
    return $this->_severity;
  }
  //
  public function setSeverity( $severity ) {
    $this->_severity = $severity;
  }
}
