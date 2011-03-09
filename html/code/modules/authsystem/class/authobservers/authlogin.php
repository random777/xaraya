<?php
/**
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * AuthLogin Event Observer
 *
 * This observers methods are called during authsystem login operations
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
sys::import('xaraya.structures.events.observer');
class AuthsystemAuthLoginObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'authsystem';

    public function showform(ixarEventSubject $subject)
    {
        $data = $subject->getArgs();
        return xarTplModule('authsystem', 'authlogin', 'showform', $data);
    }
    
    public function showformblock(ixarEventSubject $subject)
    {
        $data = $subject->getArgs();
        return xarTplModule('authsystem', 'authlogin', 'showformblock', $data);    
    }
    
    public function authenticate(ixarEventSubject $subject)
    {        
        $args = $subject->getArgs();
        extract($args); 
        if (empty($uname) || empty($pass)) return xarAuth::AUTH_FAILED;

        // check last resort first
        // @checkme: move this to privs, and register as its own auth observer ?
        $lastresort = @unserialize(xarModVars::get('privileges', 'lastresort'));
        if (!empty($lastresort) && is_array($lastresort)) {
            if ($lastresort['name'] == md5($uname) &&
                $lastresort['password'] == md5($pass)) {
                return xarAuth::LAST_RESORT;
            }
        }       

        // Get user information
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $rolestable = $xartable['roles'];
        $query = "SELECT id, pass FROM $rolestable WHERE uname = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($uname));
        if (!$result->first()) {
            $result->close();
            return xarAuth::AUTH_FAILED;
        }
        list($id, $realpass) = $result->fields;
        $result->close();

        // Confirm that passwords match
        // @CHECKME: do we need to supply the salt param?
        if (!xarUserComparePasswords($pass, $realpass, $uname, substr($realpass, 0, 2)))
            return xarAuth::AUTH_FAILED;
            
        // return the authenticated user id
        return $id;
    }

}
?>