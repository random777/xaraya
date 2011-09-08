<?php
/**
 * Upgrade file
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage installer module
 * @link http://xaraya.com/index.php/release/200.html
 */

function main_upgrade_211()
{
    $data['upgrade']['message'] = xarML('The upgrade to version 2.1.1 was successfully completed');
    $data['upgrade']['tasks'] = array();

    $upgrades = array(
                        'sql_211_01',
                        'sql_211_02',
                    );
    foreach ($upgrades as $upgrade) {
        if (!Upgrader::loadFile('upgrades/211/database/' . $upgrade . '.php')) {
            $data['failures'][] = array(
                'reply' => xarML('Failed!'),
                'description' => Upgrader::$errormessage,
                'reference' => $step->getFileName(),
                'success' => false,
            );
            $data['errormessage'] = xarML('Some checks failed. Check the reference(s) above to determine the cause.');
            continue;
        }
        $classname = substr($upgrade->getFileName(),0,strlen($upgrade->getFileName()) - 4);
        $class = new $classname();
        $result = $class->run();
        $data['upgrade']['tasks'][] = $class;        
        if (!$result) {
            $data['errormessage'] = xarML('Some parts of the upgrade failed. Check the reference(s) above to determine the cause.');
        }
    }
    return $data;
}
?>