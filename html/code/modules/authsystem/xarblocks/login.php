<?php
/**
 * Login Block user interface
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Login via a block: initialise block
 *
 * @author Jim McDonald
 * @return array
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Authsystem_LoginBlock extends BasicBlock implements iBlock
{
    public $nocache             = 1;

    public $name                = 'LoginBlock';
    public $module              = 'authsystem';
    public $text_type           = 'Login';
    public $text_type_long      = 'User Login';
    public $pageshared          = 1;

    public $showlogout          = 0;
    public $logouttitle         = '';

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $args=array())
    {
        $data = parent::display($args);
        if (empty($data)) return;

        $vars = !empty($data['content']) ? $data['content'] : array();
        
        if (!isset($vars['showlogout'])) $vars['showlogout'] = $this->showlogout;
        if (!isset($vars['logouttitle'])) $vars['logouttitle'] = $this->logouttitle;
        
        $vars['blockid'] = $data['bid'];
        if (xarUserIsLoggedIn()) {
            if (empty($vars['showlogout'])) return;
            $vars['name'] = xarUserGetVar('name');
            // Since we are logged in, set the template base to 'logout'.
            // FIXME: not allowed to set BL variables directly
            $data['_bl_template_base'] = 'logout';
            if (!empty($vars['logouttitle']))
                $data['title'] = $vars['logouttitle'];
        } else {
            if (xarServer::getVar('REQUEST_METHOD') == 'GET') {
                if (!xarVarFetch('return_url', 'pre:trim:str:1:254',
                    $return_url, '', XARVAR_NOT_REQUIRED)) return;
                if (empty($return_url))
                    $return_url = xarServer::getCurrentURL(array());
            }
            if (empty($return_url))
                $return_url = xarServer::getBaseURL();
            $vars['return_url'] = $return_url;
            sys::import('modules.authsystem.class.auth');
            $login = xarAuth::getAuthSubject('AuthLogin', $vars);
            $vars['loginform'] = $login->showformblock();
        }
        
        $data['content'] = $vars;
        return $data;
    }
}
?>
