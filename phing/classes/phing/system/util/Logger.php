<?php
// {{{ Header
/*
 * -File        $Id: Logger.php,v 1.8 2003/04/09 15:58:11 thyrell Exp $
 * -License     LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright   2002, Manuel Holtgrewe
 * -Author      manuel holtgrewe, grin@gmx.net
 */
// }}}
// {{{ constants

define("PH_LOG_FILE",   1); // 00000001b, automatically set in Logger::Log()
define("PH_LOG_DEBUG",  2); // 00000010b
define("PH_LOG_EVENT",  4); // 00000100b
define("PH_LOG_WARN",   8); // 00001000b
define("PH_LOG_ERROR", 16); // 00010000b
define("PH_LOG_ALL",   31); // 00011111b

// }}}
// {{{ class Logger

/**
 * The Logger class is responsible for handling Log events.
 *
 * Logger gives you the opportunity to send selective Log events: Depending on
 * which log level is set, it sends or does not send the log message to stdout.
 * However, the message is always written to the Logfile (if it is opened)
 *
 * The selectivity is done by using bitshifting and bit manipulation. For more
 * details, please see the "Phing User's Guide".
 *
 * @author  Manuel Holtgrewe, grin@gmx.net
 * @see	    Logger::setLogLevel, Logger::getLogLevel
 * @package  phing.system.util
 */

class Logger {
    // {{{ properties

    /**
     * @var     int     Bitmask containing the current Loglevel.
     * @access  private
     */
    var $_Loglevel = null;

    /**
     * @var     int     Bitmask containing the old loglevel
     * @access  private
     */
    var $_oldLoglevel = null;

    /**
     * @var     string  Filename of the logfile
     * @access  private
     */
    var $_LogFileName = "";

    // }}}
    // {{{ function Logger ($_loglevel, $_logfile_name, $_mode)

    /**
     * Constructor. Sets the PHP_LOG_* constants.
     *
     * @author Manuel Holtgrewe, grin@gmx.net
     * @access public
     */

    function Logger ($_loglevel=PH_LOG_ALL, $_logfile_name="", $_mode="a") {
        $this->setLogLevel($_loglevel);

        if ($_logfile_name != "") {
            $this->OpenLogFile($_logfile_name, $_mode);
        }
    }

    // }}}
    // {{{ function SetLoglevel ($_loglevel)

    /**
     * Sets the current loglevel.
     *
     * @param   int     The loglevel
     *
     * @author  Manuel  Holtgrewe, grin@gmx.net
     * @access  public
     * @see	Logger::GetLoglevel
     */

    function SetLoglevel ($_Loglevel=PH_LOG_ALL) {
        if (is_int($_Loglevel) and $_Loglevel <= PH_LOG_ALL) {
            $this->_oldLoglevel = $this->_Loglevel;
            $this->_Loglevel = $_Loglevel;
        } else {
            // error handling ?
            echo "[Logger::SetLogLevel(\$_loglevel)] : \"$_loglevel\" is no valid value\n";
        }
    }

    // }}}
    // {{{ function GetLoglevel()

    /**
     * Get the current loglevel.
     *
     * @return	int	The bitmask specifying the current Loglevel
     *
     * @author  Manuel  Holtgrewe, grin@gmx.net
     * @access  public
     * @see	Logger::SetLoglevel
     */

    function GetLoglevel() {
        return $this->_Loglevel;
    }

    // }}}
    // {{{ function RestoreLoglevel()

    /**
     * If loglevel was switched by component, this method restores the
    * old loglevel
     *
     * @author  Andreas Aderhold, andi@binarycloud.com
     * @access  public
     * @see	Logger::SetLoglevel
     */

    function RestoreLoglevel() {

        if (!is_null($this->_oldLoglevel)) {
            $this->SetLoglevel($this->_oldLoglevel);
            return($this->_Loglevel);
        } else {
            return(false);
        }
    }

    // }}}
    // {{{ function Log($_target_loglevel, $_message)

    /**
     * Send a log message to a specified loglevel.
     *
     * @param   integer Bitmask specifying the target loglevel
     * @param   string  Message to be Logged.
     *
     * @author  Manuel  Holtgrewe, grin@gmx.net
     * @access  public
     */

