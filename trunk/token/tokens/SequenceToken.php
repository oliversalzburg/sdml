<?php
  require_once( dirname( __FILE__ ) . "/../../parser/token/AbstractGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/token/IGPTToken.php" );
  require_once( dirname( __FILE__ ) . "/../../parser/GPTParserContext.php" );

  class SequenceToken extends AbstractGPTToken implements IGPTToken {
    public $Start;
    public $End;
    public $Placeholder;
    public $Target;

    public function __construct( $start, $end, $placeholder, $target ) {
      $this->Start        = $start;
      $this->End          = $end;
      $this->Placeholder  = $placeholder;
      $this->Target       = $target;
    }

    public static function parse( $tokens ) {
      // Drop "sequence" keyword
      array_shift( $tokens );

      parent::expectParameters( $tokens, "start", "end", "placeholder", "target" );

      $start        = array_shift( $tokens );
      $end          = array_shift( $tokens );
      $placeholder  = array_shift( $tokens );
      $target       = implode( " ", $tokens );

      $range = range( $start, $end );
      foreach( $range as $i ) {
        $line = str_replace( $placeholder, $i, $target );
        $parser = GPTParserContext::get()->Parser;
        $parser->injectAtCurrentScope( GPTParserContext::get()->Line, $line );
      }

      return null;
    }

    public function render( $callback ) {
     return "";
    }
  }
?>
