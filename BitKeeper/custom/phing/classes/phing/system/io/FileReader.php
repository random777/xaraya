<?php
/*
 * $Id: FileReader.php,v 1.17 2003/06/04 12:22:36 purestorm Exp $
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

import("phing.system.lang.RuntimeException");
import("phing.system.io.File");
import("phing.system.io.Reader");

/**
 * Convenience class for reading files. The constructor of this
 *  @package   phing.system.io
 */

class FileReader extends Reader {

    var $file = null;
    var $fd   = null;

    var $currentPosition       = 0;
    var $mark				   = 0;

    function FileReader($file, $exclusive = false) {
        $this->setReader($this);

        if (isInstanceOf($file, 'File')) {
            $this->file = $file;
        }
        elseif (is_string($file)) {
            $this->file = new File($file);
        }
        else {
            // will terminate exection
            throw (new RuntimeException("Illegal argument type fed to method") , __FILE__, __LINE__);
            System::halt(-1);
        }
    }

    function skip($n) {
        $this->open();

        $start = $this->currentPosition;

        $ret = @fseek($this->fd, $n, SEEK_CUR);
        if ( $ret === -1 )
            return -1;

        $this->currentPosition = ftell($this->fd);

        if ( $start > $this->currentPosition )
            $skipped = $start - $this->currentPosition;
        else
            $skipped = $this->currentPosition - $start;

        return $skipped;
    }
	
    function read($cbuf = null, $off = null, $len = null) {
        $this->open();
        if (feof($this->fd)) {
            return -1;
        }

        // Compute length to read
        $length = ($len === null) ? (1) : ($len);

        // Read data
        $data = fread($this->fd, $length);
        $this->currentPosition = ftell($this->fd);

        $out    = substr($cbuf, 0, $off);
        $out   .= $data;

        return $out;
    }
	
    function mark($n = null) {
        $this->mark = $this->currentPosition;
    }

    function reset() {
        fseek($this->fd, SEEK_SET, $this->mark);
        $this->mark = 0;
        //		rewind($this->fd);
    }

    function close() {
        if ($this->fd === null) {
            return true;
        }

        if (false === @fclose($this->fd)) {
            // FAILED.
            $msg = "Cannot fclose ".$this->file->toString(). " $php_errormsg";
            throw (new IOException($msg), __FILE__, __LINE__);
            return;
        } else {
            $this->fd = null;
            return true;
        }
    }

    function open() {
        global	$php_errormsg;

        if ($this->fd === null) {
            $this->fd = @fopen($this->file->getPath(), "rb");
        }

        if ($this->fd === false) {
            // fopen FAILED.
            // Add error from php to end of log message. $php_errormsg.
            $msg = "Cannot fopen ".$this->file->getPath()." $php_errormsg";
            throw (new IOException($msg), __FILE__, __LINE__);
            return;
        }

        if (false) {
            // Locks don't seem to work on windows??? HELP!!!!!!!!!
            // if (FALSE === @flock($fp, LOCK_EX)) { // FAILED.
            $msg = "Cannot acquire flock on $file. $php_errormsg";
            throw (new IOException($msg), __FILE__, __LINE__);
            return;
        }


        return true;
    }

	/**
	 * Whether eof has been reached with stream.
	 * @return boolean
	 */
	function eof() {
		return feof($this->fd);
	}
	 
    /**
     * Reads a entire file and stores the data in the variable
     * passed by reference.
     *
     * @param	string $file	String. Path and/or name of file to read.
     * @param	object $rBuffer	Reference. Variable of where to put contents.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */
    function readInto(&$rBuffer) {

        $this->open();

        $fileSize = $this->file->length();
        if ($fileSize === false) {
            $msg = "Cannot get filesize of ".$this->file->toString()." $php_errormsg";
            throw (new IOException($msg), __FILE__, __LINE__);
            return;
        }
        $rBuffer = @fread($this->fd, $fileSize);
        $this->close();
    }
}
?>
