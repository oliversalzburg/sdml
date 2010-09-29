<?php
  require_once( dirname( __FILE__ ) . "/ColumnToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/IGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/GPTParserContext.php" );

  class TimestampModifiedToken extends ColumnToken implements IGPTToken {
    public function __construct( $name, $type, $defaultValue = "'0000-00-00 00:00:00'" ) {
      parent::__construct( $name, $type, $defaultValue );
    }

    public static function parse( $tokens ) {
      $object = parent::parse( $tokens );

      $scope = GPTParserContext::get()->Scope;
      $object->scope = $scope;
      $scope->TriggerInsert[] = $object;
      $scope->TriggerUpdate[] = $object;
      return $object;
    }
  }
?>