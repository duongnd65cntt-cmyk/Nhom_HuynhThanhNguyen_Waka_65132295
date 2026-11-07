<?php
// FR3.3 / FR3.4 – xem lại đơn, áp mã giảm giá, tạo đơn CHO_THANH_TOAN.
//   ?loai=MUA_NOI_DUNG (từ giỏ) | ?loai=NAP_HAT_SOI&goi=<VND> | ?loai=MUA_VIP&goi=<ma_goi>
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/don_hang.php';

$u = yeu_cau_dang_nhap();
$uid = (int)$u['ma_nguoi_dung'];
dh_huy_qua_han($pdo);
$loai = (string)($_REQUEST['loai'] ?? '');
$goi = (int)($_REQUEST['goi'] ?? 0);
$d = dh_dung_dong($pdo, $uid, $loai, $goi);
$khoa_ma = "ma_$loai";
$loi = []; $ok = null;

if (!isset($d['loi']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'ap_ma') {
        $code = strtoupper(trim((string)($_POST['ma_code'] ?? '')));
        if ($code === '') { unset($_SESSION[$khoa_ma]); $ok = 'Đã bỏ mã giảm giá.'; }
        else {
            $r = dh_kiem_tra_ma($pdo, $uid, $code, $loai, $d['tong']);
            if (isset($r['loi'])) { unset($_SESSION[$khoa_ma]); $loi[] = $r['loi']; } else { $_SESSION[$khoa_ma] = $code; $ok = 'Áp dụng mã thành công.'; }
        }
    } elseif ($act === 'tao_don') {
        $r = dh_tao($pdo, $uid, $loai, $goi, (string)($_SESSION[$khoa_ma] ?? ''));
        if (isset($r['loi'])) { $loi[] = $r['loi']; unset($_SESSION[$khoa_ma]); }
        else { unset($_SESSION[$khoa_ma]); chuyen_huong('thanh_toan.php?don=' . $r['ma_don']); }
    }
}
$ma = null;
if (!isset($d['loi']) && !empty($_SESSION[$khoa_ma])) {
    $ma = dh_kiem_tra_ma($pdo, $uid, $_SESSION[$khoa_ma], $loai, $d['tong']);
    if (isset($ma['loi'])) { $loi[] = $ma['loi']; unset($_SESSION[$khoa_ma]); $ma = null; }
}
$dv = dh_don_vi($loai);
trang_dau('Đặt hàng', ['main' => 'wrap hep']);
?>
<h1>Xác nhận đơn hàng</h1>
<?php hien_loi($loi); if ($ok) echo '<div class="ok">' . e($ok) . '</div>'; ?>
<?php if (isset($d['loi'])): ?>
  <div class="panel"><p><?= e($d['loi']) ?></p><a class="btn" href="<?= e(url('gio_hang.php')) ?>">Về giỏ hàng</a></div>
<?php else: ?>
<div class="panel">
  <p class="muted"><?= e(TEN_LOAI_DON[$loai]) ?></p>
  <?php foreach ($d['dong'] as $x): ?><div class="tong" style="font-size:15px;border-top:0;border-bottom:1px solid var(--line)"><span><?= e($x['ten']) ?></span><span class="gia"><?= so($x['don_gia']) ?> <?= $dv === 'VND' ? '₫' : '🌰' ?></span></div><?php endforeach; ?>
  <form method="post" class="thanh" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="loai" value="<?= e($loai) ?>"><input type="hidden" name="goi" value="<?= $goi ?>"><input type="hidden" name="action" value="ap_ma">
    <input type="text" name="ma_code" value="<?= e($_SESSION[$khoa_ma] ?? '') ?>" placeholder="Mã giảm giá" style="flex:1"><button class="btn xam">Áp dụng</button></form>
  <div class="tong"><span>Tạm tính</span><span><?= so($d['tong']) ?> <?= $dv ?></span></div>
  <?php if ($ma && $ma['giam'] > 0): ?><div class="tong"><span>Giảm giá (<?= e($ma['ma']['ma_code']) ?>)</span><span>-<?= so($ma['giam']) ?> <?= $dv ?></span></div><?php endif; ?>
  <?php if ($ma && $ma['hat_tang'] > 0): ?><div class="tong"><span>Tặng thêm</span><span>+<?= so($ma['hat_tang']) ?> hạt sồi</span></div><?php endif; ?>
  <?php if ($ma && $ma['ngay_tang'] > 0): ?><div class="tong"><span>Tặng thêm</span><span>+<?= so($ma['ngay_tang']) ?> ngày VIP</span></div><?php endif; ?>
  <div class="tong"><b>Tổng thanh toán</b><b class="gia"><?= so($d['tong'] - ($ma['giam'] ?? 0)) ?> <?= $dv ?></b></div>
  <p class="goi-y">Đơn được giữ <?= HAN_THANH_TOAN_PHUT ?> phút để thanh toán, sau đó tự hủy.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="loai" value="<?= e($loai) ?>"><input type="hidden" name="goi" value="<?= $goi ?>"><input type="hidden" name="action" value="tao_don"><button class="btn">Tạo đơn &amp; chọn phương thức thanh toán ›</button></form>
</div>
<?php endif; ?>
<?php trang_cuoi();
