<?php
/**
 * Retrieve list of itemtypes of any module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function to retrieve the list of item types of a module (if any)
 *
 * @returns array
 * @return array containing the item types and their description
 */
	function dynamicdata_userapi_getmoduleitemtypes($args)
	{
		extract($args);
		// Argument checks
		if (empty($moduleid) && empty($module)) {
			$msg = xarML('Wrong arguments to dynamicdata_userapi_getmoduleitemtypes.');
			xarErrorSet(XAR_SYSTEM_EXCEPTION,
						'BAD_PARAM',
						 new SystemException($msg));
			return false;
		}
		if (empty($module)) {
			$info = xarModGetInfo($moduleid);
			$module = $info['name'];
		}

		$native = isset($native) ? $native : true;
		$extensions = isset($extensions) ? $extensions : true;

		$types = array();
		if ($native) {
			if ($nativetypes = xarModAPIFunc($module,'user','getitemtypes',array(),0)) $types = $nativetypes;

		}
		if ($extensions) {
			// Get all the objects at once
		    $xartable =& xarDBGetTables();
			$q = new xarQuery('SELECT',$xartable['dynamic_objects']);
			$q->addfields(array('xar_object_id AS objectid','xar_object_label AS objectlabel','xar_object_moduleid AS moduleid','xar_object_itemtype AS itemtype','xar_object_parent AS parent'));
			$q->eq('xar_object_moduleid',$moduleid);
			if (!$q->run()) return;

			// put in itemtype as key for easier manipulation
			foreach($q->output() as $row)
				$types [$row['itemtype']] = array(
											'label' => $row['objectlabel'],
											'title' => xarML('View #(1)',$row['objectlabel']),
											'url' => xarModURL('dynamicdata','user','view',array('itemtype' => $row['itemtype'])));
		}

		return $types;
	}
?>