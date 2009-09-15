<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Base JavaScript management functions
 * Find the path for a JavaScript file.
 *
 * @author Jason Judge
 * @param string $args['module']        Module name; or
 * @param int    $args['moduleid']      Module ID (deprecated)
 * @param int    $args['modid']         Module ID
 * @param string $args['filename']      File name
 * @return string the virtual pathname for the JS file; an empty value if not found
 * @todo checkme: the default module should be the current *template* module, not the *request* module?
 */
function base_javascriptapi__findfile($args)
{
    extract($args);

    // File must be supplied and may include a path.
    if (empty($filename) || $filename != strval($filename)) {
        return;
    }

    // Bug 5910: If the path has GET parameters, then move them aside for now.
    if (strpos($filename, '?') > 0) {
        list($filename, $params) = explode('?', $filename, 2);
        $params = '?' . $params;
    } else {
        $params = '';
    }

    // Use the current module if none supplied.
    if (empty($module) && empty($modid)) {
        list($module) = xarRequestGetInfo();
    }

    // Get the module ID from the module name.
    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }

    // Get details for the module if we have a valid module id.
    if (!empty($modid)) {
        $modInfo = xarModGetInfo($modid);

        // Get module directory if we have a valid module.
        if (!empty($modInfo)) {
            $modOsDir = $modInfo['osdirectory'];
        }
    }

    // Theme base directory.
    $themedir = xarTplGetThemeDir();

    // Initialise the search path.
    $searchPath = array();

    // The search path for the JavaScript file.
    if (isset($modOsDir)) {
        $searchPath[] = $themedir . '/modules/' . $modOsDir . '/includes/' . $filename;
        $searchPath[] = $themedir . '/modules/' . $modOsDir . '/xarincludes/' . $filename;
        $searchPath[] = 'modules/' . $modOsDir . '/xartemplates/includes/' . $filename;
    }
    $searchPath[] = $themedir . '/scripts/' . $filename;

    foreach($searchPath as $filePath) {
        if (file_exists($filePath)) {break;}
        $filePath = '';
    }

    if (empty($filePath)) {
        return;
    }

    return $filePath . $params;
}

?>
