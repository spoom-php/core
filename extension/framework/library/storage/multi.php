<?php namespace Framework\Storage;

/**
 * Class Multi
 * @package Framework\Storage
 *
 * @property string $namespace The default namespace
 */
class Multi extends Single {

  /**
   * The default namespace
   */
  const NAMESPACE_DEFAULT = 'default';

  /**
   * Separator char for the namespace in the indexes
   */
  const SEPARATOR_NAMESPACE = ':';

  /**
   * Default namespace
   * @var string
   */
  private $_namespace;

  /**
   * Set default or given values
   *
   * @param string            $namespace
   * @param array|object|null $data
   * @param int               $caching
   */
  public function __construct( $namespace = self::NAMESPACE_DEFAULT, $data = null, $caching = self::CACHE_SIMPLE ) {
    parent::__construct( $data, $caching );

    $this->_namespace = $namespace;
  }

  /**
   * @param string $index
   *
   * @return mixed
   */
  public function __get( $index ) {
    $iindex = '_' . $index;

    if( property_exists( $this, $iindex ) ) return $this->{$iindex};
    else return parent::__get( $index );
  }
  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index ) || parent::__isset( $index );
  }
  /**
   * Dynamic setter for privates
   *
   * @param string $index
   * @param mixed  $value
   */
  public function __set( $index, $value ) {
    switch( $index ) {
      case 'namespace':

        if( !empty( $value ) ) {
          $this->_namespace = (string) $value;
        }

        break;
      default:
        parent::__set( $index, $value );
    }
  }

  /**
   * Convert one or more namespaces to one object or array.
   * In fact, it is a getter for the namespaces.
   *
   * @param mixed $namespaces - The name ( or array of names ) of the namespace or false, if want all namespace
   * @param bool  $object     - Return object instead of array
   *
   * @return mixed
   */
  public function convert( $namespaces = false, $object = false ) {
    if( $namespaces === false ) return $object ? (object) $this->source : $this->source;

    $namespaces = is_array( $namespaces ) ? $namespaces : [ $namespaces ];
    $result     = [ ];
    foreach( $namespaces as $n ) $result[ $n ] = $this->exist( $n . self::SEPARATOR_NAMESPACE ) ? $this->source[ $n ] : [ ];

    return $object ? (object) $result : $result;
  }

  /**
   * Add a namespace to the source as reference
   *
   * @param mixed  $enumerable - the object or array to add
   * @param string $namespace  - the namespace to set
   *
   * @return $this
   */
  protected function addr( &$enumerable, $namespace = null ) {

    if( is_array( $enumerable ) || is_object( $enumerable ) ) {
      $namespace                  = is_string( $namespace ) ? $namespace : $this->namespace;
      $this->source[ $namespace ] = &$enumerable;

      // clear the cache or the cache index
      if( $this->caching != self::CACHE_NONE ) {
        $this->clean( $this->parse( $namespace . self::SEPARATOR_NAMESPACE ) );
      }
    }

    return $this;
  }
  /**
   * Add a namespace to the source
   *
   * @param mixed  $enumerable - the object or array to add
   * @param string $namespace  - the namespace to set
   *
   * @return $this
   */
  protected function add( $enumerable, $namespace = null ) {

    if( is_object( $enumerable ) ) $enumerable = clone $enumerable;
    return $this->addr( $enumerable, $namespace );
  }
  /**
   * Parse index string into { string, tokens, namespace, key } object
   * or false, if key not a string or empty. String is the normalised
   * index in <namespace>:<key> format, tokens is the key exploded by dots
   *
   * @param mixed $index - <namespace>:<index> formatted string
   *
   * @return \stdClass
   */
  protected function parse( $index ) {

    if( !is_string( $index ) ) return false;
    else {

      $tmp = explode( self::SEPARATOR_NAMESPACE, trim( $index, ' ' . self::SEPARATOR_KEY ), 2 );
      $result = parent::parse( array_pop( $tmp ) );

      // define the namespace and add it to the token list for the search
      $result->namespace = array_pop( $tmp ) ?: $this->_namespace;
      array_unshift( $result->token, $result->namespace );

      $result->id = $result->namespace . self::SEPARATOR_NAMESPACE . $result->key;
      return $result;
    }
  }

  /**
   * JSON convert support with namespaces
   *
   * @return mixed|object
   */
  public function jsonSerialize() {
    return $this->convert();
  }
}
