<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/* Handle the icon tag state
 *
 * @author Jim McDonald, Paul Rosania
*/

function blocks_userapi_handleStateIconTag($args)
{
    return "echo xarMod::apiFunc('blocks', 'user', 'drawStateIcon', array('bid' => \$bid)); ";
}

?>
