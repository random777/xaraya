<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */

/**
 * Dynamic Data Module Property
 * @author mikespub
 * Include the base class
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Handle the module property
 *
 * @package dynamicdata
 */
class Dynamic_Module_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        //TODO: this should be handled by the getOptions method
        if (count($this->options) == 0) {
            $modlist = xarModAPIFunc('modules',
                             'admin',
                             'getlist',$args);
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
            }
        }
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('modules');
        $info->id   = 19;
        $info->name = 'module';
        $info->desc = 'Module';

        return $info;
    }

}

?>