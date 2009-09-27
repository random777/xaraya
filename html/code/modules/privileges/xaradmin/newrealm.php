<?php
/**
 * Create a new realm
 *
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * addRealm - create a new realm
 * Takes no parameters
 */
function privileges_admin_newrealm()
{
    $data = array();

    if (!xarVarFetch('name',      'str:1:20', $name,      '',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('confirmed', 'bool', $confirmed, false, XARVAR_NOT_REQUIRED)) return;

    // Security Check
    if(!xarSecurityCheck('AddPrivilege',0,'Realm')) return;

    if ($confirmed) {
        if (!xarSecConfirmAuthKey()) {
            return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
        }        

        $xartable = xarDB::getTables();
        sys::import('modules.roles.class.xarQuery');
        $q = new xarQuery('SELECT',$xartable['security_realms'],'name');
        $q->eq('name', $name);
        if(!$q->run()) return;

        if ($q->getrows() > 0) {
            throw new DuplicateException(array('realm',$name));
        }

        $q = new xarQuery('INSERT',$xartable['security_realms']);
        $q->addfield('name', $name);
        if(!$q->run()) return;

        //Redirect to view page
        xarResponse::Redirect(xarModURL('privileges', 'admin', 'viewrealms'));
    }

    $data['authid'] = xarSecGenAuthKey();
    return $data;
}


?>
