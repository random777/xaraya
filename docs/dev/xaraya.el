;
; This file contains handy stuff while developing for xaraya
;
; TODO: in long term make this into a xaraya-mode package
;

;
; Standard file header
;
; TODO: assign insertion of this to key combo

(defun xar-fileheader ()
	"Insert a xaraya standard phpdoc fileheader"
	(interactive)
	(insert "
/**
 * File: $Id$
 *
 * Short description of purpose of file
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage module name
 * @link  link to where more info can be found
 * @author author name <author@email> (this tag means responsible person)
*/
"
					)
	)

;
; Standard function header
;
; TODO: assign insertion of this to a key combo

(defun xar-funcheader ()
	"Insert a xaraya standard phpdoc fileheader"
	(interactive)
	(insert "
/**
 * Short description of the function
 *
 * A somewhat longer description of the function which may be 
 * multiple lines, can contain examples.
 *
 * @author  Author Name <author@email>
 * @deprec  <insert this if function is deprecated>
 * @access  public / private / protected
 * @param   param1 type Description of parameter 1
 * @param   param2 type Description of parameter 2
 * @return  type to return description
 * @throws  list of exception identifiers which can be thrown
 * @todo    list of things which must be done to comply to relevant RFC
*/"
					)
	)

;
; Control-Meta-f : fileheader
; Control-Meta-h : funcheader
;
(global-set-key [(control meta f)] 'xar-fileheader)
(global-set-key [(control meta h)] 'xar-funcheader)

