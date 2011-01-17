<?php
/**
 * Upgrade a theme
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
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
 * @return bool true
 */
function themes_admin_upgrade()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;

    // Upgrade theme
    $upgraded = xarMod::apiFunc('themes', 'admin', 'upgrade', array('regid' => $id));

    //throw back
    if(!isset($upgraded)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>