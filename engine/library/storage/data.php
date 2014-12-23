<?php namespace Engine\Storage;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Data
 * @package Engine\Storage
 */
class Data extends Simple {

  /**
   * @param string $data
   * @param string $namespace
   * @param int    $caching
   */
  public function __construct( $data, $namespace = 'default', $caching = Simple::CACHE_SIMPLE ) {
    parent::__construct( $namespace, $caching );
    $this->add( $data );
  }

  /**
   * Add a namespace to the source
   *
   * @param mixed  $object_or_array - the object or array to add
   * @param string $namespace       - the filled namespace
   *
   * @return $this
   */
  public function add( $object_or_array, $namespace = null ) {
    return parent::add( $object_or_array, $namespace );
  }

  /**
   * Add a namespace to the source as reference
   *
   * @param mixed  $object_or_array - the object or array to add
   * @param string $namespace       - the filled namespace
   *
   * @return $this
   */
  public function addr( &$object_or_array, $namespace = null ) {
    return parent::addr( $object_or_array, $namespace );
  }
}