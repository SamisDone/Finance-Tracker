<?php 
require_once 'includes/db.php';
require_once 'includes/auth_functions.php'; // We'll create this file next for auth logic

$view = $_GET['view'] ?? 'main'; // Default view: main landing, or login/register
$pageTitle = 'Welcome to Finance Tracker';

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $result = registerUser($pdo, $username, $email, $password, $confirm_password);
        if ($result === true) {
            $_SESSION['flash_message'] = 'Registration successful! Please login.';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: index.php?view=login');
            exit;
        } else {
            $message = $result;
            $message_type = 'error';
        }
    } elseif (isset($_POST['login'])) {
        $username_or_email = trim($_POST['username_or_email']);
        $password = $_POST['password'];

        $user = loginUser($pdo, $username_or_email, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['flash_message'] = 'Login successful! Welcome back, ' . htmlspecialchars($user['username']) . '.';
            $_SESSION['flash_message_type'] = 'success';
            header('Location: dashboard.php'); // Redirect to dashboard after login
            exit;
        } else {
            $message = 'Invalid username/email or password.';
            $message_type = 'error';
        }
    }
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && $view !== 'logout') {
    if ($view === 'main' || $view === 'login' || $view === 'register') {
        header('Location: dashboard.php');
        exit;
    }
}

include 'includes/header.php'; 
?>

<?php if ($message): ?>
    <div class="flash-message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($view === 'main'): ?>
<section class="hero landing-page">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="heroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2563eb"/>
                    <stop offset="100%" stop-color="#14b8a6"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#heroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h1 class="hero-title">
            Welcome to <span>Your Personal Finance Tracker</span>
        </h1>
        <p class="hero-desc">
            Take control of your finances, track your spending, and achieve your financial goals with ease.
        </p>
        <div class="hero-illustration mb-4">
            <!-- Beautiful SVG finance illustration -->
            <svg width="340" height="180" viewBox="0 0 340 180" fill="none" xmlns="http://www.w3.org/2000/svg" class="svg-float">
                <ellipse cx="170" cy="160" rx="120" ry="18" fill="#14b8a655"/>
                <rect x="80" y="60" width="180" height="70" rx="18" fill="#232b3b" stroke="#2563eb" stroke-width="3"/>
                <rect x="110" y="90" width="50" height="25" rx="7" fill="#2563eb"/>
                <rect x="170" y="90" width="60" height="25" rx="7" fill="#14b8a6"/>
                <circle cx="135" cy="102" r="7" fill="#f59e42"/>
                <circle cx="200" cy="102" r="7" fill="#f59e42"/>
                <rect x="130" y="70" width="80" height="10" rx="4" fill="#30384e"/>
                <rect x="120" y="120" width="100" height="6" rx="3" fill="#f59e42"/>
                <g>
                    <ellipse cx="265" cy="85" rx="10" ry="10" fill="#22c55e"/>
                    <path d="M265,80 L265,90 M260,85 L270,85" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                </g>
                <g>
                    <ellipse cx="95" cy="85" rx="10" ry="10" fill="#ef4444"/>
                    <path d="M95,80 L95,90 M90,85 L100,85" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                </g>
            </svg>
        </div>
        <p class="mb-3">Ready to get started?</p>
        <a href="index.php?view=register" class="btn btn-primary me-2">Sign Up Now</a>
        <a href="index.php?view=login" class="btn btn-secondary">Login</a>
    </div>
</section>

<!-- Key Features Section -->
<section class="features-section text-center py-5">
    <div class="container">
        <h2 class="section-title text-center mb-5">Why Choose Our Finance Tracker?</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <i class="fas fa-chart-pie fa-3x mb-3 text-primary"></i>
                    <h4 class="feature-title">Comprehensive Tracking</h4>
                    <p class="feature-desc">Monitor income, expenses, budgets, and savings goals all in one place.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <i class="fas fa-bullseye fa-3x mb-3 text-success"></i>
                    <h4 class="feature-title">Goal Oriented</h4>
                    <p class="feature-desc">Set and track financial goals, with visual progress to keep you motivated.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <i class="fas fa-shield-alt fa-3x mb-3 text-info"></i>
                    <h4 class="feature-title">Secure & Private</h4>
                    <p class="feature-desc">Your financial data is kept safe and private with robust security measures.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- User Testimonials Section -->
