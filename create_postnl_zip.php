<?php
/**
 * Script to create PostNL plugin ZIP file for Joomla installation
 */

$sourceDir = __DIR__ . '/plugins/easystoreshipping/postnl';
$zipFile = __DIR__ . '/plg_easystoreshipping_postnl.zip';

if (!extension_loaded('zip')) {
    die("ZIP extension not available\n");
}

if (!is_dir($sourceDir)) {
    die("Source directory does not exist: $sourceDir\n");
}

// Remove old zip if exists
if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();

if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Cannot create ZIP file: $zipFile\n");
}

// Recursive function to add files
function addDirectoryToZip($zip, $dir, $base) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($base) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
}

addDirectoryToZip($zip, $sourceDir, dirname($sourceDir));

$zip->close();

if (file_exists($zipFile)) {
    echo "ZIP file created successfully: $zipFile\n";
    echo "File size: " . filesize($zipFile) . " bytes\n";
} else {
    echo "Failed to create ZIP file\n";
}
