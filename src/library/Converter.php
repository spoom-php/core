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

    if( ($event = new ConverterEventIO( __FUNCTION__, $this, $content, $stream ))->isPrevented() ) return $event->result;
    else {

      // modify variables from the event
      $content = $event->content;
      $stream = $event->stream;

      $result = serialize( $content );
      if( !$stream ) return $result;
      else {

        $stream->write( $result );
        return null;
      }
    }
  }
  //
  public function unserialize( $content ) {

    if( ($event = new ConverterEventIO( __FUNCTION__, $this, $content ))->isPrevented() ) return $event->result;
    else {

      // modify variables from the event
      $content = $event->content;

      // handle stream input
      if( $content instanceof Helper\StreamInterface ) {
        $content = $content->read();
      }

      return unserialize( (string) $content );
    }
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
 * Triggered before every Converter IO operation (serialize, unserialize), implemented in the child classes
 *
 * Prevention can skip the original IO operation. The `content` and `stream` can be modified by the callbacks
 */
class ConverterEventIO extends Event {

  const FUNCTION_SERIALIZE = 'serialize';
  const FUNCTION_UNSERIALIZE = 'unserialize';

  /**
   * @var ConverterInterface
   */
  public $instance;
  /**
   * @var string
   */
  public $function;
  /**
   * @var mixed
   */
  public $content;
  /**
   * @var ?Helper\StreamInterface
   */
  public $stream;

  /**
   * @var mixed
   */
  public $result;

  /**
   *
   */
  public function __construct( string $function, ConverterInterface $instance, $content, ?Helper\StreamInterface $stream = null ) {
    $this->function = $function;
    $this->instance = $instance;
    $this->content = $content;
    $this->stream = $stream;

    $this->trigger();
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
