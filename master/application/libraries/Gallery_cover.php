<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Shared by the master and public catalog; selection stays inside one gallery. */
class Gulhouse_gallery_cover
{
    const METADATA = '.gallery-cover.json';

    public static function valid_file($directory, $filename)
    {
        if (! is_string($filename) || $filename === '' || strlen($filename) > 255
            || strpos($filename, "\0") !== FALSE || strpbrk($filename, '/\\') !== FALSE
            || ! in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), array('jpg','jpeg','png','webp'), TRUE)) return FALSE;
        $base = realpath($directory);
        $candidate = $base ? $base . DIRECTORY_SEPARATOR . $filename : '';
        if (! $base || is_link($candidate) || ! is_file($candidate)) return FALSE;
        $path = realpath($candidate);
        return $path && dirname($path) === $base && @getimagesize($path) !== FALSE;
    }

    public static function selected($directory)
    {
        $base = realpath($directory);
        if (! $base || ! is_dir($base)) return '';
        $metadata = $base . DIRECTORY_SEPARATOR . self::METADATA;
        if (is_file($metadata) && ! is_link($metadata) && filesize($metadata) <= 4096) {
            $data = json_decode((string) @file_get_contents($metadata), TRUE);
            if (is_array($data) && isset($data['file']) && self::valid_file($base, $data['file'])) return $data['file'];
        }
        $files = array();
        foreach (new DirectoryIterator($base) as $file) {
            if ($file->isFile() && self::valid_file($base, $file->getFilename())) $files[] = $file->getFilename();
        }
        usort($files, function ($a, $b) {
            $score = function ($name) { return stripos($name, 'kamar_01') === 0 ? 0 : (stripos($name, 'kamar') === 0 ? 1 : 2); };
            return $score($a) === $score($b) ? strnatcasecmp($a, $b) : $score($a) - $score($b);
        });
        return isset($files[0]) ? $files[0] : '';
    }

    public static function save($directory, $filename)
    {
        if (! self::valid_file($directory, $filename)) return FALSE;
        $base = realpath($directory);
        $metadata = $base . DIRECTORY_SEPARATOR . self::METADATA;
        if (is_link($metadata)) return FALSE;
        $temporary = @tempnam($base, '.cover-');
        if (! $temporary || dirname(realpath($temporary)) !== $base) {
            if ($temporary && is_file($temporary)) @unlink($temporary);
            return FALSE;
        }
        $data = json_encode(array('version' => 1, 'file' => $filename), JSON_UNESCAPED_SLASHES);
        $ok = @file_put_contents($temporary, $data, LOCK_EX) === strlen($data);
        if ($ok) { @chmod($temporary, 0664); $ok = @rename($temporary, $metadata); }
        if (! $ok && is_file($temporary)) @unlink($temporary);
        return $ok;
    }
}
