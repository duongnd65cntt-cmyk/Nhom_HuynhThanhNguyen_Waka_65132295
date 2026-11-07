<?php
/** Module 2 – truy vấn sách, lọc, tìm kiếm. Chỉ trả sách có tình trạng <> TAM_AN. */

const SACH_COT = 's.ma_sach, s.ten_sach, s.anh_bia, s.hinh_thuc_doc, s.gia_hat_soi, s.diem_tb, s.tong_danh_gia, s.luot_xem, s.dinh_dang, s.tinh_trang, t.ten_tac_gia';
const SACH_TU = "FROM sach s JOIN tac_gia t ON t.ma_tac_gia = s.ma_tac_gia";

/** Thể loại + toàn bộ thể loại con (đa cấp). */
function the_loai_con_ids(PDO $pdo, int $id): array {
    $ds = [$id];
    $all = the_loai_cay($pdo);
    for ($i = 0; $i < count($ds); $i++) {
        foreach ($all as $c) if ((int)$c['ma_the_loai_cha'] === $ds[$i] && !in_array((int)$c['ma_the_loai'], $ds, true)) $ds[] = (int)$c['ma_the_loai'];
    }
    return $ds;
}

/** Tìm không dấu, không phân biệt hoa thường: mỗi từ phải có trong cột tu_khoa. */
function sach_dieu_kien_tu_khoa(string $q, array &$tham_so, string $cot = 's.tu_khoa'): string {
    $w = [];
    foreach (array_filter(explode(' ', bo_dau($q))) as $i => $tu) {
        $tham_so[":k$i"] = '%' . addcslashes($tu, '%_\\') . '%';
        $w[] = "$cot LIKE :k$i";
    }
    return $w ? implode(' AND ', $w) : '1=1';
}

/** FR2.2/2.3: lọc đồng thời + sắp xếp + phân trang. $f: q, the_loai, tac_gia, dinh_dang, hinh_thuc, tinh_trang, sap_xep */
function sach_loc(PDO $pdo, array $f, int $trang, int $moi = MOI_TRANG): array {
    $w = ["s.tinh_trang <> 'TAM_AN'"]; $p = [];
    if (($f['q'] ?? '') !== '') $w[] = sach_dieu_kien_tu_khoa($f['q'], $p);
    if (!empty($f['the_loai'])) {
        $ids = the_loai_con_ids($pdo, (int)$f['the_loai']);
        $w[] = 's.ma_the_loai IN (' . implode(',', array_map('intval', $ids)) . ')';
    }
    if (!empty($f['tac_gia'])) { $w[] = 's.ma_tac_gia = :tg'; $p[':tg'] = (int)$f['tac_gia']; }
    if (in_array($f['dinh_dang'] ?? '', ['SACH_DIEN_TU', 'SACH_NOI', 'TRUYEN_TRANH'], true)) { $w[] = 's.dinh_dang = :dd'; $p[':dd'] = $f['dinh_dang']; }
    if (in_array($f['hinh_thuc'] ?? '', ['MIEN_PHI', 'HOI_VIEN', 'MUA_LE'], true)) { $w[] = 's.hinh_thuc_doc = :ht'; $p[':ht'] = $f['hinh_thuc']; }
    if (in_array($f['tinh_trang'] ?? '', ['DANG_RA', 'HOAN_THANH'], true)) { $w[] = 's.tinh_trang = :tt'; $p[':tt'] = $f['tinh_trang']; }
    $order = match ($f['sap_xep'] ?? 'moi') {
        'xem' => 's.luot_xem DESC, s.ma_sach DESC',
        'diem' => 's.diem_tb DESC, s.tong_danh_gia DESC, s.ma_sach DESC',
        default => 's.ngay_dang DESC, s.ma_sach DESC',
    };
    $where = implode(' AND ', $w);
    $st = $pdo->prepare("SELECT COUNT(*) " . SACH_TU . " WHERE $where");
    $st->execute($p);
    $tong = (int)$st->fetchColumn();
    $trang = max(1, min($trang, max(1, (int)ceil($tong / $moi))));
    $st = $pdo->prepare('SELECT ' . SACH_COT . ' ' . SACH_TU . " WHERE $where ORDER BY $order LIMIT " . (int)$moi . ' OFFSET ' . (($trang - 1) * $moi));
    $st->execute($p);
    return ['ds' => $st->fetchAll(), 'tong' => $tong, 'trang' => $trang];
}

/** Kệ trang chủ (FR2.1). */
function sach_ke(PDO $pdo, string $loai, int $n = 12): array {
    [$w, $o] = match ($loai) {
        'moi' => ['1=1', 's.ngay_dang DESC'],
        'hoi_vien' => ["s.hinh_thuc_doc = 'HOI_VIEN'", 's.ngay_dang DESC'],
        'xem' => ['1=1', 's.luot_xem DESC'],
        default => ['s.tong_danh_gia > 0', 's.diem_tb DESC, s.tong_danh_gia DESC'],
    };
    return $pdo->query('SELECT ' . SACH_COT . ' ' . SACH_TU . " WHERE s.tinh_trang <> 'TAM_AN' AND $w ORDER BY $o LIMIT " . (int)$n)->fetchAll();
}

function sach_thinh_hanh(PDO $pdo, int $n = 8): array { return sach_ke($pdo, 'xem', $n); }

function sach_lien_quan(PDO $pdo, array $s, int $n = 10): array {
    $st = $pdo->prepare('SELECT ' . SACH_COT . ' ' . SACH_TU . " WHERE s.tinh_trang <> 'TAM_AN' AND s.ma_sach <> :id
        ORDER BY (s.ma_the_loai = :tl) + (s.ma_tac_gia = :tg) DESC, s.luot_xem DESC LIMIT " . (int)$n);
    $st->execute([':id' => $s['ma_sach'], ':tl' => $s['ma_the_loai'], ':tg' => $s['ma_tac_gia']]);
    return $st->fetchAll();
}

/** Cập nhật cột tìm kiếm không dấu (gọi khi thêm/sửa sách, tác giả). */
function sach_cap_nhat_tu_khoa(PDO $pdo, ?int $ma_sach = null, ?int $ma_tac_gia = null): void {
    if ($ma_tac_gia !== null) {
        $pdo->prepare('UPDATE tac_gia SET tu_khoa = ? WHERE ma_tac_gia = ?')
            ->execute([bo_dau((string)$pdo->query('SELECT ten_tac_gia FROM tac_gia WHERE ma_tac_gia = ' . (int)$ma_tac_gia)->fetchColumn()), $ma_tac_gia]);
    }
    $w = $ma_sach !== null ? 'WHERE s.ma_sach = ' . (int)$ma_sach : ($ma_tac_gia !== null ? 'WHERE s.ma_tac_gia = ' . (int)$ma_tac_gia : '');
    $ds = $pdo->query("SELECT s.ma_sach, s.ten_sach, t.ten_tac_gia FROM sach s JOIN tac_gia t ON t.ma_tac_gia = s.ma_tac_gia $w")->fetchAll();
    $up = $pdo->prepare('UPDATE sach SET tu_khoa = ? WHERE ma_sach = ?');
    foreach ($ds as $r) $up->execute([bo_dau($r['ten_sach'] . ' ' . $r['ten_tac_gia']), $r['ma_sach']]);
    cache_xoa();
}
