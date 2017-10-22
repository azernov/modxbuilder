<?php
/**
 * @var modxBuilder $this
 */

$settings = array();
$setting = $this->modx->newObject('modSystemSetting');
$setting->fromArray(
    array(
        'key' => 'test_setting',
        'namespace' => $this->config['package_name'],
        'xtype' => 'textfield',
        'value' => 'test_value',
        'area' => 'mycmp_main',
    ),'',true,true);
$settings[] = $setting;
unset($setting);
return $settings;