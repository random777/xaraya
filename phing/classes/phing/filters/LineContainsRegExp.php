<?php
/*
 * $Id: LineContainsRegExp.php,v 1.7 2003/02/25 17:38:30 openface Exp $
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
import('phing.types.RegularExpression');

/**
 * Filter which includes only those lines that contain the user-specified
 * regular expression matching strings.
 *
 * Example:
 * <pre><linecontainsregexp>
 *   <regexp pattern="foo*">
 * </linecontainsregexp></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.LineContainsRegExp">
 *    <param type="regexp" value="foo*"/>
 * </filterreader></pre>
 *
 * This will fetch all those lines that contain the pattern <code>foo</code>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.7 $ $Date: 2003/02/25 17:38:30 $
 * @access    public
 * @see       FilterReader
 * @package   phing.filters
*/
class LineContainsRegExp extends BaseParamFilterReader {

	/**
	 * Parameter name for regular expression.
	 * @var string
	 */ 
    var	$_REGEXP_KEY = "regexp";
	
	/**
	 * Regular expressions that are applied against lines.
	 * @var array
	 */ 
    var	$_regexps = array();
	
	/**
	 * [Deprecated] Line.
	 * @var string
	 */ 
    var	$_line = null;

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function LineContainsRegExp() {
        parent::BaseParamFilterReader();
    }

    /**
     * Creates a new filtered reader.
     *
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A LineContainsRegExp object filtering the
     *                underlying stream.
     */
    function &newLineContainsRegExp(&$reader) {
        // type check, error must never occur, bad code of it does
        if ( !is_a($reader, "Reader") ) {
            throw (new RuntimeException("Expected object of type 'Reader' got something else"), __FILE__, __LINE__);
            System::halt(-1);
            return;
        }

        $o = new LineContainsRegExp();
        $o->setReader($reader);

        return $o;
    }

	/**
	 * Returns all lines in a buffer that contain specified strings.
	 * @return mixed buffer, -1 on EOF
	 */
	function read()
	{
        if ( !$this->getInitialized() ) {
            $this->_initialize();
            $this->setInitialized(true);
        }
		
		$buffer = $this->in->read();
		
		if ($buffer === -1) {
		    return -1;
		}
		
		$lines = explode("\n", $buffer);		
		$matched = array();		
		
		$regexpsSize = count($this->_regexps);
		foreach($lines as $line) {	
	         for($i = 0 ; $i<$regexpsSize ; $i++) {
                    $regexp = &$this->_regexps[$i];
                    $re = &$regexp->getRegexp($this->getProject());
                    $matches = $re->matches($line);
                    if ( !$matches ) {
                        $line = null;
                        break;
                    }
            }			
			if($line !== null) {
				$matched[] = $line;
			}				
		}		
		$filtered_buffer = implode("\n", $matched);	
		return $filtered_buffer;
	}
	
    /**
	 * [Deprecated. For reference only; chain system uses new read() method.]
     * Returns the next character in the filtered stream, only including
     * lines from the original stream which match all of the specified
     * regular expressions.
     * 
     * @return the next character in the resulting stream, or -1
     * if the end of the resulting stream has been reached
     * 
     * @exception IOException if the underlying stream throws an IOException
     * during reading     
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
                $regexpsSize = count($this->_regexps);
                for($i = 0 ; $i<$regexpsSize ; $i++) {
                    $regexp = $this->_regexps[$i];
                    $re = &$regexp->getRegexp($this->getProject());
                    $matches = $re->matches($this->_line);
                    if ( !$matches ) {
                        $this->_line = null;
                        break;
                    }
                }

                return $this->readChar();
            }
        }

        return $ch;
    }

    /**
     * Adds a <code>regexp</code> element.
     * 
     * @return object regExp The <code>regexp</code> element added. 
     */
    function &createRegexp() {
        $num = array_push($this->_regexps, new RegularExpression());
        return $this->_regexps[$num-1];
    }

    /**
     * Sets the vector of regular expressions which must be contained within 
     * a line read from the original stream in order for it to match this 
     * filter.
     * 
     * @param regexps An array of regular expressions which must be contained 
     *                within a line in order for it to match in this filter. Must not be 
     *                <code>null</code>.
     */
    function setRegexps($regexps) {
        // type check, error must never occur, bad code of it does
        if ( !is_array($regexps) ) {
            throw (new RuntimeException("Excpected an 'array', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $this->_regexps = $regexps;
    }

    /**
     * Returns the array of regular expressions which must be contained within 
     * a line read from the original stream in order for it to match this 
     * filter.
     * 
     * @return array The array of regular expressions which must be contained within 
     *         a line read from the original stream in order for it to match this 
     *         filter. The returned object is "live" - in other words, changes made to 
     *         the returned object are mirrored in the filter.
     */
    function &getRegexps() {
        return $this->_regexps;
    }

    /**
     * Creates a new LineContainsRegExp using the passed in
     * Reader for instantiation.
     * 
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return object A new filter based on this configuration, but filtering
     *         the specified reader
     */
    function &chain(&$reader) {
        $newFilter = &LineContainsRegExp::newLineContainsRegExp($reader);
        $newFilter->setRegexps($this->getRegexps());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /**
     * Parses parameters to add user defined regular expressions.
     */
    function _initialize() {
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i = 0 ; $i<count($params) ; $i++) {
                if ( $this->_REGEXP_KEY === $params[$i]->getType() ) {
                    $pattern = $params[$i]->getValue();
                    $regexp = new RegularExpression();
                    $regexp->setPattern($pattern);
                    array_push($this->_regexps, $regexp);
                }
            }
        }
    }
}

?>
