<?php
// {{{ Header
/*
 * -File       $Id: FileSystem.php,v 1.32 2003/04/09 15:58:11 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Tizac
 * -Author     Charlie Killian, charlie@tizac.com
 * -Author     Andreas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.system.lang.System');

// {{{ FileSystem

/**
 * This is an abstract class for platform specific filesystem implementations
 * you have to implement each method in the platform specific filesystem implementation
 * classes Your local filesytem implementation must extend this class.
 * You should also use this class as a template to write your local implementation
 * Some native PHP filesystem specific methods are abstracted here as well. Anyway
 * you _must_ always use this methods via a File object (that by nature uses the
 * *FileSystem drivers to access the real filesystem via this class using natives.
 *
 * FIXME:
 *  - Comments
 *  - Error handling reduced to min fallthrough runtime excetions
 *    more precise errorhandling is done by the File class
 *  @package   phing.system.io
 */

class FileSystem {

    /**
     * Static method to return the FileSystem singelton representing
     * this platform's local filesystem driver.
     */

    function &getFileSystem() {
        static $sFS = null;
        if ($sFS === null) {
            switch(System::getProperty('host.fstype')) {
            case 'UNIX':
                    import('phing.system.io.UnixFileSystem');
                $sFS = new UnixFileSystem();
                break;
            case 'WIN32':
                import('phing.system.io.Win32FileSystem');
                $sFS = new Win32FileSystem();
                break;
            case 'WINNT':
                import('phing.system.io.WinNTFileSystem');
                $sFS = new WinNTFileSystem();
                break;
            default:
                throw (new RuntimeException("Host uses unsupported filesystem, unable to proceed"), __FILE__, __LINE__);
                return;
                break;
            }
        }
        return $sFS;
    }


    /* -- Normalization and construction -- */

    /**
     * Return the local filesystem's name-separator character.
     */
    function getSeparator() {
        die("getSeparator() Not implemented by local fs driver");
    }

    /**
     * Return the local filesystem's path-separator character.
     */
    function getPathSeparator() {
        die("getPathSeparator() Not implemented by local fs driver");
    }

    /**
     * Convert the given pathname string to normal form.  If the string is
     * already in normal form then it is simply returned.
     */
    function normalize($strPath) {
        die("normalize() Not implemented by local fs driver");
    }

    /**
     * Compute the length of this pathname string's prefix.  The pathname
     * string must be in normal form.
     */
    function prefixLength($pathname) {
        die("prefixLength() not implemented by local fs driver");
    }

    /**
     * Resolve the child pathname string against the parent.
    * Both strings must be in normal form, and the result
     * will be a string in normal form.
     */
    function resolve($strParent, $strChild = null) {
        die("resolve() not implemented by local fs driver");
    }

    /**
     * Resolve the given abstract pathname into absolute form.  Invoked by the
     * getAbsolutePath and getCanonicalPath methods in the File class.
     */
    function resolveFile(&$f) {
        die("resolveFile() not implemented by local fs driver");
    }

    /**
     * Return the parent pathname string to be used when the parent-directory
     * argument in one of the two-argument File constructors is the empty
     * pathname.
     */
    function getDefaultParent() {
        die("getDefaultParent() not implemented by local fs driver");
    }

    /**
     * Post-process the given URI path string if necessary.  This is used on
     * win32, e.g., to transform "/c:/foo" into "c:/foo".  The path string
     * still has slash separators; code in the File class will translate them
     * after this method returns.
     */
    function fromURIPath($strPath) {
        die("fromURIPath not implemented by local fs driver");
    }


    /* -- Path operations -- */

    /**
     * Tell whether or not the given abstract pathname is absolute.
     */
    function isAbsolute(&$f) {
        die("isAbsolute() not implemented by local fs driver");
    }

    /** canonicalize filename by checking on disk */
    function canonicalize($strPath) {
        return @realpath($strPath);
    }

    /* -- Attribute accessors -- */

    /* properties for simple boolean attributes */
    var $BA_EXISTS    = 0x01;
    var $BA_REGULAR   = 0x02;
    var $BA_DIRECTORY = 0x04;
    var $BA_HIDDEN    = 0x08;

    /**
     * Return the simple boolean attributes for the file or directory denoted
     * by the given abstract pathname, or zero if it does not exist or some
     * other I/O error occurs.
     */
    function getBooleanAttributes(&$f) {
        die("SYSTEM ERROR method getBooleanAttributes() not implemented by fs driver");
    }

