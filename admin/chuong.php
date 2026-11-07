<?php
// FR5.1 – CRUD chương: nội dung chữ / file âm thanh / thư mục ảnh truyện tranh
require_once __DIR__ . '/khung.php';

$ma_sach = (int)($_GET['sach'] ?? $_POST['ma_sach'] ?? 0);
$st = $pdo->prepare('SELECT ma_sach, ten_sach, dinh_dang FROM sach WHERE ma_sach = ?'); $st->execute([$ma_sach]);
$sach = $st->fetch();
if (!$sach) ad_xong('sach.php', 'Không tìm thấy sách.', 'loi');
$CD = ['MIEN_PHI' => 'Miễn phí', 'HOI_VIEN' => 'Hội viên', 'TRA_PHI' => 'Trả phí'];
$loi = [];
$id = (int)($_GET['sua'] ?? $_POST['ma_chuong'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    $cu = null;
    if ($id) { $q = $pdo->prepare('SELECT * FROM chuong WHERE ma_chuong = ? AND ma_sach = ?'); $q->execute([$id, $ma_sach]); $cu = $q->fetch(); if (!$cu) ad_xong("chuong.php?sach=$ma_sach", 'Chương không tồn tại.', 'loi'); }
    if ($act === 'xoa') {
        $c = $pdo->prepare('SELECT (SELECT COUNT(*) FROM chi_tiet_don_hang WHERE ma_chuong = ?) + (SELECT COUNT(*) FROM gio_hang WHERE ma_chuong = ?)'); $c->execute([$id, $id]);
        if ((int)$c->fetchColumn() > 0) { $pdo->prepare('UPDATE chuong SET trang_thai = 0 WHERE ma_chuong = ?')->execute([$id]); ad_xong("chuong.php?sach=$ma_sach", 'Chương đã có giao dịch nên được ẩn (không xóa cứng).'); }
        $pdo->prepare('DELETE FROM chuong WHERE ma_chuong = ?')->execute([$id]);
        up_xoa($cu['tep_noi_dung']); up_xoa_thu_muc($cu['tep_noi_dung']);
        ad_xong("chuong.php?sach=$ma_sach", 'Đã xóa chương.');
    }
    $v = ['so_thu_tu' => (int)($_POST['so_thu_tu'] ?? 0), 'tieu_de' => trim($_POST['tieu_de'] ?? ''), 'che_do_truy_cap' => $_POST['che_do_truy_cap'] ?? '',
          'gia_hat_soi' => (int)($_POST['gia_hat_soi'] ?? 0), 'trang_thai' => isset($_POST['trang_thai']) ? 1 : 0];
    if ($v['so_thu_tu'] < 1) $loi[] = 'Số thứ tự phải ≥ 1.';
    if ($v['tieu_de'] === '' || mb_strlen($v['tieu_de']) > 255) $loi[] = 'Tiêu đề bắt buộc (≤ 255 ký tự).';
    if (!isset($CD[$v['che_do_truy_cap']])) $loi[] = 'Chế độ truy cập không hợp lệ.';
    if ($v['che_do_truy_cap'] === 'TRA_PHI' && $v['gia_hat_soi'] <= 0) $loi[] = 'Chương trả phí phải có giá > 0.';
    if ($v['che_do_truy_cap'] !== 'TRA_PHI') $v['gia_hat_soi'] = 0;
    $noi_dung = $cu['noi_dung'] ?? null; $tep = $cu['tep_noi_dung'] ?? null; $tep_cu_xoa = null;
    if ($sach['dinh_dang'] === 'SACH_DIEN_TU') {
        $noi_dung = (string)($_POST['noi_dung'] ?? '');
        $f = $_FILES['tep_txt'] ?? null;
        if ($f && $f['error'] === UPLOAD_ERR_OK) {
            if ($f['size'] > 2 * 1048576 || (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) !== 'text/plain') $loi[] = 'Tệp .txt tối đa 2MB, định dạng văn bản thuần.';
            else $noi_dung = (string)file_get_contents($f['tmp_name']);
        }
        if (trim($noi_dung) === '' && !$loi) $loi[] = 'Cần nhập nội dung chương.';
    } elseif ($sach['dinh_dang'] === 'SACH_NOI') {
        [$p, $e1] = up_luu($_FILES['tep_audio'] ?? null, 'uploads/audio', ['audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav'], 60 * 1048576);
        if ($e1) $loi[] = $e1; elseif ($p) { $tep_cu_xoa = $tep; $tep = $p; }
        if (!$tep && !$loi) $loi[] = 'Cần tải tệp âm thanh.';
    } else {
        [$p, $e1] = up_truyen($_FILES['anh_truyen'] ?? []);
        if ($e1) $loi[] = $e1; elseif ($p) { $tep_cu_xoa = $tep; $tep = $p; }
        if (!$tep && !$loi) $loi[] = 'Cần tải ảnh các trang truyện.';
    }
    if (!$loi) {
        try {
            if ($id) $pdo->prepare('UPDATE chuong SET so_thu_tu=?, tieu_de=?, che_do_truy_cap=?, gia_hat_soi=?, trang_thai=?, noi_dung=?, tep_noi_dung=? WHERE ma_chuong=?')->execute([...array_values($v), $noi_dung, $tep, $id]);
            else $pdo->prepare('INSERT INTO chuong (so_thu_tu, tieu_de, che_do_truy_cap, gia_hat_soi, trang_thai, noi_dung, tep_noi_dung, ma_sach) VALUES (?,?,?,?,?,?,?,?)')->execute([...array_values($v), $noi_dung, $tep, $ma_sach]);
            if ($tep_cu_xoa) { up_xoa($tep_cu_xoa); up_xoa_thu_muc($tep_cu_xoa); }
            $pdo->prepare('UPDATE sach SET ngay_cap_nhat = NOW() WHERE ma_sach = ?')->execute([$ma_sach]);
            ad_xong("chuong.php?sach=$ma_sach", 'Đã lưu chương.');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') $loi[] = 'Số thứ tự đã tồn tại trong sách này.'; else throw $ex;
        }
    }
}
ad_dau('Chương – ' . $sach['ten_sach'], 'sach');
echo '<p><a href="sach.php">← Danh sách sách</a></p>';
if (isset($_GET['moi']) || $id || $loi) {
    $b = ['so_thu_tu' => (int)$pdo->query('SELECT COALESCE(MAX(so_thu_tu),0)+1 FROM chuong WHERE ma_sach = ' . $ma_sach)->fetchColumn(), 'tieu_de' => '', 'che_do_truy_cap' => 'MIEN_PHI', 'gia_hat_soi' => 0, 'trang_thai' => 1, 'noi_dung' => '', 'tep_noi_dung' => ''];
    if ($id) { $q = $pdo->prepare('SELECT * FROM chuong WHERE ma_chuong = ? AND ma_sach = ?'); $q->execute([$id, $ma_sach]); $b = $q->fetch() ?: $b; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') $b = array_merge($b, $_POST, ['trang_thai' => isset($_POST['trang_thai']) ? 1 : 0]);
    hien_loi($loi); ?>
  <form method="post" enctype="multipart/form-data" class="panel"><?= csrf_field() ?><input type="hidden" name="ma_sach" value="<?= $ma_sach ?>"><input type="hidden" name="ma_chuong" value="<?= $id ?>">
    <div class="hang2"><div style="flex:0 0 120px"><label>Thứ tự</label><input type="number" name="so_thu_tu" min="1" value="<?= e($b['so_thu_tu']) ?>"></div><div style="flex:3"><label>Tiêu đề</label><input type="text" name="tieu_de" value="<?= e($b['tieu_de']) ?>" maxlength="255" required></div></div>
    <div class="hang2"><div><label>Chế độ truy cập</label><select name="che_do_truy_cap"><?= ad_opt($CD, $b['che_do_truy_cap']) ?></select></div><div><label>Giá (hạt sồi, nếu trả phí)</label><input type="number" name="gia_hat_soi" min="0" value="<?= e($b['gia_hat_soi']) ?>"></div></div>
    <label><input type="checkbox" name="trang_thai" value="1" <?= $b['trang_thai'] ? 'checked' : '' ?>> Hiển thị</label>
    <?php if ($sach['dinh_dang'] === 'SACH_DIEN_TU'): ?><label>Nội dung</label><textarea name="noi_dung" style="min-height:260px"><?= e($b['noi_dung']) ?></textarea><label>…hoặc nạp từ tệp .txt</label><input type="file" name="tep_txt" accept=".txt,text/plain">
    <?php elseif ($sach['dinh_dang'] === 'SACH_NOI'): ?><label>Tệp âm thanh (mp3/m4a/ogg/wav ≤ 60MB)<?= $b['tep_noi_dung'] ? ' – hiện có: ' . e($b['tep_noi_dung']) : '' ?></label><input type="file" name="tep_audio" accept="audio/*">
    <?php else: ?><label>Ảnh các trang (chọn nhiều, tên file quyết định thứ tự)<?= $b['tep_noi_dung'] ? ' – hiện có thư mục ' . e($b['tep_noi_dung']) . ' (tải mới sẽ thay thế)' : '' ?></label><input type="file" name="anh_truyen[]" multiple accept="image/jpeg,image/png,image/webp"><?php endif; ?>
    <button class="btn">Lưu chương</button></form>
<?php } else {
    $ds = $pdo->prepare('SELECT * FROM chuong WHERE ma_sach = ? ORDER BY so_thu_tu'); $ds->execute([$ma_sach]); ?>
  <p><a class="btn nho" href="?sach=<?= $ma_sach ?>&moi=1">+ Thêm chương</a></p>
  <div class="cuon"><table><tr><th>#</th><th>Tiêu đề</th><th>Truy cập</th><th>Giá</th><th>Hiển thị</th><th></th></tr>
  <?php foreach ($ds as $c): ?><tr><td><?= $c['so_thu_tu'] ?></td><td><?= e($c['tieu_de']) ?></td><td><?= e($CD[$c['che_do_truy_cap']]) ?></td><td><?= $c['gia_hat_soi'] ? so($c['gia_hat_soi']) . ' 🌰' : '—' ?></td><td><?= $c['trang_thai'] ? '✓' : '✗' ?></td>
    <td><a class="btn nho xam" href="?sach=<?= $ma_sach ?>&sua=<?= $c['ma_chuong'] ?>">Sửa</a>
    <form method="post" data-xacnhan="Xóa chương này?"><?= csrf_field() ?><input type="hidden" name="action" value="xoa"><input type="hidden" name="ma_sach" value="<?= $ma_sach ?>"><input type="hidden" name="ma_chuong" value="<?= $c['ma_chuong'] ?>"><button class="btn nho do">Xóa</button></form></td></tr><?php endforeach; ?></table></div>
<?php }
ad_cuoi();
