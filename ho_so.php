<?php
// FR1.5 – Quản lý hồ sơ (phải đăng nhập)
require_once 'auth.php';
require_once 'auth_giao_dien.php';

$u = yeu_cau_dang_nhap();
$loi = [];
$thong_bao = flash_lay();
$vip = vip_trang_thai($pdo, (int)$u['ma_nguoi_dung']);

$v = [
    'ho_ten'        => $u['ho_ten'],
    'so_dien_thoai' => $u['so_dien_thoai'] ?? '',
    'ngay_sinh'     => $u['ngay_sinh'] ?? '',
    'gioi_tinh'     => $u['gioi_tinh'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v['ho_ten']        = trim((string)($_POST['ho_ten'] ?? ''));
    $v['so_dien_thoai'] = preg_replace('/[\s.\-]/', '', (string)($_POST['so_dien_thoai'] ?? ''));
    $v['ngay_sinh']     = trim((string)($_POST['ngay_sinh'] ?? ''));
    $v['gioi_tinh']     = (string)($_POST['gioi_tinh'] ?? '');

    if ($v['ho_ten'] === '' || mb_strlen($v['ho_ten'], 'UTF-8') > 150) {
        $loi[] = 'Họ tên là bắt buộc (tối đa 150 ký tự).';
    }
    if ($v['so_dien_thoai'] !== '' && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $v['so_dien_thoai'])) {
        $loi[] = 'Số điện thoại không hợp lệ.';
    }
    if ($v['ngay_sinh'] !== '') {
        $d = DateTime::createFromFormat('!Y-m-d', $v['ngay_sinh']);
        if (!$d || $d->format('Y-m-d') !== $v['ngay_sinh'] || $d > new DateTime('today') || (int)$d->format('Y') < 1900) {
            $loi[] = 'Ngày sinh không hợp lệ.';
        }
    }
    if ($v['gioi_tinh'] !== '' && !in_array($v['gioi_tinh'], ['NAM', 'NU', 'KHAC'], true)) {
        $loi[] = 'Giới tính không hợp lệ.';
    }

    // Ảnh đại diện: ≤ 2MB, jpg/png/webp (kiểm tra nội dung thật, không tin đuôi file/Content-Type của client)
    $anh_moi = null;
    $f = $_FILES['anh_dai_dien'] ?? null;
    if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > ANH_MAX_BYTE) {
            $loi[] = 'Ảnh đại diện tối đa 2MB.';
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            $loi[] = 'Tải ảnh lên thất bại, hãy thử lại.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $duoi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
            if (!$duoi || @getimagesize($f['tmp_name']) === false) {
                $loi[] = 'Ảnh đại diện chỉ chấp nhận định dạng JPG, PNG hoặc WEBP.';
            } elseif (!$loi) {
                $dir = __DIR__ . '/uploads/avatar';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $ten = 'u' . $u['ma_nguoi_dung'] . '_' . bin2hex(random_bytes(8)) . '.' . $duoi;
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $ten)) {
                    $anh_moi = 'uploads/avatar/' . $ten;
                } else {
                    $loi[] = 'Không lưu được ảnh đại diện.';
                }
            }
        }
    }

    if (!$loi) {
        // CHỈ cập nhật các cột cho phép – không đụng tới vai_tro, so_du_hat_soi, trang_thai…
        $pdo->prepare(
            'UPDATE nguoi_dung
             SET ho_ten = ?, so_dien_thoai = ?, ngay_sinh = ?, gioi_tinh = ?, anh_dai_dien = COALESCE(?, anh_dai_dien)
             WHERE ma_nguoi_dung = ?'
        )->execute([
            $v['ho_ten'],
            $v['so_dien_thoai'] ?: null,
            $v['ngay_sinh'] ?: null,
            $v['gioi_tinh'] ?: null,
            $anh_moi,
            $u['ma_nguoi_dung'],
        ]);

        // Xóa ảnh cũ do hệ thống tạo
        if ($anh_moi && $u['anh_dai_dien'] && preg_match('#^uploads/avatar/[A-Za-z0-9_.]+$#', $u['anh_dai_dien'])) {
            @unlink(__DIR__ . '/' . $u['anh_dai_dien']);
        }
        flash_dat('Đã cập nhật hồ sơ.');
        chuyen_huong('ho_so.php');
    }
}

auth_dau_trang('Hồ sơ cá nhân', '560px');
$anh = $u['anh_dai_dien'] ?: '';
?>
<h1>Hồ sơ cá nhân</h1>
<?php if ($thong_bao): ?><div class="ok"><?= e($thong_bao) ?></div><?php endif; ?>
<?php hien_loi($loi); ?>
<form method="POST" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <?php if ($anh): ?><img class="avatar" src="<?= e(url_anh($anh)) ?>" alt="Ảnh đại diện"><?php else: ?><div class="avatar"></div><?php endif; ?>
    <label for="anh_dai_dien">Ảnh đại diện (JPG/PNG/WEBP, tối đa 2MB)</label>
    <input type="file" id="anh_dai_dien" name="anh_dai_dien" accept="image/jpeg,image/png,image/webp">

    <div class="hang2">
        <div><label>Tên đăng nhập</label><input type="text" value="<?= e($u['ten_dang_nhap']) ?>" readonly></div>
        <div><label>Vai trò</label><input type="text" value="<?= $u['vai_tro'] === 'QUAN_TRI' ? 'Quản trị viên' : 'Thành viên' ?>" readonly></div>
    </div>
    <label>Trạng thái WakaVIP</label>
    <input type="text" value="<?= $vip['ngay_het_han'] ? ($vip['trang_thai'] === 'HET_HAN' ? 'Đã hết hạn ' : 'Còn hạn đến ') . date('d/m/Y H:i', strtotime($vip['ngay_het_han'])) : 'Chưa đăng ký WakaVIP' ?>" readonly>
    <div class="hang2">
        <div><label>Email</label><input type="text" value="<?= e($u['email']) ?>" readonly></div>
        <div><label>Số dư hạt sồi</label><input type="text" value="<?= number_format((float)$u['so_du_hat_soi']) ?>" readonly></div>
    </div>

    <label for="ho_ten">Họ và tên</label>
    <input type="text" id="ho_ten" name="ho_ten" value="<?= e($v['ho_ten']) ?>" maxlength="150" required>

    <div class="hang2">
        <div>
            <label for="so_dien_thoai">Số điện thoại</label>
            <input type="tel" id="so_dien_thoai" name="so_dien_thoai" value="<?= e($v['so_dien_thoai']) ?>" maxlength="20">
        </div>
        <div>
            <label for="ngay_sinh">Ngày sinh</label>
            <input type="date" id="ngay_sinh" name="ngay_sinh" value="<?= e($v['ngay_sinh']) ?>" max="<?= date('Y-m-d') ?>">
        </div>
    </div>

    <label for="gioi_tinh">Giới tính</label>
    <select id="gioi_tinh" name="gioi_tinh">
        <option value="">— Không chọn —</option>
        <?php foreach (['NAM' => 'Nam', 'NU' => 'Nữ', 'KHAC' => 'Khác'] as $k => $t): ?>
            <option value="<?= $k ?>" <?= $v['gioi_tinh'] === $k ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn">Lưu thay đổi</button>
</form>
<div class="phu"><a href="hoi_vien.php">Gói WakaVIP</a> · <a href="nap_hat_soi.php">Nạp hạt sồi</a> · <a href="doi_mat_khau.php">Đổi mật khẩu</a></div>
<?php auth_cuoi_trang();
