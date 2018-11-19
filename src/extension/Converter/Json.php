<?php namespace Spoom\Core\Converter;

use Spoom\Core\Helper;
use Spoom\Core;

/**
 * Class Json
 *
 * @property JsonMeta $meta
 */
class Json implements Core\ConverterInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * @var JsonMeta
   */
  private $_meta;

  /**
   * @param JsonMeta|int $options
   * @param int          $depth
   * @param bool         $associative
   */
  public function __construct( $options = JsonMeta::DEFAULT, int $depth = 512, bool $associative = true ) {
    $this->_meta = $options instanceof JsonMeta ? $options : new JsonMeta( $options, $depth, $associative );
  }
  /**
   *
   */
  public function __clone() {
    $this->_meta = clone $this->_meta;
  }

  //
  public function serialize( $content, ?Helper\StreamInterface $stream = null ): ?string {

    $result = json_encode( $content, $this->_meta->options );
    if( json_last_error() != JSON_ERROR_NONE ) {
      throw new Core\ConverterFailException( $this, $content, [ json_last_error(), json_last_error_msg() ] );
    }

    if( !$stream ) return $result;
    else {

      $stream->write( $result );
      return null;
    }
  }
  //
  public function unserialize( $content ) {

    // handle stream input
    if( $content instanceof Helper\StreamInterface ) {
      $content = $content->read();
    }

    $result = $content !== null && strlen( $content ) > 0 ? json_decode( $content, $this->_meta->associative, $this->_meta->depth, $this->_meta->options ) : null;
    if( json_last_error() != JSON_ERROR_NONE ) {
      throw new Core\ConverterFailException( $this, $content, [ json_last_error(), json_last_error_msg() ] );
    }

    return $result;
  }

  /**
   * @return JsonMeta
   */
  public function getMeta() {
    return $this->_meta;
  }
  /**
   * @param JsonMeta $value
   *
   * @return $this
   * @throws \TypeError Wrong type of meta
   */
  public function setMeta( $value ) {
    if( !( $value instanceof JsonMeta ) ) throw new \TypeError( 'Meta must be a subclass of ' . JsonMeta::class, $value );
    else $this->_meta = $value;

    return $this;
  }
}
/**
 * Class JsonMeta
 */
class JsonMeta {

  /**
   * Default options for the meta
   */
  const DEFAULT = JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

  /**
   * Decode json's objects into associative array
   *
   * TODO maybe this should add JSON_FORCE_OBJECT option for encode
   *
   * @var bool
   */
  public $associative = true;
  /**
   * Maximum depth for decoding
   *
   * TODO implement depth for encoding
   *
   * @var int
   */
  public $depth = 512;
  /**
   * JSON encode/decode options
   *
   * @var int
   */
  public $options;

  /**
   * @param int  $options
   * @param int  $depth
   * @param bool $associative
   */
  public function __construct( int $options = self::DEFAULT, int $depth = 512, bool $associative = true ) {
    $this->options     = $options;
    $this->depth       = $depth;
    $this->associative = $associative;
  }
}
