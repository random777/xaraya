<?php
/*
 * $Id: DirectoryScanner.php,v 1.11 2003/04/09 15:59:24 thyrell Exp $
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

import('phing.system.lang.functions');

/**
 * Class for scanning a directory for files/directories that match a certain
 * criteria.
 *
 * These criteria consist of a set of include and exclude patterns. With these
 * patterns, you can select which files you want to have included, and which
 * files you want to have excluded.
 *
 * The idea is simple. A given directory is recursively scanned for all files
 * and directories. Each file/directory is matched against a set of include
 * and exclude patterns. Only files/directories that match at least one
 * pattern of the include pattern list, and don't match a pattern of the
 * exclude pattern list will be placed in the list of files/directories found.
 *
 * When no list of include patterns is supplied, "**" will be used, which
 * means that everything will be matched. When no list of exclude patterns is
 * supplied, an empty list is used, such that nothing will be excluded.
 *
 * The pattern matching is done as follows:
 * The name to be matched is split up in path segments. A path segment is the
 * name of a directory or file, which is bounded by DIRECTORY_SEPARATOR
 * ('/' under UNIX, '\' under Windows).
 * E.g. "abc/def/ghi/xyz.php" is split up in the segments "abc", "def", "ghi"
 * and "xyz.php".
 * The same is done for the pattern against which should be matched.
 *
 * Then the segments of the name and the pattern will be matched against each
 * other. When '**' is used for a path segment in the pattern, then it matches
 * zero or more path segments of the name.
 *
 * There are special case regarding the use of DIRECTORY_SEPARATOR at
 * the beginning of the pattern and the string to match:
 * When a pattern starts with a DIRECTORY_SEPARATOR, the string
 * to match must also start with a DIRECTORY_SEPARATOR.
 * When a pattern does not start with a DIRECTORY_SEPARATOR, the
 * string to match may not start with a DIRECTORY_SEPARATOR.
 * When one of these rules is not obeyed, the string will not
 * match.
 *
 * When a name path segment is matched against a pattern path segment, the
 * following special characters can be used:
 *   '*' matches zero or more characters,
 *   '?' matches one character.
 *
 * Examples:
 *
 * "**\*.php" matches all .php files/dirs in a directory tree.
 *
 * "test\a??.php" matches all files/dirs which start with an 'a', then two
 * more characters and then ".php", in a directory called test.
 *
 * "**" matches everything in a directory tree.
 *
 * "**\test\**\XYZ*" matches all files/dirs that start with "XYZ" and where
 * there is a parent directory called test (e.g. "abc\test\def\ghi\XYZ123").
 *
 * Case sensitivity may be turned off if necessary.  By default, it is
 * turned on.
 *
 * Example of usage:
 *   $ds = new DirectroyScanner();
 *   $includes = array("**\*.php");
 *   $excludes = array("modules\*\**");
 *   $ds->SetIncludes($includes);
 *   $ds->SetExcludes($excludes);
 *   $ds->SetBasedir("test");
 *   $ds->SetCaseSensitive(true);
 *   $ds->Scan();
 *
 *   print("FILES:");
 *   $files = ds->GetIncludedFiles();
 *   for ($i = 0; $i < count($files);++$i) {
 *     println("$files[$i]\n");
 *   }
 *
 * This will scan a directory called test for .php files, but excludes all
 * .php files in all directories under a directory called "modules"
 *
 * This class is complete preg/ereg free port of the Java class
 * org.apache.tools.ant.DirectoryScanner. Even functions that use preg/ereg
 * internally (like split()) are not used. Only the _fast_ string functions
 * and comparison operators (=== !=== etc) are used for matching and tokenizing.
 *
 *  @author   Arnout J. Kuiper, ajkuiper@wxs.nl
 *  @author   Magesh Umasankar, umagesh@rediffmail.com
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *
 *  @version   $Revision: 1.11 $ $Date: 2003/04/09 15:59:24 $
 *  @package   phing.util
 */

class DirectoryScanner {

	/** default set of excludes */
    var $DEFAULTEXCLUDES = array(
		"**/*~",
        "**/#*#",
        "**/.#*",
        "**/%*%",
        "**/CVS",
        "**/CVS/**",
        "**/.cvsignore",
        "**/SCCS",
        "**/SCCS/**",
        "**/vssver.scc"
    );

    /** The base directory which should be scanned. */
    var $basedir;

