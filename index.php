<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/sach.php';

// Các mục trên trang chủ: [tiêu đề, liên kết "Xem tất cả", danh sách sách, có đánh số hạng?]
$muc = cache_lay('trang_chu_v3', CACHE_GIAY, function () use ($pdo) {
    $ds = [];
    $them = function (string $tieu_de, string $lk, array $sach, bool $hang = false) use (&$ds) { if ($sach) $ds[] = [$tieu_de, $lk, $sach, $hang]; };
    $them('🏆 Bảng Xếp Hạng', 'danh_muc.php?sap_xep=xem', sach_ke($pdo, 'xem', 12), true);
    $them('Mới đăng', 'danh_muc.php?sap_xep=moi', sach_ke($pdo, 'moi', 12));
    $them('Sách hội viên', 'danh_muc.php?hinh_thuc=HOI_VIEN', sach_loc($pdo, ['hinh_thuc' => 'HOI_VIEN'], 1, 12)['ds']);
    $them('Sách hiệu sồi', 'danh_muc.php?hinh_thuc=MUA_LE', sach_loc($pdo, ['hinh_thuc' => 'MUA_LE'], 1, 12)['ds']);
    $them('Đọc miễn phí', 'danh_muc.php?hinh_thuc=MIEN_PHI', sach_loc($pdo, ['hinh_thuc' => 'MIEN_PHI'], 1, 12)['ds']);
    $them('Sách điện tử', 'danh_muc.php?dinh_dang=SACH_DIEN_TU', sach_loc($pdo, ['dinh_dang' => 'SACH_DIEN_TU'], 1, 12)['ds']);
    $them('Sách nói', 'danh_muc.php?dinh_dang=SACH_NOI', sach_loc($pdo, ['dinh_dang' => 'SACH_NOI'], 1, 12)['ds']);
    $them('Truyện tranh', 'danh_muc.php?dinh_dang=TRUYEN_TRANH', sach_loc($pdo, ['dinh_dang' => 'TRUYEN_TRANH'], 1, 12)['ds']);
    $them('Điểm đánh giá cao', 'danh_muc.php?sap_xep=diem', sach_ke($pdo, 'diem', 12));
    foreach (the_loai_cay($pdo) as $c) {
        if ($c['ma_the_loai_cha'] !== null) continue;
        $them($c['ten_the_loai'], 'danh_muc.php?the_loai=' . $c['ma_the_loai'], sach_loc($pdo, ['the_loai' => $c['ma_the_loai']], 1, 12)['ds']);
    }
    return $ds;
});

$u = nguoi_dung_hien_tai();
$yeu_thich = [];
if ($u) {
    $st = $pdo->prepare('SELECT ' . SACH_COT . ' ' . SACH_TU . ' JOIN thu_vien tv ON tv.ma_sach = s.ma_sach WHERE tv.ma_nguoi_dung = ? AND tv.yeu_thich = 1 AND s.tinh_trang <> \'TAM_AN\' ORDER BY tv.ngay_them DESC LIMIT 12');
    $st->execute([$u['ma_nguoi_dung']]);
    $yeu_thich = $st->fetchAll();
}

// Banner chỉ có ảnh, tự chạy sau 5 giây: ảnh banner + bìa các sách nổi bật
$noi_bat = [];
foreach ($muc as $m) if ($m[0] === 'Sách hội viên' || $m[0] === 'Mới đăng') { foreach (array_slice($m[2], 0, 4) as $b) $noi_bat[$b['ma_sach']] = $b; }
$noi_bat = array_slice($noi_bat, 0, 6, true);

trang_dau('Thế giới sách của bạn');
?>
<div class="bang-chuyen banner-anh" id="bang-chuyen">
  <a class="bn" href="<?= e(url('danh_muc.php')) ?>"><img src="<?= e(url('HinhSach/banner.jpg')) ?>" alt="Waka"></a>
  <?php foreach ($noi_bat as $b): $anh = url_anh($b['anh_bia']); ?>
  <a class="bn bn-bia" href="<?= e(url('chi_tiet.php?id=' . $b['ma_sach'])) ?>" style="--bia:url('<?= e($anh) ?>')" aria-label="<?= e($b['ten_sach']) ?>"><img src="<?= e($anh) ?>" alt="<?= e($b['ten_sach']) ?>"></a>
  <?php endforeach; ?>
</div>
<div class="cham" id="cham"></div>

<?php if ($yeu_thich): ?>
  <div class="ke-tieu-de"><h2>❤ Yêu thích của bạn</h2><a href="<?= e(url('tu_sach.php?tab=yeu_thich')) ?>">Xem tất cả ›</a></div>
  <div class="hang-ngang"><?php foreach ($yeu_thich as $s) echo the_sach($s); ?></div>
<?php endif; ?>

<?php foreach ($muc as [$tieu_de, $lk, $ds, $hang]): ?>
  <div class="ke-tieu-de"><h2><?= e($tieu_de) ?></h2><a href="<?= e(url($lk)) ?>">Xem tất cả ›</a></div>
  <div class="hang-ngang"><?php foreach ($ds as $i => $s) echo the_sach($s, $hang && $i < 3 ? $i + 1 : null); ?></div>
<?php endforeach; ?>
<?php trang_cuoi();
