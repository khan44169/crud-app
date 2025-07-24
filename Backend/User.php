<?php
// User.php
require_once __DIR__ . '/config.php';

class User {

    /** Insert user. Returns inserted ID or false. */
    public static function create(string $name, string $email, string $password, string $dob) {
        $conn = db();

        // Unique email check
        if (self::getRawByEmail($email)) {
            return false; // caller handles 409
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql  = "INSERT INTO users (name, email, password_hash, dob) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $hash, $dob);
        $stmt->execute();
        return $conn->insert_id ?: false;
    }

    /** Return ALL users (no pagination; consider adding). */
    public static function getAll(): array {
        $conn = db();
        $sql  = "SELECT id, name, email, dob FROM users ORDER BY id DESC";
        $result = $conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /** Public getter by ID (safe fields). */
    public static function getById(int $id): ?array {
        $raw = self::getRawById($id);
        return $raw ? self::publicFields($raw) : null;
    }

    /** Update user (full replace). Returns bool. */
    public static function update(int $id, string $name, string $email, string $password, string $dob): bool {
        $conn = db();

        // Check email collision w/ other user
        $existing = self::getRawByEmail($email);
        if ($existing && (int)$existing['id'] !== $id) {
            return false; // caller chooses 409 response
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql  = "UPDATE users SET name=?, email=?, password_hash=?, dob=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $email, $hash, $dob, $id);
        $stmt->execute();
        return $stmt->affected_rows >= 0; // >=0: even if unchanged, treat success
    }

    /** Delete user. */
    public static function delete(int $id): bool {
        $conn = db();
        $sql  = "DELETE FROM users WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /* ---------- Internal helpers ---------- */

    private static function getRawById(int $id): ?array {
        $conn = db();
        $sql  = "SELECT * FROM users WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return $row ?: null;
    }

    private static function getRawByEmail(string $email): ?array {
        $conn = db();
        $sql  = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return $row ?: null;
    }

    private static function publicFields(array $row): array {
        return [
            'id'         => $row['id'],
            'name'       => $row['name'],
            'email'      => $row['email'],
            'dob'        => $row['dob'],
            
        ];
    }
}
