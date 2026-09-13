<?php

$src = imagecreatefrompng($argv[1]);
$sw = imagesx($src);
$sh = imagesy($src);
echo "source: {$sw}x{$sh}\n";

$W = 1584;
$H = 396;

$scale = max($W / $sw, $H / $sh);
$rw = (int) round($sw * $scale);
$rh = (int) round($sh * $scale);
$scaled = imagecreatetruecolor($rw, $rh);
imagecopyresampled($scaled, $src, 0, 0, 0, 0, $rw, $rh, $sw, $sh);

$left = (int) floor(($rw - $W) / 2);
$top = (int) max(0, min($rh - $H, (int) floor(($rh - $H) * 0.05)));

$out = imagecreatetruecolor($W, $H);
imagecopy($out, $scaled, 0, 0, $left, $top, $W, $H);

// Strong soft wash in LinkedIn company-logo overlap zone (bottom-left).
$safeW = 360;
$safeH = 280;
for ($y = $H - $safeH; $y < $H; $y++) {
    for ($x = 0; $x < $safeW; $x++) {
        $cx = 140;
        $cy = $H + 10;
        $nx = ($x - $cx) / 170;
        $ny = ($y - $cy) / 150;
        $d = sqrt($nx * $nx + $ny * $ny);
        if ($d > 1.45) {
            continue;
        }
        $t = max(0.0, min(1.0, (1.45 - $d) / 1.45));
        $t = pow($t, 1.35) * 0.92;
        $rgb = imagecolorat($out, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $tr = (int) round($r * (1 - $t) + 0xF3 * $t);
        $tg = (int) round($g * (1 - $t) + 0xF7 * $t);
        $tb = (int) round($b * (1 - $t) + 0xFE * $t);
        imagesetpixel($out, $x, $y, imagecolorallocate($out, $tr, $tg, $tb));
    }
}

imagepng($out, $argv[2], 6);
echo 'saved '.$argv[2].' '.imagesx($out).'x'.imagesy($out)." (crop y={$top})\n";
