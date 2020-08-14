<?php
/**
 * @var modxBuilder $this
 * @var string $categoryName
 * @var string $namespace
 * @var array $categoryAttr
 */

$plugins = array();

/** @var modCategory $mainCategory */
$mainCategory = $this->modx->getObject('modCategory',array(
    'category' => $categoryName
));

if(!$mainCategory) return $plugins;

/** @var modPlugin[] $realPlugins */
$realPlugins = $mainCategory->getMany('Plugins');

if(!$realPlugins) return $plugins;

foreach($realPlugins as $realPlugin){
    /** @var modPluginEvent[] $pluginEvents */
    if($pluginEvents = $realPlugin->getMany('PluginEvents')){
        foreach($pluginEvents as &$pluginEvent){
            $pluginEvent->set('pluginid', 0);
        }
    }

    /** @var modPlugin $plugin */
    $plugin = $this->modx->newObject('modPlugin');
    $pluginData = $realPlugin->toArray();
    $pluginData['id'] = 0;
    //TODO remove comment if you want make your plugin static
    //$pluginData['static'] = 1;
    $plugin->fromArray($pluginData);
    $plugin->addMany($pluginEvents);
    $plugins[] = $plugin;
}

unset($realPlugins,$pluginData);

return $plugins;