<?php
// Simple logo generator
header('Content-Type: image/png');

$width = 200;
$height = 60;

$image = imagecreate($width, $height);
$background = imagecolorallocate($image, 13, 110, 253); // Bootstrap primary color
$text_color = imagecolorallocate($image, 255, 255, 255);

// Add text
imagestring($image, 5, 20, 20, 'Keuangan Mahasiswa', $text_color);

imagepng($image);
imagedestroy($image);
?>