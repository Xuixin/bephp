<?php

$host = 'localhost';
$db = 'restaurant';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $con = new PDO($dsn, $user, $pass, $options);
    echo "เชื่อมต่อฐานข้อมูลสำเร็จ!";
} catch (PDOException $e) {

    echo "ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage();
}

global $con;

class Database
{
    private static $db;

    public function __construct()
    {
        if (self::$db === null) {
            self::$db = $GLOBALS['con'];
        }
    }

    public static function query($sql, $params = [])
    {
        try {
            $stmt = self::$db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดในการ query
            echo "เกิดข้อผิดพลาดในการ query: " . $e->getMessage();
            return [];
        }
    }

    public static function findUnique($table, $conditions)
    {
        try {
            // ตรวจสอบว่า $conditions เป็น array และไม่ว่าง
            if (!is_array($conditions) || empty($conditions)) {
                throw new InvalidArgumentException("ต้องการเงื่อนไขอย่างน้อยหนึ่งเงื่อนไข");
            }

            $keys = array_keys($conditions); // รับคีย์จากเงื่อนไข
            $values = array_values($conditions); // รับค่าจากเงื่อนไข

            // สร้าง SQL query โดยใช้ ? สำหรับ parameter
            $whereClause = implode(' AND ', array_map(function ($key) {
                return "$key = ?";
            }, $keys));

            $sql = "SELECT * FROM $table WHERE $whereClause"; // สร้าง SQL query

            $stmt = self::$db->prepare($sql);
            $stmt->execute($values); // ส่งค่าที่ตรงกับคีย์ไปใน execute
            return $stmt->fetch(PDO::FETCH_ASSOC); // คืนค่าผลลัพธ์
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดในการ query
            echo "เกิดข้อผิดพลาดในการ query: " . $e->getMessage();
            return null;
        } catch (InvalidArgumentException $e) {
            echo "เกิดข้อผิดพลาด: " . $e->getMessage();
            return null;
        }
    }

    public static function findAll($table)
    {
        try {
            $sql = "SELECT * FROM $table"; // สร้าง SQL query
            $stmt = self::$db->prepare($sql);
            $stmt->execute(); // ดำเนินการ query
            return $stmt->fetchAll(PDO::FETCH_ASSOC); // คืนค่าผลลัพธ์
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดในการ query
            echo "เกิดข้อผิดพลาดในการ query: " . $e->getMessage();
            return null;
        }
    }

    public static function create($table, $data)
    {
        try {
            $keys = array_keys($data);
            $placeholders = rtrim(str_repeat('?, ', count($data)), ', '); // สร้าง placeholders

            $sql = "INSERT INTO $table (" . implode(',', $keys) . ") VALUES ($placeholders)"; // สร้าง SQL query
            $stmt = self::$db->prepare($sql);
            $stmt->execute(array_values($data)); // ส่งค่าที่ตรงกับคีย์ไปใน execute

            return self::$db->lastInsertId(); // คืนค่า ID ล่าสุดที่ถูกสร้าง
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดในการ query
            echo "เกิดข้อผิดพลาดในการสร้างข้อมูล: " . $e->getMessage();
            return null;
        }
    }

    public static function update($table, $data, $conditions)
    {
        try {
            $setClause = implode(', ', array_map(function ($key) {
                return "$key = ?";
            }, array_keys($data))); // สร้าง set clause

            $whereClause = implode(' AND ', array_map(function ($key) {
                return "$key = ?";
            }, array_keys($conditions))); // สร้าง where clause

            $sql = "UPDATE $table SET $setClause WHERE $whereClause"; // สร้าง SQL query
            $stmt = self::$db->prepare($sql);
            $stmt->execute(array_merge(array_values($data), array_values($conditions))); // ส่งค่าที่ตรงกับคีย์ไปใน execute

            return $stmt->rowCount(); // คืนค่าจำนวนแถวที่ถูกอัปเดต
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดในการ query
            echo "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage();
            return null;
        }
    }

    public static function upsert($table, $data)
    {
        try {
            $keys = array_keys($data);
            $placeholders = rtrim(str_repeat('?, ', count($data)), ', '); // สร้าง placeholders

            // สร้าง SQL query สำหรับ UPSERT
            $updateClause = implode(', ', array_map(function ($key) {
                return "$key = $key + ?";
            }, $keys));

            $sql = "INSERT INTO $table (" . implode(',', $keys) . ") VALUES ($placeholders)
            ON DUPLICATE KEY UPDATE $updateClause"; // สร้าง SQL query

            $stmt = self::$db->prepare($sql);

            // ค่าที่จะใช้ในการเพิ่ม
            $values = array_merge(array_values($data), [1]); // เพิ่มค่า 1 เพื่อเพิ่มจำนวน

            $stmt->execute($values); // ส่งค่าที่ตรงกับคีย์ไปใน execute

            return self::$db->lastInsertId(); // คืนค่า ID ล่าสุดที่ถูกสร้าง
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดในการ query
            echo "เกิดข้อผิดพลาดในการ UPSERT ข้อมูล: " . $e->getMessage();
            return null;
        }
    }



    public static function delete($table, $conditions)
    {
        try {
            $whereClause = implode(' AND ', array_map(function ($key) {
                return "$key = ?";
            }, array_keys($conditions))); // สร้าง where clause

            $sql = "DELETE FROM $table WHERE $whereClause"; // สร้าง SQL query
            $stmt = self::$db->prepare($sql);
            $stmt->execute(array_values($conditions)); // ส่งค่าที่ตรงกับคีย์ไปใน execute

            return $stmt->rowCount(); // คืนค่าจำนวนแถวที่ถูกลบ
        } catch (PDOException $e) {
            // จัดการข้อผิดพลาดในการ query
            echo "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
            return null;
        }
    }
}
