<?php
/**
 *  View recent extension releases
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * View recent module releases via central repository
 *
 * @author John Cox
 * @access public
 * @param none
 * @returns array
 * @todo change feed url once release module is moved
 */
function base_admin_release($args)
{
    // Security Check
    if(!xarSecurityCheck('EditModules')) return;
    extract($args);

    // allow fopen
    if (!xarFuncIsDisabled('ini_set')) ini_set('allow_url_fopen', 1);
    if (!ini_get('allow_url_fopen')) {
        $msg = xarML('Unable to use fopen to get RSS feeds.');
        xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }
    // Require the xmlParser class
    require_once('modules/base/xarclass/xmlParser.php');
    // Require the feedParser class
    require_once('modules/base/xarclass/feedParser.php');
    // Check and see if a feed has been supplied to us.
    // Need to change the url once release module is moved to 
    $feedfile = "http://www.xaraya.com/index.php?module=release&func=rssviewnotes&theme=rss";
    // Get the feed file (from cache or from the remote site)
    $feeddata = xarModAPIFunc('base', 'user', 'getfile',
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
        $msg = xarML('There is a problem with a feed.');
        xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }
    $data['feedcontent'] = $feedcontent; 
    return $data;
}
?>