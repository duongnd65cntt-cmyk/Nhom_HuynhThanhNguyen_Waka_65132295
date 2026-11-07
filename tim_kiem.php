<?php
// FR2.4 – tìm kiếm có dấu/không dấu, không phân biệt hoa thường; không có kết quả → sách thịnh hành
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/sach.php';

$q = trim((string)($_GET['q'] ?? ''));
$kq = $q !== '' ? sach_loc($pdo, ['q' => $q, 'sap_xep' => $_GET['sap_xep'] ?? 'xem'], (int)($_GET['trang'] ?? 1)) : ['ds' => [], 'tong' => 0, 'trang' => 1];
trang_dau('Tìm kiếm');
?>
<h1>Kết quả tìm kiếm cho "<?= e($q) ?>" <small>(<?= so($kq['tong']) ?>)</small></h1>
<?php if ($kq['ds']): ?>
  <div class="luoi"><?php foreach ($kq['ds'] as $s) echo the_sach($s); ?></div>
  <?= phan_trang($kq['tong'], $kq['trang'], MOI_TRANG, ['q' => $q]) ?>
<?php else: ?>
  <p class="muted"><?= $q === '' ? 'Hãy nhập từ khóa.' : 'Không tìm thấy sách phù hợp. Có thể bạn quan tâm:' ?></p>
  <div class="luoi"><?php foreach (sach_thinh_hanh($pdo) as $s) echo the_sach($s); ?></div>
<?php endif; ?>
<?php trang_cuoi();
