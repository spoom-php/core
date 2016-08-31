<?php namespace Framework\Helper\Converter;

use Framework\Exception;
use Framework\Helper\ConverterInterface;
use Framework\Helper\Enumerable;
use Framework\Helper\Failable;
use Framework\Helper\Library;

/**
 * Class Xml
 * @package Framework\Helper\Converter
 */
class Xml extends Library implements ConverterInterface {
  use Failable;
  
  const FORMAT = 'xml';
  const NAME   = 'xml';

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
  function __clone() {
    $this->_meta = clone $this->_meta;
  }

  /**
   * @param mixed $content Content to serialize
   *
   * @return string
   */
  public function serialize( $content ) {
    $this->setException();
    
    // create dom and the root element
    $dom = new \DOMDocument( $this->_meta->version, $this->_meta->encoding );
    $dom->appendChild( $root = $dom->createElement( $this->_meta->root ) );

    // walk trough the enumerable and build the xml
    $objects = [ (object) [ 'element' => &$root, 'data' => $content, 'name' => $this->_meta->root, 'key' => '' ] ];
    while( $object = array_shift( $objects ) ) {
      /** @var \DOMElement $element */
      $element = $object->element;

      // handle xml "leaf"
      if( !Enumerable::is( $object->data ) ) {

        if( $object->data === null ) $value = 'NULL';
        else if( $object->data === true ) $value = 'TRUE';
        else if( $object->data === false ) $value = 'FALSE';
        else $value = (string) $object->data;

        if( in_array( $object->key, $this->_meta->attributes ) ) $element->setAttribute( $object->name, $value );
        else $element->appendChild( $dom->createTextNode( $value ) );

        // handle objects and arrays
      } else foreach( $object->data as $index => $value ) {

        // handle attributes, arrays and properties (in this order)
        if( in_array( $object->key . '.' . $index, $this->_meta->attributes ) ) {
          $objects[] = (object) [ 'element' => $element, 'data' => $value, 'name' => $index, 'key' => $object->key . '.' . $index ];
        } else if( Enumerable::isArray( $value, false ) ) {
          $objects[] = (object) [ 'element' => $element, 'data' => $value, 'name' => $index, 'key' => $object->key . '.' . $index ];
        } else {
          $child = $dom->createElement( is_numeric( $index ) ? $object->name : $index );

          $element->appendChild( $child );
          $objects[] = (object) [ 'element' => $child, 'data' => $value, 'name' => $child->tagName, 'key' => $object->key . '.' . $index ];
        }
      }
    }

    // create xml from dom
    return simplexml_import_dom( $dom )->asXML();
  }
  /**
   * @param string $content Content to unserialize
   *
   * @return mixed
   */
  public function unserialize( $content ) {
    $this->setException();
    
    $this->_meta->attributes = [];

    // collect encoding and version from xml data
    $dom    = new \DOMDocument();
    $result = [];

    if( !$dom->loadXML( $content ) ) {

      $this->setException( new Exception\Strict( static::EXCEPTION_FAIL_UNSERIALIZE, [
        'instance' => $this,
        'content'  => $content,
        'error'    => libxml_get_last_error()
      ] ) );

    } else {

      $this->_meta->version  = $dom->xmlVersion;
      $this->_meta->encoding = $dom->xmlEncoding;

      // create root element and start the parsing
      $root     = simplexml_load_string( $content );
      $elements = [ [ &$result, $root, '' ] ];
      while( $next = array_shift( $elements ) ) {
        $container = &$next[ 0 ];
        $element   = $next[ 1 ];
        $key       = $next[ 2 ];

        // handle "recursion" end, and set simple data to the container
        if( !is_object( $element ) || !( $element instanceof \SimpleXMLElement ) || ( !$element->children()->count() && !$element->attributes()->count() ) ) {
          switch( (string) $element ) {
            case 'NULL':
              $container = null;
              continue;
            case 'TRUE':
              $container = true;
              continue;
            case 'FALSE':
              $container = false;
              continue;
            default:
              $container = (string) $element;
              continue;
          }
        }

        // handle item attributes
        foreach( $element->attributes() as $index => $value ) {
          $container[ $index ] = [];
          $elements[]          = [ &$container[ $index ], $value, $key . '.' . $index ];

          // save to meta for proper write back
          $this->_meta->attributes[] = $key . '.' . $index;
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
          $container[ $index ] = null;

          if( !is_array( $value ) ) $elements[] = [ &$container[ $index ], $value, $key . '.' . $index ];
          else {

            // handle arrays
            $container[ $index ] = [];
            foreach( $value as $i => $v ) $elements[] = [ &$container[ $index ][ $i ], $v, $key . '.' . $index . '.' . $i ];
          }
        }
      }
    }

    return $result;
  }

  /**
   * @return XmlMeta
   */
  public function getMeta() {
    return clone $this->_meta;
  }
  /**
   * @param XmlMeta $value
   *
   * @return $this
   * @throws Exception\Strict
   */
  public function setMeta( $value ) {
    if( !( $value instanceof XmlMeta ) ) throw new Exception\Strict( static::EXCEPTION_INVALID_META );
    else $this->_meta = $value;

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
/**
 * Class XmlMeta
 * @package Framework\Helper\Converter
 */
class XmlMeta {

  /**
   * @param string $version
   * @param string $encoding
   */
  public function __construct( $version = '1.0', $encoding = 'UTF-8' ) {
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
