<?php
  // This logging framework is part of the project SDML originated from.
  // These references remain in the code to ensure compatibility with it.
  define( "SDML_USE_LOGGING", false );
  if( SDML_USE_LOGGING ) {
    require_once( dirname( __FILE__ ) . "/../../logging/Logging.php" );
    Logging::setLogLevel( Logging::eDEBUG );
    Logging::addConsoleLogger();
  }

  require_once( dirname( __FILE__ ) . "/parser/error/GPTParserException.php" );
  require_once( dirname( __FILE__ ) . "/parser/GPTParser.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/ColumnToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/ConstraintToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/DatabaseToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/InsertToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/KeyToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/SequenceToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/TableToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/TimestampCreatedToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/TimestampModifiedToken.php" );
  require_once( dirname( __FILE__ ) . "/token/tokens/UserToken.php" );
  require_once( dirname( __FILE__ ) . "/MySqlConnector.php" );

  $shortopts  = "";
  $shortopts .= "p::";  // MySQL password
  $shortopts .= "i:";   // Input filename
  //$shortopts .= "o::";  // Output filename (if none is given stdout will be used)
  $shortopts .= "d";    // Debug output

  $sdmlFilename   = "";
  $mysqlPassword  = "";
  $isDebug        = false;

  $options = getopt( $shortopts );
  if( !isset( $options[ "i" ] ) ) {
    echo( "Error: No input filename given.\n" );
    printUseage();
    exit;

  } else {
    $sdmlFilename = $options[ "i" ];
  }

  $useMySqlConnector = false;
  if( isset( $options[ "p" ] ) ) {
    $mysqlPassword      = $options[ "p" ];
    $useMySqlConnector  = true;
  }

  if( isset( $options[ "d" ] ) ) {
    $isDebug = true;
  }

  $connector = null;
  if( $useMySqlConnector ) {
    $connector = new MySqlConnector( $mysqlPassword );
    $connector->errorLog = "error";
    $connector->debugLog = "debug";
  }

  $parsers = array(
    "database"   => "DatabaseToken"          ,
    "table"      => "TableToken"             ,
    "constraint" => "ConstraintToken"        ,

    "int8"       => "ColumnToken"            ,
    "int16"      => "ColumnToken"            ,
    "int32"      => "ColumnToken"            ,
    "int64"      => "ColumnToken"            ,
    "uint8"      => "ColumnToken"            ,
    "uint16"     => "ColumnToken"            ,
    "uint32"     => "ColumnToken"            ,
    "uint64"     => "ColumnToken"            ,
    "string8"    => "ColumnToken"            ,
    "string16"   => "ColumnToken"            ,
    "string24"   => "ColumnToken"            ,
    "timestamp"  => "ColumnToken"            ,
    "ctimestamp" => "TimestampCreatedToken"  ,
    "mtimestamp" => "TimestampModifiedToken" ,
    "bool"       => "ColumnToken"            ,
    "byte[]"     => "ColumnToken"            ,
    "char[]"     => "ColumnToken"            ,
    "time"       => "ColumnToken"            ,
    "date"       => "ColumnToken"            ,

    "key"        => "KeyToken"               ,
    "unique"     => "KeyToken"               ,

    "insert"     => "InsertToken"            ,

    "user"       => "UserToken"              ,

    "sequence"   => "SequenceToken"
  );

  $parser = new GPTParser();
  $parser->isDebug = $isDebug;
  $parser->errorLog = "error";
  $parser->debugLog = "debug";
  $parser->init( "DatabaseToken", $parsers );
  $parser->PostProcessor = "queryCallback";
  try {
    $result = $parser->parseFile( $sdmlFilename );

  } catch( GPTParserException $ex ) {
    exit( $ex->getMessage() . "\n" );
  }

  /**
  * Print the command line parameters of this script.
  */
  function printUseage() {
    echo( "parseSdml.php -i<input.sdml> [-p<MySql root password>] [-d]\n" );
  }

  /**
  * The callback to use to process SQL statements.
  *
  * @param string $query The query to process
  */
  function queryCallback( $query ) {
    global $useMySqlConnector;
    global $isDebug;
    if( $isDebug ) {
      debug( $query );
    }
    if( $useMySqlConnector ) {
      connectorQuery( $query );

    } else {
      echoQuery( $query );
    }
  }

  /**
  * Prints the supplied query to the screen.
  *
  * @param string $query
  */
  function echoQuery( $query ) {
    echo $query . "\n";
  }

  /**
  * Passes the supplied query to a database connector.
  *
  * @param string $query
  */
  function connectorQuery( $query ) {
    global $connector;
    $connector->processParserOutput( $query );
  }

  /**
  * Used for logging debug messages.
  *
  * @param string $message
  */
  function debug( $message ) {
    if( SDML_USE_LOGGING ) {
      Logging::debug( $message );
    } else {
      echo( $message . "\n" );
    }
  }

  /**
  * Used for logging error messages.
  *
  * @param string $message
  */
  function error( $message ) {
    if( SDML_USE_LOGGING ) {
      Logging::error( $message );
    } else {
      echo( $message . "\n" );
    }
  }
?>
