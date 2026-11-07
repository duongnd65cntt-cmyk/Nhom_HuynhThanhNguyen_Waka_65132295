<?php
// FR5.2 – khóa/mở khóa tài khoản; không xóa cứng tài khoản đã có giao dịch
require_once __DIR__ . '/khung.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['ma_nguoi_dung'] ?? 0); $act = $_POST['action'] ?? '';
    $st = $pdo->prepare('SELECT vai_tro FROM nguoi_dung WHERE ma_nguoi_dung = ?'); $st->execute([$id]); $vt = $st->fetchColumn();
    if ($vt === false || $id === (int)$admin['ma_nguoi_dung'] || $vt === 'QUAN_TRI') ad_xong('nguoi_dung.php', 'Không thể thao tác trên tài khoản này.', 'loi');
    if ($act === 'khoa' || $act === 'mo') {
        $pdo->prepare('UPDATE nguoi_dung SET trang_thai = ? WHERE ma_nguoi_dung = ?')->execute([$act === 'khoa' ? 'BI_KHOA' : 'HOAT_DONG', $id]);
        ad_xong('nguoi_dung.php', $act === 'khoa' ? 'Đã khóa tài khoản (có hiệu lực ngay).' : 'Đã mở khóa tài khoản.');
    }
    if ($act === 'xoa') {
        $c = $pdo->prepare('SELECT (SELECT COUNT(*) FROM don_hang WHERE ma_nguoi_dung = ?) + (SELECT COUNT(*) FROM dang_ky_hoi_vien WHERE ma_nguoi_dung = ?) + (SELECT COUNT(*) FROM thanh_toan WHERE ma_nguoi_duyet = ?)'); $c->execute([$id, $id, $id]);
        if ((int)$c->fetchColumn() > 0) ad_xong('nguoi_dung.php', 'Tài khoản đã có giao dịch, chỉ có thể khóa.', 'loi');
        $pdo->beginTransaction();
        foreach (['gio_hang', 'thu_vien'] as $t) $pdo->prepare("DELETE FROM $t WHERE ma_nguoi_dung = ?")->execute([$id]);
        $pdo->prepare('DELETE FROM nguoi_dung WHERE ma_nguoi_dung = ?')->execute([$id]);
        $pdo->commit();
        ad_xong('nguoi_dung.php', 'Đã xóa tài khoản.');
    }
}
$q = trim($_GET['q'] ?? ''); $tt = $_GET['tt'] ?? '';
$w = '1=1'; $p = [];
if ($q !== '') { $w .= ' AND (ten_dang_nhap LIKE :q OR email LIKE :q OR ho_ten LIKE :q)'; $p[':q'] = "%$q%"; }
if (in_array($tt, ['HOAT_DONG', 'BI_KHOA'], true)) { $w .= ' AND trang_thai = :tt'; $p[':tt'] = $tt; }
$st = $pdo->prepare("SELECT n.*, (SELECT MAX(ngay_het_han) FROM dang_ky_hoi_vien v WHERE v.ma_nguoi_dung = n.ma_nguoi_dung) AS vip_han,
    (SELECT COUNT(*) FROM don_hang d WHERE d.ma_nguoi_dung = n.ma_nguoi_dung) AS so_don FROM nguoi_dung n WHERE $w ORDER BY ma_nguoi_dung"); $st->execute($p);
ad_dau('Quản lý người dùng', 'nguoi_dung');
?>
<form class="thanh" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Tên đăng nhập / email / họ tên"><select name="tt"><?= ad_opt(['HOAT_DONG' => 'Hoạt động', 'BI_KHOA' => 'Bị khóa'], $tt, true) ?></select><button class="btn nho">Lọc</button></form>
<div class="cuon"><table><tr><th>ID</th><th>Tài khoản</th><th>Họ tên</th><th>Vai trò</th><th>Trạng thái</th><th>Hạt sồi</th><th>VIP đến</th><th>Đơn</th><th></th></tr>
<?php foreach ($st as $r): ?><tr><td><?= $r['ma_nguoi_dung'] ?></td><td><b><?= e($r['ten_dang_nhap']) ?></b><br><small><?= e($r['email']) ?></small></td><td><?= e($r['ho_ten']) ?></td>
  <td><?= $r['vai_tro'] === 'QUAN_TRI' ? 'Admin' : 'Thành viên' ?></td><td><span class="tag <?= e($r['trang_thai']) ?>"><?= $r['trang_thai'] === 'HOAT_DONG' ? 'Hoạt động' : 'Bị khóa' ?></span></td>
  <td><?= so($r['so_du_hat_soi']) ?></td><td><?= $r['vip_han'] ? e(date('d/m/Y', strtotime($r['vip_han']))) : '—' ?></td><td><?= $r['so_don'] ?></td>
  <td><?php if ($r['vai_tro'] === 'THANH_VIEN'): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="ma_nguoi_dung" value="<?= $r['ma_nguoi_dung'] ?>"><button class="btn nho xam" name="action" value="<?= $r['trang_thai'] === 'HOAT_DONG' ? 'khoa' : 'mo' ?>"><?= $r['trang_thai'] === 'HOAT_DONG' ? 'Khóa' : 'Mở khóa' ?></button>
    <?php if (!$r['so_don']): ?><button class="btn nho do" name="action" value="xoa" onclick="return confirm('Xóa tài khoản?')">Xóa</button><?php endif; ?></form><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<?php ad_cuoi();
