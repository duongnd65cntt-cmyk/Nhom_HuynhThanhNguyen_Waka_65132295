<?php
// FR2.5–2.8 – chi tiết sách, lượt xem, đánh giá/bình luận
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/sach.php';
require_once __DIR__ . '/inc/doc_sach.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT s.*, t.ten_tac_gia, tl.ten_the_loai FROM sach s JOIN tac_gia t ON t.ma_tac_gia = s.ma_tac_gia
                     JOIN the_loai tl ON tl.ma_the_loai = s.ma_the_loai WHERE s.ma_sach = ? AND s.tinh_trang <> 'TAM_AN'");
$st->execute([$id]);
$s = $st->fetch();
if (!$s) {
    http_response_code(404);
    trang_dau('Không tìm thấy sách');
    echo '<div class="panel">Không tìm thấy sách này. <a href="' . e(url('index.php')) . '">Về trang chủ</a></div>';
    trang_cuoi();
    exit;
}
$u = nguoi_dung_hien_tai();
$uid = $u ? (int)$u['ma_nguoi_dung'] : 0;

// FR2.7: đánh giá – phải đăng nhập, 1–5 sao, mỗi người 1 đánh giá/sách, chỉ sửa/xóa của mình
$loi = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$u) { flash_dat('Bạn cần đăng nhập để đánh giá.', 'loi'); chuyen_huong(url('dang_nhap.php')); }
    $hd = $_POST['hanh_dong'] ?? '';
    if ($hd === 'luu_danh_gia') {
        $sao = filter_var($_POST['so_sao'] ?? '', FILTER_VALIDATE_INT);
        $bl = trim((string)($_POST['binh_luan'] ?? ''));
        if (!in_array($sao, [1, 2, 3, 4, 5], true)) $loi[] = 'Vui lòng chọn số sao từ 1 đến 5.';
        if (mb_strlen($bl, 'UTF-8') > 2000) $loi[] = 'Bình luận tối đa 2.000 ký tự.';
        if (!$loi) {
            $pdo->prepare('INSERT INTO danh_gia (ma_sach, ma_nguoi_dung, so_sao, binh_luan) VALUES (?,?,?,?)
                           ON DUPLICATE KEY UPDATE so_sao = VALUES(so_sao), binh_luan = VALUES(binh_luan), ngay_chinh_sua = NOW()')
                ->execute([$id, $uid, $sao, $bl ?: null]);
            cache_xoa();
            flash_dat('Đã lưu đánh giá của bạn.');
            chuyen_huong('chi_tiet.php?id=' . $id . '#danh-gia');
        }
    } elseif ($hd === 'xoa_danh_gia') {
        $pdo->prepare('DELETE FROM danh_gia WHERE ma_sach = ? AND ma_nguoi_dung = ?')->execute([$id, $uid]);
        cache_xoa();
        flash_dat('Đã xóa đánh giá.');
        chuyen_huong('chi_tiet.php?id=' . $id . '#danh-gia');
    }
}

// FR2.6: +1 lượt xem, chống đếm lặp bằng session (chỉ khi tải trang bằng GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['da_xem'][$id])) {
    $pdo->prepare('UPDATE sach SET luot_xem = luot_xem + 1 WHERE ma_sach = ?')->execute([$id]);
    $_SESSION['da_xem'][$id] = 1;
    $s['luot_xem']++;
}

