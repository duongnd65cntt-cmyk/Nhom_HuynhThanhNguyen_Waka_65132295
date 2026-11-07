<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/sach.php';

// FR2.1: 4 kệ sách, cache ngắn để giảm tải
$ke = cache_lay('trang_chu', CACHE_GIAY, fn() => [
    'Mới đăng' => ['moi', sach_ke($pdo, 'moi')],
    'Sách hội viên' => ['hoi_vien', sach_ke($pdo, 'hoi_vien')],
    'Đọc nhiều' => ['xem', sach_ke($pdo, 'xem')],
    'Điểm cao' => ['diem', sach_ke($pdo, 'diem')],
]);

trang_dau('Thế giới sách của bạn');
?>
<div class="banner"><img src="<?= e(url('HinhSach/banner.jpg')) ?>" alt="Waka"></div>
<?php foreach ($ke as $ten => [$loai, $ds]): if (!$ds) continue; ?>
  <div class="ke-tieu-de"><h2><?= e($ten) ?></h2>
    <a href="<?= e(url('danh_muc.php?' . http_build_query(['moi' => ['sap_xep' => 'moi'], 'hoi_vien' => ['hinh_thuc' => 'HOI_VIEN'], 'xem' => ['sap_xep' => 'xem'], 'diem' => ['sap_xep' => 'diem']][$loai]))) ?>">Xem tất cả ›</a></div>
  <div class="hang-ngang"><?php foreach ($ds as $i => $s) echo the_sach($s, $loai === 'diem' ? $i + 1 : null); ?></div>
<?php endforeach; ?>
<?php trang_cuoi();
