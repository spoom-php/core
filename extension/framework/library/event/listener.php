<?php namespace Framework\Event;

use Framework;
use Framework\EventData;
use Framework\Exception;
use Framework\Helper\FeasibleInterface;
use Framework\Helper\Library;
use Framework\StorageInterface;

/**
 * Class Listener
 * @package Framework\Event
 *
 * @property string                $library Fully qualified class name or extension library index of the handler class
 * @property bool                  $enable  Allow execute
 * @property-read StorageInterface $data    Provided data on execute
 */
class Listener extends Library {

  /**
   * Library for the listener is missing
   */
  const EXCEPTION_MISSING_LIBRARY = 'framework#0E';
  /**
   * Library for the listener isn't implement the self::CLASS_LIBRARY
   */
  const EXCEPTION_INVALID_LIBRARY = 'framework#0E';

  /**
   * The listener instance MUST implement this interface
   */
  const CLASS_LIBRARY = '\Framework\Helper\FeasibleInterface';

  /**
   * Library instance cache
   *
   * @var FeasibleInterface[]
   */
  private static $instance;

  /**
   * Executable class "path"
   *
   * @var string
   */
  protected $_library;
  /**
   * Additional data for execute
   *
   * @var Framework\StorageInterface
   */
  protected $_data;
  /**
   * @var bool
   */
  protected $_enable;

  /**
   * @param string                           $library Fully qualified class name or extension library index of the handler class
   * @param array|Framework\StorageInterface $data    Provided data on execute
   * @param bool                             $enable  Allow execute or not
   *
   * @throws Exception\Strict
   */
  public function __construct( $library, $data = [ ], $enable = true ) {

    $this->setLibrary( $library );
    $this->_data   = $data instanceof Framework\StorageInterface ? $data : new Framework\Storage( $data );
    $this->_enable = (bool) $enable;
  }

  /**
   * @param EventData $data
   *
   * @return mixed
   */
  public function execute( EventData $data ) {

    if( !$this->isEnable() ) return null;
    else {

      // create library instance only once
      if( !isset( self::$instance[ $this->_library ] ) ) {

        $tmp                               = $this->_library;
        self::$instance[ $this->_library ] = new $tmp();
      }

      // execute the the library with the event data and internal data
      $event = $data->getEvent();
      return self::$instance[ $this->_library ]->execute( $event->namespace . '.' . $event->name, [ $data, $this->_data ] );
    }
  }

  /**
   * @return string
   */
  public function getLibrary() {
    return $this->_library;

  }
  /**
   * @param string $value
   *
   * @throws Exception
   */
  public function setLibrary( $value ) {

    $value = \Framework::library( $value );
    if( !isset( $value ) ) throw new Exception\Strict( self::EXCEPTION_MISSING_LIBRARY, [ 'value' => $value ] );
    else if( !is_subclass_of( $value, self::CLASS_LIBRARY ) ) throw new Exception\Strict( self::EXCEPTION_INVALID_LIBRARY, [ 'value' => $value ] );
    else $this->_library = $value;
  }
  /**
   * @return bool
   */
  public function isEnable() {
    return $this->_enable;
  }
  /**
   * @param bool $enable
   */
  public function setEnable( $enable ) {
    $this->_enable = (bool) $enable;
  }
  /**
   * @return StorageInterface
   */
  public function getData() {
    return $this->_data;
  }
}
