<?php namespace Spoom\Core\Helper;

use Spoom\Core\Exception;

/**
 * Interface StreamInterface
 */
interface StreamInterface extends \Countable {

  /**
   * Creates a readable stream
   */
  const MODE_READ = 2;
  /**
   * Creates a writeable stream
   */
  const MODE_WRITE = 4;
  /**
   * Create will append not rewrite
   */
  const MODE_APPEND = 8;

  /**
   * Creates a read-writeable stream
   */
  const MODE_RW = self::MODE_READ | self::MODE_WRITE;
  /**
   * Creates a writeable (append) stream
   */
  const MODE_WA = self::MODE_WRITE | self::MODE_APPEND;
  /**
   * Creates a read-writeable (append) stream
   */
  const MODE_RWA = self::MODE_READ | self::MODE_WRITE | self::MODE_APPEND;

  /**
   * Convert the stream into a string, begin from the current cursor
   *
   * @return string
   */
  public function __toString();

  /**
   * Write to the stream
   *
   * @param string|StreamInterface $content The content to write
   * @param int|null               $offset  Offset in the stream where to write. Default (===null) is the current cursor
   *
   * @return static
   * @throws StreamExceptionInvalid Invalid input or instance stream
   */
  public function write( $content, int $offset = null );
  /**
   * Read from the stream
   *
   * @param int                  $length The maximum byte to read
   * @param int|null             $offset Offset in the stream to read from. Default (===null) is the current cursor
   * @param StreamInterface|null $stream Output stream if specified
   *
   * @return string
   * @throws StreamExceptionInvalid Invalid input or instance stream
   */
  public function read( int $length = 0, int $offset = null, StreamInterface $stream = null );

  /**
   * Move the internal cursor within the stream
   *
   * @param int $offset The new cursor position
   *
   * @return static
   * @throws \InvalidArgumentException when the offset is negative
   */
  public function seek( int $offset = 0 );

  /**
   * Get the internal stream resource
   *
   * @return resource|null
   */
  public function getResource();
  /**
   * Get the internal cursor position
   *
   * @return int
   */
  public function getOffset(): int;
  /**
   * Get the raw metadata of the stream
   *
   * @param string|null $key Get a specific metadata instead of an array of them
   *
   * @return array|mixed
   */
  public function getMeta( string $key = null );

  /**
   * Write to the stream is allowed
   *
   * @return bool
   */
  public function isWritable(): bool;
  /**
   * Read from the stream is allowed
   *
   * @return bool
   */
  public function isReadable(): bool;
  /**
   * Seek the stream is allowed
   *
   * @return bool
   */
  public function isSeekable(): bool;
  /**
   * Check if the offset is at the end of the stream
   *
   * @return bool
   */
  public function isEnd(): bool;
}
/**
 * Class Stream
 *
 * @property-read resource $resource
 * @property-read int      $offset
 * @property-read array    $meta
 * @property-read bool     $writable
 * @property-read bool     $readable
 * @property-read bool     $seekable
 * @property-read bool     $end
 */
class Stream implements StreamInterface, AccessableInterface {
  use Accessable;

  /**
   * @var resource
   */
  private $_resource;
  /**
   * Close resource on destruct
   *
   * @var bool
   */
  private $close;

  /**
   * Wrap a resource or create a new one
   *
   * Internally created resource will be closed after destruction
   *
   * @param resource|string $uri  Resource or a resource uri for {@see fopen()}
   * @param int             $mode Resource create mode for string uri-s
   */
  public function __construct( $uri, int $mode = 0 ) {

    $this->close = false;
    if( is_resource( $uri ) ) $this->_resource = $uri;
    else {

      $tmp             = $mode & static::MODE_WRITE ? ( $mode & static::MODE_APPEND ? 'a' : 'w' ) . ( $mode & static::MODE_READ ? '+' : '' ) : 'r';
      $this->_resource = fopen( $uri, $tmp );

      if( empty( $this->_resource ) ) throw new \InvalidArgumentException( 'Stream uri must point to a valid resource' );
      else $this->close = true;
    }
  }
  /**
   * Close the internally created resource
   */
  public function __destruct() {
    if( $this->close && is_resource( $this->_resource ) ) {
      fclose( $this->_resource );
    }
  }

