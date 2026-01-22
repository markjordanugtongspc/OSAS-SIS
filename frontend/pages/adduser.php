<?php
session_start();
require_once '../../config/db.php';
require_once '../../backend/vite_helper.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

// --- Setup User Variables for Navbar ---
$firstname = $_SESSION['firstname'] ?? 'Guest';
$lastname = $_SESSION['lastname'] ?? '';
$position = $_SESSION['position'] ?? '';
$image = $_SESSION['image'] ?? '';

// --- Admin Role Check ---
// We check if the user's position contains 'Admin' (case-insensitive)
$userPosition = $_SESSION['position'] ?? '';
$isAdmin = stripos($userPosition, 'Admin') !== false;

if (!$isAdmin) {
    // If not admin, access denied. 
    // Ideally, we'd show a specialized 403 page or redirect with a flash message.
    // For now, redirect to dashboard.
    header("Location: dashboard.php");
    exit;
}

// --- Handle Form Actions ---
$message = '';
$error = '';

// Check for session messages from previous redirect
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $fname = trim($_POST['firstname']);
            $lname = trim($_POST['lastname']);
            $email = trim($_POST['email']);
            $position = trim($_POST['position']);
            $password = $_POST['password'];

            if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
                $_SESSION['error_message'] = "All required fields must be filled.";
            } else {
                // Check email uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $_SESSION['error_message'] = "This email address is already registered.";
                } else {
                    // Image Upload
                    $imageName = '';
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $targetDir = "../images/users/";
                        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                        
                        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($fileExt, $allowed)) {
                            $newFileName = uniqid() . '.' . $fileExt;
                            $targetPath = $targetDir . $newFileName;
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                                $imageName = $newFileName;
                            }
                        }
                    }

                    $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (firstname, lastname, email, position, password, image) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$fname, $lname, $email, $position, $hashedPwd, $imageName]);
                    $_SESSION['success_message'] = "New user account created successfully.";
                }
            }

        } elseif ($action === 'edit') {
            $id = $_POST['user_id'];
            $fname = trim($_POST['firstname']);
            $lname = trim($_POST['lastname']);
            $email = trim($_POST['email']);
            $position = trim($_POST['position']);
            $password = $_POST['password'];
            
            // Image Upload
            $imageName = $_POST['current_image'] ?? '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $targetDir = "../images/users/";
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExt, $allowed)) {
                    $newFileName = uniqid() . '.' . $fileExt;
                    $targetPath = $targetDir . $newFileName;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imageName = $newFileName;
                    }
                }
            }

            if (!empty($password)) {
                $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET firstname=?, lastname=?, email=?, position=?, password=?, image=? WHERE id=?";
                $params = [$fname, $lname, $email, $position, $hashedPwd, $imageName, $id];
            } else {
                $sql = "UPDATE users SET firstname=?, lastname=?, email=?, position=?, image=? WHERE id=?";
                $params = [$fname, $lname, $email, $position, $imageName, $id];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['success_message'] = "User details updated successfully.";

        } elseif ($action === 'delete') {
            $id = $_POST['user_id'];
            if ($id == $_SESSION['user_id']) {
                $_SESSION['error_message'] = "Fail: You cannot delete your own account while logged in.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success_message'] = "User account has been permanently removed.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database Error: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header("Location: adduser.php");
    exit;
}

// --- Fetch All Users ---
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../frontend/images/spc.png">
    <title>User Management | OSAS-SIS</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=UnifrakturMaguntia&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <?= vite(['frontend/css/styles.css', 'backend/js/main.js']) ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Iconify -->
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .old-english { font-family: 'UnifrakturMaguntia', serif; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800">

    <?php include 'navbar.php'; ?>

    <main class="ml-64 p-8 transition-all duration-300">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">User Management</h1>
                <p class="text-sm text-slate-500 mt-1">Manage system administrators and staff accounts.</p>
            </div>
            <button onclick="openModal('add')" class="flex items-center gap-2 bg-[#800020] hover:bg-[#5c0016] text-white px-4 py-2.5 rounded-lg transition-colors shadow-sm font-medium text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Add New User
            </button>
        </div>

        <?php if($message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: '<?= htmlspecialchars($message) ?>',
                        confirmButtonColor: '#800020'
                    });
                });
            </script>
        <?php endif; ?>

        <?php if($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: '<?= htmlspecialchars($error) ?>',
                        confirmButtonColor: '#800020'
                    });
                });
            </script>
        <?php endif; ?>

        <!-- Users Table Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-slate-500 font-medium border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Role/Position</th>
                            <th class="px-6 py-4">Email Address</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($users as $user): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if(!empty($user['image']) && file_exists("../images/users/" . $user['image'])): ?>
                                            <img src="../images/users/<?= htmlspecialchars($user['image']) ?>" alt="" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold border border-gray-200">
                                                <?= strtoupper(substr($user['firstname'],0,1) . substr($user['lastname'],0,1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></div>
                                            <div class="text-xs text-slate-500">ID: #<?= $user['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $pos = $user['position'];
                                    $badgeClass = 'bg-slate-100 text-slate-800'; // Default
                                    
                                    if (stripos($pos, 'Admin') !== false) {
                                        $badgeClass = 'bg-purple-100 text-purple-800';
                                    } elseif ($pos === 'Staff') {
                                        $badgeClass = 'bg-blue-100 text-blue-800';
                                    } elseif ($pos === 'Student Assistant') {
                                        $badgeClass = 'bg-amber-100 text-amber-800';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                        <?= htmlspecialchars($user['position']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <?= htmlspecialchars($user['email']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick='editUser(<?= json_encode($user) ?>)' class="p-1.5 text-amber-500 hover:bg-amber-50 hover:text-amber-700 rounded-lg transition-all duration-200 group cursor-pointer" title="Edit">
                                            <span class="iconify w-4.5 h-4.5" data-icon="solar:pen-bold" data-inline="false"></span>
                                        </button>
                                        
                                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['firstname']) ?>')" class="p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700 rounded-lg transition-all duration-200 group cursor-pointer" title="Delete">
                                            <span class="iconify w-4.5 h-4.5" data-icon="solar:trash-bin-trash-bold" data-inline="false"></span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500 italic">
                                    No users found in the system.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Base -->
    <div id="userModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal Panel -->
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" id="modalPanel">
                    
                    <form action="" method="POST" enctype="multipart/form-data" id="userForm">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="user_id" id="userId" value="">
                        <input type="hidden" name="current_image" id="currentImage" value="">

                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <h3 class="text-xl font-bold leading-6 text-gray-900 mb-6" id="modalTitle">Add New User</h3>
                            
                            <div class="space-y-4">
                                <!-- Image Preview & Input -->
                                <div class="flex items-center gap-4">
                                    <div id="imagePreview" class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center overflow-hidden border border-gray-300 flex-shrink-0">
                                        <svg class="w-8 h-8 text-slate-400" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Profile Photo</label>
                                        <input type="file" name="image" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-[#800020] hover:file:bg-red-100 transition-all cursor-pointer" onchange="previewFile(this)">
                                        <p class="text-xs text-slate-400 mt-1">PNG, JPG up to 5MB</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                                        <input type="text" name="firstname" id="firstname" required class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:border-[#800020] focus:ring-[#800020]">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Last Name</label>
                                        <input type="text" name="lastname" id="lastname" required class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:border-[#800020] focus:ring-[#800020]">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                                    <input type="email" name="email" id="email" required class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:border-[#800020] focus:ring-[#800020]">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Position/Role</label>
                                    <select name="position" id="position" required class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:border-[#800020] focus:ring-[#800020]">
                                        <option value="Staff">Staff</option>
                                        <option value="Student Assistant">Student Assistant</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                                    <input type="password" name="password" id="password" class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:border-[#800020] focus:ring-[#800020]" placeholder="••••••••">
                                    <p class="text-xs text-slate-500 mt-1" id="passwordHint">Required for new users.</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-[#800020] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#5c0016] sm:ml-3 sm:w-auto transition-all">Save User</button>
                            <button type="button" onclick="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto transition-all">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const modalPanel = document.getElementById('modalPanel');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const userForm = document.getElementById('userForm');
        
        function openModal(mode) {
            modal.classList.remove('hidden');
            // Animate in
            setTimeout(() => {
                modalBackdrop.classList.remove('opacity-0');
                modalPanel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
                modalPanel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
            }, 10);

            if (mode === 'add') {
                modalTitle.textContent = "Add New User";
                formAction.value = 'add';
                document.getElementById('password').required = true;
                document.getElementById('passwordHint').textContent = "Required for new users.";
                userForm.reset();
                document.getElementById('userId').value = '';
                document.getElementById('currentImage').value = '';
                
                // Remove Admin option if present
                const positionSelect = document.getElementById('position');
                Array.from(positionSelect.options).forEach(opt => {
                    if(opt.value === 'Admin') positionSelect.removeChild(opt);
                });

                resetPreview();
            }
        }

        function closeModal() {
            // Animate out
            modalBackdrop.classList.add('opacity-0');
            modalPanel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
            modalPanel.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function editUser(user) {
            openModal('edit');
            modalTitle.textContent = "Edit User";
            formAction.value = 'edit';
            
            // Populate Fields
            document.getElementById('userId').value = user.id;
            document.getElementById('firstname').value = user.firstname;
            document.getElementById('lastname').value = user.lastname;
            document.getElementById('email').value = user.email;
            // Handle Admin Option
            const positionSelect = document.getElementById('position');
            // Remove existing to prevent duplicates
            Array.from(positionSelect.options).forEach(opt => {
                if(opt.value === 'Admin') positionSelect.removeChild(opt);
            });
            
            if (user.position === 'Admin') {
                const opt = document.createElement('option');
                opt.value = 'Admin';
                opt.textContent = 'Administrator';
                positionSelect.appendChild(opt);
                positionSelect.value = 'Admin';
            } else {
                positionSelect.value = user.position;
            }
            document.getElementById('currentImage').value = user.image || '';
            
            // Handle Password
            const pwdInput = document.getElementById('password');
            pwdInput.required = false;
            pwdInput.value = '';
            document.getElementById('passwordHint').textContent = "Leave blank to keep current password.";
            
            // Handle Image Preview
            if (user.image) {
                document.getElementById('imagePreview').innerHTML = `<img src="../images/users/${user.image}" class="w-full h-full object-cover">`;
            } else {
                resetPreview();
            }
        }

        function resetPreview() {
            document.getElementById('imagePreview').innerHTML = `
                <svg class="w-8 h-8 text-slate-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
            `;
        }

        function previewFile(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                }
                reader.readAsDataURL(file);
            }
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete user: ${name}. This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a form to submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    const inputAction = document.createElement('input');
                    inputAction.type = 'hidden';
                    inputAction.name = 'action';
                    inputAction.value = 'delete';
                    
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'user_id';
                    inputId.value = id;
                    
                    form.appendChild(inputAction);
                    form.appendChild(inputId);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
