<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Corrected include path for the database connection
include '../backend/config/conn.php'; // Make sure this path is correct

// Check if the connection exists
if (!$conn) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

// Retrieve form data sent via POST
$qr_code = $_POST['qr_code'];
$photoData = $_POST['photoData'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];
$username = $_POST['username'];

// Check if all required parameters are present
if (!$qr_code || !$photoData || !$latitude || !$longitude || !$username) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit();
}

// Extract base64 data from the input
if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
    $data = substr($photoData, strpos($photoData, ',') + 1);
    $type = strtolower($type[1]); // jpg, png, gif
    if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid image type']);
        exit();
    }
    $data = base64_decode($data);
    if ($data === false) {
        echo json_encode(['status' => 'error', 'message' => 'Base64 decode failed']);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid image data']);
    exit();
}

// Generate a unique file name to avoid collisions
$fileName = uniqid('attendance_') . '.' . $type;
$filePath = '../media/' . $fileName;

// Save the image to the media directory
if (file_put_contents($filePath, $data) === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save image']);
    exit();
}

// Insert the attendance record into the database with the file path
$stmt = $conn->prepare("INSERT INTO attendance (username, qr_code, photo_data, latitude, longitude, attendance_date) VALUES (?, ?, ?, ?, ?, NOW())");
if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare SQL statement']);
    exit();
}

$stmt->bind_param("sssss", $username, $qr_code, $filePath, $latitude, $longitude);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Attendance recorded successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to record attendance']);
}

$stmt->close();
?>
