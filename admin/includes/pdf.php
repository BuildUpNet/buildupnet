<?php
// ==========================================================
// Buildupnet Admin — tiny PDF writer (no libraries, no Composer).
// Does only what the hiring letters need: A4 pages with a JPEG letterhead behind the text, Helvetica text, wrapping, page breaks.
// Text is Windows-1252 (English letters). Characters outside it (Gurmukhi, Hindi, emoji) print as "?".
// ==========================================================
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'pdf.php') { http_response_code(403); exit; }

class MiniPdf {
    const W = 595.28;               // A4 in points
    const H = 841.89;
    private array $pages = [];      // finished content streams
    private string $cur = '';
    private ?array $img = null;
    private array $widths;
    private string $font = 'R';
    private float $size = 10;
    public float $y = 0;            // current baseline position measured from the TOP of the page (points)
    public float $top, $bottom;     // usable text area (points from top)
    private string $title;

    public function __construct(string $letterheadJpg, float $topMm, float $bottomMm, string $title = 'Letter') {
        $this->widths = require __DIR__ . '/pdf-fonts.php';
        $this->top = $topMm * 72 / 25.4; $this->bottom = $bottomMm * 72 / 25.4;
        $this->title = $title;
        $info = @getimagesize($letterheadJpg);
        if (!$info || $info[2] !== IMAGETYPE_JPEG) throw new RuntimeException('Letterhead image is missing or is not a JPEG.');
        $this->img = ['w' => $info[0], 'h' => $info[1], 'ch' => $info['channels'] ?? 3, 'data' => (string)file_get_contents($letterheadJpg)];
        $this->addPage();
    }

    public static function mm(float $mm): float { return $mm * 72 / 25.4; }

    public function addPage(): void {
        if ($this->cur !== '') $this->pages[] = $this->cur;
        $this->cur = sprintf("q %.2F 0 0 %.2F 0 0 cm /Im1 Do Q\n", self::W, self::H);
        $this->y = $this->top;
    }

    /** If less than $h points are left on this page, start a new one. */
    public function ensure(float $h): void { if ($this->y + $h > $this->bottom) $this->addPage(); }

    public function font(string $style, float $size): void { $this->font = isset($this->widths[$style]) ? $style : 'R'; $this->size = $size; }

    private function enc(string $s): string {
        $s = str_replace(['₹', "\r"], ['Rs. ', ''], $s);
        $s = str_replace(['“', '”', '‘', '’'], ['"', '"', "'", "'"], $s);
        return (string)mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    }

    public function width(string $s): float {
        $w = 0; $t = $this->widths[$this->font];
        foreach (str_split($this->enc($s)) as $ch) $w += $t[ord($ch)] ?? 500;
        return $w * $this->size / 1000;
    }

    /** Draw one line of text. $x in points from left, $y baseline from top. $align: L, R or C (inside $boxW). */
    public function text(float $x, float $y, string $s, string $align = 'L', float $boxW = 0): void {
        if ($align !== 'L') { $w = $this->width($s); $x += $align === 'R' ? $boxW - $w : ($boxW - $w) / 2; }
        $e = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->enc($s));
        $this->cur .= sprintf("BT /F%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $this->font, $this->size, $x, self::H - $y, $e);
    }

