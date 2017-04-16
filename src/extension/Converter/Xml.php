<?php namespace Spoom\Framework\Converter;

use Spoom\Framework\Helper;
use Spoom\Framework;

/**
 * Class Xml
 * @package Framework\Converter
 *
 * @property XmlMeta $meta
 */
class Xml implements Framework\ConverterInterface, Helper\AccessableInterface {
  use Helper\Accessable;
  use Helper\Failable;

  /**
   * @var XmlMeta
   */
  private $_meta;

  /**
   * @param XmlMeta|string $version
   * @param string         $encoding
   */
  public function __construct( $version = '1.0', $encoding = 'UTF-8' ) {
    $this->_meta = $version instanceof XmlMeta ? $version : ( new XmlMeta( $version, $encoding ) );
  }
  /**
   *
   */
  public function __clone() {
    $this->_meta = clone $this->_meta;
  }

  //
  public function serialize( $content, ?Helper\StreamInterface $stream = null ):?string {
    $this->setException();

    // create dom and the root element
    $dom = new \DOMDocument( $this->_meta->version, $this->_meta->encoding );
    $dom->appendChild( $root = $dom->createElement( $this->_meta->root ) );

    // walk trough the collection and build the xml
    $this->write( $dom, $root, $content, $this->_meta->root, '' );

    // create xml from dom
    $result = simplexml_import_dom( $dom );
    if( !$stream ) return $result->asXML();
    else {

      $stream->write( $result->asXML() );
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

    $this->_meta->attributes = [];

    // collect encoding and version from xml data
    $dom    = new \DOMDocument();
    $result = (object) [];

    if( !$dom->loadXML( $content ) ) $this->setException( new Framework\ConverterExceptionFail( $this, $content, libxml_get_last_error() ) );
    else {

      $this->_meta->version  = $dom->xmlVersion;
      $this->_meta->encoding = $dom->xmlEncoding;
      unset( $dom );

      // create root element and start the parsing
      $root   = simplexml_load_string( $content );
      $result = $this->read( $root, '' );
    }

    return $result;
  }

  /**
   * @param \DOMDocument $dom
   * @param \DOMElement  $element
   * @param mixed        $data
   * @param string       $name
   * @param string       $key
   */
  protected function write( \DOMDocument &$dom, \DOMElement &$element, $data, string $name, string $key ) {

    // handle xml "leaf"
    if( !Helper\Collection::is( $data ) ) {

      if( $data === null ) $value = 'NULL';
      else if( $data === true ) $value = 'TRUE';
      else if( $data === false ) $value = 'FALSE';
      else $value = (string) $data;

      if( in_array( $key, $this->_meta->attributes ) ) $element->setAttribute( $name, $value );
      else $element->appendChild( $dom->createTextNode( $value ) );

      return;
    }

    // handle objects and arrays
    foreach( $data as $index => $value ) {

      // handle attributes, arrays and properties (in this order)
      if( in_array( $key . '.' . $index, $this->_meta->attributes ) ) $this->write( $dom, $element, $value, $index, $key . '.' . $index );
      else if( Helper\Collection::isArrayNumeric( $value, false ) ) $this->write( $dom, $element, $value, $index, $key . '.' . $index );
      else {
        $child = $dom->createElement( is_int( $index ) ? $name : $index );

        $element->appendChild( $child );
        $this->write( $dom, $child, $value, $child->tagName, $key . '.' . $index );
      }
    }
  }
  /**
   * @param mixed  $element
   * @param string $key
   *
   * @return mixed
   */
  protected function read( $element, string $key ) {

    // handle "recursion" end, and set simple data to the container
    if( !is_object( $element ) || !( $element instanceof \SimpleXMLElement ) || ( !$element->children()->count() && !$element->attributes()->count() ) ) {
      switch( (string) $element ) {
        case 'NULL':
          return null;
        case 'TRUE':
          return true;
        case 'FALSE':
          return false;
        default:
          return (string) $element;
      }
    }

    $container = [];

    // handle item attributes
    foreach( $element->attributes() as $index => $value ) {

      // save to meta for proper write back
      $this->_meta->attributes[] = $key . '.' . $index;
      $container[ $index ]       = $this->read( $value, $key . '.' . $index );
    }

    // collect children names and values (it's for find the arrays before add to the queue)
    $tmp = [];
    foreach( $element->children() as $value ) {

      /** @var \SimpleXMLElement $value */
      $index = (string) $value->getName();
      if( !isset( $tmp[ $index ] ) ) $tmp[ $index ] = $value;
      else {

        if( !is_array( $tmp[ $index ] ) ) $tmp[ $index ] = [ $tmp[ $index ] ];
        $tmp[ $index ][] = $value;
      }
    }

    // walk trough all children data and add them to the queue
    foreach( $tmp as $index => $value ) {
      if( !is_array( $value ) ) $container[ $index ] = $this->read( $value, $key . '.' . $index );
      else {

        // handle arrays
        $container[ $index ] = [];
        foreach( $value as $i => $v ) {
          $container[ $index ][ $i ] = $this->read( $v, $key . '.' . $index . '.' . $i );
        }
      }
    }

    return (object) $container;
  }

  /**
   * @return XmlMeta
   */
  public function getMeta() {
    return $this->_meta;
  }
  /**
   * @param XmlMeta $value
   *
   * @return $this
   */
  public function setMeta( $value ) {
    if( !( $value instanceof XmlMeta ) ) throw new \InvalidArgumentException( 'Meta must be a subclass of ' . XmlMeta::class, $value );
    else $this->_meta = $value;

    return $this;
  }
}
/**
 * Class XmlMeta
 * @package Framework\Converter
 */
class XmlMeta {

  /**
   * @param string $version
   * @param string $encoding
   */
  public function __construct( string $version = '1.0', string $encoding = 'UTF-8' ) {
    $this->version  = $version;
    $this->encoding = $encoding;
  }

  /**
   * Automatically filled on unserialize, and used to decide the every property's type for serialize
   *
   * @var array
   */
  public $attributes = [];
  /**
   * The root element name for serialize
   *
   * @var string
   */
  public $root = 'root';
  /**
   * @var string
   */
  public $version = '1.0';
  /**
   * @var string
   */
  public $encoding = 'UTF-8';
}
