<?php
// FR5.3 – xem/lọc đơn, duyệt chuyển khoản thủ công, đổi trạng thái theo máy trạng thái
require_once __DIR__ . '/khung.php';
dh_huy_qua_han($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['don'] ?? 0); $act = $_POST['action'] ?? '';
    $don = dh_lay($pdo, $id);
    if (!$don) ad_xong('don_hang.php', 'Không tìm thấy đơn.', 'loi');
    $dich = ['duyet' => 'DA_THANH_TOAN', 'tu_choi' => 'THAT_BAI', 'huy' => 'DA_HUY', 'thu_lai' => 'CHO_THANH_TOAN'][$act] ?? null;
    if (!$dich || !dh_cho_phep_chuyen($don['trang_thai'], $dich)) ad_xong('don_hang.php', 'Chuyển trạng thái không hợp lệ (đơn ' . TEN_TRANG_THAI[$don['trang_thai']] . ').', 'loi');
    if ($act === 'duyet' || $act === 'tu_choi') $r = dh_duyet_chuyen_khoan($pdo, $id, (int)$admin['ma_nguoi_dung'], $act === 'duyet');
    elseif ($act === 'huy') $r = dh_huy($pdo, $id, null, 'Quản trị viên hủy đơn');
    else { $pdo->prepare("UPDATE don_hang SET trang_thai = 'CHO_THANH_TOAN', han_thanh_toan = DATE_ADD(NOW(), INTERVAL " . HAN_THANH_TOAN_PHUT . " MINUTE) WHERE ma_don_hang = ? AND trang_thai = 'THAT_BAI'")->execute([$id]); $r = ['ok' => 'Đã mở lại đơn để khách thử thanh toán.']; }
    ad_xong('don_hang.php?' . http_build_query(array_intersect_key($_GET, ['tt' => 1, 'loai' => 1, 'q' => 1])), $r['ok'] ?? $r['loi'], isset($r['ok']) ? 'ok' : 'loi');
}
$tt = $_GET['tt'] ?? ''; $loai = $_GET['loai'] ?? ''; $q = trim($_GET['q'] ?? '');
$w = '1=1'; $p = [];
if (isset(TEN_TRANG_THAI[$tt])) { $w .= ' AND d.trang_thai = :tt'; $p[':tt'] = $tt; }
if (isset(TEN_LOAI_DON[$loai])) { $w .= ' AND d.loai_don = :lo'; $p[':lo'] = $loai; }
if ($q !== '') { $w .= ' AND (d.so_don_hang LIKE :q OR n.ten_dang_nhap LIKE :q OR n.ho_ten LIKE :q)'; $p[':q'] = "%$q%"; }
$trang = max(1, (int)($_GET['trang'] ?? 1));
$c = $pdo->prepare("SELECT COUNT(*) FROM don_hang d JOIN nguoi_dung n ON n.ma_nguoi_dung = d.ma_nguoi_dung WHERE $w"); $c->execute($p); $tong = (int)$c->fetchColumn();
$st = $pdo->prepare("SELECT d.*, n.ten_dang_nhap, n.ho_ten FROM don_hang d JOIN nguoi_dung n ON n.ma_nguoi_dung = d.ma_nguoi_dung WHERE $w ORDER BY d.ngay_tao DESC, d.ma_don_hang DESC LIMIT " . MOI_TRANG . ' OFFSET ' . (($trang - 1) * MOI_TRANG));
$st->execute($p);
ad_dau('Quản lý đơn hàng', 'don_hang');
?>
<form class="thanh" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Số đơn / người dùng"><select name="tt"><?= ad_opt(TEN_TRANG_THAI, $tt, true) ?></select><select name="loai"><?= ad_opt(TEN_LOAI_DON, $loai, true) ?></select><button class="btn nho">Lọc</button></form>
<div class="cuon"><table><tr><th>Số đơn</th><th>Khách</th><th>Loại</th><th>Số tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Tạo lúc</th><th>Thao tác</th></tr>
<?php foreach ($st as $d):
    $pays = $pdo->prepare('SELECT t.*, a.ten_dang_nhap AS nguoi_duyet FROM thanh_toan t LEFT JOIN nguoi_dung a ON a.ma_nguoi_dung = t.ma_nguoi_duyet WHERE t.ma_don_hang = ? ORDER BY t.ma_thanh_toan'); $pays->execute([$d['ma_don_hang']]); $pays = $pays->fetchAll();
    $cho_ck = (bool)array_filter($pays, fn($x) => $x['phuong_thuc'] === 'CHUYEN_KHOAN' && $x['trang_thai'] === 'CHO_XU_LY'); ?>
<tr><td><b><?= e($d['so_don_hang']) ?></b><br><small><?= e(implode('; ', array_column(dh_chi_tiet($pdo, (int)$d['ma_don_hang']), 'ten'))) ?></small></td>
  <td><?= e($d['ho_ten']) ?><br><small><?= e($d['ten_dang_nhap']) ?></small></td><td><?= e(TEN_LOAI_DON[$d['loai_don']]) ?></td>
  <td class="gia"><?= so(dh_so_tien($d)) ?> <?= dh_don_vi($d['loai_don']) === 'VND' ? '₫' : '🌰' ?></td>
  <td><?php foreach ($pays as $x): ?><small><?= e(TEN_PHUONG_THUC[$x['phuong_thuc']]) ?> · <?= e($x['trang_thai']) ?><?= $x['nguoi_duyet'] ? ' · duyệt bởi ' . e($x['nguoi_duyet']) : '' ?></small><br><?php endforeach; ?></td>
  <td><span class="tag <?= e($d['trang_thai']) ?>"><?= e(TEN_TRANG_THAI[$d['trang_thai']]) ?></span></td><td><?= e(date('d/m/Y H:i', strtotime($d['ngay_tao']))) ?></td>
  <td><form method="post"><?= csrf_field() ?><input type="hidden" name="don" value="<?= $d['ma_don_hang'] ?>">
    <?php if ($d['trang_thai'] === 'CHO_THANH_TOAN'): ?>
      <?php if ($cho_ck): ?><button class="btn nho" name="action" value="duyet" onclick="return confirm('Xác nhận đã nhận tiền và duyệt đơn?')">Duyệt CK</button><button class="btn nho xam" name="action" value="tu_choi">Từ chối</button><?php endif; ?>
      <button class="btn nho do" name="action" value="huy" onclick="return confirm('Hủy đơn?')">Hủy</button>
    <?php elseif ($d['trang_thai'] === 'THAT_BAI'): ?><button class="btn nho xam" name="action" value="thu_lai">Mở lại</button>
    <?php else: ?><small>Trạng thái cuối</small><?php endif; ?></form></td></tr>
<?php endforeach; ?></table></div>
<?= phan_trang($tong, $trang, MOI_TRANG, ['tt' => $tt, 'loai' => $loai, 'q' => $q]) ?>
<?php ad_cuoi();
