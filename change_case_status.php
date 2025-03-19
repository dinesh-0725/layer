<?php
header('Content-Type: application/json');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];
    $userId = $_POST['userId'];

    $conn = new mysqli("localhost", "root", "", "database");
    if ($conn->connect_error) {
        echo json_encode(["status" => "ERROR", "message" => "Connection failed."]);
        exit;
    }

    // Fetch lawyer details
    $lawyerQuery = $conn->prepare("SELECT name, contact_number FROM users WHERE id = ?");
    $lawyerQuery->bind_param("i", $userId);
    $lawyerQuery->execute();
    $lawyerResult = $lawyerQuery->get_result();
    $lawyer = $lawyerResult->fetch_assoc();
    $lawyerName = $lawyer['name'];
    $lawyerContact = $lawyer['contact_number'];
    $lawyerQuery->close();

    // Update case status
    $stmt = $conn->prepare("UPDATE problem_cases SET assigned_to = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $userId, $action, $id);

    if ($stmt->execute()) {
        // Insert notification
        $notification = "Case ID $id has been accepted by $lawyerName (Contact: $lawyerContact)";
        $notiStmt = $conn->prepare("INSERT INTO users (notification) VALUES (?)");
        $notiStmt->bind_param("s", $notification);
        $notiStmt->execute();
        $notiStmt->close();

        echo json_encode(["status" => "SUCCESS", "message" => "Case accepted.", "notification" => $notification]);
    } else {
        echo json_encode(["status" => "ERROR", "message" => "Failed to update case status."]);
    }

    $stmt->close();
    $conn->close();
}
