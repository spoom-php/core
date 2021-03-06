<?php namespace Spoom\Core;

use Spoom\Core\Helper;
use Spoom\Core\Helper\Collection;

/**
 * TODO add ability to collect and commit or rollback entries based on their severity
 */
interface LoggerInterface {

  /**
   * The logger must be cloneable to use the instance as a "factory" for the new individual instances
   */
  public function __clone();

  /**
   * Flush out buffered log entries
   *
   * @param int $limit Limit the flushed entries, or flush it all (by default)
   *
   * @return static
   */
  public function flush( int $limit = 0 );
  /**
   * Clear (all) entires from the buffer, without saving it
   *
   * @param int $limit Limit the cleared entries, or all (by default)
   *
   * @return static
   */
  public function clear( int $limit = 0 );

  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   * @param int    $severity  The log level
   *
   * @return static
   */
  public function create( string $message, $context = null, string $namespace = '', int $severity = Severity::DEBUG );
  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   *
   * @return static
   */
  public function debug( string $message, $context = null, string $namespace = '' );
  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   *
   * @return static
   */
  public function info( string $message, $context = null, string $namespace = '' );
  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   *
   * @return static
   */
  public function notice( string $message, $context = null, string $namespace = '' );
  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   *
   * @return static
   */
  public function warning( string $message, $context = null, string $namespace = '' );
  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   *
   * @return static
   */
  public function error( string $message, $context = null, string $namespace = '' );
  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   *
   * @return static
   */
  public function critical( string $message, $context = null, string $namespace = '' );
  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   *
   * @return static
   */
  public function alert( string $message, $context = null, string $namespace = '' );
  /**
   * @param string $message   The log message pattern
   * @param mixed  $context   The pattern insertion or additional data, or a callable that will run only if the entry will be added for the buffer list
   * @param string $namespace The namespace for the log entry. This is useful for searching or filtering
   *
   * @return static
   */
  public function emergency( string $message, $context = null, string $namespace = '' );

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

//
class Logger implements LoggerInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Protect against infinity loop, if someting loggable happens inside the log mechanism
   *
   * @var bool
   */
  private $protect = false;

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
   * List of buffered log entries
   *
   * @var array[]
   */
  protected $_list = [];

  /**
   * @param string   $channel
   * @param int|null $severity
   */
  public function __construct( string $channel, ?int $severity = null ) {

    $this->_channel  = $channel;
    $this->_severity = $severity ?? Severity::get();
  }

  //
  public function __clone() {

    // the entry buffer must be empty after cloning to start fresh
    $this->_list = [];
  }

  //
  public function flush( int $limit = 0 ) {

    // there is nowhere to flush, but the event should triggered anyway
    if( !empty( $this->_list ) ) try {
      new LoggerEventFlush( $this, $limit );
    } catch( \Throwable $_ ) {
      // there must be no exception from the logger
    }

    return $this;
  }
  //
  public function clear( int $limit = 0 ) {

    $this->_list = $limit < 1 ? [] : array_slice( $this->_list, 0, $limit );
    return $this;
  }

  //
  public function create( string $message, $context = null, string $namespace = '', int $severity = Severity::DEBUG ) {

    // build storable entry from the input to easily manipulate and pass as argument later
    $entry = [
      'time'      => microtime( true ),
      'namespace' => $namespace,
      'message'   => $message,
      'context'   => $context,
      'severity'  => $severity
    ];

    // check type against logging level
    if( !$this->protect && static::filter( $entry, $this->_severity ) ) {
      $this->protect = true;

      // log MUST NOT throw any exception!
      try {

        // before we store the entry, trigger an event for it and let the event handlers change the entry or prevent the storing
        if( !($event = new LoggerEventCreate( $this, $entry ))->isPrevented() ) {

          // map modified entry values back from the event
          $entry = Collection::map( $event->entry, $entry, [ 'namespace', 'message', 'context' ] );
          if( is_callable( $entry['context'] ) ) $entry['context'] = $entry['context']();
          $this->_list[] = $entry;

          // TODO it should try to flush entries after every X addition or maybe after a (short) timeout
        }

      } catch( \Throwable $_ ) {
        // suppress exceptions for the logger
      }

      $this->protect = false;
    }

    return $this;
  }

