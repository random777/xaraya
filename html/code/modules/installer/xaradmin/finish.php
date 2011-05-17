<?php
/**
 * Installer
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

function installer_admin_finish()
{
    xarVarFetch('returnurl', 'str', $returnurl, 'site', XARVAR_NOT_REQUIRED);

# --------------------------------------------------------
# Create wrapper DD overlay objects for the privileges modules
#
    $objects = array(
                   'privileges_baseprivileges',
                   'privileges_privileges',
                     );

    if(!xarMod::apiFunc('modules','admin','standardinstall',array('module' => 'privileges', 'objects' => $objects))) return;

    // Default for the site time zone is the system time zone
    xarConfigVars::set(null, 'Site.Core.TimeZone', xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));

    // Defaults for templating engine options 
    xarConfigVars::set(null, 'Site.BL.CompressWhitespace', 1);
    xarConfigVars::set(null, 'Site.BL.MemCacheTemplates', false);

    switch ($returnurl) {
        case ('base'):
            xarController::redirect(xarModURL('base','admin','modifyconfig'));
        case ('modules'):
            xarController::redirect(xarModURL('modules','admin','list'));
        case ('blocks'):
            xarController::redirect(xarModURL('blocks','admin','view_instances'));
        case ('site'):
        default:
            xarController::redirect('index.php');
    }
    return true;
}

?>