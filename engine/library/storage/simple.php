<?php namespace Engine\Storage;

use Engine\Utility\Library;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Simple
 * @package Engine\Storage
 *
 * @property string   namespace      default namespace
 * @property bool     caching        enable or disable cache
 * @property bool     prefer_object  auto construct with object, instead of array
 * @property string   separator      index separator
 */
class Simple extends Library {

  const CACHE_NONE = 0;
  const CACHE_SIMPLE = 1;
  const CACHE_REFERENCE = 2;

  /**
   * Cache data storage
   * @var array
   */
  private $cache_source = array();

  /**
   * Cache for index parser
   *
   * @var array
   */
  private $cache_parse = array();

  /**
   * Data storage for namespaces
   * @var array
   */
  protected $source = array();

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
   * Index token separator
   * @var string
   */
  private $_separator = '.';

  /**
   * Build object instead of array when non exist
   * @var bool
   */
  private $_prefer_object = true;

  /**
   * Set default or given values
   *
   * @param string $namespace
   * @param int $caching
   */
  public function __construct( $namespace = 'default', $caching = self::CACHE_SIMPLE ) {
    $this->namespace = $namespace;
    $this->caching = $caching;
  }

  /**
   * @param $index
   *
   * @return mixed
   */
  public function __get( $index ) {
    $i = '_' . $index;
    if( property_exists( $this, $i ) ) return $this->{$i};
    else return parent::__get( $index );
  }