    function Log ($_target_loglevel, $_message, $file = null, $line = null, $format = null) {
        $_target_loglevel |= PH_LOG_FILE;
        $fmtTime = sprintf("[%s] (%s,%s)",date("D M j G:i:s T Y"),
                           $this->_getLogLevelLabel($_target_loglevel),
                           $_target_loglevel);
        /*
                $message = strftime("%D %T", time());
                $message .= "\t[" . $this->_getLogLevelLabel($_target_loglevel) . " (" . $_target_loglevel . ")" ."]\t";
        */
        if (!is_null($file) && !is_null($line)) {
            $source = str_replace(strrchr(basename($file),'.'),"",basename($file));
            $message = sprintf("%s %s (%s) %s", $fmtTime, $source, $line, trim($_message));
        } else {
            $message = sprintf("%s %s", $fmtTime, trim($_message));
        }


        //        $message .= trim($_message);


        // write message to logfile
        $this->_WriteToLogfile($message);
        // send message to stdout ?
        if ($this->GetLogLevel() & $_target_loglevel
                & (PH_LOG_ALL & ~PH_LOG_FILE)) { // Mask out PH_LOG_FILE.

            print rtrim($_message) . "\n";

        } /*else {
                            // this is just for debug ;-)
                			print "you don't see: \"$message\"\n";
                        }*/
    }

    // }}}
    // {{{ function getLogLevelLabel ($_loglevel)

    /**
     * Get a label showing the loglevels, the message was sent to.
     *
     * @author  Manuel  Holtgrewe, grin@gmx.net
     * @access  private
     * @see	Logger:Logger()
     */

    function _getLogLevelLabel ($_loglevel) {
        $level_names = array("FILE", "DEBUG", "EVENT", "WARNING", "ERROR");

        for ($i = 0; $i < 5; $i++) {
            if (((1 << $i) & $_loglevel) == (1 << $i)) {
                $levels[] = $level_names[$i];
            }
        }

        return implode(",", $levels);

    }

    // }}}
    // {{{ function OpenLogFile($_logfile_name, $_mode);

    /**
     * Opens the logfile
     *
     * @param   string      Filename of the logfile.
     * @param   char        The Mode the file is to be opened with. "a" for append
     *                      "w" for overwriting.
     *
     * @author  Manuel  Holtgrewe, grin@gmx.net
     * @access  public
     */

    function OpenLogFile($_logfile_name, $_mode="a") {
        /* close already open file */
        if ($this->_isLogFileOpen()) {
            $this->CloseLogFile();
        }

        $this->_LogFileName = $_logfile_name;

        if ($this->_LogFileName == "")
            return;

        /* If the file is to be overwritten, do it now: The logfile is opened and
         * and closed each time a message is added in fact. We have to "emulate"
         * this mode in a way.
         * If we are to overwrite the file, we simply open it with fopen and "w"
         * now. */
        if ($_mode == "w") {
            $h = fopen($this->_LogFileName, "w");
            fclose($h);
        } else {
            // okay, we will append, but add separator to old log, first
            $h = fopen($this->_LogFileName, "a");
            fwrite($h, "\n\n----------------------------------------------------------------\n\n");
            fclose($h);
        }

        /* Write Log beginning */
        $this->Log(PH_LOG_FILE, "Opening logfile");
    }
    // }}}
    // {{{ function CloseLogFile ()
    /**
     * Close the logfile
     *
     * @author  Manuel  Holtgrewe, grin@gmx.net
     * @access  public
     */
    function CloseLogFile() {
        $this->Log(PH_LOG_FILE, "Closing logfile");

        $this->_LogFileName = "";
    }
    // }}}
    // {{{ function _isLogFileOpen ()
    /**
     * Checks the current logfile for being opened.
     *
     * @return	bool
     *
     * @author  Manuel  Holtgrewe, grin@gmx.net
     * @acess	public
     */
    function _isLogFileOpen() {
        return $this->_LogFileName != "";
    }
    // }}}
    // {{{ function _WriteToLogFile($_message)
    function _WriteToLogFile($_message="[empty message]") {
        if ($this->_isLogFileOpen()) {
            $handle = fopen($this->_LogFileName, "a");
            fwrite($handle, $_message . "\n");
            fclose($handle);
        }
    }
    // }}}
}
// }}}

?>
