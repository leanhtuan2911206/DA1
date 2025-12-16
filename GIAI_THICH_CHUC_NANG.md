# GIẢI THÍCH CÁC CHỨC NĂNG HỆ THỐNG

## 1. PHẦN BÁO CÁO - THỐNG KÊ (Admin Dashboard)

### 1.1. Mục đích
Trang báo cáo tổng quan cung cấp thông tin thống kê về hoạt động kinh doanh tour, giúp admin nắm bắt tình hình nhanh chóng.

### 1.2. Luồng hoạt động
1. **Admin truy cập trang báo cáo**: Đăng nhập → Vào trang "Báo cáo - Thống kê"
2. **Hệ thống tải dữ liệu**:
   - Truy vấn database để đếm tổng khách hàng (từ `customers` + `tour_guests`)
   - Đếm tour đang mở (status active/upcoming hoặc có booking)
   - Tính tổng doanh thu (SUM deposit_amount từ booking có status hợp lệ)
   - Đếm booking pending và tổng booking
3. **Hiển thị chỉ số**: Hiển thị 4 card thống kê ở đầu trang
4. **Tải biểu đồ số tour**: 
   - Lấy tháng/năm từ GET (mặc định tháng/năm hiện tại)
   - Truy vấn đếm booking theo ngày trong tháng
   - Vẽ biểu đồ cột với Chart.js
5. **Tải biểu đồ doanh thu**:
   - Lấy các filter từ GET (tour, kỳ, năm, tháng, quý)
   - Gọi method `calculateRevenueData()` để tính toán
   - Vẽ biểu đồ đường và hiển thị tổng doanh thu
6. **Tải danh sách tour hoàn thành**:
   - Truy vấn các tour có booking completed
   - Tính số booking, doanh thu, ngày tour cuối
   - Lấy sự cố từ `tour_issues`
   - Hiển thị bảng danh sách

### 1.3. Các chỉ số hiển thị
- **Tổng khách hàng**: Đếm từ bảng `customers` và `tour_guests`
- **Tour đang mở**: Số tour có trạng thái active/upcoming hoặc có booking đang hoạt động
- **Doanh thu**: Tổng `deposit_amount` từ các booking có status: `deposit`, `confirmed`, `completed`
- **Tour chờ/xử lý**: Số booking có status `pending` / Tổng số booking

### 1.4. Biểu đồ số tour được đặt
- **Chức năng**: Hiển thị số lượng booking theo từng ngày trong tháng
- **Cách hoạt động**:
  - Admin chọn tháng/năm từ dropdown
  - Hệ thống truy vấn database đếm số booking theo ngày (dùng cột `start_date` hoặc `created_at`)
  - Vẽ biểu đồ cột (bar chart) với Chart.js
  - Mỗi cột đại diện cho số booking trong ngày đó

### 1.5. Biểu đồ doanh thu theo thời gian
- **Chức năng**: Hiển thị doanh thu theo tháng/quý/năm
- **Các tùy chọn lọc**:
  - **Tour**: Chọn tour cụ thể hoặc "Tất cả tour"
  - **Kỳ báo cáo**: Tháng / Quý / Năm
  - **Năm**: Chọn năm
  - **Tháng**: Chọn tháng (nếu chọn kỳ "Tháng")
  - **Quý**: Chọn quý 1-4 (nếu chọn kỳ "Quý")
- **Cách tính**:
  - Truy vấn `SUM(deposit_amount)` từ bảng `bookings`
  - Chỉ tính các booking có status: `deposit`, `confirmed`, `completed`
  - Nhóm theo ngày (nếu kỳ = tháng), theo tháng (nếu kỳ = quý/năm)
  - Hiển thị tổng doanh thu và biểu đồ đường (line chart)

### 1.6. Danh sách tour đã hoàn thành
- **Hiển thị**: Các tour có ít nhất 1 booking với status `completed`
- **Thông tin hiển thị**:
  - Tên tour, loại tour
  - Số booking đã hoàn thành
  - Tổng doanh thu
  - Ngày tour cuối cùng
  - Sự cố/sự kiện (từ bảng `tour_issues`)
- **Sắp xếp**: Theo ngày tour cuối (mới nhất trước), sau đó theo doanh thu

### 1.7. File liên quan
- **Controller**: `controllers/AdminController.php` - method `index()`, `calculateRevenueData()`
- **View**: `views/admin/index.php`
- **Database**: Bảng `bookings`, `tours`, `tour_categories`, `tour_issues`

---

## 2. PHẦN DANH SÁCH KHÁCH HÀNG

### 2.1. Mục đích
Quản lý thông tin khách hàng theo từng booking, cho phép thêm/sửa/xóa khách trong một booking.

### 2.2. Luồng hoạt động

#### A. Xem danh sách khách hàng
1. **Admin truy cập trang**: Đăng nhập → Vào "Quản lý khách hàng"
2. **Hệ thống tải danh sách booking**: Lấy tất cả booking từ database
3. **Admin chọn booking**: Chọn booking từ dropdown (có thể truyền qua GET `booking_id`)
4. **Hệ thống kiểm tra**:
   - Nếu chưa chọn booking → Hiển thị thông báo "Vui lòng chọn booking"
   - Nếu đã chọn → Lấy thông tin booking và danh sách khách
5. **Tự động tạo khách đầu tiên** (nếu cần):
   - Kiểm tra booking có `customer_name` nhưng chưa có khách nào
   - Tự động tạo khách đầu tiên từ `customer_name`
   - Gán payment_status dựa trên booking status
6. **Hiển thị danh sách**: Hiển thị bảng danh sách khách với đầy đủ thông tin

#### B. Thêm khách mới
1. **Admin click "Thêm khách"**: Từ trang danh sách khách
2. **Hệ thống hiển thị form**: Form nhập thông tin khách (booking_id đã được truyền)
3. **Admin điền thông tin**: 
   - Bắt buộc: Họ tên
   - Tùy chọn: Giới tính, ngày sinh, giấy tờ, liên hệ, yêu cầu đặc biệt
