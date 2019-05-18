<?php namespace Spoom\Core;

use Spoom\Composer\Autoload;
use Spoom\Core\Helper;
use Spoom\Core\Helper\AccessableInterface;
use Spoom\Core\Helper\Text;
use Spoom\Core\Logger;
use Spoom\Core\LoggerInterface;

class Environment implements AccessableInterface {
  use Helper\Accessable;

  /**
   * Production environment
   */
  const PRODUCTION = 'production';
  /**
   * Pre-production environment
   */
  const STAGING = 'staging';
  /**
   * Test environment
   */
  const TEST = 'test';
  /**
   * Developer's environment
   */
  const DEVELOPMENT = 'development';

  /**
   * @var Environment
   */
  private static $instance;
  /**
   * @var array<string,LoggerInterface>
   */
  private static $log = [];

  /**
   * @var string
   */
  private $_id;
  /**
   * @var string
   */
  private $_type;

  /**
   * Environment's filesystem root
   *
   * @var FileInterface
   */
  private $file;

  /**
   * @param string               $type
   * @param FileInterface        $root
   * @param string|null          $id
   *
   * @throws \InvalidArgumentException Empty environment or localization
   * @throws \LogicException There is already an instance
   */
  public function __construct( string $type, FileInterface $root, ?string $id = null ) {

    if( self::$instance ) throw new \LogicException( 'Unable to create another instance, use ::instance() instead' );
    else if( empty( $type ) ) throw new \InvalidArgumentException( "Missing configuration: 'type'" );
    else {

      //
      $this->_id    = $id ?? Text::unique( 8, false );
      $this->_type  = $type;
      $this->file   = $root;

      // register the exception handler to log every unhandled exception
      set_exception_handler( function ( $exception ) {
        if( $exception ) Exception::log( $exception, static::logger() );
      } );

      // override the PHP error handler
      set_error_handler( function ( $code, $message, $file, $line ) {
        if( $code & error_reporting() ) {
          static::logger()->create( 'Unexpected code failure: #{code} with \'{message}\' message, at \'{file}\'', [
            'code'    => $code,
            'message' => $message,
            'file'    => $file . ':' . $line
          ], static::class, Severity::get( $code ) );
        }

        return false;
      } );

      //
      self::$instance = $this;
    }
  }

  /**
   * @return string
   */
  public function getId() {
    return $this->_id;
  }
  /**
   * @return string
   */
  public function getType(): string {
    return $this->_type;
  }
  /**
   * File from the app root directory
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public function getFile( string $path = '' ): FileInterface {
    return $this->file->get( $path );
  }

  /**
   * File from the Spoom's resource directory
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public static function resource( string $path = '' ): FileInterface {
    return (new File( Autoload::DIRECTORY ))->get( $path );
  }
  /**
   * Get or set named loggers
   *
   * @param string|null          $name
   * @param LoggerInterface|null $instance
   *
   * @return LoggerInterface
   */
  public static function logger( ?string $name = null, ?LoggerInterface $instance = null ): LoggerInterface {
    if( $instance !== null ) return self::$log[ $name ?? static::class ]  = $instance;
    else return self::$log[ $name ?? static::class ] ?? self::$log[ static::class ] ?? (self::$log[ static::class ] = new Logger( static::class, Severity::get() ));
  }

  /**
   * @return static
   * @throws \Exception
   */
  public static function instance() {
    if( empty( self::$instance ) ) throw new \LogicException( 'There is no Environment instance right now' );
    else if( !( self::$instance instanceof static ) ) throw new \LogicException( 'Wrong type of Environment instance, should be ' . self::class );
    else return self::$instance;
  }
}
