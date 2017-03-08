<?php namespace Spoom\Framework\Converter;

use Spoom\Framework\Helper;
use Spoom\Framework;

/**
 * Class Native
 * @package Framework\Converter
 */
class Native implements Framework\ConverterInterface, Helper\AccessableInterface {
  use Helper\Accessable;
  use Helper\Failable;

  const FORMAT = 'pser';
  const NAME   = 'native';

  //
  public function serialize( $content, ?Helper\StreamInterface $stream = null ):?string {
    $this->setException();

    try {

      $result = serialize( $content );
      if( !$stream ) return $result;
      else {

        $stream->write( $result );
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
    if( $content instanceof Helper\StreamInterface ) {
      $content = $content->read();
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
  public function getFormat(): string {
    return static::FORMAT;
  }
  //
  public function getName(): string {
    return static::NAME;
  }
}
