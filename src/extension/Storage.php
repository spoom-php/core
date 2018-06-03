<?php namespace Spoom\Core;

use Spoom\Core\Helper\Collection;
use Spoom\Core\Helper;
use Spoom\Core\Helper\Number;
use Spoom\Core\Helper\Text;

/**
 * Interface StorageInterface
 */
interface StorageInterface extends \ArrayAccess, \Iterator, \Countable {

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
   * Get any value from the storage
   *
   * @param string $index
   * @param mixed  $default
   *
   * @return mixed
   */
  public function get( string $index, $default = null );
  /**
   * Get indexed (only string type) value from the storage
   *
   * ..or the second parameter if index not exist or not string
   *
   * @param string      $index   The index in the storage
   * @param string|null $default The returned value if index not found
   *
   * @return string|null
   */
  public function getString( string $index, ?string $default = '' ): ?string;
  /**
   * Get indexed (only number type) value from the storage
   *
   * ..or the second parameter if index not exist, not int or
   * float
   *
   * @param string         $index   The index in the storage
   * @param int|float|null $default The returned value if index not found
   *
   * @return int|float|null
   */
  public function getNumber( string $index, $default = 0 );
  /**
   * Get indexed (only int type) value from the storage
   *
   * ..or the second parameter if index not exist, not int or
   * float
   *
   * @param string   $index   The index in the storage
   * @param int|null $default The returned value if index not found
   *
   * @return int|null
   */
  public function getInteger( string $index, ?int $default = 0 ): ?int;
  /**
   * Get indexed (only float type) value from the storage
   *
   * ..or the second parameter if index not exist, not int or
   * float
   *
   * @param string     $index   The index in the storage
   * @param float|null $default The returned value if index not found
   *
   * @return float|null
   */
  public function getFloat( string $index, ?float $default = 0.0 ): ?float;
  /**
   * Get indexed (only array type) value from the storage
   *
   * ..or the second parameter if index not exist or not a
   * collection. Object will be typecasted to array
   *
   * @param string     $index   The index in the storage
   * @param array|null $default The returned value if index not found
   *
   * @return array|null
   */
  public function getArray( string $index, ?array $default = [] ): ?array;
  /**
   * Get indexed (only object type) value from the storage
   *
   * ..or the second parameter if index not exist or not a
   * collection. Arrays will be typecasted to object
   *
   * @param string      $index   The index in the storage
   * @param object|null $default The returned value if index not found
   *
   * @return object|null
   */
  public function getObject( string $index, $default = null );
  /**
   * Get indexed (only bool type) value from the storage
   *
   * ..or the second parameter if index not exist, not bool
   * or not 1/0 value
   *
   * @param string    $index   The index in the storage
   * @param bool|null $default The returned value if index not found
   *
   * @return bool|null
   */
  public function getBoolean( string $index, ?bool $default = false ): ?bool;
  /**
   * Get indexed (only callable type) value from the storage
   *
   * ..or the second parameter if index not exist or not
   * callable
   *
   * @param string        $index   The index in the storage
   * @param callable|null $default The returned value if index not found
   *
   * @return callable|null
   */
  public function getCallable( string $index, ?callable $default = null ): ?callable;
  /**
   * Same as the getString method, but insert data to string with Text::insert()
   *
   * @param string       $index
   * @param array|object $insertion
   * @param string       $default
   *
   * @return string|null
   */
  public function getPattern( string $index, $insertion, ?string $default = '' ): ?string;

  /**
   * @return bool
   */
  public function isCaching(): bool;
  /**
   * @param bool $value
   */
  public function setCaching( bool $value = true );
  /**
   * @return array|object
   */
  public function getSource();
}

/**
 * Class Storage
 *
 * @since   0.6.0
 *
 * @property      bool         $caching   Enable or disable cacheing
 * @property-read array|object $source    The storage source variable
 */
