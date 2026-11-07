<?php
// FR5.5 – ẩn/hiện/xóa bình luận vi phạm (điểm TB tự tính lại nhờ trigger)
require_once __DIR__ . '/khung.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0); $act = $_POST['action'] ?? '';
    if ($act === 'xoa') $pdo->prepare('DELETE FROM danh_gia WHERE ma_danh_gia = ?')->execute([$id]);
    elseif ($act === 'an' || $act === 'hien') $pdo->prepare('UPDATE danh_gia SET trang_thai = ? WHERE ma_danh_gia = ?')->execute([$act === 'an' ? 'AN' : 'HIEN_THI', $id]);
    cache_xoa();
    ad_xong('binh_luan.php?' . http_build_query(array_intersect_key($_GET, ['tt' => 1, 'q' => 1])), 'Đã cập nhật; điểm trung bình đã được tính lại.');
}
$tt = $_GET['tt'] ?? ''; $q = trim($_GET['q'] ?? '');
$w = '1=1'; $p = [];
if (in_array($tt, ['HIEN_THI', 'AN'], true)) { $w .= ' AND d.trang_thai = :tt'; $p[':tt'] = $tt; }
if ($q !== '') { $w .= ' AND (s.ten_sach LIKE :q OR n.ten_dang_nhap LIKE :q OR d.binh_luan LIKE :q)'; $p[':q'] = "%$q%"; }
$trang = max(1, (int)($_GET['trang'] ?? 1));
$c = $pdo->prepare("SELECT COUNT(*) FROM danh_gia d JOIN sach s ON s.ma_sach = d.ma_sach JOIN nguoi_dung n ON n.ma_nguoi_dung = d.ma_nguoi_dung WHERE $w"); $c->execute($p); $tong = (int)$c->fetchColumn();
$st = $pdo->prepare("SELECT d.*, s.ten_sach, n.ten_dang_nhap FROM danh_gia d JOIN sach s ON s.ma_sach = d.ma_sach JOIN nguoi_dung n ON n.ma_nguoi_dung = d.ma_nguoi_dung WHERE $w ORDER BY d.ngay_danh_gia DESC LIMIT " . MOI_TRANG . ' OFFSET ' . (($trang - 1) * MOI_TRANG));
$st->execute($p);
ad_dau('Quản lý bình luận', 'binh_luan');
?>
<form class="thanh" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Sách / người dùng / nội dung"><select name="tt"><?= ad_opt(['HIEN_THI' => 'Đang hiển thị', 'AN' => 'Đã ẩn'], $tt, true) ?></select><button class="btn nho">Lọc</button></form>
<div class="cuon"><table><tr><th>Sách</th><th>Người dùng</th><th>Sao</th><th>Nội dung</th><th>Ngày</th><th>Trạng thái</th><th></th></tr>
<?php foreach ($st as $d): ?><tr><td><?= e($d['ten_sach']) ?></td><td><?= e($d['ten_dang_nhap']) ?></td><td style="color:var(--yellow)"><?= str_repeat('★', (int)$d['so_sao']) ?></td><td style="max-width:340px"><?= e($d['binh_luan'] ?? '') ?></td>
  <td><?= e(date('d/m/Y', strtotime($d['ngay_danh_gia']))) ?></td><td><span class="tag <?= e($d['trang_thai']) ?>"><?= $d['trang_thai'] === 'AN' ? 'Đã ẩn' : 'Hiển thị' ?></span></td>
  <td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['ma_danh_gia'] ?>"><button class="btn nho xam" name="action" value="<?= $d['trang_thai'] === 'AN' ? 'hien' : 'an' ?>"><?= $d['trang_thai'] === 'AN' ? 'Hiện' : 'Ẩn' ?></button>
    <button class="btn nho do" name="action" value="xoa" onclick="return confirm('Xóa bình luận?')">Xóa</button></form></td></tr><?php endforeach; ?></table></div>
<?= phan_trang($tong, $trang, MOI_TRANG, ['tt' => $tt, 'q' => $q]) ?>
<?php ad_cuoi();
