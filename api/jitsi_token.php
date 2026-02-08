<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function sign_jwt_hs256($header, $payload, $secret) {
    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload))
    ];
    $signing_input = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing_input, $secret, true);
    $segments[] = base64url_encode($signature);
    return implode('.', $segments);
}

$data = json_decode(file_get_contents("php://input"), true);
$meeting_id = $data['meeting_id'] ?? '';
$name = $data['name'] ?? 'Guest';
$role = $data['role'] ?? 'patient';

if (!$meeting_id) {
    echo json_encode(['success' => false, 'error' => 'Missing meeting_id']);
    exit;
}

$now = time();
$exp = $now + 2 * 60 * 60; // 2 hours

$header = [
    'alg' => 'HS256',
    'typ' => 'JWT'
];

$payload = [
    'iss' => JITSI_ISSUER,
    'aud' => 'jitsi',
    'sub' => JITSI_DOMAIN,
    'room' => 'MediConnect360-' . $meeting_id,
    'exp' => $exp,
    'nbf' => $now - 10,
    'context' => [
        'user' => [
            'name' => $name,
            'id' => uniqid('u_', true),
            'role' => $role
        ],
        'features' => [
            'livestreaming' => false,
            'recording' => false,
            'transcription' => false,
            'outbound-call' => false
        ]
    ]
];

$jwt = sign_jwt_hs256($header, $payload, JITSI_JWT_SECRET);

echo json_encode([
    'success' => true,
    'token' => $jwt,
    'domain' => JITSI_DOMAIN,
    'appId' => JITSI_APP_ID
]);
