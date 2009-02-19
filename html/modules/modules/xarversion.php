<?php
/**
 * Module initialization
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name']               = 'Modules Administration';
$modversion['id']                 = '1';
$modversion['version']            = '2.3.0';
$modversion['displayname']        = 'Modules';
$modversion['description']        = 'Configure modules, view install/docs/credits.';
$modversion['displaydescription'] = 'Configure modules, view install/docs/credits.';
$modversion['credits']            = 'xardocs/credits.txt';
$modversion['help']               = '';
$modversion['changelog']          = '';
$modversion['license']            = '';
$modversion['official']           = 1;
$modversion['author']             = 'Jim McDonald';
$modversion['contact']            = '';
$modversion['admin']              = 1;
$modversion['user']               = 0;
$modversion['class']              = 'Core Admin';
$modversion['category']           = 'Global';

if (false) { //bug 6033
xarML('Modules');
xarML('Configure modules, view install/docs/credits.');
}

?>