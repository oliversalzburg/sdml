<?php
  // This logging framework is part of the project SDML originated from.
  // These references remain in the code to ensure compatibility with it.
  define( "SDML_USE_LOGGING", false );
  if( SDML_USE_LOGGING ) {
    require_once( dirname( __FILE__ ) . "/../../logging/Logging.php" );
    Logging::setLogLevel( Logging::eDEBUG );
    Logging::addConsoleLogger();
  }

  require_once( dirname( __FILE__ ) . "/SdmlParser.php" );
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

  $parser = new SdmlParser();
  $parser->isDebug = $isDebug;
  $parser->errorLog = "error";
  $parser->debugLog = "debug";
  $parser->init();
  $parser->QueryFunc = "queryCallback";
  try {
    $result = $parser->parseFile( $sdmlFilename );

  } catch( SdmlParserException $ex ) {
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
