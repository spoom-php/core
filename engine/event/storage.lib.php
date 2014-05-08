<?php namespace Engine\Event;

use Engine\Utility\Storage\File;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Storage
 * @package Engine\Events
 */
class Storage extends File {

  public function __construct() {
    parent::__construct( _PATH . _PATH_ENGINE . 'event/configuration/' );

    $this->separator = '-';
  }
}