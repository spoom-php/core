<?php namespace Engine\Storage;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Advance
 * @package Engine\Storage
 *
 * @property string   namespace      default namespace
 * @property bool     caching        enable or disable cache
 */
class Advance extends Data {

  const CACHE_NONE      = 0;
  const CACHE_SIMPLE    = 1;
  const CACHE_REFERENCE = 2;

  /**
   * Cache data storage
   * @var array
   */
  private $cache = array();

  /**
   * Cache state ( enabled or disabled )
   * @var bool
   */
  private $_caching = self::CACHE_NONE;

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
  public function __construct( $namespace = 'default', $data = null, $caching = self::CACHE_SIMPLE ) {
    parent::__construct( $data );

    $this->namespace = $namespace;
    $this->caching   = $caching;
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
        $this->_namespace = '' . $value;

        break;
      case 'caching':
        $this->_caching = (int) $value;

        // clear the source cache
        $this->cache = array();
        break;
      case 'separator':

        // clear the source cache
        $this->cache = array();

      // falltrough
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

    $namespaces = is_array( $namespaces ) ? $namespaces : array( $namespaces );
    $result     = array();
    foreach( $namespaces as $n ) $result[ $n ] = $this->exist( $n . ':' ) ? $this->source[ $n ] : array();

    return $object ? (object) $result : $result;
  }

  /**
   * Extend index with data. If index and data is enumerable ( array or object )
   * it will be merge index with the data. If index and data is string, the index
   * concated with data. Finally if the index and the data is numeric, the data
   * will be added to the index.
   *
   * @param string $index
   * @param mixed  $data
   * @param bool   $recursive
   *
   * @return $this
   */
  public function extend( $index, $data, $recursive = false ) {
    $tmp = parent::extend( $index, $data, $recursive );

    // clear the cache or the cache index
    $index = $this->index( $index );
    if( $this->caching == self::CACHE_SIMPLE ) {
      if( $recursive ) $this->cache = array();
      else unset( $this->cache[ $index->id ] );
    }

    return $tmp;
  }
  /**
   * Set the index to value and create structure for the index
   * if it's not exist already
   *
   * @param string $index
   * @param mixed  $value
   *
   * @return $this
   */
  public function set( $index, $value ) {
    $tmp = parent::set( $index, $value );

    // clear the cache index
    $index = $this->index( $index );
    if( $this->caching == self::CACHE_SIMPLE ) unset( $this->cache[ $index->id ] );

    return $tmp;
  }
  /**
   * Remove an index from the storage. the index can't be null, so with
   * this function ou cannot clear the storage!
   *
   * @param string $index
   *
   * @return $this
   */
  public function remove( $index ) {
    $tmp = parent::remove( $index );

    // clear the cache or the cache index
    if( $this->caching != self::CACHE_NONE ) $this->cache = array();

    return $tmp;
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
      $this->source[ is_string( $namespace ) ? $namespace : $this->namespace ] = &$enumerable;

      // clear the cache or the cache index
      if( $this->caching != self::CACHE_NONE ) $this->cache = array();
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
   * Search for the index pointed value, and return the
   * result in a { exist, container, key }
   * like object. If the index was false, the key will be null. Otherwise the key always
   * set.
   *
   * @param \stdClass $index - the Advance::index method result
   * @param bool      $build - build structure if not exist
   *
   * @return object
   */
  protected function search( $index, $build = false ) {

    // check the cache. Only load from cache if its getting ( not build ) or if the cache is referenced
    // because if it's build then the returned value may changed outside
    if( $this->caching != self::CACHE_NONE && ( !$build || $this->caching == self::CACHE_REFERENCE ) && isset( $this->cache[ $index->id ] ) ) {
      return (object) array( 'exist' => true, 'container' => &$this->cache[ $index->id ][ 'container' ], 'key' => $this->cache[ $index->id ][ 'key' ] );
    }

    // delegate work to the parent, then save the result to the cache if enabled
    $result = parent::search( $index, $build );
    if( isset( $result->container ) && ( is_array( $result->container ) || is_object( $result->container ) ) && $this->caching != self::CACHE_NONE ) switch( $this->_caching ) {
      case self::CACHE_SIMPLE:
        $this->cache[ $index->id ] = array(
          'container' => $result->container,
          'key'       => $result->key
        );

        break;

      case self::CACHE_REFERENCE:
        $this->cache[ $index->id ] = array(
          'container' => &$result->container,
          'key'       => $result->key
        );

        break;
    }

    return $result;
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

      $tmp    = explode( ':', trim( $index, ' ' . $this->separator ), 2 );
      $result = parent::parse( array_pop( $tmp ) );
      
      // define the namespace and add it to the token list for the search
      $result->namespace = array_pop( $tmp ) ?: $this->namespace;
      array_unshift( $result->token, $result->namespace );

      $result->id        = $result->namespace . ':' . $result->key;
      return $result;
    }
  }
}