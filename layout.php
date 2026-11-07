<?php
/** Giao diện dùng chung: đầu/cuối trang, thẻ sách, phân trang. */

function the_loai_cay(PDO $pdo): array {
    return cache_lay('the_loai', 300, fn() => $pdo->query('SELECT ma_the_loai, ten_the_loai, ma_the_loai_cha FROM the_loai WHERE trang_thai = 1 ORDER BY thu_tu, ten_the_loai')->fetchAll());
}

function trang_dau(string $tieu_de, array $o = []): void {
    global $pdo;
    $u = nguoi_dung_hien_tai();
    $cats = array_filter(the_loai_cay($pdo), fn($c) => $c['ma_the_loai_cha'] === null);
    $so_gio = 0;
    if ($u) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM gio_hang WHERE ma_nguoi_dung = ?');
        $st->execute([$u['ma_nguoi_dung']]);
        $so_gio = (int)$st->fetchColumn();
    }
    ?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($tieu_de) ?> - Waka</title>
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
<?php foreach ($o['css'] ?? [] as $c): ?><link rel="stylesheet" href="<?= e(url($c)) ?>"><?php endforeach; ?>
</head>
<body class="<?= e($o['body'] ?? '') ?>" data-base="<?= e(url('')) ?>" data-csrf="<?= e(csrf_token()) ?>" data-login="<?= $u ? 1 : 0 ?>">
<header class="top">
  
  <!-- Layout mới: Dùng trực tiếp hình ảnh Logo và Cờ để đều nhau tăm tắp -->
  <a href="<?= e(url('index.php')) ?>" class="logo" style="display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
      <img src="https://waka.vn/images/logo.png" alt="WAKA" style="height: 28px; width: auto; display: block;">
      <img src="https://waka.vn/images/vn_flag.gif" alt="Cờ Việt Nam" style="height: 28px; width: auto; display: block;">
  </a>

  <nav class="menu">
    <a href="<?= e(url('index.php')) ?>">Trang chủ</a>
    <a href="<?= e(url('danh_muc.php')) ?>">Tất cả sách</a>
    <?php foreach ($cats as $c): ?><a href="<?= e(url('danh_muc.php?the_loai=' . $c['ma_the_loai'])) ?>"><?= e($c['ten_the_loai']) ?></a><?php endforeach; ?>
    <a href="<?= e(url('hoi_vien.php')) ?>" class="vip">WakaVIP</a>
  </nav>
  <div class="actions">
    <form action="<?= e(url('tim_kiem.php')) ?>" method="get" class="search" autocomplete="off">
      <input type="text" name="q" id="o-tim" placeholder="Tìm sách, tác giả..." value="<?= e($_GET['q'] ?? '') ?>">
      <button aria-label="Tìm">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: #fff; display: block;">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
</button>
      <div id="goi-y" class="goi-y-box" hidden></div>
    </form>
    <a href="<?= e(url('gio_hang.php')) ?>" class="btn-mini">🛒 <span id="so-gio"><?= $so_gio ?: '' ?></span></a>
    <?php if (!$u): ?>
      <a href="<?= e(url('dang_nhap.php')) ?>" class="btn-line">Đăng nhập</a>
      <a href="<?= e(url('dang_ky.php')) ?>" class="btn">Đăng ký</a>
    <?php else: ?>
      <details class="acc">
        <summary><?php if ($u['anh_dai_dien']): ?><img src="<?= e(url_anh($u['anh_dai_dien'])) ?>" alt=""><?php else: ?><span>👤</span><?php endif; ?></summary>
        <div class="acc-list">
          <div class="acc-info"><b><?= e($u['ho_ten']) ?></b><small>🌰 <?= so($u['so_du_hat_soi']) ?> hạt sồi</small></div>
          <a href="<?= e(url('ho_so.php')) ?>">Hồ sơ cá nhân</a>
          <a href="<?= e(url('tu_sach.php')) ?>">Tủ sách</a>
          <a href="<?= e(url('lich_su_doc.php')) ?>">Lịch sử đọc</a>
          <a href="<?= e(url('dau_trang.php')) ?>">Dấu trang</a>
          <a href="<?= e(url('hoi_vien.php')) ?>">Gói WakaVIP</a>
          <a href="<?= e(url('nap_hat_soi.php')) ?>">Nạp hạt sồi</a>
          <a href="<?= e(url('lich_su_don_hang.php')) ?>">Đơn hàng</a>
          <?php if ($u['vai_tro'] === 'QUAN_TRI'): ?><a href="<?= e(url('admin/index.php')) ?>">Trang quản trị</a><?php endif; ?>
          <form action="<?= e(url('dang_xuat.php')) ?>" method="post"><?= csrf_field() ?><button>Đăng xuất</button></form>
        </div>
      </details>
    <?php endif; ?>
  </div>