4. **Admin submit form**: POST dữ liệu lên server
5. **Hệ thống validate**:
   - Kiểm tra `booking_id` và `full_name` có giá trị
   - Kiểm tra số giấy tờ không trùng trong cùng booking
6. **Xác định payment_status**:
   - Nếu booking đã `deposit` → gán `deposit`
   - Nếu booking đã `paid`/`completed`/`confirmed` → gán `paid`
   - Nếu booking `pending` → lấy từ khách đầu tiên (nếu có) hoặc mặc định `unpaid`
7. **Lưu khách mới**: Insert vào bảng `customers`
8. **Đồng bộ payment_status**: Cập nhật status cho tất cả khách cũ trong booking
9. **Thông báo kết quả**: Hiển thị success/error message và redirect về danh sách

#### C. Sửa thông tin khách
1. **Admin click "Sửa"**: Từ bảng danh sách khách
2. **Hệ thống tải thông tin**: Lấy thông tin khách từ database
3. **Hiển thị form**: Form đã điền sẵn thông tin khách
4. **Admin chỉnh sửa**: Sửa các trường cần thiết
5. **Admin submit**: POST dữ liệu lên server
6. **Hệ thống validate**: Kiểm tra dữ liệu hợp lệ, kiểm tra khách thuộc booking đúng
7. **Cập nhật database**: Update thông tin khách
8. **Thông báo và redirect**: Hiển thị kết quả, quay về danh sách

#### D. Xóa khách
1. **Admin click "Xóa"**: Từ bảng danh sách khách
2. **Hệ thống xác nhận**: Kiểm tra khách thuộc booking đúng
3. **Xóa khỏi database**: Delete từ bảng `customers`
4. **Thông báo kết quả**: Redirect về danh sách với thông báo

### 2.3. Thông tin khách hàng
- **Thông tin cơ bản**: Họ tên, giới tính, ngày sinh
- **Giấy tờ**: Loại giấy tờ (CMND/CCCD/Passport), số giấy tờ
- **Liên hệ**: Số điện thoại, email, địa chỉ
- **Thanh toán**: Trạng thái (`unpaid`, `deposit`, `paid`)
- **Yêu cầu đặc biệt**: Ghi chú về dị ứng, yêu cầu đặc biệt, v.v.

### 2.4. Luồng đồng bộ payment_status
1. **Trigger**: Khi thêm khách mới vào booking
2. **Kiểm tra booking status**:
   - Nếu booking `deposit` → payment_status = `deposit`
   - Nếu booking `paid`/`completed`/`confirmed` → payment_status = `paid`
   - Nếu booking `pending` → Lấy từ khách đầu tiên hoặc mặc định `unpaid`
3. **Gán status cho khách mới**: Áp dụng payment_status đã xác định
4. **Lấy danh sách khách cũ**: Query tất cả khách trong booking (trừ khách vừa thêm)
5. **Cập nhật từng khách cũ**: 
   - So sánh payment_status hiện tại với status mới
   - Nếu khác nhau → Update
   - Nếu giống nhau → Bỏ qua
6. **Thông báo kết quả**: Hiển thị số lượng khách đã được đồng bộ

### 2.5. Tính năng đồng bộ payment_status
- **Vấn đề**: Tất cả khách trong cùng 1 booking nên có cùng trạng thái thanh toán
- **Giải pháp**: 
  - Khi thêm khách mới, hệ thống tự động đồng bộ `payment_status` cho tất cả khách cũ trong booking
  - Nếu booking đã `deposit` hoặc `paid`, khách mới sẽ tự động có status tương ứng
  - Đảm bảo tính nhất quán dữ liệu

### 2.6. Chức năng CRUD
- **Thêm khách**: Form nhập đầy đủ thông tin, validate bắt buộc (họ tên, booking_id)
- **Sửa khách**: Cập nhật thông tin, kiểm tra khách thuộc booking đúng
- **Xóa khách**: Xóa khỏi booking, không ảnh hưởng booking

### 2.7. Validation
- **Bắt buộc**: `booking_id`, `full_name`
- **Tùy chọn**: Các trường còn lại có thể để trống
- **Duplicate check**: Kiểm tra số giấy tờ không trùng trong cùng booking

### 2.8. File liên quan
- **Controller**: `controllers/CustomerController.php`
- **View**: `views/admin/customers.php`, `customers-create.php`, `customers-edit.php`
- **Model**: `models/Customer.php`
- **Database**: Bảng `customers` (liên kết với `bookings` qua `booking_id`)

---

## 3. PHẦN QUẢN LÝ ĐOÀN KHÁCH - CHECK-IN

### 3.1. Mục đích
Quản lý đoàn khách trong tour, theo dõi trạng thái check-in và thanh toán của từng khách.

### 3.2. Luồng hoạt động

#### A. Xem danh sách đoàn khách
1. **Admin truy cập**: Vào "Quản lý đoàn khách" → Chọn tour group
2. **Hệ thống tải dữ liệu**:
   - Lấy thông tin tour group từ `group_id`
   - Lấy danh sách khách từ `tour_guests` theo `group_id`
   - Lấy thông tin booking liên quan
3. **Tính toán thống kê**:
   - Đếm tổng số khách
   - Đếm số khách đã check-in (`checked_in`)
   - Đếm số khách đã đến (`arrived` + `checked_in`)
   - Xác định trạng thái tổng thể
4. **Hiển thị**: Bảng danh sách khách với các cột: Tên, Check-in, Thanh toán, Yêu cầu đặc biệt

#### B. Cập nhật trạng thái Check-in
1. **Admin chọn trạng thái**: Click dropdown trong cột "Check-in"
2. **Chọn giá trị**: `not_arrived` / `arrived` / `checked_in`
3. **Form tự động submit**: Trigger event `onchange` → Submit form
4. **AJAX request** (nếu có):
   - Gửi POST request với `guest_id`, `group_id`, `checkin_status`
   - Không reload trang