    public function colour(float $r, float $g, float $b): void { $this->cur .= sprintf("%.3F %.3F %.3F rg %.3F %.3F %.3F RG\n", $r, $g, $b, $r, $g, $b); }

    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.6): void {
        $this->cur .= sprintf("%.2F w %.2F %.2F m %.2F %.2F l S\n", $width, $x1, self::H - $y1, $x2, self::H - $y2);
    }

    /** Break text into lines that fit $maxW points with the current font. */
    public function wrap(string $s, float $maxW): array {
        $out = [];
        foreach (preg_split('/\R/u', $s) as $para) {
            $line = '';
            foreach (preg_split('/\s+/u', trim($para)) as $word) {
                if ($word === '') continue;
                $try = $line === '' ? $word : $line . ' ' . $word;
                if ($this->width($try) <= $maxW) { $line = $try; continue; }
                if ($line !== '') $out[] = $line;
                while ($this->width($word) > $maxW && mb_strlen($word) > 1) {      // a single very long word: cut it
                    $cut = mb_strlen($word);
                    while ($cut > 1 && $this->width(mb_substr($word, 0, $cut)) > $maxW) $cut--;
                    $out[] = mb_substr($word, 0, $cut); $word = mb_substr($word, $cut);
                }
                $line = $word;
            }
            $out[] = $line;
        }
        return $out;
    }

    /** Wrapped paragraph at the current position; flows onto the next page when needed. Returns nothing, moves $this->y. */
    public function para(string $s, float $x, float $maxW, float $lineH, string $align = 'L'): void {
        foreach ($this->wrap($s, $maxW) as $ln) {
            $this->ensure($lineH);
            $this->y += $lineH;
            if ($ln !== '') $this->text($x, $this->y - ($lineH - $this->size) / 2 - 1, $ln, $align, $maxW);
        }
    }

    public function output(): string {
        if ($this->cur !== '') { $this->pages[] = $this->cur; $this->cur = ''; }
        $n = count($this->pages);
        $objs = [];                                    // 1-based object bodies
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = []; for ($i = 0; $i < $n; $i++) $kids[] = (7 + $i * 2) . ' 0 R';
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        foreach (['R' => 'Helvetica', 'B' => 'Helvetica-Bold', 'I' => 'Helvetica-Oblique'] as $k => $base) {
            $objs[$k === 'R' ? 3 : ($k === 'B' ? 4 : 5)] = '<< /Type /Font /Subtype /Type1 /BaseFont /' . $base . ' /Encoding /WinAnsiEncoding >>';
        }
        $cs = $this->img['ch'] === 1 ? '/DeviceGray' : '/DeviceRGB';
        $objs[6] = ['<< /Type /XObject /Subtype /Image /Width ' . $this->img['w'] . ' /Height ' . $this->img['h'] . ' /ColorSpace ' . $cs
                    . ' /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($this->img['data']) . ' >>', $this->img['data']];
        $res = '<< /Font << /FR 3 0 R /FB 4 0 R /FI 5 0 R >> /XObject << /Im1 6 0 R >> >>';
        foreach ($this->pages as $i => $stream) {
            $po = 7 + $i * 2; $co = $po + 1;
            $objs[$po] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources ' . $res . ' /Contents ' . $co . ' 0 R >>';
            $flate = function_exists('gzcompress');
            $data = $flate ? gzcompress($stream, 6) : $stream;
            $objs[$co] = ['<< /Length ' . strlen($data) . ($flate ? ' /Filter /FlateDecode' : '') . ' >>', $data];
        }
        $infoNo = 7 + $n * 2;
        $objs[$infoNo] = '<< /Title (' . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->enc($this->title)) . ') /Producer (Buildupnet) /CreationDate (D:' . date('YmdHis') . ') >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $off = [];
        ksort($objs);
        foreach ($objs as $no => $body) {
            $off[$no] = strlen($pdf);
            if (is_array($body)) $pdf .= $no . " 0 obj\n" . $body[0] . "\nstream\n" . $body[1] . "\nendstream\nendobj\n";
            else $pdf .= $no . " 0 obj\n" . $body . "\nendobj\n";
        }
        $x = strlen($pdf);
        $pdf .= "xref\n0 " . ($infoNo + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $infoNo; $i++) $pdf .= sprintf("%010d 00000 n \n", $off[$i]);
        return $pdf . "trailer\n<< /Size " . ($infoNo + 1) . " /Root 1 0 R /Info " . $infoNo . " 0 R >>\nstartxref\n" . $x . "\n%%EOF";
    }
}
