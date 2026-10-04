<?php
// Optional mailer for Hostinger (or any PHP host).
// To use it, build the site with VITE_FORM_ENDPOINT=/enquiry.php
// Sends the "Make your trip" request to the address below.

$to = 'sales@taxity.in';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

function clean($value, $max = 200)
{
    $value = is_string($value) ? $value : '';
    $value = trim(str_replace(["\r", "\n"], ' ', strip_tags($value)));
    return mb_substr($value, 0, $max);
}

$name    = clean($data['name'] ?? '');
$phone   = clean($data['phone'] ?? '', 20);
$pickup  = clean($data['pickup_location'] ?? '');
$dropoff = clean($data['dropoff_location'] ?? '');
$pdate   = clean($data['pickup_date'] ?? '', 20);
$ddate   = clean($data['dropoff_date'] ?? '', 20);
$ptime   = clean($data['pickup_time'] ?? '', 20);

$digits = preg_replace('/\D/', '', $phone);
if ($name === '' || $pickup === '' || $dropoff === '' || strlen($digits) < 10 || strlen($digits) > 13) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields']);
    exit;
}

$host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['SERVER_NAME'] ?? 'taxity.in');
$subject = 'Booking request from ' . $name;
$body = "Name: $name\n"
      . "Phone: $phone\n"
      . "Pick-up location: $pickup\n"
      . "Drop-off location: $dropoff\n"
      . 'Pick-up date: ' . ($pdate ?: 'Not given') . "\n"
      . 'Drop-off date: ' . ($ddate ?: 'Not given') . "\n"
      . 'Pick-up time: ' . ($ptime ?: 'Not given') . "\n";
$headers = "From: Taxity website <no-reply@$host>\r\n"
         . "Content-Type: text/plain; charset=utf-8\r\n";

$sent = mail($to, $subject, $body, $headers);

http_response_code($sent ? 200 : 500);
echo json_encode(['success' => (bool) $sent]);
