<?php
require_once 'config.php';
session_start();

$api_base = getBackendConfig();
// print($api_base);

// Fetch all exams
$exams_json = @file_get_contents($api_base . "/exams");
$exams = $exams_json ? json_decode($exams_json, true) : [];

// Simple admin authentication
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$is_logged_in && isset($_POST['password'])) {
    if ($_POST['password'] === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $is_logged_in = true;
    }
}

if (!$is_logged_in) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Exam System - Admin Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lock text-white text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Admin Login</h2>
                    <p class="text-gray-600 mt-2">Enter your password to access the dashboard</p>
                </div>
                
                <form method="POST">
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Admin Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            <i class="fas fa-key absolute right-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <button type="submit" 
                            class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors font-medium">
                        Sign In
                    </button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam System - Admin Dashboard</title>
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
                        <i class="fas fa-graduation-cap text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Exam System Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="configuration.php" class="text-gray-600 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-cog mr-1"></i>Configuration
                    </a>
                    <a href="student_upload.php" class="text-gray-600 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-users mr-1"></i>Students
                    </a>
                    <a href="combined_analysis.php" class="text-gray-600 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-chart-line mr-1"></i>Analysis
                    </a>
                    <a href="?logout=1" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-indigo-100">Total Exams</p>
                        <p class="text-3xl font-bold"><?php echo count($exams); ?></p>
                    </div>
                    <i class="fas fa-file-alt text-2xl opacity-80"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100">Active Students</p>
                        <p class="text-3xl font-bold">-</p>
                    </div>
                    <i class="fas fa-users text-2xl opacity-80"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100">Completed Tests</p>
                        <p class="text-3xl font-bold">-</p>
                    </div>
                    <i class="fas fa-check-circle text-2xl opacity-80"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-orange-500 to-red-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100">System Status</p>
                        <p class="text-3xl font-bold">Online</p>
                    </div>
                    <i class="fas fa-server text-2xl opacity-80"></i>
                </div>
            </div>
        </div>

        <!-- Exams Grid -->
        <div class="bg-white rounded-2xl shadow-sm border">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold text-gray-900">Available Exams</h2>
                <p class="text-gray-600">Manage and monitor examination activities</p>
            </div>
            
            <div class="p-6">
                <?php if (empty($exams)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">No exams found</p>
                        <p class="text-gray-400">Check your backend configuration</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($exams as $exam): ?>
                        <div class="border rounded-xl p-6 hover:shadow-md transition-shadow <?php echo $exam['status'] === 'active' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-gray-400'; ?>">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($exam['title']); ?></h3>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $exam['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($exam['status']); ?>
                                </span>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($exam['description']); ?></p>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                                <div class="text-center p-2 bg-gray-50 rounded-lg">
                                    <div class="text-gray-500">Duration</div>
                                    <div class="font-semibold text-gray-900"><?php echo $exam['duration']; ?> mins</div>
                                </div>
                                <div class="text-center p-2 bg-gray-50 rounded-lg">
                                    <div class="text-gray-500">Students</div>
                                    <div class="font-semibold text-gray-900"><?php echo $exam['total_students'] ?? 0; ?></div>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="students.php?exam_id=<?php echo $exam['id']; ?>" 
                                   class="flex-1 bg-indigo-50 text-indigo-700 py-2 px-3 rounded-lg text-sm font-medium text-center hover:bg-indigo-100 transition-colors">
                                    <i class="fas fa-users mr-1"></i>Students
                                </a>
                                <a href="results.php?exam_id=<?php echo $exam['id']; ?>" 
                                   class="flex-1 bg-green-50 text-green-700 py-2 px-3 rounded-lg text-sm font-medium text-center hover:bg-green-100 transition-colors">
                                    <i class="fas fa-chart-bar mr-1"></i>Results
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>