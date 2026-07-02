<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'SHE Inspection System'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { transition: background-color 0.2s ease, color 0.2s ease; }
        .nav-link { position: relative; }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -8px;
            left: 50%;
            background-color: #2563eb;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        .nav-link:hover::after, .nav-link.active::after { width: 80%; }
        .nav-link.active { color: #2563eb; font-weight: 600; }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .sidebar-gradient {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }
    </style>
</head>
<body class="bg-gray-50 antialiased">
    <div id="app" class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar-gradient text-white w-64 hidden md:flex md:flex-col shadow-xl">
            <div class="px-6 py-6 border-b border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-500 rounded-lg p-2">
                        <i class="fas fa-hard-hat text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg">SHE Inspection</h1>
                        <p class="text-xs text-gray-400">Management System</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="/dashboard" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-dashboard', ''); ?>">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/dashboard/fire-hydrants" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-fire-hydrants', ''); ?>">
                    <i class="fas fa-fire-extinguisher w-5"></i>
                    <span>Fire Hydrants</span>
                </a>
                <a href="/dashboard/fire-extinguishers" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-fire-extinguishers', ''); ?>">
                    <i class="fas fa-fire w-5"></i>
                    <span>Fire Extinguishers</span>
                </a>
                <a href="/dashboard/fire-alarms" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-fire-alarms', ''); ?>">
                    <i class="fas fa-bell w-5"></i>
                    <span>Fire Alarms</span>
                </a>
                <div class="border-t border-gray-700 my-2"></div>
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase">Master Data</p>
                <a href="/dashboard/points" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-points', ''); ?>">
                    <i class="fas fa-map-marker-alt w-5"></i>
                    <span>Points</span>
                </a>
                <a href="/dashboard/fire-hydrant-locations" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-fire-hydrant-locations', ''); ?>">
                    <i class="fas fa-location-dot w-5"></i>
                    <span>Hydrant Locations</span>
                </a>
                <a href="/dashboard/fire-extinguisher-locations" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-fire-extinguisher-locations', ''); ?>">
                    <i class="fas fa-location-crosshairs w-5"></i>
                    <span>Extinguisher Locations</span>
                </a>
                <a href="/dashboard/incident-types" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-incident-types', ''); ?>">
                    <i class="fas fa-exclamation-triangle w-5"></i>
                    <span>Incident Types</span>
                </a>
                <a href="/dashboard/users" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 <?php echo $__env->yieldContent('nav-users', ''); ?>">
                    <i class="fas fa-users w-5"></i>
                    <span>Users</span>
                </a>
            </nav>

            <div class="px-4 py-4 border-t border-gray-700">
                <div class="flex items-center space-x-3 px-4 py-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium" id="userName"></p>
                        <p class="text-xs text-gray-400">Inspector</p>
                    </div>
                    <button onclick="logout()" class="text-gray-400 hover:text-white" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Mobile Header -->
            <header class="bg-white shadow md:hidden">
                <div class="px-4 py-3 flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-hard-hat text-blue-600 text-xl"></i>
                        <span class="font-bold text-lg">SHE Inspection</span>
                    </div>
                    <button onclick="toggleMobileMenu()" class="text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
                <div id="mobileMenu" class="hidden px-4 pb-4 space-y-2">
                    <a href="/dashboard" class="block py-2 text-gray-600 hover:text-gray-900">Dashboard</a>
                    <a href="/dashboard/fire-hydrants" class="block py-2 text-gray-600 hover:text-gray-900">Fire Hydrants</a>
                    <a href="/dashboard/fire-extinguishers" class="block py-2 text-gray-600 hover:text-gray-900">Fire Extinguishers</a>
                    <a href="/dashboard/fire-alarms" class="block py-2 text-gray-600 hover:text-gray-900">Fire Alarms</a>
                    <div class="border-t border-gray-200 my-2"></div>
                    <p class="text-xs font-semibold text-gray-400 uppercase">Master Data</p>
                    <a href="/dashboard/points" class="block py-2 text-gray-600 hover:text-gray-900">Points</a>
                    <a href="/dashboard/fire-hydrant-locations" class="block py-2 text-gray-600 hover:text-gray-900">Hydrant Locations</a>
                    <a href="/dashboard/fire-extinguisher-locations" class="block py-2 text-gray-600 hover:text-gray-900">Extinguisher Locations</a>
                    <a href="/dashboard/incident-types" class="block py-2 text-gray-600 hover:text-gray-900">Incident Types</a>
                    <a href="/dashboard/users" class="block py-2 text-gray-600 hover:text-gray-900">Users</a>
                    <button onclick="logout()" class="text-red-600 hover:text-red-800 text-sm py-2">Logout</button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-auto">
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        }
    </script>
</body>
</html>
<?php /**PATH D:\2.ANDROID\3.FlutterVSCode\projects\SHE Inspection System\backend\she-api\resources\views/layouts/app.blade.php ENDPATH**/ ?>