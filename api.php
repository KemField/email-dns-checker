<?php

/**
 * api.php - Secure JSON API Endpoint for DNS Diagnostics
 * 
 * Supports GET/POST methods, enforces rate limiting, sanitizes inputs, 
 * runs SSRF safety validation, and returns clean diagnostic data.
 */

// Start session securely for rate limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Strict Security Headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// 2. HTTP Method Filter
$allowedMethods = ['GET', 'POST'];
if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed. Only GET and POST are supported.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Session-based Rate Limiter (Max 10 requests per minute)
$now = time();
if (!isset($_SESSION['rate_limit_requests'])) {
    $_SESSION['rate_limit_requests'] = [];
}

// Clear requests older than 60 seconds
$_SESSION['rate_limit_requests'] = array_filter(
    $_SESSION['rate_limit_requests'],
    fn($t) => $t > ($now - 60)
);

$_SESSION['rate_limit_requests'][] = $now;
$requestCount = count($_SESSION['rate_limit_requests']);
$limit = 10;
$remaining = max(0, $limit - $requestCount);

header("X-RateLimit-Limit: $limit");
header("X-RateLimit-Remaining: $remaining");

if ($requestCount > $limit) {
    http_response_code(429);
    header("Retry-After: 60");
    echo json_encode([
        'status' => 'error',
        'message' => 'Too many requests. Please wait 60 seconds before scanning again.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Input Parsing & RFC 1035 / Safe Domain Filtering
require_once __DIR__ . '/DNSChecker.php';

$domain = isset($_REQUEST['domain']) ? trim($_REQUEST['domain']) : '';
$selector = isset($_REQUEST['selector']) ? trim($_REQUEST['selector']) : null;

if (empty($domain)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'A domain name must be specified.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Strip protocol if user pasted a URL
if (preg_match('/^https?:\/\//i', $domain)) {
    $parsedUrl = parse_url($domain);
    $domain = $parsedUrl['host'] ?? $domain;
}
$domain = strtolower(preg_replace('/^www\./i', '', explode('/', $domain)[0]));

// Perform domain name check (strict RFC 1035 check)
$domainRegex = '/^(?!:\/\/)([a-zA-Z0-9-_]{1,63}\.)+[a-zA-Z]{2,15}$/';
if (!preg_match($domainRegex, $domain)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter a valid domain name (e.g., google.com, company.hu).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. Execution and Error Handling
try {
    $checker = new DNSChecker($domain, $selector);
    $result = $checker->check();

    echo json_encode(array_merge(['status' => 'success'], $result), JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An internal server error occurred while performing DNS diagnostics.'
    ], JSON_UNESCAPED_UNICODE);
}
