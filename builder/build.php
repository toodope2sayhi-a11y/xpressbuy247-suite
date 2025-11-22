<?php

$sourceDir = __DIR__ . '/../plugins-src';
$buildDir  = __DIR__ . '/../build';

if (!is_dir($buildDir)) {
    mkdir($buildDir, 0777, true);
}

$plugins = array_diff(scandir($sourceDir), ['.', '..']);

foreach ($plugins as $plugin) {
    $pluginPath = $sourceDir . '/' . $plugin;

    if (!is_dir($pluginPath)) {
        continue;
    }

    $zipFile = $buildDir . '/' . $plugin . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $plugin . '/' . substr($filePath, strlen($pluginPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
        echo "Created ZIP: $zipFile\n";
    }
}

echo "Build complete.\n";
