<?php
/**
 * Retrieve list of itemtypes of any module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function to retrieve the list of item types of a module (if any)
 *
 * @todo remove this before it can propagate
 * @return array containing the item types and their description
 */
function dynamicdata_userapi_getmoduleitemtypes($args)
{
    return DataObjectMaster::getModuleItemTypes($args);      
}
?>