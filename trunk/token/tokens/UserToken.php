<?php
  require_once( dirname( __FILE__ ) . "/../../parser/error/GPTParserException.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/AbstractGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/IGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/GPTParserContext.php" );

  class UserToken extends AbstractGPTToken implements IGPTToken {
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

      $scope = GPTParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "DatabaseToken" != $className ) throw new GPTParserException( sprintf( "User defined in invalid scope. Expected 'DatabaseToken' got '%s' at %s:%s.", $className, GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );

      $object->scope = $scope;
      $scope->User[] = $object;
      return $object;
    }

    public function render( $callback ) {
      $grantBeforeDrop =
        sprintf(
          "GRANT USAGE ON *.* TO '%s'@'localhost';",
          $this->Username
        )
      ;
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

      parent::callPostProcessCallback( $callback, $grantBeforeDrop, $drop, $create, $grant );

      $result =
        sprintf(
          "%s%s%s%s",
          $grantBeforeDrop,
          $drop,
          $create,
          $grant
        )
      ;
      return $result;
    }
  }
?>