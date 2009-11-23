<?php
/**
 *  View recent extension releases
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * View recent module releases via central repository
 *
 * @author John Cox
 * @access public
 * @param none
 * @return array Information of recent releases from http://www.xaraya.com/
 * @todo change feed url once release module is moved
 */
function base_admin_release()
{
    // Security Check
    if(!xarSecurityCheck('EditModules')) return;

    //number of releases to show
    $releasenumber=(int)xarModVars::get('base','releasenumber');

    if (!isset($releasenumber) || $releasenumber ==0) {
         $releasenumber=10;
    }

    /* allow fopen - let getfile handle this 
    if (!xarFuncIsDisabled('ini_set')) ini_set('allow_url_fopen', 1);
    if (!ini_get('allow_url_fopen')) {
        throw new ForbiddenOperationException('fopen to get RSS feeds','The current PHP configuration does not allow to use #(1) with an url');
    }
    */
    // Require the feedParser class
    sys::import('modules.base.class.feedParser');
    // Check and see if a feed has been supplied to us.
    // Need to change the url once release module is moved to
    $feedfile = "http://www.xaraya.com/index.php?module=release&func=rssviewnotes&theme=rss&releaseno=$releasenumber";

    // Get the feed file (from cache or from the remote site)
    $feeddata = xarMod::apiFunc('base', 'user', 'getfile',
                              array('url' => $feedfile,
                                    'cached' => true,
                                    'cachedir' => 'cache/rss',
                                    'refresh' => 604800,
                                    'extension' => '.xml'));

    if (!$feeddata) return;
    // Create a need feedParser object
    $p = new feedParser();
    // Tell feedParser to parse the data
    $info = $p->parseFeed($feeddata);
    if (empty($info['warning'])){
      foreach ($info as $content){
        foreach ($content as $newline){
          if(is_array($newline)) {
            if (isset($newline['description'])){
              $description = $newline['description'];
            } else {
              $description = '';
            }
            if (isset($newline['title'])){
              $title = $newline['title'];
            } else {
              $title = '';
            }
            if (isset($newline['link'])){
              $link = $newline['link'];
            } else {
              $link = '';
            }
            $feedcontent[$title] = array('title' => $title, 'link' => $link, 'description' => $description);
          }
        }
      }
      $data['chantitle']  =   $info['channel']['title'];
      $data['chanlink']   =   $info['channel']['link'];
      $data['chandesc']   =   $info['channel']['description'];
    } else {
        throw new DataNotFoundException(null,'There is a problem with a feed.');
    }
    $data['releasenumber']=$releasenumber;
    $data['feedcontent'] = $feedcontent;
    return $data;
}
?>
