<?php
$dir = __DIR__;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && ($file->getExtension() === 'php' || $file->getExtension() === 'html')) {
        if ($file->getFilename() == 'replace_index.php') continue;
        $content = file_get_contents($file->getPathname());
        $newContent = str_replace('href="index"', 'href="/"', $content);
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated " . $file->getFilename() . "\n";
            $count++;
        }
    }
}
echo "Total updated: $count\n";
