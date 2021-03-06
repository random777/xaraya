<?php

/**
* xarTpl__XarTemplateNode: <xar:template> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarTemplateNode extends xarTpl__TplTagNode
{
    function render()
    {
        $subdata = '$_bl_data';  // Subdata defaults to the data of the current template
        $type = 'module';        // Default type is module included template.
        extract($this->attributes);
        
        // File attribute is mandatory
        if (!isset($file)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'file\' attribute in <xar:template> tag.', $this);
            return;
        }
        
        // Resolve the file attribute
        $file = xarTpl__ExpressionTransformer::transformPHPExpression($file);
        if (!isset($file)) {
            return;
        }
        
        // Resolve subdata attribute
        $subdata = xarTpl__ExpressionTransformer::transformPHPExpression($subdata);
        
        switch($type) {
            case 'theme':
                return "xarTpl_includeThemeTemplate(\"$file\", $subdata)";
                break;
            case 'module':
                // Module attribute is optional
                if(!isset($module)) {
                    // No module attribute specified, determine it
                    // The module which needs to be passed in needs to come from the location of the
                    // template which holds the tag, not the active module although they will be the same
                    // in most cases. If the active module would be passed in, this would break when
                    // calling API functions from other modules which in turn use a template (rare, but possible,
                    // like generating xml with blocklayout). By passing in the modulename which holds the
                    // template, we make sure that the include resolves to the right file.
                    $patharray = explode('/',dirname($this->fileName));
                    // We need the value after 'modules' always, whether the container is overridden
                    foreach($patharray as $patharrayid => $patharrayname) {
                        if ($patharrayname == 'modules') {
                            $module = $patharray[$patharrayid+1];
                            break;
                        }
                    }
                }
                // Resolve the module attribute
                $module = xarTpl__ExpressionTransformer::transformPHPExpression($module);
                
                return "xarTpl_includeModuleTemplate(\"$module\", \"$file\", $subdata)";
                break;
            case 'system':
                // Tpl Include which cannot be overridden (for xml data for example), file is relative wrt containing file.
                $tplFile = dirname($this->fileName) . '/' . $file;
                return "xarTplFile(\"$tplFile\",$subdata)";
                break;
            default:
                $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,"Invalid value '$type' for 'type' attribute in <xar:template> tag.", $this);
                return;
        }
    }
    
    function needExceptionsControl()
    {
        return true;
    }
}
?>