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
 * Import JS Framework Plugins
 * @author Chris Powis
 * @param string $args['framework']     Name of the framework (optional - default all)
 * @return array
 */
function base_javascriptapi_importplugins($args)
{

    extract($args);

    $fwinfo = xarModAPIFunc('base', 'javascript', 'getframeworkinfo');
    if (empty($fwinfo)) return;

    if (isset($framework) && !isset($fwinfo[$framework])) return;

    $scanmods = xarModAPIFunc('modules', 'admin', 'getlist', array('state' => XARMOD_STATE_ACTIVE));

    $themedir = xarTplGetThemeDir();
    // scan frameworks
    foreach ($fwinfo as $fwname => $fwvals) {
        // not the framework we wanted? skip it...
        if (isset($framework) && $framework != $fwname) continue;
        // create an array for found plugins
        $newplugins = array();
        // store old plugins
        $oldplugins = isset($fwvals['plugins']) ? $fwvals['plugins'] : array();
        // scan modules for framework plugins
        foreach ($scanmods as $modInfo) {
            // relative path to plugins
            $relPath = $fwname . '/plugins';
            // set path to theme templates
            $themePath = $themedir . '/modules/' . $modInfo['osdirectory'];
            // set path to module templates
            $modPath = 'modules/' . $modInfo['osdirectory'] . '/xartemplates/includes/' . $relPath;
            $scandirs = array();
            $scandirs[] = $themePath . '/includes/' . $relPath;
            $scandirs[] = $themePath . '/xarincludes/' . $relPath;
            $scandirs[] = $modPath;
            // scan folders for plugin files
            foreach ($scandirs as $scandir) {
                $modFiles = xarModAPIFunc('base', 'user', 'browse_files',
                    array(
                        'basedir'   => $scandir,
                        'levels'    => 2,
                        'match_re'  => '/\.js$/',
                        'retpath'   => 'rel',
                    ));
                if (!empty($modFiles)) {
                    foreach ($modFiles as $modFile) {
                        list($plName, $plFile) = explode('/', $modFile, 2);
                        $verPath = $modPath . '/' . $plName;
                        // new plugin
                        if (!isset($newplugins[$plName])) {
                            $fileName = $verPath . '/xarversion.php';
                            if (file_exists($fileName)) {
                                include_once($fileName);
                            }
                            // no xarversion file
                            if (empty($plugininfo)) $plugininfo = array();
                            unset($fileName);
                        } else {
                            $plugininfo = $newplugins[$plName];
                        }
                        if (!isset($plugininfo['version'])) $plugininfo['version'] = xarML('Unknown');
                        if (!isset($plugininfo['displayname'])) $plugininfo['displayname'] = ucfirst($plName);
                        if (!isset($plugininfo['id'])) $plugininfo['id'] = $plName;
                        if (!isset($plugininfo['name'])) $plugininfo['name'] = $plugininfo['displayname'];
                        if (!isset($plugininfo['defaultmod'])) $plugininfo['defaultmod'] = $modInfo['name'];
                        if (!isset($plugininfo['defaultfile'])) $plugininfo['defaultfile'] = $plFile;
                        if (!isset($plugininfo['modules'])) $plugininfo['modules'] = array();
                        // new module
                        if (!isset($plugininfo['modules'][$modInfo['name']])) {
                            $plugininfo['modules'][$modInfo['name']] = array(
                                'id' => $modInfo['name'],
                                'name' => $modInfo['displayname'],
                                'files' => array(),
                                'version' => $plugininfo['version'],
                                'defaultfile' => $plugininfo['defaultfile'],
                            );
                        }
                        // new file
                        if (!isset($plugininfo['modules'][$modInfo['name']]['files'][$plFile])) {
                            $plugininfo['modules'][$modInfo['name']]['files'][$plFile] = array(
                                'id' => $plFile,
                                'name' => $plFile,
                            );
                        }
                        if (isset($oldplugins[$plName])) {
                            if (isset($oldplugins[$plName]['defaultmod']) && $modInfo['name'] == $plugininfo['defaultmod']) {
                                $plugininfo['defaultmod'] = $oldplugins[$plName]['defaultmod'];
                            }
                            if (isset($oldplugins[$plName]['modules'][$modInfo['name']]['defaultfile']) &&
                                $plFile == $oldplugins[$plName]['modules'][$modInfo['name']]['defaultfile']) {
                                $plugininfo['modules'][$modInfo['name']]['defaultfile'] = $plFile;
                            }
                        }
                        $newplugins[$plName] = $plugininfo;

                        unset($plugininfo);
                    }
                }
            }
        }
        $fwinfo[$fwname]['plugins'] = $newplugins;
    }
    xarModSetVar('base','RegisteredFrameworks', serialize($fwinfo));

    return $fwinfo;

}
 ?>