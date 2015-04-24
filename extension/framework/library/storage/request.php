<?php namespace Framework\Storage;

/**
 * Class Request
 *
 * @package Framework\Storage
 */
class Request extends Multi {

  /**
   * Namespace for $_REQUEST superglobal
   */
  const NAMESPACE_REQUEST = 'request';
  /**
   * Namespace for $_POST superglobal
   */
  const NAMESPACE_POST = 'post';
  /**
   * Namespace for $_FILES superglobal
   */
  const NAMESPACE_FILES = 'files';
  /**
   * Namespace for $_GET superglobal
   */
  const NAMESPACE_GET = 'get';
  /**
   * Namespace for $_COOKIE superglobal
   */
  const NAMESPACE_COOKIE = 'cookie';
  /**
   * Namespace for $_SERVER superglobal
   */
  const NAMESPACE_SERVER = 'server';

  /**
   * @param string    $namespace
   * @param int|mixed $caching
   */
  public function __construct( $namespace = 'request', $caching = Multi::CACHE_NONE ) {
    parent::__construct( $namespace, null, $caching );

    $this->addr( $_REQUEST, self::NAMESPACE_REQUEST );
    $this->addr( $_POST, self::NAMESPACE_POST );
    $this->addr( $_FILES, self::NAMESPACE_FILES );
    $this->addr( $_GET, self::NAMESPACE_GET );
    $this->addr( $_COOKIE, self::NAMESPACE_COOKIE );
    $this->addr( $_SERVER, self::NAMESPACE_SERVER );
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
