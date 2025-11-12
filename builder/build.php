<?php
declare(strict_types=1);
/**
 * XpressBuy247 – Multi-Plugin Builder (PHP 7.4 → 8.3 safe)
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

function sanitize_main_file(string $file, string $namespace): array {
    $orig = file_get_contents($file);
    $c = $orig;
    $c = preg_replace('/^\xEF\xBB\xBF/', '', $c);
    $c = preg_replace('/^\s*(?=<\?php)/', '', $c);
    if (preg_match('/(<\?php)(.*?)(namespace\s+[A-Za-z0-9_\\\\]+;)/s', $c, $m)) {
        if (!preg_match('/defined\s*\(\s*[\'\"]ABSPATH[\'\"]\s*\)\s*\|\|\s*exit\s*;/', $c)) {
            $c = str_replace($m[3], "defined('ABSPATH') || exit;\n\n".$m[3], $c);
        }
    }
    $c = str_replace(["\r\n","\r"], "\n", $c);
    $changed = ($c !== $orig);
    if ($changed) file_put_contents($file, $c);
    $lint_ok = true;
    try { token_get_all($c); } catch (Throwable $e) { $lint_ok = false; }
    $ns_ok = (strpos($c, "namespace {$namespace};") !== false);
    return ['changed'=>$changed,'lint_ok'=>$lint_ok,'ns_ok'=>$ns_ok];
}

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

$manifest = ['suite_version'=>'X.0.0.1','built_at'=>gmdate('c'),'plugins'=>[]];
foreach ($plugins as $p) {
    $slug = $p['slug']; $ns = $p['namespace']; $name = $p['name']; $ver = $p['version'];
    $srcDir = "{$SRC}/{$slug}";
    $main   = "{$srcDir}/{$slug}.php";
    if (!is_file($main)) { echo "[FAIL] Missing main file: {$main}\n"; continue; }

    $san = sanitize_main_file($main, $ns);
    $text = file_get_contents($main);
    $header_ok = (strpos($text, 'Plugin Name:') !== false);
    $guard_ok  = (strpos($text, "defined('ABSPATH') || exit;") !== false);
    $ns_ok     = $san['ns_ok'];
    $lint_ok   = $san['lint_ok'];

    $tmp = sys_get_temp_dir().'/xb247_'.uniqid()."/{$slug}";
    @mkdir($tmp, 0777, true);
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $f) {
        $dest = $tmp . substr($f->getPathname(), strlen($srcDir));
        if ($f->isDir()) { @mkdir($dest, 0777, true); }
        else { @mkdir(dirname($dest), 0777, true); copy($f->getPathname(), $dest); }
    }
    $zip = "{$BUILD}/{$slug}.zip";
    $ok = zip_dir(dirname($tmp), $zip);

    $rit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname($tmp), FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rit as $f) { $f->isDir() ? rmdir($f->getRealPath()) : unlink($f->getRealPath()); }
    @rmdir(dirname($tmp));

    $result = ($header_ok && $guard_ok && $ns_ok && $lint_ok && $ok) ? 'PASS' : 'FAIL';
    $manifest['plugins'][] = [
        'slug'=>$slug,'name'=>$name,'version'=>$ver,'zip'=>basename($zip),
        'result'=>$result,'header'=>$header_ok?'PASS':'FAIL','guard'=>$guard_ok?'PASS':'FAIL',
        'namespace'=>$ns_ok?'PASS':'FAIL','lint'=>$lint_ok?'PASS':'FAIL'
    ];
    echo "[{$result}] {$slug} → " . basename($zip) . PHP_EOL;
}

file_put_contents($BUILD.'/full-suite-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo PHP_EOL."=== Build Complete ===".PHP_EOL."Artifacts in: {$BUILD}".PHP_EOL;
