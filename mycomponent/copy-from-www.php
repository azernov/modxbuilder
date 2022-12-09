<?php
/**
 * Копирует актуальные файлы из www каталога
 */

$buildConfig = include __DIR__.'/build/config/config.inc.php';

$assetsSource = dirname(__DIR__, 2).'/www/assets/components/'.$buildConfig['package_name'];
$coreSource = dirname(__DIR__, 2).'/www/core/components/'.$buildConfig['package_name'];

function recurseCopy(
    string $sourceDirectory,
    string $destinationDirectory,
    string $childFolder = ''
): void {
    $directory = opendir($sourceDirectory);

    if (is_dir($destinationDirectory) === false) {
        mkdir($destinationDirectory);
    }

    if ($childFolder !== '') {
        if (is_dir("$destinationDirectory/$childFolder") === false) {
            mkdir("$destinationDirectory/$childFolder");
        }

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir("$sourceDirectory/$file") === true) {
                recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
            } else {
                copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
            }
        }

        closedir($directory);

        return;
    }

    while (($file = readdir($directory)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        if (is_dir("$sourceDirectory/$file") === true) {
            recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$file");
        }
        else {
            copy("$sourceDirectory/$file", "$destinationDirectory/$file");
        }
    }

    closedir($directory);
}

recurseCopy($assetsSource, __DIR__.'/assets/components/'.$buildConfig['package_name']);
recurseCopy($coreSource, __DIR__.'/core/components/'.$buildConfig['package_name']);
