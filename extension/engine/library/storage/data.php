<?php namespace Engine\Storage;

use Engine\Helper\Library;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Data
 * @package Engine\Storage
 *
 * @property bool     prefer         auto construct with object, instead of array
 * @property string   separator      index separator
 *
 * @method string|mixed gets( string $index, mixed $default = '' ) @depricated use getString or '!string' index postfix
 *         instead
 * @method number|mixed getn( string $index, mixed $default = 0 ) @depricated use getNumber or '!number' index postfix
 *         instead
 * @method array|mixed geta( string $index, mixed $default = [ ] ) @depricated use getArray or '!array' index postfix
 *         instead
 * @method object|mixed geto( string $index, mixed $default = null ) @depricated use getObject or '!object' index
 *         postfix instead
 */
class Data extends Library {

  /**
   * Cache for indexes
   *
   * @var array
   */
  private $cache = [ ];
  /**
   * Data storage for namespaces
   * @var array
   */
  protected $source = [ ];

  /**
   * Index token separator
   * @var string
   */
  private $_separator = '.';
  /**
   * Build object instead of array when non exist
   * @var bool
   */
  private $_prefer = true;

  public function __construct( $data = null ) {
    if( is_array( $data ) || is_object( $data ) ) $this->source = $data;
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
      case 'separator':
        $this->_separator = $value{0};

        // clear the caches
        $this->cache = [ ];
        break;
      case 'prefer':
        $this->_prefer = $value == true;
        break;
    }
  }
  /**
   * @param string $method
   * @param array  $args
   *
   * @return mixed
   */
  public function __call( $method, $args ) {

    $tmp = mb_strpos( $method, 'get' ) === 0 ? str_replace( 'get', '', $method ) : null;
    switch( $tmp ) {
      case 's':
        return $this->getString( $args[ 0 ], count( $args ) > 1 ? $args[ 1 ] : '' );
      case 'n':
        return $this->getNumber( $args[ 0 ], count( $args ) > 1 ? $args[ 1 ] : 0 );
      case 'a':
        return $this->getArray( $args[ 0 ], count( $args ) > 1 ? $args[ 1 ] : [ ] );
      case 'o':
        return $this->getObject( $args[ 0 ], count( $args ) > 1 ? $args[ 1 ] : null );
    }

    throw new \BadMethodCallException();
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

    // don't set the source attribute directly
    $result = $this->search( $this->index( $index ), true );
    if( $index && $result->key ) {

      if( is_array( $result->container ) ) $target = &$result->container[ $result->key ];
      else $target = &$result->container->{$result->key};

      $target = $value;
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
    $result = $this->search( $this->index( $index ), true );

    // set the value
    if( !$result->key ) $value = &$result->container;
    else if( is_array( $result->container ) ) $value = &$result->container[ $result->key ];
    else $value = &$result->container->{$result->key};

    // create extendable value if not exist
    if( !isset( $value ) ) $value = $this->prefer ? new \stdClass() : [ ];

    // extend arrays or objects with array or object
    if( ( is_array( $value ) || is_object( $value ) ) && ( is_array( $data ) || is_object( $data ) ) ) {

      if( is_array( $value ) ) $value = $recursive ? array_merge_recursive( $value, (array) $data ) : array_merge( $value, (array) $data );
      else if( is_object( $value ) ) $value = (object) $recursive ? array_merge_recursive( (array) $value, (array) $data ) : array_merge( (array) $value, (array) $data );

      // extend strings
    } else if( is_string( $value ) && is_string( $data ) ) $value .= $data;

    // extend numbers
    else if( is_numeric( $value ) && is_numeric( $data ) ) $value += $data;

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

    $result = $this->search( $this->index( $index ) );
    if( $result->exist ) {

      if( !$result->key ) $result->container = [ ];
      else if( is_array( $result->container ) ) unset( $result->container[ $result->key ] );
      else unset( $result->container->{$result->key} );
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
   * Get indexed value from the storage, or the second parameter if index not exist
   *
   * @param string $index   The index in the storage
   * @param mixed  $default The returned value if index not found
   *
   * @return mixed
   */
  public function get( $index, $default = null ) {

    $index = $this->index( $index );
    return $this->process( $index, $default, func_num_args() > 1 );
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
    $index->type = 'string';

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
    $index->type = 'number';

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
    $index->type = 'array';

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
    $index->type = 'object';

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
    $index->type = 'boolean';

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
    $index->type = 'callable';

    return $this->process( $index, $default );
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

    // if not index return the whole source
    $result = (object) array( 'exist' => true, 'key' => null, 'container' => &$this->source );
    if( !$index || !count( $index->token ) ) return $result;
    else {

      $result->exist = false;
      for( $count = count( $index->token ), $i = 0; $i < $count - 1; ++$i ) {

        // check actual container
        if( !is_array( $result->container ) && !is_object( $result->container ) ) {

          if( $build ) $result->container = $this->_prefer ? new \stdClass() : [ ];
          else return $result;
        }

        // handle new key check for two different data type
        $key = $index->token[ $i ];
        if( is_array( $result->container ) ) { // handle like an array

          if( !isset( $result->container[ $key ] ) ) {
            if( $build ) $result->container[ $key ] = $this->_prefer ? new \stdClass() : [ ];
            else return $result;
          }

          $result->container = &$result->container[ $key ];

        } else if( is_object( $result->container ) ) {   // handle like an object

          if( !isset( $result->container->{$key} ) ) {
            if( $build ) $result->container->{$key} = $this->_prefer ? new \stdClass() : [ ];
            else return $result;
          }

          $result->container = &$result->container->{$key};
        }
      }
    }

    // select key if container exist
    if( isset( $result->container ) && ( is_array( $result->container ) || is_object( $result->container ) ) ) {

      $key = $index->token[ $count - 1 ];
      if( is_array( $result->container ) ) {
        if( !isset( $result->container[ $key ] ) ) {
          if( $build ) $result->container[ $key ] = null;
          else return $result;
        }
      } else {
        if( !isset( $result->container->{$key} ) ) {
          if( $build ) $result->container->{$key} = null;
          else return $result;
        }
      }

      // setup the result
      $result->key   = $key;
      $result->exist = true;
    }

    return $result;
  }
  /**
   * @param $index
   *
   * @return object|null
   */
  protected function index( $index ) {

    if( is_string( $index ) && isset( $this->cache[ $index ] ) ) return $this->cache[ $index ];
    else {

      $result                = $this->parse( $index );
      $this->cache[ $index ] = &$result;

      return $result;
    }
  }
  /**
   * @param $index
   *
   * @return object|null
   */
  protected function parse( $index ) {

    // maki.lajos!string
    $result = (object) array( 'id' => '', 'key' => '', 'token' => [ ], 'type' => null );
    if( !is_string( $index ) ) return null;
    else {

      // normalize the index to id
      $tmp        = trim( $index, $this->separator . '!' );
      $result->id = $tmp;

      // define and parse key, type
      $tmp          = explode( '!', $tmp );
      $result->key  = $tmp[ 0 ];
      $result->type = empty( $tmp[ 1 ] ) ? null : $tmp[ 1 ];

      // explode key into tokens
      $result->token = empty( $result->key ) ? [ ] : explode( $this->separator, $result->key );

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
    if( $tmp->exist ) $result = $tmp->key ? ( is_array( $tmp->container ) ? $tmp->container[ $tmp->key ] : $tmp->container->{$tmp->key} ) : $tmp->container;
    else $result = $default;

    // switch result based on the type
    switch( $index->type ) {
      // force string type
      case 'string':

        $result = $tmp->exist && is_string( $result ) ? (string) $result : ( $use_default ? $default : '' );
        break;

      // force numeric type
      case 'number':

        $result = $tmp->exist && is_numeric( $result ) ? ( $result == (int) $result ? (int) $result : (float) $result ) : ( $use_default ? $default : 0 );
        break;

      // force array type
      case 'array':

        $result = $tmp->exist && ( is_array( $result ) || is_object( $result ) ) ? (array) $result : ( $use_default ? $default : [ ] );
        break;

      // force object type
      case 'object':

        $result = $tmp->exist && ( is_object( $result ) || is_array( $result ) ) ? (object) $result : ( $use_default ? $default : null );
        break;

      // force boolean type
      case 'boolean':

        $result = $tmp->exist && ( is_bool( $result ) || in_array( $result, [ 1, 0, '1', '0' ], true ) ) ? (bool) $result : ( $use_default ? $default : false );
        break;

      // force callable type
      case 'callable':

        $result = $tmp->exist && is_callable( $result ) ? $result : ( $use_default ? $default : null );
        break;
    }

    return $result;
  }
}