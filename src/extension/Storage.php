<?php namespace Spoom\Framework;

use Spoom\Framework\Helper\Enumerable;
use Spoom\Framework\Helper;
use Spoom\Framework\Helper\Number;
use Spoom\Framework\Helper\Text;

/**
 * Interface StorageInterface
 * @package Framework\Storage
 *
 * TODO optimize the 'search' cache format (for non-exists indexes)
 */
interface StorageInterface extends \ArrayAccess, \JsonSerializable {

  /**
   * Get indexed value from the storage
   *
   * ..or the second parameter if index not exist
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return mixed
   */
  public function get( string $index, $default = null );
  /**
   * Get indexed (only string type) value from the storage
   *
   * ..or the second parameter if index not exist or not string
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return string|mixed
   */
  public function getString( string $index, $default = '' );
  /**
   * Get indexed (only number type) value from the storage
   *
   * ..or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return int|float|mixed
   */
  public function getNumber( string $index, $default = 0 );
  /**
   * Get indexed (only int type) value from the storage
   *
   * ..or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return int|mixed
   */
  public function getInteger( string $index, $default = 0 );
  /**
   * Get indexed (only float type) value from the storage
   *
   * ..or the second parameter if index not exist, not int or
   * float
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return float|mixed
   */
  public function getFloat( string $index, $default = 0.0 );
  /**
   * Get indexed (only array type) value from the storage
   *
   * ..or the second parameter if index not exist or not
   * enumerable. Object will be typecasted to array
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return array|mixed
   */
  public function getArray( string $index, $default = [] );
  /**
   * Get indexed (only object type) value from the storage
   *
   * ..or the second parameter if index not exist or not
   * enumerable. Arrays will be typecasted to object
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return object|mixed
   */
  public function getObject( string $index, $default = null );
  /**
   * Get indexed (only bool type) value from the storage
   *
   * ..or the second parameter if index not exist, not bool
   * or not 1/0 value
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return bool|mixed
   */
  public function getBoolean( string $index, $default = false );
  /**
   * Get indexed (only callable type) value from the storage
   *
   * ..or the second parameter if index not exist or not
   * callable
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return callable|mixed
   */
  public function getCallable( string $index, $default = null );
  /**
   * Same as the getString method, but insert data to string with Text::insert()
   *
   * @param string       $index
   * @param array|object $insertion
   * @param string       $default
   *
   * @return null|string
   */
  public function getPattern( string $index, $insertion, $default = '' );

  /**
   * Set the index to value and create structure for the index
   * if it's not exist already
   *
   * @param string $index
   * @param mixed  $value
   *
   * @return $this
   */
  public function set( string $index, $value );
  /**
   * Extend index with data
   *
   * There are several options based on the data:
   *  - Index and data is enumerable ( array or object ), it will merge them
   *  - Index and data is string, the index concated with data
   *  - Index and data is numeric, the data will be added to the index
   *
   * @param string $index
   * @param mixed  $data
   * @param bool   $recursive
   *
   * @return $this
   */
  public function extend( string $index, $data, bool $recursive = false );
  /**
   * Remove an index from the storage. the index can't be null, so with
   * this function ou cannot clear the storage!
   *
   * @param string $index
   *
   * @return $this
   */
  public function clear( string $index );

  /**
   * Check index existance
   *
   * @param string $index
   *
   * @return bool
   */
  public function exist( string $index ): bool;
  /**
   * Iterate through an index with given function.
   * The function get value, index, this params
   * each iteration.
   * The function parameters are: key, value, index, self
   *
   *
   * @param callable    $function
   * @param string|null $index
   *
   * @return $this
   */
  public function each( callable $function, ?string $index = null );

