<?php
/**
 * Get Group information
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Get block group information
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param integer blockGroupId the block group id
 * @return array lock information
 * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 * @deprec 31-JAN-04 - moved to user API
 */
function blocks_adminapi_groupgetinfo($args)
{
    extract($args);

    if ($blockGroupId < 1) throw new BadParameterException('blockGroupId');

    return xarMod::apiFunc(
        'blocks', 'user', 'groupgetinfo',
        array('id' => $blockGroupId)
    );

}

?>
