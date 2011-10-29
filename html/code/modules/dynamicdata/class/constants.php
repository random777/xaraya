<?php
/**
 * Some constants that are hard-coded in DD functions and/or classes at the moment
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/

// TODO: 1. replace any hard-coded values with those constants
//       2. see if we can get rid of (some of) those in particular functions/classes
//          or move them to class constants ... without needing to import everything :-)

if (!defined('XARDD_MODULEID')) {
    define('XARDD_MODULEID', 182);

    define('XARDD_ITEMTYPE_OBJECTS', 0);
    define('XARDD_ITEMTYPE_PROPERTIES', 1);

    define('XARDD_OBJECTID_OBJECTS', 1);
    define('XARDD_OBJECTID_PROPERTIES', 2);

    define('XARDD_PROPTYPEID_MODULE', 19);
    define('XARDD_PROPTYPEID_ITEMTYPE', 20);
    define('XARDD_PROPTYPEID_ITEMID', 21);
}

?>
