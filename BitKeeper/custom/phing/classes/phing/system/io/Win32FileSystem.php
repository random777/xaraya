<?php
/*
 * $Id: Win32FileSystem.php,v 1.16 2003/02/25 17:38:31 openface Exp $
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

import("phing.system.io.FileSystem");

/**
 *  @package   phing.system.io
 */
class Win32FileSystem extends FileSystem {


    var $slash;
    var $altSlash;
    var $semicolon;

    var $__driveDirCache; // private static

    function Win32FileSystem() {
        // static class var workaround
        static $__ddc = null;
        if ($__ddc === null) {
            $__ddc = array();
        }
        $this->__driveDirCache =& $__ddc;
        // end static class var workaround

        $this->slash = Win32FileSystem::getSeparator();
        $this->semicolon = Win32FileSystem::getPathSeparator();
        $this->altSlash = ($this->slash === '\\') ? '/' : '\\';
    }

    function isSlash($c) {
        return ($c == '\\') || ($c == '/');
    }

    function isLetter($c) {
        return ((ord($c) >= ord('a')) && (ord($c) <= ord('z')))
               || ((ord($c) >= ord('A')) && (ord($c) <= ord('Z')));
    }

    function slashify($p) {
        if ((strlen($p) > 0) && ($p{0} != $this->slash)) {
            return $this->slash.$p;
        }
        else {
            return $p;
        }
    }

    /* -- Normalization and construction -- */

    function getSeparator() {
        // the ascii value of is the \
        return (string) chr(92);
    }

    function getPathSeparator() {
        return (string) ';';
    }

    /* A normal Win32 pathname contains no duplicate slashes, except possibly
    * for a UNC prefix, and does not end with a slash.  It may be the empty
    * string.  Normalized Win32 pathnames have the convenient property that
    * the length of the prefix almost uniquely identifies the type of the path
    * and whether it is absolute or relative:
    *
    *	0  relative to both drive and directory
    *	1  drive-relative (begins with '\\')
    *	2  absolute UNC (if first char is '\\'), else directory-relative (has form "z:foo")
    *	3  absolute local pathname (begins with "z:\\")
     */

    function normalizePrefix($strPath, $len, $sb) {
        $src = 0;
        while (($src < $len) && $this->isSlash($strPath{$src})) {
            $src++;
        }
        $c = "";
        if (($len - $src >= 2)
                && $this->isLetter($c = $strPath{$src})
                && $strPath{$src + 1} === ':') {
            /* Remove leading slashes if followed by drive specifier.
             * This hack is necessary to support file URLs containing drive
             * specifiers (e.g., "file://c:/path").  As a side effect,
             * "/c:/path" can be used as an alternative to "c:/path". */
            $sb .= $c;
            $sb .= ':';
            $src += 2;
        }
        else {
            $src = 0;
            if (($len >= 2)
                    && $this->isSlash($strPath{0})
                    && $this->isSlash($strPath{1})) {
                /* UNC pathname: Retain first slash; leave src pointed at
                 * second slash so that further slashes will be collapsed
                 * into the second slash.  The result will be a pathname
                 * beginning with "\\\\" followed (most likely) by a host
                 * name. */
                $src = 1;
                $sb.=$this->slash;
            }
        }
        return $src;
    }

    /* Normalize the given pathname, whose length is len, starting at the given
       offset; everything before this offset is already normal. */
    function normalizer($strPath, $len, $offset) {
        if ($len == 0) {
            return $strPath;
        }
        if ($offset < 3) {
            $offset = 0;	//Avoid fencepost cases with UNC pathnames
        }
        $src = 0;
        $slash = $this->slash;
        $sb = "";

        if ($offset == 0) {
            // Complete normalization, including prefix
            $src = $this->normalizePrefix($strPath, $len, $sb);
        } else {
            // Partial normalization
            $src = $offset;
            $sb .= substr($strPath, 0, $offset);
        }

        // Remove redundant slashes from the remainder of the path, forcing all
        // slashes into the preferred slash
        while ($src < $len) {
            $c = $strPath{$src++};
            if ($this->isSlash($c)) {
                while (($src < $len) && $this->isSlash($strPath{$src})) {
                    $src++;
                }
                if ($src === $len) {
                    /* Check for trailing separator */
                    $sn = (int) strlen($sb);
                    if (($sn == 2) && ($sb{1} === ':')) {
                        // "z:\\"
                        $sb .= $slash;
                        break;
                    }
                    if ($sn === 0) {
                        // "\\"
                        $sb .= $slash;
                        break;
                    }
                    if (($sn === 1) && ($this->isSlash($sb{0}))) {
                        /* "\\\\" is not collapsed to "\\" because "\\\\" marks
                        the beginning of a UNC pathname.  Even though it is
                        not, by itself, a valid UNC pathname, we leave it as
                        is in order to be consistent with the win32 APIs,
                        which treat this case as an invalid UNC pathname
                        rather than as an alias for the root directory of
                        the current drive. */
                        $sb .= $slash;
                        break;
                    }
                    // Path does not denote a root directory, so do not append
                    // trailing slash
                    break;
                } else {
                    $sb .= $slash;
                }
            } else {
                $sb.=$c;
            }
        }
        $rv = (string) $sb;
        return $rv;
    }

