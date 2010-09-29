<?php
  require_once( dirname( __FILE__ ) . "/../../parser/error/GPTParserException.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/AbstractGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/IGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/GPTParserContext.php" );

  class KeyToken extends AbstractGPTToken implements IGPTToken {
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
          throw new GPTParserException( sprintf( "Keyword 'unique' has to be followed by keyword 'key' at %s:%s.", GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );
        }
        // Drop unique keyword
        array_shift( $tokens );

        $isUnique = true;
      }
      // Drop key keyword
      array_shift( $tokens );

      if( !isset( $tokens[ 0 ] ) ) {
        throw new GPTParserException( sprintf( "Missing column name for key at %s:%s.", GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );
      }

      $scope = GPTParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "TableToken" != $className ) throw new GPTParserException( sprintf( "Key defined in invalid scope. Expected 'TableToken' got '%s' at %s:%s.", $className, GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );

      $object = new KeyToken( $isUnique, $tokens );

      $object->scope = $scope;
      $scope->Columns[] = $object;
      return $object;
    }

    public function render( $callback ) {
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