    /** The patterns for the files that should be included. */
    var $includes = null;

    /** The patterns for the files that should be excluded. */
    var $excludes = null;

    /**
     * The files that where found and matched at least one includes, and matched
     * no excludes.
     */
    var $filesIncluded;

    /** The files that where found and did not match any includes. Trie */
    var $filesNotIncluded;

    /**
     * The files that where found and matched at least one includes, and also
     * matched at least one excludes. Trie object.
     */
    var $filesExcluded;

    /**
     * The directories that where found and matched at least one includes, and
     * matched no excludes.
     */
    var $dirsIncluded;

    /** The directories that where found and did not match any includes. */
    var $dirsNotIncluded;

    /**
     * The files that where found and matched at least one includes, and also
     * matched at least one excludes.
     */
    var $dirsExcluded;

    /** Have the vars holding our results been built by a slow scan? */
    var $haveSlowResults = false;

    /** Should the file system be treated as a case sensitive one? */
    var $isCaseSensitive = true;

	/**
     * Constructor. Empty
     */
	function DirectoryScanner() {}

	/**
     * Does the path match the start of this pattern up to the first "**".
	 * This is a static mehtod and should always be called static
     *
     * This is not a general purpose test and should only be used if you
     * can live with false positives.
     *
     * pattern=**\a and str=b will yield true.
     *
     * @param   pattern             the (non-null) pattern to match against
     * @param   str                 the (non-null) string (path) to match
     * @param   isCaseSensitive     must matches be case sensitive?
	 * @return  boolean             true if matches, otherwise false
     */
	function MatchPatternStart($_pattern, $_str, $_isCaseSensitive) {
        // When str starts with a DIRECTORY_SEPARATOR, pattern must
		// start with a  DIRECTORY_SEPARATOR.
        // When pattern starts with a DIRECTORY_SEPARATOR,
		// str must start with a DIRECTORY_SEPARATOR.

		if (strStartsWith(DIRECTORY_SEPARATOR, $_str) !==
            strStartsWith(DIRECTORY_SEPARATOR, $_pattern)) {
            return false;
        }

		// tokenize the pattern
        $patDirs = array();
		$tok = strtok($_pattern, DIRECTORY_SEPARATOR);
		while ($tok !== FALSE) {
			$patDirs[] = $tok;
			$tok = strtok(DIRECTORY_SEPARATOR);
		}

		// tokenize the string
        $strDirs = array();
		$tok = strtok($_str, DIRECTORY_SEPARATOR);
		while ($tok !== FALSE) {
			$strDirs[] = $tok;
			$tok = strtok(DIRECTORY_SEPARATOR);
		}

		$patIdxStart = 0;
        $patIdxEnd   = count($patDirs) -1;
        $strIdxStart = 0;
        $strIdxEnd   = count($strDirs) -1;

        // up to first '**'
        while (($patIdxStart <= $patIdxEnd) && ($strIdxStart <= $strIdxEnd)) {

			$patDir = (string) $patDirs[$patIdxStart];

			if ($patDir === "**") {
                break;
            }

			if (!DirectoryScanner::Match($patDir, (string) $strDirs[$strIdxStart], $this->isCaseSensitive)) {
                return false;
            }

			$patIdxStart++;
            $strIdxStart++;
        }

        if ($strIdxStart > $strIdxEnd) {
            // String is exhausted
            return true;
        } else if ($patIdxStart > $patIdxEnd) {
            // String not exhausted, but pattern is. Failure.
            return false;
        } else {
            // pattern now holds ** while string is not exhausted
            // this will generate false positives but we can live with that.
            return true;
        }
	}

