<?php

namespace App\Support;

/**
 * Image-free captcha output for environments without the PHP GD extension.
 * Renders inline SVG (served as image/svg+xml).
 */
final class SimpleSvgCaptcha
{
    public function __construct(private readonly string $phrase)
    {
    }

    public function getPhrase(): string
    {
        return $this->phrase;
    }

    public function output(): void
    {
        $w = 100;
        $h = 40;
        $chars = str_split($this->phrase);
        $n = max(1, count($chars));
        $step = (int) (($w - 16) / $n);

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'">';
        echo '<rect width="100%" height="100%" fill="#dcd2e6"/>';

        for ($i = 0; $i < 5; $i++) {
            echo '<line x1="'.random_int(0, $w).'" y1="'.random_int(0, $h).'" x2="'.random_int(0, $w).'" y2="'.random_int(0, $h).'" stroke="#aaa" stroke-width="1" opacity="0.5"/>';
        }

        $x = 10;
        foreach ($chars as $ch) {
            $esc = htmlspecialchars($ch, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $rot = random_int(-20, 20);
            $y = random_int(24, 32);
            $cx = $x + 8;
            $cy = $y - 10;
            echo '<text x="'.$x.'" y="'.$y.'" transform="rotate('.$rot.' '.$cx.' '.$cy.')" font-family="Consolas, DejaVu Sans Mono, monospace" font-size="18" font-weight="600" fill="#1a1a1a">'.$esc.'</text>';
            $x += $step;
        }

        echo '</svg>';
    }
}
