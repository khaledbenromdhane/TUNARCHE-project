<?php

namespace App\Service;

/**
 * Analyses artwork images using PHP's GD extension.
 *
 * Returns an array with:
 *  - dominant_colors        : string[]  (up to 5 hex codes)
 *  - brightness             : string    (Dark | Medium | Bright)
 *  - saturation             : string    (Muted | Moderate | Vivid)
 *  - style_tags             : string[]  (e.g. Minimalist, Dramatic, Vibrant …)
 *  - visual_elements        : string[]  (e.g. High Contrast, Warm Tones, Cool Tones …)
 *  - generated_description  : string    (auto-generated artistic description paragraph)
 *  - composition            : array
 *      - visual_weight   : string  (Left-heavy | Centered | Right-heavy | Balanced)
 *      - edge_complexity : string  (Minimal | Moderate | Busy)
 *      - subject_position: string  (Central | Rule of Thirds | Distributed)
 */
class ImageAnalysisService
{
    // Number of pixels to sample per axis (sample grid = SAMPLE x SAMPLE)
    private const SAMPLE = 30;

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    public function analyze(string $filePath): array
    {
        $image = $this->loadImage($filePath);
        if ($image === null) {
            return $this->emptyResult();
        }

        [$width, $height] = [imagesx($image), imagesy($image)];

        // Build a flat array of sampled pixel data (RGB + HSV)
        $pixels = $this->samplePixels($image, $width, $height);

        imagedestroy($image);

        // --- Metrics ---
        $avgR = array_sum(array_column($pixels, 'r')) / count($pixels);
        $avgG = array_sum(array_column($pixels, 'g')) / count($pixels);
        $avgB = array_sum(array_column($pixels, 'b')) / count($pixels);

        $avgLuma      = $this->luma($avgR, $avgG, $avgB);
        $avgSaturation = array_sum(array_column($pixels, 's')) / count($pixels);

        // Dominant colours
        $dominantColors = $this->dominantColors($pixels);

        // Brightness / saturation categories
        $brightness  = $this->categorizeBrightness($avgLuma);
        $saturation  = $this->categorizeSaturation($avgSaturation);

        // Style tags & visual elements
        $warmth     = $avgR - $avgB;           // positive = warm, negative = cool
        $contrast   = $this->contrastLevel($pixels);
        $styleTags  = $this->inferStyleTags($brightness, $saturation, $warmth, $contrast);
        $visualElems = $this->inferVisualElements($brightness, $warmth, $contrast, $avgSaturation);

        // Composition (3×3 grid analysis)
        $composition = $this->analyzeComposition($pixels, self::SAMPLE);

        // Description
        $description = $this->generateDescription(
            $brightness, $saturation, $warmth, $contrast,
            $styleTags, $visualElems, $composition
        );

        return [
            'dominant_colors'       => $dominantColors,
            'brightness'            => $brightness,
            'saturation'            => $saturation,
            'style_tags'            => $styleTags,
            'visual_elements'       => $visualElems,
            'generated_description' => $description,
            'composition'           => $composition,
        ];
    }

    // -----------------------------------------------------------------------
    // Image loading
    // -----------------------------------------------------------------------