    /**
     * Matches a path against a pattern. Static
     *
     * @param pattern            the (non-null) pattern to match against
     * @param str                the (non-null) string (path) to match
     * @param isCaseSensitive    must a case sensitive match be done?
     *
     * @return true when the pattern matches against the string.
     *         false otherwise.
     */
	function MatchPath($_pattern, $_str, $_isCaseSensitive) {
        // When str starts with a DIRECTORY_SEPARATOR, pattern must
		// start with a DIRECTORY_SEPARATOR.
        // When pattern starts with a DIRECTORY_SEPARATOR, str must
		// start with a DIRECTORY_SEPARATOR.

		if (strStartsWith(DIRECTORY_SEPARATOR, $_str) !==
            strStartsWith(DIRECTORY_SEPARATOR, $_pattern)) {
            return false;
        }

		// tokenize the pattern
        $patDirs = array();
		$tok = strtok($_pattern, DIRECTORY_SEPARATOR);
		while ($tok !== FALSE) {
			$patDirs[] = $tok;
			$tok = strtok(DIRECTORY_SEPARATOR);
		}

		// tokenize the string
        $strDirs = array();
		$tok = strtok($_str, DIRECTORY_SEPARATOR);
		while ($tok !== FALSE) {
			$strDirs[] = $tok;
			$tok = strtok(DIRECTORY_SEPARATOR);
		}

		$patIdxStart = 0;
        $patIdxEnd   = count($patDirs)-1;
        $strIdxStart = 0;
        $strIdxEnd   = count($strDirs)-1;

        // up to first '**'
        while ($patIdxStart <= $patIdxEnd && $strIdxStart <= $strIdxEnd) {
            $patDir = (string) $patDirs[$patIdxStart];
            if ($patDir === "**") {
                break;
            }
            if (!DirectoryScanner::Match($patDir,(string) $strDirs[$strIdxStart], $_isCaseSensitive)) {
                return false;
            }
            $patIdxStart++;
            $strIdxStart++;
        }

		if ($strIdxStart > $strIdxEnd) {
            // String is exhausted
            for ($i = $patIdxStart; $i <= $patIdxEnd; $i++) {
                if (!$patDirs[$i] === "**") {
                    return false;
                }
            }

			return true;

        } else {
            if ($patIdxStart > $patIdxEnd) {
                // String not exhausted, but pattern is. Failure.
                return false;
            }
        }

        // up to last '**'
        while ($patIdxStart <= $patIdxEnd && $strIdxStart <= $strIdxEnd) {
            $patDir = (string) $patDirs[$patIdxEnd];
            if ($patDir === "**") {
                break;
            }

			if (!DirectoryScanner::Match($patDir,(string) $strDirs[$strIdxEnd], $_isCaseSensitive)) {
                return false;
            }

			$patIdxEnd--;
            $strIdxEnd--;
        }

		if ($strIdxStart > $strIdxEnd) {
            // String is exhausted
            for ($i = $patIdxStart; $i <= $patIdxEnd; $i++) {
                if (!$patDirs[$i] === "**") {
                    return false;
                }
            }
            return true;
        }

        while ($patIdxStart != $patIdxEnd && $strIdxStart <= $strIdxEnd) {
            $patIdxTmp = -1;
            for ($i = $patIdxStart+1; $i <= $patIdxEnd; $i++) {
                if ($patDirs[$i] === "**") {
                    $patIdxTmp = $i;
                    break;
                }
            }
            if ($patIdxTmp == $patIdxStart+1) {
                // '**/**' situation, so skip one
                $patIdxStart++;
                continue;
            }
            // Find the pattern between padIdxStart & padIdxTmp in str between
            // strIdxStart & strIdxEnd
            $patLength = ($patIdxTmp-$patIdxStart-1);
            $strLength = ($strIdxEnd-$strIdxStart+1);
            $foundIdx  = -1;

// continue 3 enters here
// NOT SURE THIS WORKS RIGHT
            for ($i = 0; $i <= $strLength - $patLength; $i++) {
                for ($j = 0; $j < $patLength; $j++) {
                    $subPat = (string) $patDirs[$patIdxStart+$j+1];
                    $subStr = (string) $strDirs[$strIdxStart+$i+$j];
                    if (!DirectoryScanner::Match($subPat,$subStr, $_isCaseSensitive)) {
                        continue 2;
                    }
                }

                $foundIdx = $strIdxStart+$i;
                break;
            }

            if ($foundIdx == -1) {
                return false;
            }

            $patIdxStart = $patIdxTmp;
            $strIdxStart = $foundIdx+$patLength;
        }

        for ($i = $patIdxStart; $i <= $patIdxEnd; $i++) {
            if (!$patDirs[$i] === "**") {
                return false;
            }
        }

        return true;
    }

