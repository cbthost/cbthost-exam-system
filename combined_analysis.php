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
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$api_base = getBackendConfig();

// Fetch available exams
$exams_json = @file_get_contents($api_base . "/exams");
$exams = $exams_json ? json_decode($exams_json, true) : [];

$message = '';
$message_type = '';
$combined_results = [];
$analysis_name = '';

// Handle analysis creation and display
if (isset($_POST['action']) && $_POST['action'] === 'create_analysis') {
    $analysis_name = $_POST['analysis_name'];
    $exam_ids = $_POST['exam_ids'];
    $labels = $_POST['labels'];
    
    // Validate inputs
    if (empty($analysis_name) || empty($exam_ids[0]) || empty($labels[0])) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } else {
        // Fetch results for each exam and combine them
        $combined_results = combineExamResults($exam_ids, $labels, $api_base);
        
        if (empty($combined_results)) {
            $message = 'No results found for the selected exams.';
            $message_type = 'error';
        } else {
            $message = 'Analysis created successfully! Found ' . count($combined_results) . ' students.';
            $message_type = 'success';
            
            // Save analysis configuration
            saveCombinedAnalysis($analysis_name, json_encode($exam_ids), json_encode($labels));
        }
    }
}

// Handle Excel export
if (isset($_POST['action']) && $_POST['action'] === 'export_excel' && isset($_POST['analysis_data'])) {
    if ($phpspreadsheet_available) {
        $analysis_data = json_decode($_POST['analysis_data'], true);
        $analysis_name = $_POST['analysis_name'] ?? 'Combined_Analysis';
        exportToExcel($analysis_data, $analysis_name);
        exit();
    } else {
        $message = 'PhpSpreadsheet is not available. Cannot export to Excel.';
        $message_type = 'error';
    }
}

// Handle analysis deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_analysis' && isset($_GET['id'])) {
    if (deleteCombinedAnalysis($_GET['id'])) {
        $message = 'Analysis deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete analysis.';
        $message_type = 'error';
    }
    header('Location: combined_analysis.php?message=' . urlencode($message) . '&type=' . $message_type);
    exit();
}

// Handle analysis loading
if (isset($_GET['action']) && $_GET['action'] === 'load_analysis' && isset($_GET['id'])) {
    $analysis = getCombinedAnalysis($_GET['id']);
    if ($analysis) {
        $analysis_name = $analysis['name'];
        $exam_ids = json_decode($analysis['exam_ids'], true);
        $labels = json_decode($analysis['labels'], true);
        $combined_results = combineExamResults($exam_ids, $labels, $api_base);
        
        if (empty($combined_results)) {
            $message = 'No results found for the saved analysis.';
            $message_type = 'error';
        } else {
            $message = 'Analysis loaded successfully! Found ' . count($combined_results) . ' students.';
            $message_type = 'success';
        }
    } else {
        $message = 'Analysis not found.';
        $message_type = 'error';
    }
}

// Check for message from redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_type = $_GET['type'] ?? 'info';
}

// Function to combine exam results
function combineExamResults($exam_ids, $labels, $api_base) {
    $all_results = [];
    $student_data = [];
    
    // Fetch results for each exam
    foreach ($exam_ids as $index => $exam_id) {
        if (empty($exam_id)) continue;
        
        $results_url = $api_base . "/exams/" . $exam_id . "/results";
        $results_json = @file_get_contents($results_url);
        
        if ($results_json) {
            $exam_results = json_decode($results_json, true);
            $label = $labels[$index] ?? "Exam " . ($index + 1);
            
            if (isset($exam_results['results'])) {
                foreach ($exam_results['results'] as $result) {
                    $student_code = $result['student_code'];
                    
                    // Initialize student data if not exists
                    if (!isset($student_data[$student_code])) {
                        $local_student = getStudentByCode($student_code);
                        $student_data[$student_code] = [
                            'student_id' => $student_code,
                            'full_name' => $local_student ? $local_student['full_name'] : $result['full_name'],
                            'exams' => []
                        ];
                    }
                    
                    // Add exam result with score only (no percentages)
                    $student_data[$student_code]['exams'][$label] = [
                        'score' => $result['score'],
                        'total_questions' => $result['total_questions'],
                        'correct_answers' => $result['correct_answers'],
                        'time_spent' => $result['time_spent'],
                        'completed_at' => $result['completed_at']
                    ];
                }
            }
        }
    }
    
    // Convert to simple array
    foreach ($student_data as $student_code => $data) {
        $all_results[] = [
            'student_id' => $data['student_id'],
            'full_name' => $data['full_name'],
            'exams' => $data['exams'],
            'exam_count' => count($data['exams'])
        ];
    }
    
    // Sort by student ID
    usort($all_results, function($a, $b) {
        return strcmp($a['student_id'], $b['student_id']);
    });
    
    return $all_results;
}

