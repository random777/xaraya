<?php

/*
 * $Id: StripPhpComments.php,v 1.5 2003/02/25 17:38:30 openface Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://binarycloud.com/phing/>.
*/

import('phing.filters.BaseFilterReader');

/**
 * This is a Php comment and string stripper reader that filters
 * those lexical tokens out for purposes of simple Php parsing.
 * (if you have more complex Php parsing needs, use a real lexer).
 * Since this class heavily relies on the single char read function,
 * you are reccomended to make it work on top of a buffered reader.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.5 $ $Date: 2003/02/25 17:38:30 $
 * @access    public
 * @see       FilterReader
 * @package   phing.filters
 */
class StripPhpComments extends BaseFilterReader {
    /**
     * The read-ahead character, used for effectively pushing a single
     * character back. -1 indicates that no character is in the buffer.
     */
    var	$_readAheadCh = -1;

    /**
     * Whether or not the parser is currently in the middle of a string
     * literal.
	 * @var boolean
     */
    var	$_inString = false;

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function StripPhpComments() {
        parent::BaseFilterReader();
    }

    /**
     * Creates a new filtered reader.
     *
     * @param reader A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A StripPhpComments object filtering the underlying
     *                stream.
     *
     */
    function &newStripPhpComments(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Excpected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $o = new StripPhpComments();
        $o->setReader($reader);

        return $o;
    }

	/**
     * Returns the  stream without Php comments.
     * 
     * @return the resulting stream, or -1
     *         if the end of the resulting stream has been reached
     * 
     * @exception IOException if the underlying stream throws an IOException
     *                        during reading     
     */
	function read() {
	
		$buffer = $this->in->read();
		if($buffer === -1) {
			return -1;
		}
		
		// This regex replace /* */ and // style comments
		$buffer = preg_replace('/\/\*[^*]*\*+([^\/*][^*]*\*+)*\/|\/\/[^\n]*|("(\\\\.|[^"\\\\])*"|\'(\\\\.|[^\'\\\\])*\'|.[^\/"\'\\\\]*)/s', "$2", $buffer);
				
		// The regex above is not identical to, but is based on the expression below:
		//
		// created by Jeffrey Friedl
        //   and later modified by Fred Curtis.
		// 	s{
        //          /\*         ##  Start of /* ... */ comment
        //          [^*]*\*+    ##  Non-* followed by 1-or-more *'s
        //          (
        //            [^/*][^*]*\*+
        //          )*          ##  0-or-more things which don't start with /
        //                      ##    but do end with '*'
        //          /           ##  End of /* ... */ comment
		//
        //        |         ##     OR  various things which aren't comments:
		//
        //          (
        //            "           ##  Start of " ... " string
        //            (
        //              \\.           ##  Escaped char
        //            |               ##    OR
        //              [^"\\]        ##  Non "\
        //            )*
        //           "           ##  End of " ... " string
		//
        //          |         ##     OR
		//
        //            '           ##  Start of ' ... ' string
        //            (
        //              \\.           ##  Escaped char
        //            |               ##    OR
        //              [^'\\]        ##  Non '\
        //            )*
        //            '           ##  End of ' ... ' string
		//
        //          |         ##     OR
		//
        //            .           ##  Anything other char
        //            [^/"'\\]*   ##  Chars which doesn't start a comment, string or escape
        //          )
        //        }{$2}gxs;
								
		return $buffer;
	}
		
	
    /*
     * Returns the next character in the filtered stream, not including
     * Php comments.
     * 
     * @return the next character in the resulting stream, or -1
     *         if the end of the resulting stream has been reached
     * 
     * @exception IOException if the underlying stream throws an IOException
     *                        during reading     
    */
    function readChar() {
        $ch = -1;

        if ( $this->_readAheadCh !== -1 ) {
            $ch = $this->_readAheadCh;
            $this->_readAheadCh = -1;
        } else {
            $ch = $this->in->readChar();
            if ( $ch === "\"" ) {
                $this->_inString = !$this->_inString;
            } else {
                if ( !$this->_inString ) {
                    if ( $ch === "/" ) {
                        $ch = $this->in->readChar();
                        if ( $ch === "/" ) {
                            while ( $ch !== "\n" && $ch !== -1 ) {
                                $ch = $this->in->readChar();
                            }
                        } else if ( $ch === "*" ) {
                            while ( $ch !== -1 ) {
                                $ch = $this->in->readChar();
                                while ( $ch === "*" && $ch !== -1 ) {
                                    $ch = $this->in->readChar();
                                }

                                if ( $ch === "/" ) {
                                    $ch = $this->readChar();
                                    echo "$ch\n";
                                    break;
                                }
                            }
                        } else {
                            $this->_readAheadCh = $ch;
                            $ch = "/";
                        }
                    }
                }
            }
        }

        return $ch;
    }

    /**
     * Creates a new StripJavaComments using the passed in
     * Reader for instantiation.
     * 
     * @param reader A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return a new filter based on this configuration, but filtering
     *         the specified reader
     */
    function &chain(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Excpected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $newFilter = &StripPhpComments::newStripPhpComments($reader);

        return $newFilter;
    }
}

?>
