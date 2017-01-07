<?php namespace Framework\Helper;

use Framework\Exception;

/**
 * Interface StreamInterface
 * @package Framework\Helper
 */
interface StreamInterface extends \Countable {

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
   * @throws Exception\Strict
   */
  public function write( $content, $offset = null );
  /**
   * Read from the stream
   *
   * @param int                  $length The maximum byte to read
   * @param int|null             $offset Offset in the stream to read from. Default (===null) is the current cursor
   * @param StreamInterface|null $stream Output stream if specified
   *
   * @return string
   * @throws Exception\Strict
   */
  public function read( $length = 0, $offset = null, $stream = null );

  /**
   * Move the internal cursor within the stream
   *
   * @param int $offset The new cursor position
   *
   * @return static
   * @throws Exception\Strict
   */
  public function seek( $offset = 0 );

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
  public function getOffset();
  /**
   * Get the raw metadata of the stream
   *
   * @param string|null $key Get a specific metadata instead of an array of them
   *
   * @return array|mixed
   */
  public function getMeta( $key = null );

  /**
   * Write to the stream is allowed
   *
   * @return bool
   */
  public function isWritable();
  /**
   * Read from the stream is allowed
   *
   * @return bool
   */
  public function isReadable();
  /**
   * Seek the stream is allowed
   *
   * @return bool
   */
  public function isSeekable();
}
/**
 * Class Stream
 * @package Framework\Helper
 */
class Stream implements StreamInterface, AccessableInterface {
  use Accessable;

  const EXCEPTION_INVALID_OPERATION = 'framework#0E';
  const EXCEPTION_INVALID_OFFSET    = 'framework#0E';
  const EXCEPTION_INVALID_RESOURCE  = 'framework#0E';
  const EXCEPTION_INVALID_STREAM    = 'framework#0E';

  /**
   * @var resource
   */
  private $_resource;

  /**
   * @param resource $resource
   *
   * @throws Exception\Strict
   */
  public function __construct( $resource ) {
    if( !is_resource( $resource ) ) throw new Exception\Strict( static::EXCEPTION_INVALID_RESOURCE );
    else {

      $this->_resource = $resource;
    }
  }

  /**
   * @inheritDoc
   */
  function __toString() {
    return $this->isReadable() ? $this->read() : "";
  }

  /**
   * Write to the stream
   *
   * @param string|StreamInterface $content The content to write
   * @param int|null               $offset  Offset in the stream where to write. Default (===null) is the current cursor
   *
   * @return static
   * @throws Exception\Strict
   */
  public function write( $content, $offset = null ) {
    if( !$this->isWritable() ) throw new Exception\Strict( static::EXCEPTION_INVALID_OPERATION, [ 'meta' => $this->getMeta() ] );
    else {

      // seek to a position if given
      if( $offset !== null ) {
        $this->seek( $offset );
      }

      // write the content
      if( !( $content instanceof StreamInterface ) ) fwrite( $this->_resource, $content );
      else if( !$content->isReadable() ) throw new Exception\Strict( static::EXCEPTION_INVALID_STREAM, [ 'value' => $content ] );
      else stream_copy_to_stream( $content->getResource(), $this->_resource );

      return $this;
    }
  }
  /**
   * Read from the stream
   *
   * @param int                  $length The maximum byte to read
   * @param int|null             $offset Offset in the stream to read from. Default (===null) is the current cursor
   * @param StreamInterface|null $stream Output stream if specified
   *
   * @return string
   * @throws Exception\Strict
   */
  public function read( $length = 0, $offset = null, $stream = null ) {
    if( !$this->isReadable() ) throw new Exception\Strict( static::EXCEPTION_INVALID_OPERATION, [ 'meta' => $this->getMeta() ] );
    else {

      // seek to a position if given
      if( $offset !== null ) {
        $this->seek( $offset );
      }

      // read the content
      if( !$stream ) return stream_get_contents( $this->_resource, $length > 0 ? $length : -1 );
      else if( !( $stream instanceof StreamInterface ) || !$stream->isWritable() ) throw new Exception\Strict( static::EXCEPTION_INVALID_STREAM, [ 'value' => $stream ] );
      else {

        stream_copy_to_stream( $this->_resource, $stream->getResource(), $length > 0 ? $length : -1 );
        return null;
      }
    }
  }

  /**
   * Move the internal cursor within the stream
   *
   * @param int $offset The new cursor position
   *
   * @return static
   * @throws Exception\Strict
   */
  public function seek( $offset = 0 ) {
    if( !$this->isSeekable() ) throw new Exception\Strict( static::EXCEPTION_INVALID_OPERATION, [ 'meta' => $this->getMeta() ] );
    else if( $offset < 0 ) throw new Exception\Strict( static::EXCEPTION_INVALID_OFFSET );
    else fseek( $this->_resource, $offset );

    return $this;
  }

  /**
   * Count elements of an object
   * @link  http://php.net/manual/en/countable.count.php
   * @return int The custom count as an integer.
   * </p>
   * <p>
   * The return value is cast to an integer.
   * @since 5.1.0
   */
  public function count() {
    return $this->_resource ? fstat( $this->_resource )[ 'size' ] : 0;
  }

  /**
   * Get the internal stream resource
   *
   * @return resource|null
   */
  public function getResource() {
    return $this->_resource;
  }
  /**
   * Get the internal cursor position
   *
   * @return int
   */
  public function getOffset() {
    return $this->_resource ? ftell( $this->_resource ) : 0;
  }
  /**
   * Get the raw metadata of the stream
   *
   * @param string|null $key Get a specific metadata instead of an array of them
   *
   * @return array|mixed
   */
  public function getMeta( $key = null ) {

    $tmp = $this->_resource ? stream_get_meta_data( $this->_resource ) : null;
    if( empty( $tmp ) || empty( $key ) ) return $tmp;
    else return !empty( $tmp[ $key ] ) ? $tmp[ $key ] : null;
  }

  /**
   * Write to the stream is allowed
   *
   * @return bool
   */
  public function isWritable() {
    $tmp = $this->getMeta( 'mode' );
    return $tmp && preg_match( '/(r\+|w\+?|a\+?|x\+?)/i', $tmp );
  }
  /**
   * Read from the stream is allowed
   *
   * @return bool
   */
  public function isReadable() {
    return true;
  }
  /**
   * Seek the stream is allowed
   *
   * @return bool
   */
  public function isSeekable() {
    return (bool) $this->getMeta( 'seekable' );
  }

  /**
   * @param StreamInterface|resource $value
   *
   * @return StreamInterface
   * @throws Exception\Strict
   */
  public static function instance( $value ) {
    return $value instanceof StreamInterface ? $value : new static( $value );
  }
}
