<?php

// helpers/response.php  –  Unified JSON response helper


/**
 * Send a JSON response and terminate execution.
 *
 * @param mixed $data    Payload to encode
 * @param int   $code    HTTP status code (default 200)
 * @param bool  $success Adds a top-level "success" flag
 */
function jsonResponse(mixed $data, int $code = 200, bool $success = true): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send a standardised error response and terminate.
 *
 * @param string $message Human-readable error
 * @param int    $code    HTTP status code (default 400)
 */
function errorResponse(string $message, int $code = 400): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error'   => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Set CORS headers. Call at the top of every API file.
 */
function setCorsHeaders(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    // Preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Decode the raw JSON request body into an associative array.
 * Returns empty array if body is absent or malformed.
 */
function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST ?: [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Verify the simple shared-secret token from the Authorization header.
 * Header format:  Authorization: Bearer <API_SECRET>
 */
function requireAuth(): void
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '');

    $token = '';
    if (str_starts_with($header, 'Bearer ')) {
        $token = trim(substr($header, 7));
    }

    if ($token !== API_SECRET) {
        errorResponse('Unauthorized. Provide a valid Bearer token.', 401);
    }
}
