<?php
// FR3.7 – lịch sử đơn hàng, lọc theo trạng thái, hủy đơn chờ thanh toán
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/don_hang.php';

$u = yeu_cau_dang_nhap();
$uid = (int)$u['ma_nguoi_dung'];
dh_huy_qua_han($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $r = dh_huy($pdo, (int)($_POST['don'] ?? 0), $uid, 'Người dùng hủy đơn');
    flash_dat($r['ok'] ?? $r['loi'], isset($r['ok']) ? 'ok' : 'loi');
    chuyen_huong('lich_su_don_hang.php');
}
$tt = (string)($_GET['tt'] ?? '');
$sql = 'SELECT * FROM don_hang WHERE ma_nguoi_dung = ?' . (isset(TEN_TRANG_THAI[$tt]) ? ' AND trang_thai = ' . $pdo->quote($tt) : '') . ' ORDER BY ngay_tao DESC, ma_don_hang DESC';
$st = $pdo->prepare($sql); $st->execute([$uid]);
$ds = $st->fetchAll();
trang_dau('Đơn hàng');
?>
<h1>Lịch sử đơn hàng</h1>
<div class="tab-bar"><a class="<?= $tt === '' ? 'on' : '' ?>" href="?">Tất cả</a>
<?php foreach (TEN_TRANG_THAI as $k => $t): ?><a class="<?= $tt === $k ? 'on' : '' ?>" href="?tt=<?= $k ?>"><?= e($t) ?></a><?php endforeach; ?></div>
<div class="cuon"><table>
<tr><th>Số đơn</th><th>Loại</th><th>Sản phẩm</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr>
<?php foreach ($ds as $d): $ct = dh_chi_tiet($pdo, (int)$d['ma_don_hang']); ?>
<tr>
  <td><b><?= e($d['so_don_hang']) ?></b></td>
  <td><?= e(TEN_LOAI_DON[$d['loai_don']]) ?></td>
  <td><?= e(implode('; ', array_column($ct, 'ten'))) ?></td>
  <td class="gia"><?= so(dh_so_tien($d)) ?> <?= dh_don_vi($d['loai_don']) === 'VND' ? '₫' : '🌰' ?></td>
  <td><span class="tag <?= e($d['trang_thai']) ?>"><?= e(TEN_TRANG_THAI[$d['trang_thai']]) ?></span><?= $d['ly_do_huy'] ? '<br><small>' . e($d['ly_do_huy']) . '</small>' : '' ?></td>
  <td><?= e(date('d/m/Y H:i', strtotime($d['ngay_tao']))) ?></td>
  <td><?php if (in_array($d['trang_thai'], ['CHO_THANH_TOAN', 'THAT_BAI'], true)): ?><a class="btn nho" href="<?= e(url('thanh_toan.php?don=' . $d['ma_don_hang'])) ?>"><?= $d['trang_thai'] === 'THAT_BAI' ? 'Thử lại' : 'Thanh toán' ?></a><?php endif; ?>
    <?php if ($d['trang_thai'] === 'CHO_THANH_TOAN'): ?><form method="post" data-xacnhan="Hủy đơn này?"><?= csrf_field() ?><input type="hidden" name="don" value="<?= (int)$d['ma_don_hang'] ?>"><button class="btn nho xam">Hủy</button></form><?php endif; ?></td>
</tr>
<?php endforeach; if (!$ds): ?><tr><td colspan="7" class="muted">Chưa có đơn hàng.</td></tr><?php endif; ?>
</table></div>
<?php trang_cuoi();
