<?php
  require_once( dirname( __FILE__ ) . "/../AbstractSdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../../ParserContext.php" );

  class ConstraintToken extends AbstractSdmlToken implements ISdmlToken {
    public $Target;
    public $References;

    public function __construct( $target, $references ) {
      $this->Target     = $target;
      $this->References = $references;
    }

    public static function parse( $tokens ) {
      array_shift( $tokens );

      parent::expectParameters( $tokens, "target", "references" );
      $object = new ConstraintToken( $tokens[ 0 ], $tokens[ 1 ] );
      //call_user_func_array( array( $object, "__construct" ), $tokens );

      $scope = ParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "DatabaseToken" != $className ) throw new SdmlParserException( sprintf( "Constraint defined in invalid scope. Expected 'DatabaseToken' got '%s' at %s:%s.", $className, ParserContext::get()->Filename, ParserContext::get()->Line ) );

      $object->scope = $scope;
      $scope->Constraints[] = $object;
      return $object;
    }

    private function getTargetTable() {
      return substr( $this->Target, 0, strpos( $this->Target, "." ) );
    }

    private function getTargetColumn() {
      return substr( $this->Target, strpos( $this->Target, "." ) + 1 );
    }

    private function getReferenceTable() {
      return substr( $this->References, 0, strpos( $this->References, "." ) );
    }

    private function getReferenceColumn() {
      return substr( $this->References, strpos( $this->References, "." ) + 1 );
    }

    public function toSql( $callback ) {
      $result =
        sprintf(
          "ALTER TABLE `%s`.`%s` ADD CONSTRAINT FK_%s_%s FOREIGN KEY (`%s`) REFERENCES `%s`.`%s` (`%s`);",
          $this->scope->Name,
          $this->getTargetTable(),
          $this->getTargetTable(),
          $this->getTargetColumn(),
          $this->getTargetColumn(),
          $this->scope->Name,
          $this->getReferenceTable(),
          $this->getReferenceColumn()
        )
      ;

      parent::callQueryCallback( $callback, $result );

      return $result;
    }
  }
?>