<?php
// FR2.4 – gợi ý khi gõ ≥ 2 ký tự: tối đa 5 sách + 3 tác giả
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/inc/sach.php';

$q = trim((string)($_GET['q'] ?? ''));
$ra = ['sach' => [], 'tac_gia' => []];
if (mb_strlen($q, 'UTF-8') >= 2) {
    $p = [];
    $w = sach_dieu_kien_tu_khoa($q, $p);
    $st = $pdo->prepare("SELECT s.ma_sach, s.ten_sach, s.anh_bia, t.ten_tac_gia FROM sach s JOIN tac_gia t ON t.ma_tac_gia = s.ma_tac_gia
                         WHERE s.tinh_trang <> 'TAM_AN' AND $w ORDER BY s.luot_xem DESC LIMIT 5");
    $st->execute($p);
    foreach ($st as $r) $ra['sach'][] = ['id' => (int)$r['ma_sach'], 'ten' => $r['ten_sach'], 'tac_gia' => $r['ten_tac_gia'], 'anh' => url_anh($r['anh_bia'])];
    $p = [];
    $w = sach_dieu_kien_tu_khoa($q, $p, 't.tu_khoa');
    $st = $pdo->prepare("SELECT t.ma_tac_gia, t.ten_tac_gia FROM tac_gia t WHERE $w ORDER BY t.ten_tac_gia LIMIT 3");
    $st->execute($p);
    foreach ($st as $r) $ra['tac_gia'][] = ['id' => (int)$r['ma_tac_gia'], 'ten' => $r['ten_tac_gia']];
}
json_ra($ra);
