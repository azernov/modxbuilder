<?php
/**
 * @var modxBuilder $this
 * @var string $categoryName
 * @var string $namespace
 */

$snippets = array();

/** @var modCategory $mainCategory */
$mainCategory = $this->modx->getObject('modCategory',array(
    'category' => $categoryName
));

if(!$mainCategory) return $snippets;

/** @var modSnippet[] $realSnippets */
$realSnippets = $mainCategory->getMany('Snippets');

if(!$realSnippets) return $snippets;

foreach($realSnippets as $realSnippet){
    /** @var modSnippet $snippet */
    $snippet = $this->modx->newObject('modSnippet');
    $snippetData = $realSnippet->toArray();
    $snippetData['id'] = 0;
    $snippet->fromArray($snippetData);
    $snippets[] = $snippet;
}

unset($realSnippets,$snippetData);

return $snippets;