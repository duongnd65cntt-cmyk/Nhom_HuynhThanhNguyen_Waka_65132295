<?php
// FR5.6 – thống kê: doanh thu theo ngày/tháng (NAP_HAT_SOI + MUA_VIP đã thanh toán, VND), sách bán chạy, hội viên còn hạn
require_once __DIR__ . '/khung.php';
dh_huy_qua_han($pdo);
$che_do = ($_GET['theo'] ?? 'ngay') === 'thang' ? 'thang' : 'ngay';
$fmt = $che_do === 'ngay' ? '%Y-%m-%d' : '%Y-%m';
$tu = $che_do === 'ngay' ? date('Y-m-d', strtotime('-29 days')) : date('Y-m-01', strtotime('-11 months'));
$st = $pdo->prepare("SELECT DATE_FORMAT(COALESCE(t.ngay, d.ngay_tao), '$fmt') AS ky, SUM(d.tong_tien_goc - d.tien_giam) AS doanh_thu, COUNT(*) AS so_don
    FROM don_hang d LEFT JOIN (SELECT ma_don_hang, MAX(ngay_thanh_toan) AS ngay FROM thanh_toan WHERE trang_thai = 'THANH_CONG' GROUP BY ma_don_hang) t ON t.ma_don_hang = d.ma_don_hang
    WHERE d.trang_thai = 'DA_THANH_TOAN' AND d.loai_don IN ('NAP_HAT_SOI','MUA_VIP') AND COALESCE(t.ngay, d.ngay_tao) >= ? GROUP BY ky ORDER BY ky");
$st->execute([$tu]);
$dt = array_column($st->fetchAll(), null, 'ky');
$ky_ds = [];
for ($i = ($che_do === 'ngay' ? 29 : 11); $i >= 0; $i--) $ky_ds[] = $che_do === 'ngay' ? date('Y-m-d', strtotime("-$i days")) : date('Y-m', strtotime(date('Y-m-01') . " -$i months"));
$max = max(1, ...array_map(fn($k) => (int)($dt[$k]['doanh_thu'] ?? 0), $ky_ds));
$tong_dt = array_sum(array_map(fn($k) => (int)($dt[$k]['doanh_thu'] ?? 0), $ky_ds));

$ban_chay = $pdo->query("SELECT s.ma_sach, s.ten_sach, COUNT(*) AS luot, SUM(ct.don_gia) AS hat_soi FROM chi_tiet_don_hang ct
    JOIN don_hang d ON d.ma_don_hang = ct.ma_don_hang AND d.loai_don = 'MUA_NOI_DUNG' AND d.trang_thai = 'DA_THANH_TOAN'
    LEFT JOIN chuong c ON c.ma_chuong = ct.ma_chuong JOIN sach s ON s.ma_sach = COALESCE(ct.ma_sach, c.ma_sach)
    GROUP BY s.ma_sach, s.ten_sach ORDER BY luot DESC, hat_soi DESC LIMIT 10")->fetchAll();
$n = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
ad_dau('Thống kê', 'index');
?>
<div class="the-so">
  <div>Hội viên còn hạn<b><?= so($n('SELECT COUNT(*) FROM (SELECT ma_nguoi_dung FROM dang_ky_hoi_vien GROUP BY ma_nguoi_dung HAVING MAX(ngay_het_han) > NOW()) x')) ?></b></div>
  <div>Đơn chờ thanh toán<b><?= so($n("SELECT COUNT(*) FROM don_hang WHERE trang_thai = 'CHO_THANH_TOAN'")) ?></b></div>
  <div>Sách đang bán<b><?= so($n("SELECT COUNT(*) FROM sach WHERE tinh_trang <> 'TAM_AN'")) ?></b></div>
  <div>Thành viên<b><?= so($n("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'THANH_VIEN'")) ?></b></div>
  <div>Doanh thu <?= $che_do === 'ngay' ? '30 ngày' : '12 tháng' ?><b><?= so($tong_dt) ?> ₫</b></div>
</div>
<div class="tab-bar"><a class="<?= $che_do === 'ngay' ? 'on' : '' ?>" href="?theo=ngay">Theo ngày</a><a class="<?= $che_do === 'thang' ? 'on' : '' ?>" href="?theo=thang">Theo tháng</a></div>
<div class="panel"><div class="cot" style="padding-bottom:24px">
<?php foreach ($ky_ds as $k): $v = (int)($dt[$k]['doanh_thu'] ?? 0); ?><div title="<?= e($k) ?>: <?= so($v) ?> ₫" style="height:<?= max(2, round($v / $max * 100)) ?>%"><span><?= e(substr($k, $che_do === 'ngay' ? 5 : 2)) ?></span></div><?php endforeach; ?></div>
<div class="cuon mt"><table><tr><th>Kỳ</th><th>Số đơn</th><th>Doanh thu (VND)</th></tr>
<?php foreach (array_reverse($ky_ds) as $k): if (!isset($dt[$k])) continue; ?><tr><td><?= e($k) ?></td><td><?= $dt[$k]['so_don'] ?></td><td class="gia"><?= so($dt[$k]['doanh_thu']) ?></td></tr><?php endforeach; if (!$dt): ?><tr><td colspan="3" class="muted">Chưa có doanh thu trong kỳ.</td></tr><?php endif; ?></table></div></div>
<h2>Sách bán chạy (đơn mua nội dung đã thanh toán)</h2>
<div class="cuon"><table><tr><th>#</th><th>Sách</th><th>Lượt bán (dòng chi tiết)</th><th>Hạt sồi thu được</th></tr>
<?php foreach ($ban_chay as $i => $r): ?><tr><td><?= $i + 1 ?></td><td><a href="<?= e(url('chi_tiet.php?id=' . $r['ma_sach'])) ?>"><?= e($r['ten_sach']) ?></a></td><td><?= $r['luot'] ?></td><td><?= so($r['hat_soi']) ?> 🌰</td></tr><?php endforeach; if (!$ban_chay): ?><tr><td colspan="4" class="muted">Chưa có dữ liệu.</td></tr><?php endif; ?></table></div>
<?php ad_cuoi();
