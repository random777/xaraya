<?php
/**
 * Base Module Initialisation
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
*/
/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name']         = 'Base';
$modversion['id']           = '68';
$modversion['displayname']  = 'Base';
$modversion['version']      = '0.1.0';
$modversion['description']  = 'Home Page';
$modversion['displaydescription'] = 'Home Page';
$modversion['credits']      = '';
$modversion['help']         = '';
$modversion['changelog']    = '';
$modversion['license']      = '';
$modversion['official']     = 1;
$modversion['author']       = 'John Robeson, Greg Allan';
$modversion['contact']      = 'johnny@xaraya.com';
$modversion['admin']        = 1;
$modversion['user']         = 1;
$modversion['class']        = 'Core Admin';
$modversion['category']     = 'System';

if (false) { //bug 6033
xarML('Base');
xarML('Home Page');
}

?>
