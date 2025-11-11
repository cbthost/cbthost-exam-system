<?php
require_once 'config.php';

$api_base = getBackendConfig();

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$exam_id = $_GET['exam_id'] ?? 0;
if (!$exam_id) {
    die("Exam ID is required");
}

$results_data = json_decode(file_get_contents($api_base . "/exams/" . $exam_id . "/results"), true);

// Process results with local student names
foreach ($results_data['results'] as &$result) {
    $local_student = getStudentByCode($result['student_code']);
    $result['display_name'] = $local_student ? $local_student['full_name'] : $result['full_name'];
    $result['is_verified'] = (bool)$local_student;
}
unset($result); // break the reference
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - Exam System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-bar text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900">
                        Results - <?php echo htmlspecialchars($results_data['exam_title']); ?>
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm">Total Students</p>
                        <p class="text-3xl font-bold"><?php echo $results_data['total_students']; ?></p>
                    </div>
                    <i class="fas fa-users text-2xl opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm">Completed Attempts</p>
                        <p class="text-3xl font-bold"><?php echo $results_data['total_completed_attempts']; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-2xl opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm">Average Score</p>
                        <p class="text-3xl font-bold"><?php echo number_format($results_data['average_score'], 2); ?></p>
                    </div>
                    <i class="fas fa-chart-line text-2xl opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm">Completion Rate</p>
                        <p class="text-3xl font-bold">
                            <?php echo $results_data['total_students'] > 0 ? number_format(($results_data['total_completed_attempts'] / $results_data['total_students']) * 100, 1) : 0; ?>%
                        </p>
                    </div>
                    <i class="fas fa-percentage text-2xl opacity-80"></i>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-2xl shadow-sm border">
            <div class="px-6 py-4 border-b">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Student Results</h2>
                        <p class="text-gray-600">Detailed performance analysis for all students</p>
                    </div>
                    <div class="text-sm text-gray-500">
                        <?php echo count($results_data['results']); ?> results
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Student ID</th>
                                <th class="px-4 py-3">Full Name</th>
                                <th class="px-4 py-3">Score</th>
                                <th class="px-4 py-3">Correct Answers</th>
                                <th class="px-4 py-3">Time Spent</th>
                                <th class="px-4 py-3">Completed At</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results_data['results'] as $result): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-gray-900">
                                    <?php echo htmlspecialchars($result['student_code']); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <?php echo htmlspecialchars($result['display_name']); ?>
                                        <?php if ($result['is_verified']): ?>
                                            <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full" title="Verified from local database">
                                                ✓
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full font-medium 
                                        <?php 
                                        $percentage = ($result['score'] / $result['total_questions']) * 100;
                                        echo $percentage >= 70 ? 'bg-green-100 text-green-800' : 
                                             ($percentage >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                                        ?>">
                                        <?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                        <?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">
                                        <?php echo $result['time_spent']; ?> mins
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <?php echo date('M j, Y g:i A', strtotime($result['completed_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <a href="print_result.php?student_id=<?php echo $result['student_id']; ?>&exam_id=<?php echo $exam_id; ?>&attempt_id=<?php echo $result['attempt_id']; ?>" 
                                           class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs hover:bg-blue-200 transition-colors"
                                           target="_blank">
                                            <i class="fas fa-print mr-1"></i>Result
                                        </a>
                                       
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($results_data['results'])): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p class="text-lg">No results found</p>
                    <p class="text-sm">No students have completed this exam yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>