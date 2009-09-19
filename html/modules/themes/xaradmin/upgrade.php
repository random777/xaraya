<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Upgrade a theme
 *
 * Loads theme admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @author Marty Vance
 * @param id the theme id to upgrade
 * @returns
 * @return
 */
function themes_admin_upgrade()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if (!xarVarFetch('id', 'int:1:', $id)) return; 
    
    // Upgrade theme
    $upgraded = xarModAPIFunc('themes', 'admin', 'upgrade', array('regid' => $id));
    
    //throw back
    if(!isset($upgraded)) return;

    xarResponse::Redirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>
