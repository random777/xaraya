<?php

/*
 * $Id: StripLineBreaks.php,v 1.6 2003/02/25 17:38:30 openface Exp $  
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
 * Filter to flatten the stream to a single line.
 * 
 * Example:
 *
 * <pre><striplinebreaks/></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.StripLineBreaks"/></pre>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.6 $ $Date: 2003/02/25 17:38:30 $
 * @access    public
 * @see       BaseParamFilterReader
 * @package   phing.filters
 */
class StripLineBreaks extends BaseParamFilterReader {
	/**
	 * Default line-breaking characters.
	 * @var string
	 */
    var	$_DEFAULT_LINE_BREAKS = "\r\n";
	
	/**
	 * Parameter name for the line-breaking characters parameter.
	 * @var string
	 */
    var	$_LINES_BREAKS_KEY = "linebreaks";
	
	/**
	 * The characters that are recognized as line breaks.
	 * @var string
	 */ 
    var	$_lineBreaks = null;			

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function StripLineBreaks() {
        parent::BaseParamFilterReader();

        $this->_lineBreaks = $this->_DEFAULT_LINE_BREAKS;
    }

    /**
     * Creates a new filtered reader.
     *
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A StripLineBreaks object filtering the underlying
     *                stream.	 
     */
    function &newStripLineBreaks(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Excpected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $o = new StripLineBreaks();
        $o->setReader($reader);

        return $o;
    }
	
    /**
     * Returns the filtered stream, only including
     * characters not in the set of line-breaking characters.
     * 
     * @return mixed	the resulting stream, or -1
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
		if($buffer === -1) {
			return -1;
		}
		
		$buffer = preg_replace("/[".$this->_lineBreaks."]/", '', $buffer);		   

        return $buffer;
    }
	
    /**
	 * [Deprecated. For reference only:  chain system uses new read().] 
     * Returns the next character in the filtered stream, only including
     * characters not in the set of line-breaking characters.
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

        $ch = $this->in->readChar();
        while ( $ch !== -1 ) {
            if ( strchr($this->_lineBreaks, $ch) === false ) {
                break;
            } else {
                $ch = $this->in->readChar();
            }
        }

        return $ch;
    }

    /**
     * Sets the line-breaking characters.
     * 
     * @param string $lineBreaks A String containing all the characters to be
     *                   considered as line-breaking.
     */
    function setLineBreaks($lineBreaks) {
        $this->_lineBreaks = (string) $lineBreaks;
    }

	/**
	 * Gets the line-breaking characters.
	 * 
	 * @return string A String containing all the characters that are considered as line-breaking.
	 */ 
    function getLineBreaks() {
        return $this->_lineBreaks;
    }

    /**
     * Creates a new StripLineBreaks using the passed in
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

        $newFilter = &StripLineBreaks::newStripLineBreaks($reader);
        $newFilter->setLineBreaks($this->getLineBreaks());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /**
     * Parses the parameters to set the line-breaking characters.
     */
    function _initialize() {
        $userDefinedLineBreaks = null;
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i = 0 ; $i<count($params) ; $i++) {
                if ( $this->_LINE_BREAKS_KEY === $params[$i]->getName() ) {
                    $userDefinedLineBreaks = $params[$i]->getValue();
                    break;
                }
            }
        }

        if ( $userDefinedLineBreaks !== null ) {
            $this->_lineBreaks = $userDefinedLineBreaks;
        }
    }
}

?>
