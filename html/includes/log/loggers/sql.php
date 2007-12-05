<?php
/**
 * SQL based logger
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage logging
 */
/**
 * Include the base class
 *
 */
include_once ('./includes/log/loggers/xarLogger.php');
// Modified from the original by the Xaraya Team

/**
 * The Log_sql class is a concrete implementation of the Log::
 * abstract class which sends messages to an SQL server.  Each entry
 * occupies a separate row in the database.
 *
 * We can create this in 2 ways: create upon errors when trying to insert the data (creates on first use)
 * Create on activation of the logger module
 *
 *
    CREATE TABLE `xar_log_messages` (
      `id` int(10) NOT NULL auto_increment,
      `ident` varchar(32) NOT NULL,
      `logtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
      `priority` tinyint(4) NOT NULL,
      `message` tinytext NOT NULL,
      PRIMARY KEY  (`id`)
    );
 *
 * @author  Jon Parise <jon@php.net>
 * @version $Revision: 1.21 $
 * @since   Horde 1.3
 * @package logging
 * @todo MichelV: Add a check for the presence of the table. If not presence, exit graciously
 */
class xarLogger_sql extends xarLogger
{
    /**
     * String holding the database table to use.
     * @var string
     */
    var $_table;

    /**
     * Pointer holding the database connection to be used.
     * @var string
     */
    var $_dbconn;

    /**
    * Set up the configuration of the specific Log Observer.
    *
    * @param  array $conf  with
    *               'table  '     => string      The name of the logger table.
    * @access public
    */
    function setConfig($conf)
    {
        parent::setConfig($conf);
       // This generates errors and is not necessary here
       // $this->_dbconn =& xarDBGetConn();

        $this->_table = $conf['table'];
    }

    /**
     * Inserts $message to the current database.
     *
     * @param string $message  The textual message to be logged.
     * @param int $level The priority of the message.
     * @return boolean  True on success or false on failure.
     * @access public
     */
    function notify($message, $level)
    {
        if (!$this->doLogLevel($level)) return false;
        // DB connection
        $dbconn =& xarDBGetConn();
        /* Build the SQL query for this log entry insertion. */
        // Generate id
        $nextId = $dbconn->GenId($this->_table);
        // Query for insertion
        $query = "INSERT INTO $this->_table (id, ident, logtime, priority, message)
                VALUES (?,?,?,?,?)";
        $bindvars = array($nextId, $this->_ident, $this->GetTime(),$level,$message);
        // Execute
        $result = &$dbconn->Execute($query,$bindvars);

        if (!$result) {
            return false;
        }
        // Needed?
        $result->Close();
        return true;
    }
}
?>