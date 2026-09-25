<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/supabase_auth.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/PetSeeker/');
}

$error = '';
$info  = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'registered_check_email') {
    $info = 'Registration successful! Please check your email inbox to verify your account or proceed to log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $tab_id     = trim($_POST['tab_id'] ?? $_GET['tab_id'] ?? '');

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter your email/username and password.';
    } else {
        $user = null;
        $auth_success = false;

        // Route 1: Email-based login through Supabase Auth
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $supa_res = supabase_auth_signin($identifier, $password);

            if ($supa_res['success']) {
                $auth_id = $supa_res['data']['user']['id'];

                // Retrieve local user record and role
                $sql = 'SELECT u.user_id, u.name, u.email, u.username, u.role_id, r.role_name 
                        FROM "user" u 
                        JOIN role r ON u.role_id = r.role_id 
                        WHERE u.auth_id = :auth_id OR u.email = :email
                        LIMIT 1';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':auth_id' => $auth_id,
                    ':email'   => $identifier
                ]);
                $user = $stmt->fetch();

                if ($user) {
                    $auth_success = true;

                    // Ensure auth_id is linked if previously missing
                    if (empty($user['auth_id'])) {
                        $up_stmt = $pdo->prepare('UPDATE "user" SET auth_id = ? WHERE user_id = ?');
                        $up_stmt->execute([$auth_id, $user['user_id']]);
                    }
                } else {
                    $error = 'User account profile not found. Please contact support.';
                }
            } else {
                $error = $supa_res['error'];
            }
        } 
        
        // Route 2: Fallback for Staff/Admin logging in with a Username
        if (!$auth_success && empty($error)) {
            $sql = 'SELECT u.user_id, u.name, u.email, u.username, u.password, u.role_id, r.role_name 
                    FROM "user" u 
                    JOIN role r ON u.role_id = r.role_id 
                    WHERE u.username = :id OR u.email = :id
                    LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $identifier]);
            $local_user = $stmt->fetch();

            if ($local_user && !empty($local_user['password']) && password_verify($password, $local_user['password'])) {
                $user = $local_user;
                $auth_success = true;
            } else {
                $error = 'Invalid credentials entered.';
            }
        }

        // Establish tab-isolated session
        if ($auth_success && $user) {
            if (empty($tab_id)) {
                $tab_id = 'tab_' . bin2hex(random_bytes(8));
            }

            if (!isset($_SESSION['tabs'])) {
                $_SESSION['tabs'] = [];
            }

            // Save under this tab's dedicated sub-session
            $_SESSION['tabs'][$tab_id] = [
                'auth_user' => [
                    'user_id'   => $user['user_id'],
                    'user_name' => $user['name'] ?: $user['username'],
                    'email'     => $user['email'],
                    'role_id'   => $user['role_id'],
                    'role'      => $user['role_name']
                ]
            ];

            // Target routing based on privilege
            if ($user['role_name'] === 'Administrator') {
                $target = 'admin_dashboard.php';
            } elseif ($user['role_name'] === 'Staff') {
                $target = 'staff_dashboard.php';
            } else {
                $target = 'index.php';
            }

            $dest = defined('BASE_URL') ? BASE_URL . $target : '/PetSeeker/' . $target;
            $dest .= (str_contains($dest, '?') ? '&' : '?') . 'tab_id=' . urlencode($tab_id);

            header('Location: ' . $dest);
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm border-0 mt-4">
      <div class="card-body p-4">
        <h3 class="card-title fw-bold text-center mb-1">Login</h3>
        <p class="text-muted text-center small mb-4">Users sign in with Email; Staff/Admin with Username</p>

        <?php if (!empty($info)): ?>
          <div class="alert alert-info py-2 small"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= auth_url('login.php') ?>" method="POST" data-validate novalidate>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email or Username</label>
            <input type="text" name="identifier" class="form-control" required autofocus placeholder="user@email.com or staff01" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In</button>
        </form>

        <div class="text-center mt-3 small">
          Don't have an account? <a href="<?= auth_url('register.php') ?>">Sign up as a User</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>