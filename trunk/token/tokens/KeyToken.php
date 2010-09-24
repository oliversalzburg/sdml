<?php
  require_once( dirname( __FILE__ ) . "/../AbstractSdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../../ParserContext.php" );

  class KeyToken extends AbstractSdmlToken implements ISdmlToken {
    public $Members;
    public $IsUnique;

    public function __construct( $isUnique, $members ) {
      $this->IsUnique = $isUnique;
      $this->Members  = $members;
    }

    public static function parse( $tokens ) {
      $isUnique = false;
      if( "unique" == $tokens[ 0 ] ) {
        if( !isset( $tokens[ 1 ] ) || "key" != $tokens[ 1 ] ) {
          throw new SdmlParserException( sprintf( "Keyword 'unique' has to be followed by keyword 'key' at %s:%s.", ParserContext::get()->Filename, ParserContext::get()->Line ) );
        }
        // Drop unique keyword
        array_shift( $tokens );

        $isUnique = true;
      }
      // Drop key keyword
      array_shift( $tokens );

      if( !isset( $tokens[ 0 ] ) ) {
        throw new SdmlParserException( sprintf( "Missing column name for key at %s:%s.", ParserContext::get()->Filename, ParserContext::get()->Line ) );
      }

      $scope = ParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "TableToken" != $className ) throw new SdmlParserException( sprintf( "Key defined in invalid scope. Expected 'TableToken' got '%s' at %s:%s.", $className, ParserContext::get()->Filename, ParserContext::get()->Line ) );

      $object = new KeyToken( $isUnique, $tokens );

      $object->scope = $scope;
      $scope->Columns[] = $object;
      return $object;
    }

    public function toSql( $callback ) {
      $result =
        sprintf(
          "%sKEY `%s_Index` (`%s`)",
          ( $this->IsUnique ) ? "UNIQUE " : "",
          implode( "", $this->Members ),
          implode( "`,`", $this->Members )
        )
      ;
      return $result;
    }
  }
?>
