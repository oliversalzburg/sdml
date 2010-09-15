<?php
  require_once( dirname( __FILE__ ) . "/../AbstractSdmlToken.php" );
  require_once( dirname( __FILE__ ) . "/../ISdmlToken.php" );

  class SequenceToken extends AbstractSdmlToken implements ISdmlToken {
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
        $parser = ParserContext::get()->Parser;
        $parser->internalParse( ParserContext::get()->Line, $line );
      }

      return null;
    }

    public function toSql( $callback ) {
     return "";
    }
  }
?>