	/**
     * Matches a string against a pattern. The pattern contains two special
     * characters:
     * '*' which means zero or more characters,
     * '?' which means one and only one character.
     *
     * @param  pattern the (non-null) pattern to match against
     * @param  str     the (non-null) string that must be matched against the
     *                 pattern
     *
     * @return boolean true when the string matches against the pattern,
     *                 false otherwise.
	 * @access public
     */
	function Match($_pattern, $_str, $_isCaseSensitive) {
        $patArr = strToCharArray($_pattern);
        $strArr = strToCharArray($_str);

		$patIdxStart = 0;
        $patIdxEnd   = count($patArr) -1;
        $strIdxStart = 0;
        $strIdxEnd   = count($strArr) -1;
        $ch = null;

        $containsStar = false;
        for ($i = 0; $i < count($patArr); ++$i) {
            if ($patArr[$i] === '*') {
                $containsStar = true;
                break;
            }
        }

        if (!$containsStar) {
            // No '*'s, so we make a shortcut
            if ($patIdxEnd !== $strIdxEnd) {
                return false; // Pattern and string do not have the same size
            }
            for ($i = 0; $i <= $patIdxEnd; ++$i) {
                $ch = $patArr[$i];
                if ($ch !== '?') {
                    if ($_isCaseSensitive && ($ch !== $strArr[$i])) {
                        return false;// Character mismatch
                    }
                    if (!$_isCaseSensitive && (strtoupper($ch) !== strtoupper($strArr[$i]))) {
                        return false; // Character mismatch
                    }
                }
            }
            return true; // String matches against pattern
        }

        if ($patIdxEnd == 0) {
            return true; // Pattern contains only '*', which matches anything
        }

        // Process characters before first star
        while(($ch = $patArr[$patIdxStart]) !== '*' && $strIdxStart <= $strIdxEnd) {
            if ($ch != '?') {
                if ($_isCaseSensitive && $ch != $strArr[$strIdxStart]) {
                    return false;// Character mismatch
                }
                if (!$_isCaseSensitive && (strtoupper($ch) !=strtoupper($strArr[$strIdxStart]))) {
                    return false;// Character mismatch
                }
            }
            $patIdxStart++;
            $strIdxStart++;
        }
        if ($strIdxStart > $strIdxEnd) {
            // All characters in the string are used. Check if only '*'s are
            // left in the pattern. If so, we succeeded. Otherwise failure.
            for ($i = $patIdxStart; $i <= $patIdxEnd; ++$i) {
                if ($patArr[$i] !== '*') {
                    return false;
                }
            }
            return true;
        }

        // Process characters after last star
        while(($ch = $patArr[$patIdxEnd]) !== '*' && $strIdxStart <= $strIdxEnd) {
            if ($ch !== '?') {
                if ($_isCaseSensitive && $ch !== $strArr[$strIdxEnd]) {
                    return false;// Character mismatch
                }
                if (!$_isCaseSensitive && (strtoupper($ch) !=strtoUpper($strArr[$strIdxEnd]))) {
                    return false;// Character mismatch
                }
            }
            $patIdxEnd--;
            $strIdxEnd--;
        }

		if ($strIdxStart > $strIdxEnd) {
            // All characters in the string are used. Check if only '*'s are
            // left in the pattern. If so, we succeeded. Otherwise failure.
            for ($i = $patIdxStart; $i <= $patIdxEnd; ++$i) {
                if ($patArr[$i] !== '*') {
                    return false;
                }
            }
            return true;
        }

        // process pattern between stars. padIdxStart and patIdxEnd point
        // always to a '*'.
        while ($patIdxStart != $patIdxEnd && $strIdxStart <= $strIdxEnd) {
            $patIdxTmp = -1;
            for ($i = $patIdxStart+1; $i <= $patIdxEnd; ++$i) {
                if ($patArr[$i] === '*') {
                    $patIdxTmp = $i;
                    break;
                }
            }
            if ($patIdxTmp == $patIdxStart+1) {
                // Two stars next to each other, skip the first one.
                $patIdxStart++;
                continue;
            }
            // Find the pattern between padIdxStart & padIdxTmp in str between
            // strIdxStart & strIdxEnd
            $patLength = ($patIdxTmp-$patIdxStart-1);
            $strLength = ($strIdxEnd-$strIdxStart+1);
            $foundIdx  = -1;

// "continue 2;" will hook up here
// NOT SURE THIS WORKS
            for ($i = 0; $i <= $strLength - $patLength; ++$i) {
                for ($j = 0; $j < $patLength; ++$j) {
                    $ch = $patArr[$patIdxStart+$j+1];
                    if ($ch !== '?') {
                        if ($_isCaseSensitive && $ch !== $strArr[$strIdxStart+$i+$j]) {
                            continue 2;
                        }
                        if (!$_isCaseSensitive && (strtoupper($ch) !==
                            strtoupper($strArr[$strIdxStart+$i+$j]))) {
                            continue 2;
                        }
                    }
                }

                $foundIdx = $strIdxStart+$i;
                break;
            }

            if ($foundIdx === -1) {
                return false;
            }

            $patIdxStart = $patIdxTmp;
            $strIdxStart = $foundIdx+$patLength;
        }

        // All characters in the string are used. Check if only '*'s are left
        // in the pattern. If so, we succeeded. Otherwise failure.
        for ($i = $patIdxStart; $i <= $patIdxEnd; ++$i) {
            if ($patArr[$i] !== '*') {
                return false;
            }
        }
        return true;
	}

