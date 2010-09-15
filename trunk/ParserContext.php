<?php
  class ParserContext {
    /**
    * The name of the file that is currently being processed.
    *
    * @var string
    */
    public $Filename;

    /**
    * The line (number) that is currently being parsed.
    *
    * @var int
    */
    public $Line;

    /**
    * The current scope of the parser.
    *
    * @var mixed
    */
    public $Scope;

    /***
    * The SdmlParser instance this context belongs to.
    *
    * @var SdmlParser
    */
    public $Parser;

    private static $instance;

    /**
    * Retrieve the singleton ParserContext instance.
    *
    */
    public static function get() {
      if( !isset( self::$instance ) ) self::$instance = new ParserContext();
      return self::$instance;
    }
  }
?>