<?php
/*
 * $Id: SourceFileScanner.php,v 1.12 2003/06/15 12:46:11 purestorm Exp $
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

/**
 *  Utility class that collects the functionality of the various
 *  scanDir methods that have been scattered in several tasks before.
 *
 *  The only method returns an array of source files. The array is a
 *  subset of the files given as a parameter and holds only those that
 *  are newer than their corresponding target files.
 *  @package   phing.util
 */

class SourceFileScanner {

    var $fileUtils;
    var $task;

    /**
     * @param task The task we should log messages through
     */
    function SourceFileScanner(&$task) {
        $this->task =& $task;
        $this->fileUtils = FileUtils::newFileUtils();
    }

    /**
     * Restrict the given set of files to those that are newer than
     * their corresponding target files.
     *
     * @param files   the original set of files
     * @param srcDir  all files are relative to this directory
     * @param destDir target files live here. if null file names
     *                returned by the mapper are assumed to be absolute.
     * @param mapper  knows how to construct a target file names from
     *                source file names.
     * @param force   Boolean that determines if the files should be
     *                forced to be copied.
     */

    function restrict(&$files, &$srcDir, &$destDir, &$mapper, $force = false) {
        $now = time();
        $targetList = "";

        /*
          If we're on Windows, we have to munge the time up to 2 secs to
          be able to check file modification times.
          (Windows has a max resolution of two secs for modification times)
        */
        $osname = strtolower(System::getProperty('os.name'));

        // indexOf()
        $index = ((($res = strpos($osname, 'win')) === false) ? -1 : $res);
        if ($index  >= 0 ) {
            $now += 2000;
        }

        $v = array();

        for ($i=0; $i< count($files); ++$i) {

            $targets = $mapper->main($files[$i]);
            if ($targets === null || count($targets) === 0) {
                $this->task->log($files[$i]." skipped - don't know how to handle it", PROJECT_MSG_VERBOSE);
                continue;
            }

            $src = null;
            if ($srcDir === null) {
                $src = new File($files[$i]);
            } else {

                // SUXX
                $src = $this->fileUtils->resolveFile($srcDir, $files[$i]);
                //print_r($src);

            }

            if ($src->lastModified() > $now) {
                $this->task->log("Warning: ".$files[$i]." modified in the future", PROJECT_MSG_WARN);
            }

            $added = false;
            $targetList = "";

            for ($j=0; (!$added && $j<count($targets)); $j++) {

                $dest = null;
                if ($destDir === null) {
                    $dest = new File($targets[$j]);
                } else {
                    $dest = $this->fileUtils->resolveFile($destDir, $targets[$j]);
                }

                if (!$dest->exists()) {
                    $this->task->log($files[$i]." added as ".$dest->getAbsolutePath()." doesn't exist.", PROJECT_MSG_VERBOSE);
                    $v[] =$files[$i];
                    $added = true;
                } else if ($src->lastModified() > $dest->lastModified()) {
                    $this->task->log($files[$i]." added as ".$dest->getAbsolutePath()." is outdated.", PROJECT_MSG_VERBOSE );
                    $v[]=$files[$i];
                    $added = true;
                } else if ($force === true) {
                    $this->task->log($files[$i]." added as ".$dest->getAbsolutePath()." is forced to be overwritten.", PROJECT_MSG_VERBOSE );
                    $v[]=$files[$i];
                    $added = true;
                } else {
                    if (strlen($targetList) > 0) {
                        $targetList .= ", ";
                    }
                    $targetList .= $dest->getAbsolutePath();
                }
            }

            if (!$added) {
                $this->task->log($files[$i]." omitted as $targetList ".(count($targets) === 1 ? " is " : " are ")."up to date.",  PROJECT_MSG_VERBOSE);
            }

        }
        $result = array();
        $result = $v;
        return $result;
    }

    /**
     * Convenience layer on top of restrict that returns the source
     * files as File objects (containing absolute paths if srcDir is
     * absolute).
     */
    function restrictAsFiles(&$files, &$srcDir, &$destDir, &$mapper) {
        $res = $this->restrict($files, $srcDir, $destDir, $mapper);
        $result = array();
        for ($i=0; $i<count($res); $i++) {
            $result[$i] = new File($srcDir, $res[$i]);
        }
        return $result;
    }
}
?>