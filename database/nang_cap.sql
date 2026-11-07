-- =====================================================================
-- Waka – nâng cấp CSDL cho bản hoàn thiện 5 module (chạy 1 lần SAU khi nhập waka_doc_sach.sql gốc)
-- MariaDB 10.4+/MySQL 8.0.16+, InnoDB, utf8mb4.
-- =====================================================================
SET NAMES utf8mb4;

-- (FR2.4) Cột tìm kiếm không dấu, ứng dụng tự cập nhật khi thêm/sửa sách và tác giả
ALTER TABLE `sach`   ADD COLUMN `tu_khoa` varchar(500) NOT NULL DEFAULT '' AFTER `ten_sach`;
ALTER TABLE `tac_gia` ADD COLUMN `tu_khoa` varchar(200) NOT NULL DEFAULT '' AFTER `ten_tac_gia`;
UPDATE `tac_gia` SET `tu_khoa` = 'nguyen nhat anh' WHERE `ma_tac_gia` = 1;
UPDATE `tac_gia` SET `tu_khoa` = 'dale carnegie' WHERE `ma_tac_gia` = 2;
UPDATE `tac_gia` SET `tu_khoa` = 'robert kiyosaki' WHERE `ma_tac_gia` = 3;
UPDATE `tac_gia` SET `tu_khoa` = 'fujiko f. fujio' WHERE `ma_tac_gia` = 4;
UPDATE `sach` SET `tu_khoa` = 'mat biec nguyen nhat anh', `anh_bia` = 'HinhSach/MatBiec.jpg' WHERE `ma_sach` = 1;
UPDATE `sach` SET `tu_khoa` = 'dac nhan tam dale carnegie', `anh_bia` = 'HinhSach/DNT.jpg' WHERE `ma_sach` = 2;
UPDATE `sach` SET `tu_khoa` = 'cha giau cha ngheo robert kiyosaki', `anh_bia` = 'HinhSach/RichDad.jpg' WHERE `ma_sach` = 3;
UPDATE `sach` SET `tu_khoa` = 'doraemon - tap 1 fujiko f. fujio', `anh_bia` = 'HinhSach/Doremon.jpg' WHERE `ma_sach` = 4;
UPDATE `sach` SET `tu_khoa` = 'toi thay hoa vang tren co xanh nguyen nhat anh', `anh_bia` = 'HinhSach/ToiThayHoaVangTrenCoXanh.jpg' WHERE `ma_sach` = 5;
UPDATE `sach` SET `tu_khoa` = 'quang ganh lo di va vui song dale carnegie', `anh_bia` = 'HinhSach/QuangGanh.jpg' WHERE `ma_sach` = 6;

-- Đường dẫn nội dung truyện tranh tính từ thư mục gốc ứng dụng (không dấu / đầu)
UPDATE `chuong` SET `tep_noi_dung` = TRIM(LEADING '/' FROM `tep_noi_dung`) WHERE `tep_noi_dung` IS NOT NULL;

-- Ràng buộc toàn vẹn (CHECK)
ALTER TABLE `tien_do_doc`  ADD CONSTRAINT `ck_tien_do_vi_tri`  CHECK (`vi_tri_cuon` BETWEEN 0 AND 100);
ALTER TABLE `dau_trang`    ADD CONSTRAINT `ck_dau_trang_vi_tri` CHECK (`vi_tri_cuon` BETWEEN 0 AND 100);
ALTER TABLE `goi_hoi_vien` ADD CONSTRAINT `ck_goi_gia_han`     CHECK (`gia_vnd` > 0 AND `thoi_han_ngay` > 0);
ALTER TABLE `danh_gia`     ADD CONSTRAINT `ck_danh_gia_sao`    CHECK (`so_sao` BETWEEN 1 AND 5);
ALTER TABLE `nguoi_dung`   ADD CONSTRAINT `ck_nguoi_dung_so_du` CHECK (`so_du_hat_soi` >= 0);
ALTER TABLE `don_hang`     ADD CONSTRAINT `ck_don_hang_tien`   CHECK (`tong_tien_goc` >= 0 AND `tien_giam` >= 0 AND `tien_giam` <= `tong_tien_goc`);
ALTER TABLE `chuong`       ADD CONSTRAINT `ck_chuong_gia`      CHECK (`gia_hat_soi` >= 0);
ALTER TABLE `sach`         ADD CONSTRAINT `ck_sach_gia`        CHECK (`gia_hat_soi` >= 0);
-- mỗi dòng giỏ / chi tiết đơn là 1 sách HOẶC 1 chương (hoặc gói, hoặc dòng nạp)
ALTER TABLE `gio_hang`     ADD CONSTRAINT `ck_gio_hang_mot_trong_hai` CHECK ((`ma_sach` IS NULL) <> (`ma_chuong` IS NULL));

-- Hiệu năng: tìm đơn quá hạn
ALTER TABLE `don_hang` ADD KEY `ix_don_hang_han` (`trang_thai`, `han_thanh_toan`);

-- (FR3.7) Hủy đơn quá hạn bằng EVENT (cần SET GLOBAL event_scheduler = ON). Nếu không bật, ứng dụng vẫn tự hủy
-- khi mở các trang đơn hàng, hoặc chạy cron/huy_don_qua_han.php mỗi phút.
DROP EVENT IF EXISTS `ev_huy_don_qua_han`;
DELIMITER $$
CREATE EVENT `ev_huy_don_qua_han` ON SCHEDULE EVERY 1 MINUTE DO
BEGIN
  UPDATE `ma_giam_gia` g JOIN `don_hang` d ON d.ma_giam_gia = g.ma_giam_gia
     SET g.so_luot_da_dung = GREATEST(g.so_luot_da_dung - 1, 0)
   WHERE d.trang_thai = 'CHO_THANH_TOAN' AND d.han_thanh_toan < NOW();
  UPDATE `thanh_toan` t JOIN `don_hang` d ON d.ma_don_hang = t.ma_don_hang
     SET t.trang_thai = 'THAT_BAI', t.ghi_chu = 'Đơn quá hạn'
   WHERE d.trang_thai = 'CHO_THANH_TOAN' AND d.han_thanh_toan < NOW() AND t.trang_thai = 'CHO_XU_LY';
  UPDATE `don_hang` SET trang_thai = 'DA_HUY', ly_do_huy = 'Quá hạn thanh toán', ngay_huy = NOW()
   WHERE trang_thai = 'CHO_THANH_TOAN' AND han_thanh_toan < NOW();
END$$
DELIMITER ;