5. **Server xử lý**:
   - Validate dữ liệu (guest_id, group_id hợp lệ)
   - Update `checkin_status` trong bảng `tour_guests`
   - Kiểm tra yêu cầu đặc biệt → Hiển thị cảnh báo nếu có
6. **Cập nhật UI**:
   - Thay đổi màu badge/select theo trạng thái mới
   - Cập nhật thống kê (số đã check-in, trạng thái tổng thể)
   - Hiển thị thông báo success/error

#### C. Cập nhật trạng thái Thanh toán
1. **Admin chọn trạng thái**: Click dropdown trong cột "Thanh toán"
2. **Chọn giá trị**: `unpaid` / `deposit` / `paid`
3. **Form tự động submit**: Tương tự check-in
4. **Server xử lý**: Update `payment_status` trong database
5. **Cập nhật UI**: Thay đổi màu và cập nhật hiển thị

#### D. Cập nhật yêu cầu đặc biệt
1. **Admin click icon ✏️**: Bên cạnh yêu cầu đặc biệt
2. **Hiển thị modal**: Form nhập/sửa yêu cầu đặc biệt
3. **Admin nhập thông tin**: Điền hoặc sửa yêu cầu
4. **Submit**: Update vào database
5. **Cập nhật hiển thị**: Badge cảnh báo ⚠️ xuất hiện nếu có yêu cầu

### 3.3. Cấu trúc dữ liệu
- **Tour Group** (`tour_groups`): Đại diện cho một đoàn khách trong tour
  - Liên kết với `booking_id`
  - Có thể có nhiều khách (tour_guests)
- **Tour Guest** (`tour_guests`): Thông tin từng khách trong đoàn
  - Liên kết với `group_id`
  - Có `checkin_status` và `payment_status`

### 3.4. Trạng thái Check-in
Có 3 trạng thái:
1. **`not_arrived`** (Chưa đến): Khách chưa đến điểm tập trung
2. **`arrived`** (Đã đến): Khách đã đến nhưng chưa check-in chính thức
3. **`checked_in`** (Check-in): Khách đã check-in thành công

### 3.5. Trạng thái Thanh toán
Có 3 trạng thái:
1. **`unpaid`** (Chưa thanh toán): Chưa thanh toán
2. **`deposit`** (Đã đặt cọc): Đã đặt cọc một phần
3. **`paid`** (Đã thanh toán): Đã thanh toán đầy đủ

### 3.6. Cách sử dụng
1. **Truy cập**: Admin vào "Quản lý đoàn khách" → Chọn tour group
2. **Xem danh sách**: Hiển thị tất cả khách trong đoàn với thông tin đầy đủ
3. **Cập nhật check-in**: 
   - Chọn trạng thái từ dropdown trong cột "Check-in"
   - Form tự động submit, cập nhật ngay lập tức
   - Không reload trang (dùng AJAX nếu có)
4. **Cập nhật thanh toán**: Tương tự check-in, chọn từ dropdown

### 3.7. Tính năng đặc biệt
- **Yêu cầu đặc biệt**: Nếu khách có `special_requests`, hiển thị badge cảnh báo ⚠️
- **Phân phòng**: Có thể gán phòng khách sạn cho khách (nếu có)
- **Cảnh báo**: Khi cập nhật khách có yêu cầu đặc biệt, hệ thống hiển thị thông báo nhắc nhở

### 3.8. Tổng hợp thống kê
- **Tổng số khách**: Tổng số khách trong đoàn
- **Đã check-in**: Số khách có `checkin_status = 'checked_in'`
- **Đã đến**: Số khách có `checkin_status = 'arrived'` hoặc `'checked_in'`
- **Trạng thái tổng thể**: 
  - "Đã check-in" nếu tất cả đã check-in
  - "Đang check-in" nếu có người đã đến/check-in
  - "Chưa check-in" nếu chưa ai đến

### 3.9. File liên quan
- **Controller**: `controllers/TourManagementController.php` - method `checkinGuest()`, `listGuests()`
- **View**: `views/admin/tour-guests.php`
- **Model**: `models/TourGuest.php`, `models/TourGroup.php`
- **Database**: Bảng `tour_groups`, `tour_guests`

---

## 4. PHẦN NHẬT KÝ HƯỚNG DẪN VIÊN

### 4.1. Mục đích
Cho phép hướng dẫn viên (HDV) ghi chép nhật ký tour, ghi lại các sự kiện, tình huống, đánh giá trong quá trình dẫn tour.

### 4.2. Luồng hoạt động

#### A. Tạo nhật ký mới (HDV)
1. **HDV truy cập**: Đăng nhập → Vào "Nhật ký tour"
2. **Chọn tour**: Chọn tour đang dẫn từ danh sách
3. **Click "Thêm nhật ký"**: Mở form tạo nhật ký
4. **Chọn loại nhật ký**: 
   - `daily` - Nhật ký hàng ngày
   - `incident` - Sự cố
   - `rating` - Đánh giá
   - `note` - Ghi chú
5. **Điền thông tin cơ bản**:
   - Tiêu đề (bắt buộc)
   - Mô tả (bắt buộc)
   - Ngày ghi (mặc định hôm nay)
   - Chọn lịch trình/ngày (tùy chọn)
6. **Điền thông tin bổ sung** (tùy loại):
   - Thời tiết, sức khỏe khách, hoạt động đặc biệt
   - Cách xử lý tình huống, phản hồi khách
   - Đánh giá phối hợp, tinh thần làm việc (nếu loại `rating`)
7. **Upload ảnh** (tùy chọn):
   - Chọn file ảnh
   - Validate: kiểm tra định dạng, kích thước
   - Upload lên thư mục `uploads/`
   - Lưu đường dẫn vào database
