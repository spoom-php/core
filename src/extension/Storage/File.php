<?php namespace Spoom\Core\Storage;

use Spoom\Core\Application;
use Spoom\Core\Exception;
use Spoom\Core\ConverterInterface;
use Spoom\Core\FileInterface;
use Spoom\Core\Helper\StreamInterface;

/**
 * Class File
 *
 * @since   0.6.0
 *
 * TODO implement multi storage null namespace support (synced namespace save/load/remove)
 *
 * @property FileInterface $directory
 * @property string        $file
 * @property-read bool     $multi
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
      $previous      = $this->searchFile( $namespace, $previous_meta[ 'format' ] );

    } catch( \Exception $e ) {
      Exception::log( $e, Application::instance()->getLog() );
    }

    // do the saving like normal
    parent::save( $namespace, $format );
    if( !$this->getException() ) {

      // clean the previous file, if there is no need for it
      if( isset( $previous ) && isset( $previous_meta ) && $previous_meta[ 'format' ] != $this->converter_cache[ $namespace ][ 'format' ] ) try {

        $previous->remove();

      } catch( \Exception $e ) {
        $this->setException( $e );
      }
    }

    return $this;
  }

  //
  protected function write( string $content, ?string $namespace = null ) {
    $file = $this->searchFile( $namespace, $this->converter_cache[ $namespace ][ 'format' ] );
    $file->stream( StreamInterface::MODE_WRITE )->write( $content );
  }
  //
  protected function read( ?string $namespace = null ): ?string {

    $file = $this->searchFile( $namespace );
    if( !$file->exist() ) return null;
    else {

      $result = $file->stream()->read();

      $format                              = strtolower( pathinfo( $file->getPath(), PATHINFO_EXTENSION ) );
      $this->converter_cache[ $namespace ] = [ 'format' => $format, 'converter' => $this->converter_map[ $format ] ?? null ];

      return $result;
    }
  }
  //
  protected function delete( ?string $namespace = null ) {

    $file = $this->searchFile( $namespace );
    if( $file ) $file->remove();
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
      foreach( $this->_converter_map as $format => $converter ) {
        $format_list[] = $format;
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

  /**
   * @return string
   */
  public function getFile(): string {
    return $this->_file;
  }
  /**
   * @param string $value
   */
  public function setFile( string $value ) {
    $this->_file = $value;
  }
}
