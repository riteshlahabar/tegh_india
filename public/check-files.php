<?php

header('Content-Type: application/json');

$targetPath = __DIR__.'/../storage/app/public/visits';
$publicPath = __DIR__.'/storage/visits';

$targetExists = file_exists($targetPath);
$publicExists = file_exists($publicPath);

$targetFiles = $targetExists ? scandir($targetPath) : [];
$publicFiles = $publicExists ? scandir($publicPath) : [];

$symlinkPath = __DIR__.'/storage';
$symlinkInfo = null;
$symlinkTarget = null;
if (file_exists($symlinkPath)) {
    $symlinkInfo = is_link($symlinkPath) ? 'is symlink' : 'is normal directory/file';
    $symlinkTarget = is_link($symlinkPath) ? readlink($symlinkPath) : null;
} else {
    $symlinkInfo = 'does not exist';
}

echo json_encode([
    'target_path' => realpath($targetPath) ?: $targetPath,
    'target_exists' => $targetExists,
    'target_files' => $targetFiles,

    'public_path' => realpath($publicPath) ?: $publicPath,
    'public_exists' => $publicExists,
    'public_files' => $publicFiles,

    'symlink_path' => realpath($symlinkPath) ?: $symlinkPath,
    'symlink_info' => $symlinkInfo,
    'symlink_target' => $symlinkTarget,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null,
    'php_version' => PHP_VERSION,
], JSON_PRETTY_PRINT);
