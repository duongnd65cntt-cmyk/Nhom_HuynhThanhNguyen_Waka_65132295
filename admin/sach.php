<?php
// FR5.1 – CRUD sách (upload bìa; sách đã có phát sinh giao dịch → chuyển TAM_AN thay vì xóa cứng)
require_once __DIR__ . '/khung.php';

$DD = ['SACH_DIEN_TU' => 'Sách điện tử', 'SACH_NOI' => 'Sách nói', 'TRUYEN_TRANH' => 'Truyện tranh'];
$HT = ['MIEN_PHI' => 'Miễn phí', 'HOI_VIEN' => 'Hội viên', 'MUA_LE' => 'Mua lẻ'];
$TT = ['DANG_RA' => 'Đang ra', 'HOAN_THANH' => 'Hoàn thành', 'TAM_AN' => 'Tạm ẩn'];
$loi = [];
$id = (int)($_GET['sua'] ?? $_POST['ma_sach'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'xoa') {
        $c = $pdo->prepare('SELECT (SELECT COUNT(*) FROM chi_tiet_don_hang WHERE ma_sach = ? OR ma_chuong IN (SELECT ma_chuong FROM chuong WHERE ma_sach = ?))
            + (SELECT COUNT(*) FROM gio_hang WHERE ma_sach = ? OR ma_chuong IN (SELECT ma_chuong FROM chuong WHERE ma_sach = ?)) + (SELECT COUNT(*) FROM thu_vien WHERE ma_sach = ?)');
        $c->execute([$id, $id, $id, $id, $id]);
        if ((int)$c->fetchColumn() > 0) {
            $pdo->prepare("UPDATE sach SET tinh_trang = 'TAM_AN' WHERE ma_sach = ?")->execute([$id]);
            cache_xoa();
            ad_xong('sach.php', 'Sách đã có giao dịch/tủ sách nên được chuyển sang TẠM ẨN (không xóa cứng).');
        }
        $b = $pdo->prepare('SELECT anh_bia FROM sach WHERE ma_sach = ?'); $b->execute([$id]); $bia = $b->fetchColumn();
        $files = $pdo->prepare('SELECT tep_noi_dung FROM chuong WHERE ma_sach = ?'); $files->execute([$id]); $files = $files->fetchAll(PDO::FETCH_COLUMN);
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM chuong WHERE ma_sach = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM sach WHERE ma_sach = ?')->execute([$id]);
        $pdo->commit();
        up_xoa($bia); foreach ($files as $f) { up_xoa($f); up_xoa_thu_muc($f); }
        cache_xoa();
        ad_xong('sach.php', 'Đã xóa sách.');
    }
    // lưu (thêm/sửa)
    $v = ['ten_sach' => trim($_POST['ten_sach'] ?? ''), 'duong_dan_tinh' => slug(trim($_POST['duong_dan_tinh'] ?? '') ?: ($_POST['ten_sach'] ?? '')),
          'ma_the_loai' => (int)($_POST['ma_the_loai'] ?? 0), 'ma_tac_gia' => (int)($_POST['ma_tac_gia'] ?? 0), 'mo_ta' => trim($_POST['mo_ta'] ?? ''),
          'nha_xuat_ban' => trim($_POST['nha_xuat_ban'] ?? ''), 'nam_xuat_ban' => (int)($_POST['nam_xuat_ban'] ?? 0) ?: null,
          'dinh_dang' => $_POST['dinh_dang'] ?? '', 'hinh_thuc_doc' => $_POST['hinh_thuc_doc'] ?? '', 'gia_hat_soi' => (int)($_POST['gia_hat_soi'] ?? 0), 'tinh_trang' => $_POST['tinh_trang'] ?? ''];
    if ($v['ten_sach'] === '' || mb_strlen($v['ten_sach']) > 255) $loi[] = 'Tên sách bắt buộc (≤ 255 ký tự).';
    if (!isset($DD[$v['dinh_dang']]) || !isset($HT[$v['hinh_thuc_doc']]) || !isset($TT[$v['tinh_trang']])) $loi[] = 'Định dạng / hình thức / tình trạng không hợp lệ.';
    if ($v['hinh_thuc_doc'] === 'MUA_LE' && $v['gia_hat_soi'] <= 0) $loi[] = 'Sách mua lẻ phải có giá hạt sồi > 0.';
    if ($v['hinh_thuc_doc'] !== 'MUA_LE') $v['gia_hat_soi'] = 0;
    if ($v['nam_xuat_ban'] !== null && ($v['nam_xuat_ban'] < 1000 || $v['nam_xuat_ban'] > (int)date('Y') + 1)) $loi[] = 'Năm xuất bản không hợp lệ.';
    $ck = $pdo->prepare('SELECT 1 FROM the_loai WHERE ma_the_loai = ?'); $ck->execute([$v['ma_the_loai']]); if (!$ck->fetchColumn()) $loi[] = 'Chọn thể loại.';
    $ck = $pdo->prepare('SELECT 1 FROM tac_gia WHERE ma_tac_gia = ?'); $ck->execute([$v['ma_tac_gia']]); if (!$ck->fetchColumn()) $loi[] = 'Chọn tác giả.';
    [$bia, $e1] = up_luu($_FILES['anh_bia'] ?? null, 'uploads/bia', MIME_ANH, ANH_MAX_BYTE, true);
    if ($e1) $loi[] = 'Ảnh bìa: ' . $e1;
    if (!$id && !$bia && !$loi) $loi[] = 'Cần tải ảnh bìa cho sách mới.';
    if (!$loi) {
        try {
            if ($id) {
                $cu = $pdo->prepare('SELECT anh_bia FROM sach WHERE ma_sach = ?'); $cu->execute([$id]); $cu = $cu->fetchColumn();
                $pdo->prepare('UPDATE sach SET ten_sach=?, duong_dan_tinh=?, ma_the_loai=?, ma_tac_gia=?, mo_ta=?, nha_xuat_ban=?, nam_xuat_ban=?, dinh_dang=?, hinh_thuc_doc=?, gia_hat_soi=?, tinh_trang=?, anh_bia=COALESCE(?, anh_bia) WHERE ma_sach=?')
                    ->execute([...array_values($v), $bia, $id]);
                if ($bia) up_xoa($cu);
            } else {
                $pdo->prepare('INSERT INTO sach (ten_sach, duong_dan_tinh, ma_the_loai, ma_tac_gia, mo_ta, nha_xuat_ban, nam_xuat_ban, dinh_dang, hinh_thuc_doc, gia_hat_soi, tinh_trang, anh_bia) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([...array_values($v), $bia]);
                $id = (int)$pdo->lastInsertId();
            }
            sach_cap_nhat_tu_khoa($pdo, $id);
            ad_xong('sach.php', 'Đã lưu sách.');
        } catch (PDOException $ex) {
            if ($bia) up_xoa($bia);
            if ($ex->getCode() === '23000') $loi[] = 'Đường dẫn tĩnh bị trùng, hãy đổi.'; else throw $ex;
        }
    }
}

$sua = isset($_GET['moi']) || $id || $loi;
ad_dau('Quản lý sách', 'sach');
if ($sua) {
    $b = ['ten_sach' => '', 'duong_dan_tinh' => '', 'ma_the_loai' => '', 'ma_tac_gia' => '', 'mo_ta' => '', 'nha_xuat_ban' => '', 'nam_xuat_ban' => '', 'dinh_dang' => 'SACH_DIEN_TU', 'hinh_thuc_doc' => 'MIEN_PHI', 'gia_hat_soi' => 0, 'tinh_trang' => 'DANG_RA', 'anh_bia' => ''];
    if ($id) { $st = $pdo->prepare('SELECT * FROM sach WHERE ma_sach = ?'); $st->execute([$id]); $b = $st->fetch() ?: $b; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') $b = array_merge($b, $_POST);
    $tl = array_column($pdo->query('SELECT ma_the_loai, ten_the_loai FROM the_loai ORDER BY ten_the_loai')->fetchAll(), 'ten_the_loai', 'ma_the_loai');
    $tg = array_column($pdo->query('SELECT ma_tac_gia, ten_tac_gia FROM tac_gia ORDER BY ten_tac_gia')->fetchAll(), 'ten_tac_gia', 'ma_tac_gia');
    hien_loi($loi); ?>
  <form method="post" enctype="multipart/form-data" class="panel"><?= csrf_field() ?><input type="hidden" name="ma_sach" value="<?= $id ?>">
    <label>Tên sách</label><input type="text" name="ten_sach" value="<?= e($b['ten_sach']) ?>" required maxlength="255">
    <label>Đường dẫn tĩnh (để trống sẽ tự tạo)</label><input type="text" name="duong_dan_tinh" value="<?= e($b['duong_dan_tinh']) ?>">
    <div class="hang2"><div><label>Thể loại</label><select name="ma_the_loai"><?= ad_opt($tl, $b['ma_the_loai']) ?></select></div>
      <div><label>Tác giả</label><select name="ma_tac_gia"><?= ad_opt($tg, $b['ma_tac_gia']) ?></select></div></div>
    <div class="hang2"><div><label>Định dạng</label><select name="dinh_dang"><?= ad_opt($DD, $b['dinh_dang']) ?></select></div>
      <div><label>Hình thức đọc</label><select name="hinh_thuc_doc"><?= ad_opt($HT, $b['hinh_thuc_doc']) ?></select></div>
      <div><label>Giá cả sách (hạt sồi, nếu mua lẻ)</label><input type="number" name="gia_hat_soi" min="0" value="<?= e($b['gia_hat_soi']) ?>"></div>
      <div><label>Tình trạng</label><select name="tinh_trang"><?= ad_opt($TT, $b['tinh_trang']) ?></select></div></div>
    <div class="hang2"><div><label>Nhà xuất bản</label><input type="text" name="nha_xuat_ban" value="<?= e($b['nha_xuat_ban']) ?>"></div>
      <div><label>Năm xuất bản</label><input type="number" name="nam_xuat_ban" value="<?= e($b['nam_xuat_ban']) ?>"></div></div>
    <label>Ảnh bìa (JPG/PNG/WEBP ≤ 2MB)</label><?php if ($b['anh_bia']): ?><img src="<?= e(url_anh($b['anh_bia'])) ?>" alt="" style="height:90px;display:block;margin-bottom:6px;border-radius:4px"><?php endif; ?>
    <input type="file" name="anh_bia" accept="image/jpeg,image/png,image/webp">
    <label>Mô tả</label><textarea name="mo_ta"><?= e($b['mo_ta']) ?></textarea>
    <button class="btn">Lưu</button> <a class="btn xam" href="sach.php" style="margin-top:18px">Hủy</a>
  </form>
<?php } else {
    $q = trim($_GET['q'] ?? ''); $tt = $_GET['tt'] ?? ''; $p = []; $w = '1=1';
    if ($q !== '') $w .= ' AND ' . sach_dieu_kien_tu_khoa($q, $p);
    if (isset($TT[$tt])) { $w .= ' AND s.tinh_trang = :tt'; $p[':tt'] = $tt; }
    $trang = max(1, (int)($_GET['trang'] ?? 1));
    $st = $pdo->prepare("SELECT COUNT(*) FROM sach s JOIN tac_gia t ON t.ma_tac_gia = s.ma_tac_gia WHERE $w"); $st->execute($p); $tong = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT s.*, t.ten_tac_gia, tl.ten_the_loai, (SELECT COUNT(*) FROM chuong c WHERE c.ma_sach = s.ma_sach) AS so_chuong FROM sach s JOIN tac_gia t ON t.ma_tac_gia = s.ma_tac_gia JOIN the_loai tl ON tl.ma_the_loai = s.ma_the_loai
        WHERE $w ORDER BY s.ma_sach DESC LIMIT " . MOI_TRANG . ' OFFSET ' . (($trang - 1) * MOI_TRANG));
    $st->execute($p); ?>
  <form class="thanh" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Tìm sách/tác giả"><select name="tt"><?= ad_opt($TT, $tt, true) ?></select><button class="btn nho">Lọc</button><a class="btn nho" href="?moi=1">+ Thêm sách</a></form>
  <div class="cuon"><table><tr><th></th><th>Tên sách</th><th>Tác giả / Thể loại</th><th>Định dạng</th><th>Hình thức</th><th>Chương</th><th>Tình trạng</th><th></th></tr>
  <?php foreach ($st as $s): ?><tr><td><img src="<?= e(url_anh($s['anh_bia'])) ?>" alt="" style="height:50px;border-radius:3px"></td><td><b><?= e($s['ten_sach']) ?></b><br><small>👁 <?= so($s['luot_xem']) ?> · ★ <?= $s['diem_tb'] ?></small></td>
    <td><?= e($s['ten_tac_gia']) ?><br><small><?= e($s['ten_the_loai']) ?></small></td><td><?= e($DD[$s['dinh_dang']]) ?></td><td><?= e($HT[$s['hinh_thuc_doc']]) ?><?= $s['gia_hat_soi'] > 0 ? '<br><small>' . so($s['gia_hat_soi']) . ' 🌰</small>' : '' ?></td>
    <td><a href="chuong.php?sach=<?= $s['ma_sach'] ?>"><?= $s['so_chuong'] ?> chương</a></td><td><span class="tag <?= e($s['tinh_trang']) ?>"><?= e($TT[$s['tinh_trang']]) ?></span></td>
    <td><a class="btn nho xam" href="?sua=<?= $s['ma_sach'] ?>">Sửa</a> <a class="btn nho xam" href="chuong.php?sach=<?= $s['ma_sach'] ?>">Chương</a>
      <form method="post" data-xacnhan="Xóa sách này? (nếu đã có giao dịch sẽ chỉ được ẩn)"><?= csrf_field() ?><input type="hidden" name="action" value="xoa"><input type="hidden" name="ma_sach" value="<?= $s['ma_sach'] ?>"><button class="btn nho do">Xóa</button></form></td></tr><?php endforeach; ?></table></div>
  <?= phan_trang($tong, $trang, MOI_TRANG, ['q' => $q, 'tt' => $tt]) ?>
<?php }
ad_cuoi();
