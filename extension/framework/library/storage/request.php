<?php namespace Framework\Storage;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Request
 *
 * @package Framework\Storage
 */
class Request extends Multi {

  /**
   * @param string    $namespace
   * @param int|mixed $caching
   */
  public function __construct( $namespace = 'request', $caching = Multi::CACHE_NONE ) {
    parent::__construct( $namespace, null, $caching );

    $this->addr( $_REQUEST, 'request' );
    $this->addr( $_POST, 'post' );
    $this->addr( $_FILES, 'files' );
    $this->addr( $_GET, 'get' );
    $this->addr( $_COOKIE, 'cookie' );
    $this->addr( $_SERVER, 'server' );
  }

  /**
   * Create, remove, update a cookie
   *
   * TODO implement setcookie with an event ( secure the cookie, or else )
   *
   * @param string      $index
   * @param mixed       $value
   * @param int|null    $expire
   * @param string|null $url
   */
  public function setCookie( $index, $value, $expire = null, $url = null ) {
    setcookie( $index, $value, $expire, $url );
  }
}
