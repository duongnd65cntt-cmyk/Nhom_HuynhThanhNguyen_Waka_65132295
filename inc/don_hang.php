<?php
/**
 * Module 3 – giỏ hàng, mã giảm giá, đơn hàng, thanh toán, kích hoạt quyền lợi.
 * Mọi thao tác tiền/quyền lợi chạy trong transaction; khóa theo thứ tự nguoi_dung → don_hang.
 *
 * Máy trạng thái đơn:
 *   CHO_THANH_TOAN → DA_THANH_TOAN (cuối) | THAT_BAI | DA_HUY (cuối)
 *   THAT_BAI → CHO_THANH_TOAN (thử lại thanh toán, gia hạn thêm 30 phút)
 */
require_once __DIR__ . '/doc_sach.php';

const GOI_NAP = [100000 => 1000, 200000 => 2000, 500000 => 5000];   // VND => hạt sồi
const TEN_LOAI_DON = ['MUA_NOI_DUNG' => 'Mua nội dung', 'NAP_HAT_SOI' => 'Nạp hạt sồi', 'MUA_VIP' => 'Mua WakaVIP'];
const TEN_TRANG_THAI = ['CHO_THANH_TOAN' => 'Chờ thanh toán', 'DA_THANH_TOAN' => 'Đã thanh toán', 'THAT_BAI' => 'Thất bại', 'DA_HUY' => 'Đã hủy'];
const TEN_PHUONG_THUC = ['VI_HAT_SOI' => 'Ví hạt sồi', 'CHUYEN_KHOAN' => 'Chuyển khoản', 'GIA_LAP' => 'Giả lập'];

function dh_don_vi(string $loai): string { return $loai === 'MUA_NOI_DUNG' ? 'hạt sồi' : 'VND'; }
function dh_cho_phep_chuyen(string $tu, string $den): bool {
    return in_array($den, ['CHO_THANH_TOAN' => ['DA_THANH_TOAN', 'THAT_BAI', 'DA_HUY'], 'THAT_BAI' => ['CHO_THANH_TOAN']][$tu] ?? [], true);
}

