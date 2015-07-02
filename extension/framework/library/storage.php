<?php namespace Framework;

use Framework\Helper\Enumerable;
use Framework\Helper\Library;

/**
 * Interface StorageInterface
 * @package Framework\Storage
 */
interface StorageInterface extends \ArrayAccess, \JsonSerializable {

  /**
   * Disable caching
   */
  const CACHE_NONE = 0;
  /**
   * Cache only the actual value of the result
   */
  const CACHE_SIMPLE = 1;
  /**
   * Cache results by reference (might be buggy with arrays in arrays)
   */
  const CACHE_REFERENCE = 2;

  /**
   * Key separator in the indexes
   */
  const SEPARATOR_KEY = '.';
  /**
   * Separator char for the namespace in the indexes
   */
  const SEPARATOR_NAMESPACE = ':';
  /**
   * Type forcing separator in the indexes
   */
  const SEPARATOR_TYPE = '!';

  /**
   * String result type
   */
  const TYPE_STRING = 'string';
  /**
   * Number (int or float) result type
   */
  const TYPE_NUMBER = 'number';
  /**
   * Boolean result type
   */
  const TYPE_BOOLEAN = 'boolean';
  /**
   * Array result type
   */
  const TYPE_ARRAY = 'array';
  /**
   * Object result type
   */
  const TYPE_OBJECT = 'object';
  /**
   * Callable result type
   */
  const TYPE_CALLABLE = 'callable';

  /**
   * Get indexed value from the storage, or the second parameter if index not exist
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return mixed
   */
  public function get( $index, $default = null );
  /**
   * Get indexed (only string type) value from the storage, or the second parameter if index not exist or not string
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return string|mixed
   */
  public function getString( $index, $default = '' );
  /**
   * Get indexed (only number type) value from the storage, or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return number|mixed
   */
  public function getNumber( $index, $default = 0 );
  /**
   * Get indexed (only array type) value from the storage, or the second parameter if index not exist or not
   * enumerable. Object will be typecasted to array
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return array|mixed
   */
  public function getArray( $index, $default = [ ] );
  /**
   * Get indexed (only object type) value from the storage, or the second parameter if index not exist or not
   * enumerable. Arrays will be typecasted to object
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return object|mixed
   */
  public function getObject( $index, $default = null );
  /**
   * Get indexed (only boolean type) value from the storage, or the second parameter if index not exist, not boolean
   * or not 1/0 value
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return bool|mixed
   */
  public function getBoolean( $index, $default = false );
  /**
   * Get indexed (only callable type) value from the storage, or the second parameter if index not exist or not
   * callable
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return callable|mixed
   */
  public function getCallable( $index, $default = null );

  /**
   * Set the index to value and create structure for the index
   * if it's not exist already
   *
   * @param string $index
   * @param mixed  $value
   *
   * @return $this
   */
  public function set( $index, $value );
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
  public function extend( $index, $data, $recursive = false );
  /**
   * Remove an index from the storage. the index can't be null, so with
   * this function ou cannot clear the storage!
   *
   * @param string $index
   *
   * @return $this
   */
  public function clear( $index );

  /**
   * Check index existance
   *
   * @param string $index
   *
   * @return bool
   */
  public function exist( $index );
  /**
   * Iterate through an index with given function.
   * The function get value, index, this params
   * each iteration.
   * The function parameters are: key, value, index, self
   *
   *
   * @param callable $function
   * @param string   $index
   *
   * @return $this
   */
  public function each( $function, $index );
}

/**
 * Class Storage
 * @package Framework
 *
 * @property      int         $caching The cache type. One of the CACHE_* constants
 * @property-read mixed       $source
 * @property      string|null $namespace
 */
class Storage extends Library implements StorageInterface {

  /**
   * Cache for indexes and search results
   *
   * @var array
   */
  private $cache = [
    'index'  => [ ],
    'search' => [ ]
  ];
  /**
   * Cache type
   *
   * @var int
   */
  private $_caching = self::CACHE_NONE;

  /**
   * Use a default namespace in the index or not. If it's null, than the indexes without namespace behave
   * like the storage doesn't have namespaces at all (the index starts from the source root)
   *
   * @var string|null
   */
  protected $_namespace = null;

  /**
   * Data storage
   *
   * @var array
   */
  protected $_source = [ ];

