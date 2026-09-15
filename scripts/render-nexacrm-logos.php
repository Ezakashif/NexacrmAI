<?php

/**
 * Rasterize the packaged NexaCRM wordmarks (dark + light) for Super Admin / OG use.
 *
 * Usage: php scripts/render-nexacrm-logos.php
 */
$bold = '/usr/share/fonts/truetype/macos/Inter-Bold.ttf';
if (! is_file($bold)) {
    $bold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
}

$targets = [
    [
        'path' => __DIR__.'/../public/branding/nexacrm-logo.png',
        'bg' => [15, 23, 42],
        'mark' => [56, 189, 248],
        'text' => [15, 23, 42],
        'transparent' => true,
    ],
    [
        'path' => __DIR__.'/../public/branding/nexacrm-logo-light.png',
        'bg' => [226, 232, 240],
        'mark' => [15, 23, 42],
        'text' => [248, 250, 252],
        'transparent' => true,
    ],
];

foreach ($targets as $target) {
    $w = 860;
    $h = 200;
    $im = imagecreatetruecolor($w, $h);
    imagesavealpha($im, true);
    imagealphablending($im, false);
    $clear = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefilledrectangle($im, 0, 0, $w, $h, $clear);
    imagealphablending($im, true);

    $bg = imagecolorallocate($im, $target['bg'][0], $target['bg'][1], $target['bg'][2]);
    $mark = imagecolorallocate($im, $target['mark'][0], $target['mark'][1], $target['mark'][2]);
    $text = imagecolorallocate($im, $target['text'][0], $target['text'][1], $target['text'][2]);

    $x = 20;
    $y = 36;
    $size = 128;
    $radius = 32;
    imagefilledrectangle($im, $x + $radius, $y, $x + $size - $radius, $y + $size, $bg);
    imagefilledrectangle($im, $x, $y + $radius, $x + $size, $y + $size - $radius, $bg);
    imagefilledellipse($im, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $bg);
    imagefilledellipse($im, $x + $size - $radius, $y + $radius, $radius * 2, $radius * 2, $bg);
    imagefilledellipse($im, $x + $radius, $y + $size - $radius, $radius * 2, $radius * 2, $bg);
    imagefilledellipse($im, $x + $size - $radius, $y + $size - $radius, $radius * 2, $radius * 2, $bg);

    imagesetthickness($im, 10);
    imageline($im, $x + 36, $y + 112, $x + 36, $y + 16, $mark);
    imageline($im, $x + 36, $y + 16, $x + 92, $y + 112, $mark);
    imageline($im, $x + 92, $y + 112, $x + 92, $y + 16, $mark);
    imagesetthickness($im, 1);

    imagettftext($im, 64, 0, 176, 122, $text, $bold, 'nexacrm.');

    imagepng($im, $target['path'], 6);
    imagedestroy($im);
    echo "saved {$target['path']}\n";
}

$coverPath = __DIR__.'/../public/branding/nexacrm-linkedin-cover.png';
$W = 1584;
$H = 396;
$cover = imagecreatetruecolor($W, $H);
imagealphablending($cover, true);
$white = imagecolorallocate($cover, 255, 255, 255);
$slate = imagecolorallocate($cover, 15, 23, 42);
$muted = imagecolorallocate($cover, 100, 116, 139);
$accent = imagecolorallocate($cover, 37, 99, 235);
$sky = imagecolorallocate($cover, 56, 189, 248);
imagefilledrectangle($cover, 0, 0, $W, $H, $white);

for ($x = (int) ($W * 0.48); $x < $W; $x++) {
    $t = ($x - $W * 0.48) / ($W * 0.52);
    $r = (int) round(255 * (1 - $t) + 239 * $t);
    $g = (int) round(255 * (1 - $t) + 246 * $t);
    $b = (int) round(255 * (1 - $t) + 255 * $t);
    imageline($cover, $x, 0, $x, $H, imagecolorallocate($cover, $r, $g, $b));
}

$semi = '/usr/share/fonts/truetype/macos/Inter-SemiBold.ttf';
$reg = '/usr/share/fonts/truetype/macos/Inter-Regular.ttf';
if (! is_file($semi)) {
    $semi = $bold;
}
if (! is_file($reg)) {
    $reg = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
}

$markX = 80;
$markY = 70;
$markSize = 56;
$radius = 14;
imagefilledrectangle($cover, $markX + $radius, $markY, $markX + $markSize - $radius, $markY + $markSize, $slate);
imagefilledrectangle($cover, $markX, $markY + $radius, $markX + $markSize, $markY + $markSize - $radius, $slate);
imagefilledellipse($cover, $markX + $radius, $markY + $radius, $radius * 2, $radius * 2, $slate);
imagefilledellipse($cover, $markX + $markSize - $radius, $markY + $radius, $radius * 2, $radius * 2, $slate);
imagefilledellipse($cover, $markX + $radius, $markY + $markSize - $radius, $radius * 2, $radius * 2, $slate);
imagefilledellipse($cover, $markX + $markSize - $radius, $markY + $markSize - $radius, $radius * 2, $radius * 2, $slate);
imagesetthickness($cover, 4);
imageline($cover, $markX + 16, $markY + 42, $markX + 16, $markY + 14, $sky);
imageline($cover, $markX + 16, $markY + 14, $markX + 40, $markY + 42, $sky);
imageline($cover, $markX + 40, $markY + 42, $markX + 40, $markY + 14, $sky);
imagesetthickness($cover, 1);

imagettftext($cover, 28, 0, $markX + $markSize + 18, $markY + 38, $slate, $bold, 'NexaCRM');
imagettftext($cover, 22, 0, 80, 180, $slate, $semi, 'A Modern Multi-Tenant CRM for Growing Businesses');
imagettftext($cover, 14, 0, 80, 220, $muted, $reg, 'A ready-to-customize Laravel CRM SaaS for developers, agencies, and businesses.');
imagettftext($cover, 13, 0, 80, 280, $accent, $semi, 'Leads · Customers · Tasks · Pipeline');

imagepng($cover, $coverPath, 6);
imagedestroy($cover);
echo "saved {$coverPath}\n";
