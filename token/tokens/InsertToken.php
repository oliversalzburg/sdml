<?php
  require_once( dirname( __FILE__ ) . "/../AbstractSdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../../ParserContext.php" );

  class InsertToken extends AbstractSdmlToken implements ISdmlToken {
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

      $scope = ParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "TableToken" != $className ) throw new SdmlParserException( sprintf( "Inserter defined in invalid scope. Expected 'TableToken' got '%s' at %s:%s.", $className, ParserContext::get()->Filename, ParserContext::get()->Line ) );

      $object->scope = $scope;
      $scope->Inserter[] = $object;
      return $object;
    }

    public function toSql( $callback ) {
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

      parent::callQueryCallback( $callback, $result );

      return $result;
    }
  }
?>