<?php
/* Get the core config */
if (!file_exists(dirname(dirname(__FILE__)).'/config.core.php')) {
    die('ERROR: missing '.dirname(dirname(__FILE__)).'/config.core.php file defining the MODX core path.');
}

echo "<pre>";
/* Boot up MODX */
echo "Loading modX...\n";
require_once dirname(dirname(__FILE__)).'/config.core.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
echo "Initializing manager...\n";
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

$componentPath = dirname(dirname(__FILE__));

/* Namespace */
if (!createObject('modNamespace',array(
    'name' => 'blockup',
    'path' => $componentPath.'/core/components/blockup/',
    'assets_path' => $componentPath.'/assets/components/blockup/',
),'name', false)) {
    echo "Error creating namespace blockup.\n";
}

/* Path settings */
if (!createObject('modSystemSetting', array(
    'key' => 'blockup.core_path',
    'value' => $componentPath.'/core/components/blockup/',
    'xtype' => 'textfield',
    'namespace' => 'blockup',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating blockup.core_path setting.\n";
}

if (!createObject('modSystemSetting', array(
    'key' => 'blockup.assets_path',
    'value' => $componentPath.'/assets/components/blockup/',
    'xtype' => 'textfield',
    'namespace' => 'blockup',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating blockup.assets_path setting.\n";
}

/* Fetch assets url */
$url = 'http';
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
    $url .= 's';
}
$url .= '://'.$_SERVER["SERVER_NAME"];
if ($_SERVER['SERVER_PORT'] != '80') {
    $url .= ':'.$_SERVER['SERVER_PORT'];
}
$requestUri = $_SERVER['REQUEST_URI'];
$bootstrapPos = strpos($requestUri, '_bootstrap/');
$requestUri = rtrim(substr($requestUri, 0, $bootstrapPos), '/').'/';
$assetsUrl = "{$url}{$requestUri}assets/components/blockup/";

if (!createObject('modSystemSetting', array(
    'key' => 'blockup.assets_url',
    'value' => '[[++assets_url]]components/blockup/',
    'xtype' => 'textfield',
    'namespace' => 'blockup',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating blockup.assets_url setting.\n";
}

if (!createObject('modPlugin', array(
    'name' => 'blockup',
    'static' => true,
    'static_file' => $componentPath.'/core/components/blockup/elements/plugins/blockup.plugin.php',
), 'name', true)) {
    echo "Error creating blockup Plugin.\n";
}
$plugin = $modx->getObject('modPlugin', array('name' => 'blockup'));
if ($plugin) {
    if (!createObject('modPluginEvent', array(
        'pluginid' => $plugin->get('id'),
        'event' => 'ContentBlocks_RegisterInputs',
        'priority' => 0,
    ), array('pluginid','event'), false)) {
        echo "Error creating modPluginEvent ContentBlocks_RegisterInputs.\n";
    }
}

$settings = include dirname(dirname(__FILE__)).'/_build/data/settings.php';
foreach ($settings as $key => $opts) {
    if (!createObject('modSystemSetting', array(
        'key' => 'blockup.' . $key,
        'value' => $opts['value'],
        'xtype' => (isset($opts['xtype'])) ? $opts['xtype'] : 'textfield',
        'namespace' => 'blockup',
        'area' => $opts['area'],
        'editedon' => time(),
    ), 'key', false)) {
        echo "Error creating blockup.".$key." setting.\n";
    }
}

echo "Done.";


/**
 * Creates an object.
 *
 * @param string $className
 * @param array $data
 * @param string $primaryField
 * @param bool $update
 * @return bool
 */
function createObject ($className = '', array $data = array(), $primaryField = '', $update = true) {
    global $modx;
    /* @var xPDOObject $object */
    $object = null;

    /* Attempt to get the existing object */
    if (!empty($primaryField)) {
        $condition = array($primaryField => $data[$primaryField]);
        if (is_array($primaryField)) {
            $condition = array();
            foreach ($primaryField as $key) {
                $condition[$key] = $data[$key];
            }
        }
        $object = $modx->getObject($className, $condition);
        if ($object instanceof $className) {
            if ($update) {
                $object->fromArray($data);
                return $object->save();
            } else {
                $condition = $modx->toJSON($condition);
                echo "Skipping {$className} {$condition}: already exists.\n";
                return true;
            }
        }
    }

    /* Create new object if it doesn't exist */
    if (!$object) {
        $object = $modx->newObject($className);
        $object->fromArray($data, '', true);
        return $object->save();
    }

    return false;
}
