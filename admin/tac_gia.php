<?php
// FR5.1 – CRUD tác giả (không xóa tác giả còn sách)
require_once __DIR__ . '/khung.php';
$loi = [];
$id = (int)($_GET['sua'] ?? $_POST['ma_tac_gia'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'xoa') {
        $c = $pdo->prepare('SELECT COUNT(*) FROM sach WHERE ma_tac_gia = ?'); $c->execute([$id]);
        if ((int)$c->fetchColumn() > 0) ad_xong('tac_gia.php', 'Tác giả còn sách, không thể xóa.', 'loi');
        $a = $pdo->prepare('SELECT anh_dai_dien FROM tac_gia WHERE ma_tac_gia = ?'); $a->execute([$id]); up_xoa($a->fetchColumn() ?: null);
        $pdo->prepare('DELETE FROM tac_gia WHERE ma_tac_gia = ?')->execute([$id]); cache_xoa();
        ad_xong('tac_gia.php', 'Đã xóa tác giả.');
    }
    $v = ['ten_tac_gia' => trim($_POST['ten_tac_gia'] ?? ''), 'duong_dan_tinh' => slug(trim($_POST['duong_dan_tinh'] ?? '') ?: ($_POST['ten_tac_gia'] ?? '')), 'tieu_su' => trim($_POST['tieu_su'] ?? '') ?: null];
    if ($v['ten_tac_gia'] === '') $loi[] = 'Tên tác giả bắt buộc.';
    [$anh, $e1] = up_luu($_FILES['anh_dai_dien'] ?? null, 'uploads/tac_gia', MIME_ANH, ANH_MAX_BYTE, true);
    if ($e1) $loi[] = 'Ảnh: ' . $e1;
    if (!$loi) {
        try {
            if ($id) { $cu = $pdo->prepare('SELECT anh_dai_dien FROM tac_gia WHERE ma_tac_gia = ?'); $cu->execute([$id]); $cu = $cu->fetchColumn();
                $pdo->prepare('UPDATE tac_gia SET ten_tac_gia=?, duong_dan_tinh=?, tieu_su=?, anh_dai_dien=COALESCE(?, anh_dai_dien) WHERE ma_tac_gia=?')->execute([...array_values($v), $anh, $id]); if ($anh) up_xoa($cu); }
            else { $pdo->prepare('INSERT INTO tac_gia (ten_tac_gia, duong_dan_tinh, tieu_su, anh_dai_dien) VALUES (?,?,?,?)')->execute([...array_values($v), $anh]); $id = (int)$pdo->lastInsertId(); }
            sach_cap_nhat_tu_khoa($pdo, null, $id);
            ad_xong('tac_gia.php', 'Đã lưu tác giả.');
        } catch (PDOException $ex) { if ($anh) up_xoa($anh); if ($ex->getCode() === '23000') $loi[] = 'Đường dẫn tĩnh bị trùng.'; else throw $ex; }
    }
}
ad_dau('Quản lý tác giả', 'tac_gia');
if (isset($_GET['moi']) || $id || $loi):
    $b = ['ten_tac_gia' => '', 'duong_dan_tinh' => '', 'tieu_su' => '', 'anh_dai_dien' => ''];
    if ($id) { $q = $pdo->prepare('SELECT * FROM tac_gia WHERE ma_tac_gia = ?'); $q->execute([$id]); $b = $q->fetch() ?: $b; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') $b = array_merge($b, $_POST);
    hien_loi($loi); ?>
  <form method="post" enctype="multipart/form-data" class="panel"><?= csrf_field() ?><input type="hidden" name="ma_tac_gia" value="<?= $id ?>">
    <label>Tên tác giả</label><input type="text" name="ten_tac_gia" value="<?= e($b['ten_tac_gia']) ?>" required>
    <label>Đường dẫn tĩnh (tự tạo nếu trống)</label><input type="text" name="duong_dan_tinh" value="<?= e($b['duong_dan_tinh']) ?>">
    <label>Ảnh đại diện (≤ 2MB)</label><input type="file" name="anh_dai_dien" accept="image/jpeg,image/png,image/webp">
    <label>Tiểu sử</label><textarea name="tieu_su"><?= e($b['tieu_su']) ?></textarea><button class="btn">Lưu</button></form>
<?php else:
    $ds = $pdo->query('SELECT t.*, (SELECT COUNT(*) FROM sach s WHERE s.ma_tac_gia = t.ma_tac_gia) AS so_sach FROM tac_gia t ORDER BY t.ten_tac_gia')->fetchAll(); ?>
  <p><a class="btn nho" href="?moi=1">+ Thêm tác giả</a></p>
  <div class="cuon"><table><tr><th>Tên</th><th>Số sách</th><th>Tiểu sử</th><th></th></tr>
  <?php foreach ($ds as $t): ?><tr><td><b><?= e($t['ten_tac_gia']) ?></b></td><td><?= $t['so_sach'] ?></td><td><small><?= e(mb_substr((string)$t['tieu_su'], 0, 90)) ?></small></td>
    <td><a class="btn nho xam" href="?sua=<?= $t['ma_tac_gia'] ?>">Sửa</a><form method="post" data-xacnhan="Xóa tác giả?"><?= csrf_field() ?><input type="hidden" name="action" value="xoa"><input type="hidden" name="ma_tac_gia" value="<?= $t['ma_tac_gia'] ?>"><button class="btn nho do">Xóa</button></form></td></tr><?php endforeach; ?></table></div>
<?php endif; ad_cuoi();
