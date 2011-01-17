<?php
/**
 * Get all items
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * Get all items
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_userapi_getall($args)
{
    return xarMod::apiFunc('dynamicdata','user','getitem',$args);
}

?>