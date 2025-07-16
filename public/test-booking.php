<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    exit(0);
}

$data = json_decode(file_get_contents("php://input"), true);
$data["user_id"] = 1;
$data["status"] = "confirmed";
$data["attended"] = false;
$data["booking_time"] = date("Y-m-d H:i:s");
$data["id"] = rand(1000, 9999);

echo json_encode([
    "success" => true,
    "message" => "Η κράτηση πραγματοποιήθηκε επιτυχώς.",
    "booking" => $data
]);
?>
