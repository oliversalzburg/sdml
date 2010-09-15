<?php
  class MySqlConnector {
    private $username;
    private $password;
    private $hostname;
    private $port;

    private $mysqli;

    public function __construct( $password, $username = "root", $hostname = "localhost", $port = 3306 ) {
      $this->username = $username;
      $this->password = $password;
      $this->hostname = $hostname;
      $this->port     = $port;

      $this->mysqli = null;
    }

    private function connect() {
      if( null == $this->mysqli ) {
        $this->mysqli = new mysqli( $this->hostname, $this->username, $this->password, null, $this->port );
        $this->mysqli->set_charset( "utf8" );
      }
    }

    public function processParserOutput( $output ) {
      $this->connect();
      $result = $this->mysqli->multi_query( $output );
      if( FALSE === $result ) {
        Logging::error( $this->mysqli->error );
        Logging::error( $output );
      }
    }
  }
?>