<?php
/**
 * @var modxBuilder $this
 * @var string $categoryName
 * @var string $namespace
 */

$templateVars = array();

/** @var modCategory $mainCategory */
$mainCategory = $this->modx->getObject('modCategory',array(
    'category' => $categoryName
));

if(!$mainCategory) return $templateVars;

/** @var modTemplateVar[] $realTemplateVars */
$realTemplateVars = $mainCategory->getMany('TemplateVars');

if(!$realTemplateVars) return $templateVars;

foreach($realTemplateVars as $realTemplateVar){
    /** @var modTemplateVarTemplate $templateVarTemplates */
    $templateVarTemplates = $realTemplateVar->getMany('TemplateVarTemplates');

    /** @var modTemplateVar $templateVar */
    $templateVar = $this->modx->newObject('modTemplateVar');
    $templateVarData = $realTemplateVar->toArray();
    $templateVarData['id'] = 0;
    $templateVar->fromArray($templateVarData);
    $templateVar->addMany($templateVarTemplates);
    $templateVars[] = $templateVar;
}

unset($realTemplateVars,$templateVarData);

return $templateVars;