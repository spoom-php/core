<?php namespace Framework\Converter;

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

  //
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
  //
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

  //
  public function getMeta() {
    return null;
  }
  //
  public function setMeta( $value ) {
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
