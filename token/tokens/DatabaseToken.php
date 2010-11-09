<?php
  require_once( dirname( __FILE__ ) . "/../../parser/error/GPTParserException.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/AbstractGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/IGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/GPTParserContext.php" );

  class DatabaseToken extends AbstractGPTToken implements IGPTToken {
    public $UseExisting;

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
      $useExisting = false;
      if( "use" == $tokens[ 0 ] ) {
        $useExisting = true;
        // Drop use token
        array_shift( $tokens );
      }
      // Drop database token
      array_shift( $tokens );

      parent::expectParameters( $tokens, "name" );
      $object = new DatabaseToken( $tokens[ 0 ] );
      call_user_func_array( array( $object, "__construct" ), $tokens );
      $object->UseExisting = $useExisting;

      $scope = GPTParserContext::get()->Scope;
      if( null != $scope ) throw new GPTParserException( sprintf( "Database defined in invalid scope. Expected 'null' got '%s' at %s:%s.", get_class( $scope ), GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );
      return $object;
    }

    public function render( $callback ) {
      $drop = "";
      $create = "";

      if( !$this->UseExisting ) {
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

      } else {
        $create =
          sprintf(
            "USE `%s`;",
            $this->Name
          )
        ;
      }

      parent::callPostProcessCallback( $callback, $drop, $create );

      // Construct tables and their inserter
      $tables   = "";
      $inserter = "";
      foreach( $this->Tables as $table ) {
        $tables .= $table->render( $callback );
        foreach( $table->Inserter as $insert ) {
          $inserter .= $insert->render( $callback );
        }
      }

      // Construct constraints
      $constraints = "";
      foreach( $this->Constraints as $constraint ) {
        $constraints .= $constraint->render( $callback );
      }

      // Construct Users
      $users = "";
      foreach( $this->User as $user ) {
        $users .= $user->render( $callback );
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