class Storage implements StorageInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Cache for search results
   *
   * @var StorageMetaSearch[]
   */
  private $cache = [];

  /**
   * @var int
   */
  private $iterator_cursor = 0;
  /**
   * @var array
   */
  private $iterator_key = [];

  /**
   * Caching flag
   *
   * @var bool
   */
  private $_caching = false;
  /**
   * Internal data storage
   *
   * @var array
   */
  private $_source = [];

  /**
   * @param array|object $source
   * @param bool         $caching
   *
   * @throws \TypeError Wrong type of source
   */
  public function __construct( $source, bool $caching = true ) {
    if( !Collection::is( $source ) ) throw new \TypeError( 'Storage can contain only array or object' );
    else {

      $this->_caching = $caching;
      $this->_source  = $source;
    }
  }

  /**
   * Create deep copy from the source and clears the cache
   */
  public function __clone() {

    $this->_source = Collection::copy( $this->_source );
    $this->cache   = [];
  }

  //
  public function get( string $index, $default = null ) {
    $tmp = $this[ $index ];
    return $tmp ?? $default;
  }
  //
  public function getString( string $index, ?string $default = '' ): ?string {
    $tmp = $this[ $index . static::SEPARATOR_TYPE . static::TYPE_STRING ];
    return $tmp ?? $default;
  }
  //
  public function getNumber( string $index, $default = 0 ) {
    $tmp = $this[ $index . static::SEPARATOR_TYPE . static::TYPE_NUMBER ];
    return $tmp ?? $default;
  }
  //
  public function getInteger( string $index, ?int $default = 0 ): ?int {
    $tmp = $this[ $index . static::SEPARATOR_TYPE . static::TYPE_INTEGER ];
    return $tmp ?? $default;
  }
  //
  public function getFloat( string $index, ?float $default = 0.0 ): ?float {
    $tmp = $this[ $index . static::SEPARATOR_TYPE . static::TYPE_FLOAT ];
    return $tmp ?? $default;
  }
  //
  public function getArray( string $index, ?array $default = [] ): ?array {
    $tmp = $this[ $index . static::SEPARATOR_TYPE . static::TYPE_ARRAY ];
    return $tmp ?? $default;
  }
  //
  public function getObject( string $index, $default = null ) {
    $tmp = $this[ $index . static::SEPARATOR_TYPE . static::TYPE_OBJECT ];
    return $tmp ?? $default;
  }
  //
  public function getBoolean( string $index, ?bool $default = false ): ?bool {
    $tmp = $this[ $index . static::SEPARATOR_TYPE . static::TYPE_BOOLEAN ];
    return $tmp ?? $default;
  }
  //
  public function getCallable( string $index, ?callable $default = null ): ?callable {
    $tmp = $this[ $index . static::SEPARATOR_TYPE . static::TYPE_CALLABLE ];
    return $tmp ?? $default;
  }
  //
  public function getPattern( string $index, $insertion, ?string $default = '' ): ?string {

    $tmp = $this->getString( $index, $default );
    return $tmp !== null ? Text::apply( $tmp, $insertion ) : $tmp;
  }

  /**
   * @param StorageMeta $meta
   * @param bool        $build   Build structure if not exist (when true, the third parameter can't be true)
   * @param bool        $is_read The search result will be used to read or write operation (don't use simple cache for write)
   *
   * @return StorageMetaSearch
   */
  protected function search( StorageMeta $meta, bool $build = false, bool $is_read = true ): StorageMetaSearch {

    // check the cache. Only load from cache if its getting ( read operation ) because if it's build then the returned value may changed outside
    if( $this->_caching && $is_read && isset( $this->cache[ $meta->id ] ) ) return $this->cache[ $meta->id ];
    else {

      $result = new StorageMetaSearch( $this->_source, $meta->token, $build );
      if( $this->_caching ) {
        $this->cache[ $meta->id ] = Collection::copy( $result, false );
      }

      return $result;
    }
  }
  /**
   * Clean the search cache based on the index
   *
   * @param string|StorageMeta|null $meta
   */
  protected function clean( $meta = null ) {

    //
    if( $meta && !( $meta instanceof StorageMeta ) ) {
      $meta = $this->getMeta( $meta );
    }

    // clear the cache
    if( !$meta || empty( $meta->token ) ) $this->cache = [];
    else foreach( $this->cache as $i => $_ ) {

      if( empty( $i ) || $i == $meta->token[ 0 ] || strpos( $i, $meta->token[ 0 ] ) === 0 ) {
        unset( $this->cache[ $i ] );
      }
    }
  }

  /**
   * @param string $index
   *
   * @return StorageMeta
   */
  private function getMeta( string $index ): StorageMeta {
    return StorageMeta::instance( $index );
  }

  //
  public function isCaching(): bool {
    return $this->_caching;
  }
  //
  public function setCaching( bool $value = true ) {
    $this->_caching = $value;
    $this->clean();
  }
  //
  public function getSource() {
    return $this->_source;
  }

  //
  public function offsetExists( $offset ) {
    return $this->search( $this->getMeta( $offset ) )->exist;
  }
  //
  public function offsetGet( $offset ) {

    // define the default result
    $tmp = $this->search( $index = $this->getMeta( $offset ) );
    if( !$tmp->exist ) $result = null;
    else if( $tmp->key === null ) $result = $tmp->container;
    else if( $tmp->container instanceof StorageInterface ) $result = $tmp->container[ $tmp->key . static::SEPARATOR_TYPE . $index->type ];
    else if( Collection::is( $tmp->container, false, true ) ) $result = $tmp->container[ $tmp->key ];
    else $result = $tmp->container->{$tmp->key};

    // switch result based on the type
    switch( $index->type ) {
      // force string type
      case static::TYPE_STRING:

        if( $tmp->exist && ( Text::is( $result ) ) ) $result = Text::read( $result );
        else $result = null;

        break;

      // force numeric type
      case 'num':
      case static::TYPE_NUMBER:

        $result = $tmp->exist ? Number::read( $result, null ) : null;
        break;

      // force integer type
      case 'int':
      case static::TYPE_INTEGER:

        $result = $tmp->exist && Number::is( $result ) ? ( (int) Number::read( $result ) ) : null;
        break;

      // force float type
      case 'double':
      case 'real':
      case static::TYPE_FLOAT:

        $result = $tmp->exist && Number::is( $result ) ? ( (float) Number::read( $result ) ) : null;
        break;

      // force array type
      case static::TYPE_ARRAY:

        $result = $tmp->exist ? Collection::read( $result, null ) : null;
        break;

      // force object type
      case static::TYPE_OBJECT:

        $result = $tmp->exist ? (object) Collection::read( $result, null ) : null;
        break;

      // force boolean type
      case 'bool':
      case static::TYPE_BOOLEAN:

        $result = $tmp->exist && ( is_bool( $result ) || in_array( $result, [ 1, 0, '1', '0' ], true ) ) ? (bool) $result : null;
        break;

      // force callable type
      case static::TYPE_CALLABLE:

        $result = $tmp->exist && is_callable( $result ) ? $result : null;
        break;
    }

    return $result;
  }
  //
  public function offsetSet( $offset, $value ) {

    $result = $this->search( $index = $this->getMeta( $offset ), true, false );
    if( $result->exist ) {

      if( $result->key === null ) $result->container = $value;
      else if( Collection::is( $result->container, false, true ) ) $result->container[ $result->key ] = $value;
      else $result->container->{$result->key} = $value;

      // clear the cache or the cache index
      if( $this->_caching ) $this->clean( $index );
    }
  }
  //
  public function offsetUnset( $offset ) {

    $result = $this->search( $index = $this->getMeta( $offset ), false, false );
    if( $result->exist ) {

      if( $result->key === null ) $result->container = [];
      else if( Collection::is( $result->container, false, true ) ) unset( $result->container[ $result->key ] );
      else unset( $result->container->{$result->key} );

      // clear the cache or the cache index
      if( $this->_caching ) $this->clean( $index );
    }
  }

  //
  public function current() {
    $tmp = $this->key();
    return is_object( $this->_source ) ? $this->_source->{$tmp} : $this->_source[ $tmp ];
  }
  //
  public function next() {
    ++$this->iterator_cursor;
  }
  //
  public function key() {
    return $this->iterator_key[ $this->iterator_cursor ];
  }
  //
  public function valid() {
    return isset( $this->iterator_key[ $this->iterator_cursor ] );
  }
  //
  public function rewind() {
    $this->iterator_cursor = 0;
    $this->iterator_key    = array_keys( is_object( $this->_source ) ? get_object_vars( $this->_source ) : $this->_source );
  }

  //
  public function count() {
    return count( $this->_source );
  }

  /**
   * @param array|object|StorageInterface $data
   *
   * @return StorageInterface
   * @throws \TypeError
   */
  public static function instance( $data ) {
    return $data instanceof StorageInterface ? $data : new static( $data );
  }
}

