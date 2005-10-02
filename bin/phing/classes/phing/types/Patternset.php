<?php
/*
 * $Id: Patternset.php,v 1.21 2003/04/09 15:59:23 thyrell Exp $
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

import('phing.system.io.FileReader');
import('phing.types.DataType');

/**
 * The patternset storage component. Carries all necessary data and methods
 * for the patternset stuff.
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.21 $
 * @package  phing.types
 */

class Patternset extends DataType {

    var $includeList = array();
    var $excludeList = array();
    var $includesFileList = array();
    var $excludesFileList = array();


    function Patternset() {}

    /**
     * Makes this instance in effect a reference to another PatternSet
     * instance.
     * You must not set another attribute or nest elements inside
     * this element if you make it a reference.
     */

    function setRefid(&$r) {
        if (!empty($this->includeList) || !empty($this->excludeList)) {
            throw ($this->tooManyAttributes());
            return;
        }
        parent::setRefid($r);
    }


    /**
    * Add a name entry on the include list
    *
    * @returns PatternsetNameEntry Reference to object
    * @throws  BuildException
    */

    function &createInclude() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        return $this->_addPatternToList($this->includeList);
    }


    /**
    * Add a name entry on the include files list
    *
    * @returns PatternsetNameEntry Reference to object
    * @throws  BuildException
    */

    function &createIncludesFile() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        return $this->_addPatternToList($this->includesFileList);
    }

    /**
    * Add a name entry on the exclude list
    *
    * @returns PatternsetNameEntry Reference to object
    * @throws  BuildException
    */
    function &createExclude() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        return $this->_addPatternToList($this->excludeList);
    }

    /**
     * add a name entry on the exclude files list
    *
    * @returns PatternsetNameEntry Reference to object
    * @throws  BuildException
     */

    function &createExcludesFile() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        return $this->_addPatternToList($this->excludesFileList);
    }


    /**
     * Sets the set of include patterns. Patterns may be separated by a comma
     * or a space.
     *
     * @param   string the string containing the include patterns
    * @returns void
    * @throws  BuildException
     */

    function setIncludes($includes) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        if ($includes !== null && strlen($includes) > 0) {
            $tok = new StringTokenizer((string)$includes, ", ", false);
            while ($tok->hasMoreTokens()) {
                $o =& $this->createInclude();
                $o->setName($tok->nextToken());
            }
        }
    }


    /**
     * Sets the set of exclude patterns. Patterns may be separated by a comma
     * or a space.
     *
     * @param string the string containing the exclude patterns
    * @returns void
    * @throws  BuildException
     */

    function setExcludes($excludes) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        if ($excludes !== null && strlen($excludes) > 0) {
            $tok = new StringTokenizer((string)$excludes, ", ", false);
            while ($tok->hasMoreTokens()) {
                $o =& $this->createExclude();
                $o->setName($tok->nextToken());
            }
        }
    }

    /**
     * add a name entry to the given list
     *
    * @param array List onto which the nameentry should be added
    * @returns PatternsetNameEntry  Reference to the created PsetNameEntry instance
     */

    function &_addPatternToList(&$list) {
        $num = array_push($list, new PatternsetNameEntry());
        return $list[$num-1];
    }


    /**
     * Sets the name of the file containing the includes patterns.
     *
     * @param includesFile The file to fetch the include patterns from.
     */
    function setIncludesfile($includesFile) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        $o =& $this->createIncludesFile();
        $o->setName($includesFile->getAbsolutePath());
    }

    /**
     * Sets the name of the file containing the excludes patterns.
     *
     * @param excludesFile The file to fetch the exclude patterns from.
     */
    function setExcludesfile($excludesFile) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        $o =& $this->createExcludesFile();
        $o->setName($excludesFile->getAbsolutePath());
    }


    /**
     *  Reads path matching patterns from a file and adds them to the
     *  includes or excludes list
     */
    function _readPatterns(&$patternfile, &$patternlist, &$p) {
        // FIXME, read the file
    }


    /** Adds the patterns of the other instance to this set. */
    function append(&$other, &$p) {
        if ($this->isReference()) {
            throw (new BuildException("Cannot append to a reference"));
            return;
        }

        $incl = $other->getIncludePatterns($p);
        if ($incl !== null) {
            for ($i=0; $i<count($incl); ++$i) {
                $o =& $this->createInclude();
                $o->setName($incl[$i]);
            }
        }

        $excl = $other->getExcludePatterns($p);
        if ($excl !== null) {
            for ($i=0; $i<count($excl); ++$i) {
                $o =& $this->createExclude();
                $o->setName($excl[$i]);
            }
        }
    }

    /** Returns the filtered include patterns. */
    function getIncludePatterns(&$p) {
        if ($this->isReference()) {
            $o =& $this->getRef($p);
            return $o->getIncludePatterns($p);
        } else {
            $this->_readFiles($p);
            return $this->_makeArray($this->includeList, $p);
        }
    }

    /** Returns the filtered exclude patterns. */
    function getExcludePatterns(&$p) {
        if ($this->isReference()) {
            $o =& $this->getRef($p);
            return $o->getExcludePatterns($p);
        } else {
            $this->_readFiles($p);
            return $this->_makeArray($this->excludeList, $p);
        }
    }

    /** helper for FileSet. */
    function hasPatterns() {
        return (boolean) count($this->includesFileList) > 0 || count($this->excludesFileList) > 0
               || count($this->includeList) > 0 || count($this->excludeList) > 0;
    }

    /**
     * Performs the check for circular references and returns the
     * referenced PatternSet.
     */
    function &getRef(&$p) {
        if (!$this->checked) {
            $stk = array();
            array_push($stk, $this);
            $this->dieOnCircularReference($stk, $p);
        }
        $o =& $this->ref->getReferencedObject($p);
        if (!isInstanceOf($o, "PatternSet")) {
            $msg = $this->ref->getRefId()." doesn't denote a patternset";
            throw (new BuildException($msg));
            return;
        } else {
            return $o;
        }
    }

    /** Convert a array of PatternSetNameEntry elements into an array of Strings. */
    function _makeArray(&$list, &$p) {
        if (count($list) === 0) {
            return null;
        }

        $tmpNames = array();
        for ($i=0;$i<count($list); ++$i) {
            $ne =& $list[$i];
            $pattern = (string) $ne->evalName($p);
            if ($pattern !== null && strlen($pattern) > 0) {
                array_push($tmpNames, $pattern);
            }
        }
        return $tmpNames;
    }

    /** Read includesfile or excludesfile if not already done so. */
    function _readFiles(&$p) {
        if (count($this->includesFileList) > 0) {
            for ($i=0; $i<count($this->includesFileList); ++$i) {
                $ne =& $this->includesFileList[$i];
                $fileName = (string) $ne->evalName($p);
                if ($fileName !== null) {
                    $inclFile = $p->resolveFile($fileName);
                    if (!$inclFile->exists()) {
                        throw (new BuildException("Includesfile ".$inclFile->getAbsolutePath()." not found."));
                        return;
                    }
                    $this->_readPatterns($inclFile, $this->includeList, $p);
                }
            }
            $this->includesFileList = array();
        }

        if (count($this->excludesFileList) > 0) {
            for ($i=0; $i<count($this->excludesFileList); ++$i) {
                $ne =& $this->excludesFileList[$i];
                $fileName = (string) $ne->evalName($p);
                if ($fileName !== null) {
                    $exclFile = $p->resolveFile($fileName);
                    if (!$exclFile->exists()) {
                        throw (new BuildException("Excludesfile ".$exclFile->getAbsolutePath()." not found."));
                        return;
                    }
                    $this->_readPatterns($exclFile, $this->excludeList, $p);
                }
            }
            $this->excludesFileList = array();
        }
    }


    function toString() {
        $includes = $this->_makeArray($this->includeList, $this->project);
        $excludes = $this->_makeArray($this->excludeList, $this->project);

        $includes = ($includes === null) ? "empty" : implode(",", $includes);
        $excludes = ($excludes === null) ? "empty" : implode(",", $excludes);
        return "patternSet{ includes: $includes  excludes: $excludes }";
    }
}


