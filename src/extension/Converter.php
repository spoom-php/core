<?php namespace Spoom\Core;

use Spoom\Core\Helper;

/**
 * Interface ConverterInterface
 */
interface ConverterInterface {

  /**
   * Serialize the content to a formatted (based on the meta property) string
   *
   * @param mixed                       $content The content to serialize
   * @param Helper\StreamInterface|null $stream  Optional output stream
   *
   * @return string|null
   * @throws ConverterFailException Failed serialization
   */
  public function serialize( $content, ?Helper\StreamInterface $stream = null ): ?string;
  /**
   * Unserialize string into a php value
   *
   * @param string|Helper\StreamInterface $content The content (can be a stream) to unserialize
   *
   * @return mixed
   * @throws ConverterFailException Failed unserialization
   */
  public function unserialize( $content );

  /**
   * @return mixed
   */
  public function getMeta();
  /**
   * @param mixed $value
   *
   * @return $this
   * @throws \InvalidArgumentException Try to set a wrong Meta subclass
   */
  public function setMeta( $value );
}

//
class Converter implements ConverterInterface, Helper\AccessableInterface {
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

    return unserialize( (string) $content );
  }

  //
  public function getMeta() {
    return null;
  }
  //
  public function setMeta( $_ ) {
    return $this;
  }
}

/**
 * Failed (de-)serialization
 */
class ConverterFailException extends Exception\Runtime {

  const ID = '29#spoom-core';

  /**
   * @param ConverterInterface $instance
   * @param mixed              $content The content to (un)serialize
   * @param mixed              $error
   */
  public function __construct( ConverterInterface $instance, $content, $error = null ) {

    $data = [ 'instance' => $instance, 'content' => $content, 'error' => $error ];
    parent::__construct( '(Un)serialization failed, due to an error', static::ID, $data );
  }
}
