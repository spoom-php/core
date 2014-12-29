<?php namespace Engine\Storage;

use Engine\Utility\Library;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Simple
 * @package Engine\Storage
 *
 * @property bool     prefer         auto construct with object, instead of array
 * @property string   separator      index separator
 */
class Simple extends Library {

  /**
   * Cache for indexes
   *
   * @var array
   */
  private $cache = array();

  /**
   * Data storage for namespaces
   * @var array
   */
  protected $source = array();

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
        $this->cache = array();
        break;
      case 'prefer':
        $this->_prefer = $value == true;
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
    if( !isset( $value ) ) $value = $this->prefer ? new \stdClass() : array();

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

      if( !$result->key ) $result->container = array();
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
   * Get indexed value from the storage, or
   * the second value if index not exist
   *
   * @param string $index   - The wanted index
   * @param mixed  $if_null - The returned value if index not found
   *
   * @return mixed
   */
  public function get( $index, $if_null = null ) {

    $result = $this->search( $this->index( $index ) );
    if( $result->exist ) return $result->key ? ( is_array( $result->container ) ? $result->container[ $result->key ] : $result->container->{$result->key} ) : $result->container;
    else return $if_null;
  }
  /**
   * Get indexed (only string type) value from the stored namespaces, or
   * the second value if index not exist
   *
   * @param string $index   - The wanted index
   * @param mixed  $if_null - The returned value if index not found
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
   * @param string $index   - The wanted index
   * @param mixed  $if_null - The returned value if index not found
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
   * @param string $index   - The wanted index
   * @param mixed  $if_null - The returned value if index not found
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
   * @param string $index   - The wanted index
   * @param mixed  $if_null - The returned value if index not found
   *
   * @return object
   */
  public function geto( $index, $if_null = null ) {
    $value = $this->get( $index, $if_null );

    return is_object( $value ) || is_array( $value ) ? (object) $value : $if_null;
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

          if( $build ) $result->container = $this->_prefer ? new \stdClass() : array();
          else return $result;
        }

        // handle new key check for two different data type
        $key = $index->token[ $i ];
        if( is_array( $result->container ) ) { // handle like an array

          if( !isset( $result->container[ $key ] ) ) {
            if( $build ) $result->container[ $key ] = $this->_prefer ? new \stdClass() : array();
            else return $result;
          }

          $container = &$result->container[ $key ];

        } else if( is_object( $result->container ) ) {   // handle like an object

          if( !isset( $result->container->{$key} ) ) {
            if( $build ) $result->container->{$key} = $this->_prefer ? new \stdClass() : array();
            else return $result;
          }

          $container = &$result->container->{$key};
        }
      }
    }

    // select key if container exist
    if( isset( $result->container ) && ( is_array( $result->container ) || is_object( $result->container ) ) ) {

      $key = $result->token[ $count - 1 ];
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
      $result->container = &$container;
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

    $result = (object) array( 'id' => '', 'key' => '', 'token' => array() );
    if( !is_string( $index ) ) return null;
    else {

      // build the result
      $result->key   = $result->id = trim( $index, $this->separator );
      $result->token = $result->key == '' ? array() : explode( $this->separator, $result->key );

      return $result;
    }
  }
}