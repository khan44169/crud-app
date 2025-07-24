<?php
function send_json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_error(string $msg, int $status = 400, array $errors = []): void {
    send_json(['success' => false, 'message' => $msg, 'errors' => $errors], $status);
}
