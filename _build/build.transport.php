<?php
/**
 * SyntaxChecker
 *
 * Copyright 2011 by Everett Griffiths <everett@fireproofsocks.com>
 *
 * This is a plugin for MODX 2.2.x, designed to check the tag syntax of MODX
 * documents, templates, and chunks.
 *
 * SyntaxChecker is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * SyntaxChecker is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Quip; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package syntaxchecker
 */
 /**
 * SyntaxChecker build script
 *
 * @package syntaxchecker
 * @subpackage build
 */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

echo 'Creating Package...';

if (!defined('MODX_CORE_PATH')) {
	define('MODX_CORE_PATH', '/Users/everett2/Sites/revo2/html/core/');
}
if (!defined('MODX_CONFIG_KEY')) {
	define('MODX_CONFIG_KEY', 'config');
}

// fire up MODX
require_once( MODX_CORE_PATH . 'model/modx/modx.class.php');
$modx = new modx();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO'); echo '<pre>'; flush();

define('PKG_NAME', 'SyntaxChecker');
define('PKG_NAME_LOWER', strtolower(PKG_NAME));
define('PKG_VERSION', '0.1');
define('PKG_RELEASE', 'beta');

$modx->loadClass('transport.modPackageBuilder', '', false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER, false, true, '{core_path}components/' . PKG_NAME_LOWER.'/');

// Get the object to be packaged
$Plugin = $modx->newObject('modPlugin');
$Plugin->set('name', PKG_NAME);
$Plugin->set('description', 'Checks MODX documents, templates, and chunks for invalid syntax and alerts the users to the problems.');

// Get the plugin content
// You can slurp contents from a file, but you gotta remove the opening & closing <?php tags.
$plugin_contents = file_get_contents('/Users/everett2/Sites/revo2/html/assets/components/syntaxchecker/elements/plugins/plugin.syntaxchecker.php');
$plugin_contents = str_replace('<?php', '', $plugin_contents);
$plugin_contents = str_replace('?>', '', $plugin_contents);
$plugin_contents = trim($plugin_contents);

$Plugin->setContent($plugin_contents);

// Plugin events
$Events['OnBeforeDocFormSave'] = $modx->newObject('modPluginEvent');
$Events['OnBeforeDocFormSave']->fromArray(array(
    'event' => 'OnBeforeDocFormSave',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);

$Events['OnBeforeChunkFormSave'] = $modx->newObject('modPluginEvent');
$Events['OnBeforeChunkFormSave']->fromArray(array(
    'event' => 'OnBeforeChunkFormSave',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);

$Events['OnBeforeTVFormSave'] = $modx->newObject('modPluginEvent');
$Events['OnBeforeTVFormSave']->fromArray(array(
    'event' => 'OnBeforeTVFormSave',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);

$Events['OnBeforeTempFormSave'] = $modx->newObject('modPluginEvent');
$Events['OnBeforeTempFormSave']->fromArray(array(
    'event' => 'OnBeforeTempFormSave',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);

$Plugin->addMany($Events);


$attributes = array(
	xPDOTransport::UNIQUE_KEY => 'name',
	xPDOTransport::PRESERVE_KEYS => false,
	xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
        'PluginEvents' => array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => false,
            xPDOTransport::UNIQUE_KEY => array('pluginid','event'),
        ),
    ),

);

$vehicle = $builder->createVehicle($Plugin, $attributes);

// Copy over related files
$vehicle->resolve('file',array(
    'source' => MODX_ASSETS_PATH .'components/'.PKG_NAME_LOWER,
    'target' => "return MODX_CORE_PATH . 'components/';",
));


// Insert the vehicle into the transport package 
$builder->putVehicle($vehicle);


/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes(array(
    'license' => file_get_contents(MODX_ASSETS_PATH .'components/'.PKG_NAME_LOWER.'/docs/license.txt'),
    'readme' => file_get_contents(MODX_ASSETS_PATH .'components/'.PKG_NAME_LOWER.'/docs/readme.txt'),
    'changelog' => file_get_contents(MODX_ASSETS_PATH .'components/'.PKG_NAME_LOWER.'/docs/changelog.txt'),
//    'setup-options' => array(
//        'source' => MODX_ASSETS_PATH .'components/docs/user.input.html',
//   ),
));


// Zip up the package
$builder->pack();

echo '<br/>Package complete.';
/*EOF*/