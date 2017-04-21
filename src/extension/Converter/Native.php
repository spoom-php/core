<?php namespace Spoom\Core\Converter;

use Spoom\Core\Helper;
use Spoom\Core;

/**
 * Class Native
 */
class Native implements Core\ConverterInterface, Helper\AccessableInterface {
  use Helper\Accessable;
  use Helper\Failable;

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
}