	/**
     * Sets the basedir for scanning. This is the directory that is scanned
     * recursively. All '/' and '\' characters are replaced by
     * DIRECTORY_SEPARATOR
     *
     * @param basedir the (non-null) basedir for scanning
     */
	function SetBasedir($_basedir) {
		$_basedir = str_replace('\\', DIRECTORY_SEPARATOR, $_basedir);
		$_basedir = str_replace('/', DIRECTORY_SEPARATOR, $_basedir);
		$this->basedir = $_basedir;
    }

    /**
     * Gets the basedir that is used for scanning. This is the directory that
     * is scanned recursively.
     *
     * @return the basedir that is used for scanning
     */
	function GetBasedir() {
        return $this->basedir;
    }

    /**
     * Sets the case sensitivity of the file system
     *
     * @param specifies if the filesystem is case sensitive
     */
    function SetCaseSensitive($_isCaseSensitive) {
        $this->isCaseSensitive = ($_isCaseSensitive) ? true : false;
    }

	/**
     * Sets the set of include patterns to use. All '/' and '\' characters are
     * replaced by DIRECTORY_SEPARATOR. So the separator used need
     * not match DIRECTORY_SEPARATOR.
     *
     * When a pattern ends with a '/' or '\', "**" is appended.
     *
     * @param includes list of include patterns
     */
	function SetIncludes($_includes = array()) {
        if (empty($_includes) || is_null($_includes)) {
            $this->includes = null;
        } else {
            for ($i = 0; $i < count($_includes); $i++) {
                $pattern = null;
				$pattern = str_replace('\\', DIRECTORY_SEPARATOR, $_includes[$i]);
				$pattern = str_replace('/', DIRECTORY_SEPARATOR, $pattern);
                if (strEndsWith(DIRECTORY_SEPARATOR, $pattern)) {
                    $pattern .= "**";
                }
                $this->includes[] = $pattern;
            }
        }
    }

    /**
     * Sets the set of exclude patterns to use. All '/' and '\' characters are
     * replaced by <code>File.separatorChar</code>. So the separator used need
     * not match <code>File.separatorChar</code>.
     *
     * When a pattern ends with a '/' or '\', "**" is appended.
     *
     * @param excludes list of exclude patterns
     */

	function SetExcludes($_excludes = array()) {
        if (empty($_excludes) || is_null($_excludes)) {
            $this->excludes = null;
        } else {
            for ($i = 0; $i < count($_excludes); $i++) {
                $pattern = null;
				$pattern = str_replace('\\', DIRECTORY_SEPARATOR, $_excludes[$i]);
				$pattern = str_replace('/', DIRECTORY_SEPARATOR, $pattern);
                if (strEndsWith(DIRECTORY_SEPARATOR, $pattern)) {
                    $pattern .= "**";
                }
                $this->excludes[] = $pattern;
            }
        }
    }

    /**
     * Scans the base directory for files that match at least one include
     * pattern, and don't match any exclude patterns.
     *
     */

	function Scan()
	{
		if ((empty($this->basedir)) || (!is_dir($this->basedir))) {
			return false;
		}

        if (is_null($this->includes)) {
            // No includes supplied, so set it to 'matches all'
            $this->includes = array("**");
		}
		if (is_null($this->excludes)) {
            $this->excludes = array();
        }

		$this->filesIncluded = array();
		$this->filesNotIncluded = array();
		$this->filesExcluded = array();
		$this->dirsIncluded = array();
		$this->dirsNotIncluded = array();
		$this->dirsExcluded = array();
        if ($this->_IsIncluded("")) {
            if (!$this->_IsExcluded("")) {
                $this->dirsIncluded[] = "";
            } else {
				$this->dirsExcluded[] = "";
            }
        } else {
            $this->dirsNotIncluded[] = "";
        }

		$this->_Scandir($this->basedir, "", true);
		return true;
    }

