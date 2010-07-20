<?php
/**
 * User System
 *
 * @package core
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage user
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @todo <marco> user status field
 */

// IS THIS STILL USED?
global $installing;

/**
 * Exceptions defined by this subsystem
 *
 */
class NotLoggedInException extends xarExceptions
{
    protected $message = 'An operation was encountered that requires the user to be logged in. If you are currently logged in please report this as a bug.';
}

/**
 * Authentication modules capabilities
 * (to be revised e.g. to differentiate read & update capability for core & dynamic)
 */
define('XARUSER_AUTH_AUTHENTICATION'           ,   1);
define('XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER',   2);
define('XARUSER_AUTH_PERMISSIONS_OVERRIDER'    ,  16);
define('XARUSER_AUTH_USER_CREATEABLE'          ,  32);
define('XARUSER_AUTH_USER_DELETEABLE'          ,  64);
define('XARUSER_AUTH_USER_ENUMERABLE'          , 128);

/*
 * Error codes
 */
define('XARUSER_AUTH_FAILED', -1);
define('XARUSER_AUTH_DENIED', -2);
define('XARUSER_LAST_RESORT', -3);

/**
 * Initialise the User System
 *
 * @access protected
 * @global xarUser_authentication modules array
 * @param args[authenticationModules] array
 * @return bool true on success
 */
function xarUser_init(Array &$args)
{
    // User System and Security Service Tables
    $prefix = xarDB::getPrefix();

    // CHECKME: is this needed?
    $tables = array(
        'roles'       => $prefix . '_roles',
        'realms'      => $prefix . '_security_realms',
        'rolemembers' => $prefix . '_rolemembers'
    );

    xarDB::importTables($tables);

    $GLOBALS['xarUser_authenticationModules'] = $args['authenticationModules'];

    xarMLS_setCurrentLocale(xarUserGetNavigationLocale());
    xarTplSetThemeName(xarUserGetNavigationThemeName());

    // Register the UserLogin event
    xarEvents::register('UserLogin');
    // Register the UserLogout event
    xarEvents::register('UserLogout');

    return true;
}

/**
 * Log the user in
 *
 * @access public
 * @param  int     $userId the id of the user logging in
 * @param  integer $rememberMe whether or not to remember this login
 * @param  string  $authModName name of the authenticating module
 * @return bool true if the user successfully logged in
 * @throws EmptyParameterException, SQLException
 */
function xarUserLogIn($userName, $password, $rememberMe = 0, $authmod=null)
{
    sys::import('modules.authsystem.class.xarauth');
    return xarAuth::login($userName, $password, $rememberMe, $authmod);
}
/**
 * Log the current logged in user out
 *
 * @access public
 * @return bool true if the user successfully logged out
 */
function xarUserLogOut()
{
    sys::import('modules.authsystem.class.xarauth');
    return xarAuth::logout();
}

/**
 * Check if the user logged in
 *
 * @access public
 * @return bool true if the user is logged in, false if they are not
 */
function xarUserIsLoggedIn()
{
    // FIXME: restore "clean" code once id+session issues are resolved
    //return xarSessionGetVar('role_id') != _XAR_ID_UNREGISTERED;
    return (xarSessionGetVar('role_id') != _XAR_ID_UNREGISTERED
            && xarSessionGetVar('role_id') != 0);
}

/**
 * Gets the user navigation theme name
 *
 * @access public
 * @return string name of the users navigation theme
 */
function xarUserGetNavigationThemeName()
{
    $themeName = xarTplGetThemeName();

    if (xarUserIsLoggedIn() && (bool)xarModVars::get('themes', 'enable_user_menu')){
        $id = xarUserGetVar('id');
        $userThemeName = xarModUserVars::get('themes', 'default', $id);
        if ($userThemeName) $themeName=$userThemeName;
    }

    return $themeName;
}

/**
 * Set the user navigation theme name
 *
 * @access public
 * @param  string $themeName name of the theme to set as navigation theme
 * @return void
 */
function xarUserSetNavigationThemeName($themeName)
{
    assert('$themeName != ""');
    // uservar system takes care of dealing with anynomous
    xarModUserVars::set('themes', 'default', $themeName);
}