$chuongs = $pdo->prepare('SELECT ma_chuong, so_thu_tu, tieu_de, che_do_truy_cap, gia_hat_soi, ngay_dang FROM chuong WHERE ma_sach = ? AND trang_thai = 1 ORDER BY so_thu_tu');
$chuongs->execute([$id]);
$chuongs = $chuongs->fetchAll();
$dg = $pdo->prepare("SELECT d.*, n.ho_ten FROM danh_gia d JOIN nguoi_dung n ON n.ma_nguoi_dung = d.ma_nguoi_dung WHERE d.ma_sach = ? AND d.trang_thai = 'HIEN_THI' ORDER BY d.ngay_danh_gia DESC");
$dg->execute([$id]);
$dg = $dg->fetchAll();
$cua_toi = null; $yeu_thich = false; $trong_tu = false; $tiep = null; $sh = so_huu($pdo, $uid, $id);
$vip = $u && vip_con_han($pdo, $uid);
$trong_gio = [];
if ($u) {
    $st = $pdo->prepare('SELECT * FROM danh_gia WHERE ma_sach = ? AND ma_nguoi_dung = ?'); $st->execute([$id, $uid]); $cua_toi = $st->fetch() ?: null;
    $st = $pdo->prepare('SELECT yeu_thich FROM thu_vien WHERE ma_sach = ? AND ma_nguoi_dung = ?'); $st->execute([$id, $uid]);
    $r = $st->fetch(); $trong_tu = (bool)$r; $yeu_thich = $r && $r['yeu_thich'];
    $tiep = doc_tiep($pdo, $uid, $id);
    $st = $pdo->prepare('SELECT ma_sach, ma_chuong FROM gio_hang WHERE ma_nguoi_dung = ?'); $st->execute([$uid]);
    foreach ($st as $r) $trong_gio[$r['ma_chuong'] ? 'c' . $r['ma_chuong'] : 'b' . $r['ma_sach']] = true;
}
$dau = chuong_dau($pdo, $id);
$gia_cart = function (string $loai, int $ma, string $nhan) use ($u, $trong_gio): string {
    if ($u && !empty($trong_gio[($loai === 'book' ? 'b' : 'c') . $ma])) return '<a class="btn nho xam" href="' . e(url('gio_hang.php')) . '">Trong giỏ ›</a>';
    if (!$u) return '<button type="button" class="btn nho" data-gio-khach="' . $loai . ':' . $ma . '">' . e($nhan) . '</button>';
    return '<form method="post" action="' . e(url('gio_hang.php')) . '">' . csrf_field() . '<input type="hidden" name="action" value="them"><input type="hidden" name="' . ($loai === 'book' ? 'ma_sach' : 'ma_chuong') . '" value="' . $ma . '"><button class="btn nho">' . e($nhan) . '</button></form>';
};
$dinh_dang = ['SACH_DIEN_TU' => 'Sách điện tử', 'SACH_NOI' => 'Sách nói', 'TRUYEN_TRANH' => 'Truyện tranh'][$s['dinh_dang']];
$nut_doc_ten = ['SACH_DIEN_TU' => 'Đọc sách', 'SACH_NOI' => 'Nghe sách', 'TRUYEN_TRANH' => 'Đọc truyện'][$s['dinh_dang']];
$hinh_thuc = ['MIEN_PHI' => 'Miễn phí', 'HOI_VIEN' => 'Hội viên WakaVIP', 'MUA_LE' => 'Mua lẻ'][$s['hinh_thuc_doc']];

// Số liệu hiển thị kiểu Waka
$luot_thich = (int)$pdo->query('SELECT COUNT(*) FROM thu_vien WHERE yeu_thich = 1 AND ma_sach = ' . $id)->fetchColumn();
$xh = $pdo->prepare("SELECT COUNT(*) + 1 FROM sach WHERE dinh_dang = ? AND tinh_trang <> 'TAM_AN' AND luot_xem > ?");
$xh->execute([$s['dinh_dang'], $s['luot_xem']]);
$hang = (int)$xh->fetchColumn();
$phan_bo = array_fill(1, 5, 0);
foreach ($dg as $d) $phan_bo[(int)$d['so_sao']]++;
$tong_dg = count($dg);
$diem_tb = (float)$s['diem_tb'];
$sao_chuoi = function (float $d): string { $n = (int)round($d); return str_repeat('★', $n) . str_repeat('☆', 5 - $n); };
$che_ten = function (string $t): string { $n = mb_strlen($t, 'UTF-8'); return $n <= 4 ? mb_substr($t, 0, 1, 'UTF-8') . '***' : mb_substr($t, 0, 2, 'UTF-8') . '*****' . mb_substr($t, -2, null, 'UTF-8'); };
$truoc_day = function (string $t): string {
    $g = max(0, time() - strtotime($t));
    if ($g < 3600) return max(1, intdiv($g, 60)) . ' phút trước';
    if ($g < 86400) return intdiv($g, 3600) . ' giờ trước';
    if ($g < 86400 * 30) return intdiv($g, 86400) . ' ngày trước';
    return date('d/m/Y', strtotime($t));
};

