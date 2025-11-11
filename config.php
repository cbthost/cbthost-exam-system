
<?php
// Define database constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'exam_system_admin');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
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

// Delete combined analysis
function deleteCombinedAnalysis($id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM combined_analyses WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    return $stmt->execute();
}

// Delete a student
function deleteStudent($id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM students WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    return $stmt->execute();
}

// Clear all students (for bulk deletion)
function clearAllStudents() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM students";
    $stmt = $db->prepare($query);
    
    return $stmt->execute();
}

// Get student by student_code (not student_id)
function getStudentByCode($student_code) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM students WHERE student_id = :student_code";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_code', $student_code);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Save combined analysis configuration
function saveCombinedAnalysis($name, $exam_ids, $labels) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO combined_analyses (name, exam_ids, labels) VALUES (:name, :exam_ids, :labels)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':exam_ids', $exam_ids);
    $stmt->bindParam(':labels', $labels);
    
    return $stmt->execute();
}

// Get all combined analyses
function getAllCombinedAnalyses() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM combined_analyses ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get combined analysis by ID
function getCombinedAnalysis($id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM combined_analyses WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get backend configuration
function getBackendConfig() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT api_base_url FROM backend_config ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['api_base_url'] : 'http://localhost:8059/api/public';
}

// Update backend configuration
function updateBackendConfig($api_base_url) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO backend_config (api_base_url) VALUES (:api_base_url)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':api_base_url', $api_base_url);
    
    return $stmt->execute();
}

// Get student by ID
function getStudentById($student_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM students WHERE student_id = :student_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add or update student
function upsertStudent($student_id, $full_name, $email = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO students (student_id, full_name, email) 
              VALUES (:student_id, :full_name, :email) 
              ON DUPLICATE KEY UPDATE full_name = :full_name, email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':email', $email);
    
    return $stmt->execute();
}

// Get all students
function getAllStudents() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM students ORDER BY full_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php



// class Database {
//     private $host = 'localhost';
//     private $db_name = 'exam_system_admin';
//     private $username = 'root';
//     private $password = '';
//     public $conn;

//     public function getConnection() {
//         $this->conn = null;
//         try {
//             $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
//             $this->conn->exec("set names utf8");
//         } catch(PDOException $exception) {
//             echo "Connection error: " . $exception->getMessage();
//         }
//         return $this->conn;
//     }
// }

// // Delete combined analysis
// function deleteCombinedAnalysis($id) {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "DELETE FROM combined_analyses WHERE id = :id";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':id', $id);
    
//     return $stmt->execute();
// }

// // Delete a student
// function deleteStudent($id) {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "DELETE FROM students WHERE id = :id";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':id', $id);
    
//     return $stmt->execute();
// }

// // Clear all students (for bulk deletion)
// function clearAllStudents() {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "DELETE FROM students";
//     $stmt = $db->prepare($query);
    
//     return $stmt->execute();
// }



// // Get student by student_code (not student_id)
// function getStudentByCode($student_code) {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "SELECT * FROM students WHERE student_id = :student_code";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':student_code', $student_code);
//     $stmt->execute();
    
//     return $stmt->fetch(PDO::FETCH_ASSOC);
// }

// // Save combined analysis configuration
// function saveCombinedAnalysis($name, $exam_ids, $labels) {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "INSERT INTO combined_analyses (name, exam_ids, labels) VALUES (:name, :exam_ids, :labels)";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':name', $name);
//     $stmt->bindParam(':exam_ids', $exam_ids);
//     $stmt->bindParam(':labels', $labels);
    
//     return $stmt->execute();
// }

// // Get all combined analyses
// function getAllCombinedAnalyses() {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "SELECT * FROM combined_analyses ORDER BY created_at DESC";
//     $stmt = $db->prepare($query);
//     $stmt->execute();
    
//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }

// // Get combined analysis by ID
// function getCombinedAnalysis($id) {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "SELECT * FROM combined_analyses WHERE id = :id";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':id', $id);
//     $stmt->execute();
    
//     return $stmt->fetch(PDO::FETCH_ASSOC);
// }


// // Get backend configuration
// function getBackendConfig() {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "SELECT api_base_url FROM backend_config ORDER BY id DESC LIMIT 1";
//     $stmt = $db->prepare($query);
//     $stmt->execute();
    
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
//     return $result ? $result['api_base_url'] : 'http://localhost:8059/api/public';
// }

// // Update backend configuration
// function updateBackendConfig($api_base_url) {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "INSERT INTO backend_config (api_base_url) VALUES (:api_base_url)";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':api_base_url', $api_base_url);
    
//     return $stmt->execute();
// }

// // Get student by ID
// function getStudentById($student_id) {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "SELECT * FROM students WHERE student_id = :student_id";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':student_id', $student_id);
//     $stmt->execute();
    
//     return $stmt->fetch(PDO::FETCH_ASSOC);
// }

// // Add or update student
// function upsertStudent($student_id, $full_name, $email = null) {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "INSERT INTO students (student_id, full_name, email) 
//               VALUES (:student_id, :full_name, :email) 
//               ON DUPLICATE KEY UPDATE full_name = :full_name, email = :email";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':student_id', $student_id);
//     $stmt->bindParam(':full_name', $full_name);
//     $stmt->bindParam(':email', $email);
    
//     return $stmt->execute();
// }

// // Get all students
// function getAllStudents() {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $query = "SELECT * FROM students ORDER BY full_name";
//     $stmt = $db->prepare($query);
//     $stmt->execute();
    
//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }
?>