    private function loadImage(string $path): ?\GdImage
    {
        if (!file_exists($path) || !extension_loaded('gd')) {
            return null;
        }

        $mime = mime_content_type($path);
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/gif'  => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => null,
        } ?: null;
    }

    // -----------------------------------------------------------------------
    // Pixel sampling — returns array of ['r','g','b','h','s','v','col','row']
    // -----------------------------------------------------------------------

    private function samplePixels(\GdImage $image, int $width, int $height): array
    {
        $pixels = [];
        $n      = self::SAMPLE;

        for ($row = 0; $row < $n; $row++) {
            for ($col = 0; $col < $n; $col++) {
                $x   = (int) ($col / $n * $width);
                $y   = (int) ($row / $n * $height);
                $rgb = imagecolorat($image, min($x, $width - 1), min($y, $height - 1));

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8)  & 0xFF;
                $b = ($rgb)       & 0xFF;

                [$h, $s, $v] = $this->rgbToHsv($r, $g, $b);

                $pixels[] = [
                    'r'   => $r,
                    'g'   => $g,
                    'b'   => $b,
                    'h'   => $h,
                    's'   => $s,
                    'v'   => $v,
                    'col' => $col,   // 0 … SAMPLE-1
                    'row' => $row,
                ];
            }
        }

        return $pixels;
    }

    // -----------------------------------------------------------------------
    // Dominant colours — simple kMeans-like quantisation (5 buckets)
    // -----------------------------------------------------------------------

    private function dominantColors(array $pixels, int $k = 5): array
    {
        // Reduce each pixel to a coarse 6-bit colour (2 bits per channel)
        $buckets = [];
        foreach ($pixels as $p) {
            $key = (($p['r'] >> 5) << 4) | (($p['g'] >> 5) << 2) | ($p['b'] >> 5);
            if (!isset($buckets[$key])) {
                $buckets[$key] = ['count' => 0, 'r' => 0, 'g' => 0, 'b' => 0];
            }
            $buckets[$key]['count']++;
            $buckets[$key]['r'] += $p['r'];
            $buckets[$key]['g'] += $p['g'];
            $buckets[$key]['b'] += $p['b'];
        }

        // Sort by count descending
        usort($buckets, fn($a, $b) => $b['count'] <=> $a['count']);

        $colors = [];
        foreach (array_slice($buckets, 0, $k) as $bucket) {
            $cnt      = $bucket['count'];
            $r        = (int) ($bucket['r'] / $cnt);
            $g        = (int) ($bucket['g'] / $cnt);
            $b        = (int) ($bucket['b'] / $cnt);
            $colors[] = sprintf('#%02x%02x%02x', $r, $g, $b);
        }

        return $colors;
    }

    // -----------------------------------------------------------------------
    // Brightness / saturation helpers
    // -----------------------------------------------------------------------

    private function luma(float $r, float $g, float $b): float
    {
        return 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }

    private function categorizeBrightness(float $luma): string
    {
        if ($luma < 80)  return 'Dark';
        if ($luma < 170) return 'Medium';
        return 'Bright';
    }

    private function categorizeSaturation(float $s): string
    {
        if ($s < 0.25) return 'Muted';
        if ($s < 0.55) return 'Moderate';
        return 'Vivid';
    }

    private function contrastLevel(array $pixels): float
    {
        $lumas = array_map(fn($p) => $this->luma($p['r'], $p['g'], $p['b']), $pixels);
        $min   = min($lumas);
        $max   = max($lumas);
        return ($max - $min) / 255.0;   // 0–1
    }

    // -----------------------------------------------------------------------
    // Style tags & visual elements
    // -----------------------------------------------------------------------

    private function inferStyleTags(
        string $brightness,
        string $saturation,
        float  $warmth,
        float  $contrast
    ): array {
        $tags = [];

        // Minimalist: bright + muted
        if ($brightness === 'Bright' && $saturation === 'Muted') {
            $tags[] = 'Minimalist';
        }

        // Monochromatic: very low saturation globally
        if ($saturation === 'Muted') {
            $tags[] = 'Monochromatic';
        }

        // Dramatic: dark + high contrast
        if ($brightness === 'Dark' && $contrast > 0.45) {
            $tags[] = 'Dramatic';
        }

        // Impressionist: vivid + warm
        if ($saturation === 'Vivid' && $warmth > 20) {
            $tags[] = 'Impressionist';
        }

        // Vibrant: vivid (any temperature)
        if ($saturation === 'Vivid') {
            $tags[] = 'Vibrant';
        }

        // Abstract: medium brightness + high contrast + vivid
        if ($brightness === 'Medium' && $contrast > 0.5 && $saturation === 'Vivid') {
            $tags[] = 'Abstract';
        }

        // Realist: moderate saturation, medium brightness, lower contrast
        if ($saturation === 'Moderate' && $brightness === 'Medium' && $contrast < 0.4) {
            $tags[] = 'Realist';
        }

        // Surrealist: dark + vivid
        if ($brightness === 'Dark' && $saturation === 'Vivid') {
            $tags[] = 'Surrealist';
        }

        return $tags ?: ['Contemporary'];
    }

    private function inferVisualElements(
        string $brightness,
        float  $warmth,
        float  $contrast,
        float  $saturation
    ): array {
        $elems = [];

        if ($contrast > 0.5)  $elems[] = 'High Contrast';
        if ($contrast < 0.2)  $elems[] = 'Low Contrast';
        if ($warmth > 15)     $elems[] = 'Warm Tones';
        if ($warmth < -15)    $elems[] = 'Cool Tones';
        if ($brightness === 'Dark')   $elems[] = 'Deep Shadows';
        if ($brightness === 'Bright') $elems[] = 'Luminous Highlights';
        if ($saturation > 0.7)        $elems[] = 'Rich Color Palette';
        if ($saturation < 0.15)       $elems[] = 'Desaturated / Grayscale';

        return $elems ?: ['Balanced Palette'];
    }

    // -----------------------------------------------------------------------
    // Artistic description generation
    // -----------------------------------------------------------------------

    public function generateDescription(
        string $brightness,
        string $saturation,
        float  $warmth,
        float  $contrast,
        array  $styleTags,
        array  $visualElems,
        array  $composition
    ): string {
        // --- Mood opening ---
        $moodPhrases = match (true) {
            $brightness === 'Dark' && $contrast > 0.45  => [
                'A striking work of dramatic intensity,',
                'A bold and moody composition,',
                'This arresting piece commands attention,',
            ],
            $brightness === 'Dark'                       => [
                'Shrouded in deep, atmospheric shadows,',
                'A quietly powerful work imbued with darkness,',
                'Rich with depth and mystery,',
            ],
            $brightness === 'Bright' && $saturation === 'Muted' => [
                'Serene and restrained in its luminosity,',
                'A clean, light-filled composition,',
                'Minimal yet radiant in its simplicity,',
            ],
            $brightness === 'Bright'                     => [
                'Radiating light and energy,',
                'Vibrant and sun-drenched,',
                'Filled with luminous brilliance,',
            ],
            default                                      => [
                'A nuanced and carefully balanced work,',
                'Thoughtful in tone and execution,',
                'Poised between light and shadow,',
            ],
        };

        // --- Colour / palette sentence ---
        $tone = $warmth > 15 ? 'warm' : ($warmth < -15 ? 'cool' : 'balanced');
        $satLabel = strtolower($saturation);
        $palettePhrases = [
            "warm"     => "The palette leans into {$satLabel} warm tones — golds, ambers, and earthy reds that invite the eye inward.",
            "cool"     => "Cool hues dominate the palette — blues, teals, and silvers lending a calm, introspective atmosphere.",
            "balanced" => "The palette is balanced and harmonious, weaving together {$satLabel} tones without a single dominant temperature.",
        ];

        // --- Composition sentence ---
        $weight   = $composition['visual_weight']   ?? 'Balanced';
        $subject  = $composition['subject_position'] ?? 'Distributed';
        $complex  = $composition['edge_complexity']  ?? 'Moderate';

        $compSentences = [
            'Central'        => 'The subject anchors the composition at its heart, drawing the viewer into a single focal point.',
            'Rule of Thirds' => 'The artist leverages the classic rule of thirds, placing key elements at the natural tension points of the frame.',
            'Distributed'    => 'Visual interest is distributed across the frame, encouraging the eye to explore freely.',
        ];
        $weightSentences = [
            'Left-heavy'  => ' Visual weight pulls gently to the left, creating a sense of flow and directional momentum.',
            'Right-heavy' => ' The composition anchors to the right, with a sense of deliberate tension and counterbalance.',
            'Centered'    => ' A strong central axis grounds the work with symmetry and stability.',
            'Balanced'    => ' The composition achieves a graceful equilibrium across the frame.',
        ];
        $complexSentences = [
            'Minimal'  => ' Fine detail is intentionally restrained, allowing the broader forms to breathe.',
            'Moderate' => ' Detail and negative space coexist in pleasing proportion.',
            'Busy'     => ' An abundance of fine detail rewards close inspection, every corner alive with texture and line.',
        ];

        $compositionSentence = ($compSentences[$subject] ?? '')
            . ($weightSentences[$weight] ?? '')
            . ($complexSentences[$complex] ?? '');

        // --- Style sentence ---
        $primaryTag = $styleTags[0] ?? 'Contemporary';
        $styleDescriptions = [
            'Minimalist'    => 'In the tradition of minimalist art, the work distils its subject to essentials, finding power in what is left unsaid.',
            'Monochromatic' => 'The monochromatic treatment strips away colour distraction, turning subtle tonal gradations into the language of the piece.',
            'Dramatic'      => 'Executed with dramatic flair, this piece evokes the chiaroscuro mastery of the Old Masters — light and shadow locked in expressive dialogue.',
            'Impressionist' => 'Inspired by the Impressionist tradition, the work captures a fleeting moment, colour and light dissolving into pure sensation.',
            'Vibrant'       => 'Colour is the true subject here — vivid, uncompromising, and emotionally direct.',
            'Surrealist'    => 'The image inhabits a liminal space between the real and the imagined, its dark palette amplifying an air of dreamlike tension.',
            'Abstract'      => 'Moving beyond representation, this abstract work trusts shape, colour, and rhythm to carry the full weight of meaning.',
            'Realist'       => 'Grounded in careful observation, this realist composition renders its subject with quiet fidelity to the visible world.',
            'Contemporary'  => 'A contemporary work that speaks in the visual language of its time — self-aware, considered, and open to interpretation.',
        ];
        $styleSentence = $styleDescriptions[$primaryTag] ?? $styleDescriptions['Contemporary'];

        // --- Visual elements closer ---
        $elemClosers = [
            'High Contrast'        => 'The dramatic tonal range electrifies the composition.',
            'Low Contrast'         => 'Soft tonal transitions lend the work an air of quiet contemplation.',
            'Warm Tones'           => 'Warm hues suffuse the piece with a feeling of intimacy and life.',
            'Cool Tones'           => 'Cool tones create a meditative, almost otherworldly stillness.',
            'Deep Shadows'         => 'Deep shadows anchor the composition in weight and mystery.',
            'Luminous Highlights'  => 'Luminous highlights lift the piece into a register of clarity and openness.',
            'Rich Color Palette'   => 'A richly saturated palette makes this work impossible to overlook.',
            'Desaturated / Grayscale' => 'The absence of colour turns every tonal nuance into a deliberate act of expression.',
            'Balanced Palette'     => 'A balanced palette underscores the work\'s considered, unified vision.',
        ];
        $closer = '';
        foreach ($visualElems as $elem) {
            if (isset($elemClosers[$elem])) {
                $closer = $elemClosers[$elem];
                break;
            }
        }

        // --- Assemble ---
        $opening   = $moodPhrases[array_rand($moodPhrases)];
        $palette   = $palettePhrases[$tone];

        return implode(' ', array_filter([
            $opening,
            $palette,
            $compositionSentence,
            $styleSentence,
            $closer,
        ]));
    }

    // -----------------------------------------------------------------------
    // Composition analysis — 3×3 grid over the sample array
    // -----------------------------------------------------------------------

    /**
     * Divides the SAMPLE×SAMPLE pixel grid into a 3×3 block grid.
     * For each of the 9 cells it computes average luma and edge density.
     */
    private function analyzeComposition(array $pixels, int $n): array
    {

        // Build a 2D luma map
        $luma = array_fill(0, $n, array_fill(0, $n, 0.0));
        foreach ($pixels as $p) {
            $luma[$p['row']][$p['col']] = $this->luma($p['r'], $p['g'], $p['b']);
        }

        // Edge density (simplified Sobel): sum |grad| per pixel
        $edgeMap = array_fill(0, $n, array_fill(0, $n, 0.0));
        for ($row = 1; $row < $n - 1; $row++) {
            for ($col = 1; $col < $n - 1; $col++) {
                $gx = $luma[$row - 1][$col + 1] - $luma[$row - 1][$col - 1]
                    + 2 * $luma[$row][$col + 1] - 2 * $luma[$row][$col - 1]
                    + $luma[$row + 1][$col + 1] - $luma[$row + 1][$col - 1];
                $gy = $luma[$row - 1][$col - 1] - $luma[$row + 1][$col - 1]
                    + 2 * $luma[$row - 1][$col] - 2 * $luma[$row + 1][$col]
                    + $luma[$row - 1][$col + 1] - $luma[$row + 1][$col + 1];
                $edgeMap[$row][$col] = sqrt($gx * $gx + $gy * $gy);
            }
        }

        // 3×3 block grid: each block covers n/3 × n/3 pixels
        $blockSize = (int) ceil($n / 3);
        $cell = [];
        for ($br = 0; $br < 3; $br++) {
            for ($bc = 0; $bc < 3; $bc++) {
                $lumaSum = 0.0;
                $edgeSum = 0.0;
                $count   = 0;
                for ($r = $br * $blockSize; $r < min(($br + 1) * $blockSize, $n); $r++) {
                    for ($c = $bc * $blockSize; $c < min(($bc + 1) * $blockSize, $n); $c++) {
                        $lumaSum += $luma[$r][$c];
                        $edgeSum += $edgeMap[$r][$c];
                        $count++;
                    }
                }
                $cell[$br][$bc] = [
                    'luma' => $count > 0 ? $lumaSum / $count : 0,
                    'edge' => $count > 0 ? $edgeSum / $count : 0,
                ];
            }
        }

        // --- Visual weight (horizontal balance) ---
        $leftLuma   = ($cell[0][0]['luma'] + $cell[1][0]['luma'] + $cell[2][0]['luma']) / 3;
        $centerLuma = ($cell[0][1]['luma'] + $cell[1][1]['luma'] + $cell[2][1]['luma']) / 3;
        $rightLuma  = ($cell[0][2]['luma'] + $cell[1][2]['luma'] + $cell[2][2]['luma']) / 3;

        $maxSide     = max($leftLuma, $rightLuma);
        $minSide     = min($leftLuma, $rightLuma);
        $sideBalance = $maxSide > 0 ? ($maxSide - $minSide) / $maxSide : 0;

        if ($sideBalance < 0.12) {
            $visualWeight = 'Balanced';
        } elseif ($leftLuma > $rightLuma) {
            $visualWeight = 'Left-heavy';
        } else {
            $visualWeight = 'Right-heavy';
        }

        // Override: very dominant centre column
        if ($centerLuma > $leftLuma * 1.2 && $centerLuma > $rightLuma * 1.2) {
            $visualWeight = 'Centered';
        }

        // --- Edge complexity (whole image average) ---
        $allEdgeVals = array_merge(...array_map(
            fn($row) => array_column($row, 'edge'),
            $cell
        ));
        $avgEdge = array_sum($allEdgeVals) / count($allEdgeVals);

        // Normalise (max possible Sobel ≈ 1440 for 8-bit luma 0–255)
        $edgeNorm = $avgEdge / 1440.0;
        if ($edgeNorm < 0.04)      $edgeComplexity = 'Minimal';
        elseif ($edgeNorm < 0.12)  $edgeComplexity = 'Moderate';
        else                       $edgeComplexity = 'Busy';

        // --- Subject position ---
        // Rule-of-thirds intersection cells: (0,1),(0,2),(1,0),(2,0) overlap corners
        // Simplified: compare centre cell vs surrounding average
        $surroundLuma = (
            $cell[0][0]['luma'] + $cell[0][1]['luma'] + $cell[0][2]['luma'] +
            $cell[1][0]['luma'] +                       $cell[1][2]['luma'] +
            $cell[2][0]['luma'] + $cell[2][1]['luma'] + $cell[2][2]['luma']
        ) / 8;

        $centreCellLuma = $cell[1][1]['luma'];

        // Rule-of-thirds: high activity at off-centre cells (corners)
        $thirdsLuma = (
            $cell[0][1]['luma'] + $cell[0][2]['luma'] +
            $cell[2][0]['luma'] + $cell[2][1]['luma']
        ) / 4;

        if ($centreCellLuma > $surroundLuma * 1.25) {
            $subjectPosition = 'Central';
        } elseif ($thirdsLuma > $centreCellLuma * 1.1) {
            $subjectPosition = 'Rule of Thirds';
        } else {
            $subjectPosition = 'Distributed';
        }

        return [
            'visual_weight'    => $visualWeight,
            'edge_complexity'  => $edgeComplexity,
            'subject_position' => $subjectPosition,
        ];
    }

    // -----------------------------------------------------------------------
    // RGB → HSV conversion
    // -----------------------------------------------------------------------

    private function rgbToHsv(int $r, int $g, int $b): array
    {
        $r /= 255.0;
        $g /= 255.0;
        $b /= 255.0;

        $max   = max($r, $g, $b);
        $min   = min($r, $g, $b);
        $delta = $max - $min;

        $v = $max;
        $s = $max > 0 ? $delta / $max : 0;

        if ($delta === 0.0) {
            $h = 0;
        } elseif ($max === $r) {
            $h = 60 * fmod(($g - $b) / $delta, 6);
        } elseif ($max === $g) {
            $h = 60 * (($b - $r) / $delta + 2);
        } else {
            $h = 60 * (($r - $g) / $delta + 4);
        }

        if ($h < 0) $h += 360;

        return [(int) round($h), round($s, 4), round($v, 4)];
    }

    // -----------------------------------------------------------------------
    // Fallback
    // -----------------------------------------------------------------------

    private function emptyResult(): array
    {
        return [
            'dominant_colors'       => [],
            'brightness'            => 'Unknown',
            'saturation'            => 'Unknown',
            'style_tags'            => [],
            'visual_elements'       => [],
            'generated_description' => '',
            'composition'           => [
                'visual_weight'    => 'Unknown',
                'edge_complexity'  => 'Unknown',
                'subject_position' => 'Unknown',
            ],
        ];
    }
}
