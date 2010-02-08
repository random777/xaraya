<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 */

/**
 * Display the results of an object list method directly
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
    sys::import('modules.dynamicdata.class.simpleinterface');

    function dynamicdata_object_runmethod($args)
    {
        $interface = new SimpleObjectInterface($args);
        return $interface->handle($args);
    }
?>
