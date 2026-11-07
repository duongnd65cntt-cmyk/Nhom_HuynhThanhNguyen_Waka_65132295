<?php
/** Khung chung cho khu vực quản trị (FR1.6 middleware + điều hướng). */
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../layout.php';
require_once __DIR__ . '/../inc/sach.php';
require_once __DIR__ . '/../inc/don_hang.php';
require_once __DIR__ . '/../inc/upload.php';
$admin = middleware_quan_tri();

function ad_dau(string $tieu_de, string $tab): void {
    $menu = ['index' => 'Thống kê', 'sach' => 'Sách', 'the_loai' => 'Thể loại', 'tac_gia' => 'Tác giả', 'don_hang' => 'Đơn hàng', 'ma_giam_gia' => 'Mã giảm giá', 'binh_luan' => 'Bình luận', 'nguoi_dung' => 'Người dùng'];
    trang_dau('Quản trị – ' . $tieu_de, ['main' => 'ad', 'flash' => false]);
    echo '<aside class="ad-menu">';
    foreach ($menu as $k => $t) echo '<a class="' . ($k === $tab ? 'on' : '') . '" href="' . e(url("admin/$k.php")) . '">' . e($t) . '</a>';
    echo '</aside><div class="ad-nd"><h1>' . e($tieu_de) . '</h1>';
    hien_flash();
}
function ad_cuoi(): void { echo '</div>'; trang_cuoi(); }
/** Redirect + flash tiện dụng. */
function ad_xong(string $url, string $msg, string $loai = 'ok'): never { flash_dat($msg, $loai); chuyen_huong($url); }
function ad_opt(array $ds, $cur, bool $trong = false): string {
    $h = $trong ? '<option value="">— Tất cả —</option>' : '';
    foreach ($ds as $k => $t) $h .= '<option value="' . e($k) . '"' . ((string)$cur === (string)$k ? ' selected' : '') . '>' . e($t) . '</option>';
    return $h;
}
