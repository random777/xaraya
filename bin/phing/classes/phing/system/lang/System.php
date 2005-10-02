<?php
// {{{ Header
/*
 * -File	   $Id: System.php,v 1.18 2003/04/09 15:58:11 thyrell Exp $
 * -License	LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author	 Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.system.lang.*');
import('phing.system.io.FileSystem');
import('phing.system.io.File');
import('phing.system.io.IOException');

/**
 *  Static System class. Creates and destroys global objects
 *  Sets paths and constants. In essence builds a sane OOP
 *  enabled environment.
 *
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *  @version  $Revision: 1.18 $
 *  @package   phing.system.lang
 */

class System {

    var $isRunning = false;

    function System() {}


    function startup() {
        $s = new System();

        // not sure of this will work stable
        register_shutdown_function(array(&$s, "shutdown"));

        // some init stuff
        $timer =& System::GetTimer();
        $timer->StartTimer();

        System::_setSystemConstants();
        System::_setIncludePaths();
        System::_setIni();

        // forcing service startup
        //System::GetPear();
        //System::GetErr();
    }

    function halt($code=0) {
        exit;
        //System::shutdown($code);
    }

    function shutdown($_exitcode = 0) {
        // FIXME remove this after debugging
        //System::println("[AUTOMATIC SYSTEM SHUTDOWN]");
        $Timer  =& System::GetTimer();
        $Timer->StopTimer();
        // print stack trace
        global $gStackTrace;
        if (!empty($gStackTrace) && $gStackTrace !== null) {
            System::println("\nError -- Unhandled Exceptions!");
            for ($i=count($gStackTrace)-1;$i>=0;--$i) {
                $e =& $gStackTrace[$i];
                if (is_a($e, 'RuntimeException')) {
                    System::println("RuntimeException");
                }
                if (is_a($e, "Exception")) {
                    System::println("  ".$gStackTrace[$i]->toString());
                }
            }
        }
        System::_restoreUserData();
        System::_purgeGarbage();
        System::_stopLogger();
        exit; // final point where everything stops
    }

    function println($message) {
        print($message.System::getProperty("line.separator"));
    }

    function &getLogger() {
        static $sLogger = null;

        if ($sLogger === null) {
            import('phing.system.util.Logger');
            $sLogger = new Logger(PH_LOG_ALL);
            //$sLogger->OpenLogfile("phing.log", "w");
        }

        return $sLogger;
    }

    function &getMessage() {
        static $sMessage = null;

        if ($sMessage === null) {
            import('phing.system.util.Message');
            $sMessage = new Message(PH_LOG_ALL);
            $sMessage->OpenLogfile("phing.log", "w");
        }

        return $sMessage;
    }

    /** wrapper to filesystem */
    function &getFileSystem() {
        return FileSystem::getFileSystem();
    }
    /*
    	function &getErr() {
    		static $sErr = null;
     
    		if ($sErr === null) {
    			import('phing.system.util.Err');
    			$sErr = new Err();
    		}
    		return $sErr;
    	}
    */
    function &getTimer() {
        static $sTimer = null;

        if ($sTimer === null) {
            import('phing.lib.Timer');
            $sTimer = new Timer();
        }

        return $sTimer;
    }
    /*
    	function &getPEAR()
    	{
    		static $sPEAR = null;
     
    		// FIXME
    		// use PEAR_INSTALL_DIR constant to find pear as last try
    		// give precedence to PEAR_HOME env then classpath and then P_I_D
    		// set system property php.pear.home
     
    		if ($sPEAR === null) {
    			@include_once('PEAR.php');
    			$foundPear = class_exists('PEAR');
    			if ($foundPear === true) {
    				$sPEAR = new PEAR();
    			} else {
    				System::println("PEAR not found, be sure to have it in you PHP_CLASSPATH");
    				System::halt(-1);
    			}
    		}
     
    		return $sTimer;
    	}
    */
    /** Returns a copy of a property value */
    function getProperty($propName) {
        // some properties are detemined on each access
        // some are cached, see below
        switch($propName) {
        case 'user.dir':
                return (string) getcwd();
            break;
            // default is to return cached property
        default:
            $theProperty = System::_properties((string) $propName);
            return $theProperty;
            break;
        }
    }

