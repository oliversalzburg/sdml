<?php
  require_once( dirname( __FILE__ ) . "/../AbstractSdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../../ParserContext.php" );

  class ColumnToken extends AbstractSdmlToken implements ISdmlToken {
    public $Name;
    public $Type;
    public $DefaultValue;

    public function __construct( $name, $type, $defaultValue = null ) {
      $this->Name           = $name;
      $this->Type           = $type;
      $this->DefaultValue   = $defaultValue;
    }

    public static function parse( $tokens ) {
      parent::expectParameters( $tokens, "type", "name" );

      $name = $tokens[ 1 ];
      $type = $tokens[ 0 ];
      array_shift( $tokens );
      array_shift( $tokens );
      $defaultValue = implode( " ", $tokens );

      $object = new ColumnToken( $name, $type, $defaultValue );
      //call_user_func_array( array( $object, "__construct" ), $tokens );

      $scope = ParserContext::get()->Scope;
      $className = get_class( $scope );
      if( "TableToken" != $className ) throw new SdmlParserException( sprintf( "Column defined in invalid scope. Expected 'TableToken' got '%s' at %s:%s.", $className, ParserContext::get()->Filename, ParserContext::get()->Line ) );

      $object->scope = $scope;
      $scope->Columns[] = $object;
      return $object;
    }

    protected static function stripModifiers( $name ) {
      $noModifiers = preg_replace( "/^([+!?*-]*)/", "", $name );
      $noArray = preg_replace( "/\[.*\]$/", "", $noModifiers) ;
      return $noArray;
    }

    protected static function translateTypes( $type, $name ) {
      preg_match( "/\[(.+)\]/", $name, $matches );
      if( isset( $matches[ 1 ] ) ) {
        $size = $matches[ 1 ];
      } else {
        $size = null;
      }

      // TODO: Replace with switch-case?
      $types[ "int8"        ] = "tinyint";
      $types[ "int16"       ] = "smallint";
      $types[ "int24"       ] = "mediumint";
      $types[ "int32"       ] = "int";
      $types[ "int64"       ] = "bigint";
      $types[ "uint8"       ] = "tinyint unsigned";
      $types[ "uint16"      ] = "smallint unsigned";
      $types[ "uint24"      ] = "mediumint) unsigned";
      $types[ "uint32"      ] = "int unsigned";
      $types[ "uint64"      ] = "bigint unsigned";
      $types[ "string8"     ] = "tinytext";
      $types[ "string16"    ] = "text";
      $types[ "string24"    ] = "mediumtext";
      $types[ "string32"    ] = "longtext";
      $types[ "timestamp"   ] = "timestamp";
      $types[ "ctimestamp"  ] = "timestamp";
      $types[ "mtimestamp"  ] = "timestamp";
      $types[ "bool"        ] = "tinyint(1)";
      $types[ "byte[]"      ] = sprintf( "binary(%s)", $size );
      $types[ "char[]"      ] = sprintf( "varchar(%s)", $size );
      $types[ "time"        ] = "time";
      $types[ "date"        ] = "date";
      if( !isset( $types[ $type ] ) ) throw new SdmlParserException( sprintf( "Unknown column type '%s' at %s:%s.", $type, ParserContext::get()->Filename, ParserContext::get()->Line ) );
      return $types[ $type ];
    }

    protected function isPrimaryKey() {
      return ( FALSE != strstr( $this->Name, "!" ) );
    }

    protected function isNullable() {
      return ( FALSE != strstr( $this->Name, "?" ) );
    }

    protected function hasIndex() {
      return ( FALSE != strstr( $this->Name, "-" ) );
    }

    protected function isAutoIncrement() {
      return ( FALSE != strstr( $this->Name, "+" ) );
    }

    public function toSql( $callback ) {
      $cleanName = self::stripModifiers( $this->Name );
      $column =
        sprintf(
          "`%s` %s %s",
          self::stripModifiers( $this->Name ),
          self::translateTypes( $this->Type, $this->Name ),
          parent::implodeEx(
            " ",
            array(
              ( !$this->isNullable() ) ? "NOT NULL" : null,
              ( $this->DefaultValue != null ) ? "DEFAULT " . $this->DefaultValue : null,
              ( $this->isAutoIncrement() ) ? "AUTO_INCREMENT" : ""
            )
          )
        )
      ;

      if( $this->isPrimaryKey() ) $column .= sprintf( ", PRIMARY KEY (`%s`)", $cleanName );
      if( $this->hasIndex()     ) $column .= sprintf( ", KEY `%s_Index` (`%s`)", $cleanName, $cleanName );
      return $column;
    }
  }
?>