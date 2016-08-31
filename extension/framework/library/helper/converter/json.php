<?php namespace Framework\Helper\Converter;

use Framework\Exception;
use Framework\Helper\ConverterInterface;
use Framework\Helper\Failable;
use Framework\Helper\Library;

/**
 * Class Json
 * @package Framework\Helper\Converter
 */
class Json extends Library implements ConverterInterface {
  use Failable;

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
    $this->_meta = $options instanceof JsonMeta ? $options : new JsonMeta( $options, $depth, $associative );
  }
  /**
   *
   */
  function __clone() {
    $this->_meta = clone $this->_meta;
  }

  /**
   * @param mixed $content Content to serialize
   *
   * @return string
   */
  public function serialize( $content ) {
    $this->setException();

    // FIXME clear resource types from the json (use JSON_PARTIAL_OUTPUT_ON_ERROR after PHP5.5)

    $result = null;
    try {

      $result = json_encode( $content, $this->_meta->options );
      if( json_last_error() != JSON_ERROR_NONE ) {
        $result = null;
        throw new Exception\Strict( static::EXCEPTION_FAIL_SERIALIZE, [
          'instance' => $this,
          'content'  => $content,
          'error'    => [ json_last_error(), json_last_error_msg() ]
        ] );
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    return $result;
  }
  /**
   * @param string $content Content to unserialize
   *
   * @return mixed
   */
  public function unserialize( $content ) {
    $this->setException();

    $result = null;
    try {

      $result = json_decode( $content, $this->_meta->associative, $this->_meta->depth, $this->_meta->options );
      if( json_last_error() != JSON_ERROR_NONE ) {
        $result = null;
        throw new Exception\Strict( static::EXCEPTION_FAIL_UNSERIALIZE, [
          'instance' => $this,
          'content'  => $content,
          'error'    => [ json_last_error(), json_last_error_msg() ]
        ] );
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
    return clone $this->_meta;
  }
  /**
   * @param JsonMeta $value
   *
   * @return $this
   * @throws Exception\Strict
   */
  public function setMeta( $value ) {
    if( !( $value instanceof JsonMeta ) ) throw new Exception\Strict( static::EXCEPTION_INVALID_META );
    else $this->_meta = $value;

    return $this;
  }

  /**
   * @return string The name of the format that the converter use
   */
  public function getFormat() {
    return static::FORMAT;
  }
  /**
   * @return string The unique name of the converter type
   */
  public function getName() {
    return static::NAME;
  }
}
/**
 * Class JsonMeta
 * @package Framework\Helper\Converter
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
  public $options = 0;

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