    /**
     * Check whether the file or directory denoted by the given abstract
     * pathname may be accessed by this process.  If the second argument is
     * false, then a check for read access is made; if the second
     * argument is true, then a check for write (not read-write)
     * access is made.  Return false if access is denied or an I/O error
     * occurs.
     */
    function checkAccess(&$f, $write) {
        // we clear stat cache, its expensive to look up from scratch,
        // but we need to be sure
        @clearstatcache();
        if (isInstanceOf($f, 'File')) {

            // Shouldn't this be $f->GetAbsolutePath() ?
            // And why doesn't GetAbsolutePath() work?

            $strPath = (string) $f->getPath();

            // FIXME
            // if file object does denote a file that yet not existst
            // path rights are checked
            if (!@file_exists($strPath) && !is_dir($strPath)) {
                $strPath = $f->getParent();
                if ($strPath === null || !is_dir($strPath)) {
                    $strPath = System::getProperty("user.dir");
                }
                //$strPath = dirname($strPath);
            }

            if (!$write) {
                return (boolean) @is_readable($strPath);
            } else {
                return (boolean) @is_writable($strPath);
            }
        } else {
            throw(new RuntimeException("Illegal argument type"),__FILE__, __LINE__);
            System::halt(-1);
            return false;
        }
    }

    /**
     * Return the time at which the file or directory denoted by the given
     * abstract pathname was last modified, or zero if it does not exist or
     * some other I/O error occurs.
     */
    function getLastModifiedTime(&$f) {
        // argument type check. Required as of php does not support type signatures
        if (!isInstanceOf($f, 'File')) {
            // fixme collect this error
            return new RuntimeException("Illegal argument type", __FILE__, __LINE__);
        }

        if (!$f->exists()) {
            return (int) 0;
        }

        @clearstatcache();
        $strPath = (string) $f->getPath();
        $mtime = @filemtime($strPath);
        if (false === $mtime) {
            // FAILED. Log and return err.
            $msg = "FileSystem::Filemtime() FAILED. Cannot can not get modified time of $strPath. $php_errormsg";
            throw (new RuntimeException($msg));
        } else {
            return (int) $mtime;
        }
    }

    /**
     * Return the length in bytes of the file denoted by the given abstract
     * pathname, or zero if it does not exist, is a directory, or some other
     * I/O error occurs.
     */
    function getLength(&$f) {
        if (isInstanceOf($f, 'File')) {
            $strPath = (string) $f->getAbsolutePath();
            $fs = @filesize((string) $strPath);
            if ($fs !== false) {
                return $fs;
            } else {
                $msg = "FileSystem::Read() FAILED. Cannot get filesize of $strPath. $php_errormsg";
                throw (new RuntimeException($msg));
            }
        } else {
            return new RuntimeException("Illegal Argument Type", __FILE__, __LINE__);
        }
    }

    /* -- File operations -- */

    /**
     * Create a new empty file with the given pathname.  Return
     * true if the file was created and false if a
     * file or directory with the given pathname already exists.  Throw an
     * IOException if an I/O error occurs.
     *
     * @param       string      Path of the file to be created.
     *     
     * @throws      IOException
     */
    function createNewFile($strPathname) {
        if (@file_exists($strPathname))
            return false;

        // Create new file
        $fp = @fopen($strPathname, "w");
        if ($fp === false) {
            throw(new IOException("The file \"$strPathname\" could not be created"));
            return false;
        } else {
            @fclose($fp);
            return true;
        }
    }

    /**
     * Delete the file or directory denoted by the given abstract pathname,
     * returning true if and only if the operation succeeds.
     */
    function delete(&$f) {
        // FIXME
        // clean this up
        if (!isInstanceOf($f, 'File') || !isInstanceOf($f, 'File')) {
            // will terminate exection at once
            new RuntimeException("Illegal argument type fed to method", __FILE__, __LINE__);
        }

        if ($f->isDirectory()) {
            return rmdir($f->getPath());
        } else {
            $path = $f->getPath();
            return $this->Unlink($path);
        }
    }

    /**
     * Arrange for the file or directory denoted by the given abstract
     * pathname to be deleted when System::Shutdown is called, returning
    * true if and only if the operation succeeds.
     */
    function deleteOnExit(&$f) {
        die("deleteOnExit() not implemented by local fs driver");
    }

