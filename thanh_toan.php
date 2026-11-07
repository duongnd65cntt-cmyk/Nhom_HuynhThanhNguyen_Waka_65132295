<?php
// FR3.5 – chọn phương thức thanh toán cho một đơn: ví hạt sồi, chuyển khoản (QR), giả lập.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/don_hang.php';

$u = yeu_cau_dang_nhap();
$uid = (int)$u['ma_nguoi_dung'];
dh_huy_qua_han($pdo);
$id = (int)($_REQUEST['don'] ?? 0);
$don = dh_lay($pdo, $id, $uid);
if (!$don) { http_response_code(404); exit('Không tìm thấy đơn hàng.'); }

$loi = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'huy') {
        $r = dh_huy($pdo, $id, $uid, 'Người dùng hủy đơn');
        flash_dat($r['ok'] ?? $r['loi'], isset($r['ok']) ? 'ok' : 'loi');
        chuyen_huong('lich_su_don_hang.php');
    }
    $r = dh_thanh_toan($pdo, $id, $uid, (string)($_POST['phuong_thuc'] ?? ''), (string)($_POST['ket_qua'] ?? 'THANH_CONG'));
    if (isset($r['ok']) && $r['trang_thai'] === 'DA_THANH_TOAN') { flash_dat($r['ok']); chuyen_huong('lich_su_don_hang.php'); }
    if (isset($r['ok'])) flash_dat($r['ok']); else flash_dat($r['loi'], 'loi');
    chuyen_huong('thanh_toan.php?don=' . $id);
}
$don = dh_lay($pdo, $id, $uid);
$u = nguoi_dung_hien_tai();
$dong = dh_chi_tiet($pdo, $id);
$tien = dh_so_tien($don);
$dv = dh_don_vi($don['loai_don']);
$thanh_toan = $pdo->prepare('SELECT * FROM thanh_toan WHERE ma_don_hang = ? ORDER BY ma_thanh_toan DESC'); $thanh_toan->execute([$id]); $thanh_toan = $thanh_toan->fetchAll();
$cho_ck = (bool)array_filter($thanh_toan, fn($t) => $t['phuong_thuc'] === 'CHUYEN_KHOAN' && $t['trang_thai'] === 'CHO_XU_LY');
$tra_duoc = in_array($don['trang_thai'], ['CHO_THANH_TOAN', 'THAT_BAI'], true);

trang_dau('Thanh toán đơn ' . $don['so_don_hang'], ['main' => 'wrap hep']);
?>
<h1>Đơn <?= e($don['so_don_hang']) ?> <span class="tag <?= e($don['trang_thai']) ?>"><?= e(TEN_TRANG_THAI[$don['trang_thai']]) ?></span></h1>
<div class="panel">
  <?php foreach ($dong as $x): ?><div class="tong" style="font-size:15px;border-top:0;border-bottom:1px solid var(--line)"><span><?= e($x['ten']) ?></span><span><?= so($x['don_gia']) ?></span></div><?php endforeach; ?>
  <?php if ($don['tien_giam'] > 0): ?><div class="tong"><span>Giảm giá</span><span>-<?= so($don['tien_giam']) ?></span></div><?php endif; ?>
  <div class="tong"><b>Cần thanh toán</b><b class="gia"><?= so($tien) ?> <?= $dv ?></b></div>
  <?php if ($don['trang_thai'] === 'CHO_THANH_TOAN' && $don['han_thanh_toan']): ?><p class="goi-y">Hạn thanh toán: <?= e(date('H:i d/m/Y', strtotime($don['han_thanh_toan']))) ?></p><?php endif; ?>
</div>

<?php if ($tra_duoc): ?>
<div class="panel mt">
  <h3>Chọn phương thức thanh toán</h3>
  <?php if ($don['trang_thai'] === 'THAT_BAI'): ?><p class="canh-bao">Lần thanh toán trước thất bại. Chọn một phương thức để thử lại (gia hạn thêm <?= HAN_THANH_TOAN_PHUT ?> phút).</p><?php endif; ?>
  <?php if ($don['loai_don'] === 'MUA_NOI_DUNG'): ?>
  <form method="post" class="thanh"><?= csrf_field() ?><input type="hidden" name="phuong_thuc" value="VI_HAT_SOI">
    <span style="flex:1">🌰 <b>Ví hạt sồi</b> — số dư <?= so($u['so_du_hat_soi']) ?></span>
    <?php if ((int)$u['so_du_hat_soi'] >= $tien): ?><button class="btn nho">Thanh toán</button><?php else: ?><a class="btn nho xam" href="<?= e(url('nap_hat_soi.php')) ?>">Nạp thêm</a><?php endif; ?></form><hr style="border-color:var(--line)">
  <?php endif; ?>
  <div>
    <b>🏦 Chuyển khoản</b>
    <?php if ($cho_ck): ?>
      <div class="hang2" style="align-items:center;margin-top:10px">
        <img alt="QR" style="max-width:220px;background:#fff;border-radius:8px" src="https://img.vietqr.io/image/<?= e(NH_MA . '-' . NH_STK) ?>-compact2.png?amount=<?= $tien ?>&addInfo=<?= e(rawurlencode($don['so_don_hang'])) ?>&accountName=<?= e(rawurlencode(NH_TEN)) ?>">
        <div>Ngân hàng: <b><?= e(NH_MA) ?></b><br>STK: <b><?= e(NH_STK) ?></b> (<?= e(NH_TEN) ?>)<br>Số tiền: <b><?= so($tien) ?> <?= $dv ?></b><br>Nội dung: <b><?= e($don['so_don_hang']) ?></b>
          <p class="goi-y">Quản trị viên sẽ đối soát và xác nhận đơn sau khi nhận được tiền.</p></div>
      </div>
    <?php else: ?>
      <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="phuong_thuc" value="CHUYEN_KHOAN"><button class="btn nho xam" style="margin-left:10px">Chọn chuyển khoản &amp; xem QR</button></form>
    <?php endif; ?>
  </div><hr style="border-color:var(--line)">
  <form method="post" class="thanh"><?= csrf_field() ?><input type="hidden" name="phuong_thuc" value="GIA_LAP">
    <span style="flex:1">🧪 <b>Cổng thanh toán giả lập</b> (đồ án)</span>
    <button class="btn nho" name="ket_qua" value="THANH_CONG">Thành công</button><button class="btn nho do" name="ket_qua" value="THAT_BAI">Thất bại</button></form>
  <?php if ($don['trang_thai'] === 'CHO_THANH_TOAN'): ?>
  <form method="post" data-xacnhan="Hủy đơn hàng này?" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="action" value="huy"><button class="btn xam nho">Hủy đơn</button></form>
  <?php endif; ?>
</div>
<?php elseif ($don['trang_thai'] === 'DA_HUY'): ?><div class="canh-bao mt">Đơn đã bị hủy: <?= e($don['ly_do_huy']) ?>.</div>
<?php endif; ?>
<p class="phu"><a href="<?= e(url('lich_su_don_hang.php')) ?>">← Lịch sử đơn hàng</a></p>
<?php trang_cuoi();