    /** Retuns reference to all properties*/
    function &getProperties() {
        return System::_properties();
    }

    function setProperty($propName, $propValue) {
        return System::_properties((string) $propName, $propValue);
    }

    function currentTimeMillis() {
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }

    function &_properties($propName = null, $propValue = null) {
        static $systemProperties = array();

        // return all
        if ($propValue === null && $propName === null) {
            return $systemProperties;
        }

        // thos means get
        if ($propValue === null) {
            if (isset($systemProperties[$propName])) {
                return $systemProperties[$propName];
            } else {
                return null;
            }
        } else {
            $oldValue = System::getProperty($propName);
            $systemProperties[$propName] = $propValue;
            return $oldValue;
        }
    }


    function _saveUserData() {
        // store user data like current path etc.
        //$this->_Userdata('save');
        return true;
    }

    function _restoreUserData() {
        // Restore env as it was before
        //$this->_Userdata('restore');
        return true;
    }

    function _stopLogger() {
        $Logger =& System::GetLogger();
        $Logger->CloseLogFile();
        return true;
    }

    function _setSystemConstants() {
        // error constants
        //define("PH_ERR_RESOURCE", 0);
        //define("PH_ERR_LOGIC", 1);
        //define("PH_ERR_NOTICE", 2);

        /*
         * PHP_OS returns on
         *   WindowsNT4.0sp6  => WINNT
         *   Windows2000      => WINNT
         *   Windows ME       => WIN32
         *   Windows 98SE     => WIN32
         *   FreeBSD 4.5p7    => FreeBSD
         *   Redhat Linux     => Linux
         */
        System::setProperty('host.os', PHP_OS);
        System::setProperty('php.classpath', PHP_CLASSPATH);

        // try to determine the host filesystem and set system property
        // used by FileSystem::getFileSystem to instantiate the correct
        // abstraction layer

        switch (strtoupper(PHP_OS)) {
        case 'WINNT':
            System::setProperty('host.fstype', 'WINNT');
            break;
        case 'WIN32':
            System::setProperty('host.fstype', 'WIN32');
            break;
        default:
            System::setProperty('host.fstype', 'UNIX');
            break;
        }

        System::setProperty('php.version', PHP_VERSION);
        System::setProperty('user.home', getenv('HOME'));
        System::setProperty('application.startdir', getcwd());
        System::setProperty('line.separator', "\n");

        // try to detect machine dependent information
        $sysInfo = array();
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $sysInfo = posix_uname();
        }

        System::setProperty("host.name", isset($sysInfo['nodename']) ? $sysInfo['nodename'] : "unknown");
        System::setProperty("host.arch", isset($sysInfo['machine']) ? $sysInfo['machine'] : "unknown");
        System::setProperty("host.domain",isset($sysInfo['domain']) ? $sysInfo['domain'] : "unknown");
        System::setProperty("host.os.release", isset($sysInfo['release']) ? $sysInfo['release'] : "unknown");
        System::setProperty("host.os.version", isset($sysInfo['version']) ? $sysInfo['version'] : "unknown");
        unset($sysInfo);
    }


    function _setIncludePaths() {
        $success = false;
        // first we expand all classpaths to include paths, just to be sure
        if (defined('PHP_CLASSPATH')) {
            $success = ini_set('include_path', PHP_CLASSPATH);
        } else {
            // guess
        }

        if ($success === false) {
            System::println("SYSTEM FAILURE: Could not set PHP include path");
            System::halt(-1);
        }
    }

    function _setIni() {
        error_reporting(E_ALL);
        set_time_limit(0);
        ini_set('magic_quotes_gpc', 'off');
        ini_set('short_open_tag', 'off');
        ini_set('default_charset', 'iso-8859-1');
        ini_set('register_globals', 'off');
        ini_set('allow_call_time_pass_reference', 'on');
    }


    function _purgeGarbage() {
        return true;
    }


    function _userdata($_strMode) {
        static $uData = null;
        if ($_strMode === 'save') {

            //$uData['pwd'] = pwd();

            // save in uData;
        } else {
            // restore
        }
    }
}



/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>
