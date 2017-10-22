<?php
/**
 * @var modxBuilder $this
 * @var string $categoryName
 * @var string $namespace
 */

$menus = array();

$realMenus = $this->modx->getCollection('modMenu',array(
    'namespace' => $namespace
));

if(!$realMenus) return $menus;

/** @var modMenu[] $realMenus */
foreach($realMenus as $realMenu){
    /** @var modMenu $menu */
    $menu = $this->modx->newObject('modMenu');
    $menuData = $realMenu->toArray();
    $menu->fromArray($menuData,'',true);
    $menus[] = $menu;
}

unset($realMenus,$menuData);

return $menus;