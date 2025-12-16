# TỔNG HỢP LUỒNG HOẠT ĐỘNG HỆ THỐNG

## 1. PHẦN BÁO CÁO - THỐNG KÊ

**Luồng hoạt động**:
1. Admin đăng nhập → Vào "Báo cáo - Thống kê"
2. Hệ thống tải dữ liệu: đếm khách hàng, tour mở, doanh thu, booking pending
3. Hiển thị 4 card thống kê
4. Tải biểu đồ số tour: lấy tháng/năm từ GET → đếm booking theo ngày → vẽ biểu đồ cột (Chart.js)
5. Tải biểu đồ doanh thu: lọc theo tour/kỳ/năm/tháng/quý → tính SUM(deposit_amount) → vẽ biểu đồ đường
6. Tải danh sách tour hoàn thành: tour có booking completed → hiển thị bảng

**Tính doanh thu**: SUM(deposit_amount) từ bookings có status: `deposit`, `confirmed`, `completed`

---

## 2. PHẦN DANH SÁCH KHÁCH HÀNG

### Xem danh sách
1. Admin chọn booking từ dropdown
2. Hệ thống lấy danh sách khách theo booking_id
3. Tự động tạo khách đầu tiên nếu booking có customer_name nhưng chưa có khách
4. Hiển thị bảng danh sách

### Thêm khách mới
1. Admin điền form (bắt buộc: họ tên)
2. Validate: booking_id, full_name, kiểm tra số giấy tờ không trùng
3. Xác định payment_status dựa trên booking status
4. Lưu khách mới → Đồng bộ payment_status cho tất cả khách cũ trong booking

### Sửa/Xóa khách
- **Sửa**: Load form → Chỉnh sửa → Validate → Update database
- **Xóa**: Kiểm tra quyền → Delete từ database

### Đồng bộ payment_status
- Khi thêm khách mới: kiểm tra booking status → gán status cho khách mới → cập nhật tất cả khách cũ trong booking

---

## 3. PHẦN QUẢN LÝ ĐOÀN KHÁCH - CHECK-IN

### Xem danh sách
1. Admin chọn tour group
2. Hệ thống tải: tour group, danh sách khách (tour_guests), booking liên quan
3. Tính thống kê: tổng số, đã check-in, đã đến
4. Hiển thị bảng với cột: Tên, Check-in, Thanh toán, Yêu cầu đặc biệt

### Cập nhật Check-in/Thanh toán
1. Admin chọn trạng thái từ dropdown
2. **AJAX request** (không reload trang):
   - Gửi POST với guest_id, group_id, status
   - Server validate → Update database
   - Trả về JSON → Cập nhật UI (màu badge, thống kê)

**Trạng thái Check-in**: `not_arrived` → `arrived` → `checked_in`  
**Trạng thái Thanh toán**: `unpaid` → `deposit` → `paid`

### Cập nhật yêu cầu đặc biệt
1. Click icon ✏️ → Mở modal
2. Nhập/sửa yêu cầu → Update database
3. Hiển thị badge cảnh báo ⚠️ nếu có

---

## 4. PHẦN NHẬT KÝ HƯỚNG DẪN VIÊN

### Tạo nhật ký mới (HDV)
1. HDV chọn tour → Click "Thêm nhật ký"
2. Chọn loại: `daily` / `incident` / `rating` / `note`
3. Điền thông tin: tiêu đề, mô tả (bắt buộc), thông tin bổ sung, upload ảnh (tùy chọn)
4. Submit → Server: validate → tự động lấy guide_id từ session → gộp thông tin bổ sung vào description → lưu vào tour_logs

### Xem danh sách
1. Chọn tour → Áp dụng filter (ngày/loại/trạng thái)
2. Truy vấn: HDV chỉ thấy của mình, Admin thấy tất cả
3. Hiển thị danh sách với đầy đủ thông tin

### Cập nhật trạng thái/Rating (Admin)
- **Trạng thái**: Click dropdown → AJAX → Update status (`pending`/`reviewed`/`completed`)
- **Rating**: Click dropdown → AJAX → Update rating (1-5 sao)

