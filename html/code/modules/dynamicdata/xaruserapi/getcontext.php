<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * get an array of context data for a module using dynamicdata
 *
 * @author the DynamicData module development team
 * @param string $module  name of the module dynamicdata is working for
 * @return array of data
 */
function dynamicdata_userapi_getcontext($args=array('module' =>'dynamicdata'))
{
    extract($args);
    $context = xarSession::getVar('ddcontext.' . $module);
    $context['tplmodule'] = $module;
    return $context;
}

?>