8. **Submit form**: POST dữ liệu lên server
9. **Server xử lý**:
   - Validate dữ liệu (tiêu đề, mô tả bắt buộc)
   - Tự động lấy `guide_id` từ session
   - Gộp các trường bổ sung vào `description` với emoji
   - Lưu vào bảng `tour_logs`
10. **Thông báo kết quả**: Redirect về danh sách nhật ký với thông báo

#### B. Xem danh sách nhật ký
1. **HDV/Admin truy cập**: Vào trang nhật ký
2. **Chọn tour**: Chọn tour từ dropdown (hoặc mặc định)
3. **Áp dụng filter** (tùy chọn):
   - Lọc theo ngày (day_number)
   - Lọc theo loại nhật ký
   - Lọc theo trạng thái
4. **Hệ thống truy vấn**: 
   - Lấy nhật ký từ `tour_logs` theo tour_id
   - Nếu là HDV → chỉ lấy nhật ký của mình (theo guide_id)
   - Nếu là Admin → lấy tất cả nhật ký
   - Áp dụng filter nếu có
5. **Hiển thị**: Danh sách nhật ký với đầy đủ thông tin, hình ảnh (nếu có)

#### C. Cập nhật trạng thái nhật ký (Admin)
1. **Admin xem nhật ký**: Trong danh sách nhật ký
2. **Chọn trạng thái**: Click dropdown "Trạng thái"
3. **Chọn giá trị**: `pending` / `reviewed` / `completed`
4. **AJAX request**: Gửi POST request không reload trang
5. **Server xử lý**: Update `status` trong database
6. **Cập nhật UI**: Thay đổi màu badge theo trạng thái mới

#### D. Đánh giá/Rating nhật ký (Admin)
1. **Admin xem nhật ký**: Trong danh sách nhật ký
2. **Chọn rating**: Click dropdown "Đánh giá" (1-5 sao)
3. **AJAX request**: Gửi POST request với rating
4. **Server xử lý**: Update `rating` trong database
5. **Cập nhật UI**: Hiển thị số sao tương ứng

#### E. Sửa nhật ký (HDV)
1. **HDV click "Sửa"**: Từ danh sách nhật ký của mình
2. **Hệ thống kiểm tra**: Đảm bảo nhật ký thuộc về HDV này
3. **Hiển thị form**: Form đã điền sẵn thông tin nhật ký
4. **HDV chỉnh sửa**: Sửa các trường cần thiết
5. **Submit**: Update vào database
6. **Thông báo kết quả**: Redirect về danh sách

### 4.3. Các loại nhật ký
1. **`daily`** (Nhật ký hàng ngày): Ghi chép hoạt động trong ngày
2. **`incident`** (Sự cố): Ghi lại sự cố, vấn đề phát sinh
3. **`rating`** (Đánh giá): Đánh giá tour, phối hợp, tinh thần làm việc
4. **`note`** (Ghi chú): Ghi chú thông thường

### 4.4. Thông tin trong nhật ký
- **Thông tin cơ bản**:
  - Tiêu đề (title)
  - Mô tả (description)
  - Loại nhật ký (log_type)
  - Ngày ghi (log_date)
  - Tour liên quan (tour_id)
  - Lịch trình liên quan (itinerary_id) - tùy chọn
- **Thông tin bổ sung** (tùy loại):
  - **Thời tiết**: Ghi nhận thời tiết trong ngày
  - **Tình trạng sức khỏe khách**: Có khách nào ốm, cần chăm sóc không
  - **Hoạt động đặc biệt**: Các hoạt động ngoài lịch trình
  - **Cách xử lý tình huống**: Cách HDV xử lý sự cố
  - **Phản hồi khách hàng**: Ý kiến, phản hồi từ khách
  - **Đánh giá phối hợp**: Điểm 1-5 về sự phối hợp với đội ngũ
  - **Tinh thần làm việc**: Điểm 1-5 về tinh thần làm việc
  - **Nhận xét**: Ghi chú thêm
- **Hình ảnh**: Có thể upload ảnh kèm theo nhật ký
- **Trạng thái**: `pending`, `reviewed`, `completed`
- **Đánh giá**: Điểm rating (1-5) nếu là loại `rating`

### 4.5. Quy trình tạo nhật ký (Chi tiết)
1. **HDV chọn tour**: Chọn tour đang dẫn từ danh sách
2. **Chọn loại nhật ký**: Chọn loại phù hợp (daily/incident/rating/note)
3. **Điền thông tin**:
   - Tiêu đề (bắt buộc)
   - Mô tả (bắt buộc)
   - Các trường bổ sung tùy loại
   - Upload ảnh (tùy chọn)
4. **Chọn ngày/lịch trình**: Gắn với ngày cụ thể hoặc hoạt động trong lịch trình
5. **Lưu**: Hệ thống tự động gắn `guide_id` từ session, lưu vào database

### 4.6. Xem và quản lý nhật ký
- **Lọc theo**:
  - Tour
  - Ngày (day_number trong lịch trình)
  - Loại nhật ký
  - Trạng thái
- **Hiển thị**: Danh sách nhật ký với đầy đủ thông tin, hình ảnh (nếu có)
- **Chỉnh sửa**: HDV có thể sửa nhật ký của mình
- **Admin quản lý**: Admin có thể xem tất cả nhật ký, cập nhật trạng thái, đánh giá

### 4.7. Tính năng đặc biệt
- **Tự động gắn guide_id**: Khi HDV tạo nhật ký, hệ thống tự động lấy `guide_id` từ session
- **Gộp mô tả**: Các trường bổ sung (thời tiết, sức khỏe, v.v.) được gộp vào `description` với emoji để dễ đọc
- **Upload ảnh**: Hỗ trợ upload ảnh, lưu vào thư mục uploads, đường dẫn lưu vào database
- **AJAX cập nhật**: Cập nhật trạng thái/rating không reload trang (dùng AJAX)

