<?php

require_once 'phing/Task.php';

/**
 * Executes PHP function or evaluates expression and sets return value to a property.
 *
 *    WARNING:
 *        This task can, of course, be abused with devastating effects.  E.g. do not
 *        modify internal Phing classes unless you know what you are doing.
 *
 * @author   Hans Lellelid <hans@xmpl.org>
 * @version  $Revision: 1.7 $
 * @package  phing.tasks.system
 *
 * @todo Add support for evaluating expressions
 */
class PhpEvalTask extends Task {
        
    var $expression; // Expression to evaluate
    var $function; // Function to execute
    var $class; // Class containing function to execute
    var $returnProperty; // name of property to set to return value 
    var $params = array(); // parameters for function calls
    
    /** Main entry point. */
    function main() {
        
        if ($this->function === null && $this->expression === null) {
            throw (new BuildException("You must specify a function to execute or PHP expression to evalute.", $this->location));
        }
        
        if ($this->function !== null && $this->expression !== null) {
            throw (new BuildException("You can specify function or expression, but not both.", $this->location));
        }
        
        if ($this->expression !== null && !empty($this->params)) {
            throw (new BuildException("You cannot use nested <param> tags when evaluationg a PHP expression.", $this->location));
        }
        
        $retval = null;
        if ($this->function !== null) {
            $retval = $this->callFunction();                                    
        } elseif ($this->expression !== null) {
            $retval = $this->evalExpression();
        }
        
        if ($this->returnProperty !== null) {
            $this->project->setProperty($this->returnProperty, $retval);
        }
    }
    
    /**
     * Calls function and returns results.
     * @return mixed
     */
      function callFunction() {
                        
        if ($this->class !== null) {
            // import the classname & unqualify it, if necessary
            $this->class = Phing::import($this->class);
                        
            $user_func = array($this->class, $this->function);
            $h_func = $this->class . '::' . $this->function; // human-readable (for log)
        } else {
            $user_func = $this->function;
            $h_func = $user_func; // human-readable (for log)
        }
        
        // put parameters into simple array
        $params = array();
        foreach($this->params as $p) {
            $params[] = $p->getValue();
        }
        
        $this->log("Calling PHP function: " . $h_func . "()");
        foreach($params as $p) {
            $this->log("  param: " . $p, PROJECT_MSG_VERBOSE);
        } 
        
        $return = call_user_func_array($user_func, $params);
        return $return;
    }
    
    /**
     * Evaluates expression and returns resulting value.
     * @return mixed
     */
      function evalExpression() {
        $this->log("Evaluating PHP expression: " . $this->expression);
        if (!StringHelper::endsWith(';', trim($this->expression))) {
            $this->expression .= ';';
        }
        $retval = null;
        eval('$retval = ' . $this->expression);
        return $retval;
    }
    
    /** Set function to execute */
     function setFunction($f) {
       $this->function = $f;
    }

    /** Set [static] class which contains function to execute */
     function setClass($c) {
       $this->class = $c;
    }
    
    /** Sets property name to set with return value of function or expression.*/
     function setReturnProperty($r) {
       $this->returnProperty = $r;
    }
    
    /** Set PHP expression to evaluate. */
     function addText($expression) {
        $this->expression = $expression;
    }

    /** Set PHP expression to evaluate. */
     function setExpression($expression) {
        $this->expression = $expression;
    }
    
    /** Add a nested <param> tag. */
     function createParam() {
        $p = new FunctionParam();
        $this->params[] = $p;
        return $p;
    }        
}

/**
 * Supports the <param> nested tag for PhpTask.
 */
class FunctionParam {

     var $val;
    
     function setValue($v) {
        $this->val = $v;
    }
    
     function addText($v) {
        $this->val = $v;
    }
    
     function getValue() {
        return $this->val;
    }
}
?>