	/**
     * Toplevel invocation for the scan.
     *
     * Returns immediately if a slow scan has already been requested.
     */
	function _SlowScan() {

        if ($this->haveSlowResults) {
            return;
        }

		// copy trie object add CopyInto() method
        $excl    = $this->dirsExcluded;
        $notIncl = $this->dirsNotIncluded;

        for ($i=0; $i<count($excl); $i++) {
            if (!$this->_CouldHoldIncluded($excl[$i])) {
                $this->_Scandir($this->basedir.$excl[$i], $excl[$i].DIRECTORY_SEPARATOR, false);
            }
        }

        for ($i=0; $i<count($notIncl); $i++) {
            if (!$this->_CouldHoldIncluded($notIncl[$i])) {
                $this->_Scandir($this->basedir.$notIncl[$i], $notIncl[$i].DIRECTORY_SEPARATOR, false);
            }
        }

        $this->haveSlowResults = true;
	}

	/**
	 * Lists contens of a given directory and returns array with entries
	 *
	 * @param   src String. Source path and name file to copy.
	 *
	 * @access  public
	 * @return  array  directory entries
	 * @author  Albert Lash, alash@plateauinnovation.com
	 */

	function ListDir($_dir) {
		$d = dir($_dir);
		$list = array();

		while($entry = $d->read()) {
			if ($entry != "." && $entry != "..") {
				$list[] = $entry;
			}
		}

		$d->close();
		return $list;
	}

    /**
     * Scans the passed dir for files and directories. Found files and
     * directories are placed in their respective collections, based on the
     * matching of includes and excludes. When a directory is found, it is
     * scanned recursively.
     *
     * @param dir   the directory to scan
     * @param vpath the path relative to the basedir (needed to prevent
     *              problems with an absolute path when using dir)
     *
	 * @access private
     * @see #filesIncluded
     * @see #filesNotIncluded
     * @see #filesExcluded
     * @see #dirsIncluded
     * @see #dirsNotIncluded
     * @see #dirsExcluded
     */
	function _Scandir($_rootdir, $_vpath, $_fast) {

		$newfiles = DirectoryScanner::ListDir($_rootdir);

		// FIXME
        //if (empty($newfiles)) {
            /*
             * two reasons are mentioned in the API docs for File::lister
             * (1) dir is not a directory. This is impossible as
             *     we wouldn't get here in this case.
             * (2) an IO error occurred (why doesn't it throw an exception
             *     then???)
             */
            //die("IO error scanning directory ". realpath($_rootdir));
        //}

		// not quite perfect
		for ($i = 0; $i < count($newfiles); ++$i) {

			$file = $_rootdir.DIRECTORY_SEPARATOR. $newfiles[$i];
			$name = $_vpath . $newfiles[$i];

			if (@is_dir($file)) {
				if ($this->_IsIncluded($name)) {
					if (!$this->_IsExcluded($name)) {
						$this->dirsIncluded[] = $name;
						if ($_fast) {
							$this->_Scandir($file, $name.DIRECTORY_SEPARATOR, $_fast);
						}
					} else {
						$this->dirsExcluded[] = $name;
						if ($_fast && $this->_CouldHoldIncluded($name)) {
							$this->_Scandir($file, $name.DIRECTORY_SEPARATOR, $_fast);
						}
					}
				} else {
					$this->dirsNotIncluded[] = $name;
					if ($_fast && $this->_CouldHoldIncluded($name)) {
						$this->_Scandir($file, $name.DIRECTORY_SEPARATOR, $_fast);
					}
				}
				if (!$_fast) {
					$this->_Scandir($file, $name.DIRECTORY_SEPARATOR, $_fast);
				}
			} elseif (@is_file($file)) {
				if ($this->_IsIncluded($name)) {
					if (!$this->_IsExcluded($name)) {
						$this->filesIncluded[] = $name;
					} else {
						$this->filesExcluded[] = $name;
					}
				} else {
					$this->filesNotIncluded[] = $name;
				}
			}
		}
	}

