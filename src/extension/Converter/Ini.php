<?php namespace Spoom\Core\Converter;

use Spoom\Core;
use Spoom\Core\Helper;
use Spoom\Core\Helper\Collection;
use Spoom\Core\Helper\Number;
use Spoom\Core\ConverterInterface;

/**
 * Class Ini
 */
class Ini implements ConverterInterface, Helper\AccessableInterface {
  use Helper\Accessable;
  use Helper\Failable;

  //
  public function serialize( $content, ?Helper\StreamInterface $stream = null ):?string {
    $this->setException();

    $result = [];
    if( !Collection::is( $content, true ) ) $this->setException( new Core\ConverterExceptionFail( $this, $content ) );
    else {

      $this->flatten( $result, Collection::read( $content, [], true ) );
      foreach( $result as $key => $value ) {

        $print    = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : Helper\Text::read( $value );
        $quote    = Number::is( $value ) || is_bool( $value ) ? '' : ( !mb_strpos( $value, '"' ) ? '"' : "'" );
        $result[] = $key . '=' . $quote . $print . $quote;
      }
    }

    $result = implode( "\n", $result );
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

    $result = (object) [];
    $ini    = parse_ini_string( $content, false );
    if( !is_array( $ini ) ) $this->setException( new Core\ConverterExceptionFail( $this, $content, error_get_last() ) );
    else foreach( $ini as $key => $value ) {

      $keys = explode( '.', $key );
      $tmp  = &$result;

      while( $key = array_shift( $keys ) ) {
        if( empty( $keys ) ) break;
        else {

          if( !isset( $tmp->{$key} ) ) $tmp->{$key} = (object) [];
          $tmp = &$tmp->{$key};
        }
      }

      $tmp->{$key} = $value;
    }

    return $result;
  }

  /**
   * Convert multi dimension array/object into one dimension with key merging (recursive)
   *
   * TODO extract the dot separator into meta option
   *
   * @param array        $input
   * @param object|array $collection
   * @param string       $root
   */
  protected function flatten( array &$input, $collection, string $root = '' ) {
    foreach( $collection as $key => $value ) {

      $key = $root . ( empty( $root ) ? '' : '.' ) . $key;
      if( !Collection::is( $value, true ) ) $input[ $key ] = $value;
      else $this->flatten( $input, $value, $key );
    }
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
