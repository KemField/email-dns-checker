<?php

// Set JSON header and CORS (if needed for local development, though here we load it from the same host)
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Require the DNSChecker core logic
require_once __DIR__ . '/DNSChecker.php';

try {
    // We accept both GET and POST requests for versatility
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

    // Basic regex checks for domains (must not contain spaces and have at least one dot, or match demo patterns)
    if (!str_starts_with($domain, 'demo-') && !preg_match('/^[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,15}$/', $domain)) {
        // Run some additional parsing to see if it's a URL and clean it up
        if (filter_var($domain, FILTER_VALIDATE_URL) || str_contains($domain, '/') || str_contains($domain, ':')) {
            // DNSChecker constructor handles URL parsing, but let's let it run if it contains some alphabetic characters
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid domain name (e.g., google.com, example.org).'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $checker = new DNSChecker($domain, $selector);
    $result = $checker->check();

    echo json_encode(array_merge(['status' => 'success'], $result), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An internal server error occurred while performing DNS diagnostics.',
        'debug' => $e->getMessage() // Handy for portfolio review
    ], JSON_UNESCAPED_UNICODE);
}