    /**
     * List the elements of the directory denoted by the given abstract
     * pathname.  Return an array of strings naming the elements of the
     * directory if successful; otherwise, return <code>null</code>.
     */
    function listDir(&$f) {
        if (isInstanceOf($f, 'File')) {
            $strPath = (string) $f->getAbsolutePath();
            $d = @dir($strPath);
            if (!$d) {
                return null;
            }
            $list = array();
            while($entry = $d->read()) {
                if ($entry != "." && $entry != "..") {
                    array_push($list, $entry);
                }
            }
            $d->close();
            unset($d);
            return $list;
        } else {
            return new RuntimeException("Illegal Argument Type", __FILE__, __LINE__);
        }
    }

    /**
     * Create a new directory denoted by the given abstract pathname,
     * returning true if and only if the operation succeeds.
     */
    function createDirectory(&$f) {
        return @mkdir($f->getAbsolutePath(),0755);
    }

    /**
     * Rename the file or directory denoted by the first abstract pathname to
     * the second abstract pathname, returning true if and only if
     * the operation succeeds.
     *
     * @param f1 reference to abstract source file
     * @param f1 reference to abstract destination file
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function rename(&$f1, &$f2) {
        if (isInstanceOf($f1, 'File') && isInstanceOf($f2, 'File')) {
            $Logger =& System::GetLogger();
            // get the canonical paths of the file to rename
            $src = (string) $f1->getAbsolutePath();
            $dest = (string) $f2->getAbsolutePath();
            if (FALSE === @rename($src, $dest)) {
                $msg = "Rename FAILED. Cannot rename $src to $dest. $php_errormsg";
                throw (new RuntimeException($msg));
            } else {
                //$Logger->Log(PH_LOG_DEBUG, "Successfully renamed $src to $dest",  __FILE__, __LINE__);
                return TRUE;
            }
        } else {
            die("IllegalArgument Exception");
        }
    }

    /**
        * Set the last-modified time of the file or directory denoted by the
        * given abstract pathname returning true if and only if the
        * operation succeeds.
     *
     * @throws IOEception, RuntimeException
        */
    function setLastModifiedTime(&$f, $time) {
        // type check
        if (!is_a($f, 'File')) {
            throw (new RuntimeException("Illeagal argument to function"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $path = (string) $f->getPath();
        $success = @touch($path, $time);

        if ($success) {
            return true;
        } else {
            throw (new IOException("Could not create directory due to: $php_errormsg"), __FILE__, __LINE__);
            return false;
        }
    }

    /**
     * Mark the file or directory denoted by the given abstract pathname as
     * read-only, returning <code>true</code> if and only if the operation
     * succeeds.
     */
    function setReadOnly(&$f) {
        die("setReadonle() not implemented by local fs driver");
    }


    /* -- Filesystem interface -- */

    /**
     * List the available filesystem roots, return array of File objects
     */
    function listRoots() {
        die("SYSTEM ERROR [listRoots() not implemented by local fs driver]");
    }

    /* -- Basic infrastructure -- */

    /**
     * Compare two abstract pathnames lexicographically.
     */
    function compare(&$f1, &$f2) {
        die("SYSTEM ERROR [compare() not implemented by local fs driver]");
    }











    // FIXME

    /* -- old file i/o and modification methods, need to be migrated  -- */


    // {{{ Copy()

    /**
     * Copy a file.
     *
     * @param	src		String. Source path and name file to copy.
     * @param	dest	String. Destination path and name of new file.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function copy($src, $dest) {

        if (!isInstanceOf($src, 'File') || !isInstanceOf($dest, 'File')) {
            // will terminate exection at once
            new RuntimeException("Illegal argument type fed to method", __FILE__, __LINE__);
        }

        $src  = $src->getAbsolutePath();
        $dest = $dest->getAbsolutePath();

        if (FALSE === @copy($src, $dest)) { // Copy FAILED. Log and return err.
            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::Copy() FAILED. Cannot copy $src to $dest. $php_errormsg";
            throw (new RuntimeException($msg));

        } else { // Copy worked. Log and return TRUE.
            return TRUE;
        }
    }

    // }}}
    // {{{ Rename()

    /** OLD
     * Rename a file or directory. Hint: Use absolute paths if possible to avoid
     * confustion of where src and dest are located.
     *
     * @param	src		String. Source file/directory to rename.
     * @param	dest	String. Destination file/directory name.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */
    /*
        function Rename($src, $dest)
        {
     
    		if (isInstanceOf($src, 'File'))
    			$src = $src->getPath();
     
    		if (isInstanceOf($dest, 'File'))
    			$dest = $dest->getPath();
     
            $Logger =& System::GetLogger();
     
            if (FALSE === @rename((string) $src, (string) $dest)) {// Copy FAILED. Log and return err.
     
                // Add error from php to end of log message. $php_errormsg.
                $msg = "FileSystem::Rename() FAILED. Cannot rename $src to $dest. $php_errormsg";
                throw (new RuntimeException($msg));     
     
            } else { // Worked. Log and return TRUE.
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::Rename() SUCCESS. $src to $dest",  __FILE__, __LINE__);
     
                return TRUE;
            }
        }
    	*/
    // }}}
    // {{{ _mkdir()

    /**
     * Create (make) a directory.
     *
     * @param	pathname	String. Path and name of new directory. This method
     *						only creates a single directory. It's used by Mkdir.
     * @param	mode		Int. The mode (permissions) of the new directory. If
     *						using octal add leading 0. eg. 0777. Mode is affect
     *						by the umask system setting.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     * @access	private
     */
    /*
        function _mkdir($pathname, $mode) {
            $Logger =& System::GetLogger();
     
            // Throw a warning if mode is 0. PHP converts illegal octal numbers to
            // 0 so 0 might not be what the user intended.
     
            if ($mode == 0) {
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::_mkdir() WARNING. Creating a directory with permissions of 0. Is this what you wanted? Possible out of range octal number for mode.", __FILE__, __LINE__);
            }
     
            $str_mode = decoct($mode); // Show octal in messages.
     
            if (FALSE === @mkdir($pathname, $mode)) {// Mkdir FAILED.
     
                // Add error from php to end of log message. $php_errormsg.
                $msg = "FileSystem::_mkdir() FAILED. Cannot mkdir $pathname. Mode $str_mode. $php_errormsg";
                throw (new RuntimeException($msg));
     
            } else { // Mkdir worked. Log and return TRUE.
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::_mkdir() $pathname. Mode $str_mode.", __FILE__, __LINE__);
     
                return TRUE;
            }
        }
    */
    // }}}
    // {{{ Mkdir()

    /**
     * This method makes one directory or recursively make directories in path.
     *
     * @param	path	String. Path of directories.
     * @param	mode	Int. The mode (permissions) of the new directory. If
     *					using octal add leading 0. eg. 0777. Mode is affect
     *					by the umask system setting.
     * @param	parents	Boolean.	True: Make parent directories as needed.
     *								False: Do not make parent directories.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     * @access	public
     */
    /*
        function Mkdir($path, $mode=0777, $parents=TRUE) {
            $Logger =& System::GetLogger();
     
            // If dir already exists return TRUE.
            if (is_dir($path)) {
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::Mkdir() $path already exists, continuing...", __FILE__, __LINE__);
                return TRUE;
            }
     
            // Throw a warning if mode is 0. PHP converts illegal octal numbers to
            // 0 so 0 might not be what the user intended.
     
            if ($mode == 0) {
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::Mkdir() WARNING. Creating a directory with permissions of 0. Is this what you wanted? Possible out of range octal number for mode.",  __FILE__, __LINE__);
            }
     
            $str_mode = decoct($mode); // Show octal in messages.
     
            // Only make directory if parents=FALSE
            if (FALSE === $parents) {
     
                // Call _mkdir.
                $error = FileSystem::_mkdir($path, $mode);
     
                if (Err::CheckError($error)) { // error.
     
                    $msg = "FileSystem::Mkdir() FAILED. Cannot Mkdir $path. Mode $str_mode. ". $error->GetMessage();
                    throw (new RuntimeException($msg));
     
                }
     
            } else { // Make parents and directory.
     
                // Break up path.
                $path_parts = preg_split("/\//", $path);
     
                if ($path_parts[0] == "") { // start at root.
     
                    $dir_to_make = "/";
     
                    // Pop off [0]
                    array_shift($path_parts);
     
                } else {
                    $dir_to_make = "";
                }
     
                foreach ($path_parts as $dir) {
     
                    $dir_to_make .= $dir."/";
     
                    if (!is_dir($dir_to_make)) { // mkdir if not existing.
     
                        $error = FileSystem::_mkdir($dir_to_make, $mode);
     
                        if (Err::CheckError($error)) { // error.
     
                            $msg = "FileSystem::Mkdir() FAILED. Cannot Mkdir $path. Mode $str_mode. ". $error->GetMessage();
                            $Logger->Log(PH_LOG_ERROR, $msg);
                            throw (new RuntimeException($msg));
     
                        }
     
                    }
     
                }
            }
            // Mkdir worked. Log and return TRUE.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Mkdir() SUCCESS. $path. Mode $str_mode.");
     
            return TRUE;
     
        }
    */
    // }}}
    // {{{ Chmod()

    /**
     * Change the permissions on a file or directory.
     *
     * @param	pathname	String. Path and name of file or directory.
     * @param	mode		Int. The mode (permissions) of the file or
     *						directory. If using octal add leading 0. eg. 0777.
     *						Mode is affected by the umask system setting.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function Chmod($pathname, $mode) {
        $Logger =& System::GetLogger();

        if ($mode == 0) {
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Chmod() WARNING. Setting mode to 0. Is this what you wanted? Possible out of range octal number.");
        }

        $str_mode = decoct($mode); // Show octal in messages.

        if (FALSE === @chmod($pathname, $mode)) {// FAILED.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::Chmod() FAILED. Cannot chmod $pathname. Mode $str_mode. $php_errormsg";
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));

        } else { // Worked. Log and return TRUE.
            //$Logger->Log(PH_LOG_DEBUG, "FileSystem::Chmod() SUCCESS. $pathname. Mode $str_mode.");

            return TRUE;
        }
    }
    // }}}
    // {{{ Lock()
    /**
     * Locks a file and throws an IO Error if this is not possible.
     *
     * @throws      IOException
     */
    function Lock($f) {
        // FIXME
        // clean this up
        if (!isInstanceOf($f, 'File') || !isInstanceOf($f, 'File')) {
            // will terminate exection at once
            new RuntimeException("Illegal argument type fed to method", __FILE__, __LINE__);
        }

        $filename = $f->GetPath();
        $fp = @fopen($filename, "w");
        $result = @flock($fp, LOCK_EX);
        @fclose($fp);

        if (!$result)
            throw( new IOException("Could not lock file \"$filename\"") );

        return $result;
    }
    // }}}
    // {{{ UnLock()
    /**
     * Unlocks a file and throws an IO Error if this is not possible.
     *
     * @throws      IOException
     */
    function UnLock($f) {
        // FIXME
        // clean this up
        if (!isInstanceOf($f, 'File') || !isInstanceOf($f, 'File')) {
            // will terminate exection at once
            new RuntimeException("Illegal argument type fed to method", __FILE__, __LINE__);
        }

        $filename = $f->GetPath();

        $fp = @fopen($filename, "w");
        $result = @flock($fp, LOCK_UN);
        fclose($fp);

        if (!$result)
            throw( new IOException("Could not unlock file \"$filename\"") );

        return $result;
    }
    // {{{ Unlink()

    /**
     * Delete a file.
     *
     * @param	file	String. Path and/or name of file to delete.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function Unlink($file) {
        //$Logger =& System::GetLogger();

        if (FALSE === @unlink($file)) {// FAILED.

            // Add error from php to end of log message. $php_errormsg.
            //$msg = "FileSystem::Unlink() FAILED. Cannot unlink $file. $php_errormsg";
            //$Logger->Log(PH_LOG_ERROR, $msg);
            //throw (new RuntimeException($msg));

            return false;
            //$resource;

        } else { // Worked. Log and return TRUE.
            //$Logger->Log(PH_LOG_DEBUG, "FileSystem::Unlink() SUCCESS. $file.");

            return TRUE;
        }
    }
    // }}}
    // {{{ Symlink()

    /**
     * Symbolically link a file to another name. Currently symlink is not
     * implemented on Windows.
     * Don't use if the application is to be portable.
     *
     * @param	target	String. Path and/or name of file to link.
     * @param	link	String. Path and/or name of link to be created.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function Symlink($target, $link) {
        $Logger =& System::GetLogger();

        // If Windows OS then symlink() will report it is not supported in
        // the build. Use this error instead of checking for Windows as the OS.

        if (FALSE === @symlink($target, $link)) { // FAILED.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::Symlink() FAILED. Cannot symlink $target to $link. $php_errormsg";
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));

        } else { // Worked. Log and return TRUE.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Symlink() SUCCESS. $target to $link.");

            return TRUE;
        }

    }
    // }}}
    // {{{ Touch()

    /**
     * Set the modification and access time on a file to the present time.
     *
     * @param	file	String. Path and/or name of file to touch.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function Touch($file, $time=FALSE) {
        $Logger =& System::GetLogger();

        if (FALSE === $time) {
            $error = @touch($file);
            $time = ""; // Don't show in msg.
        } else {
            $error = @touch($file, $time);
        }

        if (FALSE === $error) { // FAILED.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::Touch() FAILED. Cannot touch $file. $php_errormsg";
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));

        } else { // Touch worked. Log and return TRUE.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Touch() SUCCESS. $file $time.");

            return TRUE;
        }

    }
    // }}}
    // {{{ _rmdir()

    /**
     * Delete an empty directory.
     *
     * @param	dir	String. Path and/or name of empty directory to delete. Used
     * 						by Rmdir.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function _rmdir($dir) {
        $Logger =& System::GetLogger();

        if (FALSE === @rmdir($dir)) { // FAILED.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::_rmdir() FAILED. Cannot rmdir $dir. $php_errormsg";
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));

        } else { // Worked. Log and return TRUE.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::_rmdir() $dir.");

            return TRUE;
        }

    }
    // }}}
    // {{{ Rmdir()

    /**
     * Delete an empty directory OR a directory and all of its contents.
     *
     * @param	dir	String. Path and/or name of directory to delete.
     * @param	children	Boolean.	False: don't delete directory contents.
     *									True: delete directory contents.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function Rmdir($dir, $children=FALSE) {
        $Logger =& System::GetLogger();

        // If children=FALSE only delete dir if empty.
        if (FALSE === $children) {

            // Call _rmdir.
            $error = FileSystem::_rmdir($dir);

            if (Err::CheckError($error)) { // error.

                $msg = "FileSystem::Rmdir() FAILED. Cannot Rmdir $dir. ". $error->GetMessage();
                $Logger->Log(PH_LOG_ERROR, $msg);
                throw (new RuntimeException($msg));

            }

        } else { // delete contents and dir.


            $handle = @opendir($dir);

            if (FALSE === $handle) { // Error.

                $msg = "FileSystem::Rmdir() FAILED. Cannot opendir() $dir. $php_errormsg";
                $Logger->Log(PH_LOG_ERROR, $msg);
                throw (new RuntimeException($msg));

            } else { // Read from handle.

                // Don't error on readdir().
                while (false !== ($entry = @readdir($handle))) {

                    if ($entry != '.' && $entry != '..') {

                        // Only add / if it isn't already the last char.
                        // This ONLY serves the purpose of making the Logger
                        // output look nice:)

                        if (strpos(strrev($dir), "/") == 0) {// there is a /
                            $next_entry = $dir . $entry;
                        } else { // no /
                            $next_entry = $dir."/".$entry;
                        }

                        // NOTE: As of php 4.1.1 is_dir doesn't return FALSE it
                        // returns 0. So use == not ===.

                        // Don't error on is_dir()
                        if (FALSE == @is_dir($next_entry)) { // Is file.

                            $error = FileSystem::Unlink($next_entry); // Delete.

                            if (Err::CheckError($error)) { // error and return.

                                $msg = "FileSystem::Rmdir() FAILED. Cannot FileSystem::Unlink() $next_entry. ". $error->GetMessage();
                                $Logger->Log(PH_LOG_ERROR, $msg);
                                throw (new RuntimeException($msg));
                            }

                        } else { // Is directory.

                            $error = FileSystem::Rmdir($next_entry, TRUE); // Delete

                            if (Err::CheckError($error)) { // error and return.

                                $msg = "FileSystem::Rmdir() FAILED. Cannot FileSystem::Rmdir() $next_entry. ". $error->GetMessage();
                                $Logger->Log(PH_LOG_ERROR, $msg);
                                throw (new RuntimeException($msg));

                            }

                        } // end is_dir else
                    } // end .. if
                } // end while
            } // end handle if

            // Don't error on closedir()
            @closedir($handle);

            $error = FileSystem::_rmdir($dir);

            if (Err::CheckError($error)) { // error and return.

                $msg = "FileSystem::Rmdir() FAILED. Cannot FileSystem::_rmdir() $dir. ". $error->GetMessage();
                $Logger->Log(PH_LOG_ERROR, $msg);
                throw (new RuntimeException($msg));

            }

        }

        // Worked. Log and return TRUE.
        $Logger->Log(PH_LOG_DEBUG, "FileSystem::Rmdir() SUCCESS. $dir.");

        return TRUE;


    }
    // }}}
    // {{{ Umask()

    /**
     * Set the umask for file and directory creation.
     *
     * @param	mode	Int. Permissions ususally in ocatal. Use leading 0 for
     *					octal. Number between 0 and 0777.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function Umask($mode) {
        $Logger =& System::GetLogger();

        // Throw a warning if mode is 0. PHP converts illegal octal numbers to
        // 0 so 0 might not be what the user intended.

        if ($mode == 0) {
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Umask() WARNING. Setting umask to 0. Is this what you wanted? Possible out of range octal number.");
        }

        $str_mode = decoct($mode); // Show octal in messages.

        if (FALSE === @umask($mode)) { // FAILED.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::Umask() FAILED. Value $mode. $php_errormsg";
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));

        } else { // Worked. Log and return TRUE.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Umask() SUCCESS. $str_mode.");

            return TRUE;
        }

    }
    // }}}
    // {{{ Read()

    /**
     * Reads a file and stores the data in the variable passed by reference.
     *
     * @param	file	String. Path and/or name of file to read.
     * @param	rBuffer	Reference. Variable of where to put contents.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function Read($file, &$rBuffer) {
        $Logger =& System::GetLogger();

        $fp = @fopen($file, "rb");  // b is for binary and used on Windows
        // ignored on *nix.

        if (FALSE === $fp) { // fopen FAILED.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::Read() FAILED. Cannot fopen $file. $php_errormsg";
            throw (new RuntimeException($msg));

        } else { // fopen worked. Log and try to lock it.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Read() fopen'd $file.");

            if (FALSE) { // Locks don't seem to work on windows??? HELP!!!!!!!!!
                //if (FALSE === @flock($fp, LOCK_EX)) { // FAILED.
                // Add error from php to end of log message. $php_errormsg.
                $msg = "FileSystem::Read() FAILED. Cannot acquire flock on $file. $php_errormsg";
                throw (new RuntimeException($msg));

            } else { // Try to get file size.
                $Logger->Log(PH_LOG_DEBUG, "FileSystem::Read() flock'd $file.",  __FILE__, __LINE__);

                $fs = @filesize($file);
                if (FALSE === $fs) { // FAILED.

                    // Add error from php to end of log message. $php_errormsg.
                    $msg = "FileSystem::Read() FAILED. Cannot get filesize of $file. $php_errormsg";
                    throw (new RuntimeException($msg));

                } else { // Read file then close.
                    $Logger->Log(PH_LOG_DEBUG, "FileSystem::Read() filesize of $fs from $file.", __FILE__, __LINE__);

                    $rBuffer = @fread($fp, $fs);

                    if (FALSE === @fclose($fp)) { // FAILED.
                        // Add error from php to end of log message. $php_errormsg.
                        $msg = "FileSystem::Read() FAILED. Cannot fclose $file. $php_errormsg";
                        throw (new RuntimeException($msg));

                    } else {

                        $Logger->Log(PH_LOG_DEBUG, "FileSystem::Read() fread $file.",  __FILE__, __LINE__);
                        $Logger->Log(PH_LOG_DEBUG, "FileSystem::Read() fclose'd $file.",  __FILE__, __LINE__);
                        $Logger->Log(PH_LOG_DEBUG, "FileSystem::Read() SUCCESS. $file.",  __FILE__, __LINE__);
                        return TRUE;

                    } // End fclose if

                } // End filesize if

            } // End flock if

        } // End fopen if
    }
    // }}}
    // {{{ _write()

    /**
     * _write the passed buffer to filename. Overwrites existing file if any.
     *
     * @param	file	Path and/or name of file to write.
     * @param	rBuffer	Reference. String to write.
     *
     * @return	TRUE on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function _write($file, &$rBuffer) {
        $Logger =& System::GetLogger();

        $fp = @fopen($file, "wb");  // b is for binary and used on Windows
        // ignored on *nix.

        if (FALSE === $fp) { // fopen FAILED.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::_write() FAILED. Cannot fopen $file. $php_errormsg";
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));

        } else { // fopen worked. Log and try to lock it.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::_write() fopen'd $file.");

            if (FALSE) { // Locks don't seem to work on windows??? HELP!!!!!!!!!
                //if (FALSE === @flock($fp, LOCK_EX)) { // FAILED.

                // Add error from php to end of log message. $php_errormsg.
                $msg = "FileSystem::_write() FAILED. Cannot acquire flock on $file. $php_errormsg";
                $Logger->Log(PH_LOG_ERROR, $msg);
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

    function Write($file, &$rBuffer, $parents=TRUE, $mode=0777) {
        $Logger =& System::GetLogger();

        // If  already exists OR parents=FALSE. Write file and return.
        if (is_file($file) OR FALSE === $parents) {
            $error = FileSystem::_write($file, $rBuffer);

            if (Err::CheckError($error)) { // error.

                $msg = "FileSystem::Write() FAILED. Cannot Write() $file. ". $error->GetMessage();
                $Logger->Log(PH_LOG_ERROR, $msg);
                throw (new RuntimeException($msg));

            }

            // Success.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Write() SUCCESS. $file.");

            return TRUE;
        }

        // Throw a warning if mode is 0. PHP converts illegal octal numbers to
        // 0 so 0 might not be what the user intended.

        if ($mode == 0) {
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::Write() WARNING. Creating a directory with permissions of 0. Is this what you wanted? Possible out of range octal number for mode.");
        }

        $str_mode = decoct($mode); // Show octal in messages.

        // Get path parts. Don't error.
        $path_parts = @pathinfo($file);

        // Make path.
        $error = FileSystem::Mkdir($path_parts["dirname"], $mode, TRUE);

        if (Err::CheckError($error)) { // error.
            $msg = "FileSystem::Write() FAILED. Cannot Write() $file. ". $error->GetMessage();
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));
        }

        // Directory structure has been made write file.
        $error = FileSystem::_write($file, $rBuffer);

        if (Err::CheckError($error)) { // error.
            $msg = "FileSystem::Write() FAILED. Cannot Write() $file. ". $error->GetMessage();
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));
        }

        // Worked. Log and return TRUE.
        $Logger->Log(PH_LOG_DEBUG, "FileSystem::Write() SUCCESS. $file. Parent directories mode $str_mode.");

        return TRUE;

    }
    // }}}
    // {{{ CompareMTimes()

    /**
     * Compare the modified time of two files.
     *
     * @param	file1	String. Path and name of file1.
     * @param	file2	String. Path and name of file2.
     *
     * @return	Int. 	1 if file1 is newer.
                        -1 if file2 is newer.
                        0 if files have the same time.
                        Err object on failure.

     * @author  Charlie Killian, charlie@tizac.com
     */

    function CompareMTimes($file1, $file2) {
        $Logger =& System::GetLogger();

        $mtime1 = Filemtime($file1);
        $mtime2 = Filemtime($file2);

        if (Err::CheckError($mtime1)) { // FAILED. Log and return err.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::CompareMTimes() FAILED. Cannot can not get modified time of $file1. $mtime1->GetMessage";
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));
        }
        elseif (Err::CheckError($mtime2)) { // FAILED. Log and return err.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::CompareMTimes() FAILED. Cannot can not get modified time of $file2. $mtime2->GetMessage";

            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));
        }
        else { // Worked. Log and return compare.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::CompareMTimes() SUCCESS. $file1 $file2");

            // Compare mtimes.
            if ($mtime1 == $mtime2) {
                return 0;
            } else {
                return ($mtime1 < $mtime2) ? -1 : 1;
            } // end compare
        }
    }
    // }}}
    // {{{ ProcessIniFile()

    /**
     * Proccess an ini file returning an array of values.
     *
     * @param	pathname		String. Path and name to ini file.
     * @param	processSections	Boolean. TRUE include sections as associative
     *							array keys. See PHP manual.
     *
     * @return	Associative array of values on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */

    function ParseIniFile($pathname, $processSections=FALSE) {
        $Logger =& System::GetLogger();

        $ini_array = @parse_ini_file($pathname, $processSections);

        // parse_ini_file() returns 0 not FALSE as of PHP 4.1.1 so don't use ===
        if (FALSE == $ini_array) { // Copy FAILED. Log and return err.

            // Add error from php to end of log message. $php_errormsg.
            $msg = "FileSystem::ParseIniFile() FAILED. Cannot parse $pathname. processSections=$processSections. $php_errormsg";
            $Logger->Log(PH_LOG_ERROR, $msg);
            throw (new RuntimeException($msg));

        } else { // Copy worked. Log and return TRUE.
            $Logger->Log(PH_LOG_DEBUG, "FileSystem::ParseIniFile() SUCCESS. $pathname.  processSections=$processSections");

            return $ini_array;
        }
    }
    // }}}

    // {{{ Filemtime()

    /**
     * Get the modified time for a file.
     *
     * @param	file	String. Path and name of file.
     *
     * @return	Int. Unix timestamp on success. Err object on failure.
     * @author  Charlie Killian, charlie@tizac.com
     */
    /*
        function Filemtime($file)
        {
            $Logger =& System::GetLogger();
    		@clearstatcache();
            $mtime = @filemtime($file);
            if (FALSE === $mtime) { // FAILED. Log and return err.
                	$msg = "FileSystem::Filemtime() FAILED. Cannot can not get modified time of $file. $php_errormsg";
                  throw (new RuntimeException($msg));
            } else {
    			// Worked. Log and return TRUE.
                //$Logger->Log(PH_LOG_DEBUG, "FileSystem::Filemtime() SUCCESS. $file $mtime");
                return (int) $mtime;
            }
        }
        // }}}
    */

}


/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>
