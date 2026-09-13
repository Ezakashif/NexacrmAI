<?php

/**
 * Build a LinkedIn company cover (1584x396) with a clear bottom-left
 * safe zone for the overlapping circular company logo.
 */

$W = 1584;
$H = 396;
$outPath = $argv[1] ?? __DIR__.'/../public/branding/algos-crm-linkedin-cover.png';
$logoPath = __DIR__.'/../public/branding/algos-logo.png';
$uiSrcPath = $argv[2] ?? null;

$out = imagecreatetruecolor($W, $H);
imagealphablending($out, true);
imagesavealpha($out, true);

$white = imagecolorallocate($out, 255, 255, 255);
$slate = imagecolorallocate($out, 15, 23, 42);
$muted = imagecolorallocate($out, 100, 116, 139);
$accent = imagecolorallocate($out, 37, 99, 235);
$sky = imagecolorallocate($out, 56, 189, 248);

imagefilledrectangle($out, 0, 0, $W, $H, $white);

// Soft right-side blue wash
for ($x = (int) ($W * 0.48); $x < $W; $x++) {
    $t = ($x - $W * 0.48) / ($W * 0.52);
    $r = (int) round(255 * (1 - $t) + 239 * $t);
    $g = (int) round(255 * (1 - $t) + 246 * $t);
    $b = (int) round(255 * (1 - $t) + 255 * $t);
    imageline($out, $x, 0, $x, $H, imagecolorallocate($out, $r, $g, $b));
}

// Bottom-left safe zone — soft brand wash for LinkedIn circular logo
$cx = 155;
$cy = $H + 5;
for ($y = 0; $y < $H; $y++) {
    for ($x = 0; $x < 300; $x++) {
        $nx = ($x - $cx) / 175;
        $ny = ($y - $cy) / 175;
        $d = sqrt($nx * $nx + $ny * $ny);
        if ($d > 1.55) {
            continue;
        }
        $t = pow(max(0, min(1, (1.55 - $d) / 1.55)), 1.15) * 0.9;
        $rgb = imagecolorat($out, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        imagesetpixel(
            $out,
            $x,
            $y,
            imagecolorallocate(
                $out,
                (int) round($r * (1 - $t) + 219 * $t),
                (int) round($g * (1 - $t) + 234 * $t),
                (int) round($b * (1 - $t) + 254 * $t)
            )
        );
    }
}

// Dot grid only ABOVE the logo overlap (looks intentional, not under avatar)
$dot = imagecolorallocatealpha($out, 148, 163, 184, 105);
for ($y = 16; $y < 200; $y += 16) {
    for ($x = 300; $x < 720; $x += 16) {
        imagefilledellipse($out, $x, $y, 2, 2, $dot);
    }
}

$blob = imagecolorallocatealpha($out, 96, 165, 250, 95);
imagefilledellipse($out, $W - 60, 30, 200, 120, $blob);
$blob2 = imagecolorallocatealpha($out, 56, 189, 248, 105);
imagefilledellipse($out, $W - 200, -10, 160, 100, $blob2);

$fontBold = is_file('C:/Windows/Fonts/segoeuib.ttf') ? 'C:/Windows/Fonts/segoeuib.ttf' : 'C:/Windows/Fonts/arialbd.ttf';
$fontReg = is_file('C:/Windows/Fonts/segoeui.ttf') ? 'C:/Windows/Fonts/segoeui.ttf' : 'C:/Windows/Fonts/arial.ttf';

// Keep copy in the upper band and right of the avatar circle.
$contentX = 400;
$y = 36;

// Draw brand mark (avoid logo PNG black background)
$markSize = 40;
$markX = $contentX;
$markY = $y;
$markBg = imagecolorallocate($out, 15, 23, 42);
imagefilledrectangle($out, $markX, $markY, $markX + $markSize, $markY + $markSize, $markBg);
// Soft rounded look via corner pixels
$corner = imagecolorallocate($out, 255, 255, 255);
for ($i = 0; $i < 6; $i++) {
    for ($j = 0; $j < 6 - $i; $j++) {
        imagesetpixel($out, $markX + $i, $markY + $j, $white);
        imagesetpixel($out, $markX + $markSize - $i, $markY + $j, $white);
        imagesetpixel($out, $markX + $i, $markY + $markSize - $j, $white);
        imagesetpixel($out, $markX + $markSize - $i, $markY + $markSize - $j, $white);
    }
}
// Cyan "A" strokes
imagesetthickness($out, 3);
imageline($out, $markX + 20, $markY + 10, $markX + 10, $markY + 30, $sky);
imageline($out, $markX + 20, $markY + 10, $markX + 30, $markY + 30, $sky);
imageline($out, $markX + 14, $markY + 23, $markX + 26, $markY + 23, $sky);
imagesetthickness($out, 1);
imagettftext($out, 22, 0, $markX + $markSize + 12, $markY + 30, $slate, $fontBold, 'algos.');

imagettftext($out, 26, 0, $contentX, 100, $slate, $fontBold, 'Simple CRM.');
imagettftext($out, 18, 0, $contentX, 130, $accent, $fontBold, 'Smarter Customer Management.');
imagettftext($out, 12, 0, $contentX, 156, $muted, $fontReg, 'Leads, customers, tasks & pipeline — in one place.');

// CTAs stay in the upper clear band (above avatar)
$btnY = 172;
$btnW = 108;
$btnH = 32;
imagefilledrectangle($out, $contentX, $btnY, $contentX + $btnW, $btnY + $btnH, $accent);
imagettftext($out, 11, 0, $contentX + 26, $btnY + 21, $white, $fontBold, 'Visit Us');

$chipX = $contentX + $btnW + 10;
imagerectangle($out, $chipX, $btnY, $chipX + 122, $btnY + $btnH, $accent);
imagettftext($out, 10, 0, $chipX + 14, $btnY + 21, $accent, $fontReg, 'algoscrm.com');

// Product UI on the far right only
if (is_string($uiSrcPath) && is_file($uiSrcPath)) {
    $ui = @imagecreatefrompng($uiSrcPath);
    if ($ui) {
        $uw = imagesx($ui);
        $uh = imagesy($ui);
        $srcX = (int) ($uw * 0.48);
        $srcW = $uw - $srcX;
        $dstW = 760;
        $dstH = 320;
        $dstX = $W - $dstW - 28;
        $dstY = 38;
        imagecopyresampled($out, $ui, $dstX, $dstY, $srcX, (int) ($uh * 0.14), $dstW, $dstH, $srcW, (int) ($uh * 0.72));
        imagedestroy($ui);
    }
}

imagepng($out, $outPath, 6);
echo "saved {$outPath} {$W}x{$H}\n";
