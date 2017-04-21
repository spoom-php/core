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
  use Helper\Failable;

  /**
   * @var JsonMeta
   */
  private $_meta;

  /**
   * @param JsonMeta|int $options
   * @param int          $depth
   * @param bool         $associative
   */
  public function __construct( $options = JSON_PARTIAL_OUTPUT_ON_ERROR, int $depth = 512, bool $associative = false ) {
    $this->_meta = $options instanceof JsonMeta ? $options : new JsonMeta( $options, $depth, $associative );
  }
  /**
   *
   */
  public function __clone() {
    $this->_meta = clone $this->_meta;
  }

  //
  public function serialize( $content, ?Helper\StreamInterface $stream = null ):?string {
    $this->setException();

    $result = null;
    try {

      $result = json_encode( $content, $this->_meta->options );
      if( json_last_error() != JSON_ERROR_NONE ) {

        $result = null;
        throw new Core\ConverterExceptionFail( $this, $content, [ json_last_error(), json_last_error_msg() ] );
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    if( !$stream ) return $result;
    else {

      $stream->write( $result );
      return null;
    }
  }
  //
  public function unserialize( $content ) {
    $this->setException();

    // handle stream input
    if( $content instanceof Helper\StreamInterface ) {
      $content = $content->read();
    }

    $result = null;
    try {

      $result = json_decode( $content, $this->_meta->associative, $this->_meta->depth, $this->_meta->options );
      if( json_last_error() != JSON_ERROR_NONE ) {
        $result = null;
        throw new Core\ConverterExceptionFail( $this, $content, [ json_last_error(), json_last_error_msg() ] );
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
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
   */
  public function setMeta( $value ) {
    if( !( $value instanceof JsonMeta ) ) throw new \InvalidArgumentException( 'Meta must be a subclass of ' . JsonMeta::class, $value );
    else $this->_meta = $value;

    return $this;
  }
}
/**
 * Class JsonMeta
 */
class JsonMeta {

  /**
   * Decode json's objects into associative array
   *
   * TODO maybe this should add JSON_FORCE_OBJECT option for encode
   *
   * @var bool
   */
  public $associative = false;
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
  public $options = JSON_PARTIAL_OUTPUT_ON_ERROR;

  /**
   * @param int  $options
   * @param int  $depth
   * @param bool $associative
   */
  public function __construct( int $options = JSON_PARTIAL_OUTPUT_ON_ERROR, int $depth = 512, bool $associative = false ) {
    $this->options     = $options;
    $this->depth       = $depth;
    $this->associative = $associative;
  }
}
