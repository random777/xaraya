<?php
/**
 * Themes administration and initialization
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
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
$modversion['version']        = '2.0.0';
$modversion['displayname']    = xarML('Themes');
$modversion['description']    = 'Configure themes, change site appearance';
$modversion['displaydescription'] = xarML('Configure themes, change site appearance');
$modversion['credits']        = 'xardocs/credits.txt';
$modversion['help']           = '';
$modversion['changelog']      = 'xardocs/changelog.txt';
$modversion['license']        = '';
$modversion['official']       = true;
$modversion['author']         = 'Marty Vance, Andy Varganov';
$modversion['contact']        = 'andyv@xaraya.com';
$modversion['admin']          = true;
$modversion['user']           = false;
$modversion['securityschema'] = array('Themes::' => '::');
$modversion['class']          = 'Core Admin';
$modversion['category']       = 'System';
?>
