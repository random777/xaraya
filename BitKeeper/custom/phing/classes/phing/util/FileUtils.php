<?php
// {{{ Header
/*
 * -File       $Id: FileUtils.php,v 1.18 2003/07/09 06:06:40 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2003, entity
 * -Author     
 */
// }}}

// FILE UTIL CLASS
//  - handles os independent stuff etc
//  - mapper stuff
//  - filter stuff
// sorta interface to more low level File.php


// add createNewFile(&$file);
// setFileLastModified(&$file, $millis);
import('phing.system.lang.Character');
import('phing.system.util.StringTokenizer');
import('phing.system.lang.functions');
import('phing.system.io.BufferedReader');
import('phing.system.io.BufferedWriter');
import('phing.filters.util.ChainReaderHelper');

/**
 *  @package   phing.util
 */
class FileUtils {

    /**
     * Constructor, empty
     *
     */
    function FileUtils() {}

    /**
     * Factory method ;)
     *
     */
    function newFileUtils() {
        return new FileUtils();
    }

    function copyFile(&$sourceFile, &$destFile, $overwrite = false, $preserveLastModified = true, $filterSet = null, $filterChains = null, &$project) {
        if (!isInstanceOf($sourceFile, 'File') || !isInstanceOf($destFile, 'File')) {
            // this will terminate execution dumping the exception stack trace
            new RuntimeException("Illegal arguments provided to copyFile()", __FILE__, __LINE__);
        }

        if ($overwrite || !$destFile->exists() || $destFile->lastModified() < $sourceFile->lastModified()) {
            if ($destFile->exists() && $destFile->isFile()) {
                $destFile->delete();
            }

            // ensure that parent dir of dest file exists!
            $parent = $destFile->getParentFile();
            if ($parent !== null && !$parent->exists()) {
                $parent->mkdirs();
            }

            $filterChainsAvailable = ( (is_array($filterChains)) && (count($filterChains) > 0) );

            if ($filterChainsAvailable || $filterSet !== null) {
                // extend this here using filereader/writer to support filters later on
                $in  = new BufferedReader(new FileReader($sourceFile));
                $out = new BufferedWriter(new FileWriter($destFile));

                if ( $filterChainsAvailable ) {
                    $crh = new ChainReaderHelper();
                    $crh->setBufferSize(65536); // 64k buffer, but isn't being used (yet?)
                    $crh->setPrimaryReader($in);
                    $crh->setFilterChains($filterChains);
                    $crh->setProject($project);
                    $rdr = &$crh->getAssembledReader();
					
					// [hlellelid: no subsequent buffering necessary]
                    // $in  = new BufferedReader(&$rdr);
					$in = &$rdr;
                }
				
				// New read() methods returns a big buffer.				
				while(-1 !== ($buffer = $in->read())) { // -1 indicates EOF
					$out->write($buffer);
				}

                if ( $in !== null )
                    $in->close();
                if ( $out !== null )
                    $out->close();
            } else {
                // simple copy
                $sourceFile->copyTo($destFile);
            }

            if ($preserveLastModified) {
                $destFile->setLastModified($sourceFile->lastModified());
            }

        }
    }