// Danh sách chương: sắp xếp Mới nhất/Cũ nhất + phân trang 10 chương
$sx = ($_GET['sx'] ?? 'moi') === 'cu' ? 'cu' : 'moi';
$ds_ch = $sx === 'moi' ? array_reverse($chuongs) : $chuongs;
$moi_trang = 10;
$so_trang = max(1, (int)ceil(count($ds_ch) / $moi_trang));
$cp = max(1, min($so_trang, (int)($_GET['cp'] ?? 1)));
$trang_ch = array_slice($ds_ch, ($cp - 1) * $moi_trang, $moi_trang);
$lk_ch = fn(int $p, string $sx2 = '') => '?' . http_build_query(['id' => $id, 'sx' => $sx2 ?: $sx, 'cp' => $p]) . '#chuong';
$cac_trang = array_unique(array_filter([1, 2, 3, 4, 5, $so_trang, $cp - 1, $cp, $cp + 1], fn($p) => $p >= 1 && $p <= $so_trang));
sort($cac_trang);

$goi_re = $vip ? null : $pdo->query('SELECT MIN(gia_vnd) FROM goi_hoi_vien WHERE trang_thai = 1 AND gia_vnd > 0 AND thoi_han_ngay > 0')->fetchColumn();
$svg_tim = '<svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
$svg_sach = '<svg viewBox="0 0 24 24"><path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/></svg>';
$svg_hat = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/><ellipse cx="12" cy="12" rx="11" ry="3.6" transform="rotate(-20 12 12)"/></svg>';