// Function to export to Excel
function exportToExcel($analysis_data, $analysis_name) {
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        return false;
    }
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set title
    $sheet->setCellValue('A1', $analysis_name . ' - Combined Results');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    
    // Headers
    $headers = ['Student ID', 'Full Name'];
    
    // Add dynamic exam headers
    if (!empty($analysis_data[0]['exams'])) {
        foreach ($analysis_data[0]['exams'] as $exam_label => $exam_data) {
            $headers[] = $exam_label;
        }
    }
    
    $headers[] = 'Total Exams';
    
    // Write headers
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '3', $header);
        $sheet->getStyle($col . '3')->getFont()->setBold(true);
        $col++;
    }
    
    // Write data
    $row = 4;
    foreach ($analysis_data as $student) {
        $sheet->setCellValue('A' . $row, $student['student_id']);
        $sheet->setCellValue('B' . $row, $student['full_name']);
        
        $col = 'C';
        foreach ($student['exams'] as $exam_label => $exam_data) {
            $sheet->setCellValue($col . $row, $exam_data['score'] );
            $col++;
        }
        
        $sheet->setCellValue($col . $row, $student['exam_count']);
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', $col) as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    // Set headers for download
    $filename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $analysis_name) . '_analysis.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Get recent analyses
$recent_analyses = getAllCombinedAnalyses();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combined Analysis - Exam System</title>
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
                PhpSpreadsheet is not available. Excel export functionality will not work.
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Combined Results Analysis</h1>
            <p class="text-gray-600">Combine multiple exam results for comprehensive analysis and export to Excel</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Analysis Configuration -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Create New Analysis</h2>
                
                <form method="POST" id="analysisForm">
                    <input type="hidden" name="action" value="create_analysis">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Analysis Name *</label>
                        <input type="text" name="analysis_name" value="<?php echo htmlspecialchars($analysis_name); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                               placeholder="e.g., Midterm vs Final Comparison" required>
                    </div>

                    <div id="examContainer">
                        <!-- Exam fields will be added here -->
                    </div>

                    <button type="button" onclick="addExamField()" class="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-200 font-medium mb-6 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Another Exam
                    </button>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                        <i class="fas fa-chart-line mr-2"></i>Generate Analysis
                    </button>
                </form>

                <!-- Results Display -->
                <?php if (!empty($combined_results)): ?>
                <div class="mt-8 border-t pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Analysis Results</h3>
                        <?php if ($phpspreadsheet_available): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="export_excel">
                            <input type="hidden" name="analysis_data" value="<?php echo htmlspecialchars(json_encode($combined_results)); ?>">
                            <input type="hidden" name="analysis_name" value="<?php echo htmlspecialchars($analysis_name); ?>">
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium transition-colors">
                                <i class="fas fa-file-excel mr-2"></i>Export to Excel
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="w-full text-sm text-left text-gray-700">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 border-r">Student ID</th>
                                    <th class="px-4 py-3 border-r">Full Name</th>
                                    <?php if (!empty($combined_results[0]['exams'])): ?>
                                        <?php foreach ($combined_results[0]['exams'] as $exam_label => $exam_data): ?>
                                        <th class="px-4 py-3 border-r text-center"><?php echo htmlspecialchars($exam_label); ?></th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <!-- <th class="px-4 py-3 text-center">Total Exams</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($combined_results as $result): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono border-r"><?php echo htmlspecialchars($result['student_id']); ?></td>
                                    <td class="px-4 py-3 border-r"><?php echo htmlspecialchars($result['full_name']); ?></td>
                                    <?php if (!empty($result['exams'])): ?>
                                        <?php foreach ($result['exams'] as $exam_label => $exam_data): ?>
                                        <td class="px-4 py-3 border-r text-center">
                                            <span class="inline-block px-2 py-1 rounded text-xs font-medium 
                                                <?php echo $exam_data['score'] > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo $exam_data['score']; ?>
                                                <?php 
                                                // echo $exam_data['total_questions']; 
                                                ?>
                                            </span>
                                        </td>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <!-- <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php 
                                            // echo $result['exam_count'];
                                             ?>
                                        </span>
                                    </td> -->
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 text-sm text-gray-600">
                        <p>Showing <?php echo count($combined_results); ?> students with combined results from <?php echo !empty($combined_results[0]['exams']) ? count($combined_results[0]['exams']) : 0; ?> exams.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Analyses -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Analyses</h2>
                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">
                        <?php echo count($recent_analyses); ?> saved
                    </span>
                </div>
                
                <div class="space-y-4">
                    <?php if (empty($recent_analyses)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p>No recent analyses</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_analyses as $analysis): ?>
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($analysis['name']); ?></h3>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Saved</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">
                                <?php 
                                $exam_ids = json_decode($analysis['exam_ids'], true);
                                echo count($exam_ids) . ' exam' . (count($exam_ids) > 1 ? 's' : '');
                                ?>
                            </p>
                            <p class="text-xs text-gray-500 mb-2">
                                Created: <?php echo date('M j, Y g:i A', strtotime($analysis['created_at'])); ?>
                            </p>
                            <div class="flex space-x-2">
                                <a href="?action=load_analysis&id=<?php echo $analysis['id']; ?>" 
                                   class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>View
                                </a>
                                <?php if ($phpspreadsheet_available): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="export_excel">
                                    <input type="hidden" name="analysis_data" value='<?php echo json_encode(combineExamResults(json_decode($analysis['exam_ids'], true), json_decode($analysis['labels'], true), getBackendConfig())); ?>'>
                                    <input type="hidden" name="analysis_name" value="<?php echo htmlspecialchars($analysis['name']); ?>">
                                    <button type="submit" class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200 transition-colors">
                                        <i class="fas fa-download mr-1"></i>Export
                                    </button>
                                </form>
                                <?php endif; ?>
                                <a href="?action=delete_analysis&id=<?php echo $analysis['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this analysis?')"
                                   class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200 transition-colors">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let examCount = 0;
        
        function addExamField() {
            examCount++;
            const container = document.getElementById('examContainer');
            
            const examField = `
                <div class="border rounded-lg p-4 mb-4 exam-field bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Exam ${examCount} *</label>
                            <select name="exam_ids[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">Select Exam</option>
                                <?php foreach ($exams as $exam): ?>
                                <option value="<?php echo $exam['id']; ?>"><?php echo htmlspecialchars($exam['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Label *</label>
                            <input type="text" name="labels[]" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   placeholder="e.g., First Test, Midterm" required>
                        </div>
                    </div>
                    ${examCount > 1 ? '<button type="button" onclick="removeExamField(this)" class="mt-2 text-red-600 text-sm hover:text-red-800 transition-colors"><i class="fas fa-trash mr-1"></i>Remove</button>' : ''}
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', examField);
        }
        
        function removeExamField(button) {
            button.closest('.exam-field').remove();
            // Re-number remaining exams
            const examFields = document.querySelectorAll('.exam-field');
            examFields.forEach((field, index) => {
                const label = field.querySelector('label');
                label.textContent = `Exam ${index + 1} *`;
            });
            examCount = examFields.length;
        }
        
        // Add first exam field on page load
        document.addEventListener('DOMContentLoaded', addExamField);
    </script>
</body>
</html>