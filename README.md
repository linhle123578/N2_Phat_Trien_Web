**1. Clone dự án từ Github về máy tính cá nhân**

Mở Visual Code, tại thanh chức năng bên trái, chọn Source Control, màn hình sẽ hiển thị ra 2 lựa chọn như dưới đây, bấm Clone Repository.

<img width="1450" height="936" alt="Screenshot 2026-05-25 154607" src="https://github.com/user-attachments/assets/98dd4364-95df-44b2-8a55-dbcdfd3ddc75" />

Sau đó dán đường dẫn “https://github.com/linhle123578/N2_Phat_Trien_Web.git” và Enter. Sau khi clone về thành công, thư mục sẽ được tải về máy cá nhân.

<img width="1457" height="413" alt="Screenshot 2026-05-25 154345" src="https://github.com/user-attachments/assets/d3ad283b-5672-442c-9899-1e3f2e2d5655" />


<img width="1353" height="555" alt="Screenshot 2026-05-25 154135" src="https://github.com/user-attachments/assets/90ca4254-ddb6-42b1-b81b-768b703cdb3b" />

**2. Cách kết nối Chatbot với Gemini và phương thức thanh toán Momo**

Tại Explore, vào app/controllers/customer/MomoPaymentController.php, ở ngay đầu trang sẽ có yêu cầu thay thế Partner Code, Access Key và Secret Key

<img width="1464" height="436" alt="Screenshot 2026-05-25 155246" src="https://github.com/user-attachments/assets/64e882b0-c1c1-4f1b-b39e-14a95934f3d5" />

Sau đó thay thế như sau:

    MOMO_PARTNER_CODE = /MOMONPYO20260523_TEST/
    MOMO_ACCESS_KEY =  /NSnc24IkFHNNbf9M/
    MOMO_SECRET_KEY =  /JV9rVpwP4lA00Y2igeFqWlsb1bxNfUIl/
  
Lưu ý: Bỏ dấu "/" khi nhập vào code như hình dưới đây

<img width="1119" height="381" alt="Screenshot 2026-05-28 233436" src="https://github.com/user-attachments/assets/5623f6d7-a2e0-4ea6-af9e-cb90c47c272d" />


**3. Cách chạy giao diện và đăng nhập vào hệ thống**

Đầu tiên, cài đặt PHP Server từ phần Extensions, bấm Install để cài đặt

<img width="1280" height="662" alt="Screenshot 2026-05-30 202211" src="https://github.com/user-attachments/assets/7b31d49f-dbc2-4040-9e21-e3b41923dce8" />

Sau khi cài đặt xong, tại Explore, chọn app/views/customer/TrangChu.php, chọn icon PHP Server ở góc trên bên phải, hệ thống sẽ tự động chuyển sang trang web http://localhost:3000/app/views/customer/TrangChu.php

<img width="2559" height="1497" alt="Screenshot 2026-05-30 202323" src="https://github.com/user-attachments/assets/f740f89b-d12e-474b-82b5-0fe5bd595f87" />

Để đăng nhập, chọn chức năng Đăng nhập
Nếu chọn tài khoản Khách hàng, đăng nhập tài khoản như dưới đây:

    Email: ngoc.diep.vu@gmail.com
    Mật khẩu: 123456
    
<img width="2129" height="1313" alt="Screenshot 2026-05-30 202517" src="https://github.com/user-attachments/assets/7e11809c-ab67-4b45-b525-afcfaaa49be6" />

Sau khi đăng nhập thành công vào ứng dụng, chọn các chức năng trên thanh menu để trải nghiệm ứng dụng ở vai trò là người dùng

<img width="2556" height="1504" alt="Screenshot 2026-05-30 202718" src="https://github.com/user-attachments/assets/56ac611b-f09e-421c-abc0-ecd852c9a550" />

Nếu chọn tài khoản Quản lý, đăng nhập tài khoản như dưới đây:

    Email: quanlykho@farm2home.vn
    Mật khẩu: Admin@123

<img width="2096" height="1247" alt="Screenshot 2026-05-30 202828" src="https://github.com/user-attachments/assets/d2bffb0f-de5a-4490-b76a-497fe2eac70c" />

Sau khi đăng nhập thành công vào ứng dụng, chọn các chức năng trên thanh menu để trải nghiệm ứng dụng ở vai trò là quản trị viên
<img width="2559" height="1499" alt="Screenshot 2026-05-30 202928" src="https://github.com/user-attachments/assets/6813641f-dd9a-4449-9015-0970e850aa32" />

**4. Cách thanh toán với phương thức Momo**

Đầu tiên cần xoá ứng dụng MoMo chính thức nếu đang cài trên điện thoại. Sau đó, tải và cài đặt ứng dụng MoMo Test (UAT) theo đường link: https://developers.momo.vn/v3/download

Tiếp theo, Tạo tài khoản ví test theo các bước sau:

    Bước 1. Nhập số điện thoại của bạn
    
    Bước 2. Nhập OTP mặc định là là 0000 hoặc 000000 trên App MoMo test
    
    Bước 3. Nhập mật khẩu theo mật khẩu mặc định 000000
    
    Bước 4. Điền thông tin cá nhân.

Tiếp đến, cần liên kết ngân hàng & và thêm số dư ví theo các bước:

    Bước 1. Ở góc phải phía bên dưới chọn "Ví của tôi", chọn "Liên kết tài khoản" hoặc chọn "Nạp tiền" ngay tại góc trái màn hình chính.
    
    Bước 2. Chọn ngân hàng (Agribank) rồi chọn "Liên kết bằng số thẻ ATM"
    
    Bước 3. Nhập thông tin thẻ ATM.
        Số thẻ: 9704 05XX XXXX XXXX (16 chữ số, X là số bất kỳ từ 0-9)
        Họ và tên chủ thẻ
        Ngày phát hành
        
    Bước 4. Nạp tiền
    Màn hình chính chọn Nạp tiền. Sau đó Nhập số tiền cần nạp là 5 000 000 VND. Tiếp đến nhập mật khẩu là 000000 và nhập OTP (nếu có)

Sau khi đã hoàn tất cài đặt, có thể sử dụng ứng dụng trên để thanh toán khi sản phẩm từ Farrm2Home.

<img width="2507" height="1342" alt="Screenshot 2026-05-30 205842" src="https://github.com/user-attachments/assets/1a5af7b3-7245-4e6f-bf62-fea944a72fae" />

**5. Quên mật khẩu**

Truy cập vào đường link https://resend.com/api-keys và Đăng nhập bằng Email bạn muốn dùng để Khôi phục mật khẩu.

Bấm vào nút “Create API Key”.

Đặt tên cho key (Ví dụ: Farm2Home) và chọn quyền là Full Access → Bấm Add.

Copy lại chuỗi mã API Key vừa hiển thị (nó sẽ có dạng re_123456789...). Lưu ý: Bạn chỉ nhìn thấy mã một lần duy nhất nên hãy lưu lại nhé.

Tại file Explore, chọn app/controllers/customer/LogInController.php, ở dòng số **61**, thay chuỗi ‘re_123456789…’ bằng API Key thật của bạn đã lấy trên Resend. 

Sau khi đã hoàn tất các bước trên, bạn có thể sử dụng tính năng Khôi phục mật khẩu từ Farrm2Home.
