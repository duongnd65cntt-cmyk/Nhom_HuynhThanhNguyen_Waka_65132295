<?php
// FR3.1 / FR3.2 – giỏ hàng. Khách dùng localStorage (app.js), đăng nhập thì đồng bộ lên CSDL.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/inc/don_hang.php';

$u = nguoi_dung_hien_tai();
$parse = function (string $k): ?array {
    return preg_match('/^(book|chapter):([1-9][0-9]*)$/', $k, $m) ? [$m[1], (int)$m[2]] : null;
};

// Khách: tra thông tin các khóa trong localStorage
if (($_GET['action'] ?? '') === 'guest_items') {
    $keys = json_decode((string)($_GET['ids'] ?? '[]'), true);
    if (!is_array($keys) || count($keys) > 50) json_ra(['loi' => 'Dữ liệu không hợp lệ'], 400);
    $items = [];
    foreach (array_unique(array_filter($keys, 'is_string')) as $k) {
        if (!($p = $parse($k))) continue;
        if ($p[0] === 'book') $st = $pdo->prepare("SELECT ten_sach AS ten, 'Trọn sách' AS ct, gia_hat_soi AS gia FROM sach WHERE ma_sach = ? AND hinh_thuc_doc = 'MUA_LE' AND gia_hat_soi > 0 AND tinh_trang <> 'TAM_AN'");
        else $st = $pdo->prepare("SELECT s.ten_sach AS ten, CONCAT('Chương: ', c.tieu_de) AS ct, c.gia_hat_soi AS gia FROM chuong c JOIN sach s ON s.ma_sach = c.ma_sach
                                  WHERE c.ma_chuong = ? AND c.che_do_truy_cap = 'TRA_PHI' AND c.gia_hat_soi > 0 AND c.trang_thai = 1 AND s.tinh_trang <> 'TAM_AN'");
        $st->execute([$p[1]]);
        if ($r = $st->fetch()) $items[] = ['key' => $k, 'ten' => $r['ten'], 'ct' => $r['ct'], 'gia' => (int)$r['gia']];
    }
    json_ra(['items' => $items]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'dong_bo') {                         // khách vừa đăng nhập: gộp giỏ localStorage
        if (!$u) json_ra(['loi' => 'Chưa đăng nhập'], 401);
        $keys = json_decode((string)($_POST['items'] ?? '[]'), true);
        if (!is_array($keys) || count($keys) > 50) json_ra(['loi' => 'Dữ liệu không hợp lệ'], 400);
        $xong = []; $tu_choi = [];
        foreach (array_unique(array_filter($keys, 'is_string')) as $k) {
            $p = $parse($k);
            if (!$p) { $tu_choi[] = $k; $xong[] = $k; continue; }
            $loi = gio_them($pdo, (int)$u['ma_nguoi_dung'], $p[0], $p[1]);
            if ($loi) $tu_choi[] = $k;
            $xong[] = $k;
        }
        $n = $pdo->prepare('SELECT COUNT(*) FROM gio_hang WHERE ma_nguoi_dung = ?'); $n->execute([$u['ma_nguoi_dung']]);
        json_ra(['processed' => $xong, 'rejected' => $tu_choi, 'so_dong' => (int)$n->fetchColumn()]);
    }
    $u = yeu_cau_dang_nhap();
    $uid = (int)$u['ma_nguoi_dung'];
    if ($act === 'them') {
        $loi = !empty($_POST['ma_chuong']) ? gio_them($pdo, $uid, 'chapter', (int)$_POST['ma_chuong']) : gio_them($pdo, $uid, 'book', (int)($_POST['ma_sach'] ?? 0));
        flash_dat($loi ?? 'Đã thêm vào giỏ hàng.', $loi ? 'loi' : 'ok');
    } elseif ($act === 'xoa') {
        $pdo->prepare('DELETE FROM gio_hang WHERE ma_gio_hang = ? AND ma_nguoi_dung = ?')->execute([(int)($_POST['ma_gio_hang'] ?? 0), $uid]);
    } elseif ($act === 'xoa_het') {
        $pdo->prepare('DELETE FROM gio_hang WHERE ma_nguoi_dung = ?')->execute([$uid]);
    }
    chuyen_huong('gio_hang.php');
}

trang_dau('Giỏ hàng');
?>
<h1>Giỏ hàng</h1>
<?php if ($u):
    $dong = gio_dong($pdo, (int)$u['ma_nguoi_dung']); $tong = 0; $ok = true; ?>
  <?php if (!$dong): ?><div class="panel"><p class="muted">Giỏ hàng trống.</p><a class="btn" href="<?= e(url('danh_muc.php?hinh_thuc=MUA_LE')) ?>">Xem sách mua lẻ</a></div>
  <?php else: ?>
  <div class="panel">
    <?php foreach ($dong as $r): $hl = gio_dong_hop_le($r); $ok = $ok && $hl; if ($hl) $tong += (int)$r['don_gia']; ?>
      <div class="tong" style="font-size:15px;border-top:0;border-bottom:1px solid var(--line)">
        <span><b><?= e($r['ten_sach'] ?? 'Sản phẩm không còn tồn tại') ?></b><br><small><?= $r['ma_chuong'] ? 'Chương: ' . e($r['ten_chuong']) : 'Trọn sách' ?><?= $hl ? '' : ' · <span style="color:var(--red)">không còn bán</span>' ?></small></span>
        <span><span class="gia"><?= so($r['don_gia']) ?> 🌰</span>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="xoa"><input type="hidden" name="ma_gio_hang" value="<?= (int)$r['ma_gio_hang'] ?>"><button class="btn nho xam">Xóa</button></form></span>
      </div>
    <?php endforeach; ?>
    <div class="tong"><span>Tạm tính</span><span class="gia"><?= so($tong) ?> hạt sồi</span></div>
    <div class="thanh">
      <form method="post" data-xacnhan="Xóa toàn bộ giỏ hàng?"><?= csrf_field() ?><input type="hidden" name="action" value="xoa_het"><button class="btn xam">Xóa tất cả</button></form>
      <?php if ($ok): ?><a class="btn" href="<?= e(url('dat_hang.php?loai=MUA_NOI_DUNG')) ?>">Đặt hàng ›</a><?php else: ?><span class="canh-bao">Hãy xóa sản phẩm không còn bán để đặt hàng.</span><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
<?php else: ?>
  <div class="panel" id="gio-khach"><p class="muted">Đang tải giỏ hàng...</p></div>
  <script>
  (() => {
    const KEY = 'waka_guest_cart', box = document.getElementById('gio-khach'), base = document.body.dataset.base;
    const get = () => { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch { return []; } };
    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    async function ve() {
      const keys = get();
      if (!keys.length) { box.innerHTML = '<p class="muted">Giỏ hàng trống.</p>'; return; }
      const d = await fetch(base + '/gio_hang.php?action=guest_items&ids=' + encodeURIComponent(JSON.stringify(keys))).then(r => r.json());
      localStorage.setItem(KEY, JSON.stringify(d.items.map(i => i.key)));
      let tong = 0;
      box.innerHTML = d.items.map(i => { tong += i.gia; return `<div class="tong" style="font-size:15px;border-top:0;border-bottom:1px solid var(--line)"><span><b>${esc(i.ten)}</b><br><small>${esc(i.ct)}</small></span><span><span class="gia">${i.gia.toLocaleString('vi-VN')} 🌰</span> <button class="btn nho xam" data-k="${esc(i.key)}">Xóa</button></span></div>`; }).join('')
        + `<div class="tong"><span>Tạm tính</span><span class="gia">${tong.toLocaleString('vi-VN')} hạt sồi</span></div><p class="canh-bao">Hãy đăng nhập để đặt hàng – giỏ của bạn sẽ được giữ lại.</p><a class="btn" href="${base}/dang_nhap.php">Đăng nhập để đặt hàng</a>`;
      const b = document.getElementById('so-gio'); if (b) b.textContent = d.items.length || '';
    }
    box.addEventListener('click', e => { const k = e.target.dataset.k; if (k) { localStorage.setItem(KEY, JSON.stringify(get().filter(x => x !== k))); ve(); } });
    ve();
  })();
  </script>
<?php endif; ?>
<?php trang_cuoi();
