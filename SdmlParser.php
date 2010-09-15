<?php
  require_once( dirname( __FILE__ ) . "/error/SdmlParserException.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/ColumnToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/ConstraintToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/DatabaseToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/InsertToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/SequenceToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/TableToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/TimestampCreatedToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/TimestampModifiedToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/UserToken.php" );
  require_once( dirname( __FILE__ ) . "/token/ParserLibrary.php" );
  require_once( dirname( __FILE__ ) . "/ParserContext.php" );

  class SdmlParser {
    private $isInitialized = false;
    public $isDebug = false;
    
    public $errorLog;
    public $debugLog;

    private $scope;
    private $scopes;

    private $lastWhitespace;

    private $context;
    private $result;
    private $databases;

    /**
    * User-supplied callback function to execute any resulting query.
    *
    * @var mixed
    */
    public $QueryFunc;

    public function  __construct() {
    }

    public function init() {
      ParserLibrary::registerParser( "database",    "DatabaseToken"           );
      ParserLibrary::registerParser( "table",       "TableToken"              );
      ParserLibrary::registerParser( "constraint",  "ConstraintToken"         );

      ParserLibrary::registerParser( "int8",        "ColumnToken"             );
      ParserLibrary::registerParser( "int16",       "ColumnToken"             );
      ParserLibrary::registerParser( "int32",       "ColumnToken"             );
      ParserLibrary::registerParser( "int64",       "ColumnToken"             );
      ParserLibrary::registerParser( "uint8",       "ColumnToken"             );
      ParserLibrary::registerParser( "uint16",      "ColumnToken"             );
      ParserLibrary::registerParser( "uint32",      "ColumnToken"             );
      ParserLibrary::registerParser( "uint64",      "ColumnToken"             );
      ParserLibrary::registerParser( "string8",     "ColumnToken"             );
      ParserLibrary::registerParser( "string16",    "ColumnToken"             );
      ParserLibrary::registerParser( "string24",    "ColumnToken"             );
      ParserLibrary::registerParser( "timestamp",   "ColumnToken"             );
      ParserLibrary::registerParser( "ctimestamp",  "TimestampCreatedToken"   );
      ParserLibrary::registerParser( "mtimestamp",  "TimestampModifiedToken"  );
      ParserLibrary::registerParser( "bool",        "ColumnToken"             );
      ParserLibrary::registerParser( "byte[]",      "ColumnToken"             );
      ParserLibrary::registerParser( "char[]",      "ColumnToken"             );
      ParserLibrary::registerParser( "time",        "ColumnToken"             );
      ParserLibrary::registerParser( "date",        "ColumnToken"             );

      ParserLibrary::registerParser( "insert",      "InsertToken"             );

      ParserLibrary::registerParser( "user",        "UserToken"               );

      ParserLibrary::registerParser( "sequence",    "SequenceToken"           );
      $this->isInitialized = true;
    }

    public function parseFile( $filename ) {
      if( $this->isInitialized == false ) throw new SdmlParserException( "SDML parser not initialized. Call init() first." );

      $lines = file( $filename, FILE_IGNORE_NEW_LINES );

      $this->context = ParserContext::get();
      $this->context->Filename  = $filename;
      $this->context->Parser    = $this;

      $this->lastWhitespace = 0;
      $this->scopes = array();

      $this->databases = array();

      // Iterate over all lines
      foreach( $lines as $lineNumber => $line ) {
        $this->internalParse( $lineNumber, $line );
      }

      $result = "";

      // Render result
      foreach( $this->databases as $database ) {
        $result .= $database->toSql( $this->QueryFunc );
      }

      return $result;
    }

    public function internalParse( $lineNumber, $line ) {
      $this->context->Line = $lineNumber + 1;

      if( $this->isDebug ) Logging::debug( $line );

      // Skip comments
      $isComment = preg_match( "~^\s*((//)|(#)|(;))~", $line, $matches );
      if( 1 == $isComment ) return;

      // Find indentation
      $lineLength = strlen( $line );
      $found      = preg_match( "/^(\s)+/", $line, $matches );
      if( 0 == $found || 0 == $lineLength ) {
        // Skip empty lines
        if( 0 == $lineLength ) return;

      } else {
        // Adjust scope
        $whiteSpace = $matches[ 0 ];
        if( strlen( $whiteSpace ) > $this->lastWhitespace ) {
          $this->scopes[] = $this->result;
          $this->lastWhitespace = strlen( $whiteSpace );
          if( $this->isDebug ) Logging::debug( "Adjusting scope downwards" );

        } else if( strlen( $whiteSpace ) < $this->lastWhitespace ) {
          array_pop( $this->scopes );
          $this->lastWhitespace = strlen( $whiteSpace );
          if( $this->isDebug ) Logging::debug( "Adjusting scope upwards" );
        }
      }

      // Set resulting scope in parser context.
      if( 0 == count( $this->scopes ) ) {
        $this->context->Scope = null;

      } else {
        $this->context->Scope = $this->scopes[ count( $this->scopes ) - 1 ];
      }

      if( $this->isDebug ) Logging::debug( get_class( $this->context->Scope ) );

      // Remove excessive whitespace
      $line = preg_replace( "/\s+/", " ", trim( $line ) );

      // Split into tokens
      $tokens = explode( " ", $line );
      $token  = $tokens[ 0 ];

      if( $this->isDebug ) Logging::debug( "Parsing line." );
      // Construct parser and parse tokens
      $parser = ParserLibrary::parserFromToken( $token );
      $this->result = call_user_func( array( $parser, "parse" ), $tokens );

      if( "DatabaseToken" == get_class( $this->result ) ) {
        $this->databases[] = $this->result;
      }

      return $this->result;
    }
  }
?>