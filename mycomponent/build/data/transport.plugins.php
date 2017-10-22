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
    $pluginEvents = $realPlugin->getMany('PluginEvents');

    /** @var modPlugin $plugin */
    $plugin = $this->modx->newObject('modPlugin');
    $pluginData = $realPlugin->toArray();
    $pluginData['id'] = 0;
    $plugin->fromArray($pluginData);
    $plugin->addMany($pluginEvents);
    $plugins[] = $plugin;
}

unset($realPlugins,$pluginData);

return $plugins;