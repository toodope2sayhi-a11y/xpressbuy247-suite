<?php
declare(strict_types=1);
/**
 * XpressBuy247 – Multi-Plugin Builder (PHP 7.4 → 8.3 safe)
 * Extended with Manifest Embedding (Guardian-Compatible)
 */

$ROOT  = __DIR__ . '/..';
$SRC   = $ROOT . '/plugins-src';
$BUILD = $ROOT . '/build';
@mkdir($BUILD, 0777, true);

$plugins = [
    ['slug'=>'xpressbuy247-core-framework-pro','name'=>'XpressBuy247 Core Framework Pro','namespace'=>'XpressBuy247\\CoreFrameworkPro','version'=>'X.0.0.1'],
    ['slug'=>'xpressbuy247-guardian-suite-pro','name'=>'XpressBuy247 Guardian Suite Pro','namespace'=>'XpressBuy247\\GuardianSuitePro','version'=>'X.0.0.1'],
    ['slug'=>'xpressbuy247-smart-sandbox-installer-pro','name'=>'XpressBuy247 Smart Sandbox Installer Pro','namespace'=>'XpressBuy247\\SandboxInstallerPro','version'=>'X.0.0.1'],
    ['slug'=>'xpressbuy247-mu-watchdog-pro','name'=>'XpressBuy247 MU Watchdog Pro','namespace'=>'XpressBuy247\\WatchdogPro','version'=>'X.0.0.1'],
];

/**
 * Sanitize and reorder plugin main file
 */
function sanitize_main_file(string $file, string $namespace): array {
    $orig = file_get_contents($file);
    $c = $orig;

    // Normalize line endings and remove BOM
    $c = str_replace(["\r\n", "\r"], "\n", $c);
    $c = preg_replace('/^\xEF\xBB\xBF/', '', $c);

    // --- AUTO-FIX: header / namespace order ---
    if (preg_match('/(<\?php\s*)(\/\*\*[\s\S]*?\*\/)?\s*(declare\(strict_types=1;\))?/i', $c, $m)) {
        $php = $m[1] ?? '<?php';
        $header = $m[2] ?? '';
        $declare = $m[3] ?? '';
        $body = preg_replace('/^<\?php[\s\S]*/', '', $c);
        $newHeader = $php . "\n" . $declare . "\n\nnamespace {$namespace};\n\ndefined('ABSPATH') || exit;\n\n" . $header . "\n";
        $c = $newHeader . $body;
    }

    $changed = ($c !== $orig);
    if ($changed) file_put_contents($file, $c);

    // Basic lint / namespace check
    $lint_ok = true;
    try { token_get_all($c); } catch (Throwable $e) { $lint_ok = false; }
    $ns_ok = (strpos($c, "namespace {$namespace};") !== false);
    return ['changed'=>$changed,'lint_ok'=>$lint_ok,'ns_ok'=>$ns_ok];
}

/**
 * Zip a directory recursively
 */
function zip_dir(string $source, string $zipPath): bool {
    if (!extension_loaded('zip')) throw new RuntimeException('Zip extension not available');
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
    $source = realpath($source);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $file) {
        $path = $file->getRealPath();
        $local = substr($path, strlen($source) + 1);
        if ($file->isDir()) $zip->addEmptyDir($local);
        else $zip->addFile($path, $local);
    }
    return $zip->close();
}

// ------------------------------------------------------------------

$manifest = ['suite_version'=>'X.0.0.1','built_at'=>gmdate('c'),'plugins'=>[]];

foreach ($plugins as $p) {
    $slug = $p['slug']; 
    $ns = $p['namespace']; 
    $name = $p['name']; 
    $ver = $p['version'];

    $srcDir = "{$SRC}/{$slug}";
    $main   = "{$srcDir}/{$slug}.php";
    if (!is_file($main)) { echo "[FAIL] Missing main file: {$main}\n"; continue; }

    $san = sanitize_main_file($main, $ns);
    $text = file_get_contents($main);
    $header_ok = (strpos($text, 'Plugin Name:') !== false);
    $guard_ok  = (strpos($text, "defined('ABSPATH') || exit;") !== false);
    $ns_ok     = $san['ns_ok'];
    $lint_ok   = $san['lint_ok'];

    // Copy source into temp folder for zipping
    $tmpRoot = sys_get_temp_dir().'/xb247_'.uniqid();
    $tmpPlugin = "{$tmpRoot}/{$slug}";
    @mkdir($tmpPlugin, 0777, true);

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $f) {
        $dest = $tmpPlugin . substr($f->getPathname(), strlen($srcDir));
        if ($f->isDir()) { @mkdir($dest, 0777, true); }
        else { @mkdir(dirname($dest), 0777, true); copy($f->getPathname(), $dest); }
    }

    $zip = "{$BUILD}/{$slug}.zip";
    $ok = zip_dir($tmpRoot, $zip);

    // Cleanup temp
    $rit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rit as $f) { $f->isDir() ? rmdir($f->getRealPath()) : unlink($f->getRealPath()); }
    @rmdir($tmpRoot);

    $result = ($header_ok && $guard_ok && $ns_ok && $lint_ok && $ok) ? 'PASS' : 'FAIL';
    $manifest['plugins'][] = [
        'slug'=>$slug,'name'=>$name,'version'=>$ver,'zip'=>basename($zip),
        'result'=>$result,'header'=>$header_ok?'PASS':'FAIL','guard'=>$guard_ok?'PASS':'FAIL',
        'namespace'=>$ns_ok?'PASS':'FAIL','lint'=>$lint_ok?'PASS':'FAIL'
    ];
    echo "[{$result}] {$slug} → " . basename($zip) . PHP_EOL;
}

// ------------------------------------------------------------------
// Embed per-plugin manifest (if present) and finalize suite manifest
// ------------------------------------------------------------------

$manifestDir = __DIR__ . '/manifests';
foreach ($manifest['plugins'] as &$plugin) {
    $slug = $plugin['slug'];
    $ver  = $plugin['version'];
    $zip  = "{$BUILD}/{$slug}.zip";
    $json = "{$manifestDir}/{$slug}.json";

    if (is_file($json) && is_file($zip)) {
        $data = json_decode(file_get_contents($json), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Add checksum + build timestamp
            $data['build_metadata']['checksum'] = hash_file('sha256', $json);
            $data['build_metadata']['built_at'] = gmdate('c');
            file_put_contents($json, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $zipObj = new ZipArchive();
            if ($zipObj->open($zip) === true) {
                $zipObj->addFile($json, "{$slug}/manifest.json");
                $zipObj->close();
                $plugin['manifest_embedded'] = 'YES';
                echo "[EMBED] Added manifest.json into {$slug}.zip\n";
            } else {
                $plugin['manifest_embedded'] = 'NO';
                echo "[WARN] Could not reopen {$slug}.zip to embed manifest\n";
            }
        } else {
            echo "[WARN] Invalid JSON manifest for {$slug}\n";
        }
    } else {
        $plugin['manifest_embedded'] = 'NO';
    }
}
unset($plugin);

// Write suite manifest
$manifest['built_at'] = gmdate('c');
file_put_contents($BUILD . '/full-suite-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo PHP_EOL . "=== Build Complete ===" . PHP_EOL . "Artifacts in: {$BUILD}" . PHP_EOL;
