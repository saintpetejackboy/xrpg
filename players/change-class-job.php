<?php
// /players/apply-class-job-change.php - Backend handler for class and job changes

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/db.php';

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['succes
