<?php
session_start();
require_once 'config.php';

$api_base = getBackendConfig();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$student_id = $_GET['student_id'] ?? 0;
$exam_id = $_GET['exam_id'] ?? 0;
$attempt_id = $_GET['attempt_id'] ?? 0;

if (!$student_id || !$exam_id) {
    die("Student ID and Exam ID are required");
}

$results_data = json_decode(file_get_contents($api_base . "/exams/" . $exam_id . "/results"), true);

// Find specific student result
$student_result = null;
foreach ($results_data['results'] as $result) {
    if ($result['student_id'] == $student_id && (!$attempt_id || $result['attempt_id'] == $attempt_id)) {
        $student_result = $result;
        break;
    }
}

if (!$student_result) {
    die("Student result not found");
}

// Get student name from local database
$local_student = getStudentByCode($student_result['student_code']);
$display_name = $local_student ? $local_student['full_name'] : $student_result['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result - <?php echo htmlspecialchars($display_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .result-card {
                border: 2px solid #000 !important;
                box-shadow: none !important;
            }
            body {
                background: white !important;
            }
        }
        .result-card {
            border: 2px solid #334155;
            border-radius: 12px;
            padding: 32px;
            background: white;
            max-width: 900px;
            margin: 20px auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .score-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0 auto 16px;
        }
        .signature-box {
            border: 1px dashed #64748b;
            height: 60px;
            margin-top: 20px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center no-print mt-6 space-x-4">
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                <i class="fas fa-print mr-2"></i>Print Result
            </button>
            <a href="results.php?exam_id=<?php echo $exam_id; ?>" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Results
            </a>
        </div>

        <div class="result-card">
            <!-- Header -->
            <div class="text-center border-b-2 border-gray-300 pb-6 mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">EXAMINATION RESULT</h2>
                <h4 class="text-xl text-gray-700">Computer Based Testing System</h4>
            </div>

            <!-- Student and Exam Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div>
                    <h5 class="text-lg font-semibold text-gray-900 mb-4">Student Information</h5>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Student ID</label>
                            <p class="text-gray-900 font-mono bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($student_result['student_code']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <p class="text-gray-900 bg-gray-50 p-2 rounded">
                                <?php echo htmlspecialchars($display_name); ?>
                                <?php if ($local_student): ?>
                                    <span class="text-sm text-green-600 ml-2">✓ Verified</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div>
                    <h5 class="text-lg font-semibold text-gray-900 mb-4">Exam Information</h5>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Exam</label>
                            <p class="text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($results_data['exam_title']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Completed</label>
                            <p class="text-gray-900 bg-gray-50 p-2 rounded"><?php echo date('F j, Y g:i A', strtotime($student_result['completed_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Score -->
            <div class="text-center mb-8">
                <div class="text-5xl font-bold text-blue-600 mb-2">
                    <?php echo number_format(($student_result['score'] / $student_result['total_questions']) * 100, 1); ?>%
                </div>
                <p class="text-lg text-gray-600">Overall Score</p>
            </div>

            <!-- Score Breakdown -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="text-center">
                    <div class="score-circle bg-green-500">
                        <?php echo $student_result['score']; ?>
                    </div>
                    <p class="text-gray-700 font-medium">Score</p>
                </div>
                <div class="text-center">
                    <div class="score-circle bg-blue-500">
                        <?php echo $student_result['correct_answers']; ?>
                    </div>
                    <p class="text-gray-700 font-medium">Correct</p>
                </div>
                <div class="text-center">
                    <div class="score-circle bg-yellow-500">
                        <?php echo $student_result['total_questions'] - $student_result['correct_answers']; ?>
                    </div>
                    <p class="text-gray-700 font-medium">Incorrect</p>
                </div>
                <div class="text-center">
                    <div class="score-circle bg-purple-500">
                        <?php echo $student_result['time_spent']; ?>
                    </div>
                    <p class="text-gray-700 font-medium">Minutes</p>
                </div>
            </div>

            <!-- Detailed Results -->
            <?php if (!empty($student_result['details'])): ?>
            <div class="mt-8">
                <h5 class="text-lg font-semibold text-gray-900 mb-4">Detailed Answers</h5>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700 border">Question</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700 border">Your Answer</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700 border">Correct Answer</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700 border">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student_result['details'] as $detail): ?>
                            <tr class="border">
                                <td class="px-4 py-3 border text-gray-700"><?php echo substr($detail['question_text'], 0, 50); ?>...</td>
                                <td class="px-4 py-3 border text-gray-700"><?php echo htmlspecialchars($detail['student_answer']); ?></td>
                                <td class="px-4 py-3 border text-gray-700"><?php echo htmlspecialchars($detail['correct_answer']); ?></td>
                                <td class="px-4 py-3 border">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $detail['is_correct'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $detail['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Signature Section -->
            <div class="mt-8 grid grid-cols-2 gap-6">
                <div>
                    <div class="signature-box flex items-center justify-center text-gray-500 text-sm">
                        Student's Signature
                    </div>
                </div>
                <div>
                    <div class="signature-box flex items-center justify-center text-gray-500 text-sm">
                        Exam Officer's Signature & Stamp
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center border-t pt-6">
                <p class="text-gray-600"><strong>Generated on:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                <p class="text-gray-500 text-sm mt-2">This is an official examination result</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            if (window.location.search.indexOf('auto_print=1') > -1) {
                window.print();
            }
        }
    </script>
</body>
</html>