    /* Check that the given pathname is normal.  If not, invoke the real
     * normalizer on the part of the pathname that requires normalization.
     * This way we iterate through the whole pathname string only once.
    */

    function normalize($strPath) {
        $n = (int) strlen($strPath);
        $slash    = $this->slash;
        $altSlash = $this->altSlash;
        $prev = 0;
        for ($i = 0; $i < $n; ++$i) {
            $c = $strPath{$i};
            if ($c === $altSlash) {
                return $this->normalizer($strPath, $n, ($prev === $slash) ? $i - 1 : $i);
            }
            if (($c === $slash) && ($prev === $slash) && ($i > 1)) {
                return $this->normalizer($strPath, $n, $i - 1);
            }
            if (($c === ':') && ($i > 1)) {
                return $this->normalizer($strPath, $n, 0);
            }
            $prev = $c;
        }
        if ($prev === $slash) {
            return $this->normalizer($strPath, $n, $n - 1);
        }
        return $strPath;
    }

    function prefixLength($strPath) {
        $path  = (string) $strPath;
        $slash = (string) $this->slash;
        $n = (int) strlen($path);
        if ($n === 0) {
            return 0;
        }
        $c0 = $path{0};
        $c1 = ($n > 1) ? $path{1} :
              0;
        if ($c0 === $slash) {
            if ($c1 === $slash) {
                return 2;			// absolute UNC pathname "\\\\foo"
            }
            return 1;				// drive-relative "\\foo"
        }

        if ($this->isLetter($c0) && ($c1 === ':')) {
            if (($n > 2) && ($path{2}) === $slash) {
                return 3;			// Absolute local pathname "z:\\foo" */
            }
            return 2;				// Directory-relative "z:foo"
        }
        return 0;					// Completely relative
    }

    function resolve($parent, $child) {
        $parent = (string) $parent;
        $child  = (string) $child;
        $slash  = (string) $this->slash;

        $pn = (int) strlen($parent);
        if ($pn === 0) {
            return $child;
        }
        $cn = (int) strlen($child);
        if ($cn === 0) {
            return $parent;
        }

        $c = $child;
        if (($cn > 1) && ($c{0} === $slash)) {
            if ($c{1} === $slash) {
                // drop prefix when child is a UNC pathname
                $c = substr($c, 2);
            }
            else {
                //Drop prefix when child is drive-relative */
                $c = substr($c, 1);
            }
        }

        $p = $parent;
        if ($p{$pn - 1} === $slash) {
            $p = substr($p, 0, $pn - 1);
        }
        return $p.$this->slashify($c);
    }

    function getDefaultParent() {
        return (string) ("".$this->slash);
    }

    function fromURIPath($strPath) {
        $p = (string) $strPath;
        if ((strlen($p) > 2) && ($p{2} === ':')) {

            // "/c:/foo" --> "c:/foo"
            $p = substr($p,1);

            // "c:/foo/" --> "c:/foo", but "c:/" --> "c:/"
            if ((strlen($p) > 3) && strEndsWith('/', $p)) {
                $p = substr($p, 0, strlen($p) - 1);
            }
        }
        else if ((strlen($p) > 1) && strEndsWith('/', $p)) {
            // "/foo/" --> "/foo"
            $p = substr($p, 0, strlen($p) - 1);
        }
        return (string) $p;
    }


    /* -- Path operations -- */

    function isAbsolute(&$f) {
        $pl = (int) $f->getPrefixLength();
        $p  = (string) $f->getPath();
        return ((($pl === 2) && ($p{0} === $this->slash)) || ($pl === 3));
    }

    /** private */
    function _driveIndex($d) {
        $d = (string) $d{0};
        if ((ord($d) >= ord('a')) && (ord($d) <= ord('z'))) {
            return ord($d) - ord('a');
        }
        if ((ord($d) >= ord('A')) && (ord($d) <= ord('Z'))) {
            return ord($d) - ord('A');
        }
        return -1;
    }

    /** private */
    function _getDriveDirectory($drive) {
        $drive = (string) $drive{0};
        $i = (int) $this->_driveIndex($drive);
        if ($i < 0) {
            return null;
        }

        $s = (isset($this->__driveDirCache[$i]) ? $this->__driveDirCache[$i] : null);

        if ($s !== null) {
            return $s;
        }

        $s = $this->_getDriveDirectory($i + 1);
        $this->__driveDirCache[$i] = $s;
        return $s;
    }

