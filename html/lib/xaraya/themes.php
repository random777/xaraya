<?php
/**
 * Theme handling functions
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @author mrb <marcel@xaraya.com> (this tag means responsible person)
 * @todo Most of this doesnt belong here, but in the themes module, move it away
*/

/**
 * Wrapper functions to support Xaraya 1 API
 *
**/
//sys::import('xaraya.variables.theme');
//function xarThemeGetVar($themeName, $name, $prep = NULL)                           {   return xarThemeVars::get($themeName, $name); }
//function xarThemeSetVar($themeName, $name, $prime = NULL, $value, $description='') {   return xarThemeVars::set($themeName, $name, $value); }
//function xarThemeDelVar($themeName, $name)                                         {   return xarThemeVars::delete($themeName, $name); }

/**
 * get a theme variable
 *
 * @access public
 * @param string themeName The name of the theme
 * @param string name The name of the variable
 * @return mixed The value of the variable or void if variable doesn't exist
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarThemeGetVar($themeName, $name, $prep = NULL)
{
    if (empty($themeName)) {
        $msg = xarML('Empty themeName (#(1)).', '$themeName');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    return xarVar__GetVarByAlias($themeName, $name, $uid = NULL, $prep, $type = 'themevar');
}

/**
 * set a theme variable
 *
 * @access public
 * @param string themeName The name of the theme
 * @param string name The name of the variable
 * @param mixed value The value of the variable
 * @return bool true on success
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarThemeSetVar($themeName, $name, $prime = NULL, $value, $description='')
{
    if (empty($themeName)) {
        $msg = xarML('Empty themeName (#(1)).', '$themeName');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    return xarVar__SetVarByAlias($themeName, $name, $value, $prime, $description, $uid = NULL, $type = 'themevar');
}


/**
 * delete a theme variable
 *
 * @access public
 * @param string themeName The name of the theme
 * @param string name The name of the variable
 * @return bool true on success
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarThemeDelVar($themeName, $name)
{
    if (empty($themeName)) {
        $msg = xarML('Empty themeName (#(1)).', '$themeName');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));return;
    }

    return xarVar__DelVarByAlias($themeName, $name, $uid = NULL, $type = 'themevar');
}

/**
 * Gets theme registry ID given its name
 *
 * @access public
 * @param string themeName The name of the theme
 * @return xarMod::getRegID for processing
 * @throws DATABASE_ERROR, BAD_PARAM, THEME_NOT_EXIST
 */
function xarThemeGetIDFromName($themeName)
{
    if (empty($themeName)) throw new EmptyParameterException('themeName');

    return xarMod::getRegID($themeName, $type = 'theme');
}

/**
 * get information on theme
 *
 * @access public
 * @param int themeRegId theme id
 * @return array array of theme information
 * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarThemeGetInfo($regId)
{
    return xarModGetInfo($regId, $type = 'theme');
}


/**
 * load database definition for a theme
 *
 * @param string themeName name of theme to load database definition for
 * @param string themeDir directory that theme is in (if known)
 * @return xarMod::loadDbInfo for processing.
 */
function xarThemeDBInfoLoad($themeName, $themeDir = NULL)
{
    return xarMod::loadDbInfo($themeName, $themeDir, $type = 'theme');
}


/**
 * Gets the displayable name for the passed themeName.
 * The displayble name is sensible to user language.
 *
 * @access public
 * @param themeName registered name of theme
 * @return string the displayable name
 */
function xarThemeGetDisplayableName($themeName)
{
    // The theme display name is language sensitive,
    // so it's fetched through xarML.
    // TODO: need to think of something that actually works.
    return xarML($themeName);
}

/**
 * checks if a theme is installed and its state is XARTHEME_STATE_ACTIVE
 *
 * @access public
 * @param string themeName registered name of theme
 * @return bool true if the theme is available, false if not
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarThemeIsAvailable($themeName)
{
    return xarMod::isAvailable($themeName, $type = 'theme');
}


// PROTECTED FUNCTIONS

/**
 * Get info from xartheme.php
 *
 * @access protected
 * @param string themeOSdir the theme's directory
 * @return xarMod_getFileInfo for processing
 */
function xarTheme_getFileInfo($themeOsDir)
{
    return xarMod_getFileInfo($themeOsDir, $type = 'theme');
}

/**
 * Load a theme's base information
 *
 * @access protected
 * @param string themeName the theme's name
 * @return to xarMod__getBaseInfo for processing
 */
function xarTheme_getBaseInfo($themeName)
{
    return xarMod_getBaseInfo($themeName, $type = 'theme');
}

/**
 * Get all theme variables for a particular theme
 *
 * @access protected
 * @return array an array of theme variables
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarTheme_getVarsByTheme($themeName)
{
    return xarMod_getVarsByModule($themeName, $type = 'theme');
}

/**
 * Get all theme variables with a particular name
 *
 * @access protected
 * @return bool true on success
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarTheme_getVarsByName($name)
{
    return xarMod_getVarsByName($name, $type = 'theme');
}

/**
 * Get the theme's current state
 *
 * @param int themeRegId the theme's registered id
 * @param string themeMode the theme's site mode
 * @return to xarMod__getState for processing
 */
function xarTheme_getState($themeRegId, $themeMode)
{
    return xarMod_getState($themeRegId, $themeMode, $type = 'theme');
}
?>
