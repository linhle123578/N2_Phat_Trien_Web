<?php
require_once __DIR__ . '/../../models/ProfileAdminModel.php';

class ProfileAdminController
{
    private $model;
    private $admin_id;

    public function __construct($conn)
    {
        $this->model = new ProfileAdminModel($conn);
        // Fallback for demo
        $this->admin_id = $_SESSION['admin_id'] ?? 'ADM001';
    }

    public function index()
    {
        // 1. Ddmin info
        $admin = [
            'admin_id'   => $this->admin_id,
            'full_name'  => 'Admin Farm2Home',
            'birthday'   => '',
            'phone'      => '0933 111 222',
            'gender'     => 'Nam',
            'address'    => 'Hà Nội, Việt Nam',
            'department' => 'Quản trị hệ thống',
            'account_id' => 'ACC001',
            'email'      => 'admin@farm2home.vn',
            'avatar'     => 'user_1.jpg',
            'role'       => 'Quản trị viên',
        ];

        // 2. Fetch from DB
        $dbAdmin = $this->model->getAdminWithAccount($this->admin_id);
        if ($dbAdmin) {
            $admin = array_merge($admin, $dbAdmin);
        }

        $msg_profile  = '';
        $msg_password = '';

        // 3. Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['save_profile'])) {
                $data = [
                    'admin_id'   => $this->admin_id,
                    'account_id' => $admin['account_id'] ?? null,
                    'full_name'  => trim($_POST['full_name'] ?? ''),
                    'phone'      => trim($_POST['phone'] ?? ''),
                    'birthday'   => trim($_POST['birthday'] ?? ''),
                    'gender'     => trim($_POST['gender'] ?? ''),
                    'address'    => trim($_POST['address'] ?? ''),
                    'department' => trim($_POST['department'] ?? ''),
                    'email'      => trim($_POST['email'] ?? ''),
                ];
                
                if ($this->model->updateProfile($data)) {
                    $msg_profile = "Cập nhật hồ sơ thành công!";
                    $admin = array_merge($admin, $data);
                } else {
                    $msg_profile = "Cập nhật hồ sơ thất bại!";
                }
            } elseif (isset($_POST['change_password'])) {
                $old_password = $_POST['old_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if ($new_password !== $confirm_password) {
                    $msg_password = "Mật khẩu xác nhận không khớp!";
                } else {
                    $result = $this->model->updatePassword($admin['account_id'], $old_password, $new_password);
                    if ($result) {
                        $msg_password = "Đổi mật khẩu thành công!";
                    } else {
                        $msg_password = "Mật khẩu cũ không chính xác hoặc có lỗi xảy ra!";
                    }
                }
            }
        }

        require_once __DIR__ . '/../../views/admin/ProfileAdmin.php';
    }
}