  /**
   * @param mixed|null  $data
   * @param string|null $namespace
   * @param int         $caching
   */
  public function __construct( $data = null, $namespace = null, $caching = self::CACHE_SIMPLE ) {

    if( Enumerable::is( $data ) ) $this->_source = $data;
    $this->_caching   = $caching;
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
   * @param string $index
   * @param mixed  $value
   */
  public function __set( $index, $value ) {

    switch( $index ) {
      case 'namespace':

        $this->_namespace = !empty( $value ) ? (string) $value : null;

        break;
      case 'caching':

        $this->_caching = (int) $value;
        $this->clean();

        break;
    }
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

    $result = $this->search( $index = $this->index( $index ), true, false );
    if( $index ) {

      if( $result->key === null ) $target = &$result->container;
      else if( is_array( $result->container ) ) $target = &$result->container[ $result->key ];
      else $target = &$result->container->{$result->key};

      $target = $value;

      // clear the cache or the cache index
      if( $this->_caching != self::CACHE_NONE ) $this->clean( $index );
    }

    return $this;
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
    $result = $this->search( $index = $this->index( $index ), true, false );

    // set the value
    if( $result->key === null ) $value = &$result->container;
    else if( is_array( $result->container ) ) $value = &$result->container[ $result->key ];
    else $value = &$result->container->{$result->key};

    // handle data manipulation
    if( !isset( $value ) ) $value = $data;
    else if( is_numeric( $data ) && is_numeric( $value ) ) $value += $data;
    else if( is_string( $data ) && is_string( $value ) ) $value .= $data;
    else if( Enumerable::is( $data ) && Enumerable::is( $value ) ) {

      $convert = is_object( $value );
      $data    = is_object( $data ) && $data instanceof \JsonSerializable ? (array) $data->jsonSerialize() : (array) $data;
      $value   = is_object( $value ) && $value instanceof \JsonSerializable ? (array) $value->jsonSerialize() : (array) $value;

      $value = $recursive ? array_merge_recursive( $value, $data ) : array_merge( $value, $data );
      if( $convert ) $value = (object) $value;
    }

    // clear the cache or the cache index
    if( $this->_caching != self::CACHE_NONE ) $this->clean( $index );

    return $this;
  }
  /**
   * Remove an index from the storage. the index can't be null, so with
   * this function ou cannot clear the storage!
   *
   * @param string $index
   *
   * @return $this
   */
  public function clear( $index ) {

    $result = $this->search( $index = $this->index( $index ), false, false );
    if( $result->exist ) {

      if( $result->key === null ) $result->container = [ ];
      else if( is_array( $result->container ) ) unset( $result->container[ $result->key ] );
      else unset( $result->container->{$result->key} );

      // clear the cache or the cache index
      if( $this->_caching != self::CACHE_NONE ) $this->clean( $index );
    }

    return $this;
  }

  /**
   * Check index existance
   *
   * @param string $index
   *
   * @return bool
   */
  public function exist( $index ) {
    return $this->search( $this->index( $index ) )->exist;
  }
  /**
   * Iterate through an index with given function.
   * The function get value, index, this params
   * each iteration.
   * The function parameters are: key, value, index, self
   *
   *
   * @param callable $function
   * @param string   $index
   *
   * @return $this
   */
  public function each( $function, $index = null ) {

    // first check the function type
    if( is_callable( $function ) ) {

      // check result existance
      $result = $this->search( $index = $this->parse( $index ) );
      if( $result->exist ) {

        // find the value
        if( $result->key === null ) $value = &$result->container;
        else if( is_array( $result->container ) ) $value = &$result->container[ $result->key ];
        else $value = &$result->container->{$result->key};

        // check the value type
        if( Enumerable::is( $value ) ) {

          foreach( $value as $key => $data ) {
            $full_key = trim( $index->key ) === '' ? ( $index->id . $key ) : ( $index->id . self::SEPARATOR_KEY . $key );
            $result   = $function( $key, $data, !$index ? $key : $full_key, $this );

            if( $result === false ) break;
          }
        }
      }
    }

    return $this;
  }

  /**
   * Get indexed value from the storage, or the second parameter if index not exist
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return mixed
   */
  public function get( $index, $default = null ) {
    return $this->process( $this->index( $index ), $default, func_num_args() > 1 );
  }
  /**
   * Get indexed (only string type) value from the storage, or the second parameter if index not exist or not string
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return string|mixed
   */
  public function getString( $index, $default = '' ) {

    $index       = $this->index( $index );
    $index->type = self::TYPE_STRING;

    return $this->process( $index, $default );
  }
  /**
   * Get indexed (only number type) value from the storage, or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return number|mixed
   */
  public function getNumber( $index, $default = 0 ) {

    $index       = $this->index( $index );
    $index->type = self::TYPE_NUMBER;

    return $this->process( $index, $default );
  }
  /**
   * Get indexed (only array type) value from the storage, or the second parameter if index not exist or not
   * enumerable. Object will be typecasted to array
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return array|mixed
   */
  public function getArray( $index, $default = [ ] ) {

    $index       = $this->index( $index );
    $index->type = self::TYPE_ARRAY;

    return $this->process( $index, $default );
  }
  /**
   * Get indexed (only object type) value from the storage, or the second parameter if index not exist or not
   * enumerable. Arrays will be typecasted to object
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return object|mixed
   */
  public function getObject( $index, $default = null ) {

    $index       = $this->index( $index );
    $index->type = self::TYPE_OBJECT;

    return $this->process( $index, $default );
  }
  /**
   * Get indexed (only boolean type) value from the storage, or the second parameter if index not exist, not boolean
   * or not 1/0 value
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return bool|mixed
   */
  public function getBoolean( $index, $default = false ) {

    $index       = $this->index( $index );
    $index->type = self::TYPE_BOOLEAN;

    return $this->process( $index, $default );
  }
  /**
   * Get indexed (only callable type) value from the storage, or the second parameter if index not exist or not
   * callable
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return callable|mixed
   */
  public function getCallable( $index, $default = null ) {

    $index       = $this->index( $index );
    $index->type = self::TYPE_CALLABLE;

    return $this->process( $index, $default );
  }

  /**
   * Connect a namespace source to an enumerable
   *
   * @param mixed  $enumerable - the object or array to add
   * @param string $namespace  - the namespace to set
   *
   * @return $this
   */
  protected function connect( &$enumerable, $namespace ) {

    if( Enumerable::is( $enumerable ) ) {
      $this->source[ $namespace ] = &$enumerable;

      // clear the cache or the cache index
      if( $this->caching != self::CACHE_NONE ) {
        $this->clean( $this->parse( $namespace . self::SEPARATOR_NAMESPACE ) );
      }
    }

    return $this;
  }
  /**
   * Search for the index pointed value, and return the
   * result in a { exist, container, key }
   * like object. If the index was false, the key will be null. Otherwise the key always
   * set.
   *
   * @param \stdClass $index   The Simple::parse method result
   * @param bool      $build   Build structure if not exist (when true, the third parameter can't be true)
   * @param bool      $is_read The search result will be used to read or write operation (don't use simple cache for write)
   *
   * @return object
   */
  protected function search( $index, $build = false, $is_read = true ) {

    // check the cache. Only load from cache if its getting ( read operation ) or if the cache is referenced
    // because if it's build then the returned value may changed outside
    if( $this->_caching != self::CACHE_NONE && ( $is_read || $this->_caching == self::CACHE_REFERENCE ) && isset( $this->cache[ 'search' ][ $index->id ] ) ) {
      return (object) [
        'exist'     => true,
        'container' => &$this->cache[ 'search' ][ $index->id ][ 'container' ],
        'key'       => $this->cache[ 'search' ][ $index->id ][ 'key' ]
      ];
    }

    $result = Enumerable::search( $this->_source, $index->token, $build );
    if( Enumerable::is( $result->container ) ) {

      switch( $this->_caching ) {
        case self::CACHE_SIMPLE:

          $this->cache[ 'search' ][ $index->id ] = [
            'container' => $result->container,
            'key'       => $result->key
          ];
          break;

        case self::CACHE_REFERENCE:
          $this->cache[ 'search' ][ $index->id ] = [
            'container' => &$result->container,
            'key'       => $result->key
          ];
          break;
      }
    }

    return $result;
  }
  /**
   * Parse the standard index to pieces for easier handling
   *
   * @param string $index
   *
   * @return object|null
   */
  protected function parse( $index ) {

    if( !is_string( $index ) ) return null;
    else {

      $result = (object) [ 'id' => '', 'key' => '', 'token' => [ ], 'type' => null, 'namespace' => null ];
      $tmp    = explode( self::SEPARATOR_NAMESPACE, trim( $index, ' ' . self::SEPARATOR_KEY ), 2 );

      // normalize the index to id
      $part_main  = rtrim( trim( array_pop( $tmp ), ' ' . self::SEPARATOR_KEY ), self::SEPARATOR_TYPE );
      $result->id = $part_main;

      // define and parse key, type
      $part_main    = explode( self::SEPARATOR_TYPE, $part_main, 2 );
      $result->key  = trim( $part_main[ 0 ] );
      $result->type = empty( $part_main[ 1 ] ) ? null : $part_main[ 1 ];

      // explode key into tokens
      $result->token = $result->key === '' ? [ ] : explode( self::SEPARATOR_KEY, $result->key );

      // define the namespace and add it to the token list for the search
      $result->namespace = array_pop( $tmp ) ?: $this->_namespace;
      if( $result->namespace ) array_unshift( $result->token, $result->namespace );

      $result->id = ( $result->namespace ? ( $result->namespace . self::SEPARATOR_NAMESPACE ) : '' ) . $result->key;
      return $result;
    }
  }
  /**
   * Clean the search cache based on the index
   *
   * @param string|object|null $index The result of `->index()` method or null
   */
  protected function clean( $index = null ) {

    // parse string index
    if( $index && !is_object( $index ) ) {
      $index = $this->index( $index );
    }

    // clear the cache
    if( !$index || empty( $index->token ) ) $this->cache[ 'search' ] = [ ];
    else foreach( $this->cache[ 'search' ] as $i => $_ ) {

      if( $i == $index->token[ 0 ] || strpos( $i, $index->token[ 0 ] ) === 0 ) {
        unset( $this->cache[ 'search' ][ $i ] );
      }
    }
  }

  /**
   * Wrapper for index parsing
   *
   * @param string $index
   *
   * @return object|null
   */
  private function index( $index ) {

    if( is_string( $index ) && isset( $this->cache[ 'index' ][ $index ] ) ) return $this->cache[ 'index' ][ $index ];
    else {

      $result                           = $this->parse( $index );
      $this->cache[ 'index' ][ $index ] = &$result;

      return $result;
    }
  }
  /**
   * Process the storage getter result. This will convert the result to the right type
   *
   * @param object    $index       The result of index() method
   * @param mixed     $default     The default value if no right type cast or existance
   * @param   boolean $use_default Flag for
   *
   * @return mixed
   */
  private function process( $index, $default, $use_default = true ) {

    // define the default result
    $tmp = $this->search( $index );
    if( !$tmp->exist ) $result = $default;
    else $result = $tmp->key !== null ? ( is_array( $tmp->container ) ? $tmp->container[ $tmp->key ] : $tmp->container->{$tmp->key} ) : $tmp->container;

    // switch result based on the type
    switch( $index->type ) {
      // force string type
      case self::TYPE_STRING:

        if( $tmp->exist && ( is_string( $result ) || is_numeric( $result ) || is_null( $result ) ) ) $result = (string) $result;
        else $result = ( $use_default ? $default : '' );

        break;

      // force numeric type
      case self::TYPE_NUMBER:

        $result = $tmp->exist && is_numeric( $result ) ? ( $result == (int) $result ? (int) $result : (float) $result ) : ( $use_default ? $default : 0 );
        break;

      // force array type
      case self::TYPE_ARRAY:

        $result = $tmp->exist && ( is_array( $result ) || is_object( $result ) ) ? (array) $result : ( $use_default ? $default : [ ] );
        break;

      // force object type
      case self::TYPE_OBJECT:

        $result = $tmp->exist && ( is_object( $result ) || is_array( $result ) ) ? (object) $result : ( $use_default ? $default : null );
        break;

      // force boolean type
      case self::TYPE_BOOLEAN:

        $result = $tmp->exist && ( is_bool( $result ) || in_array( $result, [ 1, 0, '1', '0' ], true ) ) ? (bool) $result : ( $use_default ? $default : false );
        break;

      // force callable type
      case self::TYPE_CALLABLE:

        $result = $tmp->exist && is_callable( $result ) ? $result : ( $use_default ? $default : null );
        break;
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  public function jsonSerialize() {
    return $this->getObject( '' );
  }
  /**
   * @inheritdoc
   */
  public function offsetExists( $offset ) {
    return $this->exist( $offset );
  }
  /**
   * @inheritdoc
   */
  public function offsetGet( $offset ) {
    return $this->get( $offset );
  }
  /**
   * @inheritdoc
   */
  public function offsetSet( $offset, $value ) {
    $this->set( $offset, $value );
  }
  /**
   * @inheritdoc
   */
  public function offsetUnset( $offset ) {
    $this->clear( $offset );
  }
}
