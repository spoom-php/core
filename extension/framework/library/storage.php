<?php namespace Framework;

use Framework\Helper\Enumerable;
use Framework\Helper\Library;
use Framework\Helper\Number;
use Framework\Helper\Text;

/**
 * Interface StorageInterface
 * @package Framework\Storage
 *
 * TODO optimize the 'search' cache format (for non-exists indexes)
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
   * Text result type
   */
  const TYPE_STRING = 'string';
  /**
   * Number (int or float) result type
   */
  const TYPE_NUMBER = 'number';
  /**
   * Int result type
   */
  const TYPE_INTEGER = 'integer';
  /**
   * Float result type
   */
  const TYPE_FLOAT = 'float';
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
   * Get indexed (only int type) value from the storage, or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return int|mixed
   */
  public function getInteger( $index, $default = 0 );
  /**
   * Get indexed (only float type) value from the storage, or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return float|mixed
   */
  public function getFloat( $index, $default = 0.0 );
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
   * Same as the getString method, but insert data to string with Text::insert()
   *
   * @param string $index
   * @param array  $insertion
   * @param string $default
   *
   * @return null|string
   */
  public function getPattern( $index, $insertion, $default = '' );

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

  /**
   * @return int
   */
  public function getCaching();
  /**
   * @param int $value
   */
  public function setCaching( $value );
  /**
   * @return null|string
   */
  public function getNamespace();
  /**
   * @param null|string $value
   */
  public function setNamespace( $value );
  /**
   * @return array
   */
  public function getSource();
}

