<?php

/*
 * $Id: BaseFilterReader.php,v 1.6 2003/07/09 06:06:39 purestorm Exp $
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

import('phing.system.io.FilterReader');
import('phing.system.io.StringReader');


/**
 * Base class for core filter readers.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @version   $Revision: 1.6 $ $Date: 2003/07/09 06:06:39 $
 * @access    public
 * @see       FilterReader
 * @package   phing.filters
 */
class BaseFilterReader extends FilterReader {
    var	$_initialized = false;	// Have the parameters passed been interpreted?
    var	$_project     = null;	// The Phing project this filter is part of.

    /**
     * Constructor used by Phing's introspection mechanism.
     * The original filter reader is only used for chaining
     * purposes, never for filtering purposes (and indeed
     * it would be useless for filtering purposes, as it has
     * no real data to filter). ChainedReaderHelper uses
     * this placeholder instance to create a chain of real filters.
     */
    function BaseFilterReader() {
        $dummy = "";
        $this->setReader(new StringReader($dummy));
    }

    /**
     * Creates a new filtered reader.
     *
     * @param object  A Reader object providing the underlying stream.
     *                Must not be <code>null</code>.
     *
     * @return object A FilterReader object.
     *           
     */
    function &newBaseFilterReader(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Excpected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $o = new BaseFilterReader();
        $o->setReader($reader);

        return $o;
    }

    /**
     * Returns the initialized status.
     * 
     * @return boolean whether or not the filter is initialized
     */
    function getInitialized() {
        return $this->_initialized;
    }

    /**
     * Sets the initialized status.
     * 
     * @param boolean $initialized Whether or not the filter is initialized.
     */
    function setInitialized($initialized) {
        $this->_initialized = (boolean) $initialized;
    }

    /**
     * Sets the project to work with.
     * 
     * @param object $project The project this filter is part of. 
     *                Should not be <code>null</code>.
     */
    function setProject(&$project) {
        // type check, error must never occur, bad code of it does
        if (!is_a($project, 'Project')) {
            throw (new RuntimeException("Excpected object of type 'Project' got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $this->_project = $project;
    }

    /**
     * Returns the project this filter is part of.
     * 
     * @return object The project this filter is part of
     */
    function &getProject() {
        return $this->_project;
    }

    /**
     *  Logs a message with the given priority.
     *
     *  @param  string   The message to be logged.
     *  @param  integer  The message's priority at this message should have
     *  @access public
     */
    function log($msg, $level = PROJECT_MSG_INFO) {
        if ($this->_project !== null) {
            $this->_project->log($msg, $level);
        }
    }

    /**
     * Reads characters.
     *
     * @param  cbuf "Destination" buffer to write characters to. 
     *              Must not be <code>null</code>.
     * @param  off  Offset at which to start storing characters.
     * @param  len  Maximum number of characters to read.
     *
     * @return Characters read, or -1 if the end of the stream
     *         has been reached
     *
     * @throws IOException If an I/O error occurs
     */
    function read($cbuf = null, $off = null, $len = null) {
        return $this->in->read((string) $cbuf, (int) $off, (int) $len);
    }

    /**
     * Reads to the end of the stream, returning the contents as a String.
     * 
     * @return the remaining contents of the reader, as a String
     * 
     * @exception IOException if the underlying reader throws one during 
     *            reading
     */
    function readFully() {
        $data = null;
        while ( ($read = $this->in->read("", 0, 8192)) !== -1 ) {
            $data .= $read;
        }

        return $data;
    }

    /**
     * Reads a line of text ending with '\n' (or until the end of the stream).
     * The returned String retains the '\n'.
     * 
     * @return the line read, or <code>null</code> if the end of the
               stream has already been reached
     * 
     * @exception IOException if the underlying reader throws one during 
     *                        reading
     */
    function readLine() {
        $line = null;

        while ( ($ch = $this->in->read()) != -1 ) {
            $line .= $ch;
            if ( $ch === "\n" )
                break;
        }

        return $line;
    }
	
	/**
	 * Returns whether the end of file has been reached with input stream.
	 * @return boolean
	 */ 
	function eof() {
		return $this->in->eof();
	}
}

?>
