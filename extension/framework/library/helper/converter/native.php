<?php namespace Framework\Helper\Converter;

use Framework\Exception;
use Framework\Helper\ConverterInterface;
use Framework\Helper\Library;

/**
 * Class Native
 * @package Framework\Helper\Converter
 */
class Native extends Library implements ConverterInterface {

  const FORMAT = 'pser';
  const NAME   = 'native';

  /**
   * @param mixed $content Content to serialize
   *
   * @return string
   */
  public function serialize( $content ) {
    return serialize( $content );
  }
  /**
   * @param string $content Content to unseraialize
   *
   * @return mixed
   */
  public function unserialize( $content ) {
    return unserialize( $content );
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
