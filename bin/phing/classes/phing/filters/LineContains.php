<?php

/*
 * $Id: LineContains.php,v 1.7 2003/02/25 17:38:30 openface Exp $
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
import('phing.filters.BaseFilterReader');

/**
 * Filter which includes only those lines that contain all the user-specified
 * strings.
 *
 * Example:
 *
 * <pre><linecontains>
 *   <contains value="foo">
 *   <contains value="bar">
 * </linecontains></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.LineContains">
 *    <param type="contains" value="foo"/>
 *    <param type="contains" value="bar"/>
 * </filterreader></pre>
 *
 * This will include only those lines that contain <code>foo</code> and
 * <code>bar</code>.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.7 $ $Date: 2003/02/25 17:38:30 $
 * @access    public
 * @see       FilterReader
 * @package   phing.filters
*/
class LineContains extends BaseParamFilterReader {

	/**
	 * The parameter name for the string to match on.
	 * @var string
	 */ 
    var $_CONTAINS_KEY = "contains";

	/**
	 * Array of Contains objects.
	 * @var array
	 */ 
    var $_contains = array();

	/**
	 * [Deprecated] 
	 * @var string
	 */ 
    var $_line = null;

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function LineContains() {
        parent::BaseParamFilterReader();
    }

    /**
     * Creates a new filtered reader.
     *
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A LineContains object filtering the underlying
     *                stream.
     */
    function &newLineContains(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, "Reader")) {
            throw (new RuntimeException("Expected object of type 'Reader' got something else"), __FILE__, __LINE__);
            System::halt(-1);
            return;
        }

        $o = new LineContains();
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
		$containsSize = count($this->_contains);
		
		foreach($lines as $line) {								
	        for($i = 0 ; $i < $containsSize ; $i++) {
	            $containsStr = $this->_contains[$i]->getValue();
	            if ( strstr($line, $containsStr) === false ) {
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
	 * [Deprecated. For reference only, used to be read() method.] 
     * Returns the next character in the filtered stream, only including
     * lines from the original stream which contain all of the specified words.
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
            if ( strlen($this->_line) === 1 )
                $this->_line = null;
            else
                $this->_line = substr($this->_line, 1);
        } else {
            $this->_line = $this->readLine();
            if ( $this->_line === null ) {
                $ch = -1;
            } else {
                $containsSize = count($this->_contains);
                for($i = 0 ; $i < $containsSize ; $i++) {
                    $containsStr = $this->_contains[$i]->getValue();
                    if ( strstr($this->_line, $containsStr) === false ) {
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
     * Adds a <code>contains</code> element.
     *
     * @return contains The <code>contains</code> element added.
     *                  Must not be <code>null</code>.
     */
    function &createContains() {
        $num = array_push($this->_contains, new Contains());
        return $this->_contains[$num-1];
    }

    /**
     * Sets the array of words which must be contained within a line read
     * from the original stream in order for it to match this filter.
     *
     * @param string $contains An array of words which must be contained
     *                 within a line in order for it to match in this filter.
     *                 Must not be <code>null<code>.
     */
    function setContains($contains) {
        // type check, error must never occur, bad code of it does
        if ( !is_array($contains) ) {
            throw (new RuntimeException("Excpected array got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $this->_contains = $contains;
    }

    /**
     * Returns the vector of words which must be contained within a line read
     * from the original stream in order for it to match this filter.
     *
     * @return array The array of words which must be contained within a line read
     *         from the original stream in order for it to match this filter. The
     *         returned object is "live" - in other words, changes made to the
     *         returned object are mirrored in the filter.
     */
    function &getContains() {
        return $this->_contains;
    }

    /**
     * Creates a new LineContains using the passed in
     * Reader for instantiation.
     *
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A new filter based on this configuration, but filtering
     *         the specified reader
     */
    function &chain(&$reader) {
        $newFilter = &LineContains::newLineContains($reader);
        $newFilter->setContains($this->getContains());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /**
     * Parses the parameters to add user-defined contains strings.
     */
    function _initialize() {
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i = 0 ; $i<count($params) ; $i++) {
                if ( $this->_CONTAINS_KEY = $params[$i]->getType() ) {
                    $cont = new Contains();
                    $cont->setValue($params[$i]->getValue());
                    array_push($this->_contains, $cont);
                    break;
                }
            }
        }
    }
}

/**
 * Holds a contains element.
 */
class Contains {

	/**
	 * @var string
	 */ 
    var $_value;
	
	/**
	 * Set 'contains' value.
	 * @param string $contains
	 */ 
    function setValue($contains) {
        $this->_value = (string) $contains;
    }
	
	/**
	 * Returns 'contains' value.
	 * @return string
	 */ 
    function getValue() {
        return $this->_value;
    }
}
?>
