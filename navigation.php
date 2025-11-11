<nav class="bg-white shadow-lg border-b">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <a href="index.php" class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Exam System Admin</span>
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="configuration.php" class="text-gray-600 hover:text-indigo-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'configuration.php' ? 'text-indigo-600 font-medium' : ''; ?>">
                    <i class="fas fa-cog mr-1"></i>Configuration
                </a>
                <a href="student_upload.php" class="text-gray-600 hover:text-indigo-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'student_upload.php' ? 'text-indigo-600 font-medium' : ''; ?>">
                    <i class="fas fa-upload mr-1"></i>Student Upload
                </a>
                <a href="combined_analysis.php" class="text-gray-600 hover:text-indigo-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'combined_analysis.php' ? 'text-indigo-600 font-medium' : ''; ?>">
                    <i class="fas fa-chart-line mr-1"></i>Analysis
                </a>
                <a href="?logout=1" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </div>
</nav>