  //
  public function __toString() {
    return $this->isReadable() ? $this->read() : "";
  }

  //
  public function write( $content, int $offset = null ) {
    if( !$this->isWritable() ) throw new StreamExceptionInvalid( $this, 'write' );
    else {

      // seek to a position if given
      if( $offset !== null ) {
        $this->seek( $offset );
      }

      // write the content
      if( !( $content instanceof StreamInterface ) ) fwrite( $this->_resource, $content );
      else if( !$content->isReadable() ) throw new StreamExceptionInvalid( $content, 'read' );
      else stream_copy_to_stream( $content->getResource(), $this->_resource );

      return $this;
    }
  }
  //
  public function read( int $length = 0, int $offset = null, StreamInterface $stream = null ) {
    if( !$this->isReadable() ) throw new StreamExceptionInvalid( $this, 'read' );
    else {

      // seek to a position if given
      if( $offset !== null ) {
        $this->seek( $offset );
      }

      // read the content
      if( !$stream ) return stream_get_contents( $this->_resource, $length > 0 ? $length : -1 );
      else if( !( $stream instanceof StreamInterface ) || !$stream->isWritable() ) throw new StreamExceptionInvalid( $stream, 'write' );
      else {

        stream_copy_to_stream( $this->_resource, $stream->getResource(), $length > 0 ? $length : -1 );
        return null;
      }
    }
  }

  //
  public function seek( int $offset = 0 ) {
    if( !$this->isSeekable() ) throw new StreamExceptionInvalid( $this, 'seek' );
    else if( $offset < 0 ) throw new \InvalidArgumentException( 'Offset must be non-negative' );
    else fseek( $this->_resource, $offset );

    return $this;
  }

  //
  public function count() {
    return $this->_resource ? fstat( $this->_resource )[ 'size' ] : 0;
  }

  //
  public function getResource() {
    return $this->_resource;
  }
  //
  public function getOffset(): int {
    return $this->_resource ? ftell( $this->_resource ) : 0;
  }
  //
  public function getMeta( string $key = null ) {

    $tmp = $this->_resource ? stream_get_meta_data( $this->_resource ) : null;
    if( !isset( $tmp ) || !isset( $key ) ) return $tmp;
    else return $tmp[ $key ] ?? null;
  }

  //
  public function isWritable(): bool {
    $tmp = $this->getMeta( 'mode' );
    return $tmp && preg_match( '/(r\+|w\+?|a\+?|x\+?)/i', $tmp );
  }
  //
  public function isReadable(): bool {
    return is_resource( $this->_resource );
  }
  //
  public function isSeekable(): bool {
    return (bool) $this->getMeta( 'seekable' );
  }
  //
  public function isEnd(): bool {
    return $this->_resource ? feof( $this->_resource ) : true;
  }

  /**
   * @param StreamInterface|resource $value
   *
   * @return StreamInterface
   * @throws \InvalidArgumentException
   */
  public static function instance( $value ): StreamInterface {
    return $value instanceof StreamInterface ? $value : new static( $value );
  }
}

/**
 * Not a valid stream for write/read operation
 *
 */
class StreamExceptionInvalid extends Exception\Logic {

  const ID = '0#spoom-core';

  /**
   * @param StreamInterface $stream
   * @param string|mixed    $operation
   */
  public function __construct( StreamInterface $stream, $operation = null ) {

    $data = [ 'meta' => $stream->getMeta(), 'operation' => $operation ];
    parent::__construct( Text::apply( 'Stream is not suitable for {operation}', $data ), static::ID, $data );
  }
}
