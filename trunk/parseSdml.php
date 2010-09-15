<?php
  require_once( dirname( __FILE__ ) . "/logging/Logging.php" );
  require_once( dirname( __FILE__ ) . "/SdmlParser.php" );
  require_once( dirname( __FILE__ ) . "/MySqlConnector.php" );

  Logging::setLogLevel( Logging::eDEBUG );
  Logging::addConsoleLogger();

  $shortopts  = "";
  $shortopts .= "p::";  // MySQL password
  $shortopts .= "i:";   // Input filename
  //$shortopts .= "o::";  // Output filename (if none is given stdout will be used)
  $shortopts .= "d";    // Debug output

  $sdmlFilename = "";
  $mysqlPassword = "";
  $isDebug = false;

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
  }

  $parser = new SdmlParser();
  $parser->isDebug = $isDebug;
  $parser->init();
  $parser->QueryFunc = "queryCallback";
  try {
    $result = $parser->parseFile( $sdmlFilename );

  } catch( SdmlParserException $ex ) {
    exit( $ex->getMessage() . "\n" );
  }

  function printUseage() {
    echo( "parseSdml.php -i<input.sdml> [-p<MySql root password>] [-d]\n" );
  }

  function queryCallback( $query ) {
    global $useMySqlConnector;
    global $isDebug;
    if( $isDebug ) {
      Logging::debug( $query );
    }
    if( $useMySqlConnector ) {
      connectorQuery( $query );

    } else {
      echoQuery( $query );
    }
  }

  function echoQuery( $query ) {
    echo $query . "\n";
  }

  function connectorQuery( $query ) {
    global $connector;
    $connector->processParserOutput( $query );
  }
?>
