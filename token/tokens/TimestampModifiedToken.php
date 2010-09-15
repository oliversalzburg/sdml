<?php
  require_once( dirname( __FILE__ ) . "/ColumnToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../../ParserContext.php" );

  class TimestampModifiedToken extends ColumnToken implements ISdmlToken {
    public function __construct( $name, $type, $defaultValue = "'0000-00-00 00:00:00'" ) {
      parent::__construct( $name, $type, $defaultValue );
    }

    public static function parse( $tokens ) {
      $object = parent::parse( $tokens );

      $scope = ParserContext::get()->Scope;
      $object->scope = $scope;
      $scope->TriggerInsert[] = $object;
      $scope->TriggerUpdate[] = $object;
      return $object;
    }
  }
?>