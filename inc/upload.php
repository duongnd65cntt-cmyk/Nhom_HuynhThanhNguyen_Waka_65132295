<?php
/** Upload an toàn: kiểm tra MIME thật bằng finfo, giới hạn dung lượng, đổi tên ngẫu nhiên (FR1.5, FR5.1). */
const MIME_ANH = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

/** @return array [đường dẫn tương đối|null, lỗi|null]. Không có file → [null, null]. */
function up_luu(?array $f, string $thu_muc, array $mime_ok, int $max, bool $la_anh = false): array {
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > $max) return [null, 'Tệp vượt quá ' . round($max / 1048576) . 'MB.'];
    if ($f['error'] !== UPLOAD_ERR_OK) return [null, 'Tải tệp lên thất bại.'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $duoi = $mime_ok[$mime] ?? null;
    if (!$duoi || ($la_anh && @getimagesize($f['tmp_name']) === false)) return [null, 'Định dạng tệp không được phép (' . implode('/', $mime_ok) . ').'];
    $dir = APP_ROOT . '/' . trim($thu_muc, '/');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return [null, 'Không tạo được thư mục lưu.'];
    $ten = bin2hex(random_bytes(10)) . '.' . $duoi;
    if (!move_uploaded_file($f['tmp_name'], "$dir/$ten")) return [null, 'Không lưu được tệp.'];
    return [trim($thu_muc, '/') . '/' . $ten, null];
}
function up_xoa(?string $rel): void {
    $rel = trim((string)$rel, '/');
    if ($rel !== '' && preg_match('#^uploads/(bia|audio|tac_gia)/[A-Za-z0-9_.\-]+$#', $rel) && is_file(APP_ROOT . '/' . $rel)) @unlink(APP_ROOT . '/' . $rel);
}
/** Nhiều ảnh truyện → thư mục uploads/truyen/<ngẫu nhiên>/001.ext… @return array [thư mục|null, lỗi|null] */
function up_truyen(array $files): array {
    $n = isset($files['name']) ? count(array_filter((array)$files['name'], fn($x) => $x !== '')) : 0;
    if ($n === 0) return [null, null];
    if ($n > 200) return [null, 'Tối đa 200 ảnh mỗi chương.'];
    $thu_muc = 'uploads/truyen/' . bin2hex(random_bytes(8));
    $ds = [];
    foreach ($files['name'] as $i => $ten) if ($ten !== '') $ds[] = ['ten' => $ten, 'f' => ['name' => $ten, 'tmp_name' => $files['tmp_name'][$i], 'size' => $files['size'][$i], 'error' => $files['error'][$i]]];
    usort($ds, fn($a, $b) => strnatcasecmp($a['ten'], $b['ten']));
    @mkdir(APP_ROOT . '/' . $thu_muc, 0755, true);
    foreach ($ds as $i => $x) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($x['f']['tmp_name']);
        $duoi = MIME_ANH[$mime] ?? null;
        if ($x['f']['error'] !== UPLOAD_ERR_OK || !$duoi || $x['f']['size'] > 5 * 1048576 || @getimagesize($x['f']['tmp_name']) === false) { up_xoa_thu_muc($thu_muc); return [null, 'Ảnh "' . $x['ten'] . '" không hợp lệ (JPG/PNG/WEBP, ≤ 5MB).']; }
        move_uploaded_file($x['f']['tmp_name'], sprintf('%s/%s/%03d.%s', APP_ROOT, $thu_muc, $i + 1, $duoi));
    }
    return [$thu_muc, null];
}
function up_xoa_thu_muc(?string $rel): void {
    $rel = trim((string)$rel, '/');
    if (!preg_match('#^uploads/truyen/[a-f0-9]{16}$#', $rel)) return;
    foreach (glob(APP_ROOT . "/$rel/*") ?: [] as $f) @unlink($f);
    @rmdir(APP_ROOT . "/$rel");
}
function slug(string $s): string { return trim(preg_replace('/[^a-z0-9]+/', '-', bo_dau($s)), '-') ?: 'muc'; }