trang_dau($s['ten_sach']);
?>
<div class="ct-nen" style="--bia:url('<?= e(url_anh($s['anh_bia'])) ?>')">
  <nav class="duong-dan"><a href="<?= e(url('index.php')) ?>">Trang chủ</a><span>›</span><?= e($s['ten_sach']) ?></nav>
  <div class="ct-luoi">
    <div class="bia-to"><?php if ($s['hinh_thuc_doc'] === 'MUA_LE'): ?><span class="goc">SỒI</span><?php elseif ($s['hinh_thuc_doc'] === 'HOI_VIEN'): ?><span class="goc" style="background:linear-gradient(90deg,#f5b301,#e08a00)">HỘI VIÊN</span><?php endif; ?>
      <img src="<?= e(url_anh($s['anh_bia'])) ?>" alt="<?= e($s['ten_sach']) ?>"></div>

    <div class="ct-info">
      <h1><?= e($s['ten_sach']) ?></h1>
      <div class="diem"><?= number_format($diem_tb, 1) ?> <span class="sao-v"><?= $sao_chuoi($diem_tb) ?></span> <small>· <?= (int)$s['tong_danh_gia'] ?> đánh giá</small></div>
      <?php if ($hang <= 10): ?><a class="xh-pill" href="<?= e(url('danh_muc.php?dinh_dang=' . $s['dinh_dang'] . '&sap_xep=xem')) ?>"><b>#<?= $hang ?></b> trong Top xu hướng <?= e($dinh_dang) ?> ›</a><?php else: ?><div style="height:18px"></div><?php endif; ?>
      <div class="meta">
        <div><span>Tác giả</span><a href="<?= e(url('danh_muc.php?tac_gia=' . $s['ma_tac_gia'])) ?>"><?= e($s['ten_tac_gia']) ?></a></div>
        <div><span>Thể loại</span><a href="<?= e(url('danh_muc.php?the_loai=' . $s['ma_the_loai'])) ?>"><?= e($s['ten_the_loai']) ?></a></div>
        <div><span>Nhà cung cấp</span><b><?= e($s['nha_xuat_ban'] ?: 'Waka') ?></b></div>
        <div><span>Tình trạng ra</span><b><?= count($chuongs) ?> chương · <?= $s['tinh_trang'] === 'HOAN_THANH' ? 'Hoàn thành' : 'Đang ra' ?></b></div>
        <div><span>Hình thức đọc</span><b><?= e($hinh_thuc) ?><?= $s['hinh_thuc_doc'] === 'MUA_LE' ? ' · ' . so($s['gia_hat_soi']) . ' hạt sồi/cả sách' : '' ?></b></div>
        <div><span>Định dạng</span><b><?= e($dinh_dang) ?><?= $s['nam_xuat_ban'] ? ' · ' . (int)$s['nam_xuat_ban'] : '' ?></b></div>
      </div>
      <div class="so-lieu">
        <div><?= $svg_tim ?>Lượt thích: <?= so($luot_thich) ?></div>
        <div><?= $svg_sach ?>Lượt đọc: <?= $s['luot_xem'] >= 1000 ? number_format($s['luot_xem'] / 1000, 1, ',', '') . ' k' : so($s['luot_xem']) ?></div>
      </div>

      <div class="ct-nut">
        <?php if ($dau): ?>
          <?php if ($tiep): ?><a class="nut-doc" href="<?= e(url('doc.php?c=' . $tiep['ma_chuong'] . '&vt=' . $tiep['vi_tri_cuon'])) ?>"><?= $svg_sach ?>Đọc tiếp</a><a class="nut-phu" href="<?= e(url('doc.php?c=' . $dau)) ?>">Đọc từ đầu</a>
          <?php else: ?><a class="nut-doc" href="<?= e(url('doc.php?c=' . $dau)) ?>"><?= $svg_sach ?><?= e($nut_doc_ten) ?></a><?php endif; ?>
        <?php endif; ?>
        <?php if ($u): ?>
          <form method="post" action="<?= e(url('tu_sach.php')) ?>"><?= csrf_field() ?><input type="hidden" name="ma_sach" value="<?= $id ?>"><input type="hidden" name="quay_lai" value="chi_tiet.php?id=<?= $id ?>">
            <input type="hidden" name="action" value="yeu_thich"><input type="hidden" name="dat" value="<?= $yeu_thich ? 0 : 1 ?>">
            <button class="nut-tron<?= $yeu_thich ? ' on' : '' ?>" title="<?= $yeu_thich ? 'Bỏ yêu thích' : 'Yêu thích' ?>" aria-label="Yêu thích"><?= $svg_tim ?></button></form>
        <?php else: ?>
          <a class="nut-tron" href="<?= e(url('dang_nhap.php')) ?>" title="Đăng nhập để yêu thích" aria-label="Yêu thích"><?= $svg_tim ?></a>
        <?php endif; ?>
        <button type="button" class="nut-tron" data-chia-se title="Chia sẻ" aria-label="Chia sẻ"><svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg></button>
        <?php if ($u && !$trong_tu): ?>
          <form method="post" action="<?= e(url('tu_sach.php')) ?>"><?= csrf_field() ?><input type="hidden" name="ma_sach" value="<?= $id ?>"><input type="hidden" name="quay_lai" value="chi_tiet.php?id=<?= $id ?>"><input type="hidden" name="action" value="them"><button class="nut-phu">+ Tủ sách</button></form>
        <?php endif; ?>
        <?php if ($s['hinh_thuc_doc'] === 'MUA_LE' && (int)$s['gia_hat_soi'] > 0 && !$sh['sach']) echo $gia_cart('book', $id, 'Mua cả sách · ' . so($s['gia_hat_soi']) . ' 🌰'); ?>
        <?php if ($s['hinh_thuc_doc'] === 'HOI_VIEN' && !$vip): ?><a class="nut-phu" href="<?= e(url('hoi_vien.php')) ?>">Đăng ký WakaVIP</a><?php endif; ?>
      </div>
      <div class="mo-ta-ct"><?= e($s['mo_ta'] ?? '') ?></div>
    </div>

    <?php if (!$vip): ?>
    <aside class="uu-dai">
      <h4>ƯU ĐÃI GÓI VIP</h4>
      <div class="trong"><div><b>GÓI WAKAVIP</b><?= $goi_re ? 'Từ ' . so($goi_re) . '₫' : 'Đọc không giới hạn' ?></div><a class="btn-xanh" href="<?= e(url('hoi_vien.php')) ?>">MUA NGAY</a></div>
      <div class="tang">🎁 Đọc không giới hạn sách hội viên</div>
    </aside>
    <?php endif; ?>
  </div>
