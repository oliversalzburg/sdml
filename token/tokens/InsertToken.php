<?php
  require_once( dirname( __FILE__ ) . "/../../parser/error/GPTParserException.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/AbstractGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/IGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/GPTParserContext.php" );

  class InsertToken extends AbstractGPTToken implements IGPTToken {
    public $Delimiter;
    public $Data;

    public function __construct( $delimiter, $data ) {
      $this->Delimiter  = $delimiter;
      $this->Data       = $data;
    }

    public static function parse( $tokens ) {
      // Drop 'insert' keyword
      array_shift( $tokens );

      parent::expectParameters( $tokens, "delimiter", "data" );

      // Remember delimiter
      $delimiter = $tokens[ 0 ];
      // Remove delimiter
      array_shift( $tokens );

      // Reconstruct parameter string
      $data = implode( " ", $tokens );

      $object = new InsertToken( $delimiter, $data );
      //call_user_func_array( array( $object, "__construct" ), $tokens );

      $scope = GPTParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "TableToken" != $className ) throw new GPTParserException( sprintf( "Inserter defined in invalid scope. Expected 'TableToken' got '%s' at %s:%s.", $className, GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );

      $object->scope = $scope;
      $scope->Inserter[] = $object;
      return $object;
    }

    public function render( $callback ) {
      $databaseName = $this->scope->scope->Name;
      $tableName    = $this->scope->Name;
      $data         = implode( ",", explode( $this->Delimiter, $this->Data ) );

      $result =
        sprintf(
          "INSERT INTO `%s`.`%s` VALUES (%s);",
          $databaseName,
          $tableName,
          $data
        )
      ;

      parent::callPostProcessCallback( $callback, $result );

      return $result;
    }
  }
?>