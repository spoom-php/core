<?php namespace Framework\Storage;

use Framework\Storage;

/**
 * Class Request
 *
 * @depricated Use one of the HTTP specialized extensions
 *
 * @package Framework\Storage
 *
 * @deprecated
 */
class Request extends Storage {

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
  public function __construct( $namespace = 'request', $caching = Storage::CACHE_NONE ) {
    parent::__construct( $namespace, null, $caching );

    $this->connect( $_REQUEST, self::NAMESPACE_REQUEST );
    $this->connect( $_POST, self::NAMESPACE_POST );
    $this->connect( $_FILES, self::NAMESPACE_FILES );
    $this->connect( $_GET, self::NAMESPACE_GET );
    $this->connect( $_COOKIE, self::NAMESPACE_COOKIE );
    $this->connect( $_SERVER, self::NAMESPACE_SERVER );
  }

  /**
   * Create, remove, update a cookie
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
