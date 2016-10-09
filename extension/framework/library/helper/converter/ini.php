<?php namespace Framework\Helper\Converter;

use Framework\Exception;
use Framework\Helper\ConverterInterface;
use Framework\Helper\Enumerable;
use Framework\Helper\Failable;
use Framework\Helper\Library;

/**
 * Class Ini
 * @package Framework\Helper\Converter
 */
class Ini extends Library implements ConverterInterface {
  use Failable;

  const FORMAT = 'ini';
  const NAME   = 'ini';

  /**
   * @inheritDoc
   *
   * @param mixed    $content The content to serialize
   * @param resource $stream  Optional output stream
   *
   * @return string|null
   */
  public function serialize( $content, $stream = null ) {
    $this->setException();

    $result = [];
    if( !Enumerable::is( $content ) ) {

      $this->setException( new Exception\Strict( static::EXCEPTION_FAIL_SERIALIZE, [
        'instance' => $this,
        'content'  => $content
      ] ) );

    } else {

      $iterator = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( Enumerable::cast( $content ) ) );
      foreach( $iterator as $value ) {

        $keys = [];
        foreach( range( 0, $iterator->getDepth() ) as $depth ) {
          $keys[] = $iterator->getSubIterator( $depth )->key();
        }

        $print    = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value;
        $quote    = is_numeric( $value ) || is_bool( $value ) ? '' : ( !mb_strpos( $value, '"' ) ? '"' : "'" );
        $result[] = join( '.', $keys ) . "={$quote}{$print}{$quote}";
      }
    }

    $result = implode( "\n", $result );
    if( !$stream ) return $result;
    else {

      fwrite( $stream, $result );
      return null;
    }
  }
  /**
   * @inheritDoc
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

    $result = (object) [];
    $ini    = parse_ini_string( $content, false );
    if( !is_array( $ini ) ) {

      $this->setException( new Exception\Strict( static::EXCEPTION_FAIL_UNSERIALIZE, [
        'instance' => $this,
        'content'  => $content,
        'error'    => error_get_last()
      ] ) );

    } else foreach( $ini as $key => $value ) {

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
   * @return null
   */
  public function getMeta() {
    return null;
  }
  /**
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
