<?php
/**
 * PreRequest Subject Observer
 *
**/
sys::import('xaraya.structures.events.observer');
class ThemesPreDispatchObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'base';
    
    public function notify(ixarEventSubject $subject)
    {
        // pre request default theme handling
        // Default Page Title
        // CHECKME: Does this need to be here?
        $SiteSlogan = xarModVars::get('themes', 'SiteSlogan');
        xarTpl::setPageTitle(xarVarPrepForDisplay($SiteSlogan));

        $request = xarController::getRequest();

        // Check if a theme variable was passed in the request
        xarVarFetch('theme','str:1:',$theme,'',XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);

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

}
?>