<?php
require_once 'config.php';

$api_base = getBackendConfig();

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$exam_id = $_GET['exam_id'] ?? 0;

// Database connection using Database class
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new PDOException("Database connection failed");
    }
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle exam list operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_list') {
        $list_name = trim($_POST['list_name'] ?? '');
        $list_description = trim($_POST['list_description'] ?? '');
        
        if (!empty($list_name)) {
            $stmt = $pdo->prepare("INSERT INTO exam_lists (name, description) VALUES (?, ?)");
            $stmt->execute([$list_name, $list_description]);
            header('Location: students.php?exam_id=' . $exam_id);
            exit();
        } else {
            $error = "List name is required";
        }
    }
    
    if ($_POST['action'] === 'add_exam_to_list') {
        $list_id = $_POST['list_id'] ?? 0;
        $exam_id_to_add = $_POST['exam_id'] ?? 0;
        $exam_title = trim($_POST['exam_title'] ?? '');
        
        if ($list_id && $exam_id_to_add && !empty($exam_title)) {
            $stmt = $pdo->prepare("INSERT INTO exam_list_items (exam_list_id, exam_id, exam_title) VALUES (?, ?, ?)");
            $stmt->execute([$list_id, $exam_id_to_add, $exam_title]);
            header('Location: students.php?exam_id=' . $exam_id);
            exit();
        }
    }
}

if ($_GET['action'] ?? '' === 'delete_list') {
    $stmt = $pdo->prepare("DELETE FROM exam_lists WHERE id = ?");
    $stmt->execute([$_GET['list_id']]);
    header('Location: students.php?exam_id=' . $exam_id);
    exit();
}

if ($_GET['action'] ?? '' === 'remove_exam_from_list') {
    $stmt = $pdo->prepare("DELETE FROM exam_list_items WHERE id = ?");
    $stmt->execute([$_GET['item_id']]);
    header('Location: students.php?exam_id=' . $exam_id);
    exit();
}