/*
 * Note, this class here should become a nested class to
 * Patternset (Patternset:NameEntry) as it is only needed
 * internally.
 * This is not possible with php 4.x right now so we place
 * this class (against good style) in this file.
 */

class PatternsetNameEntry {

    var $name       = null;
    var $ifCond     = null;
    var $unlessCond = null;


    function setName($name) {
        $this->name = (string) $name;
    }


    function setIf($cond) {
        $this->ifCond = (string) $cond;
    }


    function setUnless($cond) {
        $this->unlessCond = (string) cond;
    }


    function getName() {
        return $this->name;
    }


    function evalName(&$project) {
        return $this->valid($project) ? $this->name : null;
    }


    function valid(&$project) {
        if ($this->ifCond !== null && $project->getProperty($this->ifCond) === null) {
            return false;
        } else if ($this->unlessCond !== null && $project->getProperty($this->unlessCond) !== null) {
            return false;
        }
        return true;
    }


    function toString() {
        $buf = $this->name;
        if (($this->ifCond !== null) || ($this->unlessCond !== null)) {
            $buf .= ":";
            $connector = "";

            if ($this->ifCond !== null) {
                $buf .= "if->{$this->ifCond}";
                $connector = ";";
            }
            if ($this->unlessCond !== null) {
                $buf .= "$connector unless->{$this->unlessCond}";
            }
        }
        return (string) $buf;
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