### 4.8. Phân quyền
- **HDV**: 
  - Xem nhật ký của mình
  - Tạo nhật ký mới
  - Sửa nhật ký của mình
- **Admin**:
  - Xem tất cả nhật ký
  - Cập nhật trạng thái nhật ký
  - Đánh giá/rating nhật ký
  - Xóa nhật ký (nếu cần)

### 4.9. File liên quan
- **Controller**: 
  - `controllers/TourController.php` - method `logsStore()`, `logsUpdate()`, `logs()`
  - `controllers/PartnerController.php` - method `logs()` (cho HDV)
- **View**: 
  - `views/admin/tour-logs.php` (admin xem)
  - `views/admin/hdv_logs.php` (HDV xem)
- **Model**: `models/TourLog.php`
- **Database**: Bảng `tour_logs`

---

## TỔNG KẾT

### Điểm chung của 4 phần
1. **Xác thực người dùng**: Tất cả đều yêu cầu đăng nhập
2. **Phân quyền**: Admin có quyền cao hơn HDV
3. **Validation**: Kiểm tra dữ liệu đầu vào
4. **Thông báo**: Dùng session để hiển thị success/error messages
5. **Database**: Sử dụng PDO để truy vấn an toàn

### Công nghệ sử dụng
- **Backend**: PHP thuần, PDO
- **Frontend**: HTML, CSS (Bootstrap 5), JavaScript (AJAX/Fetch API)
- **Database**: MySQL/MariaDB
- **Chart**: Chart.js (cho biểu đồ)

### Lưu ý khi vấn đáp
1. **Nêu rõ mục đích** của từng phần
2. **Giải thích luồng hoạt động** (workflow)
3. **Nêu các tính năng đặc biệt** (đồng bộ, validation, v.v.)
4. **Minh họa bằng ví dụ** cụ thể nếu có thể
5. **Nhấn mạnh tính năng nổi bật** (AJAX, tự động hóa, v.v.)

---

## CÂU HỎI VẤN ĐÁP

### PHẦN 1: BÁO CÁO - THỐNG KÊ

**Q1: Trang báo cáo hiển thị những thông tin gì?**
- Trang báo cáo hiển thị 4 chỉ số chính: Tổng khách hàng, Tour đang mở, Doanh thu tổng, và Tour chờ/xử lý. Ngoài ra còn có 2 biểu đồ: biểu đồ số tour được đặt theo ngày và biểu đồ doanh thu theo thời gian (tháng/quý/năm). Cuối cùng là danh sách các tour đã hoàn thành kèm thông tin chi tiết.

**Q2: Làm thế nào để tính doanh thu?**
- Doanh thu được tính bằng cách lấy tổng `deposit_amount` từ bảng `bookings`, nhưng chỉ tính các booking có status là `deposit`, `confirmed`, hoặc `completed`. Các booking `pending` hoặc `cancelled` không được tính vào doanh thu.

**Q3: Biểu đồ doanh thu có thể lọc theo những tiêu chí nào?**
- Có thể lọc theo: Tour cụ thể hoặc tất cả tour, kỳ báo cáo (tháng/quý/năm), năm, tháng (nếu chọn kỳ tháng), và quý (nếu chọn kỳ quý). Hệ thống sẽ tự động cập nhật biểu đồ và tổng doanh thu khi thay đổi các bộ lọc.

**Q4: Tại sao cần biểu đồ số tour được đặt?**
- Biểu đồ này giúp admin theo dõi xu hướng đặt tour theo từng ngày trong tháng, từ đó có thể nhận biết được những ngày nào có nhiều booking, những ngày nào ít, để có kế hoạch phân bổ nhân sự và tài nguyên hợp lý.

**Q5: Danh sách tour đã hoàn thành có ý nghĩa gì?**
- Danh sách này giúp admin đánh giá hiệu quả của các tour, xem tour nào đã hoàn thành, có bao nhiêu booking, doanh thu bao nhiêu, và có sự cố gì xảy ra không. Đây là dữ liệu quan trọng để cải thiện dịch vụ và lên kế hoạch cho các tour tương lai.

**Q6: Phần báo cáo hoạt động như thế nào?**
- Khi admin truy cập trang báo cáo, hệ thống sẽ: (1) Kiểm tra đăng nhập và quyền admin, (2) Truy vấn database để tính các chỉ số (tổng khách, tour mở, doanh thu, booking pending), (3) Lấy tháng/năm từ GET request (mặc định tháng/năm hiện tại), (4) Đếm số booking theo từng ngày trong tháng và vẽ biểu đồ cột, (5) Tính doanh thu theo kỳ đã chọn (tháng/quý/năm) và vẽ biểu đồ đường, (6) Truy vấn các tour đã hoàn thành với thông tin chi tiết, (7) Hiển thị tất cả dữ liệu lên giao diện.

**Q7: Biểu đồ doanh thu được tính toán ra sao?**
- Hệ thống gọi method `calculateRevenueData()` với các tham số: kỳ báo cáo (tháng/quý/năm), năm, tháng, quý, và tour_id. Sau đó: (1) Xây dựng SQL query với SUM(deposit_amount) từ bảng bookings, (2) Chỉ lấy các booking có status 'deposit', 'confirmed', 'completed', (3) Nhóm theo ngày (nếu kỳ=tháng) hoặc theo tháng (nếu kỳ=quý/năm), (4) Nếu có tour_id thì thêm điều kiện lọc theo tour, (5) Trả về mảng gồm labels (nhãn), values (giá trị), và total (tổng), (6) Frontend nhận dữ liệu và vẽ biểu đồ bằng Chart.js.

---

### PHẦN 2: DANH SÁCH KHÁCH HÀNG

