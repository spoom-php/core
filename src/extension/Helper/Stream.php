<?php namespace Spoom\Core\Helper;

use Spoom\Core\Exception;

/**
 * Interface StreamInterface
 */
interface StreamInterface {

  /**
   * Creates a readable stream
   */
  const MODE_READ = 2;
  /**
   * Creates a writeable stream (without truncate!) and place the pointer at the beggining of the stream
   */
  const MODE_WRITE = 4;
  /**
   * Change the write behavior and place the pointer to the end of the stream
   */
  const MODE_APPEND = 8;
  /**
   * Change the write behavior and truncate the stream to zero length
   */
  const MODE_TRUNCATE = 16;

  /**
   * Creates a read-writeable stream (without truncate!) and place the pointer at the beggining of the stream
   */
  const MODE_RW = self::MODE_READ | self::MODE_WRITE;
  /**
   * Creates a read-writeable stream, truncate it to zero length and place the pointer at the beggining of the stream
   */
  const MODE_RWT = self::MODE_READ | self::MODE_WRITE | self::MODE_TRUNCATE;
  /**
   * Creates a writeable stream and place the pointer at the end of the stream
   */
  const MODE_WA = self::MODE_WRITE | self::MODE_APPEND;
  /**
   * Creates a writeable empty stream
   */
  const MODE_WT = self::MODE_WRITE | self::MODE_TRUNCATE;
  /**
   * Creates a read-writeable (append) stream (without truncate!) and place the pointer at the end of the stream
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
   * @throws StreamInvalidException Invalid input or instance stream
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
   * @throws StreamInvalidException Invalid input or instance stream
   */
  public function read( int $length = 0, ?int $offset = null, StreamInterface $stream = null );
  /**
   * Truncate the stream to length
   *
   * @see ftruncate()
   *
   * @param int|null $length NULL is the current offset
   *
   * @return static
   */
  public function truncate( ?int $length = null );

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
   * Get the stream size in bytes
   *
   * @param bool $unreaded Get only the unreaded bytes
   *
   * @return int|null null if the size is unknown
   */
  public function getSize( bool $unreaded = false ): ?int;
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
 * @property-read int|null $size
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
   * @param resource|string $uri   Resource or a resource uri for {@see fopen()}
   * @param int             $mode  Resource create mode for string uri-s
   * @param bool            $close Close the resource after destruction (only works with resource type $uri)
   *
   * @throws \InvalidArgumentException
   */
  public function __construct( $uri, int $mode = 0, bool $close = false ) {

    $this->close = $close;
    if( is_resource( $uri ) ) $this->_resource = $uri;
    else {

      // define stream open flags
      if( !( $mode & static::MODE_WRITE ) ) $tmp = 'r';
      else {

        $tmp = $mode & static::MODE_TRUNCATE ? 'w' : ( $mode & static::MODE_APPEND ? 'a' : 'c' );
        if( $mode & static::MODE_READ ) $tmp .= '+';
      }

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
    if( !$this->isWritable() ) throw new StreamInvalidException( $this, 'write' );
    else {

      // seek to a position if given
      if( $offset !== null ) {
        $this->seek( $offset );
      }

      // write the content
      if( !( $content instanceof StreamInterface ) ) fwrite( $this->_resource, $content );
      else if( !$content->isReadable() ) throw new StreamInvalidException( $content, 'read' );
      else stream_copy_to_stream( $content->getResource(), $this->_resource );

      return $this;
    }
  }
  //
  public function read( int $length = 0, ?int $offset = null, StreamInterface $stream = null ) {
    if( !$this->isReadable() ) throw new StreamInvalidException( $this, 'read' );
    else {

      // seek to a position if given
      if( $offset !== null ) {
        $this->seek( $offset );
      }

      // read the content
      if( !$stream ) return stream_get_contents( $this->_resource, $length > 0 ? $length : -1 );
      else if( !( $stream instanceof StreamInterface ) || !$stream->isWritable() ) throw new StreamInvalidException( $stream, 'write' );
      else {

        stream_copy_to_stream( $this->_resource, $stream->getResource(), $length > 0 ? $length : -1 );
        return null;
      }
    }
  }
  //
  public function truncate( ?int $length = null, bool $seek = true ) {
    if( !$this->isWritable() ) throw new StreamInvalidException( $this, 'write' );
    else {

      $length = $length === null ? $this->getOffset() : $length;
      ftruncate( $this->_resource, $length );
      if( $seek ) $this->seek( $length );

      return $this;
    }
  }
  //
  public function seek( int $offset = 0 ) {
    if( !$this->isSeekable() ) throw new StreamInvalidException( $this, 'seek' );
    else if( $offset < 0 ) throw new \InvalidArgumentException( 'Offset must be non-negative' );
    else fseek( $this->_resource, $offset );

    return $this;
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
  public function getSize( bool $unreaded = false ): ?int {
    if( !is_resource( $this->_resource ) ) return null;
    else {

      // check simple stat first
      // TODO find better ways to determine the stream length
      $tmp = fstat( $this->_resource );
      if( isset( $tmp[ 'size' ] ) ) return $tmp[ 'size' ] - ( $unreaded ? $this->getOffset() : 0 );
      else if( !$this->isSeekable() ) return null;
      else {

        // backup current position in the string (seek to the begining if needed)
        $offset = $this->getOffset();
        if( !$unreaded ) fseek( $this->_resource, 0 );

        // read every bytes
        $size = 0;
        while( fgetc( $this->_resource ) !== false ) ++$size;

        // reset the pointer
        fseek( $this->_resource, $offset );
        return $size;
      }
    }
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
    return $tmp && preg_match( '/(r\+|w\+?|a\+?|x\+?|c\+?)/i', $tmp );
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
class StreamInvalidException extends Exception\Logic {

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
