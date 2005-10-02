<?php
// {{{ Header
/*
 * -File       $Id: FileWriter.php,v 1.9 2003/03/26 21:53:10 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2002, binarycloud
 * -Author     Charlie Killian, <charlie@tizac.com>
 * -Author     jason hines <jason@greenhell.com>
 */
// }}}

import("phing.system.io.File");
import("phing.system.io.Writer");
/**
 * Convenience class for reading files. The constructor of this
 *
 * @package   phing.system.io
 */

class FileWriter extends Writer {

    var $file = null;
    var $fd = null;

    function FileWriter($file, $append = false, $exclusive = false) {
        if (isInstanceOf($file, 'File')) {
            $this->file = $file;
        }
        elseif (is_string($file)) {
            $this->FileWriter(new File($file));
        }
        else {
            die("IllegalArgumentException");
        }

        parent::Writer($this);
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
        if ($this->fd === null) {
            $this->fd = @fopen($this->file->getPath(), "wb");
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

    function writeBuffer(&$buffer) {

        if (!$this->file->canWrite()) {
            //exception
        }

        $this->open();
        $result = @fwrite($this->fd, $buffer);
        $this->close();

        if ($result === -1) {
            //exception
        } else {
            return true;
        }

    }

    function write($buf, $off = null, $len = null) {
        if ( $off === null && $len === null )
            $to_write = $buf;
        else
            $to_write = substr($buf, $off, $len);

        $this->open();
        $result = @fwrite($this->fd, $to_write);

        if ( $result === -1 ) {
            // exception
        } else {
            return true;
        }
    }


    // {{{ Write()

    /**
     * Write() writes a file and makes directories in path if they don't
     * exist.
     *
     * @param	file	String. Path and/or name of file to create.
     * @param	rBuffer	Reference. Contents to write.
     * @param	parents	Boolean. Create parent directories if they don't exist.
     * @param	mode	Int. The mode (permissions) of the new directories.
     *					If using octal add leading 0. eg. 0777. Mode is
     *					affect by the umask system setting.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */
    /*
        function write(&$rBuffer, $parents = true, $mode = 0777) {
            $Logger =& System::GetLogger();
            // If  already exists OR parents=FALSE. Write file and return.
            if ($this->file->isFile() OR FALSE === $parents) {
                $error = $this->_write($rBuffer);
                if (Err::CheckError($error)) { // error.
                    $msg = "FileSystem::Write() FAILED. Cannot Write() $file. ". $error->GetMessage();
                    throw (new RuntimeException($msg));
                }
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::Write() SUCCESS. $file.");
                return TRUE;
            }
     
            // Throw a warning if mode is 0. PHP converts illegal octal numbers to
            // 0 so 0 might not be what the user intended.
     
            if ($mode == 0) {
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::Write() WARNING. Creating a directory with permissions of 0. Is this what you wanted? Possible out of range octal number for mode.");
            }
     
            $str_mode = decoct($mode); // Show octal in messages.
     
            // Make path.
            $error = $this->file->mkdirs();
     
            if (Err::CheckError($error)) { // error.
                $msg = "FileSystem::Write() FAILED. Cannot Write() $file. ". $error->GetMessage();
                throw (new RuntimeException($msg));
            }
     
            if (!$this->file->canWrite()) { // error.
     
                $msg = "FileSystem::Write() FAILED. Cannot Write() $file. ". $error->GetMessage();
                $Logger->Log(PH_LOG_ERROR, $msg);
                throw (new RuntimeException($msg));
            }
     
            // Worked. Log and return TRUE.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Write() SUCCESS ". $this->file->toString(). ". Parent directories mode $str_mode.");
            return TRUE;
        }
        // }}}
        // {{{ _write()
    */
    /**
     * _write the passed buffer to filename. Overwrites existing file if any.
     *
     * @param	file	Path and/or name of file to write.
     * @param	rBuffer	Reference. String to write.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function _write(&$rBuffer) {
        $Logger =& System::GetLogger();
        $fp = @fopen($file->getPath(), "wb");  	// b is for binary and used on Windows
        // ignored on *nix.
        if (FALSE === $fp) { // fopen FAILED.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::_write() FAILED. Cannot fopen $file. $php_errormsg";
            throw (new RuntimeException($msg));
        } else { // fopen worked. Log and try to lock it.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::_write() fopen'd $file.");
            if (FALSE) { // Locks don't seem to work on windows??? HELP!!!!!!!!!
                //if (FALSE === @flock($fp, LOCK_EX)) { // FAILED.
                // Add error from php to end of log message. $php_errormsg.
                $msg = "FileSystem::_write() FAILED. Cannot acquire flock on $file. $php_errormsg";
                throw (new RuntimeException($msg));
            } else { // Write file.
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::_write() flock'd $file.");
                if (-1 === @fwrite($fp, $rBuffer)) { // FAILED.
                    // Add error from php to end of log message. $php_errormsg.
                    $msg = "FileSystem::_write() FAILED. Cannot fwrite $file. $php_errormsg";
                    $Logger->Log(PH_LOG_ERROR, $msg);
                    throw (new RuntimeException($msg));
                } else { // Close.
                    $Logger->Log(PH_LOG_DEBUG, "FileSystem::_write() wrote $file.");
                    if (FALSE === @fclose($fp)) { // FAILED.
                        // Add error from php to end of log message. $php_errormsg.
                        $msg = "FileSystem::_write() FAILED. Cannot fclose $file. $php_errormsg";
                        $Logger->Log(PH_LOG_ERROR, $msg);
                        throw (new RuntimeException($msg));
                    } else {
                        $Logger->Log(PH_LOG_DEBUG, "FileSystem::_write() fread $file.");
                        $Logger->Log(PH_LOG_DEBUG, "FileSystem::_write() fclose'd $file.");
                        $Logger->Log(PH_LOG_DEBUG, "FileSystem::_write() $file.");
                        return TRUE;
                    } // End fclose if
                } // End fwrite if
            } // End flock if
        } // End fopen if
    }
    // }}}
}
?>
