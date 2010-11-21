<?php
  require_once( dirname( __FILE__ ) . "/../../parser/error/GPTParserException.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/AbstractGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/IGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/GPTParserContext.php" );

  class TableToken extends AbstractGPTToken implements IGPTToken {
    public $Name;
    public $Engine;
    public $DefaultCharset;

    public $Columns;
    public $Inserter;

    public $TriggerInsert;
    public $TriggerUpdate;
    public static $TriggerDelimiterBegin = "DELIMITER \$\$\n";
    public static $TriggerDelimiterEnd   = "\nDELIMITER ;";

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

      $scope = GPTParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "DatabaseToken" != $className ) throw new GPTParserException( sprintf( "Table defined in invalid scope. Expected 'DatabaseToken' got '%s' at %s:%s.", $className, GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );

      $object->scope = $scope;
      $scope->Tables[] = $object;
      return $object;
    }

    private function constructTrigger( $callback ) {
      $triggers = "";
      $delimiterBegin = self::$TriggerDelimiterBegin;
      $delimiterEnd   = self::$TriggerDelimiterEnd;

      // Insert trigger
      if( 0 < count( $this->TriggerInsert ) ) {
        $insertTrigger = sprintf(
          "%s" .
          "CREATE TRIGGER %s`on%sCreated` BEFORE INSERT ON %s`%s` FOR EACH ROW BEGIN ",
          $delimiterBegin,
          ( $this->scope->Name != "" ) ? "`" . $this->scope->Name . "`." : "",
          $this->Name,
          ( $this->scope->Name != "" ) ? "`" . $this->scope->Name . "`." : "",
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
          ( ( $delimiterEnd != "" ) ? "\n$$" . $delimiterEnd : "" )
        ;
        parent::callPostProcessCallback( $callback, $insertTrigger );
        $triggers .= $insertTrigger;
      }

      // Update trigger
      if( 0 < count( $this->TriggerUpdate ) ) {
        $updateTrigger = sprintf(
          "%s" .
          "CREATE TRIGGER %s`on%sUpdated` BEFORE UPDATE ON %s`%s` FOR EACH ROW BEGIN ",
          $delimiterBegin,
          ( $this->scope->Name != "" ) ? "`" . $this->scope->Name . "`." : "",
          $this->Name,
          ( $this->scope->Name != "" ) ? "`" . $this->scope->Name . "`." : "",
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
          ( ( $delimiterEnd != "" ) ? "\n$$" . $delimiterEnd : "" )
        ;
        parent::callPostProcessCallback( $callback, $updateTrigger );
        $triggers .= $updateTrigger;
      }
      return $triggers;
    }

    public function render( $callback ) {
      // Construct columns
      $columns = "";
      foreach( $this->Columns as $column ) {
        $columns .= $column->render( null ) . ", ";
      }
      if( count( $this->Columns ) > 0 ) {
        $columns = substr( $columns, 0, strlen( $columns ) - 2 );
      }

      $table =
        sprintf(
          "DROP TABLE IF EXISTS %s`%s`;\n" .
          "CREATE TABLE %s`%s` (%s) ENGINE=%s DEFAULT CHARSET=%s;",
          ( $this->scope->Name != "" ) ? "`" . $this->scope->Name . "`." : "",
          $this->Name,
          ( $this->scope->Name != "" ) ? "`" . $this->scope->Name . "`." : "",
          $this->Name,
          $columns,
          $this->Engine,
          $this->DefaultCharset
        )
      ;

      parent::callPostProcessCallback( $callback, $table );

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