// ===================== GIỎ HÀNG (FR3.1, FR3.2) =====================
function gio_dong(PDO $pdo, int $uid): array {
    $st = $pdo->prepare('SELECT g.ma_gio_hang, g.ma_sach, g.ma_chuong, s.ma_sach AS sach_id, s.ten_sach, s.hinh_thuc_doc, s.tinh_trang,
            c.tieu_de AS ten_chuong, c.che_do_truy_cap, c.trang_thai AS chuong_tt, COALESCE(c.gia_hat_soi, s.gia_hat_soi, 0) AS don_gia
        FROM gio_hang g LEFT JOIN chuong c ON c.ma_chuong = g.ma_chuong
        LEFT JOIN sach s ON s.ma_sach = COALESCE(g.ma_sach, c.ma_sach) WHERE g.ma_nguoi_dung = ? ORDER BY g.ngay_them DESC');
    $st->execute([$uid]);
    return $st->fetchAll();
}
function gio_dong_hop_le(array $r): bool {
    if ($r['ten_sach'] === null || $r['tinh_trang'] === 'TAM_AN' || (int)$r['don_gia'] <= 0) return false;
    return $r['ma_chuong'] === null ? $r['hinh_thuc_doc'] === 'MUA_LE' : ($r['che_do_truy_cap'] === 'TRA_PHI' && (int)$r['chuong_tt'] === 1);
}
/** Thêm 1 dòng. Trả về null nếu OK, hoặc chuỗi lỗi. $loai: 'book' | 'chapter'. */
function gio_them(PDO $pdo, int $uid, string $loai, int $id): ?string {
    if ($loai === 'book') {
        $st = $pdo->prepare("SELECT ma_sach FROM sach WHERE ma_sach = ? AND hinh_thuc_doc = 'MUA_LE' AND gia_hat_soi > 0 AND tinh_trang <> 'TAM_AN'");
        $st->execute([$id]);
        if (!$st->fetchColumn()) return 'Sách không bán lẻ hoặc không còn tồn tại.';
        $ma_sach = $id;
    } else {
        $st = $pdo->prepare("SELECT c.ma_sach FROM chuong c JOIN sach s ON s.ma_sach = c.ma_sach WHERE c.ma_chuong = ? AND c.che_do_truy_cap = 'TRA_PHI'
                             AND c.gia_hat_soi > 0 AND c.trang_thai = 1 AND s.tinh_trang <> 'TAM_AN'");
        $st->execute([$id]);
        $ma_sach = $st->fetchColumn();
        if (!$ma_sach) return 'Chương không bán hoặc không còn tồn tại.';
    }
    $sh = so_huu($pdo, $uid, (int)$ma_sach);
    if ($sh['sach'] || ($loai === 'chapter' && isset($sh['chuong'][$id]))) return 'Bạn đã sở hữu nội dung này.';
    $co_sach = $pdo->prepare('SELECT 1 FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_sach = ?');
    $co_sach->execute([$uid, $ma_sach]);
    if ($co_sach->fetchColumn()) return $loai === 'book' ? 'Sách đã có trong giỏ.' : 'Cả sách đã có trong giỏ, không cần mua lẻ chương.';
    if ($loai === 'book') {   // mua cả sách thay thế các chương lẻ của sách đó trong giỏ
        $pdo->prepare('DELETE FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_chuong IN (SELECT ma_chuong FROM chuong WHERE ma_sach = ?)')->execute([$uid, $ma_sach]);
        $pdo->prepare('INSERT INTO gio_hang (ma_nguoi_dung, ma_sach) VALUES (?, ?)')->execute([$uid, $ma_sach]);
        return null;
    }
    $dup = $pdo->prepare('SELECT 1 FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_chuong = ?');
    $dup->execute([$uid, $id]);
    if ($dup->fetchColumn()) return 'Chương đã có trong giỏ.';
    $pdo->prepare('INSERT INTO gio_hang (ma_nguoi_dung, ma_chuong) VALUES (?, ?)')->execute([$uid, $id]);
    return null;
}

// ===================== MÃ GIẢM GIÁ (FR3.3) =====================
/** @return array ['loi'=>string] hoặc ['ma'=>row,'giam'=>int,'hat_tang'=>int,'ngay_tang'=>int] */
function dh_kiem_tra_ma(PDO $pdo, int $uid, string $code, string $loai, int $tien, bool $khoa = false): array {
    $st = $pdo->prepare('SELECT * FROM ma_giam_gia WHERE ma_code = ?' . ($khoa ? ' FOR UPDATE' : ''));
    $st->execute([strtoupper(trim($code))]);
    $m = $st->fetch();
    if (!$m || !(int)$m['trang_thai']) return ['loi' => 'Mã giảm giá không tồn tại hoặc đang tắt.'];
    $now = time();
    if (strtotime($m['ngay_bat_dau']) > $now || strtotime($m['ngay_het_han']) < $now) return ['loi' => 'Mã giảm giá chưa có hiệu lực hoặc đã hết hạn.'];
    if (!in_array($m['pham_vi_ap_dung'], ['TAT_CA', $loai], true)) return ['loi' => 'Mã không áp dụng cho loại đơn này.'];
    if ($tien < (int)$m['gia_tri_don_toi_thieu']) return ['loi' => 'Đơn chưa đạt giá trị tối thiểu ' . so($m['gia_tri_don_toi_thieu']) . ' để dùng mã.'];
    if ($m['gioi_han_toan_he'] !== null && (int)$m['so_luot_da_dung'] >= (int)$m['gioi_han_toan_he']) return ['loi' => 'Mã giảm giá đã hết lượt sử dụng.'];
    $c = $pdo->prepare("SELECT COUNT(*) FROM don_hang WHERE ma_nguoi_dung = ? AND ma_giam_gia = ? AND trang_thai IN ('CHO_THANH_TOAN','DA_THANH_TOAN','THAT_BAI')");
    $c->execute([$uid, $m['ma_giam_gia']]);
    if ((int)$c->fetchColumn() >= (int)$m['gioi_han_moi_nguoi']) return ['loi' => 'Bạn đã dùng hết lượt của mã này.'];
    $giam = $hat = $ngay = 0;
    if ($m['loai_giam'] === 'GIAM_TIEN') $giam = min($tien, (int)$m['gia_tri']);
    elseif ($m['loai_giam'] === 'GIAM_PHAN_TRAM') {
        $giam = (int)floor($tien * (float)$m['gia_tri'] / 100);
        if ($m['muc_giam_toi_da'] !== null) $giam = min($giam, (int)$m['muc_giam_toi_da']);
        $giam = min($tien, $giam);
    } elseif ($m['loai_quyen_loi_tang'] === 'HAT_SOI') $hat = (int)$m['gia_tri'];
    elseif ($m['loai_quyen_loi_tang'] === 'NGAY_VIP') {
        if ($loai !== 'MUA_VIP') return ['loi' => 'Mã tặng ngày VIP chỉ áp dụng khi mua gói WakaVIP.'];
        $ngay = (int)$m['gia_tri'];
    }
    if ($tien - $giam <= 0) return ['loi' => 'Mã làm tổng thanh toán bằng 0, không thể áp dụng.'];
    return ['ma' => $m, 'giam' => $giam, 'hat_tang' => $hat, 'ngay_tang' => $ngay];
}

// ===================== ĐẶT HÀNG (FR3.4) =====================
/** Dựng các dòng hàng + tổng tiền cho 1 loại đơn. @return array ['dong'=>[],'tong'=>int] hoặc ['loi'=>] */
function dh_dung_dong(PDO $pdo, int $uid, string $loai, int $goi): array {
    if ($loai === 'NAP_HAT_SOI') {
        if (!isset(GOI_NAP[$goi])) return ['loi' => 'Gói nạp không hợp lệ.'];
        return ['dong' => [['ma_sach' => null, 'ma_chuong' => null, 'ma_goi' => null, 'don_gia' => $goi, 'hat' => GOI_NAP[$goi], 'ten' => number_format(GOI_NAP[$goi], 0, ',', '.') . ' hạt sồi']], 'tong' => $goi];
    }
    if ($loai === 'MUA_VIP') {
        $st = $pdo->prepare('SELECT ma_goi, ten_goi, gia_vnd FROM goi_hoi_vien WHERE ma_goi = ? AND trang_thai = 1 AND gia_vnd > 0 AND thoi_han_ngay > 0');
        $st->execute([$goi]);
        $g = $st->fetch();
        if (!$g) return ['loi' => 'Gói hội viên không tồn tại hoặc đã ngừng bán.'];
        return ['dong' => [['ma_sach' => null, 'ma_chuong' => null, 'ma_goi' => (int)$g['ma_goi'], 'don_gia' => (int)$g['gia_vnd'], 'hat' => 0, 'ten' => $g['ten_goi']]], 'tong' => (int)$g['gia_vnd']];
    }
    if ($loai !== 'MUA_NOI_DUNG') return ['loi' => 'Loại đơn không hợp lệ.'];
    $dong = []; $tong = 0;
    foreach (gio_dong($pdo, $uid) as $r) {
        if (!gio_dong_hop_le($r)) return ['loi' => 'Giỏ có sản phẩm không còn đủ điều kiện mua. Hãy kiểm tra lại giỏ.'];
        $sh = so_huu($pdo, $uid, (int)$r['sach_id']);
        if ($sh['sach'] || ($r['ma_chuong'] !== null && isset($sh['chuong'][(int)$r['ma_chuong']]))) return ['loi' => 'Giỏ có nội dung bạn đã sở hữu. Hãy xóa khỏi giỏ.'];
        $dong[] = ['ma_sach' => $r['ma_chuong'] === null ? (int)$r['ma_sach'] : null, 'ma_chuong' => $r['ma_chuong'] === null ? null : (int)$r['ma_chuong'],
                   'ma_goi' => null, 'don_gia' => (int)$r['don_gia'], 'hat' => 0, 'ten' => $r['ten_sach'] . ($r['ma_chuong'] ? ' – ' . $r['ten_chuong'] : '')];
        $tong += (int)$r['don_gia'];
    }
    return $dong ? ['dong' => $dong, 'tong' => $tong] : ['loi' => 'Giỏ hàng đang trống.'];
}

/** Tạo đơn CHO_THANH_TOAN (hạn 30 phút) cùng chi tiết, giữ chỗ lượt dùng mã. @return array ['ma_don'=>int] | ['loi'=>string] */
function dh_tao(PDO $pdo, int $uid, string $loai, int $goi = 0, string $code = ''): array {
    try {
        $pdo->beginTransaction();
        $pdo->prepare('SELECT ma_nguoi_dung FROM nguoi_dung WHERE ma_nguoi_dung = ? FOR UPDATE')->execute([$uid]);
        $d = dh_dung_dong($pdo, $uid, $loai, $goi);
        if (isset($d['loi'])) { $pdo->rollBack(); return $d; }
        $ma = ['giam' => 0, 'hat_tang' => 0, 'ngay_tang' => 0, 'ma' => null];
        if ($code !== '') {
            $ma = dh_kiem_tra_ma($pdo, $uid, $code, $loai, $d['tong'], true);
            if (isset($ma['loi'])) { $pdo->rollBack(); return $ma; }
        }
        $so = 'DH' . date('YmdHis') . strtoupper(bin2hex(random_bytes(2)));
        $pdo->prepare('INSERT INTO don_hang (so_don_hang, ma_nguoi_dung, loai_don, ma_giam_gia, tong_tien_goc, tien_giam, hat_soi_tang, ngay_vip_tang, trang_thai, han_thanh_toan)
                       VALUES (?,?,?,?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL ' . HAN_THANH_TOAN_PHUT . ' MINUTE))')
            ->execute([$so, $uid, $loai, $ma['ma']['ma_giam_gia'] ?? null, $d['tong'], $ma['giam'], $ma['hat_tang'], $ma['ngay_tang'], 'CHO_THANH_TOAN']);
        $ma_don = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_sach, ma_chuong, ma_goi, don_gia, so_hat_soi_nhan) VALUES (?,?,?,?,?,?)');
        foreach ($d['dong'] as $x) $ins->execute([$ma_don, $x['ma_sach'], $x['ma_chuong'], $x['ma_goi'], $x['don_gia'], $x['hat']]);
        if ($ma['ma']) {
            $up = $pdo->prepare('UPDATE ma_giam_gia SET so_luot_da_dung = so_luot_da_dung + 1 WHERE ma_giam_gia = ? AND (gioi_han_toan_he IS NULL OR so_luot_da_dung < gioi_han_toan_he)');
            $up->execute([$ma['ma']['ma_giam_gia']]);
            if ($up->rowCount() !== 1) throw new RuntimeException('Mã giảm giá vừa hết lượt.');
        }
        $pdo->commit();
        return ['ma_don' => $ma_don];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('dh_tao: ' . $ex->getMessage());
        return ['loi' => $ex instanceof RuntimeException ? $ex->getMessage() : 'Không tạo được đơn hàng. Vui lòng thử lại.'];
    }
}

function dh_lay(PDO $pdo, int $ma_don, ?int $uid = null): ?array {
    $st = $pdo->prepare('SELECT * FROM don_hang WHERE ma_don_hang = ?' . ($uid !== null ? ' AND ma_nguoi_dung = ' . (int)$uid : ''));
    $st->execute([$ma_don]);
    return $st->fetch() ?: null;
}
function dh_chi_tiet(PDO $pdo, int $ma_don): array {
    $st = $pdo->prepare('SELECT ct.*, s.ten_sach, c.tieu_de, cs.ten_sach AS ten_sach_chuong, g.ten_goi FROM chi_tiet_don_hang ct
        LEFT JOIN sach s ON s.ma_sach = ct.ma_sach LEFT JOIN chuong c ON c.ma_chuong = ct.ma_chuong
        LEFT JOIN sach cs ON cs.ma_sach = c.ma_sach LEFT JOIN goi_hoi_vien g ON g.ma_goi = ct.ma_goi WHERE ct.ma_don_hang = ? ORDER BY ct.ma_chi_tiet');
    $st->execute([$ma_don]);
    return array_map(function ($r) {
        $r['ten'] = $r['ten_goi'] ?? ($r['ma_chuong'] ? $r['ten_sach_chuong'] . ' – ' . $r['tieu_de'] : ($r['ten_sach'] ?? ($r['so_hat_soi_nhan'] > 0 ? so($r['so_hat_soi_nhan']) . ' hạt sồi' : 'Sản phẩm')));
        return $r;
    }, $st->fetchAll());
}
function dh_so_tien(array $don): int { return (int)$don['tong_tien_goc'] - (int)$don['tien_giam']; }

// ===================== KÍCH HOẠT (FR3.6) =====================
/** Gọi TRONG transaction, khi đơn đang CHO_THANH_TOAN và đã khóa. Chuyển DA_THANH_TOAN và cấp quyền lợi. */
function dh_kich_hoat(PDO $pdo, array $don): void {
    if (!$pdo->inTransaction()) throw new LogicException('Phải gọi trong transaction.');
    $ma = (int)$don['ma_don_hang']; $uid = (int)$don['ma_nguoi_dung'];
    $pdo->prepare("UPDATE don_hang SET trang_thai = 'DA_THANH_TOAN' WHERE ma_don_hang = ? AND trang_thai = 'CHO_THANH_TOAN'")->execute([$ma]);
    $dong = dh_chi_tiet($pdo, $ma);
    $cong = (int)$don['hat_soi_tang'];
    if ($don['loai_don'] === 'NAP_HAT_SOI') $cong += array_sum(array_column($dong, 'so_hat_soi_nhan'));
    if ($cong > 0) $pdo->prepare('UPDATE nguoi_dung SET so_du_hat_soi = so_du_hat_soi + ? WHERE ma_nguoi_dung = ?')->execute([$cong, $uid]);

    if ($don['loai_don'] === 'MUA_NOI_DUNG') {
        $tl = $pdo->prepare('INSERT INTO thu_vien (ma_nguoi_dung, ma_sach, da_mua) VALUES (?,?,1) ON DUPLICATE KEY UPDATE da_mua = 1');
        $xoa_s = $pdo->prepare('DELETE FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_sach = ?');
        $xoa_c = $pdo->prepare('DELETE FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_chuong = ?');
        foreach ($dong as $x) {
            $sach = $x['ma_sach'] ?: $pdo->query('SELECT ma_sach FROM chuong WHERE ma_chuong = ' . (int)$x['ma_chuong'])->fetchColumn();
            $tl->execute([$uid, $sach]);
            if ($x['ma_chuong']) $xoa_c->execute([$uid, $x['ma_chuong']]); else $xoa_s->execute([$uid, $x['ma_sach']]);
        }
    } elseif ($don['loai_don'] === 'MUA_VIP') {
        $goi = (int)($dong[0]['ma_goi'] ?? 0);
        $dup = $pdo->prepare('SELECT 1 FROM dang_ky_hoi_vien WHERE ma_don_hang = ?'); $dup->execute([$ma]);
        if ($dup->fetchColumn()) return;                                         // idempotent
        $st = $pdo->prepare('SELECT thoi_han_ngay FROM goi_hoi_vien WHERE ma_goi = ?'); $st->execute([$goi]);
        $ngay = (int)$st->fetchColumn();
        if ($ngay <= 0) throw new RuntimeException('Gói hội viên không hợp lệ.');
        // Còn hạn: nối tiếp hạn cũ. Hết hạn: tính từ lúc thanh toán.
        $pdo->prepare('INSERT INTO dang_ky_hoi_vien (ma_nguoi_dung, ma_goi, ma_don_hang, ngay_bat_dau, ngay_het_han)
            SELECT ?, ?, ?, b.bd, DATE_ADD(b.bd, INTERVAL ? DAY)
            FROM (SELECT GREATEST(NOW(), COALESCE(MAX(ngay_het_han), NOW())) AS bd FROM dang_ky_hoi_vien WHERE ma_nguoi_dung = ?) b')
            ->execute([$uid, $goi, $ma, $ngay + (int)$don['ngay_vip_tang'], $uid]);
    }
}

/** Mở transaction, khóa người dùng rồi đơn. Trả về đơn (đã khóa) hoặc null nếu không có. */
function dh_khoa(PDO $pdo, int $ma_don, ?int $uid = null): ?array {
    $st = $pdo->prepare('SELECT ma_nguoi_dung FROM don_hang WHERE ma_don_hang = ?');
    $st->execute([$ma_don]);
    $chu = $st->fetchColumn();
    if ($chu === false || ($uid !== null && (int)$chu !== $uid)) return null;
    $pdo->beginTransaction();
    $pdo->prepare('SELECT ma_nguoi_dung FROM nguoi_dung WHERE ma_nguoi_dung = ? FOR UPDATE')->execute([$chu]);
    $st = $pdo->prepare('SELECT * FROM don_hang WHERE ma_don_hang = ? FOR UPDATE');
    $st->execute([$ma_don]);
    return $st->fetch() ?: null;
}

// ===================== THANH TOÁN (FR3.5) =====================
/** @return array ['ok'=>string,'trang_thai'=>string] | ['loi'=>string] */
function dh_thanh_toan(PDO $pdo, int $ma_don, int $uid, string $pt, string $ket_qua = 'THANH_CONG'): array {
    try {
        $don = dh_khoa($pdo, $ma_don, $uid);
        if (!$don) return ['loi' => 'Không tìm thấy đơn hàng.'];
        if ($don['trang_thai'] === 'THAT_BAI') {       // thử lại
            $pdo->prepare("UPDATE don_hang SET trang_thai = 'CHO_THANH_TOAN', han_thanh_toan = DATE_ADD(NOW(), INTERVAL " . HAN_THANH_TOAN_PHUT . " MINUTE) WHERE ma_don_hang = ?")->execute([$ma_don]);
            $don['trang_thai'] = 'CHO_THANH_TOAN'; $don['han_thanh_toan'] = date('Y-m-d H:i:s', time() + 60 * HAN_THANH_TOAN_PHUT);
        }
        if ($don['trang_thai'] !== 'CHO_THANH_TOAN') { $pdo->rollBack(); return ['loi' => 'Đơn hàng không ở trạng thái chờ thanh toán.']; }
        $het = $pdo->prepare('SELECT han_thanh_toan < NOW() FROM don_hang WHERE ma_don_hang = ?'); $het->execute([$ma_don]);
        if ($het->fetchColumn()) { $pdo->rollBack(); dh_huy($pdo, $ma_don, null, 'Quá hạn thanh toán'); return ['loi' => 'Đơn hàng đã quá hạn thanh toán và bị hủy.']; }
        if (!isset(TEN_PHUONG_THUC[$pt]) || ($pt === 'VI_HAT_SOI' && $don['loai_don'] !== 'MUA_NOI_DUNG')) { $pdo->rollBack(); return ['loi' => 'Phương thức thanh toán không hợp lệ cho đơn này.']; }
        $tien = dh_so_tien($don);
        $ghi = $pdo->prepare('INSERT INTO thanh_toan (ma_don_hang, phuong_thuc, so_tien, trang_thai, ma_giao_dich, noi_dung_chuyen_khoan, ngay_thanh_toan, ghi_chu) VALUES (?,?,?,?,?,?,?,?)');

        if ($pt === 'CHUYEN_KHOAN') {
            $co = $pdo->prepare("SELECT 1 FROM thanh_toan WHERE ma_don_hang = ? AND phuong_thuc = 'CHUYEN_KHOAN' AND trang_thai = 'CHO_XU_LY'"); $co->execute([$ma_don]);
            if (!$co->fetchColumn()) $ghi->execute([$ma_don, $pt, $tien, 'CHO_XU_LY', null, $don['so_don_hang'], null, 'Chờ đối soát chuyển khoản']);
            $pdo->commit();
            return ['ok' => 'Hãy chuyển khoản đúng số tiền và nội dung ' . $don['so_don_hang'] . '. Quản trị viên sẽ xác nhận sau khi nhận tiền.', 'trang_thai' => 'CHO_THANH_TOAN'];
        }
        if ($pt === 'GIA_LAP' && $ket_qua === 'THAT_BAI') {
            $ghi->execute([$ma_don, $pt, $tien, 'THAT_BAI', 'SIM-' . strtoupper(bin2hex(random_bytes(4))), null, null, 'Cổng giả lập: thất bại']);
            $pdo->prepare("UPDATE don_hang SET trang_thai = 'THAT_BAI' WHERE ma_don_hang = ?")->execute([$ma_don]);
            $pdo->commit();
            return ['loi' => 'Thanh toán thất bại. Bạn có thể thử lại.', 'trang_thai' => 'THAT_BAI'];
        }
        if ($pt === 'VI_HAT_SOI') {
            $tr = $pdo->prepare('UPDATE nguoi_dung SET so_du_hat_soi = so_du_hat_soi - ? WHERE ma_nguoi_dung = ? AND so_du_hat_soi >= ?');
            $tr->execute([$tien, $uid, $tien]);
            if ($tr->rowCount() !== 1) { $pdo->rollBack(); return ['loi' => 'Số dư hạt sồi không đủ. Hãy nạp thêm.']; }
            $ghi->execute([$ma_don, $pt, $tien, 'THANH_CONG', null, null, date('Y-m-d H:i:s'), 'Trừ trực tiếp từ ví hạt sồi']);
        } else {
            $ghi->execute([$ma_don, $pt, $tien, 'THANH_CONG', 'SIM-' . strtoupper(bin2hex(random_bytes(4))), null, date('Y-m-d H:i:s'), 'Cổng thanh toán giả lập']);
        }
        dh_kich_hoat($pdo, $don);
        $pdo->commit();
        return ['ok' => 'Thanh toán thành công!', 'trang_thai' => 'DA_THANH_TOAN'];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('dh_thanh_toan: ' . $ex->getMessage());
        return ['loi' => 'Không thể xử lý thanh toán lúc này.'];
    }
}

/** Admin duyệt chuyển khoản thủ công (FR5.3): ghi ma_nguoi_duyet, ngay_duyet. */
function dh_duyet_chuyen_khoan(PDO $pdo, int $ma_don, int $admin, bool $dong_y): array {
    try {
        $don = dh_khoa($pdo, $ma_don);
        if (!$don || $don['trang_thai'] !== 'CHO_THANH_TOAN') { if ($pdo->inTransaction()) $pdo->rollBack(); return ['loi' => 'Đơn không ở trạng thái chờ thanh toán.']; }
        $st = $pdo->prepare("SELECT ma_thanh_toan FROM thanh_toan WHERE ma_don_hang = ? AND phuong_thuc = 'CHUYEN_KHOAN' AND trang_thai = 'CHO_XU_LY' ORDER BY ma_thanh_toan DESC LIMIT 1 FOR UPDATE");
        $st->execute([$ma_don]);
        $tt = $st->fetchColumn();
        if (!$tt) { $pdo->rollBack(); return ['loi' => 'Khách chưa chọn chuyển khoản cho đơn này.']; }
        if ($dong_y) {
            $pdo->prepare("UPDATE thanh_toan SET trang_thai = 'THANH_CONG', ma_nguoi_duyet = ?, ngay_duyet = NOW(), ngay_thanh_toan = NOW(), ghi_chu = 'Admin duyệt thủ công' WHERE ma_thanh_toan = ?")->execute([$admin, $tt]);
            dh_kich_hoat($pdo, $don);
        } else {
            $pdo->prepare("UPDATE thanh_toan SET trang_thai = 'THAT_BAI', ma_nguoi_duyet = ?, ngay_duyet = NOW(), ghi_chu = 'Admin từ chối: không nhận được tiền' WHERE ma_thanh_toan = ?")->execute([$admin, $tt]);
            $pdo->prepare("UPDATE don_hang SET trang_thai = 'THAT_BAI' WHERE ma_don_hang = ?")->execute([$ma_don]);
        }
        $pdo->commit();
        return ['ok' => $dong_y ? 'Đã duyệt thanh toán và kích hoạt quyền lợi.' : 'Đã đánh dấu thất bại.'];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('dh_duyet: ' . $ex->getMessage());
        return ['loi' => 'Không thể cập nhật đơn hàng.'];
    }
}

// ===================== HỦY (FR3.7) =====================
/** Hủy đơn CHO_THANH_TOAN, hoàn lượt dùng mã. $uid = null khi hệ thống/admin hủy. */
function dh_huy(PDO $pdo, int $ma_don, ?int $uid, string $ly_do): array {
    try {
        $don = dh_khoa($pdo, $ma_don, $uid);
        if (!$don || $don['trang_thai'] !== 'CHO_THANH_TOAN') { if ($pdo->inTransaction()) $pdo->rollBack(); return ['loi' => 'Chỉ hủy được đơn đang chờ thanh toán.']; }
        $pdo->prepare("UPDATE don_hang SET trang_thai = 'DA_HUY', ly_do_huy = ?, ngay_huy = NOW() WHERE ma_don_hang = ?")->execute([mb_substr($ly_do, 0, 255), $ma_don]);
        $pdo->prepare("UPDATE thanh_toan SET trang_thai = 'THAT_BAI', ghi_chu = 'Đơn bị hủy' WHERE ma_don_hang = ? AND trang_thai = 'CHO_XU_LY'")->execute([$ma_don]);
        if ($don['ma_giam_gia'] !== null) $pdo->prepare('UPDATE ma_giam_gia SET so_luot_da_dung = GREATEST(so_luot_da_dung - 1, 0) WHERE ma_giam_gia = ?')->execute([$don['ma_giam_gia']]);
        $pdo->commit();
        return ['ok' => 'Đã hủy đơn hàng.'];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('dh_huy: ' . $ex->getMessage());
        return ['loi' => 'Không thể hủy đơn hàng.'];
    }
}
/** Đơn quá hạn tự hủy – gọi từ cron/huy_don_qua_han.php và lười (lazy) ở các trang đơn hàng. */
function dh_huy_qua_han(PDO $pdo): int {
    $n = 0;
    $ds = $pdo->query("SELECT ma_don_hang FROM don_hang WHERE trang_thai = 'CHO_THANH_TOAN' AND han_thanh_toan IS NOT NULL AND han_thanh_toan < NOW()")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ds as $id) if (isset(dh_huy($pdo, (int)$id, null, 'Quá hạn thanh toán')['ok'])) $n++;
    return $n;
}