    function _getUserPath() {
        //For both compatibility and security, we must look this up every time
        return (string) $this->normalize(System::getProperty("user.dir"));
    }

    function _getDrive($path) {
        $path = (string) $path;
        $pl   = $this->prefixLength($path);
        return ($pl === 3) ? substr($path, 0, 2) : null;
    }

    function resolveFile(&$f) {
        $path = (string) $f->getPath();
        $pl   = (int) $f->getPrefixLength();

        if (($pl === 2) && ($path{0} === $this->slash)) {
            return path;			// UNC
        }

        if ($pl === 3) {
            return $path;			// Absolute local
        }

        if ($pl === 0) {
            return (string) ($this->_getUserPath().$this->slashify($path)); //Completely relative
        }

        if ($pl === 1) {			// Drive-relative
            $up = (string) $this->_getUserPath();
            $ud = (string) $this->_getDrive($up);
            if ($ud !== null) {
                return (string) $ud.$path;
            }
            return (string) $up.$path;			//User dir is a UNC path
        }

        if ($pl === 2) {				// Directory-relative
            $up = (string) $this->_getUserPath();
            $ud = (string) $this->_getDrive($up);
            if (($ud !== null) && strStartsWith($ud, $path)) {
                return (string) ($up . $this->slashify(substr($path,2)));
            }
            $drive = (string) $path{0};
            $dir   = (string) $this->_getDriveDirectory($drive);

            $np = (string) "";
            if ($dir !== null) {
                /* When resolving a directory-relative path that refers to a
                drive other than the current drive, insist that the caller
                have read permission on the result */
                $p = (string) $drive . (':'.$dir.$this->slashify(substr($path,2)));

                if (!$this->checkAccess($p, false)) {
                    // FIXME
                    // throw security error
                    die("Can't resolve path $p");
                }
                return $p;
            }
            return (string) $drive.':'.$this->slashify(substr($path,2)); //fake it
        }
        // FIXME
        die("Unresolvable path: $path");
    }

    /* -- most of the following is mapped to the functions mapped th php natives in FileSystem */

    function canonicalize($strPath)       {
        return parent::canonicalize($strPath);
    }

    /* -- Attribute accessors -- */

    function getBooleanAttributes(&$f)     {
        return parent::getBooleanAttributes(&$f);
    }
    function checkAccess(&$f, $write = false) {
        return parent::checkAccess(&$f, $write);
    }
    function getLastModifiedTime(&$f)     {
        return parent::getLastModifiedTime(&$f);
    }
    function getLength(&$f)               {
        return parent::getLength(&$f);
    }

    /* -- File operations -- */

    function createFileExclusively($path) {
        return parent::createFileExclusively($path);
    }
    function delete(&$f)                  {
        return parent::delete($f);
    }
    function deleteOnExit(&$f)            {
        return parent::deleteOnExit($f);
    }
    function listDir(&$f)                 {
        return parent::listDir($f);
    }
    function createDirectory(&$f)         {
        return parent::createDirectory($f);
    }
    function rename(&$f1, &$f2)           {
        return parent::rename($f1, $f2);
    }
    function copy(&$f1, &$f2)                {
        return parent::copy($f1, $f2);
    }
    function setLastModifiedTime(&$f, $time) {
        return parent::setLastModifiedTime($f, $time);
    }

    function setReadOnly(&$f) {
        // dunno how to do this on win
    }

    /* -- Filesystem interface -- */

    function _access($path) {
        if (!$this->checkAccess($path, false)) {
            // FIXME
            // throw security error
            die("Can't resolve path $p");
            return false;
        }
        return true;
    }

    function _nativeListRoots() {
        // FIXME
    }

    function listRoots() {
        $ds = _nativeListRoots();
        $n = 0;
        for ($i = 0; $i < 26; ++$i) {
            if ((($ds >> $i) & 1) !== 0) {
                if (!$this->_access((string)( chr(ord('A') + $i) . ':' + $this->slash))) {
                    $ds &= ~(1 << $i);
                } else {
                    $n++;
                }
            }
        }
        $fs = array();
        $j = (int) 0;
        $slash = (string) $this->slash;
        for ($i = 0; $i < 26; ++$i) {
            if ((($ds >> $i) & 1) !== 0) {
                $fs[$j++] = new File(chr(ord('A') + $i) . ':' + $this->slash);
            }
        }
        return $fs;
    }

    /* -- Basic infrastructure -- */

    /** compares file paths lexicographically */
    function compare(&$f1, &$f2) {
        if (isInstanceOf($f1, 'File') && isInstanceOf($f2, 'File')) {
            $f1Path = $f1->getPath();
            $f2Path = $f2->getPath();
            return (boolean) strcasecmp((string) $f1Path, (string) $f2Path);
        } else {
            die("Runtime exception: IllegalArgumentType");
        }
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

?>
