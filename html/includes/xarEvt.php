<?php
/**
 * Event Messagging System
 *
 * This subsystem issues events into the module space when something happens
 * An event is composed of a system short identifier + an event name
 * Example: a module is loading:  ModLoad 
 * Short identifier: Mod
 * Event name: Load
 * Each subsystem in Xaraya may register events and trigger them, the event subsystem
 * itself is initialized directly after the DB subsystem. Any systems loaded before
 * that need to check whether the proper things are loaded themselves.
 *
 * @package events
 * @copyright (C) 2002-2004 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Frank Besler <besfred@xaraya.com>
 * @todo Document EMS
 * @todo Document functions
 */

/**
 * List of supported events
 * 
 * Multilanguage package:
 * ----------------------
 * MLSMissingTranslationKey    - translationkey is missing
 * MLSMissingTranslationString - translation string is missing
 * MLSMissingTranslationDomain - translation domain is missing
 *
 * Module package: 
 * ---------------
 * ModLoad    - event is issued at the end of the xarModLoad function, just before returning true
 * ModAPILoad - event is issued at the end of the xarModAPILoad function, just before returning true
 *
 * Server package:
 * ---------------
 * ServerRequest - event is issued at the end of processing a server request
 *
 * Session package:
 * ----------------
 * SessionCreate - event is triggered when a new session is being created (see xarSession.php)
 *
 * User package:
 * -------------
 * UserLogin - event is triggered when a user is successfully logged in (value = new userid)
 * UserLogout - event is triggered when a user is successfully logged out (value = old userid)
 */

/**
 * Exceptions raised by this subsystem
 *
 */
class EventRegistrationException extends RegistrationExceptions
{ 
    protected $message = 'The event "#(1)" is not properly registered';
}

/**
 * Intializes Event Messaging System
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @param $args['loadLevel']
 * @return bool true
 */
function xarEvt_init(&$args, $whatElseIsGoingLoaded)
{
    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarEvt__shutdown_handler');
    
    return true;
}

/**
 * Shutdown handler for events subsystem
 *
 * @access private
 */
function xarEvt__shutdown_handler()
{
    //xarLogMessage("xarEvt shutdown handler");
}

/**
 * Trigger an event and call the potential handlers for it in the modules
 *
 * The specified event is issued to the active modules. If a module
 * has defined a specific handler for that event, that function is
 * executed.
 * 
 * @author  Marco Canini
 * @author  Marcel van der Boom <marcel@xaraya.com>
 * @access  protected
 * @param   string $eventName The name of the event
 * @param   mixed  $value Passed as parameter to the even handler function in the module
 * @return  void
 * @todo    Analyze thoroughly for performance issues
*/
function xarEvt_trigger($eventName, $value = NULL)
{
    // Must make sure the event exists.
    if (!xarEvt__checkEvent($eventName)) return; // throw back

    //FIXME: MAKE A global variable to tell *all* of the core not to cache anything
    //during page view! (change xarMod_noCacheState for that one)
    //There are case (instalation, module instalation or others?) where the cache during
    //the pageview can be compromised. As these are rare events, a variable to tell
    //everything not to cache would be good enough

    // Don't trigger events if modules are changing state
    if (!empty($GLOBALS['xarMod_noCacheState'])) return;

    xarLogMessage("Triggered event ($eventName)");

    // get the list of event handlers
    $handlers = xarEvt__GetHandlersList();

    $todo = array();

    // specific event handlers (lower-case in the handlers list)
    $event = strtolower($eventName);
    if (!empty($handlers[$event])) {
        foreach ($handlers[$event] as $modName => $modDir) {
            $todo[$modName] = $modDir;
        }
    }

    // generic event handlers
    $event = 'event';
    if (!empty($handlers[$event])) {
        foreach ($handlers[$event] as $modName => $modDir) {
            $todo[$modName] = $modDir;
        }
    }

    // call the different event handlers
    foreach ($todo as $modName => $modDir) {
        xarEvt__notify($modName, $eventName, $value, $modDir);
    }

}

