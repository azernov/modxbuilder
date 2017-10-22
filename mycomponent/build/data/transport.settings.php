<?php
/**
 * @var modxBuilder $this
 * @var string $categoryName
 * @var string $namespace
 */

$settings = array();

$realSettings = $this->modx->getCollection('modSystemSetting',array(
    'namespace' => $namespace
));

if(!$realSettings) return $settings;

/** @var modSystemSetting[] $realSettings */
foreach($realSettings as $realSetting){
    /** @var modSystemSetting $setting */
    $setting = $this->modx->newObject('modSystemSetting');
    $settingData = $realSetting->toArray();
    $setting->fromArray($settingData,'',true);
    $settings[] = $setting;
}

unset($realSettings,$settingData);

return $settings;