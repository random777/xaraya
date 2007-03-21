<?php
/**
 * Update/insert a template tag
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
 * Update/insert a template tag
 *
 * @author Marty Vance
 * @param tagname
 * @return bool true on success, error message on failure
 * @author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_admin_removetpltag()
{
    // Get parameters
    if (!xarVarFetch('tagname', 'str:1:', $tagname)) return;

    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '::')) return;

    if(!xarTplUnregisterTag($tagname)) {
        $msg = xarML('Could not unregister (#(1)).', $tagname);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                        new SystemException($msg));
       return;
    }

    xarResponseRedirect(xarModUrl('themes', 'admin', 'listtpltags'));

    return true;
}

?>