<section class="testimonials-section bg-light-subtle py-5">
    <div class="container">
        <h2 class="section-title text-center mb-5">What Our Users Say</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <p class="testimonial-text fst-italic">"This tracker has revolutionized how I manage my money. It's so easy to use and has helped me save more than ever before!"</p>
                        <div class="testimonial-author d-flex align-items-center mt-3">
                            <img src="assets/images/avatars/avatar1.png" alt="Sarah W. Avatar" class="testimonial-avatar rounded-circle me-3" width="60" height="60">
                            <div>
                                <p class="testimonial-name fw-bold mb-0">Sarah W.</p>
                                <p class="testimonial-role text-muted mb-0">Freelance Designer</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <p class="testimonial-text fst-italic">"I love the budgeting tools and the visual reports. Finally, I feel in control of my finances. Highly recommended!"</p>
                        <div class="testimonial-author d-flex align-items-center mt-3">
                            <img src="assets/images/avatars/avatar2.png" alt="John B. Avatar" class="testimonial-avatar rounded-circle me-3" width="60" height="60">
                            <div>
                                <p class="testimonial-name fw-bold mb-0">John B.</p>
                                <p class="testimonial-role text-muted mb-0">Software Engineer</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <p class="testimonial-text fst-italic">"The goal-setting feature is fantastic! I've managed to save for a down payment on my car thanks to this app."</p>
                        <div class="testimonial-author d-flex align-items-center mt-3">
                            <img src="assets/images/avatars/avatar3.png" alt="Maria G. Avatar" class="testimonial-avatar rounded-circle me-3" width="60" height="60">
                            <div>
                                <p class="testimonial-name fw-bold mb-0">Maria G.</p>
                                <p class="testimonial-role text-muted mb-0">Marketing Manager</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php elseif ($view === 'login'): ?>
<?php $pageTitle = 'Login'; ?>
<section class="hero login-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="loginHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2563eb"/>
                    <stop offset="100%" stop-color="#14b8a6"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#loginHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="fa-solid fa-right-to-bracket"></i> Login
        </h2>
    </div>
</section>
<div class="form-container card form-container-sm mb-4">
    <form action="index.php?view=login" method="POST" autocomplete="on">
        <div class="form-group">
            <label for="username_or_email"><i class="fa-solid fa-user"></i> Username or Email:</label>
            <input type="text" id="username_or_email" name="username_or_email" required autocomplete="username">
        </div>
        <div class="form-group">
            <label for="password"><i class="fa-solid fa-lock"></i> Password:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" name="login" class="btn btn-primary btn-full-width"><i class="fa-solid fa-arrow-right"></i> Login</button>
    </form>
    <div class="auth-links">
        <p>Don't have an account? <a href="index.php?view=register">Register here</a></p>
    </div>
</div>

<?php elseif ($view === 'register'): ?>
<?php $pageTitle = 'Register'; ?>
<section class="hero register-hero">
    <div class="hero-bg-anim" aria-hidden="true">
        <svg width="100%" height="100%" viewBox="0 0 1440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="registerHeroGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2563eb"/>
                    <stop offset="100%" stop-color="#f59e42"/>
                </linearGradient>
            </defs>
            <path d="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z" fill="url(#registerHeroGradient)">
                <animate attributeName="d" dur="8s" repeatCount="indefinite" values="M0,200 Q400,350 900,150 T1440,200 V400 H0 Z;M0,220 Q400,170 900,270 T1440,220 V400 H0 Z;M0,200 Q400,350 900,150 T1440,200 V400 H0 Z"/>
            </path>
        </svg>
    </div>
    <div class="hero-content">
        <h2 class="hero-title">
            <i class="fa-solid fa-user-plus"></i> Register
        </h2>
    </div>
</section>
<div class="form-container card form-container-sm mb-4">
    <form action="index.php?view=register" method="POST" autocomplete="on">
        <div class="form-group">
            <label for="username"><i class="fa-solid fa-user"></i> Username:</label>
            <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" autocomplete="username">
        </div>
        <div class="form-group">
            <label for="email"><i class="fa-solid fa-envelope"></i> Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" autocomplete="email">
        </div>
        <div class="form-group">
            <label for="password"><i class="fa-solid fa-lock"></i> Password:</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm_password"><i class="fa-solid fa-lock"></i> Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
        </div>
        <button type="submit" name="register" class="btn btn-primary btn-full-width"><i class="fa-solid fa-user-plus"></i> Register</button>
    </form>
    <div class="auth-links">
        <p>Already have an account? <a href="index.php?view=login">Login here</a></p>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
