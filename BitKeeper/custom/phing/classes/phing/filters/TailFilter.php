<?php

/*
 * $Id: TailFilter.php,v 1.6 2003/03/01 19:02:38 seasonfive Exp $  
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

/**
 * Reads the last <code>n</code> lines of a stream. (Default is last10 lines.)
 *
 * Example:
 *
 * <pre><tailfilter lines="3" /></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.TailFilter">
 *   <param name="lines" value="3">
 * </filterreader></pre>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @copyright © 2003 seasonfive. All rights reserved
 * @version   $Revision: 1.6 $ $Date: 2003/03/01 19:02:38 $
 * @access    public
 * @see       BaseParamFilterReader
 * @package   phing.filters
 */
class TailFilter extends BaseParamFilterReader {

	/**
	 * Parameter name for the number of lines to be returned.
	 * @var string
	 */
    var	$_LINES_KEY = "lines";
	
	/**
	 * [Deprecated] Number of lines currently read in.
	 * @var integer
	 */ 
    var	$_linesRead = 0;
	
	/**
	 * Number of lines to be returned in the filtered stream.
	 * @var integer
	 */ 
    var	$_lines = 10;
	
	/**
	 * [Deprecated] Array to hold _lines last lines read ahead.
	 * @var array
	 */ 
    var	$_lineBuffer = array();
	
	/**
	 * [Deprecated] Buffer to hold in characters read ahead.
	 * @var string
	 */ 
    var	$_buffer = null;
	
	/**
	 * [Deprecated] The character position that has been returned from the buffer.
	 * @var integer
	 */ 
    var	$_returnedCharPos = -1;
	
	/**
	 * [Deprecated] Whether or not read-ahead been completed.
	 * @var boolean
	 */ 
    var	$_completedReadAhead = false;
	
	/**
	 * [Deprecated] Current index position on the buffer.
	 * @var integer
	 */ 
    var	$_bufferPos = 0;

	/**
	 * The previous to last buffer.
	 * @var string
	 */
	var $_prevBuffer = null;  
	
    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function TailFilter() {
        parent::BaseParamFilterReader();
    }

    /**
     * Creates a new filtered reader.
     *
     * @return reader A Reader object providing the underlying stream.
     *                Must not be <code>null</code>.
     */
    function &newTailFilter(&$reader) {
        $o = new TailFilter();
        $o->setReader($reader);

        return $o;
    }
	
	/**
	 * Returns the last n lines of a file.
	 * @return mixed The filtered buffer or -1 if EOF.
	 */
	function read()
	{
		while ( ($buffer = $this->in->read()) !== -1 ) {
			// Remove the last "\n" from buffer for
			// prevent explode to add an empty cell at
			// the end of array
			$buffer= trim($buffer, "\n");
			
			$lines = explode("\n", $buffer);

			if ( count($lines) >= $this->_lines ) {
				// Buffer have more (or same) number of lines than needed.
				// Fill lineBuffer with the last "$this->_lines" lasts ones.
				$off = count($lines)-$this->_lines;
				$this->_lineBuffer = array_slice($lines, $off);
			} else {
				// Some new lines ...
				// Prepare space for insert these new ones
				$this->_lineBuffer = array_slice($this->_lineBuffer, count($lines)-1);
				$this->_lineBuffer = array_merge($this->_lineBuffer, $lines);
			}
		}

		if ( empty($this->_lineBuffer) )
			$ret = -1;
		else {
			$ret = implode("\n", $this->_lineBuffer);
			$this->_lineBuffer = array();
		}

		return $ret;
	}

    /**
     * Returns the next character in the filtered stream. If the read-ahead
     * has been completed, the next character in the buffer is returned.
     * Otherwise, the stream is read to the end and buffered (with the buffer
     * growing as necessary), then the appropriate position in the buffer is
     * set to read from.
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

        if ( !$this->_completedReadAhead ) {
            $ch = -1;
            $line = null;
            while ( ($ch = $this->in->readChar()) !== -1 ) {

                $line .= $ch;

                if ( $ch === "\n" ) {
                    if ( $this->_lineRead < $this->_lines ) {
                        $this->_lineBuffer[] = $line;
                        $this->_lineRead++;
                    } else {
                        $this->_lineBuffer = array_slice($this->_lineBuffer, 1);
                        $this->_lineBuffer[] = $line;
                    }

                    $line = null;
                }
            }
            $this->_completedReadAhead = true;
            $this->_buffer = implode("", $this->_lineBuffer);
            $this->_returnedCharPos = -1;
        }

        $this->_returnedCharPos++;
        if ( $this->_returnedCharPos >= strlen($this->_buffer) )
            return -1;
        else {
            return $this->_buffer{$this->_returnedCharPos};
        }
    }

    /**
     * Sets the number of lines to be returned in the filtered stream.
     * 
     * @param integer $lines the number of lines to be returned in the filtered stream.
     */
    function setLines($lines) {
        $this->_lines = (int) $lines;
    }

    /**
     * Returns the number of lines to be returned in the filtered stream.
     * 
     * @return integer The number of lines to be returned in the filtered stream.
     */
    function getLines() {
        return $this->_lines;
    }

    /**
     * Creates a new TailFilter using the passed in
     * Reader for instantiation.
     * 
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return object A new filter based on this configuration, but filtering
     *         the specified reader.
     */
    function &chain(&$reader) {
        $newFilter = TailFilter::newTailFilter($reader);
        $newFilter->setLines($this->getLines());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /**
     * Scans the parameters list for the "lines" parameter and uses
     * it to set the number of lines to be returned in the filtered stream.
     */
    function _initialize() {
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i=0, $_i=count($params); $i < $_i; $i++) {
                if ( $this->LINES_KEY = $params[$i]->getName() ) {
                    $this->_lines = (int) $params[$i]->getValue();
                    break;
                }
            }
        }
    }
}

?>
