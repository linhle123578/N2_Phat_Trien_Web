<?php
class ProfileAdminModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }
    public function getAdminWithAccount(string $admin_id): ?array
    {
        $sql = "
            SELECT
                a.admin_id,
                a.full_name,
                a.birthday,
                a.phone,
                a.gender,
                a.address,
                a.department,
                a.account_id,
                acc.email,
                acc.avatar,
                acc.role
            FROM admin a
            LEFT JOIN account acc ON a.account_id = acc.account_id
            WHERE a.admin_id = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, 's', $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function updateProfile(array $data): bool
    {
        $sql1 = "UPDATE admin SET full_name=?, phone=?, birthday=?, gender=?, address=?, department=? WHERE admin_id=?";
        $stmt1 = mysqli_prepare($this->conn, $sql1);
        if (!$stmt1) return false;
        mysqli_stmt_bind_param($stmt1, 'sssssss',
            $data['full_name'], $data['phone'], $data['birthday'],
            $data['gender'], $data['address'], $data['department'], $data['admin_id']
        );
        $ok1 = mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);

        $ok2 = true;
        if (!empty($data['email']) && !empty($data['account_id'])) {
            $sql2 = "UPDATE account SET email=? WHERE account_id=?";
            $stmt2 = mysqli_prepare($this->conn, $sql2);
            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, 'ss', $data['email'], $data['account_id']);
                $ok2 = mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }
        }
        
        return $ok1 && $ok2;
    }
    
    public function updatePassword($account_id, $old_password, $new_password)
    {
        if (!$account_id) return false;
        
        $sql = "SELECT password FROM account WHERE account_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 's', $account_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($row && $row['password'] === $old_password) {
            $sql2 = "UPDATE account SET password = ? WHERE account_id = ?";
            $stmt2 = mysqli_prepare($this->conn, $sql2);
            if (!$stmt2) return false;
            mysqli_stmt_bind_param($stmt2, 'ss', $new_password, $account_id);
            $ok = mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
            return $ok;
        }
        return false;
    }
}
