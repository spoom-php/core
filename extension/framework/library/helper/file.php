<?php namespace Framework\Helper;

/**
 * Class File
 * @package Framework\Helper
 */
abstract class File {

  /**
   * Get file list from a directory. It can filter the files  based on a regexp
   *
   * @param string      $directory the directory to search
   * @param bool        $fullpath  if true the directory path prepended to files
   * @param null|string $regexp    filter files by regexp
   *
   * TODO use glob instead of regexp
   *
   * @return array the array of files
   */
  public static function getList( $directory, $fullpath = false, $regexp = null ) {

    // check
    if( !is_dir( $directory ) ) return [ ];

    $files = [ ];
    $directory_stream = opendir( $directory );
    while( $file = readdir( $directory_stream ) ) {
      if( is_dir( $directory . $file ) || $file == '.' || $file == '..' || ( is_string( $regexp ) && !preg_match( $regexp, $file ) ) ) continue;
      $files[ ] = $fullpath ? $directory . $file : $file;
    }

    return $files;
  }

  /**
   * Remove the given path recursively
   *
   * @param $path
   *
   * @return bool
   */
  public static function remove( $path ) {

    if( is_file( $path ) ) return @unlink( $path );
    else if( is_dir( $path ) ) {

      $objects = scandir( $path );
      foreach( $objects as $object ) if( $object != '.' && $object != '..' ) {
        File::remove( $path . '/' . $object );
      }

      reset( $objects );
      return rmdir( $path );
    }

    return false;
  }
}