/**
 * Class StorageMeta
 */
class StorageMeta {

  /**
   * @var static[]
   */
  protected static $cache = [];

  /**
   * @var string
   */
  public $id = '';
  /**
   * @var string|null
   */
  public $namespace = null;
  /**
   * @var string|null
   */
  public $type;
  /**
   * @var array
   */
  public $token = [];

  /**
   * @param string $index
   */
  public function __construct( string $index ) {

    // trim separators from the index
    $index = trim( $index, " \t\n\r\0\x0B" . StorageInterface::SEPARATOR_KEY );
    $index = ltrim( $index, StorageInterface::SEPARATOR_NAMESPACE );
    $index = rtrim( $index, StorageInterface::SEPARATOR_TYPE );

    // process the index
    $buffer = '';
    for( $i = 0, $length = strlen( $index ); $i < $length; ++$i ) {
      switch( $index{$i} ) {

        // simple token separator
        case StorageInterface::SEPARATOR_KEY:
          $this->token[] = $buffer;
          $buffer        = '';
          break;

        // namespace definition
        case StorageInterface::SEPARATOR_NAMESPACE:
          if( $this->namespace === null ) {
            $this->namespace = $this->token[] = $buffer;
            $buffer          = '';
          }
          break;

        // type separator at the end
        case StorageInterface::SEPARATOR_TYPE:
          $this->type    = substr( $index, $i + 1 );
          $this->token[] = $buffer;
          $buffer        = '';
          break 2;

        default:

          $buffer .= $index{$i};
      }
    }
    if( strlen( $buffer ) > 0 ) $this->token[] = $buffer;

    // define the unique id for this index
    if( $this->namespace ) $this->id .= $this->namespace . StorageInterface::SEPARATOR_NAMESPACE;
    $this->id .= implode( StorageInterface::SEPARATOR_KEY, $this->namespace ? array_slice( $this->token, 1 ) : $this->token );
    if( $this->type ) $this->id .= StorageInterface::SEPARATOR_TYPE . $this->type;
  }

