<?php

/*
 * $Id: PrefixLines.php,v 1.6 2003/02/25 17:38:30 openface Exp $
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
 * Attaches a prefix to every line.
 *
 * Example:
 * <pre><prefixlines prefix="Foo"/></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.PrefixLines">
 *  <param name="prefix" value="Foo"/>
 * </filterreader></pre>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.6 $ $Date: 2003/02/25 17:38:30 $
 * @access    public
 * @see       FilterReader
 * @package   phing.filters
*/
class PrefixLines extends BaseParamFilterReader {

	/**
	 * Parameter name for the prefix.
	 * @var string
	 */ 
    var	$_PREFIX_KEY = "lines";
	
	/**
	 * The prefix to be used.
	 * @var string
	 */ 
    var	$_prefix = null;
	
	/**
	 * [Deprecated] Data that must be read from, if not null.
	 * @var string
	 */ 
    var	$_queuedData = null;

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function PrefixLines() {
        parent::BaseParamFilterReader();
    }

    /**
     * Creates a new filtered reader.
     *
     * @param reader A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A PrefixLines object filtering the underlying
     *                stream.
     */
    function &newPrefixLines(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Excpected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $o = new PrefixLines();
        $o->setReader($reader);

        return $o;
    }

	/**
	 * Adds a prefix to each line of input stream and returns resulting stream.
	 * 
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
		$filtered = array();		
		
		foreach($lines as $line) {
			$line = $this->_prefix . $line;
			$filtered[] = $line;
		}
				
		$filtered_buffer = implode("\n", $filtered);	
		return $filtered_buffer;
	}
	
    /**
     * [Deprecated. For reference only; Chain system uses new read() method.]
	 * Returns the next character in the filtered stream. One line is read
     * from the original input, and the prefix added. The resulting
     * line is then used until it ends, at which point the next original line
     * is read, etc.
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

        if ( $this->_queuedData !== null && strlen($this->_queuedData) == 0 ) {
            $this->_queuedData = null;
        }

        if ( $this->_queuedData !== null ) {
            $ch = substr($this->_queuedData, 0, 1);
            $this->_queuedData = substr($this->_queuedData, 1);
            if ( strlen($this->_queuedData) === 0 ) {
                $this->_queuedData = null;
            }
        } else {
            $this->_queuedData = $this->readLine();
            if ( $this->_queuedData === null ) {
                $ch = -1;
            } else {
                if ( $this->_prefix !== null ) {
                    $this->_queuedData = $this->_prefix.$this->_queuedData;
                }

                return $this->readChar();
            }
        }

        return $ch;
    }

    /**
     * Sets the prefix to add at the start of each input line.
     * 
     * @param string $prefix The prefix to add at the start of each input line.
     *               May be <code>null</code>, in which case no prefix
     *               is added.
     */
    function setPrefix($prefix) {
        $this->_prefix = (string) $prefix;
    }

    /**
     * Returns the prefix which will be added at the start of each input line.
     * 
     * @return string The prefix which will be added at the start of each input line
     */
    function getPrefix() {
        return $this->_prefix;
    }

    /**
     * Creates a new PrefixLines filter using the passed in
     * Reader for instantiation.
     * 
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return object A new filter based on this configuration, but filtering
     *         the specified reader
     */
    function &chain(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Excpected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $newFilter = &PrefixLines::newPrefixLines($reader);
        $newFilter->setPrefix($this->getPrefix());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /**
     * Initializes the prefix if it is available from the parameters.
     */
    function _initialize() {
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i = 0 ; $i<count($params) ; $i++) {
                if ( $this->_PREFIX_KEY = $params[$i]->getName() ) {
                    $this->_prefix = (string) $params[$i]->getValue();
                    break;
                }
            }
        }
    }
}

?>
