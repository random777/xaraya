<?php
/*
 * $Id: BufferedReader.php,v 1.7 2003/06/04 12:22:36 purestorm Exp $
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

import("phing.system.io.Reader");

/*
 * Convenience class for reading files.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @version   $Revision: 1.7 $ $Date: 2003/06/04 12:22:36 $
 * @access    public
 * @see       FilterReader
 * @package   phing.system.io
*/
class BufferedReader extends Reader {

    var	$_bufferSize = 0;
    var	$_buffer     = null;
    var	$_bufferPos  = 0;
	
	/**
	 * 
	 * @param object $reader The reader (e.g. FileReader).
	 * @param integer $buffsize The size of the buffer we should use for reading files.
	 * 							A large buffer ensures that most files (all scripts?) are parsed in 1 buffer.
	 */	 
    function BufferedReader(&$reader, $buffsize = 65536) {
        $this->setReader($reader);
        $this->_bufferSize = $buffsize;
    }

	/**
	 * Reads and returns $_bufferSize chunk of data.
	 * @return mixed buffer or -1 if EOF.
	 */
    function read()
    {
    	if ( ($data = $this->in->read('', 0, $this->_bufferSize)) !== -1 ) {
			$notValidPart = strrchr($data, "\n");
			$notValidPartSize = strlen($notValidPart);
		
			if ( $notValidPartSize > 1 ) {
				// Block doesn't finish on a EOL
				// Find the last EOL and forgot all following stuff
				$dataSize = strlen($data);
				$validSize = $dataSize - $notValidPartSize + 1;
			
				$data = substr($data, 0, $validSize);

				// Rewind to the begining of the forgotten stuff.
				$this->in->skip(-$notValidPartSize+1);
			}
		}

		return $data;
    }

    function readLine() {
        $line = null;
        while ( ($ch = $this->_getNextChar()) !== -1 ) {
            if ( $ch === "\n" ) {
                break;
            }
            $line .= $ch;
        }

        // Warning : Not consider an empty line as an EOF
        if ( $line === null && $ch !== -1 )
            return "";

        return $line;
    }
	
	/**
	 * Reads a single char from the reader.
	 * @return string single char or -1 if EOF.
	 */
    function readChar()	{
        return $this->_getNextChar();
    }

    function _getNextChar() {
        // It seems non-buffered I/O are a bit faster ... :-/
        // Perhaps its due to my poor machine (PII/300MHz).
        // Feel free to test with buffered I/O and send me results !
        return $this->in->read();

        /*

        // Here is the buffered I/O code ...

        if ( $this->_buffer === null ) {
        	// Buffer is empty, fill it ...
        	$read = $this->in->read("", 0, $this->_bufferSize);
        	if ( $read === -1 )
        		$ch = -1;
        	else {
        		$this->_buffer = $read;
        		return $this->_getNextChar();
        	}
        } else {
        	// Get next buffered char ...
        	$ch = $this->_buffer{$this->_bufferPos};
        	$this->_bufferPos++;
        	if ( $this->_bufferPos >= strlen($this->_buffer) ) {
        		$this->_buffer = null;
        		$this->_bufferPos = 0;
        	}
        }

        return $ch; */
    }
	
	/**
	 * Returns whether eof has been reached in stream.
	 * This is important, because filters may want to know if the end of the file (and not just buffer)
	 * has been reached.
	 * @return boolean
	 */ 
	function eof() {
		return $this->in->eof();
	}
	
}
?>
