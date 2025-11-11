<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$exam_list_id = $_GET['exam_list_id'] ?? 0;

// Database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch exam list data
$stmt = $pdo->prepare("SELECT * FROM exam_lists WHERE id = ?");
$stmt->execute([$exam_list_id]);
$exam_list_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam_list_data) {
    die("Exam list not found");
}

// Fetch exams in this list
$stmt = $pdo->prepare("SELECT * FROM exam_list_items WHERE exam_list_id = ?");
$stmt->execute([$exam_list_id]);
$exam_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students from local database
$students = getAllStudents();

// If no students in local DB, fetch from your student table structure
if (empty($students)) {
    $stmt = $pdo->prepare("SELECT * FROM students ORDER BY full_name");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch exam details for each exam item
$exam_details = [];
foreach ($exam_items as $item) {
    $exam_id = $item['exam_id'];
    
    // Try to get exam details from your exams table if it exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'exams'");
    $stmt->execute();
    $exams_table_exists = $stmt->fetch();
    
    if ($exams_table_exists) {
        $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$exam_id]);
        $exam_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exam_data) {
            $exam_details[$exam_id] = [
                'title' => $exam_data['title'] ?? $exam_data['exam_title'] ?? 'Unknown Exam',
                'duration' => $exam_data['duration'] ?? 'N/A',
                'total_questions' => $exam_data['total_questions'] ?? 'N/A',
                'passing_score' => $exam_data['passing_score'] ?? 'N/A'
            ];
        }
    } else {
        // Fallback to exam_list_items data
        $exam_details[$exam_id] = [
            'title' => $item['exam_title'] ?? 'Unknown Exam',
            'duration' => 'To be announced',
            'total_questions' => 'N/A',
            'passing_score' => 'N/A'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Exam Cards - <?php echo htmlspecialchars($exam_list_data['name']); ?></title>
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
            @page {
                margin: 0.5in;
            }
        }
        .exam-card {
            border: 2px solid #000;
            padding: 20px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body class="bg-gray-100 print:bg-white">
    <div class="no-print fixed top-4 right-4 z-50">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg">
            <i class="fas fa-print mr-2"></i>Print All
        </button>
        <button onclick="window.close()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-lg ml-2">
            <i class="fas fa-times mr-2"></i>Close
        </button>
    </div>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold text-center mb-8 no-print">
            Exam Cards for: <?php echo htmlspecialchars($exam_list_data['name']); ?>
            <span class="text-lg font-normal text-gray-600 block mt-2">
                Total Students: <?php echo count($students); ?> | Total Exams: <?php echo count($exam_items); ?>
            </span>
        </h1>

        <?php if (empty($students)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4 text-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                No students found in the database. Please add students first.
            </div>
        <?php endif; ?>

        <?php foreach ($students as $index => $student): ?>
        <div class="exam-card page-break bg-white">
            <!-- Header -->
            <div class="text-center border-b-2 border-gray-300 pb-4 mb-6">
                <h1 class="text-3xl font-bold text-gray-800">EXAMINATION CARD</h1>
                <p class="text-lg text-gray-600 mt-2">Academic Year <?php echo date('Y'); ?>/<?php echo date('Y') + 1; ?></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Student Information</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-medium text-gray-600">Student ID:</span>
                        <span class="text-gray-800 font-mono"><?php echo htmlspecialchars($student['student_id']); ?></span>
                        
                        <span class="font-medium text-gray-600">Full Name:</span>
                        <span class="text-gray-800 font-semibold"><?php echo htmlspecialchars($student['full_name']); ?></span>
                        
                        <?php if (!empty($student['email'])): ?>
                        <span class="font-medium text-gray-600">Email:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($student['email']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Exam List Information</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-medium text-gray-600">List Name:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($exam_list_data['name']); ?></span>
                        
                        <span class="font-medium text-gray-600">Total Exams:</span>
                        <span class="text-gray-800"><?php echo count($exam_items); ?></span>
                        
                        <span class="font-medium text-gray-600">Generated:</span>
                        <span class="text-gray-800"><?php echo date('M j, Y g:i A'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Exam Schedule -->
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Exam Schedule</h3>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">#</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Exam ID</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Exam Title</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Duration</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date & Time</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Venue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($exam_items as $item_index => $item): 
                                $exam_id = $item['exam_id'];
                                $exam_detail = $exam_details[$exam_id] ?? [];
                            ?>
                            <tr class="<?php echo $item_index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $item_index + 1; ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($exam_id); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-800 font-medium"><?php echo htmlspecialchars($exam_detail['title'] ?? $item['exam_title']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($exam_detail['duration'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600">To be announced</td>
                                <td class="px-4 py-3 text-sm text-gray-600">Main Hall</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Instructions -->
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
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
            <div class="mt-6 text-center text-xs text-gray-500 border-t pt-2">
                <p>Card generated on: <?php echo date('F j, Y \a\t g:i A'); ?> | Student: <?php echo htmlspecialchars($student['student_id']); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($students)): ?>
    <script>
        // Alert if no students found
        window.onload = function() {
            alert('No students found in the database. Please add students before printing exam cards.');
            window.close();
        }
    </script>
    <?php endif; ?>
</body>
</html>