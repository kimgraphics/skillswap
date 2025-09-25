<?php
require_once '../includes/header.php';

if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Always initialize formData fully
$formData = [
    'username' => '',
    'email' => '',
    'password' => '',
    'first_name' => '',
    'last_name' => '',
    'location' => '',
    'availability' => 'flexible'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'location' => $_POST['location'] ?? '',
        'availability' => $_POST['availability'] ?? 'flexible'
    ];
    
    $auth = new Auth();
    $result = $auth->register($formData);
    
    if ($result['success']) {
        $success = $result['message'] . ' You can now <a href="login.php">login</a>.';
        
        // Reset form data safely
        $formData = [
            'username' => '',
            'email' => '',
            'password' => '',
            'first_name' => '',
            'last_name' => '',
            'location' => '',
            'availability' => 'flexible'
        ];
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SkillSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-image">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="card-title fw-bold">Create Your Account</h2>
                            <p class="text-muted">Join the SkillSwap community today</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($formData['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($formData['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($formData['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="generatePassword">
                                        <i class="fa fa-random"></i> Generate
                                    </button>
                                </div>
                                <small id="strengthMessage" class="text-muted"></small>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location (Optional)</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($formData['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="availability" class="form-label">Availability</label>
                                <select class="form-select" id="availability" name="availability">
                                    <option value="flexible" <?php echo ($formData['availability'] ?? '') === 'flexible' ? 'selected' : ''; ?>>Flexible</option>
                                    <option value="weekdays" <?php echo ($formData['availability'] ?? '') === 'weekdays' ? 'selected' : ''; ?>>Weekdays</option>
                                    <option value="weekends" <?php echo ($formData['availability'] ?? '') === 'weekends' ? 'selected' : ''; ?>>Weekends</option>
                                    <option value="both" <?php echo ($formData['availability'] ?? '') === 'both' ? 'selected' : ''; ?>>Both Weekdays & Weekends</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p class="text-muted">Already have an account? <a href="login.php">Sign in here</a></p>
                        </div>

                        <div class="text-center mt-2">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="fa fa-home"></i> Back to Home
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
        const pwd = document.getElementById('password');
        const type = pwd.type === 'password' ? 'text' : 'password';
        pwd.type = type;
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    // Generate strong password (always meets requirements)
    function generatePassword() {
        const lower = "abcdefghijklmnopqrstuvwxyz";
        const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        const numbers = "0123456789";
        const special = "!@#$%^&*()";
        const all = lower + upper + numbers + special;

        let password = "";
        password += lower.charAt(Math.floor(Math.random() * lower.length));
        password += upper.charAt(Math.floor(Math.random() * upper.length));
        password += numbers.charAt(Math.floor(Math.random() * numbers.length));
        password += special.charAt(Math.floor(Math.random() * special.length));

        for (let i = 4; i < 12; i++) {
            password += all.charAt(Math.floor(Math.random() * all.length));
        }

        password = password.split('').sort(() => 0.5 - Math.random()).join('');
        return password;
    }

    document.getElementById('generatePassword').addEventListener('click', function () {
        const pwdField = document.getElementById('password');
        pwdField.value = generatePassword();
        checkStrength(pwdField.value);
    });

    // Check password strength
    function checkStrength(password) {
        let strengthMessage = "";
        let strongRegex = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\\$%\\^&\\*])(?=.{8,})");
        if (!strongRegex.test(password)) {
            strengthMessage = "Password must be at least 8 chars, include uppercase, lowercase, number, and special char.";
            document.getElementById("strengthMessage").classList.add("text-danger");
            document.getElementById("strengthMessage").classList.remove("text-success");
        } else {
            strengthMessage = "Strong password ✅";
            document.getElementById("strengthMessage").classList.add("text-success");
            document.getElementById("strengthMessage").classList.remove("text-danger");
        }
        document.getElementById("strengthMessage").textContent = strengthMessage;
    }
    document.getElementById('password').addEventListener('input', function () {
        checkStrength(this.value);
    });
    </script>
</body>
</html>
