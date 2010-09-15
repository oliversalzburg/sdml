<?php
  interface ISdmlToken {
    static function parse( $tokens );

    /**
    * Render the content of the token as a SQL statement.
    *
    * @param mixed $callback An optional callback function that should receive the result as well.
    */
    function toSql( $callback );
  }
?>
