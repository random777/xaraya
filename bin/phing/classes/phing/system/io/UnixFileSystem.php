<?php
// {{{ Header
/*
 * -File       $Id: UnixFileSystem.php,v 1.14 2003/05/02 14:31:56 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Tizac
 * -Author     Andreas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.system.lang.System');
import('phing.system.io.FileSystem');

// {{{ UnixFileSystem

/**
 * UnixFileSystem class. This class encapsulates the basic file system functions
 * for platforms using the unix (posix)-stylish filesystem. It wraps php native
 * functions suppressing normal PHP error reporting and instead uses Exception
 * to report and error.
 *
 * This class is part of a oop based filesystem abstraction and targeted to run
 * on all supported php platforms.
 *
 * Note: For debugging turn track_errors on in the php.ini. The error messages
 * and log messages from this class will then be clearer because $php_errormsg
 * is passed as part of the message.
 *
 * FIXME:
 *  - Comments
 *  - Error handling reduced to min, error are handled by File mainly
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.14 $
 * @package   phing.system.io
 */

class UnixFileSystem extends FileSystem {

    /**
     * Empty class constructor.
     */
    function UnixFileSystem() {}

    /**
     * returns OS dependant path separator char
     */
    function getSeparator() {
        return '/';
    }

    /**
     * returns OS dependant directory separator char
     */
    function getPathSeparator() {
        return ':';
    }

    /**
     * A normal Unix pathname contains no duplicate slashes and does not end
     * with a slash.  It may be the empty string.
     *
     * Check that the given pathname is normal.  If not, invoke the real
     * normalizer on the part of the pathname that requires normalization.
     * This way we iterate through the whole pathname string only once.
     */
    function normalize($strPathname) {
        // Resolve home directories. We assume /home is where all home
        // directories reside, b/c there is no other way to do this with
        // PHP AFAIK.
        if ($strPathname{0} === "~") {
            if ($strPathname{1} === "/") { // like ~/foo => /home/user/foo
                $strPathname = "/home/" . get_current_user() . substr($strPathname, 1);
            } else { // like ~foo => /home/foo
                $pos = strpos($strPathname, "/");
                $name = substr($strPathname, 1, $pos - 2);
                $strPathname = "/home/" . $name . substr($strPathname, $pos);
            }
        }

        $n = strlen($strPathname);
        $prevChar = 0;
        for ($i = 0; $i < $n; ++$i) {
            $c = $strPathname{$i};
            if (($prevChar === '/') && ($c === '/')) {
                return (string) UnixFileSystem::_normalizer($strPathname, $n, $i - 1);
            }
            $prevChar = $c;
        }
        if ($prevChar === '/') {
            return (string) UnixFileSystem::_normalizer($strPathname, $n, $n - 1);
        }
        return (string) $strPathname;
    }

    /**
     * Normalize the given pathname, whose length is $len, starting at the given
     * $offset; everything before this offset is already normal.
     */
    function _normalizer($pathname, $len, $offset) {
        if ($len === 0) {
            return $pathname;
        }
        $n = (int) $len;
        while (($n > 0) && ($pathname{$n-1} === '/')) {
            $n--;
        }
        if ($n === 0) {
            return '/';
        }
        $sb = "";

        if ($offset > 0) {
            $sb.=substr($pathname, 0, $offset);
        }
        $prevChar = 0;
        for ($i = $offset; $i < $n; ++$i) {
            $c = $pathname{$i};
            if (($prevChar === '/') && ($c === '/')) {
                continue;
            }
            $sb .= $c;
            $prevChar = $c;
        }
        return (string) $sb;
    }

    /**
     * Compute the length of the pathname string's prefix.  The pathname
     * string must be in normal form.
     */

    function prefixLength($pathname) {
        if (strlen($pathname === 0)) {
            return 0;
        }
        return (($pathname{0} === '/') ? 1 : 0);
    }