**Q1: Tại sao cần quản lý khách hàng theo booking?**
- Mỗi booking có thể có nhiều khách (ví dụ: gia đình 4 người), nên cần quản lý thông tin từng khách riêng biệt. Việc quản lý theo booking giúp dễ dàng xem tất cả khách trong cùng một đoàn, đồng thời đảm bảo tính nhất quán về trạng thái thanh toán.

**Q2: Tính năng đồng bộ payment_status hoạt động như thế nào?**
- Khi thêm khách mới vào booking, hệ thống sẽ tự động kiểm tra trạng thái thanh toán của booking. Nếu booking đã `deposit` hoặc `paid`, khách mới sẽ tự động có status tương ứng. Đồng thời, hệ thống cũng cập nhật lại status cho tất cả khách cũ trong booking để đảm bảo tất cả khách trong cùng booking có cùng trạng thái thanh toán.

**Q3: Tại sao cần tự động tạo khách đầu tiên?**
- Khi tạo booking, có thể chỉ có thông tin `customer_name` mà chưa có chi tiết khách. Hệ thống tự động tạo khách đầu tiên từ `customer_name` để admin có thể bổ sung thông tin chi tiết sau, tránh mất dữ liệu và tiết kiệm thời gian.

**Q4: Validation trong phần khách hàng như thế nào?**
- Các trường bắt buộc là `booking_id` và `full_name`. Các trường còn lại như giới tính, ngày sinh, giấy tờ, liên hệ đều là tùy chọn. Hệ thống cũng kiểm tra số giấy tờ không được trùng trong cùng một booking để tránh nhầm lẫn.

**Q5: Có thể xóa khách khỏi booking không?**
- Có, admin có thể xóa khách khỏi booking. Tuy nhiên, hệ thống sẽ kiểm tra xem khách có thuộc booking đó không trước khi xóa. Việc xóa khách không ảnh hưởng đến booking, chỉ xóa thông tin khách đó khỏi danh sách.

**Q6: Phần quản lý khách hàng hoạt động như thế nào?**
- Luồng hoạt động: (1) Admin chọn booking từ dropdown → hệ thống lấy booking_id từ GET request, (2) Hệ thống truy vấn danh sách khách từ bảng `customers` theo booking_id, (3) Nếu booking chưa có khách nhưng có customer_name → tự động tạo khách đầu tiên với payment_status dựa trên booking status, (4) Hiển thị bảng danh sách khách với đầy đủ thông tin, (5) Khi thêm khách mới: validate dữ liệu → xác định payment_status → lưu khách mới → đồng bộ status cho khách cũ, (6) Khi sửa/xóa: kiểm tra quyền và dữ liệu hợp lệ → thực hiện thao tác → thông báo kết quả.

**Q7: Tính năng đồng bộ payment_status hoạt động ra sao?**
- Khi thêm khách mới: (1) Hệ thống kiểm tra booking status, (2) Nếu booking 'deposit' → payment_status = 'deposit', nếu 'paid'/'completed'/'confirmed' → 'paid', nếu 'pending' → lấy từ khách đầu tiên hoặc mặc định 'unpaid', (3) Gán status cho khách mới, (4) Query tất cả khách cũ trong booking, (5) So sánh status hiện tại với status mới, (6) Update từng khách cũ nếu khác nhau, (7) Thông báo số lượng khách đã được đồng bộ. Điều này đảm bảo tất cả khách trong cùng booking có cùng trạng thái thanh toán.

---

### PHẦN 3: QUẢN LÝ ĐOÀN KHÁCH - CHECK-IN

**Q1: Sự khác biệt giữa "Danh sách khách hàng" và "Quản lý đoàn khách" là gì?**
- "Danh sách khách hàng" quản lý thông tin chi tiết của khách theo booking (họ tên, giấy tờ, liên hệ). "Quản lý đoàn khách" tập trung vào quản lý trạng thái check-in và thanh toán của khách trong tour, phục vụ cho việc điều hành tour thực tế.

**Q2: Tại sao cần 3 trạng thái check-in?**
- 3 trạng thái giúp theo dõi chi tiết quá trình khách đến: "Chưa đến" - khách chưa xuất hiện, "Đã đến" - khách đã đến điểm tập trung nhưng chưa check-in chính thức, "Check-in" - đã xác nhận và hoàn tất thủ tục. Điều này giúp HDV và admin nắm được tình hình thực tế của đoàn.

**Q3: Cập nhật check-in có reload trang không?**
- Không, hệ thống sử dụng AJAX để cập nhật trạng thái check-in và thanh toán mà không cần reload trang. Điều này giúp trải nghiệm người dùng mượt mà hơn, đặc biệt khi cần cập nhật nhiều khách liên tiếp.

**Q4: Tổng hợp thống kê có ý nghĩa gì?**
- Thống kê giúp nhanh chóng nắm được tình hình tổng thể: tổng số khách, bao nhiêu đã check-in, bao nhiêu đã đến. Trạng thái tổng thể ("Đã check-in", "Đang check-in", "Chưa check-in") giúp đánh giá nhanh tiến độ của đoàn.

**Q5: Yêu cầu đặc biệt được xử lý như thế nào?**
- Nếu khách có yêu cầu đặc biệt (dị ứng, yêu cầu ăn uống, v.v.), hệ thống sẽ hiển thị badge cảnh báo ⚠️ bên cạnh tên khách. Khi cập nhật thông tin khách có yêu cầu đặc biệt, hệ thống sẽ hiển thị thông báo nhắc nhở để đảm bảo không bỏ sót.

**Q6: Phần quản lý đoàn khách - check-in hoạt động như thế nào?**
- Luồng xem danh sách: (1) Admin chọn tour group → hệ thống lấy group_id, (2) Truy vấn thông tin tour group và danh sách khách từ `tour_guests`, (3) Tính toán thống kê: tổng số, đã check-in, đã đến, (4) Xác định trạng thái tổng thể, (5) Hiển thị bảng danh sách. Luồng cập nhật check-in: (1) Admin chọn trạng thái từ dropdown, (2) Form tự động submit (onchange event), (3) Gửi AJAX request với guest_id, group_id, checkin_status, (4) Server validate và update database, (5) Kiểm tra yêu cầu đặc biệt → hiển thị cảnh báo nếu có, (6) Cập nhật UI: thay đổi màu badge, cập nhật thống kê, không reload trang.

