<?php
// {{{ Header
/* -File        $Id: Err.php,v 1.5 2003/02/01 20:02:09 openface Exp $
 * -License     LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright   2001, The Turing Studio, Inc.
 * -Author      alex black, enigma@turingstudio.com
 * -Author		Albert Lash, alash@plateauinnovation.com
 */
// }}}
// {{{ Err
/**
 * This is the phing error class. It abstracts PEAR_Error
 * and provides some utility functions. Right now it is exactly
 * the same as the binarycloud.
 *
 * @author  alex black, enigma@turingstudio.com
 * @author  Albert Lash, alash@plateauinnovation.com
 *  @package  phing.system.util
 */

class Err {

    // {{{ Method Err()
    /**
     * This is the public Method method,
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function Err() {
        $this->errors=array();
        return true;
    }

    // }}}
    // {{{ Method NewError
    /**
     * This is the public method NewError, it's the static
     * method used to return a new error object and log the
     * error to the stack in $Err->errors.
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function &NewError($params) {

        $Err =& System::GetErr();
        $Log =& System::GetLogger();

        $user_info = array(
                         'type' => $params['type'],
                         'source' => $params['source'],
                         'file' => $params['file'],
                         'line' => $params['line'],
                     );

        $obj = PEAR::raiseError($params['message'],$params['code'],PEAR_ERROR_RETURN,null,$user_info,null,null);
        $ref =& $Err->AddError($obj);

        $Log->Log(PH_LOG_EVENT, $params['message'], $params['file'], $params['line']);
        return $ref;
    }

    // }}}
    // {{{ Method CheckError
    /**
     * This is the public method CheckError, it uses
     * PEAR::isError to check if the value passed is an
     * error object.
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     * @return  bool    true if error, false if not
     */

    function CheckError(&$obj) {
        $result = PEAR::isError($obj);
        return $result;
    }

    // }}}
    // {{{ Method AddError
    /**
     * This is the public method AddError, it adds
     * an error object to the error stack.
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function &AddError($obj) {
        array_push($this->errors,$obj);
        $err_num = count($this->errors)-1;
        $ref =& $this->errors[$err_num];
        return $ref;
    }

    // }}}
    // {{{ Method Destroy
    /**
     * This is the public method Destroy, it removes
     * an error object from the error stack.
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function Destroy(&$obj) {
        $obj = null;
        return true;
    }

    // }}}
    // {{{ Method GetMessage()
    /**
     * This is the public method GetMessage, it returns
     * a the error message of the error object
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function GetMessage(&$obj) {
        return $obj->message;
    }

    // }}}
    // {{{ Method GetCode()
    /**
     * This is the public method GetCode, it returns
     * a the code of the error object
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function GetCode(&$obj) {
        return $obj->code;
    }

    // }}}
    // {{{ Method GetType()
    /**
     * This is the public method GetMessage, it returns
     * a reference to the error message of the error object
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function GetType(&$obj) {
        return $obj->userinfo['type'];
    }

    // }}}
    // {{{ Method GetSource()
    /**
     * This is the public method GetMessage, it returns
     * a reference to the error message of the error object
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function GetSource(&$obj) {
        return $obj->userinfo['source'];
    }

    // }}}
    // {{{ Method GetFile()
    /**
     * This is the public method GetMessage, it returns
     * a reference to the error message of the error object
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function GetFile(&$obj) {
        return $obj->userinfo['file'];
    }

    // }}}
    // {{{ Method GetLine()
    /**
     * This is the public method GetMessage, it returns
     * a reference to the error message of the error object
     *
     * @author  alex black, enigma@turingstudio.com
     * @access  public
     */

    function GetLine(&$obj) {
        return $obj->userinfo['line'];
    }

    // }}}

    // {{{ Vars
    var $errors;
    // }}}
}
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
// }}}
?>
