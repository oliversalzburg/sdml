<?php
  require_once( dirname( __FILE__ ) . "/ColumnToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../../ParserContext.php" );

  class TimestampCreatedToken extends ColumnToken implements ISdmlToken {
    public function __construct( $name, $type, $defaultValue = "CURRENT_TIMESTAMP" ) {
      parent::__construct( $name, $type, $defaultValue );
    }

    public static function parse( $tokens ) {
      $object = parent::parse( $tokens );

      $scope = ParserContext::get()->Scope;
      $object->scope = $scope;
      $scope->TriggerInsert[] = $object;
      return $object;
    }
  }
?>