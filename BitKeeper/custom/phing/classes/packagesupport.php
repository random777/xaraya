<?php
/*
 * $Id: packagesupport.php,v 1.18 2003/04/09 15:58:09 thyrell Exp $
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
 * <http://www.phing.info/>.
 */

/**
 * Adding package support
 *
 * ** DEPRECATED ** DEPRECATED ** DEPRECATED ** DEPRECATED ** DEPRECATED **
 * ** DEPRECATED ** DEPRECATED ** DEPRECATED ** DEPRECATED ** DEPRECATED **
 * ** DEPRECATED ** DEPRECATED ** DEPRECATED ** DEPRECATED ** DEPRECATED **
 *
 * Will be replaced by token filters @import package.path.File;
 *
 * This file will be removed soon !
 *
 * @param   string  A package and file def, i.e. com.thyrell.phpmake.Task
 * @param   array   The parameter array passed to the constructor
 * @return  bool    True/false
 * @author  odysseas, odysseas@binarycloud.com
 * @author  andreas aderhold, andi@binarycloud.com
 * @access  public
 */

function import($_file) {
    $path = strtr($_file, '.', DIRECTORY_SEPARATOR) . ".php";
    $path = str_replace("*.php", "manifest", $path);
    require_once($path);
}

function getResourcePath($path) {
    global $gImportPaths;

    if (!defined("PATH_SEPARATOR")) {
        if (strtoupper(substr(PHP_OS,0,3)) == 'WIN') {
            define('PATH_SEPARATOR', ';');
        } else {
            define('PATH_SEPARATOR', ':');
        }
    }

    $importPaths = array();

    $paths = ini_get("include_path");

    $tok = strtok($paths, PATH_SEPARATOR);
    while ($tok !== FALSE) {
        array_push($importPaths, $tok);
        $tok = strtok(PATH_SEPARATOR);
    }

    $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

    foreach ($importPaths as $prefix) {
        $foo_path = $prefix . DIRECTORY_SEPARATOR . $path;
        if (file_exists($foo_path)) {
            return $foo_path;
        }
    }
    return null;
}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>
