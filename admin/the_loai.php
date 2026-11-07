<?php
// FR5.1 – CRUD thể loại (đa cấp). Thể loại còn sách/con → ẩn thay vì xóa.
require_once __DIR__ . '/khung.php';
$loi = [];
$id = (int)($_GET['sua'] ?? $_POST['ma_the_loai'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'xoa') {
        $c = $pdo->prepare('SELECT (SELECT COUNT(*) FROM sach WHERE ma_the_loai = ?) + (SELECT COUNT(*) FROM the_loai WHERE ma_the_loai_cha = ?)'); $c->execute([$id, $id]);
        if ((int)$c->fetchColumn() > 0) { $pdo->prepare('UPDATE the_loai SET trang_thai = 0 WHERE ma_the_loai = ?')->execute([$id]); cache_xoa(); ad_xong('the_loai.php', 'Thể loại còn sách/thể loại con nên được ẩn.'); }
        $pdo->prepare('DELETE FROM the_loai WHERE ma_the_loai = ?')->execute([$id]); cache_xoa();
        ad_xong('the_loai.php', 'Đã xóa thể loại.');
    }
    $cha = (int)($_POST['ma_the_loai_cha'] ?? 0) ?: null;
    $v = ['ten_the_loai' => trim($_POST['ten_the_loai'] ?? ''), 'duong_dan_tinh' => slug(trim($_POST['duong_dan_tinh'] ?? '') ?: ($_POST['ten_the_loai'] ?? '')),
          'mo_ta' => trim($_POST['mo_ta'] ?? '') ?: null, 'ma_the_loai_cha' => $cha, 'thu_tu' => (int)($_POST['thu_tu'] ?? 0), 'trang_thai' => isset($_POST['trang_thai']) ? 1 : 0];
    if ($v['ten_the_loai'] === '') $loi[] = 'Tên thể loại bắt buộc.';
    if ($cha && $id && in_array($cha, the_loai_con_ids($pdo, $id), true)) $loi[] = 'Thể loại cha không được là chính nó hoặc con của nó.';
    if (!$loi) {
        try {
            if ($id) $pdo->prepare('UPDATE the_loai SET ten_the_loai=?, duong_dan_tinh=?, mo_ta=?, ma_the_loai_cha=?, thu_tu=?, trang_thai=? WHERE ma_the_loai=?')->execute([...array_values($v), $id]);
            else $pdo->prepare('INSERT INTO the_loai (ten_the_loai, duong_dan_tinh, mo_ta, ma_the_loai_cha, thu_tu, trang_thai) VALUES (?,?,?,?,?,?)')->execute(array_values($v));
            cache_xoa(); ad_xong('the_loai.php', 'Đã lưu thể loại.');
        } catch (PDOException $ex) { if ($ex->getCode() === '23000') $loi[] = 'Đường dẫn tĩnh bị trùng.'; else throw $ex; }
    }
}
ad_dau('Quản lý thể loại', 'the_loai');
$tat = $pdo->query('SELECT t.*, p.ten_the_loai AS ten_cha, (SELECT COUNT(*) FROM sach s WHERE s.ma_the_loai = t.ma_the_loai) AS so_sach FROM the_loai t LEFT JOIN the_loai p ON p.ma_the_loai = t.ma_the_loai_cha ORDER BY COALESCE(t.ma_the_loai_cha, t.ma_the_loai), t.ma_the_loai_cha IS NOT NULL, t.thu_tu')->fetchAll();
if (isset($_GET['moi']) || $id || $loi):
    $b = ['ten_the_loai' => '', 'duong_dan_tinh' => '', 'mo_ta' => '', 'ma_the_loai_cha' => '', 'thu_tu' => 0, 'trang_thai' => 1];
    foreach ($tat as $t) if ((int)$t['ma_the_loai'] === $id) $b = $t;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') $b = array_merge($b, $_POST, ['trang_thai' => isset($_POST['trang_thai']) ? 1 : 0]);
    $cha_ds = []; foreach ($tat as $t) if ((int)$t['ma_the_loai'] !== $id) $cha_ds[$t['ma_the_loai']] = $t['ten_the_loai'];
    hien_loi($loi); ?>
  <form method="post" class="panel"><?= csrf_field() ?><input type="hidden" name="ma_the_loai" value="<?= $id ?>">
    <label>Tên</label><input type="text" name="ten_the_loai" value="<?= e($b['ten_the_loai']) ?>" required>
    <label>Đường dẫn tĩnh (tự tạo nếu trống)</label><input type="text" name="duong_dan_tinh" value="<?= e($b['duong_dan_tinh']) ?>">
    <div class="hang2"><div><label>Thể loại cha</label><select name="ma_the_loai_cha"><option value="">— Không (cấp 1) —</option><?= ad_opt($cha_ds, $b['ma_the_loai_cha']) ?></select></div><div><label>Thứ tự</label><input type="number" name="thu_tu" value="<?= e($b['thu_tu']) ?>"></div></div>
    <label>Mô tả</label><textarea name="mo_ta"><?= e($b['mo_ta']) ?></textarea>
    <label><input type="checkbox" name="trang_thai" value="1" <?= $b['trang_thai'] ? 'checked' : '' ?>> Hiển thị</label>
    <button class="btn">Lưu</button></form>
<?php else: ?>
  <p><a class="btn nho" href="?moi=1">+ Thêm thể loại</a></p>
  <div class="cuon"><table><tr><th>Tên</th><th>Cha</th><th>Số sách</th><th>Thứ tự</th><th>Hiển thị</th><th></th></tr>
  <?php foreach ($tat as $t): ?><tr><td><?= $t['ma_the_loai_cha'] ? '↳ ' : '' ?><b><?= e($t['ten_the_loai']) ?></b></td><td><?= e($t['ten_cha'] ?? '—') ?></td><td><?= $t['so_sach'] ?></td><td><?= $t['thu_tu'] ?></td><td><?= $t['trang_thai'] ? '✓' : '✗' ?></td>
    <td><a class="btn nho xam" href="?sua=<?= $t['ma_the_loai'] ?>">Sửa</a><form method="post" data-xacnhan="Xóa thể loại?"><?= csrf_field() ?><input type="hidden" name="action" value="xoa"><input type="hidden" name="ma_the_loai" value="<?= $t['ma_the_loai'] ?>"><button class="btn nho do">Xóa</button></form></td></tr><?php endforeach; ?></table></div>
<?php endif; ad_cuoi();
