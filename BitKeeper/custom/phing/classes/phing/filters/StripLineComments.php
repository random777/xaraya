<?php

/*
 * $Id: StripLineComments.php,v 1.5 2003/02/25 17:38:30 openface Exp $  
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

import('phing.filters.BaseParamFilterReader');

/*
 * This filter strips line comments.
 *
 * Example:
 *
 * <pre><striplinecomments>
 *   <comment value="#"/>
 *   <comment value="--"/>
 *   <comment value="REM "/>
 *   <comment value="rem "/>
 *   <comment value="//"/>
 * </striplinecomments></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.StripLineComments">
 *   <param type="comment" value="#"/>
 *   <param type="comment" value="--"/>
 *   <param type="comment" value="REM "/>
 *   <param type="comment" value="rem "/>
 *   <param type="comment" value="//"/>
 * </filterreader></pre>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.5 $ $Date: 2003/02/25 17:38:30 $
 * @access    public
 * @see       BaseParamFilterReader
 * @package   phing.filters
 */
class StripLineComments extends BaseParamFilterReader {
    var	$_COMMENTS_KEY = "comment";	// Parameter name for the comment prefix.
    var	$_comments     = array();	// Array that holds the comment prefixes.
    var	$_line         = null;		// The line that has been read ahead.

    /*
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
    */
    function StripLineComments() {
        parent::BaseParamFilterReader();
    }

    /*
     * Creates a new filtered reader.
     *
     * @param reader A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
    */
    function &newStripLineComments(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Excpected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $o = new StripLineComments();
        $o->setReader($reader);

        return $o;
    }
	
    /**
     * Returns stream only including
     * lines from the original stream which don't start with any of the 
     * specified comment prefixes.
     * 
     * @return mixed the resulting stream, or -1
     *         if the end of the resulting stream has been reached.
     * 
     * @exception IOException if the underlying stream throws an IOException
     *            during reading     
     */
    function read() {
	
        if ( !$this->getInitialized() ) {
            $this->_initialize();
            $this->setInitialized(true);
        }
		
		$buffer = $this->in->read();
		
		if ($buffer === -1) {
		    return -1;
		}
		
		$lines = explode("\n", $buffer);		
		$filtered = array();	
			
		$commentsSize = count($this->_comments);
		
		foreach($lines as $line) {			
			for($i = 0; $i < $commentsSize; $i++) {
			    $comment = $this->_comments[$i]->getValue();
			    if ( StrStartsWith($comment, ltrim($line)) ) {
			        $line = null;
			        break;
			    }
			}
			if ($line !== null) {
			    $filtered[] = $line;
			}
		}
				
		$filtered_buffer = implode("\n", $filtered);	
		return $filtered_buffer;
    }
	
    /**
	 * [For reference only.  Chain system is using new read() method.] 
     * Returns the next character in the filtered stream, only including
     * lines from the original stream which don't start with any of the 
     * specified comment prefixes.
     * 
     * @return the next character in the resulting stream, or -1
     *         if the end of the resulting stream has been reached
     * 
     * @exception IOException if the underlying stream throws an IOException
     *            during reading     
    */
    function readChar() {
        if ( !$this->getInitialized() ) {
            $this->_initialize();
            $this->setInitialized(true);
        }

        $ch = -1;

        if ( $this->_line !== null ) {
            $ch = substr($this->_line, 0, 1);
            if ( strlen($this->_line) === 1 ) {
                $this->_line = null;
            } else {
                $this->_line = substr($this->_line, 1);
            }
        } else {
            $this->_line = $this->readLine();
            if ( $this->_line === null ) {
                $ch = -1;
            } else {
                $commentsSize = count($this->_comments);
                for($i = 0 ; $i<$commentsSize ; $i++) {
                    $comment = $this->_comments[$i]->getValue();
                    if ( StrStartsWith($comment, $this->_line) ) {
                        $this->_line = null;
                        break;
                    }
                }
                return $this->readChar();
            }
        }

        return $ch;
    }

    /*
     * Adds a <code>comment</code> element to the list of prefixes.
     * 
     * @return comment The <code>comment</code> element added to the
     *                 list of comment prefixes to strip.
    */
    function &createComment() {
        $num = array_push($this->_comments, new Comment());
        return $this->_comments[$num-1];
    }

    /*
     * Sets the list of comment prefixes to strip.
     * 
     * @param comments A list of strings, each of which is a prefix
     *                 for a comment line. Must not be <code>null</code>.
    */
    function setComments($lineBreaks) {
        if ( !is_array($lineBreaks) ) {
            throw (new RuntimeException("Excpected 'array', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $this->_comments = $lineBreaks;
    }

    /*
     * Returns the list of comment prefixes to strip.
     * 
     * @return the list of comment prefixes to strip.
    */
    function getComments() {
        return $this->_comments;
    }

    /*
     * Creates a new StripLineComments using the passed in
     * Reader for instantiation.
     * 
     * @param reader A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return a new filter based on this configuration, but filtering
        *         the specified reader
        */
    function &chain(&$reader) {
        $newFilter = &StripLineComments::newStripLineComments($reader);
        $newFilter->setComments($this->getComments());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /*
     * Parses the parameters to set the comment prefixes.
    */
    function _initialize() {
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i = 0 ; $i<count($params) ; $i++) {
                if ( $this->_COMMENTS_KEY === $params[$i]->getType() ) {
                    $comment = new Comment();
                    $comment->setValue($params[$i]->getValue());
                    array_push($this->_comments, $comment);
                }
            }
        }
    }
}

/*
 * The class that holds a comment representation.
*/
class Comment {
    var	$_value;	// The prefix for a line comment.

    /*
     * Sets the prefix for this type of line comment.
     *
     * @param comment The prefix for a line comment of this type.
     *                Must not be <code>null</code>.
     */
    function setValue($value) {
        $this->_value = (string) $value;
    }

    /*
     * Returns the prefix for this type of line comment.
     * 
     * @return the prefix for this type of line comment.
    */
    function getValue() {
        return $this->_value;
    }
}
?>
