<?php
/*
 * $Id: Fileset.php,v 1.59 2003/04/09 15:59:23 thyrell Exp $
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

import('phing.system.io.File');
import('phing.types.DataType');
import('phing.types.Patternset');
import('phing.util.DirectoryScanner');

/**
 * The Fileset class provides methods and properties for accessing
 * and managing filesets. It extends ProjectComponent and thus inherits
 * all methods and properties (not explicitly declared). See ProjectComponent
 * for further detail.
 *
 * TODO:
 *   - merge this with patternsets: Fileset extends Patternset !!!
 *	 requires additional mods to the parsing algo
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.59 $
 * @see	  phing.ProjectComponent
 * @package   phing.types
 */

class Fileset extends DataType {

    var $useDefaultExcludes = true;
    var $defaultPatterns;
    var $additionalPatterns = array();
    var $dir;
    var $isCaseSensitive = true;

    function Fileset($fileset = null) {
        if ($fileset !== null && is_a($fileset, "FileSet")) {
            $this->dir = $fileset->dir;
            $this->defaultPatterns = $fileset->defaultPatterns;
            $this->additionalPatterns = $fileset->additionalPatterns;
            $this->useDefaultExcludes = $fileset->useDefaultExcludes;
            $this->isCaseSensitive = $fileset->isCaseSensitive;
        }
        $this->defaultPatterns =& new Patternset();
    }


    /**
    * Makes this instance in effect a reference to another PatternSet
    * instance.
    * You must not set another attribute or nest elements inside
    * this element if you make it a reference.
    */

    function setRefid(&$r) {
        if ((isset($this->dir) && !is_null($this->dir)) || $this->defaultPatterns->hasPatterns()) {
            throw ($this->tooManyAttributes());
            return;
        }
        if (!empty($this->additionalPatterns)) {
            throw ($this->noChildrenAllowed());
            return;
        }
        parent::setRefid($r);
    }


    function setDir($dir) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        if (is_a($dir, "File")) {
            $dir = $dir->getPath();
        }
        $this->dir =& new File((string) $dir);
    }


    function getDir(&$p) {
        if ($this->isReference()) {
            $ret =& $this->getRef($p);
            $ret = $ret->getDir($p);
            return $ret;
        }
        return $this->dir;
    }


    function &createPatternSet() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        $num = array_push($this->additionalPatterns, new Patternset());
        return $this->additionalPatterns[$num-1];
    }

    /**
    * add a name entry on the include list
    */
    function &createInclude() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        return $this->defaultPatterns->createInclude();
    }

    /**
     * add a name entry on the include files list
     */
    function &createIncludesFile() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        return $this->defaultPatterns->createIncludesFile();
    }

    /**
     * add a name entry on the exclude list
     */
    function &createExclude() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        return $this->defaultPatterns->createExclude();
    }

    /**
     * add a name entry on the include files list
     */
    function &createExcludesFile() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed());
            return;
        }
        return $this->defaultPatterns->createExcludesFile();
    }

    /**
     * Sets the set of include patterns. Patterns may be separated by a comma
     * or a space.
     */
    function setIncludes($includes) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        $this->defaultPatterns->setIncludes($includes);
    }

    /**
     * Sets the set of exclude patterns. Patterns may be separated by a comma
     * or a space.
     */
    function setExcludes($excludes) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        $this->defaultPatterns->setExcludes($excludes);
    }

    /**
     * Sets the name of the file containing the includes patterns.
     *
     * @param $incl The file to fetch the include patterns from.
     * @throws BE
     */
    function setIncludesfile($incl) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        $this->defaultPatterns->setIncludesfile($incl);
    }

    /**
     * Sets the name of the file containing the includes patterns.
     *
     * @param $excl The file to fetch the exclude patterns from.
     * @throws BE
     */
    function setExcludesfile($excl) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        $this->defaultPatterns->setExcludesfile($excl);
    }

    /**
     * Sets whether default exclusions should be used or not.
     *
     * @param $useDefaultExcludes "true"|"on"|"yes" when default exclusions
     *						   should be used, "false"|"off"|"no" when they
     *						   shouldn't be used.
     */
    function setDefaultexcludes($useDefaultExcludes) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        $this->useDefaultExcludes = $useDefaultExcludes;
    }

    /**
     * Sets case sensitivity of the file system
     */
    function setCaseSensitive($isCaseSensitive) {
        $this->isCaseSensitive = $isCaseSensitive;
    }

    /** returns a reference to the dirscanner object belonging to this fileset */
    function &getDirectoryScanner(&$p) {
        if ($this->isReference()) {
            $o =& $this->getRef($p);
            return $o->getDirectoryScanner($p);
        }

        if ($this->dir == null) {
            throw (new BuildException("No directory specified for fileset."));
            return;
        }
        if (!$this->dir->exists()) {
            throw (new BuildException("Directory ".$this->dir->getAbsolutePath()." not found."));
            return;
        }
        if (!$this->dir->isDirectory()) {
            throw (new BuildException($this->dir->getAbsolutePath()." is not a directory."));
            return;
        }
        $ds = new DirectoryScanner();
        $this->_setupDirectoryScanner($ds, $p);
        $ds->scan();
        return $ds;
    }

    /** feed dirscanner with infos defined by this fileset */
    function _setupDirectoryScanner(&$ds, &$p) {
        if ($ds === null) {
            throw (new RuntimeException("DirectoryScanner cannot be null"), __FILE__, __LINE__);
            return;
        }
        // FIXME
        // pass dir directly wehn dirscanner supports File
        $ds->setBasedir($this->dir->getPath());

        for ($i=0; $i<count($this->additionalPatterns); ++$i) {
            $o =& $this->additionalPatterns[$i];
            $this->defaultPatterns->append($o, $p);
        }

        $p->log("FileSet: Setup file scanner in dir " . $this->dir->toString() . " with " . $this->defaultPatterns->toString(), PROJECT_MSG_DEBUG);

        $ds->setIncludes($this->defaultPatterns->getIncludePatterns($p));
        $ds->setExcludes($this->defaultPatterns->getExcludePatterns($p));
        if ($this->useDefaultExcludes) {
            $ds->addDefaultExcludes();
        }
        $ds->setCaseSensitive($this->isCaseSensitive);
    }


    /**
     * Performs the check for circular references and returns the
     * referenced FileSet.
     */
    function &getRef(&$p) {
        if (!$this->checked) {
            $stk = array();
            array_push($stk, $this);
            $this->dieOnCircularReference($stk, $p);
        }

        $o =& $this->ref->getReferencedObject($p);
        if (!isInstanceOf($o, "FileSet")) {
            $msg = $this->ref->getRefId()." doesn't denote a fileset";
            throw (new BuildException($msg));
            return;
        } else {
            return $o;
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
