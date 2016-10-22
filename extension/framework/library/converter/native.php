<?php namespace Framework\Converter;

use Framework\Exception;
use Framework\Helper;
use Framework;

/**
 * Class Native
 * @package Framework\Converter
 *
 * @property-read string $format Used format name
 * @property-read string $name   The converter name
 */
class Native implements Framework\ConverterInterface, Helper\AccessableInterface {
  use Helper\Accessable;
  use Helper\Failable;

  const FORMAT = 'pser';
  const NAME   = 'native';

  /**
   * @inheritdoc
   *
   * @param mixed    $content The content to serialize
   * @param resource $stream  Optional output stream
   *
   * @return string|null
   */
  public function serialize( $content, $stream = null ) {
    $this->setException();

    try {

      $result = serialize( $content );
      if( !$stream ) return $result;
      else {

        fwrite( $stream, $result );
        return null;
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    return null;
  }
  /**
   * @inheritdoc
   *
   * @param string|resource $content The content (can be a stream) to unserialize
   *
   * @return mixed
   */
  public function unserialize( $content ) {
    $this->setException();

    // handle stream input
    if( is_resource( $content ) ) {
      $content = stream_get_contents( $content );
    }

    try {
      return unserialize( $content );
    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    return null;
  }

  /**
   * There is no support for meta in this converter
   *
   * @return null
   */
  public function getMeta() {
    return null;
  }
  /**
   * There is no support for meta in this converter
   *
   * @param null $value
   *
   * @return $this
   * @throws Exception\Strict
   */
  public function setMeta( $value ) {
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
