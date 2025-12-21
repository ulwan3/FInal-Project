folder images file favion.php
<?php
// Simple favicon generator
header('Content-Type: image/x-icon');

// Create a 16x16 icon
$image = imagecreate(16, 16);
$background = imagecolorallocate($image, 13, 110, 253);
imagepng($image);
imagedestroy($image);
?>