<?php
/**
 * @var modxBuilder $this
 * @var string $categoryName
 * @var string $namespace
 */

$chunks = array();

/** @var modCategory $mainCategory */
$mainCategory = $this->modx->getObject('modCategory',array(
    'category' => $categoryName
));

if(!$mainCategory) return $chunks;

/** @var modChunk[] $realChunks */
$realChunks = $mainCategory->getMany('Chunks');

if(!$realChunks) return $chunks;

foreach($realChunks as $realChunk){
    /** @var modChunk $chunk */
    $chunk = $this->modx->newObject('modChunk');
    $chunkData = $realChunk->toArray();
    $chunkData['id'] = 0;
    $chunk->fromArray($chunkData);
    $chunks[] = $chunk;
}

unset($realChunks,$chunkData);

return $chunks;