/**
 * Get the user navigation locale
 *
 * @access public
 * @return string $locale users navigation locale name
 */
function xarUserGetNavigationLocale()
{
    if (xarUserIsLoggedIn())
    {
        $id = xarUserGetVar('id');
          //last resort user is falling over on this uservar by setting multiple times
         //return true for last resort user - use default locale
         if ($id == XARUSER_LAST_RESORT) return true;

        $locale = xarModUserVars::get('roles', 'locale');
        if (empty($locale)) {
            $locale = xarSessionGetVar('navigationLocale');
        }
    } else {
        $locale = xarSessionGetVar('navigationLocale');
    }
    if (empty($locale)) {
        $locale = xarConfigVars::get(null, 'Site.MLS.DefaultLocale');
    }
    xarSessionSetVar('navigationLocale', $locale);
    return $locale;
}

/**
 * Set the user navigation locale
 *
 * @access public
 * @param  string $locale
 * @return bool true if the navigation locale is set, false if not
 */
function xarUserSetNavigationLocale($locale)
{
    if (xarMLSGetMode() != XARMLS_SINGLE_LANGUAGE_MODE) {
        xarSessionSetVar('navigationLocale', $locale);
        if (xarUserIsLoggedIn()) {
            $userLocale = xarModUserVars::get('roles', 'locale');
            xarModUserVars::set('roles', 'locale', $locale);
        }
        return true;
    }
    return false;
}

/*
 * User variables API functions
 */

/**
 * Initialise the user object
 *
 * @todo get rid of this global here
 */
$GLOBALS['xarUser_objectRef'] = null;

/**
 * Get a user variable
 *
 * @access public
 * @param  string  $name the name of the variable
 * @param  integer $userId integer the user to get the variable for
 * @return mixed the value of the user variable if the variable exists, void if the variable doesn't exist
 * @throws EmptyParameterException, NotLoggedInException, BadParameterException, IDNotFoundException
 * @todo <marco> #1 figure out why this check failsall the time now: if ($userId != xarSessionGetVar('role_id')) {
 * @todo <marco FIXME: ignoring unknown user variables for now...
 * @todo redesign the delegation to auth* modules for handling user variables
 * @todo add some security for getting to user variables (at least from another id)
 * @todo define clearly what the difference or similarity is with dd here
 */