    /**
     * Interpret the filename as a file relative to the given file -
     * unless the filename already represents an absolute filename.
     *
     * @param  $file the "reference" file for relative paths. This
     *         instance must be an absolute file and must not contain
     *         ./ or ../ sequences (same for \ instead of /).
     * @param  $filename a file name
     *
     * @return an absolute file that doesn't contain ./ or ../ sequences
    *         and uses the correct separator for the current platform.
     */
    function resolveFile(&$file, $filename) {
        // remove this and use the static class constant File::seperator
        // as soon as ZE2 is ready
        $fs = FileSystem::getFileSystem();

        $filename = (string) str_replace('/', $fs->getSeparator(), str_replace('\\', $fs->getSeparator(), $filename));

        // deal with absolute files
        if (strStartsWith($fs->getSeparator(), $filename) ||
                (strlen($filename) >= 2 && Character::isLetter($filename{0}) && $filename{1} === ':')) {
            return new File((string)$this->normalize($filename));
        }

        if (strlen($filename) >= 2 && Character::isLetter($filename{0}) && $filename{1} === ':') {
            return new File((string)$this->normalize($filename));
        }

        $helpFile = new File($file->getAbsolutePath());

        $tok = new StringTokenizer($filename, $fs->getSeparator());
        while ($tok->hasMoreTokens()) {
            $part = (string) $tok->nextToken();
            if ($part === '..') {
                $parentFile = (string) $helpFile->getParent();
                if ($parentFile === null) {
                    $msg = "The file or path you specified ($filename) is invalid relative to ".$file->getPath();
                    // FIXME
                    die($msg);
                }
                $helpFile = new File($parentFile);
            } else if ($part === '.') {
                // Do nothing here
            } else {
                $helpFile = new File($helpFile, $part);
            }
        }
        return new File($helpFile->getAbsolutePath());
    }

    /**
     * normalize the given absolute path.
     *
     * This includes:
     *   - Uppercase the drive letter if there is one.
     *   - Remove redundant slashes after the drive spec.
     *   - resolve all ./, .\, ../ and ..\ sequences.
     *   - DOS style paths that start with a drive letter will have
     *     \ as the separator.
    *
     */
    function normalize($path) {
        $path = (string) $path;
        $orig = (string) $path;

        $path = str_replace('/', DIRECTORY_SEPARATOR, str_replace('\\', DIRECTORY_SEPARATOR, $path));

        // make sure we are dealing with an absolute path
        if (!strStartsWith(DIRECTORY_SEPARATOR, $path)
                && !(strlen($path) >= 2 && Character::isLetter($path{0}) && $path{1} === ':')) {
            // FIXME
            // return error
            die("$path is not an absolute path");
            return false;
        }

        $dosWithDrive = false;
        $root = null;

        // Eliminate consecutive slashes after the drive spec

        if (strlen($path) >= 2 && Character::isLetter($path{0}) && $path{1} === ':') {
            $dosWithDrive = true;

            $ca = str_replace('/', '\\', $path);
            $ca = strToCharArray($ca);

            $sb = "";
            $sb .= strtoupper($ca[0]).':';

            for ($i = 2; $i < count($ca); ++$i) {
                if (($ca[$i] !== '\\') ||
                        ($ca[$i] === '\\' && $ca[$i - 1] !== '\\')
                   ) {
                    $sb .= $ca[$i];
                }
            }

            $path = (string) $sb;
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);

            if (strlen($path) == 2) {
                $root = $path;
                $path = "";
            } else {
                $root = substr($path, 0, 3);
                $path = substr($path, 3);
            }

        }
        else {
            if (strlen($path) == 1) {
                $root = DIRECTORY_SEPARATOR;
                $path = "";
            } else if ($path{1} == DIRECTORY_SEPARATOR) {
                // UNC drive
                $root = DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR;
                $path = substr($path, 2);
            }
            else {
                $root = DIRECTORY_SEPARATOR;
                $path = substr($path, 1);
            }
        }

        $s = array();
        array_push($s, $root);
        $tok = new StringTokenizer($path, DIRECTORY_SEPARATOR);
        while ($tok->hasMoreTokens()) {
            $thisToken = $tok->nextToken();
            if ("." === $thisToken) {
                continue;
            } else if (".." === $thisToken) {
                if (count($s) < 2) {

                    //FIXME
                    // return error
                    die("Cannot resolve path: $orig");
                    return false;

                } else {
                    array_pop($s);
                }
            } else { // plain component
                array_push($s, $thisToken);
            }
        }

        $sb = "";
        for ($i=0; $i<count($s); ++$i) {
            if ($i > 1) {
                // not before the filesystem root and not after it, since root
                // already contains one
                $sb .= DIRECTORY_SEPARATOR;
            }
            $sb .= (string) $s[$i];
        }


        $path = (string) $sb;
        if ($dosWithDrive === true) {
            $path = str_replace('/', '\\', $path);
        }
        return $path;
    }
}
?>
