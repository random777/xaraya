;
; This file contains handy stuff while developing for xaraya
; provided you are using a (x)emacs like editor. ;-)
;
; It's the intention to gather handy functions for editting
; xaraya files in this package, maybe even make it into a xaraya-mode 
; who knows.
;

;
; Standard file header
;
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

(defun xar-classheader ()
  "Insert a xaraya standard phpdoc classheader"
  (interactive)
  (insert "
/**
 * Short class description
 *
 * A somewhat longer description of the class which
 * may be on multiple lines and can contain examples
 *
 * @package unassigned - replace with packagename
 * @author Author Name <author@email>
 * @deprec date since deprecated <insert if class is deprecated>
 * @todo  todo-item, specify each with new tag
 */"
)
)


;
; Control-Meta-f : fileheader
; Control-Meta-p : funcheader
; Contral-Meta-a : classheader 
(global-set-key [(control meta f)] 'xar-fileheader)
(global-set-key [(control meta p)] 'xar-funcheader)
(global-set-key [(control meta a)] 'xar-classheader)


;
; Hook advised by pear standards
;
; I think this is not necessary with the newer emacsen as they 
; have a php mode force pear option. If not set this function 
; as a hook when editting php files and your fine.
;
(defun php-mode-hook ()
  (setq tab-width 4
        c-basic-offset 4
        c-hanging-comment-ender-p nil
        indent-tabs-mode
        (not
         (and (string-match "/\\(PEAR\\|pear\\)/" (buffer-file-name))
              (string-match "\.php$" (buffer-file-name)))))
  )