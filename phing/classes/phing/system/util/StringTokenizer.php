<?php
// {{{ Header
/*
 * -File     $Id: StringTokenizer.php,v 1.8 2003/04/09 15:58:11 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Andrzej Nowodworski, a.nowodworski@learn.pl
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.system.lang.functions');

/**
 *  @package  phing.system.util
 */
class StringTokenizer {

    var $currentPosition = null;
    var $newPosition     = null;
    var $maxPosition     = null;
    var $str             = null;
    var $delimiters      = null;
    var $retDelims       = null;
    var $delimsChanged   = false;
    var $maxDelimChar    = null;  // ordinal value of char

    function StringTokenizer($str, $delim = ' \t\n\r\f', $returnDelims = false) {
        $this->currentPosition = (int) 0;
        $this->newPosition     = (int) -1;
        $this->delimsChanged   = false;
        $this->str             = (string) $str;
        $this->maxPosition     = (string) strlen($str);
        $this->delimiters      = (string) $delim;
        $this->retDelims       = (boolean) $returnDelims;
        $this->setMaxDelimChar();
    }

    /** Set maxDelimChar to the highest char in the delimiter set. */
    function setMaxDelimChar() {
        if ($this->delimiters === null) {
            $this->maxDelimChar = 0;
            return;
        }

        $m = 0;

        for ($i = 0; $i < strlen($this->delimiters); ++$i) {
            $c = ord($this->delimiters{$i});  // replace with charAt
            if ($m < $c) {
                $m = $c;
            }
        }
        $this->maxDelimChar = $m;
    }

    /**
     * Skips delimiters starting from the specified position. If retDelims
     * is false, returns the index of the first non-delimiter character at or
     * after startPos. If retDelims is true, startPos is returned.
     */
    function skipDelimiters($startPos) {
        if ($this->delimiters == null) {
            die("NullPointer");
        }

        $position = (int) $startPos;
        while (!$this->retDelims && ($position < $this->maxPosition)) {
            $c = ord($this->str{$position});
            if (($c > $this->maxDelimChar) || strIndexOf(chr($c), $this->delimiters) < 0)
                break;
            $position++;
        }
        return $position;
    }

    /**
     * Skips ahead from startPos and returns the index of the next delimiter
     * character encountered, or maxPosition if no such delimiter is found.
     */
    function scanToken($startPos) {
        $position = (int) $startPos;
        while ($position < $this->maxPosition) {
            $c = ord($this->str{$position});  //char at
            if (($c <= $this->maxDelimChar) && strIndexOf(chr($c), $this->delimiters) >= 0)
                break;
            $position++;
        }
        if ($this->retDelims && ($startPos == $position)) {
            $c = ord($this->str{$position});  //charAt
            if (($c <= $this->maxDelimChar) && strIndexOf(chr($c), $this->delimiters) >= 0)
                $position++;
        }
        return $position;
    }

    /**
     * Tests if there are more tokens available from this tokenizer's string.
     * If this method returns <tt>true</tt>, then a subsequent call to
     * <tt>nextToken</tt> with no argument will successfully return a token.
     *
     * @return  <code>true</code> if and only if there is at least one token
     *          in the string after the current position; <code>false</code>
     *          otherwise.
     */
    function hasMoreTokens() {
        //return ($this->token !== false) ? true :false;

        $this->newPosition = $this->skipDelimiters($this->currentPosition);
        return ($this->newPosition < $this->maxPosition);
    }

    /**
     * Returns the next token from this string tokenizer.
     *
     * @return     the next token from this string tokenizer.
     * @exception  NoSuchElementException  if there are no more tokens in this
     *               tokenizer's string.
     */
    function nextToken($delim = null) {
        if ($delim !== null) {
            $this->delimiters = (string) $delim;
            $this->delimsChanged = true;
            $this->setMaxDelimChar();
        }

        /*
         * If next position already computed in hasMoreElements() and
         * delimiters have changed between the computation and this invocation,
         * then use the computed value.
         */

        $this->currentPosition = (($this->newPosition >= 0) && !$this->delimsChanged) ?
                                 $this->newPosition : $this->skipDelimiters($this->currentPosition);

        $this->delimsChanged = false;
        $this->newPosition = -1;

        if ($this->currentPosition >= $this->maxPosition)
            die("NoSuchElementException");

        // use this explicit casts here to make it more readable
        $start = (int) $this->currentPosition;
        $this->currentPosition = $this->scanToken($this->currentPosition);
        return (string) substr($this->str, $start, $this->currentPosition-$start);
    }

    /**
     * Calculates the number of times that this tokenizer's
     * nextToken method can be called before it generates an
     * exception. The current position is not advanced.
     *
     * @return  integer the number of tokens remaining in the string using the current
     *          delimiter set.
     * @see     StringTokenizer::nextToken()
     */
    function countTokens() {
        $count  = (int) 0;
        $currpos = (int) $this->currentPosition;
        while ($currpos < $this->maxPosition) {
            $currpos = $this->skipDelimiters($currpos);
            if ($currpos >= $this->maxPosition)
                break;
            $currpos = $this->scanToken($currpos);
            $count++;
        }
        return $count;
    }
}
?>
