<?php

// ==============================
// BUILDER EXECUTION PROOF
// ==============================
$buildDir = __DIR__ . '/../build';
if (!is_dir($buildDir)) {
    mkdir($buildDir, 0777, true);
}

// This file proves the builder actually executed
file_put_contents(
    $buildDir . '/BUILD_TRIGGERED.txt',
    'Builder ran at ' . date('Y-m-d H:i:s') . PHP_EOL
);

echo "=== BUILDER EXECUTED ===\n";

// ==============================
// CONFIG
// ==============================
$sourceDir = __DIR__ . '/../plugins-src';

// Clean old ZIPs so only fresh builds exist
if (is_dir($buildDir)) {
    foreach (glob($buildDir . '/*.zip') as $oldZip) {
        unlink($oldZip);
    }
}

// Validate source directory
if (!is_dir($sourceDir)) {
    echo "❌ plugins-src folder not found.\n";
    exit;
}

// ==============================
// BUILD PROCESS
// ==============================
$plugins = array_diff(scandir($sourceDir), ['.', '..']);

foreach ($plugins as $plugin) {

    $pluginPath = $sourceDir . '/' . $plugin;

    if (!is_dir($pluginPath)) {
        continue;
    }

    // Unique ZIP name per build
    $timestamp = date('Y-m-d_H-i-s');
    $zipFile = $buildDir . '/' . $plugin . '-' . $timestamp . '.zip';

    $zip = new ZipArchive();

    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo "❌ Failed to create ZIP for: {$plugin}\n";
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

    if (file_exists($zipFile)) {
        echo "✅ ZIP CREATED: {$zipFile}\n";
    } else {
        echo "❌ ZIP FAILED: {$zipFile}\n";
    }
}

echo "=== BUILD COMPLETE ===\n";
