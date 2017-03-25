<?php namespace Spoom\Framework\Storage;

use Spoom\Framework\Application;
use Spoom\Framework\Exception;
use Spoom\Framework\ConverterInterface;
use Spoom\Framework\FileInterface;

/**
 * Class File
 * @package Framework\Storage
 *
 * @since   0.6.0
 *
 * TODO implement multi storage null namespace support (synced namespace save/load/remove)
 *
 * @property FileInterface $directory
 * @property-read string   $file
 */
class File extends Permanent {

  /**
   * Root directory source
   *
   * @var FileInterface
   */
  private $_directory;

  /**
   * File name in the directory (if any)
   *
   * @var string
   */
  private $_file;

  /**
   * @param FileInterface        $directory  Root directory for the storage
   * @param ConverterInterface[] $converters Default converters for the permanent storage. The first converter will be the default format
   * @param string|null          $file       Single file storage in the root directory
   */
  public function __construct( FileInterface $directory, array $converters = [], ?string $file = null ) {
    $this->_directory = $directory;
    $this->_file      = $file;

    parent::__construct( [], false, $converters );
  }

  //
  public function save( ?string $namespace = null, ?string $format = null ) {

    // save previous file data for later
    if( isset( $this->converter_cache[ $namespace ] ) ) try {

      $previous_meta = $this->converter_cache[ $namespace ];
      $previous      = $this->searchFile( $namespace, $previous_meta->getFormat() );

    } catch( \Exception $e ) {
      Exception::log( $e, Application::instance()->getLog() );
    }

    // do the saving like normal
    parent::save( $namespace, $format );
    if( !$this->getException() ) {

      // clean the previous file, if there is no need for it
      if( isset( $previous ) && isset( $previous_meta ) && $previous_meta != $this->converter_cache[ $namespace ] ) try {

        $previous->destroy();

      } catch( \Exception $e ) {
        $this->setException( $e );
      }
    }

    return $this;
  }

  //
  protected function write( string $content, ?string $namespace = null ) {
    $file = $this->searchFile( $namespace, $this->converter_cache[ $namespace ]->getFormat() );
    $file->write( $content, false );
  }
  //
  protected function read( ?string $namespace = null ): ?string {

    $file = $this->searchFile( $namespace );
    if( !$file->exist() ) return null;
    else {

      $result                              = $file->read();
      $this->converter_cache[ $namespace ] = $this->getConverterMap()->get( strtolower( pathinfo( $file->getPath(), PATHINFO_EXTENSION ) ) );

      return $result;
    }
  }
  //
  protected function destroy( ?string $namespace = null ) {

    $file = $this->searchFile( $namespace );
    if( $file ) $file->destroy();
  }

  /**
   * Get file by namespace and format
   *
   * @param string|null $namespace The namespace
   * @param string|null $format    Force extension for the file
   *
   * @return FileInterface The file MAY not exists
   * @throws \InvalidArgumentException Empty namespace with multi storage
   */
  protected function searchFile( ?string $namespace, ?string $format = null ): FileInterface {

    // define and check the namespace
    $namespace = !$this->isMulti() ? $this->_file : $namespace;
    if( $namespace === null ) throw new \InvalidArgumentException( 'Namespace cannot be NULL' );

    // collect available formats
    if( !empty( $format ) ) $format_list = [ $format ];
    else {

      $format_list = [];
      foreach( $this->_converter_map->get() as $converter ) {
        $format_list[] = $converter->getFormat();
      }
    }

    // search for the file in the directory
    $file = $this->_directory->search( '/' . $namespace . '\.(' . implode( '|', $format_list ) . ')$/i' );
    return !empty( $file ) ? $file[ 0 ] : $this->_directory->get( $namespace . '.' . ( $format ?: $this->getFormat() ) );
  }

  /**
   * Use namespaces or not (directory or simple file storage)
   *
   * @return bool
   */
  public function isMulti(): bool {
    return empty( $this->_file );
  }
  /**
   * @since ???
   *
   * @return FileInterface
   */
  public function getDirectory(): FileInterface {
    return $this->_directory;
  }
  /**
   * @since ???
   *
   * @param FileInterface $value
   */
  public function setDirectory( FileInterface $value ) {
    $this->_directory = $value;
  }

  /**
   * @since ???
   *
   * @return string
   */
  public function getFile(): string {
    return $this->_file;
  }
  /**
   * @since ???
   *
   * @param string $value
   */
  public function setFile( string $value ) {
    $this->_file = $value;
  }
}
