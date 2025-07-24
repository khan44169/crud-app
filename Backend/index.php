<?php
// index.php

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/User.php';

// Basic CORS (dev)

header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// For preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit;
}


$method = $_SERVER['REQUEST_METHOD'];

// Normalize path: /user-api/index.php/users/1  OR /users/1
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', $path)));

// Strip leading folders until we reach 'users'
$idx = array_search('users', $segments, true);
if ($idx === false) {
    send_error('Endpoint not found.', 404);
}
$segments = array_slice($segments, $idx); // now 0=>'users', 1=>{id?}

$userId = isset($segments[1]) && ctype_digit($segments[1]) ? (int)$segments[1] : null;

/* ---- Route handling ---- */
switch ($method) {
    case 'GET':
        if ($userId === null) {
            $users = User::getAll();
            send_json(['success' => true, 'data' => $users]);
        } else {
            $user = User::getById($userId);
            if (!$user) {
                send_error('User not found.', 404);
            }
            send_json(['success' => true, 'data' => $user]);
        }
        break;

    case 'POST':
        if ($userId !== null) {
            send_error('POST not allowed on /users/{id}.', 405);
        }
        $in = json_decode(file_get_contents('php://input'), true);
        if (!is_array($in)) {
            send_error('Invalid JSON body.', 400);
        }
        $name = trim($in['name'] ?? '');
        $email = filter_var($in['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = (string)($in['password'] ?? '');
        $dob = $in['dob'] ?? '';
        $errors = [];
        if ($name === '') $errors['name'] = 'Required.';
        if (!$email) $errors['email'] = 'Invalid.';
        if (strlen($password) < 8) $errors['password'] = 'Min 8 chars.';
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dob)) $errors['dob'] = 'YYYY-MM-DD.';
        if ($errors) {
            send_error('Validation failed.', 422, $errors);
        }

        $id = User::create($name, $email, $password, $dob);
        if (!$id) {
            send_error('Email already exists or create failed.', 409);
        }
        $user = User::getById($id);
        send_json(['success' => true, 'data' => $user], 201);
        break;

    case 'PUT':
        if ($userId === null) {
            send_error('ID required for update.', 400);
        }
        $in = json_decode(file_get_contents('php://input'), true);
        if (!is_array($in)) {
            send_error('Invalid JSON body.', 400);
        }
        $name = trim($in['name'] ?? '');
        $email = filter_var($in['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = (string)($in['password'] ?? '');
        $dob = $in['dob'] ?? '';
        $errors = [];
        if ($name === '') $errors['name'] = 'Required.';
        if (!$email) $errors['email'] = 'Invalid.';
        if (strlen($password) < 8) $errors['password'] = 'Min 8 chars.';
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dob)) $errors['dob'] = 'YYYY-MM-DD.';
        if ($errors) {
            send_error('Validation failed.', 422, $errors);
        }

        $ok = User::update($userId, $name, $email, $password, $dob);
        if (!$ok) {
            // could be email collision
            send_error('Update failed (email exists?).', 409);
        }
        $user = User::getById($userId);
        send_json(['success' => true, 'data' => $user]);
        break;

    case 'DELETE':
        if ($userId === null) {
            send_error('ID required for delete.', 400);
        }
        $ok = User::delete($userId);
        if (!$ok) {
            send_error('User not found or delete failed.', 404);
        }
        send_json(['success' => true, 'message' => 'User deleted.']);
        break;

    default:
        send_error('Method not allowed.', 405);
}