/**
 * Notify the event handlers that an event has occurred
 *
 * Notifies a module that a certain event has occurred
 * the event handler in the module is called
 *
 * @author  Marco Canini
 * @author  Marcel van der Boom <marcel@xaraya.com>
 * @access  private
 * @param   $modName   string The name of the module
 * @param   $eventName string The name of the event to send
 * @param   $value     mixed  Optional value to pass to the event handler 
 * @param   $modDir    string The directory of the module
 * @return  void
 * @throws  BAD_PARAM
 * @todo    Analyze thoroughly for performance issues.
*/
function xarEvt__notify($modName, $eventName, $value, $modDir = NULL)
{
    if (!xarEvt__checkEvent($eventName)) return; // throw back

    if (empty($modName)) throw new EmptyParameterException('modName');

    if (empty($modDir)) {
        $modDir = $modName;
    }

    // We can't rely on the API, the event system IS the API!
    // - no use of xarModAPIFunc because that sets exceptions and we 
    //   don't want that when a module doesn't react to an event.
    // - we could use xarModAPILoad. This will create another event ModAPILoad 
    //   if the api wasn't loaded yet. The event will *not* be created if the
    //   API was already loaded. However, this would mean that all module APIs
    //   are always loaded, which is a bit too much, so we should try it another way

    // Function naming: module_eventapi_OnEventName
    $funcSpecific = "{$modName}_eventapi_On$eventName";
    $funcGeneral  = "{$modName}_eventapi_OnEvent";

    // set which file to load for looking up the event handler
    $xarapifile="modules/{$modDir}/xareventapi.php";
    $xartabfile="modules/{$modDir}/xartables.php";    

    static $loaded = array();

    //If not loaded, try to
    if (!isset($loaded[$xarapifile])) {
        try {
            $loaded[$xarapifile] = xarInclude($xarapifile, XAR_INCLUDE_ONCE);
        } catch(FileNotFoundException $e) {
            $loaded[$xarapifile] = false;
        }
    }
    
    //Nothing to do if the API file isnt there
    if (isset($loaded[$xarapifile]) && $loaded[$xarapifile] == false) return;

    //$loaded ==true!
    if (function_exists($funcSpecific))  $funcToRun = $funcSpecific;
    if (function_exists($funcGeneral))  $funcToRunGeneral = $funcGeneral;
    
    if (isset($funcToRun) || isset($funcToRunGeneral)) {
        //LAZY LOAD!
        // We may need the tables
        try {
            xarInclude($xartabfile, XAR_INCLUDE_ONCE);
            $xartabfunc = $modName.'_xartables';
            if (function_exists($xartabfunc)) xarDB::importTables($xartabfunc());
        } catch(FileNotFoundException $e) {
            // no worries
        }
    }
    
     if (isset($funcToRun)) {
        $funcToRun($value);
     } elseif (isset($funcToRunGeneral)) {
        $funcToRunGeneral($eventName, $value);
     }

    // Nothing to be done, be silent about it
}

/**
 * Register a supported event
 *
 * The event 'eventName' is registered as a supported event
 *
 * @author  Marco Canini
 * @access  protected
 * @param   $eventName string Which event are we registering?
 * @return  bool  true on success
 * @throws  EMPTY_PARAM
 */
function xarEvt_registerEvent($eventName)
{
    if (empty($eventName)) throw new EmptyParameterException('eventName');
    
    $GLOBALS['xarEvt_knownEvents'][$eventName] = true;
    return true;
}

/**
 * Check whether an event is registered
 *
 * @author  Marco Canini
 * @author  Marcel van der Boom
 * @access  private
 * @param   $eventName Name of the event to check
 * @return  bool 
 * @throws  EVENT_NOT_REGISTERED
*/
function xarEvt__checkEvent($eventName)
{
    if (!isset($GLOBALS['xarEvt_knownEvents'][$eventName])) {
        throw new EventRegistrationException($eventName);
    }
    return true;
}

/**
 * Get the list of known event handlers
 *
 * @author  mikespub
 * @access  private
 * @return array of event handlers
 */
function xarEvt__GetHandlersList()
{
    if (xarCore::isCached('Evt.Handlers', 'list')) {
        return xarCore::getCached('Evt.Handlers', 'list');
    }
    if (function_exists('xarConfigGetVar')) {
        $handlers = xarConfigGetVar('Site.Evt.Handlers');
    } else {
        $dbconn =& xarDBGetConn();
        $sitetabpre = xarDBGetSiteTablePrefix();
        $configtable = $sitetabpre.'_config_vars';
        $query = "SELECT xar_value FROM $configtable WHERE xar_name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array('Site.Evt.Handlers'), ResultSet::FETCHMODE_ASSOC);

        $handlers = array();
        if ($result->next()) {
            $value = $result->get('xar_value');
            if (!empty($value)) {
                $handlers = unserialize($value);
            }
        }
        $result->Close();
    }
    xarCore::setCached('Evt.Handlers', 'list', $handlers);
    return $handlers;
}

?>