    /**
     * Resolve the child pathname string against the parent.
     * Both strings must be in normal form, and the result
     * will be in normal form.
     */
    function resolve($parent, $child) {

        if ($child === "") {
            return $parent;
        }

        if ($child{0} === '/') {
            if ($parent === '/') {
                return $child;
            }
            return $parent.$child;
        }

        if ($parent === '/') {
            return $parent.$child;
        }

        return $parent.'/'.$child;
    }

    function getDefaultParent() {
        return '/';
    }

    function isAbsolute(&$f) {
        return ($f->getPrefixLength() !== 0);
    }

    /**
     * the file resolver
     */
    function resolveFile(&$f) {
        // resolve if parent is a file oject only
        if (isInstanceOf($f, 'File')) {
            if ($this->isAbsolute($f)) {
                return $f->getPath();
            } else {
                return $this->resolve(System::getProperty("user.dir"), $f->getPath());
            }
        } else {
            throw (new RuntimeException("IllegalArgutmentType: Argument is not File"), __FILE__, __LINE__);
        }
    }

    /* -- most of the following is mapped to the php natives wrapped by FileSystem */

    function canonicalize($strPath)   {
        return parent::canonicalize($strPath);
    }

    /* -- Attribute accessors -- */
    function getBooleanAttributes(&$f) {
        //$rv = getBooleanAttributes0($f);
        $name = $f->getName();
        $hidden = (strlen($name) > 0) && ($name{0} == '.');
        return ($hidden ? $this->BA_HIDDEN : 0);
    }

    function checkAccess(&$f, $write = false)  {
        return parent::checkAccess($f, $write);
    }
    function getLastModifiedTime(&$f) {
        return parent::getLastModifiedTime($f);
    }


    function getLength(&$f) {
        return parent::getLength($f);
    }

    /* -- File operations -- */
    function createFileExclusively($path) {
        return parent::createFileExclusively($path);
    }

    function delete(&$f) {
        return parent::delete($f);
    }
    function deleteOnExit(&$f) {
        return parent::deleteOnExit($f);
    }
    function listDir(&$f) {
        return parent::listDir($f);
    }
    function createDirectory(&$f) {
        return parent::createDirectory($f);
    }
    function rename(&$f1, &$f2) {
        return parent::rename($f1, $f2);
    }
    function copy(&$f1, &$f2) {
        return parent::copy($f1, $f2);
    }
    function setLastModifiedTime(&$f, $time) {
        return parent::setLastModifiedTime($f, $time);
    }

    /**
     * set file readonly on unix
     */
    function setReadOnly(&$f) {
        if (isInstanceOf($f, 'File')) {
            $strPath = (string) $f->getPath();
            $perms = (int) (@fileperms($strPath) & 0444);
            return FileSystem::Chmod($strPath, $perms);
        } else {
            throw (new RuntimeException("IllegalArgutmentType: Argument is not File"), __FILE__, __LINE__);
        }
    }

    /**
     * compares file paths lexicographically
     */
    function compare(&$f1, &$f2) {
        if (isInstanceOf($f1, 'File') && isInstanceOf($f2, 'File')) {
            $f1Path = $f1->getPath();
            $f2Path = $f2->getPath();
            return (boolean) strcmp((string) $f1Path, (string) $f2Path);
        } else {
            throw (new RuntimeException("IllegalArgutmentType: Argument is not File"), __FILE__, __LINE__);
        }
    }

    /* -- fs interface --*/

    function listRoots() {
        if (!$this->checkAccess('/', false)) {
            die ("Can not access root");
        }
        return array(new File("/"));
    }

    /**
     * returns the contents of a directory in an array
     */
    function lister(&$f) {
        $dir = @opendir($f->getAbsolutePath());
        if (!$dir) {
            throw (new RuntimeException("Can't open directory " . $f->toString()), __FILE__, __LINE__);
        }
        $vv = array();
        while (($file = @readdir($dir)) !== false) {
            if ($file == "." || $file == "..") {
                continue;
            }
            $vv[] = (string) $file;
        }
        @closedir($dir);
        return $vv;
    }

}
// }}}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>
