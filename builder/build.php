<?php

$sourceDir = __DIR__ . '/../plugins-src';
$buildDir  = __DIR__ . '/../build';

// Ensure build directory exists and is clean
if (is_dir($buildDir)) {
    array_map('unlink', glob("$buildDir/*.zip"));
} else {
    mkdir($buildDir, 0777, true);
}

// Scan plugins directory
$plugins = array_diff(scandir($sourceDir), ['.', '..']);

foreach ($plugins as $plugin) {

    $pluginPath = $sourceDir . '/' . $plugin;

    if (!is_dir($pluginPath)) {
        continue;
    }

    // ✅ UNIQUE ZIP NAME PER BUILD
    $timestamp = date('Y-m-d_H-i-s');
    $zipFile = $buildDir . '/' . $plugin . '-' . $timestamp . '.zip';

    $zip = new ZipArchive();

    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo "❌ Failed to create ZIP for: $plugin\n";
        continue;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pluginPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {

        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = $plugin . '/' . substr($filePath, strlen($pluginPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
    echo "✅ Created ZIP: $zipFile\n";
}

echo "✅ Build complete with fresh ZIP files.\n";
