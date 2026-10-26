<?php
// ==========================================================
// Buildupnet Admin — Helper Functions
// Place at: /admin/includes/functions.php
// ==========================================================

function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

/** A small POST form that looks like a button (used for delete / toggle / move). Needs a CSRF token. */
function postButton(string $action, array $fields, string $label, string $class = 'abtn abtn-outline abtn-sm', string $confirm = '', string $title = ''): string {
    $h = '<form method="POST" action="' . htmlspecialchars($action, ENT_QUOTES) . '" style="display:inline;margin:0;">' . csrfField();
    foreach ($fields as $k => $v) {
        $h .= '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES) . '" value="' . htmlspecialchars((string)$v, ENT_QUOTES) . '">';
    }
    $h .= '<button type="submit" class="' . htmlspecialchars($class, ENT_QUOTES) . '"'
        . ($confirm !== '' ? ' data-confirm="' . htmlspecialchars($confirm, ENT_QUOTES) . '"' : '')
        . ($title !== '' ? ' title="' . htmlspecialchars($title, ENT_QUOTES) . '"' : '')
        . ' style="cursor:pointer;">' . $label . '</button></form>';
    return $h;
}

/** Delete a project image, but only inside assets/img/projects (no path tricks). */
function deleteProjectImage(?string $rel): void {
    if (!$rel || !preg_match('#^assets/img/projects/img_[A-Za-z0-9._-]+$#', $rel)) return;
    $path = __DIR__ . '/../../' . $rel;
    if (is_file($path)) @unlink($path);
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

function flashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $flash['type'] = $flash['type'] === 'success' ? 'success' : 'error';
    $color = $flash['type'] === 'success' ? '#2D7A52' : '#c0392b';
    $bg    = $flash['type'] === 'success' ? '#E8F5EE' : '#FDECEA';
    echo '<div class="flash flash-' . $flash['type'] . '" style="background:' . $bg . ';border-left:4px solid ' . $color . ';color:' . $color . ';">'
        . clean($flash['message']) . '</div>';
}

function uploadImage(array $file, string $folder): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) return null;
    if ($file['size'] > 8 * 1024 * 1024) return null; // 8MB max upload

    // Check the REAL file type (not the browser-sent one)
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extByMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extByMime[$mime]) || @getimagesize($file['tmp_name']) === false) return null;

    if (!is_dir($folder)) mkdir($folder, 0755, true);
    $base = 'img_' . bin2hex(random_bytes(10));
    $dir  = rtrim($folder, '/') . '/';

    // Speed = SEO: re-encode as WebP, max 1400px wide (a 2 MB screenshot becomes ~100 KB). Falls back to the original if GD is missing.
    if (function_exists('imagewebp')) {
        $img = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
        if ($img) {
            $w = imagesx($img); $h = imagesy($img);
            if ($w > 1400) { $img = imagescale($img, 1400, (int)round($h * 1400 / $w)); }
            imagepalettetotruecolor($img); imagealphablending($img, true); imagesavealpha($img, true);
            $ok = imagewebp($img, $dir . $base . '.webp', 82);
            imagedestroy($img);
            if ($ok) return $base . '.webp';
        }
    }
    $filename = $base . '.' . $extByMime[$mime];
    return move_uploaded_file($file['tmp_name'], $dir . $filename) ? $filename : null;
}

function uploadFile(array $file, string $folder): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) return null;
    if ($file['size'] > 10 * 1024 * 1024) return null; // 10MB max

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ok = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/x-ole-storage', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];
    if (!isset($ok[$ext]) || !in_array($mime, $ok[$ext], true)) return null;

    if (!is_dir($folder)) mkdir($folder, 0755, true);
    $filename = 'resume_' . bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], rtrim($folder, '/') . '/' . $filename)) return null;
    return $filename;
}
