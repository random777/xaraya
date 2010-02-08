<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges
 * @link http://xaraya.com/index.php/release/1098.html
 */
/**
 * Return table name definitions to Xaraya
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * This function is called internally by the core whenever the module is
 * loaded. It is called by xarMod__dbInfoLoad()
 *
 * @return array
 */
function privileges_xartables()
{
    $prefix = xarDB::getPrefix();
    $tables['privileges']     = $prefix . '_privileges';
    $tables['privmembers']    = $prefix . '_privmembers';
    $tables['security_acl']   = $prefix . '_security_acl';
    $tables['instances']      = $prefix . '_instances';
    $tables['security_realms']    = $prefix . '_security_realms';
    $tables['security_instances'] = $prefix . '_security_instances';
    return $tables;
}
?>