</div>

<div class="muc-chuong" id="chuong">
  <h2>Danh sách chương (<?= count($chuongs) ?>)</h2>
  <div class="hang-dk">
    <a class="nut-vien" href="<?= e(url('gio_hang.php')) ?>">🛒 Mua nhiều chương</a>
    <a class="sx" href="<?= e($lk_ch(1, $sx === 'moi' ? 'cu' : 'moi')) ?>"><?= $sx === 'moi' ? 'Mới nhất ⇅' : 'Cũ nhất ⇅' ?></a>
  </div>
  <div class="ds-ch">
  <?php foreach ($trang_ch as $c):
      $q = quyen_chuong($c, $u, $vip, $sh);
      $tra = $c['che_do_truy_cap'] === 'TRA_PHI'; ?>
    <div class="ch-dong">
      <a class="ten" href="<?= e(url('doc.php?c=' . $c['ma_chuong'])) ?>" title="<?= e($c['tieu_de']) ?>"><?= e($c['tieu_de']) ?></a>
      <span class="gio"><?= e(date('H:i d-m-Y', strtotime($c['ngay_dang']))) ?></span>
      <span class="gia-s"><?php if ($tra && (int)$c['gia_hat_soi'] > 0): ?><?= so($c['gia_hat_soi']) ?> <?= $svg_hat ?><?php elseif ($c['che_do_truy_cap'] === 'HOI_VIEN'): ?><span style="color:var(--green)">VIP</span><?php else: ?><span style="color:#aaa;font-weight:600">Miễn phí</span><?php endif; ?></span>
      <?php if ($tra && !$q['duoc_doc'] && (int)$c['gia_hat_soi'] > 0 && !$sh['sach']): echo $gia_cart('chapter', (int)$c['ma_chuong'], 'MUA');
      else: ?><a class="doc-ngay<?= $q['duoc_doc'] ? '' : ' khoa-c' ?>" href="<?= e(url('doc.php?c=' . $c['ma_chuong'])) ?>"><?= $q['duoc_doc'] ? 'ĐỌC NGAY' : '🔒 MỞ KHÓA' ?></a><?php endif; ?>
    </div>
  <?php endforeach; if (!$chuongs): ?><p class="muted">Chưa có chương nào.</p><?php endif; ?>
  </div>

  <?php if ($so_trang > 1): ?>
  <nav class="pt2">
    <a class="<?= $cp <= 1 ? 'tat' : '' ?>" href="<?= e($lk_ch(max(1, $cp - 1))) ?>" aria-label="Trang trước">‹</a>
    <?php $truoc = 0; foreach ($cac_trang as $p): if ($truoc && $p - $truoc > 1) echo '<span class="dot">…</span>'; $truoc = $p; ?>
      <a class="<?= $p === $cp ? 'on' : '' ?>" href="<?= e($lk_ch($p)) ?>"><?= $p ?></a>
    <?php endforeach; ?>
    <a class="<?= $cp >= $so_trang ? 'tat' : '' ?>" href="<?= e($lk_ch(min($so_trang, $cp + 1))) ?>" aria-label="Trang sau">›</a>
  </nav>
  <form class="di-toi" method="get" action="#chuong">
    <b>Trang <?= $cp ?>/<?= $so_trang ?></b>
    <input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="sx" value="<?= e($sx) ?>">
    <input type="number" name="cp" min="1" max="<?= $so_trang ?>" placeholder="Đi tới trang">
    <button>Đi</button>
  </form>
  <?php endif; ?>
