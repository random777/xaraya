<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Base JavaScript management functions
 * Include a module JavaScript link in a page.
 *
 * @author Jason Judge
 * @param string $args['module']    Module name; or
 * @param int $args['moduleid']     Module ID
 * @param string $args['filename']  File name list (comma-separated or array)
 * @param string $args['position']  Position on the page; generally 'head' or 'body'
 * @return bool true=success; null=fail
 */
function base_javascriptapi_modulefile($args)
{

    $result = true;

    // Set some defaults - only attribute 'filename' is mandatory.
    if (empty($args['module'])) {
        // No module name is supplied, default the module from the
        // current template module (not the current executing module).
        $args['module'] = '$_bl_module_name';
    } else {
        // The module name is supplied.
        $args['module'] = addslashes($args['module']);
    }

    extract($args);

    if (empty($position)) {
        $position = 'head';
    } else {
        $position = addslashes($position);
    }

    // Filename can be an array of files to include, or a
    // comma-separated list. This allows a bunch of files
    // to be included from a source module in one go.
    if (!is_array($args['filename'])) {
        $files = explode(',', $args['filename']);
    }

    foreach ($files as $file) {
        $args['filename'] = addslashes($file);
        $filePath = xarModAPIfunc('base', 'javascript', '_findfile', $args);

        // A failure to find a file is recorded, but does not stop subsequent files.
        if (!empty($filePath)) {
            $result = $result & xarTplAddJavaScript($position, 'src', xarServerGetBaseURL() . $filePath, $filePath);
        } else {
            $result = false;
        }
    }

    return $result;
}

?>
