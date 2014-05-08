<?php namespace Engine\Request\Session;

use Engine\Utility\Storage\Simple;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Session
 * @package Engine\Storage
 */
class Storage extends Simple {

  /**
   * @param string $namespace
   * @param int    $caching
   */
  public function __construct( $namespace = 'session', $caching = Simple::CACHE_SIMPLE ) {
    $this->source = & $_SESSION;

    parent::__construct( $namespace, $caching );
  }
}