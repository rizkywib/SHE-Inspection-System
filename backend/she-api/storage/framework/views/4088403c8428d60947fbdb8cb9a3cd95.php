<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SHE Inspection System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 antialiased">
    <main class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-md">
            <div>
                <div class="mx-auto h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-hard-hat text-white text-2xl"></i>
                </div>
                <h1 class="mt-6 text-center text-3xl font-extrabold text-gray-900">SHE Inspection System</h1>
                <p class="mt-2 text-center text-sm text-gray-600">Sign in to your account</p>
            </div>

            <form id="loginForm" class="mt-8 space-y-6">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input id="username" name="username" type="text" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Username">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" name="password" type="password" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Password">
                    </div>
                </div>

                <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
                <div id="successMessage" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded"></div>

                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-sign-in-alt text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Sign in
                </button>
            </form>

            <div class="text-center">
                <p class="text-xs text-gray-500">Demo credentials: admin@sheinspection.com / admin123</p>
            </div>
        </div>
    </main>

    <script>
        const API_URL = '/api';
        let token = localStorage.getItem('token');

        async function login(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorEl = document.getElementById('errorMessage');
            const successEl = document.getElementById('successMessage');

            errorEl.classList.add('hidden');
            successEl.classList.add('hidden');

            try {
                const res = await fetch(`${API_URL}/auth/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ username, password })
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Login failed');
                token = data.token;
                localStorage.setItem('token', token);
                localStorage.setItem('user', JSON.stringify(data.user));
                successEl.textContent = 'Login successful! Redirecting...';
                successEl.classList.remove('hidden');
                setTimeout(() => window.location.href = '/dashboard', 800);
            } catch (err) {
                errorEl.textContent = err.message;
                errorEl.classList.remove('hidden');
            }
        }

        if (token) window.location.href = '/dashboard';
        document.getElementById('loginForm').addEventListener('submit', login);
    </script>
</body>
</html>
<?php /**PATH D:\2.ANDROID\3.FlutterVSCode\projects\SHE Inspection System\backend\she-api\resources\views/auth/login.blade.php ENDPATH**/ ?>