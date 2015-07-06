<?php namespace Framework\Extension;

use Framework\Extension;
use Framework\Request;
use Framework\Storage;

/**
 * Class Localization
 * @package Framework\Extension
 *
 * @property-read Extension $extension
 * @property      string    $localization
 */
class Localization extends Storage\File {

  /**
   * Extension data source
   *
   * @var Extension
   */
  private $_extension = null;

  /**
   * Currently loaded localization
   *
   * @var string
   */
  protected $_localization;

  /**
   * Set defaults
   *
   * @param Extension $source
   */
  public function __construct( Extension $source ) {
    parent::__construct( $source->directory( '' ) . Extension::DIRECTORY_LOCALIZATION );

    $this->_extension = $source;
  }

  /**
   * @param $index
   *
   * @return Extension|string|null
   */
  public function __get( $index ) {

    if( $index == 'extension' ) return $this->_extension;
    else if( $index == 'localization' ) {

      if( !isset( $this->_localization ) ) $this->localization = Request::getLocalization();
      return $this->_localization;
    }

    return parent::__get( $index );
  }
  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return $index == 'extension' || $index == 'localization' || parent::__isset( $index );
  }
  /**
   * Dynamic setter for privates
   *
   * @param string $index
   * @param mixed  $value
   */
  public function __set( $index, $value ) {
    switch( $index ) {
      case 'localization':

        // save the original localization for later compare
        $tmp = $this->_localization;

        // set the new localization
        $global = Request::getLocalization();
        if( $this->validate( $value ) ) $this->_localization = $value;
        else if( $global != $value && $this->validate( $global ) ) $this->_localization = $global;
        else if( $this->validate( $this->_extension->manifest->getString( 'localization' ) ) ) {
          $this->_localization = $this->_extension->manifest->getString( 'localization' );
        }

        // clear meta/cache/storage when the localization has changed
        if( $this->_localization != $tmp ) {

          $this->_source = [ ];
          $this->meta    = [ ];
          $this->clean();
        }

        // log: debug
        Request::getLog()->debug( 'The \'{localization}\' localization selected', [
          'localization' => $this->_localization,
          'directory' => $this->_path
        ], '\Framework\Extension\Localization' );

        break;
      default:
        parent::__set( $index, $value );
    }
  }

  /**
   * Check if the given localization name directory exists
   *
   * @param string $name The localization name to check
   *
   * @return bool
   */
  protected function validate( $name ) {
    return is_string( $name ) && is_dir( _PATH_BASE . $this->_path . $name . '/' );
  }
  /**
   * @param string      $namespace
   * @param string|null $format
   * @param bool        $exist
   *
   * @return mixed
   */
  protected function getFile( $namespace, $format = null, &$exist = false ) {

    // define the localization of not already
    if( !isset( $this->_localization ) ) $this->localization = Request::getLocalization();

    // change the directory temporary then search for the path
    $tmp = $this->_path;
    $this->_path .= $this->_localization . '/';

    $result      = parent::getFile( $namespace, $format, $exist );
    $this->_path = $tmp;

    return $result;
  }
}
