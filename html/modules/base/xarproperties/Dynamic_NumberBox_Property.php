<?php
/**
 * Number Box Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
*/

include_once "modules/base/xarproperties/Dynamic_FloatBox_Property.php";

/**
 * handle a numberbox property
 *
 * @package dynamicdata
 */
class Dynamic_NumberBox_Property extends Dynamic_FloatBox_Property
{
    var $size = 10;
    var $maxlength = 30;
    var $datatype = 'number';

    // Treat the input as an integer - zero decimal places.
    var $precision = 0;

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
    function getBasePropertyInfo()
    {
        $args = array();
        $baseInfo = array(
            'id'         => 15,
            'name'       => 'integerbox',
            'label'      => 'Number Box',
            'format'     => '15',
            'validation' => '',
            'source'     => '',
            'dependancies' => '',
            'requiresmodule' => '',
            'aliases'        => '',
            'args'           => serialize($args)
        );
        return $baseInfo;
    }
}

?>