### Sửa nhật ký (HDV)
1. Click "Sửa" → Kiểm tra quyền → Load form
2. Chỉnh sửa → Submit → Update database

**Tự động gắn guide_id**: Khi đăng nhập → lưu guide_id vào session → Khi tạo nhật ký → tự động lấy từ session

---

## 5. ROUTER HOẠT ĐỘNG

**Cách router hoạt động**:
1. **Entry point**: File `index.php` là điểm vào duy nhất
2. **Lấy action**: Router lấy tham số `$_GET['action']` từ URL (mặc định là `/`)
3. **Định tuyến**: File `routes/admin.php` sử dụng `match` expression (PHP 8+) để map action với controller method
4. **Gọi controller**: Tạo instance controller và gọi method tương ứng

**Ví dụ URL và Route**:
- `index.php?action=admin` → `(new AdminController)->index()`
- `index.php?action=customers` → `(new CustomerController)->index()`
- `index.php?action=customers-create` → `(new CustomerController)->create()`
- `index.php?action=tour-guest-checkin` → `(new TourManagementController)->checkinGuest()`

**Quy tắc đặt tên route**:
- `{module}` → Xem danh sách (index)
- `{module}-create` → Form tạo mới
- `{module}-store` → Lưu dữ liệu mới (POST)
- `{module}-edit` → Form sửa
- `{module}-update` → Cập nhật dữ liệu (POST)
- `{module}-delete` → Xóa dữ liệu
- `{module}-{action}` → Các action đặc biệt (checkin, sync, print, v.v.)

**Ưu điểm**:
- Tập trung tất cả route ở một file (`routes/admin.php`)
- Dễ quản lý và bảo trì
- Không cần framework phức tạp
- Rõ ràng, dễ hiểu

---

## 6. LUỒNG XỬ LÝ REQUEST TỔNG QUÁT

**Luồng xử lý**:
1. Frontend gửi request (GET/POST) → `index.php`
2. Router phân tích `action` từ URL → Gọi controller method tương ứng
3. Controller: kiểm tra authentication → kiểm tra phân quyền
4. Controller gọi Model → Model dùng PDO truy vấn database
5. Controller xử lý kết quả → Load View
6. View render HTML (hoặc JSON nếu AJAX) → Trả về client

**Session & Authentication**:
- Đăng nhập: verify password → lưu vào session (id, name, email, role, guide_id)
- Truy cập trang: requireAuth() → kiểm tra session → redirect login nếu không có

**Validation**:
- Client-side: HTML5 validation + JavaScript
- Server-side: kiểm tra REQUEST_METHOD → validate format → validate business logic → sanitize → xử lý

---

## 7. TỔNG KẾT

**Điểm chung**:
- Xác thực người dùng, phân quyền (Admin/HDV)
- Validation đa tầng (client + server)
- Thông báo qua session
- PDO để truy vấn an toàn

**Tính năng đặc biệt**:
- AJAX: cập nhật không reload trang
- Tự động hóa: tạo khách đầu tiên, đồng bộ payment_status, gắn guide_id
- Cảnh báo thông minh: hiển thị khi có yêu cầu đặc biệt

**Công nghệ**: PHP thuần + PDO, Bootstrap 5, JavaScript (AJAX/Fetch API), MySQL/MariaDB, Chart.js

---

## 8. SƠ ĐỒ LUỒNG HOẠT ĐỘNG

```
NGƯỜI DÙNG (Admin/HDV)
    ↓
ĐĂNG NHẬP → Lưu session (user_id, role, guide_id)
    ↓
ROUTER/CONTROLLER → Kiểm tra auth & phân quyền
    ↓
    ├─→ MODEL → PDO → DATABASE
    └─→ VIEW → Render HTML/JSON
```

---

## 9. CÁC TRẠNG THÁI

**Booking**: `pending` → `deposit` → `confirmed` → `completed`  
**Check-in**: `not_arrived` → `arrived` → `checked_in`  
**Thanh toán**: `unpaid` → `deposit` → `paid`  
**Nhật ký**: `pending` → `reviewed` → `completed`

---

**Ghi chú**: File này tổng hợp các luồng hoạt động chính. Xem chi tiết tại `GIAI_THICH_CHUC_NANG.md`.
