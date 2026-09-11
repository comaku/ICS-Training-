<?php

header('Content-Type: application/json');

require_once 'config.php';

/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Read JSON sent by JavaScript
|--------------------------------------------------------------------------
*/

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Get form data
|--------------------------------------------------------------------------
*/

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$company = trim($input['company'] ?? '');
$message = trim($input['message'] ?? '');

/*
|--------------------------------------------------------------------------
| Validate required fields
|--------------------------------------------------------------------------
*/

if ($name === '' || $email === '' || $message === '') {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required fields.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Validate email
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Prepare data for Supabase
|--------------------------------------------------------------------------
*/

$data = [
    'name' => $name,
    'email' => $email,
    'company' => $company,
    'message' => $message
];

/*
|--------------------------------------------------------------------------
| Send data to Supabase
|--------------------------------------------------------------------------
*/

$url = rtrim(SUPABASE_URL, '/') . '/rest/v1/contacts';

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,

    CURLOPT_HTTPHEADER => [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ],

    CURLOPT_POSTFIELDS => json_encode($data),

    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$curlError = curl_error($ch);

curl_close($ch);

/*
|--------------------------------------------------------------------------
| Handle cURL error
|--------------------------------------------------------------------------
*/

if ($response === false || $curlError) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to connect to the database.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Handle Supabase error
|--------------------------------------------------------------------------
*/

if ($httpCode < 200 || $httpCode >= 300) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Your enquiry could not be saved.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Success
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'message' => 'Your enquiry has been submitted successfully.'
]);

?>
