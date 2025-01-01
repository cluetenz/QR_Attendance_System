<?php
session_start();
include 'config/conn.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$username = $_SESSION['username'];

// Fetch attendance records for the logged-in user
$stmt = $conn->prepare("
    SELECT a.attendance_date, a.photo_data, a.latitude, a.longitude, 
           s.student_id, s.student_name, s.student_course, s.student_semester, s.student_section
    FROM attendance a
    JOIN students s ON a.username = s.username
    WHERE a.username = ?
    AND DATE(a.attendance_date) = CURDATE() 
");

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$attendanceRecords = [];
while ($row = $result->fetch_assoc()) {
    $attendanceRecords[] = $row;
}

$stmt->close();

// Return the attendance records as a JSON response
header('Content-Type: application/json');
echo json_encode($attendanceRecords);
?>
