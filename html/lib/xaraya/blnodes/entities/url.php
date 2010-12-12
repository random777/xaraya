<?php

/**
 * xarTpl_XarUrlEntityNode
 *
 * More generic than ModUrlEntityNode, supports args, fragments and generateXMLURL
 * The 'hasExtras' workaround for bug 3603 is deprecated for this entity &xar-url;
 *
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @package blocklayout
 * @access private
 * @todo model this class and the xarTpl__XarModUrlEntityNode as parent/derived pair.
 */
class xarTpl__XarUrlEntityNode extends xarTpl__EntityNode
{
    /**
     * Renders the entitiy &xar-url-module-type-func-argsArray-fragment-generateXMLURL
     *
     * This entity wraps xarModURL(). As in the function all parameters are optional.
     * See the function for details, 'fragment' and 'generateXMLURL' changed their positions
     * Use 'Null' for unneeded parameters to skip them
     * argsArray can be:
     * * $name of an array with parameter=>value pairs OR
     * * a list of 'parameter'=>'value' pairs as arguments for an array() function OR
     * * 'Null' to skip
     */
    function render()
    {
        if (count($this->parameters) > 7) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-url entity.', $this);
            return;
        }

        // Build the arguments for xarModURL beginning from the last parameter.
        // Parameters which position differs between entity and function need to
        // be preset with their default value and placed afterwards
        $args ='';
        $generateXMLURL = Null;

        switch (count($this->parameters)) {
        case 7 :
            // TODO entrypoint
        case 6 :
            //XMLURLS
            if (strtolower($this->parameters[5]) != 'null') {
                $generateXMLURL = $this->parameters[5];
            }
        case 5 :
            // fragment, anchor
            if (strtolower($this->parameters[4]) == 'null') {
                $args = "', Null'" . $args;
            } else {
                $args = ", '".$this->parameters[4]."'" . $args;
            }
            if (empty($generateXMLURL)) {
                $args = ", Null" . $args;
            } else {
                $args = ", '".$generateXMLURL."'" . $args;
            }
        case 4 :
            // args
            if (strtolower($this->parameters[3]) == 'null') {
                $args = ", array()" . $args;
            } elseif (substr($this->parameters[3], 0, 1) == '$') {
                $args = ', '.$this->parameters[3] . $args;
            } else {
                $args = ", array(".$this->parameters[3].")" . $args;
            }
        case 3 :
            // function
            $args = ", '".$this->parameters[2]."'" . $args;
        case 2 :
            // type
            $args = ", '".$this->parameters[1]."'" . $args;
        case 1 :
            // module
            $args = "'".$this->parameters[0]."'" . $args;
        }

        return "xarModURL($args)";
    }
}
?>
