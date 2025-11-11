<?php

require_once 'config.php';


$api_base = getBackendConfig();

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$student_id = $_GET['student_id'] ?? 0;
$exam_id = $_GET['exam_id'] ?? 0;

if (!$student_id || !$exam_id) {
    die("Student ID and Exam ID are required");
}

// $api_base = "http://192.168.1.116:8054/api/public";
$results_data = json_decode(file_get_contents($api_base . "/exams/" . $exam_id . "/results"), true);

// Find student result
$student_result = null;
foreach ($results_data['results'] as $result) {
    if ($result['student_id'] == $student_id) {
        $student_result = $result;
        break;
    }
}

if (!$student_result) {
    die("Student result not found for this exam");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-user-graduate me-2"></i>
                <?php echo htmlspecialchars($student_result['full_name']); ?> - Result
            </span>
            <div>
                <a href="students.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left me-1"></i>Back to Students
                </a>
                <a href="print_result.php?student_id=<?php echo $student_id; ?>&exam_id=<?php echo $exam_id; ?>&attempt_id=<?php echo $student_result['attempt_id']; ?>" 
                   class="btn btn-warning btn-sm" target="_blank">
                    <i class="fas fa-print me-1"></i>Print Result
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Exam Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-4">
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h3 class="text-primary"><?php echo $student_result['score']; ?>/<?php echo $student_result['total_questions']; ?></h3>
                                    <small class="text-muted">SCORE</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h3 class="text-success"><?php echo $student_result['correct_answers']; ?></h3>
                                    <small class="text-muted">CORRECT</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h3 class="text-danger"><?php echo $student_result['total_questions'] - $student_result['correct_answers']; ?></h3>
                                    <small class="text-muted">INCORRECT</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h3 class="text-info"><?php echo $student_result['time_spent']; ?>m</h3>
                                    <small class="text-muted">TIME SPENT</small>
                                </div>
                            </div>
                        </div>

                        <div class="progress mb-3" style="height: 30px;">
                            <div class="progress-bar bg-success" style="width: <?php echo ($student_result['correct_answers'] / $student_result['total_questions']) * 100; ?>%">
                                Correct: <?php echo $student_result['correct_answers']; ?>
                            </div>
                            <div class="progress-bar bg-danger" style="width: <?php echo (($student_result['total_questions'] - $student_result['correct_answers']) / $student_result['total_questions']) * 100; ?>%">
                                Incorrect: <?php echo $student_result['total_questions'] - $student_result['correct_answers']; ?>
                            </div>
                        </div>

                        <h5>Performance Percentage</h5>
                        <div class="progress mb-4" style="height: 25px;">
                            <div class="progress-bar 
                                <?php 
                                $percentage = ($student_result['score'] / $student_result['total_questions']) * 100;
                                if ($percentage >= 70) echo 'bg-success';
                                elseif ($percentage >= 50) echo 'bg-warning';
                                else echo 'bg-danger';
                                ?>" 
                                style="width: <?php echo $percentage; ?>%">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($student_result['details'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detailed Answers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Question</th>
                                        <th>Your Answer</th>
                                        <th>Correct Answer</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($student_result['details'] as $index => $detail): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars(substr($detail['question_text'], 0, 80)); ?>...</td>
                                        <td><?php echo htmlspecialchars($detail['student_answer']); ?></td>
                                        <td><?php echo htmlspecialchars($detail['correct_answer']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $detail['is_correct'] ? 'success' : 'danger'; ?>">
                                                <?php echo $detail['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Student Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Student ID:</strong><br><?php echo htmlspecialchars($student_result['student_code']); ?></p>
                        <p><strong>Full Name:</strong><br><?php echo htmlspecialchars($student_result['full_name']); ?></p>
                        <p><strong>Exam:</strong><br><?php echo htmlspecialchars($results_data['exam_title']); ?></p>
                        <p><strong>Completed:</strong><br><?php echo date('F j, Y g:i A', strtotime($student_result['completed_at'])); ?></p>
                        <p><strong>Attempt ID:</strong><br><?php echo $student_result['attempt_id']; ?></p>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="print_card.php?student_id=<?php echo $student_id; ?>&exam_id=<?php echo $exam_id; ?>" 
                           class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-id-card me-1"></i>Print Exam Card
                        </a>
                        <a href="print_result.php?student_id=<?php echo $student_id; ?>&exam_id=<?php echo $exam_id; ?>&attempt_id=<?php echo $student_result['attempt_id']; ?>" 
                           class="btn btn-outline-success w-100 mb-2" target="_blank">
                            <i class="fas fa-print me-1"></i>Print Result
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>