# Waka – website đọc sách, truyện & sách nói (PHP + MariaDB/MySQL)
Huỳnh Thanh Nguyên _ 65132295

## Cài đặt (XAMPP)
1. Chép thư mục `waka/` vào `htdocs/`, bật Apache + MySQL.
2. Tạo CSDL `waka_doc_sach` (utf8mb4_unicode_ci) rồi nhập **theo thứ tự** các file trong `database/`:
   1. `waka_doc_sach.sql` – cấu trúc + dữ liệu mẫu gốc
   2. `nang_cap.sql` – cột tìm kiếm không dấu, CHECK, index, EVENT hủy đơn quá hạn
   3. `mat_khau_demo.sql` – đặt mật khẩu demo `Waka@12345` cho `admin / vanan / thuhuong / minhquan / baongoc`
3. Sửa `config.php` nếu cần (`APP_URL`, tài khoản DB, thông tin chuyển khoản). Cấu hình mail bằng biến môi trường `WAKA_SMTP_USER`, `WAKA_SMTP_PASS`; khi `APP_DEBUG = true` email luôn được ghi vào `logs/mail.log` (dùng để thử Quên mật khẩu).
4. Đơn quá hạn tự hủy theo 3 cách (dùng cách nào cũng được): tự hủy khi mở trang đơn hàng/thanh toán/admin; `SET GLOBAL event_scheduler = ON;` (EVENT trong `nang_cap.sql`); hoặc cron `* * * * * php cron/huy_don_qua_han.php`.
5. Mở `http://localhost/waka/`.

## Cấu trúc (gọn)
```
config.php            cấu hình + PDO         layout.php         giao diện dùng chung
auth.php              session, CSRF, phân quyền, mail, tiện ích, trạng thái VIP
inc/sach.php          M2 lọc/tìm kiếm        inc/don_hang.php   M3 giỏ, mã, đơn, thanh toán, kích hoạt, hủy
inc/doc_sach.php      M4 quyền đọc, tiến độ, dấu trang, lịch sử, tủ sách
inc/upload.php        upload an toàn (MIME thật, random tên)
M1  dang_ky dang_nhap dang_xuat quen_mat_khau dat_lai_mat_khau doi_mat_khau ho_so
M2  index danh_muc tim_kiem api_goi_y chi_tiet
M3  gio_hang dat_hang thanh_toan lich_su_don_hang nap_hat_soi
M4  tu_sach doc api_doc lich_su_doc dau_trang hoi_vien
M5  admin/ index(thống kê) sach chuong the_loai tac_gia nguoi_dung don_hang ma_giam_gia binh_luan
assets/ app.css app.js doc.js        cron/ huy_don_qua_han.php
```

## Máy trạng thái đơn hàng
`CHO_THANH_TOAN → DA_THANH_TOAN (cuối) | THAT_BAI | DA_HUY (cuối)`; `THAT_BAI → CHO_THANH_TOAN` (thử lại, gia hạn thêm 30 phút). Chỉ hủy được đơn `CHO_THANH_TOAN`.
Lượt dùng mã giảm giá được **giữ chỗ khi tạo đơn** (để không vượt giới hạn khi nhiều đơn chờ) và **hoàn lại khi hủy/quá hạn**.
Quy đổi nạp: 100.000₫ = 1.000 hạt sồi (xem `GOI_NAP` trong `inc/don_hang.php`).

## Ghi chú bảo mật
- Mật khẩu bcrypt; prepared statement; escape đầu ra; CSRF mọi form/AJAX POST; quyền kiểm tra ở máy chủ; session đọc lại trạng thái tài khoản mỗi request.
- Gói gốc từng chứa mật khẩu ứng dụng Gmail trong `auth.php`; bản này đã chuyển sang biến môi trường. **Hãy thu hồi/đổi mật khẩu ứng dụng đó.**
- Chưa có webhook ngân hàng: chuyển khoản được đối soát bằng admin duyệt thủ công (đúng đặc tả cho phép).
