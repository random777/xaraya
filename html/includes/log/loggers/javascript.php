<?php
/**
 * JavaScriptLogger
 *
 * Implements a javascript logger in a separate HTML window
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage logging
 */
/**
 * Include the base file
 *
 */
include_once ('./includes/log/loggers/xarLogger.php');

/**
 * Javascript logger
 *
 * @package logging
 */
class xarLogger_javascript extends xarLogger
{
    /**
    * Buffer for logging messages
    */
    var $_buffer;

    /**
    * The level of load of the core systems.
    */
    var $_loadLevel;

    /**
    * Write out the buffer if it is possible (the template system is already loaded)
    *
    * @access public
    */
    function writeOut()
    {
        if ($this->_loadLevel & XARCORE_BIT_TEMPLATE) return false;
        xarTplAddJavaScript('head', 'code', $this->_buffer);
        $this->_buffer = '';

        return true;
    }

    /**
     * Sets up the configuration specific parameters for each driver
     *
     * @param array     $conf               Configuration options for the specific driver.
     *
     * @access public
     * @return boolean
     */
    function setConfig(&$conf)
    {
        parent::setConfig($conf);
        $this->_loadLevel = & $conf['loadLevel'];
        $this->_buffer = $this->getCommonCode();
    }

    /**
    * Common Code. This will create the javascript debug window.
    *
    * @access private
    */
    function getCommonCode()
    {
        $header = "<hr size=\\\"1\\\"></hr><span style=\\\"font-face: Verdana,arial; font-size: 10pt;\\\">".
                  date("Y-m-d H:i:s").
                  "</span>";

        $code = "\ndebugWindow = window.open(\"\",".
                "\"Xaraya_Javascript_Logger\",\"width=450,height=500,scrollbars=yes,resizable=yes\");\n".
                "if (debugWindow) {\n".
                "    debugWindow.document.write(\"".$header."\"+'<p><b>'+window.location.href+'</b></p>');\n".
                "}\n";
        return $code;
    }

   /**
    * Updates the Observer
    *
    * @param string $message Log message
    * @param int $level level of priority of the message
    * @return boolean  True on success or false on failure.
    * @access public
    */
    function notify($message, $level)
    {
        $strings = array ("\r\n", "\r", "\n");
        $replace = array ("<br />", "<br />", "<br />");

        // Abort early if the level of priority is above the maximum logging level.
        if (!$this->doLogLevel($level)) return false;

        $this->_buffer .= "if (debugWindow) {\n".
                "    debugWindow.document.write('".$this->getTime().
                ' - ('.$this->levelToString($level).')<br/>'.
                addslashes(str_replace($strings, $replace, $message) ). "<br/><br/>');\n".
                "    debugWindow.scrollBy(0,100000);\n".
                "}\n";

        $this->writeOut();
    }
}

?>