function xarUserGetVar($name, $userId = NULL)
{
    if (empty($name)) throw new EmptyParameterException('name');

    if (empty($userId)) $userId = xarSessionGetVar('role_id');
    //LEGACY
    if ($name == 'id' || $name == 'uid') return $userId;

    if ($userId == _XAR_ID_UNREGISTERED) {
        // Anonymous user => only id, name and uname allowed, for other variable names
        // an exception of type NOT_LOGGED_IN is raised
        // CHECKME: if we're going the route of moditemvars, this doesn need to be the case
        if ($name == 'name' || $name == 'uname') {
            return xarML('Anonymous');
        }
        throw new NotLoggedInException();
    }

    // Don't allow any module to retrieve passwords in this way
    if ($name == 'pass') throw new BadParameterException('name');

    if (!xarCoreCache::isCached('User.Variables.'.$userId, $name)) {

        if ($name == 'name' || $name == 'uname' || $name == 'email') {
            if ($userId == XARUSER_LAST_RESORT) {
                return xarML('No Information'); // better return null here
            }
            // retrieve the item from the roles module
            $userRole = xarMod::apiFunc('roles',  'user',  'get',
                                       array('id' => $userId));

            if (empty($userRole) || $userRole['id'] != $userId) {
                throw new IDNotFoundException($userId,'User identified by id #(1) does not exist.');
            }

            xarCoreCache::setCached('User.Variables.'.$userId, 'uname', $userRole['uname']);
            xarCoreCache::setCached('User.Variables.'.$userId, 'name', $userRole['name']);
            xarCoreCache::setCached('User.Variables.'.$userId, 'email', $userRole['email']);

        } elseif (!xarUser__isVarDefined($name)) {
            if (xarModVars::get('roles',$name) || xarModVars::get('roles','set'.$name)) { //acount for optionals that need to be activated)
                $value = xarModUserVars::get('roles',$name,$userId);
                if ($value == null) {
                    xarCoreCache::setCached('User.Variables.'.$userId, $name, false);
                    // Here we can't raise an exception because they're all optional
                    $optionalvars=array('locale','timezone','usertimezone','userlastlogin',
                                        'userhome','primaryparent','passwordupdate');
                    //if ($name != 'locale' && $name != 'timezone') {
                    if (!in_array($name, $optionalvars)) {
                    // log unknown user variables to inform the site admin
                        $msg = xarML('User variable #(1) was not correctly registered', $name);
                        xarLogMessage($msg, XARLOG_LEVEL_ERROR);
                    }
                    return;
                }
                else {
                    xarCoreCache::setCached('User.Variables.'.$userId, $name, $value);
                }
            }

        } else {
            // retrieve the user item
            $itemid = $GLOBALS['xarUser_objectRef']->getItem(array('itemid' => $userId));
            if (empty($itemid) || $itemid != $userId) {
                throw new IDNotFoundException($userId,'User identified by id #(1) does not exist.');
            }

            // save the properties
            $properties =& $GLOBALS['xarUser_objectRef']->getProperties();
            foreach (array_keys($properties) as $key) {
                if (isset($properties[$key]->value)) {
                    xarCoreCache::setCached('User.Variables.'.$userId, $key, $properties[$key]->value);
                }
            }
        }
    }

    if (!xarCoreCache::isCached('User.Variables.'.$userId, $name)) {
        return false; //failure
    }

    $cachedValue = xarCoreCache::getCached('User.Variables.'.$userId, $name);
    if ($cachedValue === false) {
        // Variable already searched but doesn't exist and has no default
        return;
    }

    return $cachedValue;
}

/**
 * Set a user variable
 *
 * @since 1.23 - 2002/02/01
 * @access public
 * @param  string  $name  the name of the variable
 * @param  mixed   $value the value of the variable
 * @param  integer $userId integer user's ID
 * @return bool true if the set was successful, false if validation fails
 * @throws EmptyParameterException, BadParameterException, NotLoggedInException, xarException, IDNotFoundException
 * @todo redesign the delegation to auth* modules for handling user variables
 * @todo some securitycheck for retrieving at least other users variables ?
 */
function xarUserSetVar($name, $value, $userId = null)
{
    // check that $name is valid
    if (empty($name)) throw new EmptyParameterException('name');
    if ($name == 'id' || $name == 'authenticationModule' || $name == 'pass') {
        throw new BadParameterException('name');
    }

    if (empty($userId)) {
        $userId = xarSessionGetVar('role_id');
    }
    if ($userId == _XAR_ID_UNREGISTERED) {
        // Anonymous user
        throw new NotLoggedInException();
    }

    if ($name == 'name' || $name == 'uname' || $name == 'email') {
        // TODO: replace with some roles API
        // TODO: not -^ but get rid of this entirely here.
        xarUser__setUsersTableUserVar($name, $value, $userId);

    } elseif (!xarUser__isVarDefined($name)) {
        if (xarModVars::get('roles',$name)) {
            xarCoreCache::setCached('User.Variables.'.$userId, $name, false);
            throw new xarException($name,'User variable #(1) was not correctly registered');
        } else {
            xarModUserVars::set('roles',$name,$value,$userId);
        }
    } else {
        // retrieve the user item
        $itemid = $GLOBALS['xarUser_objectRef']->getItem(array('itemid' => $userId));
        if (empty($itemid) || $itemid != $userId) {
            throw new IDNotFoundException($userId,'User identified by id "#(1)" does not exist.');
        }

        // check if we need to update the item
        if ($value != $GLOBALS['xarUser_objectRef']->properties[$name]->value) {
            // validate the new value
            if (!$GLOBALS['xarUser_objectRef']->properties[$name]->validateValue($value)) {
                return false;
            }
            // update the item
            $itemid = $GLOBALS['xarUser_objectRef']->updateItem(array($name => $value));
            if (!isset($itemid)) return; // throw back
        }

    }

    // Keep in sync the UserVariables cache
    xarCoreCache::setCached('User.Variables.'.$userId, $name, $value);

    return true;
}

