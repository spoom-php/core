<?php namespace Spoom\Core\Converter;

use Spoom\Core\Helper;
use Spoom\Core;

/**
 * Class Native
 */
class Native implements Core\ConverterInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  //
  public function serialize( $content, ?Helper\StreamInterface $stream = null ):?string {

      $result = serialize( $content );
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

      return unserialize( $content );
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
