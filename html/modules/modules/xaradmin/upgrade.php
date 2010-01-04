<?php
/**
 * Upgrade a module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Upgrade a module
 *
 * Loads module admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @author Xaraya Development Team
 * @param id the module id to upgrade
 * @returns
 * @return
 */
function modules_admin_upgrade()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;

    //First check the modules dependencies
    if (!xarModAPIFunc('modules','admin','verifydependency',array('regid'=>$id))) {
        //Oops, we got problems...
        //Handle the exception with a nice GUI:
        xarErrorHandled();

        //Checking if the user has already passed thru the GUI:
        xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);
    } else {
        //No dependencies problems, jump dependency GUI
        $command = true;
    }

    if (!$command) {
        //Let's make a nice GUI to show the user the options
        $data = array();
        $data['id'] = (int) $id;
        //They come in 3 arrays: satisfied, satisfiable and unsatisfiable
        //First 2 have $modInfo under them foreach module,
        //3rd has only 'regid' key with the ID of the module

        // get any dependency info on this module for a better message if something is missing
        $thisinfo = xarModGetInfo($id);
        if (!isset($thisinfo)) {
            xarErrorHandled();
        }
        if (isset($thisinfo['dependencyinfo'])) {
            $data['dependencyinfo'] = $thisinfo['dependencyinfo'];
        } else {
            $data['dependencyinfo'] = array();
        }

        $data['authid']       = xarSecGenAuthKey();
        $data['dependencies'] = xarModAPIFunc('modules','admin','getalldependencies',array('regid'=>$id));
        $data['displayname'] = $thisinfo['displayname'];
        return $data;
    }

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    $success = true;
    $minfo=xarModGetInfo($id);
    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_UPGRADED) {
        // Upgrade module
        $upgraded = xarModAPIFunc(
            'modules', 'admin', 'upgrade',
            array('regid' => $id)
        );

        // Don't throw back - handle it here.
        // Bug 1222: check for exceptions in the exception stack.
        // If there are any, then return NULL to display them (even if
        // the upgrade worked).
        if(!isset($upgraded) || xarCurrentErrorType()) {
            // Flag a failure.
            $success = false;
        }

        // Bug 1669
        // Also check if module upgrade returned false
        if (!$upgraded) {
            $msg = xarML('Module failed to upgrade');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'SYSTEM_ERROR',
                            new SystemException($msg));
            // Flag a failure.
            $success = false;
        }
    }

    if (!$success) {
        // Upgrade failed
        // Send the full error stack to the upgrade template for rendering.
        // (The hope is that all errors can be rendered like this eventually)
        if (xarCurrentErrorType()) {
            // Get the error stack
            $errorstack = xarErrorget();
            // Free up the error stack since we are handling it locally.
            xarErrorFree();
            //Let's make a nice GUI to show the user the options
            $data = array();
            $data['id'] = (int) $id;
            $data['displayname'] = $minfo['name'];
            // Return the stack for rendering.
            $data['errorstack'] = $errorstack;
            return $data;
        }
    }

    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];

    // The module might have new or updated properties, after upgrading, flush the
    // property cache otherwise you will get errors on displaying the property.
    if(!xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true))) {
        return false; //FIXME: Do we want an exception here if flushing fails?
    }
    // The module might have new js plugins, after upgrading, flush the plugin
    // cache otherwise you will get errors on calling the plugin
    if (!xarModAPIFunc('base', 'javascript', 'importplugins')) {
        return false; //FIXME: Do we want an exception here if flushing fails?
    }

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    //    xarResponseRedirect(xarModURL('modules', 'admin', "list#$target"));
    xarResponseRedirect(xarModURL('modules', 'admin', "list", array('state' => 0), NULL, $target));

    return true;
}

?>