<?php
// 1. Load config and DB first so BASE_URL and tab sessions are properly initialized
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/supabase_auth.php';

// Redirect logged-in users before rendering headers
if (!empty($auth_user)) {
    header('Location: ' . auth_url('index.php'));
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pwd  = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($password !== $confirm_pwd) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Register via Supabase Auth API
        $res = supabase_auth_signup($email, $password, [
            'name'         => $name,
            'phone_number' => $phone_number
        ]);

        if (!empty($res['success'])) {
            $success = 'Account created successfully! A confirmation email has been dispatched to <strong>' . htmlspecialchars($email) . '</strong>. You can now <a href="' . auth_url('login.php') . '" class="alert-link">login here</a>.';
            $_POST = [];
        } else {
            $error = $res['error'] ?? 'Registration failed. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <h3 class="card-title fw-bold text-center mb-1">Create an Account</h3>
        <p class="text-muted text-center small mb-4">Join our community to report lost or found pets</p>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success py-2 small"><?= $success ?></div>
        <?php endif; ?>

        <form action="<?= auth_url('register.php') ?>" method="POST" data-validate novalidate>
          <div class="mb-3">
            <label for="name" class="form-label fw-semibold">Full Name *</label>
            <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email Address *</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label for="phone_number" class="form-label fw-semibold">Phone Number</label>
            <input type="text" name="phone_number" id="phone_number" class="form-control" placeholder="e.g., 09171234567" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Password *</label>
            <input type="password" name="password" id="password" class="form-control" required>
            <div class="form-text">Minimum 6 characters.</div>
          </div>

          <div class="mb-3">
            <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Register</button>
        </form>

        <p class="text-center text-muted mt-3 mb-0 small">
          Already have an account? <a href="<?= auth_url('login.php') ?>">Login here</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>