<?php
/**
 * Device Support class
 *
 * This class models a device that makes server requests
 * Lets try to keep it lean
 *
 * @package core
 * @subpackage device
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
class xarDevice extends Object implements ixarDevice
{
    public static $device_id = 'generic_device';

    /**
     * Initialize
     */
    static function init(Array $args=array())
    {        
    }

    /**
     * Get the device making a request
     */
    public static function getRequestingDevice($ua=null) 
    {
        self::$device_id = $ua = self::getDevice();
        return $ua; 
    }
    
    public static function getDevice() 
    {
        return  self::$device_id; 
    }
    
    public static function setTheme($theme='') 
    {
        $set = false;
        if (empty($theme)) return $set;
        $theme = xarVarPrepForOS($theme);
        if (xarThemeIsAvailable($theme)){
            $set = true;
            xarTpl::setThemeName($theme);
            xarVarSetCached('Themes.name','CurrentTheme', $theme);
        }
        return $set;
    }
    public static function configTheme($theme='') 
    {
        // Default Page Title
        // CHECKME: Does this need to be here?
        $SiteSlogan = xarModVars::get('themes', 'SiteSlogan');
        xarTpl::setPageTitle(xarVarPrepForDisplay($SiteSlogan));

        $request = xarController::getRequest();
        if (empty($theme) && xarUserIsLoggedIn() && $request->getType() == 'admin') {
            // Admin theme 
            $theme = xarModVars::get('themes', 'admin_theme');
            self::setTheme($theme);
        } elseif (empty($theme) && (bool) xarModVars::get('themes', 'enable_user_menu') == true) {
            // User Override (configured in themes admin modifyconfig)
            // Users are allowed to set theme in profile, get user setting...
            $theme = xarModUserVars::get('themes', 'default_theme');
            // get the list of permitted themes
            $user_themes = xarModVars::get('themes', 'user_themes');
            $user_themes = !empty($user_themes) ? explode(',',$user_themes) : array();
    
            // Set the theme if it is valid
            if (!empty($user_themes) && in_array($theme, $user_themes)) {
                self::setTheme($theme);
            }
        } else {
            self::setTheme($theme);
        }
    }

    public static function configPageTemplate($template='') 
    {
        $set = false;
        if (xarTpl::getPageTemplateName() != 'default') return $set;
        
        $set = true;
        $request = xarController::getRequest();
        if (!xarUserIsLoggedIn() && $request->getType() == 'user') {
            // For the anonymous user, see if a module specific page exists
            if (!xarTplSetPageTemplateName('user-'.$request->getModule())) {
                xarTplSetPageTemplateName($request->getModule());
            }
            return $set;
        }

        if (xarUserIsLoggedIn()) {
            if (xarUserIsLoggedIn() && $request->getType() == 'user') {
                // Same thing for user side where user is logged in
                if (!xarTpl::setPageTemplateName('user-'.$request->getModule())) {
                    xarTpl::setPageTemplateName('user');
                }
            } elseif (xarUserIsLoggedIn() && $request->getType() == 'admin') {
                 // Use the admin-$modName.xt page if available when $modType is admin
                // falling back on admin.xt if the former isn't available
                if (!xarTpl::setPageTemplateName('admin-'.$request->getModule())) {
                    xarTpl::setPageTemplateName('admin');
                }
            }
        }
    }
}

interface ixarDevice
{
    public static function getRequestingDevice($ua=null);
    public static function getDevice();
}
?>