/**
 * Compare Passwords
 *
 * @access public
 * @param  string $givenPassword  the password given for comparison
 * @param  string $realPassword   the reference password to compare to
 * @param  string $userName       name of the corresponding user?
 * @param  string $cryptSalt      ?
 * @return bool true if the passwords match, false otherwise
 * @todo   weird duckling here
 * @todo   consider something strong than md5 here (not trivial wrt upgrading though)
 */
function xarUserComparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
{
    // TODO: consider moving to something stronger like sha1
    $md5pass = md5($givenPassword);
    if (strcmp($md5pass, $realPassword) == 0)
        // Huh? shouldn't this be true instead of the md5 ?
        return $md5pass;

    return false;
}

// PROTECTED FUNCTIONS

// PRIVATE FUNCTIONS

/**
 * Get user's authentication module
 *
 * @access private
 * @param  userId string
 * @todo   what happens for anonymous users ???
 * @todo   check coherence 1 vs. 0 for Anonymous users !!!
 * @todo   this should be somewhere else probably (base class of auth* or roles mebbe)
 * @todo   is $userId a string? looks like an ID
 */
function xarUser__getAuthModule($userId)
{
    if ($userId == xarSessionGetVar('role_id')) {
        $authModName = xarSessionGetVar('authenticationModule');
        if (isset($authModName)) {
            return $authModName;
        }
    }

    $dbconn   = xarDB::getConn();
    $xartable = xarDB::getTables();

    // Get user auth_module name
    $rolestable = $xartable['roles'];
    $modstable = $xartable['modules'];

    $query = "SELECT mods.name
              FROM $modstable mods, $rolestable roles
              WHERE mods.id = roles.auth_module_id AND
                    roles.id = ?";
    $stmt =& $dbconn->prepareStatement($query);
    $result =& $stmt->executeQuery(array($userId),ResultSet::FETCHMODE_NUM);

    if (!$result->next()) {
        // That user has never logon, strange, don't you think?
        // However fallback to authsystem
        $authModName = 'authsystem';
    } else {
        $authModName = $result->getString(1);
        // TODO: remove when issue of Anonymous users is resolved
        // Q: what issue?
        if (empty($authModName)) {
            $authModName = 'authsystem';
        }
    }
    $result->Close();

    if (!xarMod::apiLoad($authModName, 'user')) return;

    return $authModName;
}

/**
 * See if a Variable has been defined
 *
 * @access private
 * @param  string $name name of the variable to check
 * @return bool true if the variable is defined
 * @todo   rething this.
 */
function xarUser__isVarDefined($name)
{
    // Retrieve the dynamic user object if necessary
    if (!isset($GLOBALS['xarUser_objectRef']) && xarModIsHooked('dynamicdata','roles')) {
        $GLOBALS['xarUser_objectRef'] = xarMod::apiFunc('dynamicdata', 'user', 'getobject',
                                                       array('module' => 'roles'));
        if (empty($GLOBALS['xarUser_objectRef']) || empty($GLOBALS['xarUser_objectRef']->objectid)) {
            $GLOBALS['xarUser_objectRef'] = false;
        }
    }

    // Check if this property is defined for the dynamic user object
    if (empty($GLOBALS['xarUser_objectRef']) || empty($GLOBALS['xarUser_objectRef']->properties[$name])) {
        return false;
    }

    return true;
}

/**
 * @access private
 * @return bool
 * @throws SQLException
 * @todo replace with some roles API ?
**/
function xarUser__setUsersTableUserVar($name, $value, $userId)
{

    $dbconn   = xarDB::getConn();
    $xartable = xarDB::getTables();

    $rolestable = $xartable['roles'];
    $usercolumns = $xartable['users_column'];

    // The $name variable will be used to get the appropriate column
    // from the users table.
    try {
        $dbconn->begin();
        $query = "UPDATE $rolestable SET $usercolumns[$name] = ? WHERE id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($value,$userId));
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}
?>