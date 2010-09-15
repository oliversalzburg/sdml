<?php
  require_once( dirname( __FILE__ ) . "/../AbstractSdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../../ParserContext.php" );

  class UserToken extends AbstractSdmlToken implements ISdmlToken {
    public $Username;
    public $Password;

    public function __construct( $username, $password ) {
      $this->Username = $username;
      $this->Password = $password;
    }

    public static function parse( $tokens ) {
      // Drop 'user' keyword
      array_shift( $tokens );

      parent::expectParameters( $tokens, "username", "password" );
      $username = $tokens[ 0 ];
      $password = $tokens[ 1 ];

      $object = new UserToken( $username, $password );
      call_user_func_array( array( $object, "__construct" ), $tokens );

      $scope = ParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "DatabaseToken" != $className ) throw new SdmlParserException( sprintf( "User defined in invalid scope. Expected 'DatabaseToken' got '%s' at %s:%s.", $className, ParserContext::get()->Filename, ParserContext::get()->Line ) );

      $object->scope = $scope;
      $scope->User[] = $object;
      return $object;
    }

    public function toSql( $callback ) {
      $drop =
        sprintf(
          "DROP USER '%s'@'localhost';",
          $this->Username
        )
      ;
      $create =
        sprintf(
          "CREATE USER '%s'@'localhost' IDENTIFIED BY '%s';",
          $this->Username,
          $this->Password
        )
      ;
      $databaseName = $this->scope->Name;
      $grant =
        sprintf(
          "GRANT ALL PRIVILEGES ON %s.* TO '%s'@'localhost';",
          $databaseName,
          $this->Username
        )
      ;

      parent::callQueryCallback( $callback, $drop, $create, $grant );

      $result =
        sprintf(
          "%s%s%s",
          $drop,
          $create,
          $grant
        )
      ;
      return $result;
    }
  }
?>