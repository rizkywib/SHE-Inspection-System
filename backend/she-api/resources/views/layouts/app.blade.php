<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SHE Inspection System')</title>
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
                    <img id="sidebarBrandLogo" src="/images/ecogreen-logo-print.png" alt="Ecogreen Oleochemicals"
                        class="h-14 w-12 flex-shrink-0 rounded-lg bg-white object-contain">
                    <div>
                        <h1 class="font-bold text-lg">SHE Inspection</h1>
                        <p class="text-xs text-gray-400">Management System</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="/dashboard" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-dashboard', '')">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/dashboard/fire-hydrants" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-fire-hydrants', '')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 shrink-0"><path d="M4 22v-2h2v-3H5q-.825 0-1.412-.587T3 15v-2q0-.825.588-1.412T5 11h1V8H4V6h2.35q.625-1.75 2.163-2.875T12 2t3.488 1.125T17.65 6H20v2h-2v3h1q.825 0 1.413.588T21 13v2q0 .825-.587 1.413T19 17h-1v3h2v2zm10.475-5.525Q15.5 15.45 15.5 14t-1.025-2.475T12 10.5t-2.475 1.025T8.5 14t1.025 2.475T12 17.5t2.475-1.025m-3.537-1.412Q10.5 14.625 10.5 14t.438-1.062T12 12.5t1.063.438T13.5 14t-.437 1.063T12 15.5t-1.062-.437"/></svg>
                    <span>Hydrant</span>
                </a>
                <a href="/dashboard/fire-extinguishers#inspection-list" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-fire-extinguishers', '')">
                    <i class="fas fa-fire w-5"></i>
                    <span>Fire Extinguishers</span>
                </a>
                <a href="/dashboard/fire-alarms" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-fire-alarms', '')">
                    <i class="fas fa-bell w-5"></i>
                    <span>Fire Alarms</span>
                </a>
                <a href="/dashboard/es-ew-inspections" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-es-ew-inspections', '')">
                    <i class="fas fa-shower w-5"></i>
                    <span>ES&amp;EW Inspection</span>
                </a>
                <a href="/dashboard/inspections" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-inspections', '')">
                    <i class="fas fa-clipboard-check w-5"></i>
                    <span>Inspection</span>
                </a>
                <a href="/dashboard/permit-matrix" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-permit-matrix', '')">
                    <i class="fas fa-file-signature w-5"></i>
                    <span>Permit Matrix</span>
                </a>
                <a href="/dashboard/safety-talk-trainings" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-safety-talk-trainings', '')">
                    <i class="fas fa-people-group w-5"></i>
                    <span>Safety Talk / Training</span>
                </a>
                <div class="border-t border-gray-700 my-2"></div>
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase">Master Data</p>
                <a href="/dashboard/points" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-points', '')">
                    <i class="fas fa-map-marker-alt w-5"></i>
                    <span>Points</span>
                </a>
                <a href="/dashboard/fire-hydrant-locations" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-fire-hydrant-locations', '')">
                    <i class="fas fa-location-dot w-5"></i>
                    <span>Hydrant Locations</span>
                </a>
                <a href="/dashboard/fire-extinguisher-locations" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-fire-extinguisher-locations', '')">
                    <i class="fas fa-location-crosshairs w-5"></i>
                    <span>Extinguisher Locations</span>
                </a>
                <a href="/dashboard/incident-types" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-incident-types', '')">
                    <i class="fas fa-exclamation-triangle w-5"></i>
                    <span>Incident Types</span>
                </a>
                <a href="/dashboard/es-ew-areas" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-es-ew-areas', '')">
                    <i class="fas fa-shower w-5"></i>
                    <span>ES&EW Areas</span>
                </a>
                <a href="/dashboard/supervision-areas" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-supervision-areas', '')">
                    <i class="fas fa-eye w-5"></i>
                    <span>Area Pengawasan</span>
                </a>
                <a href="/dashboard/permit-types" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-permit-types', '')">
                    <i class="fas fa-file-signature w-5"></i>
                    <span>Type Permit</span>
                </a>
                <a href="/dashboard/permit-job-performances" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-permit-job-performances', '')">
                    <i class="fas fa-briefcase w-5"></i>
                    <span>Job Performance</span>
                </a>
                <a href="/dashboard/permit-main-areas" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-permit-main-areas', '')">
                    <i class="fas fa-layer-group w-5"></i>
                    <span>Main Area</span>
                </a>
                <a href="/dashboard/permit-sub-areas" class="nav-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-permit-sub-areas', '')">
                    <i class="fas fa-sitemap w-5"></i>
                    <span>Sub Area</span>
                </a>
                <a id="usersMenuDesktop" href="/dashboard/users" class="hidden nav-link items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 @yield('nav-users', '')">
                    <i class="fas fa-users w-5"></i>
                    <span>Users</span>
                </a>
            </nav>

            <div class="px-4 py-4 border-t border-gray-700">
                <div class="flex items-center space-x-3 px-4 py-3">
                    <a href="/dashboard/profile" class="flex items-center space-x-3 flex-1 min-w-0 hover:text-blue-200" title="Edit Profil">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center shrink-0">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate" id="userName"></p>
                            <p class="text-xs text-gray-400 truncate" id="userRole"></p>
                        </div>
                    </a>
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
                        <img id="mobileBrandLogo" src="/images/ecogreen-logo-print.png" alt="Ecogreen Oleochemicals"
                            class="h-9 w-8 flex-shrink-0 rounded bg-white object-contain">
                        <span class="font-bold text-lg">SHE Inspection</span>
                    </div>
                    <button onclick="toggleMobileMenu()" class="text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
                <div id="mobileMenu" class="hidden px-4 pb-4 space-y-2">
                    <a href="/dashboard" class="block py-2 text-gray-600 hover:text-gray-900">Dashboard</a>
                    <a href="/dashboard/fire-hydrants" class="block py-2 text-gray-600 hover:text-gray-900">Hydrant</a>
                    <a href="/dashboard/fire-extinguishers#inspection-list" class="block py-2 text-gray-600 hover:text-gray-900">Fire Extinguishers</a>
                    <a href="/dashboard/fire-alarms" class="block py-2 text-gray-600 hover:text-gray-900">Fire Alarms</a>
                    <a href="/dashboard/es-ew-inspections" class="block py-2 text-gray-600 hover:text-gray-900">ES&amp;EW Inspection</a>
                    <a href="/dashboard/inspections" class="block py-2 text-gray-600 hover:text-gray-900">Inspection</a>
                    <a href="/dashboard/permit-matrix" class="block py-2 text-gray-600 hover:text-gray-900">Permit Matrix</a>
                    <a href="/dashboard/safety-talk-trainings" class="block py-2 text-gray-600 hover:text-gray-900">Safety Talk / Training</a>
                    <div class="border-t border-gray-200 my-2"></div>
                    <p class="text-xs font-semibold text-gray-400 uppercase">Master Data</p>
                    <a href="/dashboard/points" class="block py-2 text-gray-600 hover:text-gray-900">Points</a>
                    <a href="/dashboard/fire-hydrant-locations" class="block py-2 text-gray-600 hover:text-gray-900">Hydrant Locations</a>
                    <a href="/dashboard/fire-extinguisher-locations" class="block py-2 text-gray-600 hover:text-gray-900">Extinguisher Locations</a>
                    <a href="/dashboard/incident-types" class="block py-2 text-gray-600 hover:text-gray-900">Incident Types</a>
                    <a href="/dashboard/es-ew-areas" class="block py-2 text-gray-600 hover:text-gray-900">ES&EW Areas</a>
                    <a href="/dashboard/supervision-areas" class="block py-2 text-gray-600 hover:text-gray-900">Area Pengawasan</a>
                    <a href="/dashboard/permit-types" class="block py-2 text-gray-600 hover:text-gray-900">Type Permit</a>
                    <a href="/dashboard/permit-job-performances" class="block py-2 text-gray-600 hover:text-gray-900">Job Performance</a>
                    <a href="/dashboard/permit-main-areas" class="block py-2 text-gray-600 hover:text-gray-900">Main Area</a>
                    <a href="/dashboard/permit-sub-areas" class="block py-2 text-gray-600 hover:text-gray-900">Sub Area</a>
                    <a id="usersMenuMobile" href="/dashboard/users" class="hidden py-2 text-gray-600 hover:text-gray-900">Users</a>
                    <a href="/dashboard/profile" class="block py-2 text-blue-600 hover:text-blue-800"><i class="fas fa-user-pen mr-2"></i>Edit Profil</a>
                    <button onclick="logout()" class="text-red-600 hover:text-red-800 text-sm py-2">Logout</button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-auto">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        }

        function logout() {
            const token = localStorage.getItem('token');
            const request = token
                ? fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                })
                : Promise.resolve();

            request.finally(() => {
                localStorage.clear();
                window.location.href = '/';
            });
        }

        (function renderLoggedInUser() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            const nameElement = document.getElementById('userName');
            const roleElement = document.getElementById('userRole');
            if (nameElement) nameElement.textContent = user.name || 'User';
            if (roleElement) roleElement.textContent = String(user.role || '').replaceAll('_', ' ');

            if (['super_admin', 'admin'].includes(user.role)) {
                const desktopUsersMenu = document.getElementById('usersMenuDesktop');
                const mobileUsersMenu = document.getElementById('usersMenuMobile');
                desktopUsersMenu?.classList.remove('hidden');
                desktopUsersMenu?.classList.add('flex');
                mobileUsersMenu?.classList.remove('hidden');
                mobileUsersMenu?.classList.add('block');
            }
        })();
    </script>
</body>
</html>
