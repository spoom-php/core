<?php namespace Spoom\Framework\Converter;

use Spoom\Framework\Helper;
use Spoom\Framework;

/**
 * Class Json
 * @package Framework\Converter
 *
 * @property JsonMeta    $meta
 * @property-read string $format Used format name
 * @property-read string $name   The converter name
 */
class Json implements Framework\ConverterInterface, Helper\AccessableInterface {
  use Helper\Accessable;
  use Helper\Failable;

  const FORMAT = 'json';
  const NAME   = 'json';

  /**
   * @var JsonMeta
   */
  private $_meta;

  /**
   * @param JsonMeta|int $options
   * @param int          $depth
   * @param bool         $associative
   */
  public function __construct( $options = 0, $depth = 512, $associative = false ) {
    $this->_meta = $options instanceof JsonMeta ? $options : new JsonMeta( $options | JSON_PARTIAL_OUTPUT_ON_ERROR, $depth, $associative );
  }
  /**
   *
   */
  public function __clone() {
    $this->_meta = clone $this->_meta;
  }

  //
  public function serialize( $content, $stream = null ) {
    $this->setException();

    $result = null;
    try {

      $result = json_encode( $content, $this->_meta->options );
      if( json_last_error() != JSON_ERROR_NONE ) {

        $result = null;
        throw new Framework\ConverterExceptionFail( $this, $content, [ json_last_error(), json_last_error_msg() ] );
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    if( !$stream ) return $result;
    else {

      fwrite( $stream, $result );
      return null;
    }
  }
  //
  public function unserialize( $content ) {
    $this->setException();

    // handle stream input
    if( is_resource( $content ) ) {
      $content = stream_get_contents( $content );
    }

    $result = null;
    try {

      $result = json_decode( $content, $this->_meta->associative, $this->_meta->depth, $this->_meta->options );
      if( json_last_error() != JSON_ERROR_NONE ) {
        $result = null;
        throw new Framework\ConverterExceptionFail( $this, $content, [ json_last_error(), json_last_error_msg() ] );
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    return $result;
  }

  //
  public function getMeta() {
    return clone $this->_meta;
  }
  //
  public function setMeta( $value ) {
    if( !( $value instanceof JsonMeta ) ) throw new \InvalidArgumentException( 'Meta must be a subclass of ' . JsonMeta::class, $value );
    else $this->_meta = $value;

    return $this;
  }

  //
  public function getFormat() {
    return static::FORMAT;
  }
  //
  public function getName() {
    return static::NAME;
  }
}
/**
 * Class JsonMeta
 * @package Framework\Converter
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
  public function __construct( $options = 0, $depth = 512, $associative = false ) {
    $this->options     = $options;
    $this->depth       = $depth;
    $this->associative = $associative;
  }
}
