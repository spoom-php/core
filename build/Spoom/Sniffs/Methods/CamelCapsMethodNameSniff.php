<?php

if( class_exists( 'Generic_Sniffs_NamingConventions_CamelCapsFunctionNameSniff', true ) === false ) {
  throw new PHP_CodeSniffer_Exception( 'Class Generic_Sniffs_NamingConventions_CamelCapsFunctionNameSniff not found' );
}

class Spoom_Sniffs_Methods_CamelCapsMethodNameSniff extends Generic_Sniffs_NamingConventions_CamelCapsFunctionNameSniff {

  /**
   * Constructs a Spoom_Sniffs_Methods_CamelCapsMethodNameSniff.
   */
  public function __construct() {
    parent::__construct( array( T_CLASS, T_INTERFACE, T_TRAIT ), array( T_FUNCTION ), true );

  }//end __construct()

  /**
   * Processes the tokens within the scope.
   *
   * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
   * @param int                  $stackPtr  The position where this token was
   *                                        found.
   * @param int                  $currScope The position of the current scope.
   *
   * @return void
   */
  protected function processTokenWithinScope( PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope ) {
    $methodName = $phpcsFile->getDeclarationName( $stackPtr );
    if( $methodName === null ) {
      // Ignore closures.
      return;
    }

    // Ignore magic methods.
    if( preg_match( '|^__|', $methodName ) !== 0 ) {
      $magicPart = strtolower( substr( $methodName, 2 ) );
      if( isset( $this->magicMethods[ $magicPart ] ) === true
        || isset( $this->methodsDoubleUnderscore[ $magicPart ] ) === true
      ) {
        return;
      }
    }

    $testName = ltrim( $methodName, '_' );
    if( PHP_CodeSniffer::isCamelCaps( $testName, false, true, false ) === false ) {
      $error     = 'Method name "%s" is not in camel caps format';
      $className = $phpcsFile->getDeclarationName( $currScope );
      $errorData = array( $className . '::' . $methodName );
      $phpcsFile->addError( $error, $stackPtr, 'NotCamelCaps', $errorData );
      $phpcsFile->recordMetric( $stackPtr, 'CamelCase method name', 'no' );
    } else {
      $phpcsFile->recordMetric( $stackPtr, 'CamelCase method name', 'yes' );
    }

  }//end processTokenWithinScope()

  /**
   * Processes the tokens outside the scope.
   *
   * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
   * @param int                  $stackPtr  The position where this token was
   *                                        found.
   *
   * @return void
   */
  protected function processTokenOutsideScope( PHP_CodeSniffer_File $phpcsFile, $stackPtr ) {

  }//end processTokenOutsideScope()

}//end class