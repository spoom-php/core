<?php namespace Spoom\Core\Storage;

use Spoom\Core\Storage;
use Spoom\Core\StorageInterface;
use Spoom\Core\StorageMeta;
use Spoom\Core\StorageMetaSearch;

//
interface PersistentInterface extends StorageInterface {

  /**
   * Save (all) namespaces to the persistent storage
   *
   * This will save storage data associated with the namespaces to the persistent storage (disk, database, etc..)
   *
   * @param array|null $namespace_list
   *
   * @return static
   * @throws \LogicException Trying to save a readonly namespace
   */
  public function save( ?array $namespace_list = null );
  /**
   * Load (all) namespaces from the persistent storage
   *
   * This will load saved data (on disk, database, etc..) for associated namespaces into the storage
   *
   * @param array|null $namespace_list
   *
   * @return static
   */
  public function load( ?array $namespace_list = null );
  /**
   * Remove (all) namespaces from the persistent storage
   *
   * This will clear any saved data (on disk, database, etc..) associated with the namespaces, and clear the storage as well
   *
   * @param array|null $namespace_list
   *
   * @return static
   */
  public function remove( ?array $namespace_list = null );

  /**
   * Get (all) namespace(s) options
   *
   * @param null|string $name The namespace or NULL for all namespaces
   *
   * @return array Array of options or an array of [ namespace => options, ... ] arrays
   */
  public function getNamespace( ?string $name = null ): array;
  /**
   * Add, edit or remove namespace options
   *
   * @param string     $name The namespace
   * @param array|null $meta The new options for the namespace or NULL to remove the namespace completly
   *
   * @return static
   */
  public function setNamespace( string $name, ?array $meta = null );

  /**
   * Get the current enviroment
   *
   * @return null|string
   */
  public function getEnvironment(): ?string;
  /**
   * Set a new environment
   *
   * @param null|string $value The new environment or NULL to disable the feature
   * @param bool        $reset Reset the storage after the change, or leave the data intact
   *
   * @return static
   */
  public function setEnvironment( ?string $value, bool $reset = true );
}

//
abstract class Persistent extends Storage implements PersistentInterface {

  /**
   * Default options for namespaces
   */
  const NAMESPACE_DEFAULT = [
    'autoload' => true,
    'readonly' => false
  ];

  /**
   * This will prevent endless recursion with 'autoload' namespaces
   *
   * Array of namespaces which must be ignored on search operations to pervent endless recursion
   *
   * @var array
   */
  private $protect = [];

  /**
   * @var string|null
   */
  protected $_environment = null;
  /**
   * Namespaces options
   *
   * The key is the namespace and the value is the array of options
   *
   * @var array
   */
  protected $_namespace_list = [];

  /**
   * @param array|object $source         Inital data for the storage
   * @param array        $namespace_list List of namespaces options
   * @param bool         $caching        Use cacheing or not
   */
  public function __construct( $source, array $namespace_list = [], bool $caching = true ) {
    parent::__construct( $source, $caching );

    //
    foreach( $namespace_list as $namespace => $meta ) {
      $this->setNamespace( $namespace, $meta );
    }
  }

  /**
   * {@inheritDoc}
   *
   * Setup the autoloader for namespaces and prevent modification of the readonly namespaces
   *
   * @throws \LogicException Trying to change a readonly namespace
   */
  protected function search( StorageMeta $meta, bool $build = false, bool $is_read = true ): StorageMetaSearch {
    $namespace = $this->getNamespace( $meta->namespace );

    // prevent every write operation if the namespace is 'readonly'
    if( !$is_read && ( $namespace[ 'readonly' ] ?? false ) ) {
      throw new \LogicException( "Failed to edit the storage, the '{$meta->namespace}' is readonly" );
    }

    // try to load the storage data if there is no already
    if( ( $namespace[ 'autoload' ] ?? true ) && !in_array( $meta->namespace, $this->protect ) ) {
      $this->protect[] = $meta->namespace;

      if( !isset( $this[ $meta->namespace . static::SEPARATOR_NAMESPACE ] ) ) {
        $this->load( [ $meta->namespace ] );
      }

      // release protection
      array_pop( $this->protect );
    }

    // delegate problem to the parent
    return parent::search( $meta, $build, $is_read );
  }

  //
  public function getNamespace( ?string $name = null ): array {

    if( $name === null ) return $this->_namespace_list;
    else if( !array_key_exists( $name, $this->_namespace_list ) ) throw new \LogicException( "There is no '${name}' namespace in the Persistent storage" );
    else return $this->_namespace_list[ $name ];
  }
  //
  public function setNamespace( string $name, ?array $meta = null ) {
    if( $meta === null ) unset( $this->_namespace_list[ $name ] );
    else $this->_namespace_list[ $name ] = $meta + static::NAMESPACE_DEFAULT;

    return $this;
  }

  //
  public function getEnvironment(): ?string {
    return $this->_environment;
  }
  //
  public function setEnvironment( ?string $value, bool $reset = true ) {
    $this->_environment = $value;

    if( $reset ) $this->setSource( [] );
    return $this;
  }
}