<?php
/**
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */

/**
 * Activate a module
 *
 * @author Xaraya Development Team
 * Loads module admin API and calls the activate
 * function to actually perform the activation,
 * then redirects to the list function with a
 * status message and returns true.
 *
 * @param id the module id to activate
 * @returns
 * @return
 */
function modules_admin_activate()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVarFetch('id', 'int:1:', $id)) return; 

    // Activate
    $activated = xarMod::apiFunc('modules',
                              'admin',
                              'activate',
                              array('regid' => $id));

    //throw back
    if (!isset($activated)) return;
    $minfo=xarMod::getInfo($id);
    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];

    xarResponse::redirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>
