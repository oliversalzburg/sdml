<?php
  require_once( dirname( __FILE__ ) . "/../error/SdmlParserException.php" );

  class ParserLibrary {
    private static $instance;

    private $parsers;

    private static function get() {
      if( !isset( self::$instance ) ) self::$instance = new ParserLibrary();
      return self::$instance;
    }

    public static function registerParser( $token, $className ) {
      self::get()->parsers[ $token ] = $className;
    }

    public static function parserFromToken( $token ) {
      if( !isset( self::get()->parsers[ $token ] ) ) throw new SdmlParserException( sprintf( "Unknown token '%s' at %s:%s.", $token, ParserContext::get()->Filename, ParserContext::get()->Line ) );
      $className = self::get()->parsers[ $token ];
      return $className;
    }
  }
?>