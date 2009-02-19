<?php
/**
 * Initialization function
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 * @author John Cox <admin@dinerminor.com>
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name']           = 'Mail';
$modversion['id']             = '771';
$modversion['displayname']    = 'Mail';
$modversion['version']        = '0.1.2';
$modversion['description']    = 'Mail handling utility module';
$modversion['displaydescription']    = 'Mail handling utility module';
$modversion['credits']        = '';
$modversion['help']           = 'xardocs/README';
$modversion['changelog']      = 'xardocs/ChangeLog.txt';
$modversion['license']        = 'xardocs/LICENSE';
$modversion['official']       = 1;
$modversion['author']         = 'John Cox via phpMailer';
$modversion['contact']        = 'niceguyeddie@xaraya.com';
$modversion['admin']          = 1;
$modversion['user']           = 0;
$modversion['class']          = 'Core Complete';
$modversion['category']       = 'Global';

if (false) { //bug 6033
xarML('Mail');
xarML('Mail handling utility module');
}

?>