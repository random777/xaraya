<?php

/*
 * $Id: TabToSpaces.php,v 1.6 2003/02/25 17:38:30 openface Exp $  
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
 * Converts tabs to spaces.
 *
 * Example:
 *
 * <pre><tabtospaces tablength="8"></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.TabsToSpaces">
 *   <param name="tablength" value="8">
 * </filterreader></pre>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.6 $ $Date: 2003/02/25 17:38:30 $
 * @access    public
 * @see       BaseParamFilterReader
 * @package   phing.filters
 */
class TabToSpaces extends BaseParamFilterReader {
	/**
	 * The default tab length. 
	 * @var integer
	 */
    var	$_DEFAULT_TAB_LENGTH = 8;
	
	/**
	 * Parameter name for the length of a tab.
	 * @var string
	 */
    var	$_TAB_LENGTH_KEY     = "tablength";
	
	/**
	 * Tab length in this filter.
	 * @var integer
	 */  
    var	$_tabLength          = 8;
	
	/**
	 * [deprecated] The number of spaces still to be read to represent the last-read tab.
	 * @var integer
	 */  
    var	$_spacesRemaining    = 0;			// 

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function TabToSpaces() {
        parent::BaseParamFilterReader();

        $this->_tabLength = $this->_DEFAULT_TAB_LENGTH;
    }

    /**
     * Creates a new filtered reader.
     *
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A TabToSpaces object filtering the underlying
     *                stream.
     */
    function &newTabToSpaces(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Excpected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $o = new TabToSpaces();
        $o->setReader($reader);

        return $o;
    }

	/**
     * Returns stream after converting tabs to the specified number of spaces.
     * 
     * @return the resulting stream, or -1
     *         if the end of the resulting stream has been reached
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
		
		$buffer = str_replace("\t", str_repeat(' ', $this->_tabLength), $buffer);
		
		return $buffer;		
    }
	
    /**
	 * [Deprecated. For reference only.  Chain system uses new read() method.] 
     * Returns the next character in the filtered stream, converting tabs
     * to the specified number of spaces.
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

        if ( $this->_spacesRemaining > 0 ) {
            $this->_spacesRemaining--;
            $ch = " ";
        } else {
            $ch = $this->in->readChar();
            if ( $ch === "\t" ) {
                $this->_spacesRemaining = $this->_tabLength - 1;
                $ch = " ";
            }
        }

        return $ch;
    }

    /**
     * Sets the tab length.
     * 
     * @param tabLength the number of spaces to be used when converting a tab.
     */
    function setTablength($tabLength) {
        $this->_tabLength = (int) $tabLength;
    }

    /**
     * Returns the tab length.
     * 
     * @return the number of spaces used when converting a tab
     */
    function getTablength() {
        return $this->_tabLength;
    }

    /**
     * Creates a new TabsToSpaces using the passed in
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

        $newFilter = &TabToSpaces::newTabToSpaces($reader);
        $newFilter->setTablength($this->getTablength());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /**
     * Parses the parameters to set the tab length.
     */
    function _initialize() {
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i = 0 ; $i<count($params) ; $i++) {
                if ( $this->_TAB_LENGTH_KEY === $params[$i]->getName() ) {
                    $this->_tabLength = (int) $params[$i]->getValue();
                    break;
                }
            }
        }
    }
}

?>
