<?php
/**
 * Initialise the Authsystem module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 * @author Marco Canini
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name']                 = 'authsystem';
$modversion['displayname']          = 'Authsystem';
$modversion['id']                   = '42';
$modversion['version']              = '1.0.0';
$modversion['description']          = 'Xaraya default authentication module';
$modversion['displaydescription']   = 'Xaraya default authentication module';
$modversion['official']             = 1;
$modversion['author']               = 'Marco Canini, Jo Dalle Nogare';
$modversion['contact']              = 'marco.canini@xaraya.com, jojodee@xaraya.com';
$modversion['admin']                = 1;
$modversion['user']                 = 0;
$modversion['class']                = 'Authentication';
$modversion['category']             = 'System';

if (false) { //bug 6033
xarML('Authsystem');
xarML('Xaraya default authentication module');
}

?>
