<?php namespace Spoom\Core\Storage;

use Spoom\Core\FileInterface;
use Spoom\Core\Helper\StreamInterface;

/**
 * @since 0.6.0
 */
class File extends Persistent {

  /**
   * Root directory of the storage
   *
   * Namespaces will be files in this directory, or subdirectory if there is any environment
   *
   * @var FileInterface
   */
  private $_directory;

  /**
   * Directory for the current environment
   *
   * @var FileInterface
   */
  private $path;

  /**
   * @param FileInterface $directory
   * @param array         $namespace_list
   *
   * @throws \LogicException $directory is not a valid directory
   */
  public function __construct( FileInterface $directory, array $namespace_list = [] ) {

    if( !$directory->isDirectory() ) throw new \LogicException( '$directory must be a valid directory' );
    else {

      $this->_directory = $this->path = $directory;

      parent::__construct( [], $namespace_list );
    }
  }

  //
  public function save( ?array $namespace_list = null ) {

    if( !($event = new PersistentEventIO( __FUNCTION__, $this, $namespace_list ))->isPrevented() ) {
      $namespace_list = $event->namespace_list ?? array_keys( $this->getNamespace() );

      // we have to check namespace saving pre-requirements before the actual saving loop to prevent (or at least minimalize) half written situations
      foreach( $namespace_list as $namespace ) {
        $_namespace = $this->getNamespace( $namespace );
        if( $_namespace[ 'readonly' ] ?? false ) throw new \LogicException( "Failed to save the storage, the '{$namespace}' is readonly" );
      }

      // actual saving loop
      foreach( $namespace_list as $namespace ) {

        $meta = $this->getNamespace( $namespace );
        $file = $this->file( $namespace );

        /** @var ConverterInterface $converter */
        $converter = $meta[ 'converter' ];
        $converter->serialize( $this[ $namespace . static::SEPARATOR_NAMESPACE ], $file->stream( StreamInterface::MODE_WT ) );
      }
    }

    return $this;
  }
  //
  public function load( ?array $namespace_list = null ) {

    if( !($event = new PersistentEventIO( __FUNCTION__, $this, $namespace_list ))->isPrevented() ) {
      $namespace_list = $event->namespace_list ?? array_keys( $this->getNamespace() );

      foreach( $namespace_list as $namespace ) {
        $meta = $this->getNamespace( $namespace );

        $file = $this->file( $namespace );
        if( $file->exist() ) {

          /** @var ConverterInterface $converter */
          $converter = $meta[ 'converter' ];
          $content   = $converter->unserialize( $file->stream()->read() );

          $this->setSource( $content, $namespace );
        }
      }
    }
    return $this;
  }
  //
  public function remove( ?array $namespace_list = null ) {

    if( !($event = new PersistentEventIO( __FUNCTION__, $this, $namespace_list ))->isPrevented() ) {
      $namespace_list = $event->namespace_list ?? array_keys( $this->getNamespace() );

      // we have to check namespace remove pre-requirements before the actual remove loop to prevent (or at least minimalize) half written situations
      foreach( $namespace_list as $namespace ) {
        $_namespace = $this->getNamespace( $namespace );
        if( $_namespace[ 'readonly' ] ?? false ) throw new \LogicException( "Failed to remove namespaces from the storage, the '{$namespace}' is readonly" );
      }

      foreach( $namespace_list as $namespace ) {

        $file = $this->file( $namespace );
        if( $file ) $file->remove();

        $this->setSource( null, $namespace );
      }
    }
    return $this;
  }

  /**
   * Get storage file for the given namespace
   *
   * @param string $namespace
   *
   * @return FileInterface
   * @throws \LogicException
   */
  protected function file( string $namespace ): FileInterface {

    $meta = $this->getNamespace( $namespace );
    return $this->path->get( $namespace . '.' . $meta[ 'format' ] );
  }

  /**
   * Directory for the current environment
   *
   * @return FileInterface
   */
  protected function getPath(): FileInterface {
    return $this->path;
  }

  /**
   * {@inheritDoc}
   *
   * Check environment named subdirectory existence before the change
   *
   * @param null|string $value The new environment's name
   * @param bool        $reset Clear the storage after a successful change or keep the data as is
   *
   * @return static
   * @throws \InvalidArgumentException Missing subdirectory
   */
  public function setEnvironment( ?string $value, bool $reset = true ) {

    //
    $directory = $this->getDirectory()->get( $value ?? '' );
    if( !$directory->exist( [ FileInterface::META_TYPE => FileInterface::TYPE_DIRECTORY ] ) ) {
      throw new \InvalidArgumentException( "Unable to set environment '{$value}', cause the directory '{$directory->getPath( true )}' doesn't exists" );
    }

    // change working directory and reset the storage
    $this->path = $directory;
    return parent::setEnvironment( $value, $reset );
  }

  /**
   * {@inheritDoc} Checks the meta for missing or invalid values
   *
   * @param string     $name
   * @param array|null $meta
   *
   * @return static
   * @throws \LogicException Missing 'converter' or 'format' option in the $meta
   */
  public function setNamespace( string $name, array $meta = null ) {

    // 'converter' enables text file conversion, 'format' is used to create files with the correct extension
    if( $meta !== null && ( empty( $meta[ 'converter' ] ) || empty( $meta[ 'format' ] ) ) ) {
      throw new \LogicException( "There must be a 'converter' and a 'format' in the namespace meta" );
    }

    return parent::setNamespace( $name, $meta );
  }

  /**
   * @return FileInterface
   */
  public function getDirectory(): FileInterface {
    return $this->_directory;
  }
  /**
   * @param FileInterface $value
   */
  public function setDirectory( FileInterface $value ) {
    $this->_directory = $value;
  }
}
