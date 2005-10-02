<?php

/*
 * $Id: ExpandProperties.php,v 1.6 2003/02/24 22:36:27 openface Exp $
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
 * Expands Phing Properties, if any, in the data.
 * <p>
 * Example:<br>
 * <pre><expandproperties/></pre>
 * Or:
 * <pre><filterreader classname="phing.filters.ExpandProperties'/></pre>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.6 $ $Date: 2003/02/24 22:36:27 $
 * @access    public
 * @see       BaseFilterReader
 * @package   phing.filters
 */
class ExpandProperties extends BaseFilterReader {

	/**
	 * [Deprecated] Data that must be read from, if not null.
	 * @var string
	 */ 
    var $_queuedData = null;

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function ExpandProperties() {
        parent::BaseFilterReader();
    }

    /**
     * Creates a new filtered reader.
     *
     * @param in A Reader object providing the underlying stream.
     *           Must not be <code>null</code>.
     *
     * @return object An ExpandProperties object filtering the
     *                underlying stream.
     */
    function &newExpandProperties(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, "Reader")) {
            throw (new RuntimeException("Expected object of type 'Reader' got something else"), __FILE__, __LINE__);
            System::halt(-1);
            return;
        }
        $o = new ExpandProperties();
        $o->setReader($reader);

        return $o;
    }

	/**
	 * Returns the filtered stream. 
	 * The original stream is first read in fully, and the Phing properties are expanded.
     * 
     * @return mixed 	the filtered stream, or -1 if the end of the resulting stream has been reached.
     * 
     * @exception IOException if the underlying stream throws an IOException
     * during reading
	 */
	function read() {
				
		$buffer = $this->in->read();
		
		if($buffer === -1) {
			return -1;
		}
		
	    $project = &$this->getProject();
		$buffer = ProjectConfigurator::replaceProperties($project, $buffer, $project->getProperties());
		
		return $buffer;
	}

    /**
	 * [Deprecated. For reference only:  used to be read() method.]
     * Returns the next character in the filtered stream. The original
     * stream is first read in fully, and the Phing properties are expanded.
     * The results of this expansion are then queued so they can be read
     * character-by-character.
     * 
     * @return the next character in the resulting stream, or -1
     * if the end of the resulting stream has been reached
     * 
     * @exception IOException if the underlying stream throws an IOException
     * during reading     
    */
    function readChar() {
        $ch = -1;

        if ( $this->_queuedData !== null && strlen($this->_queuedData) === 0 ) {
            $this->_queuedData = null;
        }

        if ( $this->_queuedData !== null ) {
            $ch = substr($this->_queuedData, 0, 1);
            $this->_queuedData = substr($this->_queuedData, 1);
            if ( strlen($this->_queuedData === 0) ) {
                $this->_queuedData = null;
            }
        } else {
            $this->_queuedData = $this->readFully();
            if ( $this->_queuedData === null ) {
                $ch = -1;
            } else {
                $project = &$this->getProject();
                $this->_queuedData = ProjectConfigurator::replaceProperties($project, $this->_queuedData,$project->getProperties());
                return $this->readChar();
            }
        }

        return $ch;
    }

    /**
     * Creates a new ExpandProperties filter using the passed in
     * Reader for instantiation.
     * 
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return object A new filter based on this configuration, but filtering
     *         the specified reader
     */
    function &chain(&$reader) {
        // type check, error must never occur, bad code if it does
        if (!is_a($reader, "Reader")) {
            throw (new RuntimeException("Toto Expected object of type 'Reader' got something else", __FILE__, __LINE__));
            System::halt(-1);
            return;
        }
        $newFilter = &ExpandProperties::newExpandProperties($reader);
        return $newFilter;
    }
}

?>
