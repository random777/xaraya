<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function main_upgrade_230()
{
    $data['upgrade']['message'] = xarML('The upgrade to version 2.3.0 was successfully completed');
    $data['upgrade']['tasks'] = array();
    
    $upgrades = array(
                        'sql_230_01', // Upgrading the core module version numbers
                        'sql_230_02', // Add a configuration field to the themes table
                        'sql_230_03', // Create the themes configurations table
                        'sql_230_04', // Import the themes configurations object
                        'sql_230_05', // register Mod* event subjects and observers
                        'sql_230_06', // Add a class column to the themes table
                    );
    foreach ($upgrades as $upgrade) {
        if (!Upgrader::loadFile('upgrades/230/database/' . $upgrade . '.php')) {
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