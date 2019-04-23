<?php namespace Spoom\Core\Logger;

use Spoom\Core\Converter;
use Spoom\Core\ConverterInterface;
use Spoom\Core\FileInterface;
use Spoom\Core\Helper\StreamInterface;
use Spoom\Core\Logger;
use Spoom\Core\LoggerInterface;
use Spoom\Core\Helper\Text;
use Spoom\Core\Application;
use Spoom\Core\LoggerEventFlush;
/**
 * This logger will add entries to files
 *
 * @property      string        $channel  The name of the logger
 * @property      int           $severity Maximum severity level that will be logged
 * @property-read FileInterface $file     The default log file
 */
class File extends Logger {

  /**
   * Directory for the log files
   *
   * @var FileInterface
   */
  private $_directory = null;

  /**
   * @var ConverterInterface
   */
  protected $converter;

  /**
   * @param FileInterface           $directory
   * @param string                  $channel
   * @param int                     $severity
   * @param null|ConverterInterface $converter
   */
  public function __construct( FileInterface $directory, string $channel, int $severity = Application::SEVERITY_DEBUG, ?ConverterInterface $converter = null ) {
    parent::__construct( $channel, $severity );

    $this->_directory = $directory;

    //
    $this->converter = $converter ?? new Converter();
  }

  /**
   * Flush buffers on destruct the make them permanently stored
   */
  public function __destruct() {
    $this->flush();
  }

  //
  public function flush( int $limit = 0 ) {

    // we can't do anything with an empty list
    if( !empty( $this->_list ) ) try {

      //
      $stream = $this->getFile()->stream( StreamInterface::MODE_WA );
      if( !(new LoggerEventFlushFile( $this, $limit, $stream ))->isPrevented() ) {

        for( $i = 0, $length = count( $this->_list ); ($limit < 1 || $i < $limit) && $i < $length; ++$i ) {

          $entry = $this->_list[$i];
          $entry[ 'description' ] = Text::apply( $entry[ 'message' ], $entry[ 'context' ] ?? [] );
          $entry[ 'context' ] = static::dump( $entry[ 'context' ] ?? null, 5 );

          $this->converter->serialize( $entry, $stream );
          $stream->write( "\n" );
        }

        // flush and destroy the stream to finalize the write operations
        $stream->flush();
        unset( $stream );

        // we should clear the flushed entires from the list to prevent duplications
        $this->clear( $limit );
      }

    } catch( \Throwable $_ ) {
      // there must be no exception from the logger
    }

    return $this;
  }

  /**
   * @return FileInterface
   */
  public function getFile(): FileInterface {
    return $this->_directory->get( ( date( 'Ymd', time() ) . '-' ) . $this->getChannel() . '.log' );
  }
}

/**
 * Extends the `LoggerEventFlush` event with a stream param
 *
 * Prevention will cancel the default flush behavior. The `stream` can be modified in the callbacks
 */
class FileEventFlush extends LoggerEventFlush {

  /**
   * @var StreamInterface
   */
  public $stream;

  /**
   * @param LoggerInterface $instance
   * @param int             $limit
   * @param StreamInterface $stream
   */
  public function __construct( LoggerInterface $instance, int $limit, StreamInterface $stream ) {
    $this->stream   = $stream;

    parent::__construct( $instance, $limit );
  }
}
