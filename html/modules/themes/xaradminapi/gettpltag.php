<?php
/**
 * Get registered template tags
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
 * Get registered template tags
 *
 * @param string tagname
 * @return array of tags in the database
 * @author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_adminapi_gettpltag($args)
{
    extract($args);
    if (!isset($tagname)) return;

    $aData = array(
        'tagname'       => '',
        'module'        => '',
        'handler'       => '',
        'attributes'    => array(),
        'num_atributes' => 0
    );

    if (trim($tagname) != '') {
        $oTag = xarTplGetTagObjectFromName($tagname);
        $aData = array(
            'tagname'       => $oTag->getName(),
            'module'        => $oTag->getModule(),
            'handler'       => $oTag->getHandler(),
            'attributes'    => $oTag->getAttributes(),
            'num_atributes' => sizeOf($oTag->getAttributes())
        );

    }
    $aData['max_attrs'] = 10;

    return $aData;
}

?>