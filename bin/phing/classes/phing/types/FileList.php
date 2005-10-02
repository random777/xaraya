<?php
/*
 * $Id: FileList.php,v 1.10 2003/03/26 21:53:11 purestorm Exp $
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

import("phing.BuildException");
import("phing.Project");
import("phing.types.DataType");
import("phing.system.io.File");
import("phing.system.util.StringTokenizer");

/**
 * FileList represents an explicitly named list of files. FileLists
 * are useful when you want to capture a list of files regardless of
 * whether they currently exist.
 *
 * @version $Revision: 1.10 $ $Date: 2003/03/26 21:53:11 $
 * @package   phing.types
 */

class FileList extends DataType {

    var $filenames = array();
    var $dir = null;

    function FileList($filelist = null) {
        parent::DataType();
        if ($filelist !== null) {
            $this->dir       = $filelist->dir;
            $this->filenames = $filelist->filenames;
        }
    }

    /**
     * Makes this instance in effect a reference to another FileList
     * instance.
     */
    function setRefid(&$r) {
        if ($this->dir !== null || count($this->filenames) !== 0) {
            throw ($this->tooManyAttributes());
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
        $this->dir = new File((string) $dir);
    }

    function getDir(&$p) {
        if ($this->isReference()) {
            $ret =& $this->getRef($p);
            $ret = $ret->getDir($p);
            return $ret;
        }
        return $this->dir;
    }

    function setFiles($filenames) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes());
            return;
        }
        if ($filenames !== null && count($filenames) > 0) {
            $tok = new StringTokenizer($filenames, ", \t\n\r\f", false);
            while ($tok->hasMoreTokens()) {
                $this->filenames[] = $tok->nextToken();
            }
        }
    }

    /** Returns the list of files represented by this FileList. */
    function getFiles(&$p) {
        if ($this->isReference()) {
            $ret =& $this->getRef($p);
            $ret = $ret->getFiles($p);
            return $ret;
        }

        if ($this->dir === null) {
            throw ( new BuildException("No directory specified for filelist."), __FILE__, __LINE__);
            return;
        }

        if (count($this->filenames) === 0) {
            throw ( new BuildException("No files specified for filelist."), __FILE__, __LINE__);
        }

        $result = $this->filenames;
        return $result;
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
        if (!isInstanceOf($o, "FileList")) {
            $msg = $this->ref->getRefId()." doesn't denote a filelist";
            throw (new BuildException($msg), __FILE__, __LINE__);
            return;
        } else {
            return $o;
        }
    }

}
?>
