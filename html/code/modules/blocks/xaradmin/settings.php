<?php
/**
 * List modules and current settings
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * List modules and current settings
 * @param several params from the associated form in template
 * @author Jim McDonald
 * @author Paul Rosania
 */
function blocks_admin_settings()
{
    // Security
    if(!xarSecurityCheck('EditBlocks')) return;

    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('filter', 'str:1:', $filter, null, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('return_url', 'pre:trim:str:1:', $return_url, '', XARVAR_NOT_REQUIRED)) return;

    xarModUserVars::set('blocks', 'selstyle', $selstyle);

    xarController::redirect(xarModURL('blocks', 'admin', 'view_instances',array('filter' => $filter)));
        $return_url = xarModURL('blocks', 'admin', 'view_instances',array('filter' => $filter));

    xarController::redirect($return_url);
    return true;
}

?>