  //
  public function debug( string $message, $context = null, string $namespace = '' ) {
    return $this->create( $message, $context, $namespace, Severity::DEBUG );
  }
  //
  public function info( string $message, $context = null, string $namespace = '' ) {
    return $this->create( $message, $context, $namespace, Severity::INFO );
  }
  //
  public function notice( string $message, $context = null, string $namespace = '' ) {
    return $this->create( $message, $context, $namespace, Severity::NOTICE );
  }
  //
  public function warning( string $message, $context = null, string $namespace = '' ) {
    return $this->create( $message, $context, $namespace, Severity::WARNING );
  }
  //
  public function error( string $message, $context = null, string $namespace = '' ) {
    return $this->create( $message, $context, $namespace, Severity::ERROR );
  }
  //
  public function critical( string $message, $context = null, string $namespace = '' ) {
    return $this->create( $message, $context, $namespace, Severity::CRITICAL );
  }
  //
  public function alert( string $message, $context = null, string $namespace = '' ) {
    return $this->create( $message, $context, $namespace, Severity::ALERT );
  }
  //
  public function emergency( string $message, $context = null, string $namespace = '' ) {
    return $this->create( $message, $context, $namespace, Severity::EMERGENCY );
  }

  /**
   * Get the list of buffered log entires
   */
  public function getList(): array {
    return $this->_list;
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

  /**
   * @param array $entry
   * @param int   $severity
   *
   * @return bool
   */
  public static function filter( array $entry, int $severity ) {
    return $entry['severity'] > Severity::NONE && $entry['severity'] <= $severity;
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
  public static function dump( $data, int $deep = 10 ) {

    if( --$deep < 0 ) return '...';
    else if( !Collection::is( $data ) ) return $data;
    else {

      // handle custom classes
      $_data = Collection::copy( $data, false );
      if( is_object( $data ) && !( $data instanceof \StdClass ) ) try {
        if( \method_exists( $data, '__debugInfo'  ) ) $_data = $data->__debugInfo();
        else {

          $reflection = new \ReflectionClass( $data );
          $tmp        = [ '__CLASS__' => $reflection->getName() ];

          foreach( $reflection->getProperties() as $property ) {
            if( !$property->isStatic() ) {
              $property->setAccessible( true );

              $key                                = $property->isPrivate() ? '-' : ( $property->isProtected() ? '#' : '+' );
              $tmp[ $key . $property->getName() ] = $property->getValue( $data );
            }
          }

          $_data = $tmp;
        }

      } catch( \ReflectionException $_ ) {
        // can't do anything
      }

      //
      foreach( $_data as &$value ) $value = static::dump( $value, $deep );
      return $_data;
    }
  }
}

/**
 * Event before log entry creation
 *
 * Prevention will discard the entry. The `namespace`, `message` and `context` properites of the $entry can be modified in the callbacks
 */
class LoggerEventCreate extends Event {

  /**
   * @var LoggerInterface
   */
  public $instance;
  /**
   * @var array
   */
  public $entry;

  /**
   * @param LoggerInterface $instance
   * @param array           $entry
   */
  public function __construct( LoggerInterface $instance, array $entry ) {
    $this->instance = $instance;
    $this->entry = $entry;
  }
}

/**
 * Event before log entries flushing, implemented in the child classes
 *
 * Prevention will cancel the default flush behavior
 */
class LoggerEventFlush extends Event {

  /**
   * @var LoggerInterface
   */
  public $instance;
  /**
   * @var int
   */
  public $limit;

  /**
   * @param LoggerInterface $instance
   * @param int             $limit
   */
  public function __construct( LoggerInterface $instance, int $limit ) {
    $this->instance = $instance;
    $this->limit = $limit;
  }
}
