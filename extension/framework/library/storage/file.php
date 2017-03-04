<?php namespace Framework\Storage;

use Framework\Exception;
use Framework\ConverterInterface;
use Framework\FileInterface;

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
  public function __construct( FileInterface $directory, $converters = [], $file = null ) {
    $this->_directory = $directory;
    $this->_file      = $file;

    parent::__construct( null, $this->isMulti() ? 'default' : null, self::CACHE_NONE, $converters );
  }

  //
  public function save( $namespace = null, $format = null ) {

    // save previous file data for later
    if( isset( $this->converter_cache[ $namespace ] ) ) try {

      $previous_meta = $this->converter_cache[ $namespace ];
      $previous      = $this->searchFile( $namespace, $previous_meta->getFormat() );

    } catch( \Exception $e ) {
      Exception::wrap( $e )->log();
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
  protected function write( $content, $namespace = null ) {
    $file = $this->searchFile( $namespace, $this->converter_cache[ $namespace ]->getFormat() );
    $file->write( $content, false );
  }
  //
  protected function read( $namespace = null ) {

    $file = $this->searchFile( $namespace );
    if( !$file->exist() ) return null;
    else {

      $result                              = $file->read();
      $this->converter_cache[ $namespace ] = $this->getConverter()->get( strtolower( pathinfo( $file->getPath(), PATHINFO_EXTENSION ) ) );

      return $result;
    }
  }
  //
  protected function destroy( $namespace = null ) {

    $file = $this->searchFile( $namespace );
    if( $file ) $file->destroy();
  }

  /**
   * Get file by namespace and format
   *
   * @param string      $namespace The namespace
   * @param string|null $format    Force extension for the file
   *
   * @return FileInterface The file MAY not exists
   * @throws \InvalidArgumentException Empty namespace with multi storage
   */
  protected function searchFile( $namespace, $format = null ) {

    // define and check the namespace
    $namespace = !$this->isMulti() ? $this->_file : $namespace;
    if( $namespace === null ) throw new \InvalidArgumentException( 'Namespace cannot be NULL' );

    // collect available formats
    if( !empty( $format ) ) $format_list = [ $format ];
    else {

      $format_list = [];
      foreach( $this->_converter->get() as $converter ) {
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
  public function isMulti() {
    return empty( $this->_file );
  }
  /**
   * @since ???
   *
   * @return FileInterface
   */
  public function getDirectory() {
    return $this->_directory;
  }
  /**
   * @since ???
   *
   * @param FileInterface $value
   */
  public function setDirectory( $value ) {
    $this->_directory = $value;
  }

  /**
   * @since ???
   *
   * @return string
   */
  public function getFile() {
    return $this->_file;
  }
  /**
   * @since ???
   *
   * @param string $value
   */
  public function setFile( $value ) {
    $this->_file = (string) $value;
  }
}
