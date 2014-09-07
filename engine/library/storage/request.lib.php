<?php namespace Engine\Storage;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * @todo    implement set and get cookie method or event, for cookie security implementations
 *
 * Class Request
 * @package Engine\Storage
 */
class Request extends Simple {

  /**
   * @param string $namespace
   * @param int    $caching
   */
  public function __construct( $namespace = 'request', $caching = Simple::CACHE_SIMPLE ) {
    parent::__construct( $namespace, $caching );

    $this->separator = '>';
    $this->addr( $_REQUEST, 'request' );
    $this->addr( $_POST, 'post' );
    $this->addr( $_FILES, 'files' );
    $this->addr( $_GET, 'get' );
    $this->addr( $_COOKIE, 'cookie' );
    $this->addr( $_SERVER, 'server' );
  }

  /**
   * @todo implement setcookie with an event ( secure the cookie, or else )
   *
   * @param string      $index
   * @param mixed       $value
   * @param int|null    $expire
   * @param string|null $url
   */
  public function setc( $index, $value, $expire = null, $url = null ) {
    setcookie( $index, $value, $expire, $url );
  }
}