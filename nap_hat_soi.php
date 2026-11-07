<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/don_hang.php';
$u = yeu_cau_dang_nhap();
trang_dau('Nạp hạt sồi');
?>
<h1>Nạp hạt sồi</h1>
<div class="vip-trang-thai">Số dư hiện tại: <b class="gia"><?= so($u['so_du_hat_soi']) ?> hạt sồi</b> · Hạt sồi dùng để mua sách/chương lẻ.</div>
<div class="mat-the">
<?php foreach (GOI_NAP as $vnd => $hat): ?>
  <div class="card"><h3><?= so($hat) ?> hạt sồi</h3><p class="gia"><?= so($vnd) ?> ₫</p>
    <a class="btn" href="<?= e(url('dat_hang.php?loai=NAP_HAT_SOI&goi=' . $vnd)) ?>">Nạp ngay</a></div>
<?php endforeach; ?>
</div>
<p class="phu">Muốn đọc không giới hạn sách hội viên? <a href="<?= e(url('hoi_vien.php')) ?>">Xem gói WakaVIP</a></p>
<?php trang_cuoi();