// Fetch exam lists
$exam_lists = $pdo->query("SELECT * FROM exam_lists ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current exam data if available
$exam_data = null;
if ($exam_id) {
    $exam_data = json_decode(file_get_contents($api_base . "/exams/" . $exam_id . "/students"), true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students & Exam Lists - Exam System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <nav class="bg-gray-800 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-users text-xl"></i>
                    <h1 class="text-xl font-bold">
                        <?php echo $exam_id ? "Students - " . htmlspecialchars($exam_data['exam_title']) : "Exam Lists Management"; ?>
                    </h1>
                </div>
                <div class="flex space-x-2">
                    <a href="index.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                    <?php if ($exam_id): ?>
                    <a href="?exam_id=<?php echo $exam_id; ?>&print_all=1" class="bg-amber-500 hover:bg-amber-600 px-4 py-2 rounded-lg transition duration-200" target="_blank">
                        <i class="fas fa-print mr-2"></i>Print All Cards
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Error Message -->
        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Exam Lists Management -->
        <div class="bg-white rounded-lg shadow-md mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Exam Lists Management</h2>
            </div>
            <div class="p-6">
                <!-- Create New Exam List Form -->
                <form method="POST" class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <input type="hidden" name="action" value="create_list">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">List Name *</label>
                            <input type="text" name="list_name" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   value="<?php echo htmlspecialchars($_POST['list_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <input type="text" name="list_description" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   value="<?php echo htmlspecialchars($_POST['list_description'] ?? ''); ?>">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition duration-200">
                                <i class="fas fa-plus mr-2"></i>Create List
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Exam Lists Display -->
                <div class="space-y-4">
                    <?php foreach ($exam_lists as $list): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($list['name']); ?></h3>
                            <div class="flex space-x-2">
                                <a href="?exam_id=<?php echo $exam_id; ?>&print_list=<?php echo $list['id']; ?>" 
                                   class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded text-sm transition duration-200"
                                   target="_blank">
                                    <i class="fas fa-print mr-1"></i>Print All
                                </a>
                                <a href="?exam_id=<?php echo $exam_id; ?>&action=delete_list&list_id=<?php echo $list['id']; ?>" 
                                   class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition duration-200"
                                   onclick="return confirm('Are you sure you want to delete this exam list?')">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </a>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($list['description']); ?></p>
                        
                        <!-- Add Exam to List Form -->
                        <form method="POST" class="mb-3 p-3 bg-blue-50 rounded">
                            <input type="hidden" name="action" value="add_exam_to_list">
                            <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div>
                                    <input type="number" name="exam_id" placeholder="Exam ID" required
                                           class="w-full px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                                           value="<?php echo htmlspecialchars($_POST['exam_id'] ?? ''); ?>">
                                </div>
                                <div class="md:col-span-2">
                                    <input type="text" name="exam_title" placeholder="Exam Title" required
                                           class="w-full px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                                           value="<?php echo htmlspecialchars($_POST['exam_title'] ?? ''); ?>">
                                </div>
                                <div>
                                    <button type="submit" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition duration-200 w-full">
                                        <i class="fas fa-plus mr-1"></i>Add Exam
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- List of Exams in this List -->
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM exam_list_items WHERE exam_list_id = ?");
                        $stmt->execute([$list['id']]);
                        $exams_in_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if ($exams_in_list): ?>
                        <div class="bg-white border border-gray-200 rounded">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Exam ID</th>
                                        <th class="px-3 py-2 text-left">Exam Title</th>
                                        <th class="px-3 py-2 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exams_in_list as $exam_item): ?>
                                    <tr class="border-t border-gray-200 hover:bg-gray-50">
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($exam_item['exam_id']); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($exam_item['exam_title']); ?></td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="?exam_id=<?php echo $exam_id; ?>&action=remove_exam_from_list&item_id=<?php echo $exam_item['id']; ?>" 
                                               class="text-red-600 hover:text-red-800 text-sm"
                                               onclick="return confirm('Remove this exam from list?')">
                                                <i class="fas fa-times mr-1"></i>Remove
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-gray-500 py-4 bg-gray-50 rounded">
                            No exams added to this list yet.
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($exam_lists)): ?>
                    <div class="text-center text-gray-500 py-8 bg-gray-50 rounded-lg">
                        <i class="fas fa-list-alt text-4xl mb-4 text-gray-300"></i>
                        <p class="text-lg">No exam lists created yet.</p>
                        <p class="text-sm">Create your first exam list using the form above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Students Table (only shown when exam_id is provided) -->
        <?php if ($exam_id && $exam_data): ?>
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">
                    Students Assigned to Exam (<?php echo count($exam_data['students']); ?>)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Best Score</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($exam_data['students'] as $student): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($student['student_code']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($student['full_name']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($student['email']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $student['total_attempts'] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $student['total_attempts']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $student['best_score'] > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo $student['best_score']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $student['assignment_status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($student['assignment_status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="print_card.php?student_id=<?php echo $student['student_code']; ?>&exam_id=<?php echo $exam_id; ?>" 
                                   class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded border border-blue-200 transition duration-200">
                                    <i class="fas fa-id-card mr-1"></i>Print Card
                                </a>
                                <a href="student_result.php?student_id=<?php echo $student['student_id']; ?>&exam_id=<?php echo $exam_id; ?>" 
                                   class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 px-3 py-1 rounded border border-green-200 transition duration-200">
                                    <i class="fas fa-chart-line mr-1"></i>View Result
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['print_alll'])): ?>
    <script>
        window.onload = function() {
            <?php foreach ($exam_data['students'] as $student): ?>
            window.open('print_card.php?student_id=<?php echo $student['student_id']; ?>&exam_id=<?php echo $exam_id; ?>', '_blank');
            <?php endforeach; ?>
        }
    </script>
    <?php endif; ?>

    <?php if (isset($_GET['print_list'])): ?>
    <script>
        window.onload = function() {
            window.open('print_all_cards.php?exam_list_id=<?php echo $_GET['print_list']; ?>', '_blank');
        }
    </script>
    <?php endif; ?>
</body>
</html>