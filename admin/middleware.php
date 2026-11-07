<?php
/**
 * FR1.6 – Middleware riêng cho khu vực Admin.
 * Kiểm tra ở phía máy chủ bằng session; trạng thái/vai trò được đọc lại từ CSDL mỗi request
 * (xem nguoi_dung_hien_tai()) nên tài khoản bị khóa hoặc bị hạ quyền mất hiệu lực ngay.
 */
require_once __DIR__ . '/../auth.php';

function middleware_quan_tri(): array {
    $u = nguoi_dung_hien_tai();
    if (!$u) {                                   // Khách → đăng nhập
        $_SESSION['next'] = 'admin/index.php';
        chuyen_huong(url('dang_nhap.php'));
    }
    if ($u['vai_tro'] !== 'QUAN_TRI') {          // Thành viên → cấm
        http_response_code(403);
        exit('403 – Bạn không có quyền truy cập khu vực quản trị.');
    }
    return $u;
}