</div>

<?php $lq = sach_lien_quan($pdo, $s); if ($lq): ?>
<div class="ke-tieu-de"><h2>Sách liên quan</h2></div>
<div class="hang-ngang"><?php foreach ($lq as $x) echo the_sach($x); ?></div>
<?php endif; ?>

<div class="muc-chuong" id="danh-gia">
  <h2>Đánh giá &amp; bình luận</h2>
  <?php hien_loi($loi); ?>
  <div class="dg-tong">
    <div class="so-to"><?= number_format($diem_tb, 1) ?><small><?= (int)$s['tong_danh_gia'] ?> đánh giá</small></div>
    <div class="dg-thanh">
      <?php for ($i = 5; $i >= 1; $i--): $pc = $tong_dg ? round($phan_bo[$i] * 100 / $tong_dg) : 0; ?>
        <span class="st"><?= str_repeat('★', $i) ?></span><div class="nen"><i style="width:<?= $pc ?>%"></i></div>
      <?php endfor; ?>
    </div>
    <div class="dg-viet">
      <?php if ($u): ?><button type="button" class="btn-viet" onclick="document.getElementById('form-dg').hidden=false;this.hidden=true">✎ <?= $cua_toi ? 'Sửa đánh giá' : 'Viết đánh giá' ?></button>
      <?php else: ?><a class="btn-viet" href="<?= e(url('dang_nhap.php')) ?>">✎ Viết đánh giá</a><?php endif; ?>
    </div>
  </div>

  <?php if ($u): ?>
  <div class="form-dg" id="form-dg" <?= $loi ? '' : 'hidden' ?>>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="hanh_dong" value="luu_danh_gia">
      <div class="sao-chon">
        <?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" id="sao<?= $i ?>" name="so_sao" value="<?= $i ?>" <?= (int)($cua_toi['so_sao'] ?? 0) === $i ? 'checked' : '' ?>><label for="sao<?= $i ?>">★</label><?php endfor; ?>
      </div>
      <textarea name="binh_luan" maxlength="2000" placeholder="Chia sẻ cảm nhận của bạn..."><?= e($cua_toi['binh_luan'] ?? '') ?></textarea>
      <?php if ($cua_toi && $cua_toi['trang_thai'] === 'AN'): ?><p class="canh-bao">Đánh giá của bạn đang bị quản trị viên ẩn.</p><?php endif; ?>
      <button class="btn"><?= $cua_toi ? 'Cập nhật đánh giá' : 'Gửi đánh giá' ?></button>
    </form>
    <?php if ($cua_toi): ?><form method="post" data-xacnhan="Xóa đánh giá của bạn?"><?= csrf_field() ?><input type="hidden" name="hanh_dong" value="xoa_danh_gia"><button class="btn nho do" style="margin-top:10px">Xóa đánh giá</button></form><?php endif; ?>
  </div>
  <?php endif; ?>

  <?php foreach ($dg as $d): ?>
    <div class="dg-the">
      <span class="avt"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4.5"/><path d="M3.5 21c0-4.7 3.8-7.5 8.5-7.5s8.5 2.8 8.5 7.5z"/></svg></span>
      <span class="ten-n"><?= e($che_ten($d['ho_ten'])) ?></span><span class="ngay"><?= e($truoc_day($d['ngay_danh_gia'])) ?></span>
      <span class="nd"><?= nl2br(e($d['binh_luan'] ?? '')) ?></span><span class="sao-p"><?= str_repeat('★', (int)$d['so_sao']) ?></span>
    </div>
  <?php endforeach; if (!$dg): ?><p class="muted" style="margin-top:16px">Chưa có đánh giá nào.</p><?php endif; ?>
</div>
<?php trang_cuoi();
