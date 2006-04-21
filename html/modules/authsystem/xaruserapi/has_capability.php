
/**
 * Check for module capability
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * check whether this module has a certain capability
 * @public
 * @param args['capability'] the capability to check for
 * @author Marco Canini
 * @returns bool
 */
function authsystem_userapi_has_capability($args)
{
    extract($args);

    assert('isset($capability)');

    switch($capability) {
        case XARUSER_AUTH_AUTHENTICATION:
            return true;
            break;
        case XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER:
        case XARUSER_AUTH_USER_ENUMERABLE:
        case XARUSER_AUTH_PERMISSIONS_OVERRIDER:
        case XARUSER_AUTH_USER_CREATEABLE:
        case XARUSER_AUTH_USER_DELETEABLE:
            return false;
            break;
        default:
            throw new BadParameterException($capability,'Unknown capability requested "#(1)"');
    }
}

?>
