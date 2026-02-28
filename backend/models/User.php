<?php
require_once __DIR__ . '/../config/db.php';

class User {
    private $conn;
    // Separate tables per role
    private $tables = [
        'user' => 'users',
        'vendor' => 'vendors',
        'admin' => 'admins'
    ];

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $role = $data['role'];
        $table = $this->tables[$role] ?? null;
        if (!$table) return false;

        $name = htmlspecialchars(strip_tags($data['full_name']));
        $email = htmlspecialchars(strip_tags($data['email']));
        $phone = htmlspecialchars(strip_tags($data['phone']));
        $password = password_hash($data['password'], PASSWORD_BCRYPT);

        if ($role === 'vendor') {
            $query = "INSERT INTO vendors (full_name, email, password, phone, business_name, pan_number, address)
                      VALUES (:name, :email, :password, :phone, :business_name, :pan_number, :address)";
            $stmt = $this->conn->prepare($query);
            $bname = isset($data['business_name']) ? htmlspecialchars(strip_tags($data['business_name'])) : null;
            $pan = isset($data['pan_number']) ? htmlspecialchars(strip_tags($data['pan_number'])) : null;
            $addr = isset($data['address']) ? htmlspecialchars(strip_tags($data['address'])) : null;
            $stmt->bindParam(":business_name", $bname);
            $stmt->bindParam(":pan_number", $pan);
            $stmt->bindParam(":address", $addr);
        } else {
            $query = "INSERT INTO $table (full_name, email, password, phone) VALUES (:name, :email, :password, :phone)";
            $stmt = $this->conn->prepare($query);
        }

        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":phone", $phone);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function isEmailTaken($email) {
        foreach ($this->tables as $table) {
            $stmt = $this->conn->prepare("SELECT id FROM $table WHERE email = ? LIMIT 1");
            $stmt->bindParam(1, $email);
            $stmt->execute();
            if ($stmt->rowCount() > 0) return true;
        }
        return false;
    }

    public function validateCredentials($email, $password) {
        foreach ($this->tables as $role => $table) {
            $stmt = $this->conn->prepare("SELECT id, full_name, email, password, phone FROM $table WHERE email = ? LIMIT 1");
            $stmt->bindParam(1, $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $row['password'])) {
                    unset($row['password']);
                    $row['role'] = $role;
                    return $row;
                }
            }
        }
        return false;
    }

    public function findById($id, $role = null) {
        if ($role && isset($this->tables[$role])) {
            $table = $this->tables[$role];
            $stmt = $this->conn->prepare("SELECT id, full_name, email, phone FROM $table WHERE id = ? LIMIT 1");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $row['role'] = $role;
                return $row;
            }
            return false;
        }
        // Fallback: search all tables
        foreach ($this->tables as $r => $table) {
            $stmt = $this->conn->prepare("SELECT id, full_name, email, phone FROM $table WHERE id = ? LIMIT 1");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $row['role'] = $r;
                return $row;
            }
        }
        return false;
    }

    public function countUsers() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function countVendors() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM vendors");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function findAll() {
        $query = "SELECT id, full_name, email, 'user' as role, phone, created_at FROM users
                  UNION ALL
                  SELECT id, full_name, email, 'vendor' as role, phone, created_at FROM vendors
                  UNION ALL
                  SELECT id, full_name, email, 'admin' as role, phone, created_at FROM admins
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>