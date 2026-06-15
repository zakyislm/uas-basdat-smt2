<?php
$html = file_get_contents("https://unsplash.com/s/photos/motorcycle");
preg_match_all('/https:\/\/images\.unsplash\.com\/photo-[a-zA-Z0-9\-]+/', $html, $m);
$unique = array_values(array_unique($m[0]));
print_r(array_slice($unique, 0, 15));
?>
