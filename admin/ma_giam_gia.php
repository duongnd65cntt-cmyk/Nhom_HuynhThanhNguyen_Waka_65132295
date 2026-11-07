<?php
// FR5.4 – CRUD mã giảm giá, bật/tắt, hạn và giới hạn lượt
require_once __DIR__ . '/khung.php';
$LG = ['GIAM_PHAN_TRAM' => 'Giảm %', 'GIAM_TIEN' => 'Giảm tiền', 'TANG_THEM' => 'Tặng thêm'];
$PV = ['TAT_CA' => 'Tất cả', 'MUA_NOI_DUNG' => 'Mua nội dung', 'NAP_HAT_SOI' => 'Nạp hạt sồi', 'MUA_VIP' => 'Mua VIP'];
$loi = [];
$id = (int)($_GET['sua'] ?? $_POST['ma_giam_gia'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'bat_tat') { $pdo->prepare('UPDATE ma_giam_gia SET trang_thai = 1 - trang_thai WHERE ma_giam_gia = ?')->execute([$id]); ad_xong('ma_giam_gia.php', 'Đã đổi trạng thái mã.'); }
    if ($act === 'xoa') {
        $c = $pdo->prepare('SELECT COUNT(*) FROM don_hang WHERE ma_giam_gia = ?'); $c->execute([$id]);
        if ((int)$c->fetchColumn() > 0) { $pdo->prepare('UPDATE ma_giam_gia SET trang_thai = 0 WHERE ma_giam_gia = ?')->execute([$id]); ad_xong('ma_giam_gia.php', 'Mã đã được dùng trong đơn nên chỉ được tắt.'); }
        $pdo->prepare('DELETE FROM ma_giam_gia WHERE ma_giam_gia = ?')->execute([$id]); ad_xong('ma_giam_gia.php', 'Đã xóa mã.');
    }
    $d = fn($k) => ($t = strtotime(str_replace('T', ' ', (string)($_POST[$k] ?? '')))) ? date('Y-m-d H:i:s', $t) : null;
    $v = ['ma_code' => strtoupper(trim($_POST['ma_code'] ?? '')), 'loai_giam' => $_POST['loai_giam'] ?? '', 'gia_tri' => (int)($_POST['gia_tri'] ?? 0),
          'loai_quyen_loi_tang' => ($_POST['loai_giam'] ?? '') === 'TANG_THEM' ? ($_POST['loai_quyen_loi_tang'] ?? '') : null,
          'muc_giam_toi_da' => ($_POST['muc_giam_toi_da'] ?? '') !== '' ? (int)$_POST['muc_giam_toi_da'] : null, 'gia_tri_don_toi_thieu' => (int)($_POST['gia_tri_don_toi_thieu'] ?? 0),
          'pham_vi_ap_dung' => $_POST['pham_vi_ap_dung'] ?? '', 'ngay_bat_dau' => $d('ngay_bat_dau'), 'ngay_het_han' => $d('ngay_het_han'),
          'gioi_han_toan_he' => ($_POST['gioi_han_toan_he'] ?? '') !== '' ? (int)$_POST['gioi_han_toan_he'] : null, 'gioi_han_moi_nguoi' => max(1, (int)($_POST['gioi_han_moi_nguoi'] ?? 1)), 'trang_thai' => isset($_POST['trang_thai']) ? 1 : 0];
    if (!preg_match('/^[A-Z0-9_]{3,30}$/', $v['ma_code'])) $loi[] = 'Mã gồm 3–30 ký tự chữ/số/gạch dưới.';
    if (!isset($LG[$v['loai_giam']])) $loi[] = 'Loại giảm không hợp lệ.';
    if ($v['gia_tri'] <= 0) $loi[] = 'Giá trị phải > 0.';
    if ($v['loai_giam'] === 'GIAM_PHAN_TRAM' && $v['gia_tri'] > 100) $loi[] = 'Giảm % tối đa 100.';
    if ($v['loai_giam'] === 'TANG_THEM' && !in_array($v['loai_quyen_loi_tang'], ['HAT_SOI', 'NGAY_VIP'], true)) $loi[] = 'Chọn quyền lợi tặng thêm (hạt sồi / ngày VIP).';
    if ($v['loai_giam'] === 'TANG_THEM' && $v['loai_quyen_loi_tang'] === 'NGAY_VIP' && !in_array($v['pham_vi_ap_dung'], ['MUA_VIP', 'TAT_CA'], true)) $loi[] = 'Mã tặng ngày VIP chỉ áp dụng cho Mua VIP.';
    if (!isset($PV[$v['pham_vi_ap_dung']])) $loi[] = 'Phạm vi không hợp lệ.';
    if (!$v['ngay_bat_dau'] || !$v['ngay_het_han'] || $v['ngay_het_han'] <= $v['ngay_bat_dau']) $loi[] = 'Thời gian hiệu lực không hợp lệ.';
    if (!$loi) {
        try {
            if ($id) $pdo->prepare('UPDATE ma_giam_gia SET ma_code=?, loai_giam=?, gia_tri=?, loai_quyen_loi_tang=?, muc_giam_toi_da=?, gia_tri_don_toi_thieu=?, pham_vi_ap_dung=?, ngay_bat_dau=?, ngay_het_han=?, gioi_han_toan_he=?, gioi_han_moi_nguoi=?, trang_thai=? WHERE ma_giam_gia=?')->execute([...array_values($v), $id]);
            else $pdo->prepare('INSERT INTO ma_giam_gia (ma_code, loai_giam, gia_tri, loai_quyen_loi_tang, muc_giam_toi_da, gia_tri_don_toi_thieu, pham_vi_ap_dung, ngay_bat_dau, ngay_het_han, gioi_han_toan_he, gioi_han_moi_nguoi, trang_thai) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute(array_values($v));
            ad_xong('ma_giam_gia.php', 'Đã lưu mã giảm giá.');
        } catch (PDOException $ex) { if ($ex->getCode() === '23000') $loi[] = 'Mã đã tồn tại.'; else throw $ex; }
    }
}
ad_dau('Mã giảm giá', 'ma_giam_gia');
if (isset($_GET['moi']) || $id || $loi):
    $b = ['ma_code' => '', 'loai_giam' => 'GIAM_PHAN_TRAM', 'gia_tri' => '', 'loai_quyen_loi_tang' => '', 'muc_giam_toi_da' => '', 'gia_tri_don_toi_thieu' => 0, 'pham_vi_ap_dung' => 'TAT_CA', 'ngay_bat_dau' => date('Y-m-d 00:00:00'), 'ngay_het_han' => date('Y-m-d 23:59:00', strtotime('+30 days')), 'gioi_han_toan_he' => '', 'gioi_han_moi_nguoi' => 1, 'trang_thai' => 1];
    if ($id) { $q = $pdo->prepare('SELECT * FROM ma_giam_gia WHERE ma_giam_gia = ?'); $q->execute([$id]); $b = $q->fetch() ?: $b; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') $b = array_merge($b, $_POST, ['trang_thai' => isset($_POST['trang_thai']) ? 1 : 0]);
    $dt = fn($s) => $s ? str_replace(' ', 'T', substr(str_replace('T', ' ', (string)$s), 0, 16)) : '';
    hien_loi($loi); ?>
  <form method="post" class="panel"><?= csrf_field() ?><input type="hidden" name="ma_giam_gia" value="<?= $id ?>">
    <div class="hang2"><div><label>Mã</label><input type="text" name="ma_code" value="<?= e($b['ma_code']) ?>" required style="text-transform:uppercase"></div>
      <div><label>Loại</label><select name="loai_giam"><?= ad_opt($LG, $b['loai_giam']) ?></select></div>
      <div><label>Giá trị (% / tiền / hạt sồi / ngày)</label><input type="number" name="gia_tri" min="1" value="<?= e($b['gia_tri']) ?>"></div>
      <div><label>Nếu “Tặng thêm”</label><select name="loai_quyen_loi_tang"><option value="">—</option><?= ad_opt(['HAT_SOI' => 'Hạt sồi', 'NGAY_VIP' => 'Ngày VIP'], $b['loai_quyen_loi_tang']) ?></select></div></div>
    <div class="hang2"><div><label>Giảm tối đa (với %)</label><input type="number" name="muc_giam_toi_da" min="0" value="<?= e($b['muc_giam_toi_da']) ?>"></div>
      <div><label>Giá trị đơn tối thiểu</label><input type="number" name="gia_tri_don_toi_thieu" min="0" value="<?= e($b['gia_tri_don_toi_thieu']) ?>"></div>
      <div><label>Phạm vi</label><select name="pham_vi_ap_dung"><?= ad_opt($PV, $b['pham_vi_ap_dung']) ?></select></div></div>
    <div class="hang2"><div><label>Bắt đầu</label><input type="datetime-local" name="ngay_bat_dau" value="<?= e($dt($b['ngay_bat_dau'])) ?>"></div><div><label>Hết hạn</label><input type="datetime-local" name="ngay_het_han" value="<?= e($dt($b['ngay_het_han'])) ?>"></div>
      <div><label>Giới hạn toàn hệ thống (trống = vô hạn)</label><input type="number" name="gioi_han_toan_he" min="0" value="<?= e($b['gioi_han_toan_he']) ?>"></div><div><label>Giới hạn mỗi người</label><input type="number" name="gioi_han_moi_nguoi" min="1" value="<?= e($b['gioi_han_moi_nguoi']) ?>"></div></div>
    <label><input type="checkbox" name="trang_thai" value="1" <?= $b['trang_thai'] ? 'checked' : '' ?>> Đang bật</label><button class="btn">Lưu</button></form>
<?php else: $ds = $pdo->query('SELECT * FROM ma_giam_gia ORDER BY ma_giam_gia DESC')->fetchAll(); ?>
  <p><a class="btn nho" href="?moi=1">+ Thêm mã</a></p>
  <div class="cuon"><table><tr><th>Mã</th><th>Ưu đãi</th><th>Phạm vi</th><th>Hiệu lực</th><th>Lượt dùng</th><th>Trạng thái</th><th></th></tr>
  <?php foreach ($ds as $m): $het = strtotime($m['ngay_het_han']) < time(); ?>
  <tr><td><b><?= e($m['ma_code']) ?></b></td><td><?= e($LG[$m['loai_giam']]) ?>: <?= so($m['gia_tri']) ?><?= $m['loai_giam'] === 'GIAM_PHAN_TRAM' ? '%' : ($m['loai_quyen_loi_tang'] === 'HAT_SOI' ? ' hạt sồi' : ($m['loai_quyen_loi_tang'] === 'NGAY_VIP' ? ' ngày VIP' : ' ₫/🌰')) ?></td><td><?= e($PV[$m['pham_vi_ap_dung']]) ?></td>
    <td><small><?= e(date('d/m/Y', strtotime($m['ngay_bat_dau']))) ?> → <?= e(date('d/m/Y', strtotime($m['ngay_het_han']))) ?></small></td><td><?= $m['so_luot_da_dung'] ?>/<?= $m['gioi_han_toan_he'] ?? '∞' ?></td>
    <td><span class="tag <?= $m['trang_thai'] && !$het ? 'HOAT_DONG' : 'BI_KHOA' ?>"><?= !$m['trang_thai'] ? 'Tắt' : ($het ? 'Hết hạn' : 'Bật') ?></span></td>
    <td><a class="btn nho xam" href="?sua=<?= $m['ma_giam_gia'] ?>">Sửa</a><form method="post"><?= csrf_field() ?><input type="hidden" name="ma_giam_gia" value="<?= $m['ma_giam_gia'] ?>"><button class="btn nho xam" name="action" value="bat_tat">Bật/Tắt</button>
      <button class="btn nho do" name="action" value="xoa" onclick="return confirm('Xóa mã?')">Xóa</button></form></td></tr><?php endforeach; ?></table></div>
<?php endif; ad_cuoi();
