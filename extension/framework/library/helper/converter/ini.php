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
   * @param mixed $content Content to serialize
   *
   * @return string
   */
  public function serialize( $content ) {
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

    return implode( "\n", $result );
  }
  /**
   * @param string $content Content to unserialize
   *
   * @return mixed
   */
  public function unserialize( $content ) {
    $this->setException();

    $result = [];
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

          if( !isset( $tmp[ $key ] ) ) $tmp[ $key ] = [];
          $tmp = &$tmp[ $key ];
        }
      }

      $tmp[ $key ] = $value;
    }

    return (object) $result;
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