/**
 * Class Storage
 * @package Framework
 *
 * @since   0.6.0
 *
 * @property      int         $caching   The cache type. One of the CACHE_* constants
 * @property-read mixed       $source    The storage source variable
 * @property      string|null $namespace The default namespace if not provided. If null, no default namespace is added to the index
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
   * Create deep copy from the source and clears the cache
   */
  public function __clone() {

    $this->_source = Enumerable::copy( $this->_source );
    $this->cache   = [
      'index'  => [ ],
      'search' => [ ]
    ];
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
    if( $result->exist ) {

      if( $result->key === null ) $result->container = $value;
      else if( Enumerable::isArrayLike( $result->container ) ) $result->container[ $result->key ] = $value;
      else $result->container->{$result->key} = $value;

      // clear the cache or the cache index
      if( $this->_caching != self::CACHE_NONE ) {
        $this->clean( $index );
      }
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

    // get the value to extend it
    if( $result->key === null ) $value = $result->container;
    else if( Enumerable::isArrayLike( $result->container ) ) $value = $result->container[ $result->key ];
    else $value = $result->container->{$result->key};

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

    // set the manipulated value
    if( $result->key === null ) $result->container = $value;
    else if( Enumerable::isArrayLike( $result->container ) ) $result->container[ $result->key ] = $value;
    else $result->container->{$result->key} = $value;

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
      else if( Enumerable::isArrayLike( $result->container ) ) unset( $result->container[ $result->key ] );
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
        if( $result->key === null ) $value = $result->container;
        else if( Enumerable::isArrayLike( $result->container ) ) $value = $result->container[ $result->key ];
        else $value = $result->container->{$result->key};

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
    return $this->process( $this->index( $index ), $default );
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
   * Get indexed (only int type) value from the storage, or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return int|mixed
   */
  public function getInteger( $index, $default = 0 ) {

    $index       = $this->index( $index );
    $index->type = self::TYPE_INTEGER;

    return $this->process( $index, $default );
  }
  /**
   * Get indexed (only float type) value from the storage, or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return float|mixed
   */
  public function getFloat( $index, $default = 0 ) {

    $index       = $this->index( $index );
    $index->type = self::TYPE_FLOAT;

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
   * Same as the getString method, but insert data to string with Text::insert()
   *
   * @param string $index
   * @param array  $insertion
   * @param string $default
   *
   * @return null|string
   */
  public function getPattern( $index, $insertion, $default = '' ) {

    $value = $this->getString( $index, $default );
    return is_string( $value ) ? Text::insert( $value, $insertion ) : $value;
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
      $this->_source[ $namespace ] = &$enumerable;

      // clear the cache or the cache index
      if( $this->_caching != self::CACHE_NONE ) {
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

    // check the cache. Only load from cache if its getting ( read operation ) because if it's build then the returned value may changed outside
    if( $this->_caching != self::CACHE_NONE && $is_read && isset( $this->cache[ 'search' ][ $index->id ] ) ) {
      return (object) [
        'exist'     => $this->cache[ 'search' ][ $index->id ][ 'exist' ],
        'container' => $this->cache[ 'search' ][ $index->id ][ 'container' ],
        'key'       => $this->cache[ 'search' ][ $index->id ][ 'key' ]
      ];
    }

    $result = Enumerable::search( $this->_source, $index->token, $build );
    switch( $this->_caching ) {
      case self::CACHE_SIMPLE:

        $this->cache[ 'search' ][ $index->id ] = (array) $result;
        break;
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

      if( empty( $i ) || $i == $index->token[ 0 ] || strpos( $i, $index->token[ 0 ] ) === 0 ) {
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
   * @param object $index   The result of index() method
   * @param mixed  $default The default value if no right type cast or existance
   *
   * @return mixed
   */
  private function process( $index, $default ) {

    // define the default result
    $tmp = $this->search( $index );
    if( !$tmp->exist ) $result = $default;
    else if( $tmp->key === null ) $result = $tmp->container;
    else if( Enumerable::isArrayLike( $tmp->container ) ) $result = $tmp->container[ $tmp->key ];
    else $result = $tmp->container->{$tmp->key};

    // switch result based on the type
    switch( $index->type ) {
      // force string type
      case self::TYPE_STRING:

        if( $tmp->exist && ( is_string( $result ) || Number::is( $result, false ) || is_null( $result ) ) ) $result = (string) $result;
        else $result = $default;

        break;

      // force numeric type
      case self::TYPE_NUMBER:

        $result = $tmp->exist && Number::is( $result ) ? Number::read( $result ) : $default;
        break;

      // force integer type
      case 'int':
      case self::TYPE_INTEGER:

        $result = $tmp->exist && Number::is( $result ) ? ( (int) Number::read( $result ) ) : $default;
        break;

      // force float type
      case self::TYPE_FLOAT:

        $result = $tmp->exist && Number::is( $result ) ? ( (float) Number::read( $result ) ) : $default;
        break;
      
      // force array type
      case self::TYPE_ARRAY:

        $result = $tmp->exist && Enumerable::is( $result ) ? (array) $result : $default;
        break;

      // force object type
      case self::TYPE_OBJECT:

        $result = $tmp->exist && Enumerable::is( $result ) ? (object) $result : $default;
        break;

      // force boolean type
      case self::TYPE_BOOLEAN:

        $result = $tmp->exist && ( is_bool( $result ) || in_array( $result, [ 1, 0, '1', '0' ], true ) ) ? (bool) $result : $default;
        break;

      // force callable type
      case self::TYPE_CALLABLE:

        $result = $tmp->exist && is_callable( $result ) ? $result : $default;
        break;
    }

    return $result;
  }

  /**
   * @return int
   */
  public function getCaching() {
    return $this->_caching;
  }
  /**
   * @param int $value
   */
  public function setCaching( $value ) {
    $this->_caching = (int) $value;
    $this->clean();
  }
  /**
   * @return null|string
   */
  public function getNamespace() {
    return $this->_namespace;
  }
  /**
   * @param null|string $value
   */
  public function setNamespace( $value ) {
    $this->_namespace = !empty( $value ) ? (string) $value : null;

    // clear the index cache to avoid invalid index matches
    $this->cache[ 'index' ] = [ ];
  }
  /**
   * @return array
   */
  public function getSource() {
    return $this->_source;
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
