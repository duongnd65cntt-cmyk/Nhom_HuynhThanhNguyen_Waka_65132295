<?php
// FR2.2 / FR2.3 – danh sách sách: lọc đồng thời, sắp xếp, phân trang 20 sách, giữ bộ lọc khi chuyển trang
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/sach.php';

$f = [
    'the_loai' => (int)($_GET['the_loai'] ?? $_GET['id'] ?? 0) ?: '',
    'tac_gia' => (int)($_GET['tac_gia'] ?? 0) ?: '',
    'dinh_dang' => (string)($_GET['dinh_dang'] ?? ''),
    'hinh_thuc' => (string)($_GET['hinh_thuc'] ?? ''),
    'tinh_trang' => (string)($_GET['tinh_trang'] ?? ''),
    'sap_xep' => (string)($_GET['sap_xep'] ?? 'moi'),
];
$kq = sach_loc($pdo, $f, (int)($_GET['trang'] ?? 1));
$query = array_filter($f, fn($v) => $v !== '' && $v !== 'moi');

$cats = the_loai_cay($pdo);
$tac_gia = $pdo->query("SELECT t.ma_tac_gia, t.ten_tac_gia FROM tac_gia t WHERE EXISTS (SELECT 1 FROM sach s WHERE s.ma_tac_gia = t.ma_tac_gia AND s.tinh_trang <> 'TAM_AN') ORDER BY t.ten_tac_gia")->fetchAll();
$tieu_de = 'Tất cả sách';
foreach ($cats as $c) if ((int)$c['ma_the_loai'] === (int)$f['the_loai']) $tieu_de = $c['ten_the_loai'];
if ($f['tac_gia']) foreach ($tac_gia as $t) if ((int)$t['ma_tac_gia'] === (int)$f['tac_gia']) $tieu_de = 'Tác giả: ' . $t['ten_tac_gia'];

$cay = function (?int $cha, int $cap = 0) use (&$cay, $cats, $f, $query): string {
    $h = '';
    foreach ($cats as $c) {
        if ((($c['ma_the_loai_cha'] === null) ? null : (int)$c['ma_the_loai_cha']) !== $cha) continue;
        $q = ['the_loai' => $c['ma_the_loai']] + $query;
        $h .= '<a class="' . ($cap ? 'con ' : '') . ((int)$f['the_loai'] === (int)$c['ma_the_loai'] ? 'on' : '') . '" href="?' . e(http_build_query($q)) . '">' . e($c['ten_the_loai']) . '</a>' . $cay((int)$c['ma_the_loai'], $cap + 1);
    }
    return $h;
};
$opt = fn(array $ds, string $cur) => implode('', array_map(fn($k) => '<option value="' . e($k) . '"' . ($cur === (string)$k ? ' selected' : '') . '>' . e($ds[$k]) . '</option>', array_keys($ds)));

trang_dau($tieu_de);
?>
<h1><?= e($tieu_de) ?> <small>(<?= so($kq['tong']) ?> sách)</small></h1>
<div class="bo-cuc">
  <form class="loc" method="get">
    <h4>Thể loại</h4>
    <div class="cay"><a class="<?= $f['the_loai'] ? '' : 'on' ?>" href="?<?= e(http_build_query(array_diff_key($query, ['the_loai' => 1]))) ?>">Tất cả</a><?= $cay(null) ?></div>
    <input type="hidden" name="the_loai" value="<?= e($f['the_loai']) ?>">
    <h4>Tác giả</h4>
    <select name="tac_gia"><option value="">Tất cả</option><?= $opt(array_column($tac_gia, 'ten_tac_gia', 'ma_tac_gia'), (string)$f['tac_gia']) ?></select>
    <h4>Định dạng</h4>
    <select name="dinh_dang"><option value="">Tất cả</option><?= $opt(['SACH_DIEN_TU' => 'Sách điện tử', 'SACH_NOI' => 'Sách nói', 'TRUYEN_TRANH' => 'Truyện tranh'], $f['dinh_dang']) ?></select>
    <h4>Hình thức đọc</h4>
    <select name="hinh_thuc"><option value="">Tất cả</option><?= $opt(['MIEN_PHI' => 'Miễn phí', 'HOI_VIEN' => 'Hội viên', 'MUA_LE' => 'Mua lẻ'], $f['hinh_thuc']) ?></select>
    <h4>Tình trạng</h4>
    <select name="tinh_trang"><option value="">Tất cả</option><?= $opt(['DANG_RA' => 'Đang ra', 'HOAN_THANH' => 'Hoàn thành'], $f['tinh_trang']) ?></select>
    <h4>Sắp xếp</h4>
    <select name="sap_xep"><?= $opt(['moi' => 'Mới nhất', 'xem' => 'Xem nhiều', 'diem' => 'Điểm cao'], $f['sap_xep']) ?></select>
    <button class="btn" style="width:100%;margin-top:14px">Lọc</button>
  </form>
  <section>
    <?php if (!$kq['ds']): ?><p class="muted">Không có sách phù hợp bộ lọc.</p>
    <?php else: ?><div class="luoi"><?php foreach ($kq['ds'] as $s) echo the_sach($s); ?></div><?php endif; ?>
    <?= phan_trang($kq['tong'], $kq['trang'], MOI_TRANG, $query) ?>
  </section>
</div>
<?php trang_cuoi();