**Q7: Cập nhật check-in không reload trang hoạt động ra sao?**
- Hệ thống sử dụng AJAX với Fetch API: (1) Khi admin thay đổi dropdown, JavaScript bắt sự kiện 'change', (2) Lấy giá trị mới và các thông tin cần thiết (guest_id, booking_id), (3) Gửi POST request với header 'Content-Type: application/json' và 'X-Requested-With: XMLHttpRequest', (4) Server nhận request, kiểm tra isAjax(), xử lý và trả về JSON response, (5) JavaScript nhận response, cập nhật UI (màu badge, thống kê), (6) Nếu lỗi → hiển thị thông báo và khôi phục giá trị cũ. Toàn bộ quá trình không reload trang, trải nghiệm mượt mà.

---

### PHẦN 4: NHẬT KÝ HƯỚNG DẪN VIÊN

**Q1: Tại sao cần nhật ký hướng dẫn viên?**
- Nhật ký giúp ghi lại các sự kiện, tình huống, và đánh giá trong quá trình dẫn tour. Đây là tài liệu quan trọng để đánh giá chất lượng tour, rút kinh nghiệm, và cải thiện dịch vụ. Ngoài ra, nhật ký cũng là bằng chứng khi có tranh chấp hoặc sự cố.

**Q2: Các loại nhật ký khác nhau như thế nào?**
- Có 4 loại: "Daily" - ghi chép hoạt động hàng ngày, "Incident" - ghi lại sự cố, vấn đề phát sinh, "Rating" - đánh giá tour, phối hợp, tinh thần làm việc, "Note" - ghi chú thông thường. Mỗi loại có các trường thông tin bổ sung phù hợp.

**Q3: Hệ thống tự động gắn guide_id như thế nào?**
- Khi HDV đăng nhập, hệ thống lưu `guide_id` vào session. Khi HDV tạo nhật ký mới, hệ thống tự động lấy `guide_id` từ session và gắn vào nhật ký, đảm bảo nhật ký được gán đúng với HDV tạo ra nó.

**Q4: Tại sao cần gộp mô tả với các trường bổ sung?**
- Các trường bổ sung như thời tiết, sức khỏe khách, phản hồi được gộp vào `description` với emoji để dễ đọc và tìm kiếm. Điều này giúp khi xem lại nhật ký, có thể nhanh chóng nắm được các thông tin quan trọng.

**Q5: Phân quyền trong nhật ký như thế nào?**
- HDV chỉ có thể xem, tạo, và sửa nhật ký của chính mình. Admin có thể xem tất cả nhật ký của tất cả HDV, cập nhật trạng thái nhật ký (pending/reviewed/completed), đánh giá/rating nhật ký, và xóa nhật ký nếu cần. Điều này đảm bảo tính minh bạch và quản lý hiệu quả.

**Q6: Upload ảnh trong nhật ký có giới hạn gì không?**
- Hệ thống hỗ trợ upload ảnh kèm theo nhật ký. Ảnh được lưu vào thư mục `uploads` và đường dẫn được lưu vào database. Khi xem nhật ký, ảnh sẽ được hiển thị kèm theo để minh họa cho nội dung nhật ký.

**Q7: Phần nhật ký hướng dẫn viên hoạt động như thế nào?**
- Luồng tạo nhật ký: (1) HDV chọn tour và click "Thêm nhật ký", (2) Chọn loại nhật ký (daily/incident/rating/note), (3) Điền tiêu đề, mô tả (bắt buộc) và các trường bổ sung tùy loại, (4) Upload ảnh nếu có → validate và lưu vào thư mục uploads, (5) Chọn ngày/lịch trình liên quan, (6) Submit form → server tự động lấy guide_id từ session, (7) Gộp các trường bổ sung vào description với emoji, (8) Lưu vào bảng tour_logs với đầy đủ thông tin, (9) Thông báo kết quả và redirect. Luồng xem nhật ký: (1) Chọn tour và áp dụng filter (ngày/loại/trạng thái), (2) Truy vấn database (HDV chỉ thấy của mình, Admin thấy tất cả), (3) Hiển thị danh sách với đầy đủ thông tin và ảnh.

**Q8: Cập nhật trạng thái/rating nhật ký hoạt động ra sao?**
- Admin cập nhật trạng thái: (1) Click dropdown "Trạng thái" trong danh sách nhật ký, (2) Chọn giá trị mới (pending/reviewed/completed), (3) JavaScript gửi AJAX request với log_id và status, (4) Server validate (kiểm tra quyền admin, log_id hợp lệ), (5) Update status trong database, (6) Trả về JSON response, (7) Frontend cập nhật màu badge theo status mới. Tương tự với rating: chọn số sao (1-5) → gửi AJAX → update rating → cập nhật hiển thị số sao. Toàn bộ không reload trang.

**Q9: Hệ thống tự động gắn guide_id hoạt động như thế nào?**
- Khi HDV đăng nhập: (1) Hệ thống tìm guide_id từ bảng hdv dựa trên user_id hoặc email/contact, (2) Lưu guide_id vào session $_SESSION['user']['guide_id']. Khi tạo nhật ký: (1) HDV submit form tạo nhật ký, (2) Server kiểm tra role === 'hdv' và guide_id <= 0, (3) Tự động lấy guide_id từ $_SESSION['user']['guide_id'], (4) Gắn vào data array trước khi insert vào database, (5) Đảm bảo nhật ký luôn được gán đúng với HDV tạo ra nó, không thể giả mạo.

---

### CÂU HỎI TỔNG HỢP

