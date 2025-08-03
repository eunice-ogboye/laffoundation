<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "laff_foundation";
    private $username = "your_username";
    private $password = "your_password";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// api/volunteers.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getVolunteers($db);
        break;
    case 'POST':
        addVolunteer($db);
        break;
    case 'DELETE':
        deleteVolunteer($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getVolunteers($db) {
    $query = "SELECT * FROM volunteers ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $volunteers = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $volunteers[] = $row;
    }
    
    echo json_encode($volunteers);
}

function addVolunteer($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!isset($data['name']) || !isset($data['position'])) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required fields"));
        return;
    }
    
    $query = "INSERT INTO volunteers (name, position, bio, photo, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    
    if($stmt->execute(array($data['name'], $data['position'], $data['bio'] ?? '', $data['photo'] ?? ''))) {
        echo json_encode(array("message" => "Volunteer added successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to add volunteer"));
    }
}

function deleteVolunteer($db) {
    $id = $_GET['id'] ?? null;
    
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing volunteer ID"));
        return;
    }
    
    $query = "DELETE FROM volunteers WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if($stmt->execute(array($id))) {
        echo json_encode(array("message" => "Volunteer deleted successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to delete volunteer"));
    }
}

// api/events.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getEvents($db);
        break;
    case 'POST':
        addEvent($db);
        break;
    case 'DELETE':
        deleteEvent($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getEvents($db) {
    $query = "SELECT * FROM events ORDER BY event_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $events = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = $row;
    }
    
    echo json_encode($events);
}

function addEvent($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!isset($data['title']) || !isset($data['event_date']) || !isset($data['location'])) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required fields"));
        return;
    }
    
    $query = "INSERT INTO events (title, event_date, location, description, volunteers_count, beneficiaries_count, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    
    if($stmt->execute(array(
        $data['title'], 
        $data['event_date'], 
        $data['location'], 
        $data['description'] ?? '', 
        $data['volunteers_count'] ?? 0, 
        $data['beneficiaries_count'] ?? 0, 
        $data['image'] ?? ''
    ))) {
        echo json_encode(array("message" => "Event added successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to add event"));
    }
}

function deleteEvent($db) {
    $id = $_GET['id'] ?? null;
    
    if(!$id) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing event ID"));
        return;
    }
    
    $query = "DELETE FROM events WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if($stmt->execute(array($id))) {
        echo json_encode(array("message" => "Event deleted successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to delete event"));
    }
}

// database/schema.sql
-- Create database
CREATE DATABASE IF NOT EXISTS laff_foundation;
USE laff_foundation;

-- Create volunteers table
CREATE TABLE IF NOT EXISTS volunteers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    bio TEXT,
    photo LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT,
    volunteers_count INT DEFAULT 0,
    beneficiaries_count INT DEFAULT 0,
    image LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default volunteers
INSERT INTO volunteers (name, position, bio) VALUES 
('Leke Aderinola', 'Founder and Director', 'Passionate about empowering African children through education'),
('Tayo Olutimehin', 'Head of Operations', 'Expert in managing educational programs and community outreach'),
('Adedamola Aderinola', 'Head of Media', 'Specializes in digital communication and social media strategy');

-- Insert default events
INSERT INTO events (title, event_date, location, description, volunteers_count, beneficiaries_count) VALUES 
('Community Outreach Program', '2023-06-15', 'Lagos, Nigeria', 'Our team conducted street projects, health awareness campaigns, and advocated for child rights in underserved communities. Over 200 families received essential supplies and health checkups.', 10, 50),
('Youth Education Workshop', '2023-04-22', 'Community Center, Lagos', 'A full-day workshop focused on digital literacy and career guidance for underprivileged youth. Participants received hands-on training and mentorship from industry professionals.', 15, 120),
('Green Earth Initiative', '2023-03-05', 'Lagos, Nigeria', 'Community tree planting day with educational sessions on environmental conservation. We planted over 300 native trees and educated participants on sustainable practices.', 20, 300),
('Orphanage Outreach Program', '2024-02-01', 'Ibadan, Nigeria', 'In 2024, we organized an outreach to two orphanage homes in Ibadan, Nigeria. We provided food, clothing, and educational materials to the children while engaging them in fun activities and games to uplift their spirits and provide educational support.', 10, 50);

// api/upload.php - Handle file uploads
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
    exit;
}

$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    // Check file type
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid file type"));
        exit;
    }
    
    // Check file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(array("message" => "File too large"));
        exit;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(array(
            "message" => "File uploaded successfully",
            "filename" => $fileName,
            "path" => "uploads/" . $fileName
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to upload file"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "No file uploaded"));
}