<?php
require_once 'config.php';

// Check if PhpSpreadsheet is available
$phpspreadsheet_available = false;
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        $phpspreadsheet_available = true;
    } elseif (file_exists('libs/PhpSpreadsheet/vendor/autoload.php')) {
        require_once 'libs/PhpSpreadsheet/vendor/autoload.php';
        $phpspreadsheet_available = true;
    }
} catch (Exception $e) {
    $phpspreadsheet_available = false;
}
    use PhpOffice\PhpSpreadsheet\IOFactory;


session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = '';
$upload_stats = [
    'total' => 0,
    'success' => 0,
    'errors' => 0,
    'error_details' => []
];

// Handle student upload
if (isset($_POST['action']) && $_POST['action'] === 'upload_students') {
    if (!$phpspreadsheet_available) {
        $message = 'PhpSpreadsheet is not available. Cannot process Excel files.';
        $message_type = 'error';
    } elseif (isset($_FILES['student_file']) && $_FILES['student_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['student_file']['tmp_name'];
        $file_type = strtolower(pathinfo($_FILES['student_file']['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_types = ['xlsx', 'xls', 'csv'];
        if (!in_array($file_type, $allowed_types)) {
            $message = 'Invalid file type. Please upload Excel (.xlsx, .xls) or CSV files.';
            $message_type = 'error';
        } else {
            try {
                // Load the spreadsheet
                $spreadsheet = IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                // Process rows (skip header if exists)
                $start_row = 0;
                if (isset($_POST['has_header']) && $_POST['has_header'] === '1') {
                    $start_row = 1;
                }
                
                $upload_stats['total'] = count($rows) - $start_row;
                
                for ($i = $start_row; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    
                    // Skip empty rows
                    if (empty($row[0]) && empty($row[1])) {
                        continue;
                    }
                    
                    $student_id = trim($row[0]);
                    $full_name = trim($row[1]);
                    $email = isset($row[2]) ? trim($row[2]) : null;
                    
                    // Validate required fields
                    if (empty($student_id) || empty($full_name)) {
                        $upload_stats['errors']++;
                        $upload_stats['error_details'][] = "Row " . ($i + 1) . ": Missing student ID or name";
                        continue;
                    }
                    
                    // Insert/update student
                    if (upsertStudent($student_id, $full_name, $email)) {
                        $upload_stats['success']++;
                    } else {
                        $upload_stats['errors']++;
                        $upload_stats['error_details'][] = "Row " . ($i + 1) . ": Failed to save student";
                    }
                }
                
                if ($upload_stats['success'] > 0) {
                    $message = "Successfully processed {$upload_stats['success']} out of {$upload_stats['total']} students.";
                    $message_type = 'success';
                } else {
                    $message = "No students were processed. Please check your file format.";
                    $message_type = 'error';
                }
                
            } catch (Exception $e) {
                $message = 'Error processing file: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Please select a valid file to upload.';
        $message_type = 'error';
    }
}

// Handle manual student addition
if (isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $student_id = trim($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']) ?: null;
    
    if (empty($student_id) || empty($full_name)) {
        $message = 'Student ID and Full Name are required.';
        $message_type = 'error';
    } else {
        if (upsertStudent($student_id, $full_name, $email)) {
            $message = 'Student added successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to add student.';
            $message_type = 'error';
        }
    }
}

// Handle student deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_student' && isset($_GET['id'])) {
    if (deleteStudent($_GET['id'])) {
        $message = 'Student deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete student.';
        $message_type = 'error';
    }
}


// Handle clear all students
if (isset($_GET['action']) && $_GET['action'] === 'clear_all_students') {
    if (clearAllStudents()) {
        $message = 'All students deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete all students.';
        $message_type = 'error';
    }
}

// Get all students
$students = getAllStudents();
$total_students = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Exam System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php 
            echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 
                 ($message_type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' : 
                 'bg-blue-100 text-blue-800 border border-blue-200'); 
        ?>">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas <?php 
                        echo $message_type === 'success' ? 'fa-check-circle' : 
                             ($message_type === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle'); 
                    ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <button onclick="this.parentElement.parentElement.style.display='none'" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$phpspreadsheet_available): ?>
        <div class="mb-6 p-4 rounded-lg bg-yellow-100 text-yellow-800 border border-yellow-200">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                PhpSpreadsheet is not available. Excel upload functionality will not work.
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Student Management</h1>
            <p class="text-gray-600">Upload student data for personalized result analysis and name matching</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upload Section -->
            <div class="space-y-8">
                <!-- Excel Upload -->
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Upload Student Excel File</h2>
                    
                    <?php if ($phpspreadsheet_available): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_students">
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Excel/CSV File</label>
                            <input type="file" name="student_file" accept=".xlsx,.xls,.csv" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            <p class="text-sm text-gray-500 mt-1">
                                Supported formats: .xlsx, .xls, .csv
                            </p>
                        </div>
                        
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="has_header" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">First row contains headers</span>
                            </label>
                        </div>

                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="font-medium text-blue-900 mb-2">File Format Requirements:</h4>
                            <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                                <li><strong>Column A:</strong> Student ID (Required)</li>
                                <li><strong>Column B:</strong> Full Name (Required)</li>
                                <li><strong>Column C:</strong> Email (Optional)</li>
                            </ul>
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                            <i class="fas fa-upload mr-2"></i>Upload Students
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-exclamation-triangle text-3xl mb-4"></i>
                        <p>Excel upload functionality is not available.</p>
                        <p class="text-sm">Please install PhpSpreadsheet to enable this feature.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Manual Student Addition -->
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Add Student Manually</h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="add_student">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Student ID *</label>
                                <input type="text" name="student_id" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                       placeholder="e.g., STU001" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" name="full_name" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                       placeholder="e.g., John Doe" required>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email (Optional)</label>
                            <input type="email" name="email" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   placeholder="e.g., john@example.com">
                        </div>

                        <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 font-medium transition-colors">
                            <i class="fas fa-user-plus mr-2"></i>Add Student
                        </button>
                    </form>
                </div>

                <!-- Upload Statistics -->
                <?php if ($upload_stats['total'] > 0): ?>
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Upload Statistics</h2>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-700"><?php echo $upload_stats['total']; ?></div>
                            <div class="text-sm text-blue-600">Total Rows</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-700"><?php echo $upload_stats['success']; ?></div>
                            <div class="text-sm text-green-600">Successful</div>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <div class="text-2xl font-bold text-red-700"><?php echo $upload_stats['errors']; ?></div>
                            <div class="text-sm text-red-600">Errors</div>
                        </div>
                    </div>

                    <?php if (!empty($upload_stats['error_details'])): ?>
                    <div class="mt-4">
                        <h4 class="font-medium text-gray-900 mb-2">Error Details:</h4>
                        <div class="max-h-32 overflow-y-auto text-sm text-red-600">
                            <?php foreach ($upload_stats['error_details'] as $error): ?>
                            <div class="py-1 border-b"><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Student List -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Student Database</h2>
                    <span class="px-3 py-1 text-sm bg-indigo-100 text-indigo-800 rounded-full">
                        <?php echo $total_students; ?> students
                    </span>
                </div>

                <?php if ($total_students > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Student ID</th>
                                <th class="px-4 py-3">Full Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Last Updated</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono">
                                    <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($student['student_id']); ?></code>
                                </td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($student['email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" class="text-blue-600 hover:text-blue-800">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    <?php echo date('M j, Y', strtotime($student['updated_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="?action=delete_student&id=<?php echo $student['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete student <?php echo htmlspecialchars($student['student_id']); ?>?')"
                                       class="text-red-600 hover:text-red-800 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
                    <div>
                        Showing <?php echo $total_students; ?> students
                    </div>
                    <div class="space-x-2">
                        <button onclick="exportStudentList()" class="text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-download mr-1"></i>Export List
                        </button>
                        <button onclick="clearAllStudents()" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash mr-1"></i>Clear All
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-users text-4xl mb-4"></i>
                    <p class="text-lg">No students in database</p>
                    <p class="text-sm">Upload an Excel file or add students manually to get started.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- How It Works Section -->
        <div class="mt-12 bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">How Student Name Matching Works</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-6 border rounded-lg">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-upload text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">1. Upload Student Data</h3>
                    <p class="text-sm text-gray-600">Upload Excel/CSV with Student IDs and Names</p>
                </div>
                
                <div class="text-center p-6 border rounded-lg">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-sync text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">2. Automatic Matching</h3>
                    <p class="text-sm text-gray-600">System matches Student IDs from exam results with your database</p>
                </div>
                
                <div class="text-center p-6 border rounded-lg">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-excel text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">3. Personalized Results</h3>
                    <p class="text-sm text-gray-600">Export combined results with correct student names</p>
                </div>
            </div>
            
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium text-gray-900 mb-2">Important Notes:</h4>
                <ul class="text-sm text-gray-700 list-disc list-inside space-y-1">
                    <li><strong>Student ID must match exactly</strong> with the ID used during exam registration</li>
                    <li>If a Student ID is not found in your database, the system will use the name from the exam server</li>
                    <li>You can update student information at any time by re-uploading the file</li>
                    <li>Duplicate Student IDs will be updated with the latest information</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function exportStudentList() {
            if (confirm('Export current student list to CSV?')) {
                // Create CSV content
                let csv = 'Student ID,Full Name,Email\n';
                <?php foreach ($students as $student): ?>
                csv += '<?php echo addslashes($student['student_id']); ?>,<?php echo addslashes($student['full_name']); ?>,<?php echo addslashes($student['email'] ?? ''); ?>\n';
                <?php endforeach; ?>
                
                // Create and download file
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.setAttribute('hidden', '');
                a.setAttribute('href', url);
                a.setAttribute('download', 'student_list_<?php echo date('Y-m-d'); ?>.csv');
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        }
        
        function clearAllStudents() {
            if (confirm('Are you sure you want to delete ALL students? This action cannot be undone.')) {
                window.location.href = '?action=clear_all_students';
            }
        }
        
        // File input preview
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const fileName = e.target.files[0]?.name;
                    if (fileName) {
                        // You can add file preview logic here if needed
                        console.log('Selected file:', fileName);
                    }
                });
            }
        });
    </script>
</body>
</html>