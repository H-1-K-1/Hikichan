<?php
// Banner rotation script
$banners_dir = 'static/banners/';
$priority_dir = 'static/banners_priority/';
$banners = [];
$priority_banners = [];

// Get regular banners
if (is_dir($banners_dir)) {
    $banners = array_filter(scandir($banners_dir), function($file) {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif']);
    });
    $banners = array_values($banners); // Reindex array
}

// Get priority banners
if (is_dir($priority_dir)) {
    $priority_banners = array_filter(scandir($priority_dir), function($file) {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif']);
    });
    $priority_banners = array_values($priority_banners);
}

// Combine banners (priority banners have higher weight)
$all_banners = [];
foreach ($banners as $banner) {
    $all_banners[] = $banners_dir . $banner;
}
foreach ($priority_banners as $banner) {
    // Add priority banners multiple times to increase selection chance (e.g., 1/3 probability)
    $all_banners[] = $priority_dir . $banner;
    $all_banners[] = $priority_dir . $banner;
}

// Select a random banner
$selected_banner = !empty($all_banners) ? $all_banners[array_rand($all_banners)] : null;

if ($selected_banner) {
    // Set content type based on extension
    $ext = strtolower(pathinfo($selected_banner, PATHINFO_EXTENSION));
    $content_types = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif'
    ];
    header('Content-Type: ' . ($content_types[$ext] ?? 'image/png'));
    
    // Output the image
    readfile($selected_banner);
} else {
    // Fallback if no banners are found
    header('Content-Type: image/png');
    $fallback = imagecreatetruecolor(468, 60);
    $white = imagecolorallocate($fallback, 255, 255, 255);
    $black = imagecolorallocate($fallback, 0, 0, 0);
    imagefilledrectangle($fallback, 0, 0, 468, 60, $white);
    imagestring($fallback, 5, 10, 20, 'No banners available', $black);
    imagepng($fallback);
    imagedestroy($fallback);
}
?>