**Q1: Hệ thống sử dụng công nghệ gì?**
- Backend: PHP thuần với PDO để truy vấn database an toàn. Frontend: HTML, CSS (Bootstrap 5), JavaScript với Fetch API cho AJAX. Database: MySQL/MariaDB. Biểu đồ: Chart.js.

**Q2: Bảo mật được đảm bảo như thế nào?**
- Tất cả các trang đều yêu cầu đăng nhập và kiểm tra session. Phân quyền rõ ràng giữa admin và HDV. Sử dụng PDO với prepared statements để tránh SQL injection. Validation dữ liệu đầu vào ở cả client và server side.

**Q3: Tính năng AJAX được sử dụng ở đâu?**
- AJAX được sử dụng trong: cập nhật trạng thái check-in/thanh toán (không reload trang), cập nhật trạng thái/rating nhật ký, cập nhật trạng thái lịch trình tour. Điều này giúp trải nghiệm người dùng mượt mà hơn.

**Q4: Làm thế nào để đảm bảo tính nhất quán dữ liệu?**
- Hệ thống có các cơ chế: đồng bộ payment_status cho tất cả khách trong cùng booking, tự động gắn guide_id từ session, validation dữ liệu đầu vào, kiểm tra duplicate (số giấy tờ), và sử dụng transaction khi cần thiết.

**Q5: Nếu có lỗi xảy ra, hệ thống xử lý như thế nào?**
- Hệ thống sử dụng try-catch để bắt lỗi, hiển thị thông báo lỗi rõ ràng cho người dùng qua session messages. Các lỗi database được log lại để debug. Trong trường hợp lỗi nghiêm trọng, hệ thống sẽ fallback về giá trị mặc định để trang vẫn có thể hiển thị.

**Q6: Hệ thống có thể mở rộng thêm tính năng gì?**
- Có thể mở rộng: thêm báo cáo chi tiết hơn (theo HDV, theo tour, theo thời gian), tích hợp thanh toán online, gửi email/SMS thông báo, ứng dụng mobile cho HDV, tích hợp bản đồ để theo dõi vị trí đoàn, và phân tích dữ liệu bằng AI để dự đoán xu hướng.

**Q7: Ưu điểm của việc sử dụng PHP thuần là gì?**
- PHP thuần dễ học, dễ hiểu, phù hợp cho người mới. Không cần framework phức tạp, dễ deploy và maintain. Code rõ ràng, dễ debug. Hiệu năng tốt cho ứng dụng quy mô vừa và nhỏ.

**Q8: Database được thiết kế như thế nào?**
- Database sử dụng quan hệ rõ ràng: `bookings` liên kết với `tours`, `customers` liên kết với `bookings`, `tour_groups` liên kết với `bookings`, `tour_guests` liên kết với `tour_groups`, `tour_logs` liên kết với `tours` và `hdv`. Sử dụng foreign key để đảm bảo tính toàn vẹn dữ liệu.

**Q9: Làm thế nào để tối ưu hiệu năng?**
- Sử dụng index trên các cột thường được truy vấn (tour_id, booking_id, guide_id). Chỉ lấy dữ liệu cần thiết, không select *. Sử dụng AJAX để giảm số lần reload trang. Cache các truy vấn không thay đổi thường xuyên.

**Q10: Kinh nghiệm rút ra từ dự án này?**
- Quan trọng nhất là thiết kế database hợp lý từ đầu. Validation dữ liệu ở cả client và server. Sử dụng AJAX để cải thiện UX. Code rõ ràng, dễ đọc, dễ maintain. Luôn có cơ chế xử lý lỗi và thông báo cho người dùng. Phân quyền rõ ràng để đảm bảo bảo mật.

**Q11: Luồng xử lý request trong hệ thống hoạt động như thế nào?**
- Khi người dùng thực hiện thao tác: (1) Frontend gửi request (GET/POST) đến server, (2) Router phân tích URL và gọi controller tương ứng, (3) Controller kiểm tra authentication (requireAuth), (4) Controller kiểm tra phân quyền (admin/hdv), (5) Controller gọi Model để truy vấn/update database, (6) Model sử dụng PDO với prepared statements để thực hiện SQL, (7) Controller xử lý kết quả và chuẩn bị dữ liệu, (8) Controller load View và truyền dữ liệu, (9) View render HTML và trả về cho client, (10) Nếu là AJAX → trả về JSON thay vì HTML. Toàn bộ quá trình có validation và xử lý lỗi ở mỗi bước.

**Q12: Session và authentication hoạt động ra sao?**
- Khi đăng nhập: (1) User nhập email/password, (2) Hệ thống tìm user trong database, (3) Verify password (hỗ trợ cả hash và plain text), (4) Tìm guide_id nếu là HDV, (5) Lưu thông tin vào $_SESSION['user'] (id, name, email, role, guide_id), (6) Redirect đến trang tương ứng. Khi truy cập trang: (1) Controller gọi requireAuth(), (2) Kiểm tra session_status, start session nếu chưa, (3) Kiểm tra $_SESSION['user'] tồn tại, (4) Nếu không → redirect về login, (5) Nếu có → tiếp tục xử lý. Session được duy trì cho đến khi logout hoặc hết hạn.

**Q13: Validation dữ liệu hoạt động như thế nào?**
- Client-side: (1) HTML5 validation (required, type, pattern), (2) JavaScript validate trước khi submit, (3) Hiển thị lỗi ngay lập tức. Server-side: (1) Kiểm tra REQUEST_METHOD (POST/GET), (2) Kiểm tra dữ liệu tồn tại (isset), (3) Validate format (email, phone, date), (4) Validate business logic (duplicate, range, relationship), (5) Sanitize dữ liệu (trim, htmlspecialchars), (6) Nếu lỗi → lưu vào session và redirect về form, (7) Nếu hợp lệ → xử lý và lưu database. Validation ở cả 2 tầng đảm bảo an toàn và UX tốt.
