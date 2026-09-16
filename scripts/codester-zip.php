#!/usr/bin/env php
<?php

/**
 * Create or list a ZIP without the Info-ZIP `zip` / `unzip` CLIs.
 * Seller-only helper used by scripts/build-codester-package.sh.
 */

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "PHP zip extension (ZipArchive) is not enabled.\n");
    exit(1);
}

$command = $argv[1] ?? '';

if ($command === 'create') {
    $sourceDir = $argv[2] ?? '';
    $zipPath = $argv[3] ?? '';
    if ($sourceDir === '' || $zipPath === '') {
        fwrite(STDERR, "usage: codester-zip.php create <source-dir> <zip-path>\n");
        exit(1);
    }

    $sourceDir = realpath($sourceDir);
    if ($sourceDir === false || ! is_dir($sourceDir)) {
        fwrite(STDERR, "source directory not found\n");
        exit(1);
    }

    $zipDir = dirname($zipPath);
    if (! is_dir($zipDir) && ! mkdir($zipDir, 0775, true) && ! is_dir($zipDir)) {
        fwrite(STDERR, "could not create zip directory\n");
        exit(1);
    }

    if (is_file($zipPath)) {
        unlink($zipPath);
    }

    $zip = new ZipArchive;
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        fwrite(STDERR, "could not create zip\n");
        exit(1);
    }

    $rootName = basename($sourceDir);
    $prefixLength = strlen($sourceDir) + 1;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), $prefixLength));
        $entry = $rootName.'/'.$relative;

        if ($file->isDir()) {
            $zip->addEmptyDir($entry);
            continue;
        }

        if (! $zip->addFile($file->getPathname(), $entry)) {
            $zip->close();
            fwrite(STDERR, "could not add {$entry}\n");
            exit(1);
        }
    }

    $zip->close();
    exit(0);
}

if ($command === 'list') {
    $zipPath = $argv[2] ?? '';
    if ($zipPath === '' || ! is_file($zipPath)) {
        fwrite(STDERR, "usage: codester-zip.php list <zip-path>\n");
        exit(1);
    }

    $zip = new ZipArchive;
    if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
        fwrite(STDERR, "could not open zip\n");
        exit(1);
    }

    for ($i = 0; $i < $zip->numFiles; $i++) {
        echo $zip->getNameIndex($i), PHP_EOL;
    }

    $zip->close();
    exit(0);
}

fwrite(STDERR, "usage: codester-zip.php <create|list> ...\n");
exit(1);