</header>
<main class="<?= e($o['main'] ?? 'wrap') ?>">
<?php if ($o['flash'] ?? true) hien_flash(); ?>
<?php
}

function trang_cuoi(array $o = []): void {
    ?>
</main>
<footer class="foot">© Waka – đồ án website đọc sách, truyện &amp; sách nói</footer>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php foreach ($o['js'] ?? [] as $j): ?><script src="<?= e(url($j)) ?>"></script><?php endforeach; ?>
</body></html>
<?php
}

function nhan_hinh_thuc(array $s): string {
    return match ($s['hinh_thuc_doc']) {
        'HOI_VIEN' => '<span class="badge vip">HỘI VIÊN</span>',
        'MUA_LE' => '<span class="badge retail">' . so($s['gia_hat_soi']) . ' 🌰</span>',
        default => '<span class="badge">MIỄN PHÍ</span>',
    };
}

function the_sach(array $s, ?int $hang = null): string {
    $diem = (int)($s['tong_danh_gia'] ?? 0) > 0 ? '★ ' . number_format((float)$s['diem_tb'], 1) . ' <small>(' . (int)$s['tong_danh_gia'] . ')</small>' : '<small>Chưa có đánh giá</small>';
    return '<a class="card-sach" href="' . e(url('chi_tiet.php?id=' . $s['ma_sach'])) . '">'
        . '<div class="bia">' . nhan_hinh_thuc($s) . ($hang ? '<span class="xh">#' . $hang . '</span>' : '')
        . '<img loading="lazy" src="' . e(url_anh($s['anh_bia'])) . '" alt="' . e($s['ten_sach']) . '"></div>'
        . '<div class="ten">' . e($s['ten_sach']) . '</div>'
        . '<div class="tg">' . e($s['ten_tac_gia'] ?? '') . '</div><div class="sao">' . $diem . '</div></a>';
}

/** Thanh phân trang giữ nguyên bộ lọc ($query là mảng tham số hiện tại). */
function phan_trang(int $tong, int $trang, int $moi, array $query): string {
    $n = (int)ceil($tong / $moi);
    if ($n <= 1) return '';
    unset($query['trang']);
    $lk = fn(int $p, string $t, string $cls = '') => '<a class="' . $cls . '" href="?' . e(http_build_query($query + ['trang' => $p])) . '">' . $t . '</a>';
    $h = '<nav class="pt">';
    if ($trang > 1) $h .= $lk($trang - 1, '‹');
    for ($p = max(1, $trang - 2); $p <= min($n, $trang + 2); $p++) $h .= $lk($p, (string)$p, $p === $trang ? 'on' : '');
    if ($trang < $n) $h .= $lk($trang + 1, '›');
    return $h . '</nav>';
}

function hien_loi(array $loi): void {
    if (!$loi) return;
    echo '<div class="loi"><ul>';
    foreach ($loi as $l) echo '<li>' . e($l) . '</li>';
    echo '</ul></div>';
}