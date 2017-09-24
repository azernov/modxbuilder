<?php
/**
 * @var modxBuilder $this
 */

$chunks = array();
$chunk = $this->modx->newObject('modChunk');
$chunk->fromArray(array(
    'id' => 0,
    'name' => 'exampleChunk',
    'description' => 'exampleDescription',
    'snippet' => 'exampleContent. You can get content from file by file_get_contents()',
    'static' => 0,
    'source' => 1,
    'static_file' => "core/components/{$this->config['package_name']}/elements/chunks/exampleChunk.tpl",
), '', true, true);
$chunks[] = $chunk;
unset($chunk);
return $chunks;