	/**
     * Tests whether a name matches against at least one include pattern.
     *
     * @param name the name to match
     * @return <code>true</code> when the name matches against at least one
     *         include pattern, <code>false</code> otherwise.
     */
	function _IsIncluded($_name) {
        for ($i = 0; $i < count($this->includes); ++$i) {
            if (DirectoryScanner::MatchPath($this->includes[$i], $_name, $this->isCaseSensitive)) {
                return true;
            }
        }
        return false;
    }

	/**
     * Tests whether a name matches the start of at least one include pattern.
     *
     * @param name the name to match
     * @return <code>true</code> when the name matches against at least one
     *         include pattern, <code>false</code> otherwise.
     */
    function _CouldHoldIncluded($_name) {
        for ($i = 0; $i < count($this->includes); $i++) {
            if (DirectoryScanner::MatchPatternStart($this->includes[$i], $_name, $this->isCaseSensitive)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tests whether a name matches against at least one exclude pattern.
     *
     * @param name the name to match
     * @return <code>true</code> when the name matches against at least one
     *         exclude pattern, <code>false</code> otherwise.
     */

	function _IsExcluded($_name) {
		for ($i = 0; $i < count($this->excludes); $i++) {
            if (DirectoryScanner::MatchPath($this->excludes[$i], $_name, $this->isCaseSensitive)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the names of the files that matched at least one of the include
     * patterns, and matched none of the exclude patterns.
     * The names are relative to the basedir.
     *
     * @return the names of the files
     */
	function getIncludedFiles() {
		$files = array();
        for ($i = 0; $i < count($this->filesIncluded); ++$i) {
            $files[$i] = (string) $this->filesIncluded[$i];
        }
        return $files;
    }

    /**
     * Get the names of the files that matched at none of the include patterns.
     * The names are relative to the basedir.
     *
     * @return the names of the files
     */
	function GetNotIncludedFiles() {
		$this->_SlowScan();
		$files= array();
        for ($i = 0; $i < count($this->filesNotIncluded); ++$i) {
            $files[$i] = (string) $this->filesNotIncluded[$i];
        }
        return $files;
    }

    /**
     * Get the names of the files that matched at least one of the include
     * patterns, an matched also at least one of the exclude patterns.
     * The names are relative to the basedir.
     *
     * @return the names of the files
     */

	function GetExcludedFiles() {
		$this->_SlowScan();

		$files = array();
        for ($i = 0; $i < count($this->filesExcluded); ++$i) {
            $files[$i] = (string) $this->filesExcluded[$i];
        }
        return $files;
    }

    /**
     * Get the names of the directories that matched at least one of the include
     * patterns, an matched none of the exclude patterns.
     * The names are relative to the basedir.
     *
     * @return the names of the directories
     */

	function GetIncludedDirectories() {
		$directories = array();
        for ($i = 0; $i < count($this->dirsIncluded); ++$i) {
            $directories[$i] = (string) $this->dirsIncluded[$i];
        }
        return $directories;
    }

    /**
     * Get the names of the directories that matched at none of the include
     * patterns.
     * The names are relative to the basedir.
     *
     * @return the names of the directories
     */

	function GetNotIncludedDirectories() {
		$this->_SlowScan();
		$directories = array();
        for ($i = 0; $i < count($this->dirsNotIncluded); ++$i) {
            $directories[$i] = (string) $this->dirsNotIncluded[$i];
        }
        return $directories;
    }

    /**
     * Get the names of the directories that matched at least one of the include
     * patterns, an matched also at least one of the exclude patterns.
     * The names are relative to the basedir.
     *
     * @return the names of the directories
     */
	function GetExcludedDirectories() {
		$this->_SlowScan();
		$directories = array();
        for ($i = 0; $i < count($this->dirsExcluded); ++$i) {
            $directories[$i] = (string) $this->dirsExcluded[$i];
        }
        return $directories;
    }

	/**
     * Adds the array with default exclusions to the current exclusions set.
     *
     */
    function AddDefaultExcludes() {
		$excludesLength = ($this->excludes == null) ? 0 : count($this->excludes);
		for ($i=0; $i < count($this->DEFAULTEXCLUDES); ++$i) {
			$pattern = str_replace('\\', DIRECTORY_SEPARATOR, $this->DEFAULTEXCLUDES[$i]);
			$pattern = str_replace('/', DIRECTORY_SEPARATOR, $pattern);
			$this->excludes[] = $pattern;
        }
    }

}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>
