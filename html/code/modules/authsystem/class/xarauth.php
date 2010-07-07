<?php
class xarAuth extends Object
{
    protected static $authobjects = array();
    protected static $authmodules = array();

    public static function getAuthObject($authmodule)
    {
        // return cached instance
        if (isset(self::$authobjects[$authmodule]))
            return self::$authobjects[$authmodule];

        // check for a valid auth module class
        $authinfo = self::isAuthModule($authmodule);
        if (!$authinfo)
            return self::$authobjects[$authmodule] = false;
        $className = $authinfo['class'];
        // cache a new instance
        self::$authobjects[$authmodule] = new $className();

        // return cached instance
        return self::$authobjects[$authmodule];
    }
/**
 * Check if a module is authsystem capable
**/
    public static function isAuthModule($authmodule)
    {
        if (!xarMod::isAvailable($authmodule)) return false;

        if (isset(self::$authmodules[$authmodule]))
            return self::$authmodules[$authmodule];

        $classPath = sys::code() . "modules/{$authmodule}/class/{$authmodule}_auth.php";
        if (!file_exists($classPath))
            return self::$authmodules[$authmodule] = false;

        sys::import("modules.{$authmodule}.class.{$authmodule}_auth");
        $className = ucfirst($authmodule) . '_Auth';
        if (!class_exists($className))
            return self::$authmodules[$authmodule] = false;

        self::$authmodules[$authmodule] = array('name' => $authmodule, 'class' => $className);
        return self::$authmodules[$authmodule];
    }

/**
 * Return the array of stored auth modules
**/
    public static function getAuthModules()
    {
        if (!empty(self::$authmodules))
            return self::$authmodules;
        //$authmods = @unserialize(xarModVars::get('authsystem', 'authmodules'));
        if (empty($authmods) || !is_array($authmods)) {
            $authmods = self::scan();
            //xarModVars::set('authsystem', 'authmodules', serialize($authmods));
        }
        return self::$authmodules = $authmods;
    }
/**
 * Return the array of auth module objects
**/
    public static function getAuthObjects()
    {
        // get list of registered auth modules
        $authmods = self::getAuthModules();

        // validate cache
        if (!empty($authmods) && is_array($authmods)) {
            // check all required modules are in the cache
            foreach ($authmods as $authmod => $authconfig) {
                if (!isset(self::$authobjects[$authmod])) {
                    // auth module isn't cached, refresh cache
                    $refresh = true;
                    break;
                }
            }
            // passed checks, return cached array
            if (empty($refresh))
                return self::$authobjects;
        }
        // At this point we're (re-)building the cache
        self::refresh($authmods);

        return self::$authobjects;
    }
/**
 * refresh the static cache of auth module objects
**/
    public static function refresh(Array $authmodules = array())
    {
        if (empty($authmodules) || !is_array($authmodules)) {
            $authmodules = self::scan();
        }
        $found = array();
        foreach ($authmodules as $mod) {
            $authmodule = $mod['name'];
            $authobject = self::getAuthObject($authmodule);
        }
    }

    public static function scan()
    {
        $authmodules = xarMod::apiFunc('modules','admin','getlist',
            array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        $found = array();
        foreach ($authmodules as $mod) {
            $authmodule = self::isAuthModule($mod['name']);
            if (!$authmodule) continue;
            $found[$mod['name']] = $authmodule;
        }
        // update the list of registered modules
        // xarModVars::set('authsystem', 'authmodules', serialize($found));

        return $found;
    }
}

?>