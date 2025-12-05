## Mục tiêu
- Khôi phục nút "Đăng xuất" về giao diện cũ (màu sáng, bo tròn, padding rộng) như trước khi chuyển sang các lớp CSS thuần.

## Thay đổi dự kiến
- Sửa markup nút ở `views/admin/hdv_detail.php` để dùng lại: `class="btn btn-light rounded-pill px-4"`.
- Loại bỏ (hoặc đổi tên) các lớp CSS tuỳ biến có thể ghi đè Bootstrap: `.btn`, `.btn-primary`, `.btn-outline`, `.btn-sm` trong block `<style>` của file.
- Giữ nguyên bố cục `topbar`; chỉ thay class của nút.

## Phòng tránh xung đột
- Nếu dự án có Bootstrap đang load ở layout, việc bỏ các lớp `.btn*` tuỳ biến đảm bảo hình dạng nút quay về đúng như trước.
- Nếu không có Bootstrap, sẽ thêm CSS mô phỏng giao diện cũ dưới tên `.btn-light` và `.rounded-pill` (không dùng selector `.btn` để tránh ảnh hưởng toàn cục).

## Kiểm tra
- Mở trang HDV, xác nhận nút có nền sáng, chữ tối, bo tròn, kích thước rộng; hover hoạt động bình thường.
- Đảm bảo không ảnh hưởng các nút khác trong trang.

Xác nhận để mình áp dụng chỉnh sửa ngay.