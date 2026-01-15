<?php
session_start();
require_once 'backend/vite_helper.php';
require_once 'config/db.php';

// Get flash data
$error = $_SESSION['error'] ?? '';
$typedEmail = $_SESSION['typed_email'] ?? '';
if ($error) {
    unset($_SESSION['error']);
    unset($_SESSION['typed_email']);
}

// Check for successful login flag
$loginSuccess = isset($_SESSION['login_success']) ? $_SESSION['login_success'] : false;
if ($loginSuccess) {
    unset($_SESSION['login_success']); // Clear the flag
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
    } else {
        $remember = isset($_POST['remember']);
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Password is correct
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['position'] = $user['position'];
                $_SESSION['image'] = $user['image'];
                $_SESSION['login_success'] = true; // Flag for loading animation and redirect
                
                // Handle Remember Me
                if ($remember) {
                    setcookie('remember_email', $email, time() + (86400 * 30), "/"); // 30 days
                    setcookie('remember_password', $password, time() + (86400 * 30), "/"); // 30 days
                } else {
                    setcookie('remember_email', '', time() - 3600, "/");
                    setcookie('remember_password', '', time() - 3600, "/");
                }
                
                // Redirect back to show animation, then redirect to dashboard via JS
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                // Invalid credentials (generic message for security)
                $_SESSION['error'] = "Invalid email or password.";
                $_SESSION['typed_email'] = $email;
                
                // Clear password cookie if it exists but login failed
                if (isset($_COOKIE['remember_password'])) {
                    setcookie('remember_password', '', time() - 3600, "/");
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "System error: " . $e->getMessage();
        }
    }
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sport Inventory & Cabinet Management System</title>
    <?= vite(['backend/js/main.js', 'frontend/css/styles.css']) ?>
</head>
<body class="h-full bg-white font-sans antialiased">

    <!-- Toast Notification -->
    <?php if ($error): ?>
        <div id="toast" class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-2xl border border-red-200 overflow-hidden animate-slide-in-right">
            <div class="p-4 flex items-start gap-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-red-600">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1 pt-0.5">
                    <h3 class="text-sm font-semibold text-gray-900">Authentication Failed</h3>
                    <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($error) ?></p>
                </div>
                <button onclick="document.getElementById('toast').remove()" class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <!-- Progress bar -->
            <div class="h-1 bg-red-500 animate-shrink"></div>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast');
                if (toast) {
                    toast.classList.add('animate-fade-out');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 5000);
        </script>
    <?php endif; ?>

    <div class="h-full flex">
        
        <!-- Left Side - Maroon Panel with Background -->
        <div class="hidden lg:flex lg:flex-1 items-center justify-center p-12 relative overflow-hidden">
            <!-- Background Image -->
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('frontend/images/DSA.jpg');"></div>
            
            <!-- Maroon Overlay (semi-transparent to show image) -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#800020]/85 to-[#5c0016]/90"></div>
            
            <!-- Decorative Elements -->
            <div class="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-1/3 translate-y-1/3"></div>
            
            <div class="relative z-10 text-center">
                <div class="w-48 h-48 rounded-full bg-white mx-auto mb-8 drop-shadow-2xl overflow-hidden flex items-center justify-center">
                    <img src="frontend/images/spc.png" alt="St. Peter's College Logo" class="w-full h-full object-cover">
                </div>
                <h1 class="text-4xl font-bold text-white mb-4 tracking-tight">St. Peter's College</h1>
                <p class="text-lg text-white/80 font-light">Sport Inventory System &amp; Cabinet Management System</p>
            </div>
        </div>

        <!-- Right Side - White Login Form -->
        <div class="flex-1 flex items-center justify-center p-8 sm:p-12">
            <div class="w-full max-w-md">
                
                <!-- Mobile Logo -->
                <div class="lg:hidden text-center mb-8">
                    <div class="w-24 h-24 rounded-full bg-white mx-auto mb-4 drop-shadow-lg overflow-hidden flex items-center justify-center">
                        <img src="frontend/images/spc.png" alt="St. Peter's College Logo" class="w-full h-full object-cover">
                    </div>
                    <h2 class="text-2xl font-bold text-[#800020]">St. Peter's College</h2>
                    <p class="text-sm text-gray-600 mt-1">Sport Inventory System &amp; Cabinet Management System</p>
                </div>

                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome back</h2>
                    <p class="text-gray-600 mb-8">Please sign in to your account</p>
                </div>


                <form class="space-y-6" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email address</label>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            autocomplete="email" 
                            required
                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all"
                            placeholder="you@example.com"
                            value="<?= $typedEmail ?: (isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : '') ?>">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                autocomplete="current-password"
                                required
                                class="block w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all"
                                placeholder="••••••••"
                                value="<?= isset($_COOKIE['remember_password']) ? htmlspecialchars($_COOKIE['remember_password']) : '' ?>">
                            <button 
                                type="button"
                                id="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#800020] transition-colors focus:outline-none"
                                aria-label="Toggle password visibility">
                                <!-- Eye Closed Icon (default) -->
                                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                                <!-- Eye Open Icon (hidden by default) -->
                                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox" <?= isset($_COOKIE['remember_email']) ? 'checked' : '' ?> class="h-4 w-4 text-[#800020] focus:ring-[#800020] border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                        </div>
                    </div>

                    <div>
                        <button 
                            type="submit"
                            class="w-full bg-[#800020] hover:bg-[#5c0016] text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#800020] focus:ring-offset-2 active:scale-[0.98]">
                            Sign in
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-xs text-gray-500">
                    &copy; <?= date('Y') ?> St. Peter's College. All rights reserved.
                </p>
            </div>
        </div>

    </div>

    <!-- Loading Animation Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 text-center shadow-2xl transform transition-all">
            <div class="mb-6">
                <!-- Spinning Logo -->
                <div class="w-24 h-24 mx-auto mb-4 relative">
                    <div class="absolute inset-0 rounded-full bg-gradient-to-br from-[#800020] to-[#5c0016] animate-spin-slow"></div>
                    <div class="absolute inset-2 rounded-full bg-white flex items-center justify-center">
                        <img src="frontend/images/spc.png" alt="Loading" class="w-16 h-16 object-cover rounded-full">
                    </div>
                </div>
                <!-- Loading Text -->
                <h3 class="text-xl font-bold text-gray-900 mb-2">Welcome Back!</h3>
                <p class="text-gray-600">Redirecting to dashboard...</p>
            </div>
            <!-- Progress Bar -->
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-[#800020] to-[#5c0016] rounded-full animate-progress"></div>
            </div>
        </div>
    </div>

    <style>
        /* Modern UI adjustments */
    </style>

    <script>
        // Password Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeClosed = document.getElementById('eyeClosed');
            const eyeOpen = document.getElementById('eyeOpen');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    // Toggle password visibility
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle eye icons with smooth transition
                    eyeClosed.classList.toggle('hidden');
                    eyeOpen.classList.toggle('hidden');
                });
            }

            // Show loading animation ONLY on successful login
            const loginSuccess = <?= $loginSuccess ? 'true' : 'false' ?>;
            const loadingModal = document.getElementById('loadingModal');
            
            if (loginSuccess && loadingModal) {
                // Show the loading modal
                loadingModal.classList.remove('hidden');
                loadingModal.classList.add('flex');
                
                // Redirect to dashboard after animation completes (3.8 seconds)
                setTimeout(function() {
                    window.location.href = 'frontend/pages/dashboard.php';
                }, 3800);
            }
        });
    </script>


</body>

</html>
