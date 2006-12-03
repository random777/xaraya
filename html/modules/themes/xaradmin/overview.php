<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Overview displays standard Overview page
 *
 * Used to call the template that provides display of the overview
 *
 * @author jojodee
 * @return array xarTplModule with $data containing template data
 *         array containing the menulinks for the overview item on the main manu
 * @since 2 Nov 2005
 */
function themes_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminTheme')) return;

    $data=array();

    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */

    return xarTplModule('themes', 'admin', 'main', $data,'main');
}

?>