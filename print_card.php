<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$student_id = $_GET['student_id'] ?? 0;
$exam_id = $_GET['exam_id'] ?? 0;
$exam_list_id = $_GET['exam_list_id'] ?? 0;

// Database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch student data from local database
$student_data = getStudentByCode($student_id);

if (!$student_data) {
    // If student not found by code, try to get from students table directly
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_data) {
        die("Student not found in database");
    }
}

$display_name = $student_data['full_name'] ?? 'Unknown Student';
$student_code = $student_data['student_id'] ?? $student_id;
$student_email = $student_data['email'] ?? '';

$single_exam_mode = false;
$exam_list_data = null;
$exam_title = '';
$exam_items = [];

if ($exam_id && !$exam_list_id) {
    // Single exam mode - fetch exam details from database
    $single_exam_mode = true;
    
    // Try to get exam details from exams table if it exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'exams'");
    $stmt->execute();
    $exams_table_exists = $stmt->fetch();
    
    if ($exams_table_exists) {
        $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$exam_id]);
        $exam_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exam_data) {
            $exam_title = $exam_data['title'] ?? $exam_data['exam_title'] ?? 'Unknown Exam';
        } else {
            $exam_title = 'Exam ID: ' . $exam_id;
        }
    } else {
        // Check if exam exists in exam_list_items
        $stmt = $pdo->prepare("SELECT exam_title FROM exam_list_items WHERE exam_id = ? LIMIT 1");
        $stmt->execute([$exam_id]);
        $exam_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $exam_title = $exam_item['exam_title'] ?? 'Exam ID: ' . $exam_id;
    }
} else if ($exam_list_id) {
    // Exam list mode
    $stmt = $pdo->prepare("SELECT el.*, 
                          (SELECT COUNT(*) FROM exam_list_items WHERE exam_list_id = el.id) as exam_count 
                          FROM exam_lists el WHERE el.id = ?");
    $stmt->execute([$exam_list_id]);
    $exam_list_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exam_list_data) {
        $stmt = $pdo->prepare("SELECT * FROM exam_list_items WHERE exam_list_id = ?");
        $stmt->execute([$exam_list_id]);
        $exam_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no exam items but we have an exam_id, add it as a single exam
        if (empty($exam_items) && $exam_id) {
            $single_exam_mode = true;
            $stmt = $pdo->prepare("SELECT exam_title FROM exam_list_items WHERE exam_id = ? LIMIT 1");
            $stmt->execute([$exam_id]);
            $exam_item = $stmt->fetch(PDO::FETCH_ASSOC);
            $exam_title = $exam_item['exam_title'] ?? 'Exam ID: ' . $exam_id;
        }
    }
}

// If no exam data found but we have exam_id, use it as single exam
if (!$single_exam_mode && empty($exam_items) && $exam_id) {
    $single_exam_mode = true;
    $exam_title = 'Exam ID: ' . $exam_id;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Card - <?php echo htmlspecialchars($display_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .page-break {
                page-break-after: always;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-white print:bg-white">
    <div class="no-print fixed top-4 right-4 z-50">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg transition duration-200">
            <i class="fas fa-print mr-2"></i>Print
        </button>
        <button onclick="window.close()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-lg transition duration-200 ml-2">
            <i class="fas fa-times mr-2"></i>Close
        </button>
    </div>

    <div class="max-w-4xl mx-auto p-6 bg-white border border-gray-200 rounded-lg shadow-sm print:shadow-none print:border-0">
        <!-- Header -->
        <div class="text-center border-b-2 border-gray-300 pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">EXAMINATION CARD</h1>
            <p class="text-lg text-gray-600 mt-2">Academic Year <?php echo date('Y'); ?>/<?php echo date('Y') + 1; ?></p>
        </div>

        <!-- Student Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Student Information</h3>
                <div class="grid grid-cols-2 gap-2">
                    <span class="font-medium text-gray-600">Student ID:</span>
                    <span class="text-gray-800 font-mono"><?php echo htmlspecialchars($student_code); ?></span>
                    
                    <span class="font-medium text-gray-600">Full Name:</span>
                    <span class="text-gray-800 font-semibold">
                        <?php echo htmlspecialchars($display_name); ?>
                        <span class="text-sm text-green-600 ml-1">✓ Verified</span>
                    </span>
                    
                    <?php if (!empty($student_email)): ?>
                    <span class="font-medium text-gray-600">Email:</span>
                    <span class="text-gray-800"><?php echo htmlspecialchars($student_email); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Verification</h3>
                <div class="grid grid-cols-2 gap-2">
                    <span class="font-medium text-gray-600">Status:</span>
                    <span class="text-green-600 font-semibold">
                        <i class="fas fa-check-circle mr-1"></i>
                        VERIFIED
                    </span>
                    
                    <span class="font-medium text-gray-600">Date:</span>
                    <span class="text-gray-800"><?php echo date('F j, Y'); ?></span>
                    
                    <span class="font-medium text-gray-600">Time:</span>
                    <span class="text-gray-800"><?php echo date('g:i A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Exam Information -->
        <div class="space-y-3">
            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">
                <?php echo $single_exam_mode ? 'Exam Information' : 'Exam Schedule'; ?>
            </h3>
            
            <?php if ($single_exam_mode): ?>
            <!-- Single Exam Display -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="font-medium text-gray-600">Exam Title:</span>
                        <p class="text-gray-800 font-semibold text-lg"><?php echo htmlspecialchars($exam_title); ?></p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Exam ID:</span>
                        <p class="text-gray-800 font-mono"><?php echo htmlspecialchars($exam_id); ?></p>
                    </div>
                </div>
                <?php if ($exam_list_data): ?>
                <div class="mt-3 pt-3 border-t border-blue-200">
                    <span class="font-medium text-gray-600">Exam List:</span>
                    <p class="text-gray-800"><?php echo htmlspecialchars($exam_list_data['name']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <!-- Multiple Exams Display -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">#</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Exam ID</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Exam Title</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date & Time</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Venue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($exam_items as $index => $item): ?>
                        <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo $index + 1; ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($item['exam_id']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-800 font-medium"><?php echo htmlspecialchars($item['exam_title']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600">To be announced</td>
                            <td class="px-4 py-3 text-sm text-gray-600">Main Hall</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Instructions -->
        <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <h4 class="font-semibold text-yellow-800 mb-2">Important Instructions:</h4>
            <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                <li>Bring this card and your student ID to the examination venue</li>
                <li>Arrive at least 30 minutes before the examination time</li>
                <li>No electronic devices allowed except calculators if specified</li>
                <li>Follow all examination rules and regulations</li>
                <li>Keep this card safe until all examinations are completed</li>
            </ul>
        </div>

        <!-- Signature Area -->
        <div class="mt-8 pt-4 border-t border-gray-300">
            <div class="grid grid-cols-2 gap-8">
                <div class="text-center">
                    <p class="text-gray-600 mb-12">Student's Signature</p>
                    <p class="text-sm text-gray-500">_________________________</p>
                </div>
                <div class="text-center">
                    <p class="text-gray-600 mb-12">Examination Officer</p>
                    <p class="text-sm text-gray-500">_________________________</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-xs text-gray-500 border-t pt-4">
            <p>Card generated on: <?php echo date('F j, Y \a\t g:i A'); ?> | Student ID: <?php echo htmlspecialchars($student_code); ?></p>
        </div>
    </div>
</body>
</html>