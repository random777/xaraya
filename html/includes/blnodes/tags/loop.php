<?php

/**
* xarTpl__XarLoopNode: <xar:loop> tag class
 *
 * @package blocklayout
 *
 * @todo why do we need both loop:number and loop:index? i think loop:number should refer to the loop number
 * 
 */
class xarTpl__XarLoopNode extends xarTpl__TplTagNode
{
    function loopCounter($operator = NULL)
    {
        static $loopCounter = 0;
        static $loopStack = array();
        if (isset($operator)) {
            if ($operator == '++') {
                $loopCounter++;
            } else {
                // $operator == --
                $loopCounter--;
            }
        }
        return $loopCounter;
    }
    
    function renderBeginTag()
    {
        extract($this->attributes);
        
        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:loop> tag.', $this);
            return;
        }
        
        if (isset($prefix)) {
            $this->raiseError(XAR_BL_DEPRECATED_ATTRIBUTE,'Use of deprecated \'prefix\' attribute in <xar:loop> tag.',$this);
            return;
        }
        
        $name = xarTpl__ExpressionTransformer::transformPHPExpression($name);
        if (!isset($name)) return; // throw back
        
        // Increment the loopCounter and retrieve its new value
        // NOTE: class method!
        $loopCounter = xarTpl__XarLoopNode::loopCounter('++');
        
        $loopName ='$loop_'.$loopCounter;
        $idpart ='';
        if(isset($id)) {
            // If id is set, it must match the name production from xml spec, check that here
            // <mrb> I have a complete class for this locally, which checks agains the xml spec, 
            // lets do it simple for now, just make sure it doesnt start with a number. (bug 4050)
            if(is_numeric(substr($id,0,1))) {
                $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'The id attribute must start with a letter',$this);
                return;
            }
            // Make the id property point to the same loop so loop:id:index etc. works too
            $idpart = $loopName.'->'.$id.'='.$loopName.'; $loop->'.$id.'=& '.$loopName.'->'.$id.';';
        }
        $output = '';
        if($loopCounter > 1) {
            $previousLoop ='$loop_'.($loopCounter-1);
            $output .= $previousLoop.'_save=serialize('.$previousLoop.');';
        }
        $output .= $loopName.'->index=-1; '.$loopName.'->number='.$loopCounter.';
        foreach ('.$name.' as '.$loopName.'->key => '.$loopName.'->item ) {
            unset($loop); '.$loopName.'->index++;
            $loop->index = '.$loopName.'->index;
            $loop->key   = '.$loopName.'->key; 
            $loop->item  =& '.$loopName.'->item; 
            $loop->number= '.$loopName.'->number;
            '. $idpart;
            return $output;
        }
        
        function renderEndTag()
       {
            // Decrement the loopCounter and retrieve its new value
            $previousLoop = xarTpl__XarLoopNode::loopCounter('--');
            $output = '} ';
        if($previousLoop >= 1 ) {
            $output .= '$loop = unserialize($loop_'.$previousLoop.'_save);';
        } 
        return $output;
    }
    
    function hasChildren()
    {
        return true;
    }
    
    function hasText()
    {
        return true;
    }
    
    function isAssignable()
    {
        return false;
    }
}
?>