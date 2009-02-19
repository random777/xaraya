<?php
/**
 * Themes administration and initialization
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 * @author Marty Vance
 */
/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name']           = 'Themes Administration';
$modversion['id']             = '70';
$modversion['version']        = '1.8.0';
$modversion['displayname']    = 'Themes';
$modversion['description']    = 'Configure themes, change site appearance';
$modversion['displaydescription'] = 'Configure themes, change site appearance';
$modversion['credits']        = '';
$modversion['help']           = 'xardocs/documentation.txt';
$modversion['changelog']      = 'xardocs/overridescheme.txt';
$modversion['license']        = '';
$modversion['official']       = 1;
$modversion['author']         = 'Marty Vance, Andy Varganov';
$modversion['contact']        = 'andyv@xaraya.com';
$modversion['admin']          = 1;
$modversion['user']           = 0;
$modversion['class']          = 'Core Admin';
$modversion['category']       = 'Global';

if (false) { //bug 6033
xarML('Themes');
xarML('Configure themes, change site appearance');
}

?>
