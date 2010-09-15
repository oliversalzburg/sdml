<?php
  require_once( dirname( __FILE__ ) . "/../AbstractSdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../../ParserContext.php" );

  class TableToken extends AbstractSdmlToken implements ISdmlToken {
    public $Name;
    public $Engine;
    public $DefaultCharset;

    public $Columns;
    public $Inserter;

    public $TriggerInsert;
    public $TriggerUpdate;

    public function __construct( $name, $engine = "InnoDB", $defaultCharset = "utf8" ) {
      $this->Name           = $name;
      $this->Engine         = $engine;
      $this->DefaultCharset = $defaultCharset;

      $this->Columns  = array();
      $this->Inserter = array();

      $this->TriggerInsert = array();
      $this->TriggerUpdate = array();
    }

    public static function parse( $tokens ) {
      array_shift( $tokens );

      parent::expectParameters( $tokens, "name" );
      $object = new TableToken( $tokens[ 0 ] );
      call_user_func_array( array( $object, "__construct" ), $tokens );

      $scope = ParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "DatabaseToken" != $className ) throw new SdmlParserException( sprintf( "Table defined in invalid scope. Expected 'DatabaseToken' got '%s' at %s:%s.", $className, ParserContext::get()->Filename, ParserContext::get()->Line ) );

      $object->scope = $scope;
      $scope->Tables[] = $object;
      return $object;
    }

    private function formatTable() {
      return sprintf( "CREATE TABLE `%s`.`%s` (%s) ENGINE=%s DEFAULT CHARACTER=%s;", $this->scope->Name, $this->Name, implode( ",", $this->Columns ), $this->Engine, $this->DefaultCharset );
    }

    private function constructTrigger( $callback ) {
      $triggers = "";
      $delimiterBegin = "";//"DELIMITER $$ ";
      $delimiterEnd   = "";//"DELIMITER ;";

      // Insert trigger
      if( 0 < count( $this->TriggerInsert ) ) {
        $insertTrigger = sprintf(
          "%s" .
          "CREATE TRIGGER `%s`.`on%sCreated` BEFORE INSERT ON `%s`.`%s` FOR EACH ROW BEGIN ",
          $delimiterBegin,
          $this->scope->Name,
          $this->Name,
          $this->scope->Name,
          $this->Name
        );
        foreach( $this->TriggerInsert as $trigger ) {
          $insertTrigger .= sprintf(
            "SET NEW.%s = NOW();",
           $trigger->Name
          );
        }
        $insertTrigger .=
          " END;" .
          $delimiterEnd
        ;
        parent::callQueryCallback( $callback, $insertTrigger );
        $triggers .= $insertTrigger;
      }

      // Update trigger
      if( 0 < count( $this->TriggerUpdate ) ) {
        $updateTrigger = sprintf(
          "%s" .
          "CREATE TRIGGER `%s`.`on%sUpdated` BEFORE UPDATE ON `%s`.`%s` FOR EACH ROW BEGIN ",
          $delimiterBegin,
          $this->scope->Name,
          $this->Name,
          $this->scope->Name,
          $this->Name
        );
        foreach( $this->TriggerUpdate as $trigger ) {
          $updateTrigger .= sprintf(
            "SET NEW.%s = NOW();",
           $trigger->Name
          );
        }
        $updateTrigger .=
          " END;" .
          $delimiterEnd
        ;
        parent::callQueryCallback( $callback, $updateTrigger );
        $triggers .= $updateTrigger;
      }
      return $triggers;
    }

    public function toSql( $callback ) {
      // Construct columns
      $columns = "";
      foreach( $this->Columns as $column ) {
        $columns .= $column->toSql( null ) . ", ";
      }
      if( count( $this->Columns ) > 0 ) {
        $columns = substr( $columns, 0, strlen( $columns ) - 2 );
      }

      $table =
        sprintf(
          "CREATE TABLE `%s`.`%s` (%s) ENGINE=%s DEFAULT CHARSET=%s;",
          $this->scope->Name,
          $this->Name,
          $columns,
          $this->Engine,
          $this->DefaultCharset
        )
      ;

      parent::callQueryCallback( $callback, $table );

      // Construct triggers
      $triggers = $this->constructTrigger( $callback );

      $result =
        sprintf(
          "%s%s",
          $table,
          $triggers
        )
      ;

      return $result;
    }
  }
?>
