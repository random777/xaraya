<?php
/**
 * Dynamic Data Field Status Property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * Dynamic Data Field Status Property
 * @author mikespub <mikespub@xaraya.com>
*/
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class to handle field status
 *
 * @package dynamicdata
 */
class Dynamic_FieldStatus_Property extends Dynamic_Select_Property
{
    function Dynamic_FieldStatus_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => 0, 'name' => xarML('Disabled')),
                                 array('id' => 1, 'name' => xarML('Active')),
                                 array('id' => 2, 'name' => xarML('Display Only')),
                                 array('id' => 3, 'name' => xarML('Hidden')),
                             );
        }
    }

    // default methods from Dynamic_Select_Property

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 25,
                              'name'       => 'fieldstatus',
                              'label'      => 'Field Status',
                              'format'     => '25',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>
