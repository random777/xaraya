<?php
/**
 * Call an installer function
 * @package Xaraya eXtensible Management System
 * @package Installer
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 * @link http://xaraya.com/index.php/release/200.html
 */
/**
 * Call an installer function.
 *
 * @author John Robeson
 * @author Marcel van der Boom <marcel@hsdev.com>
 * This function is similar to xarModFunc but simplified. 
 * We need this because during install we cant have the module
 * subsystem online directly, so we need a direct way of calling
 * the admin functions of the installer. The actual functions
 * called adhere to normal Xaraya module functions, so we can use
 * the installer later on when xaraya is installed
 *
 * @access public
 * @param funcName specific function to run
 * @param args argument array
 * @returns mixed
 * @return The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarInstallFunc($funcName = 'main', $args = array())
{
    $modName = 'installer';
    $modType = 'admin';

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    if (!function_exists($modFunc)) {
        // try to load it
        xarInstallLoad();
        if(!function_exists($modFunc)) throw new FunctionNotFoundException($modFunc);
    }

    // Load the translations file
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType, $funcName) === NULL) return;

    $tplData = $modFunc($args);
    if (!is_array($tplData)) {
        return $tplData;
    }

    // <mrb> Why is this here?
    $templateName = NULL;
    if (isset($tplData['_bl_template'])) {
        $templateName = $tplData['_bl_template'];
    }

    return xarTplModule($modName, $modType, $funcName, $tplData, $templateName);
}

function xarInstallAPIFunc($funcName = 'main', $args = array())
{
    $modName = 'installer';
    $modType = 'admin';

    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    if (!function_exists($modAPIFunc)) {
        // attempt to load the install api
        xarInstallAPILoad();
        // let's check for the function again to be sure
        if (!function_exists($modAPIFunc)) throw new FunctionNotFoundException($modAPIFunc);
    }

    // Load the translations file
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType.'api', $funcName) === NULL) return;

    return $modAPIFunc($args);
}

/**
 * Loads the modType API for installer identified by modName.
 *
 * @access public
 * @param modName registered name of the module
 * @param modType type of functions to load
 * @returns bool
 * @return true on success
 * @raise BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarInstallAPILoad()
{
    static $loadedAPICache = array();

    $modName    = 'installer';
    $modOsDir   = 'installer';
    $modType  = 'admin';

    if (isset($loadedAPICache[strtolower("$modName$modType")])) {
        // Already loaded from somewhere else
        return true;
    }

    $modOsType = xarVarPrepForOS($modType);

    $osfile = "modules/$modOsDir/xar{$modOsType}api.php";
    if (!file_exists($osfile)) throw new FileNotFoundException($osfile);


    // Load the file
    include $osfile;
    $loadedAPICache[strtolower("$modName$modType")] = true;

    return true;
}

/**
 * Loads the modType of installer identified by modName.
 *
 * @access public
 * @returns string
 * @return true
 * @raise BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarInstallLoad()
{
    static $loadedModuleCache = array();

    $modName = 'installer';
    $modType = 'admin';

    if (empty($modName)) throw new EmptyParameterException('modName');

    if (isset($loadedModuleCache[strtolower("$modName$modType")])) {
        // Already loaded from somewhere else
        return true;
    }
   
    // Load the module files
    $modOsType = xarVarPrepForOS($modType);
    $modOsDir = 'installer';

    $osfile = "modules/$modOsDir/xar$modOsType.php";
    if (!file_exists($osfile)) throw new FileNotFoundException($osfile);

    // Load file
    include $osfile;
    $loadedModuleCache[strtolower("$modName$modType")] = true;

    // Load the module translations files
    $res = xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:', $modType);
    return true;
}

?>