  /**
   * @param string $index
   *
   * @return static
   */
  public static function instance( string $index ) {
    return static::$cache[ $index ] ?? ( static::$cache[ $index ] = new static( $index ) );
  }
}
/**
 * Class StorageMetaSearch
 */
class StorageMetaSearch {

  /**
   * @var bool
   */
  public $exist = true;
  /**
   * @var string|null
   */
  public $key = null;
  /**
   * @var array|object
   */
  public $container;

  /**
   * @param array|object $source
   * @param array        $token
   * @param bool         $build
   */
  public function __construct( &$source, array $token, bool $build ) {

    $this->container = &$source;
    if( count( $token ) ) {

      $this->exist = false;
      for( $count = count( $token ), $i = 0; $i < $count - 1; ++$i ) {

        // check the container type
        if( !Collection::is( $this->container ) ) {

          if( $build ) $this->container = [];
          else return;
        }

        // handle new key check for two different data type
        $key = $token[ $i ];
        if( is_array( $this->container ) ) { // handle like an array

          if( !isset( $this->container[ $key ] ) ) {
            if( $build ) $this->container[ $key ] = [];
            else return;
          }

          // FIXME this reference will ruin your life! DO NOT USE IT! EVER!
          $this->container = &$this->container[ $key ];

        } else if( is_object( $this->container ) ) {   // handle like an object

          if( $this->container instanceof StorageInterface ) break;
          else {

            if( !isset( $this->container->{$key} ) ) {
              if( $build ) $this->container->{$key} = [];
              else return;
            }

            $this->container = &$this->container->{$key};
          }
        }
      }

      // select key if container exist
      $key = implode( StorageInterface::SEPARATOR_KEY, array_slice( $token, $i ) );
      if( Collection::is( $this->container ) ) {

        if( is_array( $this->container ) ) {
          if( !isset( $this->container[ $key ] ) ) {
            if( $build ) $this->container[ $key ] = null;
            else return;
          }
        } else if( !( $this->container instanceof StorageInterface ) ) {
          if( !isset( $this->container->{$key} ) ) {
            if( $build ) $this->container->{$key} = null;
            else return;
          }
        }

        // setup the result
        $this->key   = $key;
        $this->exist = true;
      }
    }
  }
}
