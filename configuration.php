<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = '';

// Handle backend configuration update
if (isset($_POST['action']) && $_POST['action'] === 'update_backend') {
    $api_base_url = $_POST['api_base_url'];
    if (updateBackendConfig($api_base_url)) {
        $message = 'Backend configuration updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to update backend configuration.';
        $message_type = 'error';
    }
}

// Handle student import
if (isset($_POST['action']) && $_POST['action'] === 'import_students') {
    if (isset($_FILES['student_file']) && $_FILES['student_file']['error'] === UPLOAD_ERR_OK) {
        $handle = fopen($_FILES['student_file']['tmp_name'], 'r');
        $imported = 0;
        
        // Skip header if CSV
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 2) {
                $student_id = $data[0];
                $full_name = $data[1];
                $email = $data[2] ?? null;
                
                if (upsertStudent($student_id, $full_name, $email)) {
                    $imported++;
                }
            }
        }
        fclose($handle);
        
        $message = "Successfully imported $imported students!";
        $message_type = 'success';
    } else {
        $message = 'Please select a valid CSV file.';
        $message_type = 'error';
    }
}


$current_config = getBackendConfig();
$students = getAllStudents();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration - Exam System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'navigation.php'; ?>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Backend Configuration -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Backend Configuration</h2>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_backend">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">API Base URL</label>
                        <input type="url" name="api_base_url" value="<?php echo htmlspecialchars($current_config); ?>" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                               placeholder="http://localhost:8059/api/public" required>
                    </div>
                    
                    <button type="submit" class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 font-medium">
                        Update Configuration
                    </button>
                </form>
            </div>

            <!-- Student Management -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Student Management</h2>
                
                <form method="POST" enctype="multipart/form-data" class="mb-6">
                    <input type="hidden" name="action" value="import_students">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Import Students (CSV)</label>
                        <input type="file" name="student_file" accept=".csv" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        <p class="text-sm text-gray-500 mt-1">CSV format: student_id,full_name,email</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 font-medium">
                        Import Students
                    </button>
                </form>

                <!-- Students List -->
                <div class="border rounded-lg">
                    <div class="px-4 py-3 bg-gray-50 border-b">
                        <h3 class="font-medium text-gray-900">Current Students (<?php echo count($students); ?>)</h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <?php foreach ($students as $student): ?>
                        <div class="px-4 py-3 border-b last:border-b-0">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($student['full_name']); ?></p>
                                    <p class="text-sm text-gray-500">ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                                </div>
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Active</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>