  /**
   * @return int
   */
  public function getCaching(): int;
  /**
   * @param int $value
   */
  public function setCaching( int $value );
  /**
   * @return null|string
   */
  public function getNamespace(): ?string;
  /**
   * @param null|string $value
   */
  public function setNamespace( ?string $value );
  /**
   * @return array|object
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
class Storage implements StorageInterface, Helper\AccessableInterface {
  use Helper\Accessable;

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
   * Cache for indexes and search results
   *
   * @var array
   */
  private $cache = [
    'index'  => [],
    'search' => []
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
  protected $_source = [];

  /**
   * @param mixed|null  $data
   * @param string|null $namespace
   * @param int         $caching
   */
  public function __construct( $data = null, ?string $namespace = null, int $caching = self::CACHE_SIMPLE ) {

    $this->_caching   = $caching;
    $this->_namespace = $namespace;

    if( Enumerable::is( $data ) ) $this->_source = $data;
  }

  /**
   * Create deep copy from the source and clears the cache
   */
  public function __clone() {

    $this->_source = Enumerable::copy( $this->_source );
    $this->cache   = [
      'index'  => [],
      'search' => []
    ];
  }

  //
  public function set( string $index, $value ) {

    $result = $this->search( $index = $this->index( $index ), true, false );
    if( $result->exist ) {

      if( $result->key === null ) $result->container = $value;
      else if( Enumerable::isArrayLike( $result->container ) ) $result->container[ $result->key ] = $value;
      else $result->container->{$result->key} = $value;

      // clear the cache or the cache index
      if( $this->_caching != static::CACHE_NONE ) {
        $this->clean( $index );
      }
    }

    return $this;
  }
  //
  public function extend( string $index, $data, bool $recursive = false ) {
    $result = $this->search( $index = $this->index( $index ), true, false );

    // get the value to extend it
    if( $result->key === null ) $value = $result->container;
    else if( Enumerable::isArrayLike( $result->container ) ) $value = $result->container[ $result->key ];
    else $value = $result->container->{$result->key};

    // handle data manipulation
    if( !isset( $value ) ) $value = $data;
    else if( Number::is( $data, true ) && Number::is( $value, true ) ) $value += $data;
    else if( Text::is( $data, true ) && Text::is( $value, true ) ) $value .= $data;
    else if( Enumerable::is( $data ) && Enumerable::is( $value ) ) {

      $convert = is_object( $value );
      $data    = Enumerable::read( $data, false, [] );
      $value   = Enumerable::read( $value, false, [] );

      $value = $recursive ? array_merge_recursive( $value, $data ) : array_merge( $value, $data );
      if( $convert ) $value = (object) $value;
    }

    // set the manipulated value
    if( $result->key === null ) $result->container = $value;
    else if( Enumerable::isArrayLike( $result->container ) ) $result->container[ $result->key ] = $value;
    else $result->container->{$result->key} = $value;

    // clear the cache or the cache index
    if( $this->_caching != static::CACHE_NONE ) $this->clean( $index );

    return $this;
  }
  //
  public function clear( string $index ) {

    $result = $this->search( $index = $this->index( $index ), false, false );
    if( $result->exist ) {

      if( $result->key === null ) $result->container = [];
      else if( Enumerable::isArrayLike( $result->container ) ) unset( $result->container[ $result->key ] );
      else unset( $result->container->{$result->key} );

      // clear the cache or the cache index
      if( $this->_caching != static::CACHE_NONE ) $this->clean( $index );
    }

    return $this;
  }

  //
  public function exist( string $index ): bool {
    return $this->search( $this->index( $index ) )->exist;
  }
  //
  public function each( callable $function, ?string $index = null ) {

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
          $full_key = trim( $index->key ) === '' ? ( $index->id . $key ) : ( $index->id . static::SEPARATOR_KEY . $key );
          $result   = $function( $key, $data, !$index ? $key : $full_key, $this );

          if( $result === false ) break;
        }
      }
    }

    return $this;
  }

  //
  public function get( string $index, $default = null ) {
    return $this->process( $this->index( $index ), $default );
  }
  //
  public function getString( string $index, $default = '' ) {

    $index       = $this->index( $index );
    $index->type = static::TYPE_STRING;

    return $this->process( $index, $default );
  }
  //
  public function getNumber( string $index, $default = 0 ) {

    $index       = $this->index( $index );
    $index->type = static::TYPE_NUMBER;

    return $this->process( $index, $default );
  }
  //
  public function getInteger( string $index, $default = 0 ) {

    $index       = $this->index( $index );
    $index->type = static::TYPE_INTEGER;

    return $this->process( $index, $default );
  }
  //
  public function getFloat( string $index, $default = 0 ) {

    $index       = $this->index( $index );
    $index->type = static::TYPE_FLOAT;

    return $this->process( $index, $default );
  }
  //
  public function getArray( string $index, $default = [] ) {

    $index       = $this->index( $index );
    $index->type = static::TYPE_ARRAY;

    return $this->process( $index, $default );
  }
  //
  public function getObject( string $index, $default = null ) {

    $index       = $this->index( $index );
    $index->type = static::TYPE_OBJECT;

    return $this->process( $index, $default );
  }
  //
  public function getBoolean( string $index, $default = false ) {

    $index       = $this->index( $index );
    $index->type = static::TYPE_BOOLEAN;

    return $this->process( $index, $default );
  }
  //
  public function getCallable( string $index, $default = null ) {

    $index       = $this->index( $index );
    $index->type = static::TYPE_CALLABLE;

    return $this->process( $index, $default );
  }
  //
  public function getPattern( string $index, $insertion, $default = '' ) {

    $value = $this->getString( $index, $default );
    return is_string( $value ) ? Text::insert( $value, $insertion ) : $value;
  }

  /**
   * Connect a namespace source to an enumerable
   *
   * @param array|object $enumerable - the object or array to add
   * @param string       $namespace  - the namespace to set
   *
   * @return $this
   */
  protected function connect( &$enumerable, string $namespace ) {

    if( Enumerable::is( $enumerable ) ) {
      $this->_source[ $namespace ] = &$enumerable;

      // clear the cache or the cache index
      if( $this->_caching != static::CACHE_NONE ) {
        $this->clean( $this->parse( $namespace . static::SEPARATOR_NAMESPACE ) );
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
   * @param object $index   The Simple::parse method result
   * @param bool   $build   Build structure if not exist (when true, the third parameter can't be true)
   * @param bool   $is_read The search result will be used to read or write operation (don't use simple cache for write)
   *
   * @return object
   */
  protected function search( $index, bool $build = false, bool $is_read = true ) {

    // check the cache. Only load from cache if its getting ( read operation ) because if it's build then the returned value may changed outside
    if( $this->_caching != static::CACHE_NONE && $is_read && isset( $this->cache[ 'search' ][ $index->id ] ) ) {
      return (object) [
        'exist'     => $this->cache[ 'search' ][ $index->id ][ 'exist' ],
        'container' => $this->cache[ 'search' ][ $index->id ][ 'container' ],
        'key'       => $this->cache[ 'search' ][ $index->id ][ 'key' ]
      ];
    }

    $result = Enumerable::search( $this->_source, $index->token, $build );
    switch( $this->_caching ) {
      case static::CACHE_SIMPLE:

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
  protected function parse( string $index ) {

    $result = (object) [ 'id' => '', 'key' => '', 'token' => [], 'type' => null, 'namespace' => null ];
    $tmp    = explode( static::SEPARATOR_NAMESPACE, trim( $index, ' ' . static::SEPARATOR_KEY ), 2 );

    // normalize the index to id
    $part_main  = rtrim( trim( array_pop( $tmp ), ' ' . static::SEPARATOR_KEY ), static::SEPARATOR_TYPE );
    $result->id = $part_main;

    // define and parse key, type
    $part_main    = explode( static::SEPARATOR_TYPE, $part_main, 2 );
    $result->key  = trim( $part_main[ 0 ] );
    $result->type = empty( $part_main[ 1 ] ) ? null : $part_main[ 1 ];

    // explode key into tokens
    $result->token = $result->key === '' ? [] : explode( static::SEPARATOR_KEY, $result->key );

    // define the namespace and add it to the token list for the search
    $result->namespace = array_pop( $tmp ) ?: $this->_namespace;
    if( $result->namespace ) array_unshift( $result->token, $result->namespace );

    $result->id = ( $result->namespace ? ( $result->namespace . static::SEPARATOR_NAMESPACE ) : '' ) . $result->key;
    return $result;
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
    if( !$index || empty( $index->token ) ) $this->cache[ 'search' ] = [];
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
  private function index( string $index ) {

    if( !isset( $this->cache[ 'index' ][ $index ] ) ) {

      $result                           = $this->parse( $index );
      $this->cache[ 'index' ][ $index ] = $result;
    }

    return clone $this->cache[ 'index' ][ $index ];
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
    else if( $tmp->container instanceof StorageInterface ) $result = $tmp->container->get( $tmp->key . static::SEPARATOR_TYPE . $index->type, $default );
    else if( Enumerable::isArrayLike( $tmp->container ) ) $result = $tmp->container[ $tmp->key ];
    else $result = $tmp->container->{$tmp->key};

    // switch result based on the type
    switch( $index->type ) {
      // force string type
      case static::TYPE_STRING:

        if( $tmp->exist && ( Text::is( $result ) ) ) $result = Text::read( $result );
        else $result = $default;

        break;

      // force numeric type
      case 'num':
      case static::TYPE_NUMBER:

        $result = $tmp->exist ? Number::read( $result, $default ) : $default;
        break;

      // force integer type
      case 'int':
      case static::TYPE_INTEGER:

        $result = $tmp->exist && Number::is( $result ) ? ( (int) Number::read( $result ) ) : $default;
        break;

      // force float type
      case 'double':
      case 'real':
      case static::TYPE_FLOAT:

        $result = $tmp->exist && Number::is( $result ) ? ( (float) Number::read( $result ) ) : $default;
        break;

      // force array type
      case static::TYPE_ARRAY:

        $result = $tmp->exist ? Enumerable::read( $result, false, $default ) : $default;
        break;

      // force object type
      case static::TYPE_OBJECT:

        $result = $tmp->exist ? Enumerable::read( $result, true, $default ) : $default;
        break;

      // force boolean type
      case 'bool':
      case static::TYPE_BOOLEAN:

        $result = $tmp->exist && ( is_bool( $result ) || in_array( $result, [ 1, 0, '1', '0' ], true ) ) ? (bool) $result : $default;
        break;

      // force callable type
      case static::TYPE_CALLABLE:

        $result = $tmp->exist && is_callable( $result ) ? $result : $default;
        break;
    }

    return $result;
  }

  //
  public function getCaching(): int {
    return $this->_caching;
  }
  //
  public function setCaching( int $value ) {
    $this->_caching = $value;
    $this->clean();
  }
  //
  public function getNamespace(): ?string {
    return $this->_namespace;
  }
  //
  public function setNamespace( ?string $value ) {
    $this->_namespace = $value !== null ? $value : null;

    // clear the index cache to avoid invalid index matches
    $this->cache[ 'index' ] = [];
  }
  //
  public function getSource() {
    return $this->_source;
  }

  //
  public function jsonSerialize() {
    return $this->getObject( '' );
  }

  //
  public function offsetExists( $offset ) {
    return $this->exist( $offset );
  }
  //
  public function offsetGet( $offset ) {
    return $this->get( $offset );
  }
  //
  public function offsetSet( $offset, $value ) {
    $this->set( $offset, $value );
  }
  //
  public function offsetUnset( $offset ) {
    $this->clear( $offset );
  }
}
