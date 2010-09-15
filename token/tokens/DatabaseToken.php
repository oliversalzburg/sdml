<?php
  require_once( dirname( __FILE__ ) . "/../AbstractSdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );

  class DatabaseToken extends AbstractSdmlToken implements ISdmlToken {
    public $Name;
    public $CharacterSet;
    public $Collate;

    public $User;

    public $Tables;
    public $Constraints;

    public function __construct( $name, $characterSet = "utf8", $collate = "utf8_general_ci" ) {
      $this->Name         = $name;
      $this->CharacterSet = $characterSet;
      $this->Collate      = $collate;

      $this->User         = array();

      $this->Tables       = array();
      $this->Constraints  = array();
    }

    public static function parse( $tokens ) {
      array_shift( $tokens );

      parent::expectParameters( $tokens, "name" );
      $object = new DatabaseToken( $tokens[ 0 ] );
      call_user_func_array( array( $object, "__construct" ), $tokens );

      $scope = ParserContext::get()->Scope;
      if( null != $scope ) throw new SdmlParserException( sprintf( "Database defined in invalid scope. Expected 'null' got '%s' at %s:%s.", get_class( $scope ), ParserContext::get()->Filename, ParserContext::get()->Line ) );
      return $object;
    }

    public function toSql( $callback ) {
      $drop =
        sprintf(
          "DROP DATABASE IF EXISTS `%s`;",
          $this->Name
        )
      ;
      $create =
        sprintf(
          "CREATE DATABASE `%s` CHARACTER SET %s COLLATE %s;",
          $this->Name,
          $this->CharacterSet,
          $this->Collate
        )
      ;

      parent::callQueryCallback( $callback, $drop, $create );

      // Construct tables and their inserter
      $tables   = "";
      $inserter = "";
      foreach( $this->Tables as $table ) {
        $tables .= $table->toSql( $callback );
        foreach( $table->Inserter as $insert ) {
          $inserter .= $insert->toSql( $callback );
        }
      }

      // Construct constraints
      $constraints = "";
      foreach( $this->Constraints as $constraint ) {
        $constraints .= $constraint->toSql( $callback );
      }

      // Construct Users
      $users = "";
      foreach( $this->User as $user ) {
        $users .= $user->toSql( $callback );
      }

      $result =
        sprintf(
          "%s" .
          "%s" .
          "%s" .
          "%s" .
          "%s" .
          "%s",
          $drop,
          $create,
          $tables,
          $inserter,
          $constraints,
          $users
        )
      ;
      return $result;
    }
  }
?>