  /**
   * Dynamic setter for privates
   *
   * @param string $index
   * @param mixed $value
   */
  public function __set( $index, $value ) {
    switch( $index ) {
      case 'namespace':
        $this->_namespace = '' . $value;

        // clear the parse cache
        $this->cache_parse = array();
        break;
      case 'cache_type':
        $this->_caching = (int) $value;

        // clear the source cache
        $this->cache_source = array();
        break;
      case 'separator':
        $this->_separator = $value{0};

        // clear the caches
        $this->cache_parse = array();
        $this->cache_source = array();
        break;
      case 'prefer_object':
        $this->_prefer_object = $value == true;
        break;
    }
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
   * Convert one or more namespaces to one object or array.
   * In fact, it is a getter for the namespaces.
   *
   * @param mixed $namespaces - The name ( or array of names ) of the namespace or false, if want all namespace
   * @param bool $force_object - Return object instead of array
   *
   * @return mixed
   */
  public function convert( $namespaces = false, $force_object = false ) {
    if( $namespaces === false ) return $force_object ? (object) $this->source : $this->source;

    $namespaces = is_array( $namespaces ) ? $namespaces : array( $namespaces );
    $result = array();
    foreach( $namespaces as $n ) $result[ $n ] = $this->exist( $n . ':' ) ? $this->source[ $n ] : array();

    return $force_object ? (object) $result : $result;
  }

  /**
   * Extend index with data. If index and data is enumerable ( array or object )
   * it will be merge index with the data. If index and data is string, the index
   * concated with data. Finally if the index and the data is numeric, the data
   * will be added to the index.
   *
   * @param string $index
   * @param mixed $data
   * @param bool $recursive
   *
   * @return $this
   */
  public function extend( $index, $data, $recursive = false ) {
    $index = $this->parse( $index );
    $result = $this->search( $index, true );

    // set the value
    if( !$result->key ) $value = &$result->container;
    else if( is_array( $result->container ) ) $value = &$result->container[ $result->key ];
    else $value = &$result->container->{$result->key};

    // create extendable value if not exist
    if( !isset( $value ) ) $value = $this->prefer_object ? new \stdClass() : array();

    // extend arrays or objects with array or object
    if( ( is_array( $value ) || is_object( $value ) ) && ( is_array( $data ) || is_object( $data ) ) ) {

      if( is_array( $value ) ) $value = $recursive ? array_merge_recursive( $value, (array) $data ) : array_merge( $value, (array) $data );
      else if( is_object( $value ) ) $value = (object) $recursive ? array_merge_recursive( (array) $value, (array) $data ) : array_merge( (array) $value, (array) $data );

      // extend strings
    } else if( is_string( $value ) && is_string( $data ) ) $value .= $data;

    // extend numbers
    else if( is_numeric( $value ) && is_numeric( $data ) ) $value += $data;

    // clear the cache or the cache index
    if( $this->_caching == self::CACHE_SIMPLE ) {
      if( $recursive ) $this->cache_source = array();
      else unset( $this->cache_source[ $index->string ] );
    }

    return $this;
  }

  /**
   * Iterate through an index with given function.
   * The function get value, index, this params
   * each iteration.
   * The function parameters are: key, value, index, self
   *
   *
   * @param callable $function
   * @param string $index
   *
   * @return $this
   */
  public function each( $function, $index = null ) {

    // first check the function type
    if( is_callable( $function ) ) {

      $index = $this->parse( $index );
      $result = $this->search( $index );

      // check result existance
      if( $result->exist ) {

        // find the value
        if( !$result->key ) $value = &$result->container;
        else if( is_array( $result->container ) ) $value = &$result->container[ $result->key ];
        else $value = &$result->container->{$result->key};

        // check the value type
        if( is_array( $value ) || is_object( $value ) ) {

          foreach( $value as $key => $data ) {
            $full_key = trim( $index->key ) == '' ? ( $index->string . $key ) : ( $index->string . $this->separator . $key );
            $result = $function( $key, $data, !$index ? $key : $full_key, $this );

            if( $result === false ) break;
          }
        }
      }
    }

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
  public function remove( $index ) {
    $index = $this->parse( $index );
    $result = $this->search( $index );

    if( $result->exist ) {

      if( !$result->key ) $result->container = array();
      else if( is_array( $result->container ) ) unset( $result->container[ $result->key ] );
      else unset( $result->container->{$result->key} );

      // clear the cache or the cache index
      if( $this->_caching != self::CACHE_NONE ) $this->cache_source = array();
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
    $index = $this->parse( $index );

    return $this->search( $index )->exist;
  }

  /**
   * Get indexed value from the storage, or
   * the second value if index not exist
   *
   * @param string $index - The wanted index
   * @param mixed $if_null - The returned value if index not found
   *
   * @return mixed
   */
  public function get( $index, $if_null = null ) {
    $index = $this->parse( $index );
    $result = $this->search( $index );
    $return = $if_null;

    if( $result->exist ) {
      $return = $result->key ? ( is_array( $result->container ) ? $result->container[ $result->key ] : $result->container->{$result->key} ) : $result->container;
    }

    return $return;
  }

  /**
   * Get indexed (only string type) value from the stored namespaces, or
   * the second value if index not exist
   *
   * @param string $index - The wanted index
   * @param mixed $if_null - The returned value if index not found
   *
   * @return string
   */
  public function gets( $index, $if_null = '' ) {
    $value = $this->get( $index, $if_null );

    return is_string( $value ) ? (string) $value : $if_null;
  }

  /**
   * Get indexed (only numeric type) value from the stored namespaces, or
   * the second value if index not exist
   *
   * @param string $index - The wanted index
   * @param mixed $if_null - The returned value if index not found
   *
   * @return number
   */
  public function getn( $index, $if_null = 0 ) {
    $value = $this->get( $index, $if_null );

    return is_numeric( $value ) ? (string) $value : $if_null;
  }

  /**
   * Get indexed (only array type) value from the stored namespaces, or
   * the second value if index not exist
   *
   * @param string $index - The wanted index
   * @param mixed $if_null - The returned value if index not found
   *
   * @return array
   */
  public function geta( $index, $if_null = array() ) {
    $value = $this->get( $index, $if_null );

    return is_array( $value ) || is_object( $value ) ? (array) $value : $if_null;
  }

  /**
   * Get indexed (only object type) value from the stored namespaces, or
   * the second value if index not exist
   *
   * @param string $index - The wanted index
   * @param mixed $if_null - The returned value if index not found
   *
   * @return object
   */
  public function geto( $index, $if_null = null ) {
    $value = $this->get( $index, $if_null );

    return is_object( $value ) || is_array( $value ) ? (object) $value : $if_null;
  }

  /**
   * Set the index to value and create structure for the index
   * if it's not exist already
   *
   * @param string $index
   * @param mixed $value
   *
   * @return $this
   */
  public function set( $index, $value ) {
    $index = $this->parse( $index );
    $result = $this->search( $index, true );

    // don't set the source attribute directly
    if( $index && $result->key ) {
      if( is_array( $result->container ) ) $target = &$result->container[ $result->key ];
      else $target = &$result->container->{$result->key};

      $target = $value;

      // clear the cache index
      if( $this->_caching == self::CACHE_SIMPLE ) unset( $this->cache_source[ $index->string ] );
    }

    return $this;
  }

  /**
   * Add a namespace to the source as reference
   *
   * @param mixed $object_or_array - the object or array to add
   * @param string $namespace - the namespace to set
   *
   * @return $this
   */
  protected function addr( &$object_or_array, $namespace = null ) {
    if( !is_string( $namespace ) ) $namespace = $this->_namespace;

    if( is_array( $object_or_array ) || is_object( $object_or_array ) ) {
      $this->source[ $namespace ] = &$object_or_array;

      // clear the cache or the cache index
      if( $this->_caching != self::CACHE_NONE ) $this->cache_source = array();
    }

    return $this;
  }

  /**
   * Add a namespace to the source
   *
   * @param mixed $object_or_array - the object or array to add
   * @param string $namespace - the namespace to set
   *
   * @return $this
   */
  protected function add( $object_or_array, $namespace = null ) {
    if( is_object( $object_or_array ) ) $object_or_array = clone $object_or_array;

    return $this->addr( $object_or_array, $namespace );
  }

  /**
   * Search for the index pointed value, and return the
   * result in a { exist, container, key }
   * like object. If the index was false, the key will be null. Otherwise the key always
   * set.
   *
   * @param \stdClass $index - the Simple::parse method result
   * @param bool $build - build structure if not exist
   *
   * @return object
   */
  protected function search( $index, $build = false ) {

    // preparing result object
    $result = new \stdClass();
    $result->exist = false;
    $result->key = null;
    $result->container = null;

    // if not index return the whole source
    if( !$index ) {
      $result->exist = true;
      $result->container = &$this->source;

      return $result;
    }

    // check the cache. Only load from cache if its getting ( not build ) or if the cache is referenced
    // because if it's build then the returned value may changed outside
    if( $this->_caching != self::CACHE_NONE && ( !$build || $this->_caching == self::CACHE_REFERENCE ) && isset( $this->cache_source[ $index->string ] ) ) {
      $result->exist = true;
      $result->container = &$this->cache_source[ $index->string ][ 'container' ];
      $result->key = $this->cache_source[ $index->string ][ 'key' ];

      return $result;
    }

    // preparing inputs and working variables
    $tokens = array( $index->namespace );
    $tokens = array_merge( $tokens, $index->tokens );

    $count = count( $tokens );
    $container = &$this->source;

    // iterate trough the tokens
    for( $i = 0; $i < $count - 1; ++$i ) {
      $key = $tokens[ $i ];

      // check actual container
      if( !is_array( $container ) && !is_object( $container ) ) {
        if( $build ) $container = $this->_prefer_object ? new \stdClass() : array();
        else return $result;
      }

      // handle like an array
      if( is_array( $container ) ) {

        if( !isset( $container[ $key ] ) ) {
          if( $build ) $container[ $key ] = $this->_prefer_object ? new \stdClass() : array();
          else return $result;
        }

        $container = &$container[ $key ];
        continue;
      }

      // handle like an object
      if( is_object( $container ) ) {

        if( !isset( $container->{$key} ) ) {
          if( $build ) $container->{$key} = $this->_prefer_object ? new \stdClass() : array();
          else return $result;
        }

        $container = &$container->{$key};
        continue;
      }
    }

    // select key if container exist
    if( isset( $container ) && ( is_array( $container ) || is_object( $container ) ) ) {

      $key = $tokens[ $count - 1 ];

      if( is_array( $container ) ) {
        if( !isset( $container[ $key ] ) ) {
          if( $build ) $container[ $key ] = null;
          else return $result;
        }
      } else {
        if( !isset( $container->{$key} ) ) {
          if( $build ) $container->{$key} = null;
          else return $result;
        }
      }

      // setup the result
      $result->key = $key;
      $result->container = &$container;
      $result->exist = true;

      // save to cache
      if( $this->_caching != self::CACHE_NONE ) {
        switch( $this->_caching ) {
          case self::CACHE_SIMPLE:
            $this->cache_source[ $index->string ] = array(
                'container' => $result->container,
                'key' => $result->key
            );

            break;

          case self::CACHE_REFERENCE:
            $this->cache_source[ $index->string ] = array(
                'container' => &$result->container,
                'key' => $result->key
            );

            break;
        }
      }
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
    $result = (object) array( 'string' => '', 'key' => '', 'namespace' => $this->namespace, 'tokens' => array() );

    if( is_string( $index ) ) {

      // read parsed index from cache
      if( isset( $this->cache_parse[ $index ] ) ) return $this->cache_parse[ $index ];

      // explode index by namespace separator (:)
      $splited = explode( ':', $index, 2 );
      $count = count( $splited );

      // build the result
      $result->namespace = $count == 2 ? $splited[ 0 ] : $this->namespace;
      $result->key = trim( $count == 2 ? $splited[ 1 ] : $splited[ 0 ], ' :' . $this->separator );
      $result->tokens = $result->key == '' ? array() : explode( $this->separator, $result->key );
      $result->string = $result->namespace . ':' . $result->key;

      // build cache with reference
      $this->cache_parse[ $index ] = &$result;

      return $result;
    }

    return false;
  }
}