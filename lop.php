<?php
// Disable error reporting for production, but you may want to enable it during development
error_reporting(0);

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include Composer's autoloader
require 'vendor/autoload.php';

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "recipe_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Logout Process
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Create database and select it if not exists
$query = "CREATE DATABASE IF NOT EXISTS $dbname";
$conn->query($query);
$query = "USE $dbname";
$conn->query($query);

// Create Users Table (if not exists)
$query = "CREATE TABLE IF NOT EXISTS users3 (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    otp VARCHAR(6) NOT NULL,
    otp_expiry DATETIME NOT NULL,
    account_number VARCHAR(8) DEFAULT NULL
)";
$conn->query($query);

// Create Admin Table (if not exists)
$query = "CREATE TABLE IF NOT EXISTS admin (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    aname VARCHAR(255) NOT NULL UNIQUE,
    pass VARCHAR(255) NOT NULL
)";
$conn->query($query);

// OPTIONAL: Insert default admin record if it doesn't exist
$adminCheck = $conn->query("SELECT * FROM admin WHERE aname = 'admin'");
if ($adminCheck->num_rows == 0) {
    $defaultAdminUsername = 'admin';
    $defaultAdminPassword = password_hash('admin123', PASSWORD_DEFAULT); // change as needed
    $stmt = $conn->prepare("INSERT INTO admin (aname, pass) VALUES (?, ?)");
    $stmt->bind_param("ss", $defaultAdminUsername, $defaultAdminPassword);
    $stmt->execute();
    $stmt->close();
}

/**
 * Function to send OTP via email using PHPMailer
 *
 * @param string $email Recipient email address
 * @param string $otp One-Time Password
 * @param string|null $accountNumber (Optional) Account number to include in the email
 * @return bool Returns true if email sent, false otherwise
 */
function sendOTP($email, $otp, $accountNumber = null) {
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration (update with your own settings)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        // Use your actual email credentials here
        $mail->Username = 'mrsarvesh03082003@gmail.com';
        $mail->Password = 'gghv kasa klmm dmbz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Use the same email as the sender address
        $mail->setFrom($mail->Username, 'Art of Cooking');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Account Verification / Password Reset';
        
        $accountMessage = $accountNumber ? "Your account number is: <strong>$accountNumber</strong>.<br>" : "";
        $mail->Body = "
            <p>Hello,</p>
            <p>Your One-Time Password (OTP) for verifying your account is: <strong>$otp</strong>.</p>
            <p>This OTP is valid for the next 10 minutes. Please do not share it with anyone.</p>
            <p>Thank you for choosing the Art of Cooking Information System.</p>
            <p>Bon Appétit!<br>The Art of Cooking Team</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the detailed error message for debugging
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

$message = '';

// =======================================================
// Forgot Password Flow (action=forgot)
// =======================================================
if (isset($_GET['action']) && $_GET['action'] == 'forgot') {
    if (isset($_POST['forgot_password'])) {
        $email = $_POST['email'];
        $sql = "SELECT * FROM users3 WHERE email='$email'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $otp = rand(100000, 999999);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $conn->query("UPDATE users3 SET otp='$otp', otp_expiry='$otp_expiry' WHERE email='$email'");
            if ($conn->affected_rows > 0 && sendOTP($email, $otp)) {
                $_SESSION['fp_email'] = $email;
                header("Location: " . $_SERVER['PHP_SELF'] . "?action=forgot_verify");
                exit();
            } else {
                $message = "<div class='alert alert-danger custom-alert'>Error sending OTP. Try again later.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger custom-alert'>No account found with that email.</div>";
        }
    }
}

// =======================================================
// Forgot Password OTP Verification
// =======================================================
if (isset($_GET['action']) && $_GET['action'] == 'forgot_verify') {
    if (isset($_POST['verify_fp_otp'])) {
        $user_otp = $_POST['otp'];
        if (isset($_SESSION['fp_email'])) {
            $email = $_SESSION['fp_email'];
            $sql = "SELECT otp, otp_expiry FROM users3 WHERE email='$email'";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $current_time = date('Y-m-d H:i:s');
                if ($user_otp == $row['otp'] && $current_time <= $row['otp_expiry']) {
                    header("Location: " . $_SERVER['PHP_SELF'] . "?action=reset_password");
                    exit();
                } else {
                    $message = "<div class='alert alert-danger custom-alert'>Invalid OTP or OTP expired. Try again.</div>";
                    unset($_SESSION['fp_email']);
                }
            } else {
                $message = "<div class='alert alert-danger custom-alert'>Email not found.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger custom-alert'>Session expired. Please start over.</div>";
        }
    }
}

// =======================================================
// Reset Password for Forgot Password
// =======================================================
if (isset($_GET['action']) && $_GET['action'] == 'reset_password') {
    if (isset($_POST['reset_fp_password'])) {
        if (isset($_SESSION['fp_email'])) {
            $email = $_SESSION['fp_email'];
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $conn->query("UPDATE users3 SET password='$new_password' WHERE email='$email'");
            $message = "<div class='alert alert-success custom-alert'>Password reset successfully!</div>";
            unset($_SESSION['fp_email']);
        } else {
            $message = "<div class='alert alert-danger custom-alert'>Session expired. Please try again.</div>";
        }
    }
}

// =======================================================
// Registration Process with OTP (default view)
// =======================================================
if (!isset($_GET['action']) && isset($_POST['register'])) {
    $username = $_POST['username'];
    $plain_password = $_POST['password'];  // Plain password input
    $email = $_POST['email'];

    // Check if the username or email already exists
    $checkQuery = "SELECT * FROM users3 WHERE username = '$username' OR email = '$email'";
    $checkResult = $conn->query($checkQuery);
    if ($checkResult->num_rows > 0) {
        $message = "<div class='alert alert-danger custom-alert'>Username or Email already exists. Please choose a different one.</div>";
    } else {
        // Proceed with registration if no duplicate is found
        $password = password_hash($plain_password, PASSWORD_DEFAULT);
        $otp = rand(100000, 999999);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $accountNumber = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = $otp_expiry;
        $_SESSION['temp_user'] = [
            'username'       => $username,
            'password'       => $password,
            'email'          => $email,
            'account_number' => $accountNumber,
            'otp'            => $otp,
            'otp_expiry'     => $otp_expiry
        ];
        
        if (sendOTP($email, $otp, $accountNumber)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "<div class='alert alert-danger custom-alert'>Error sending OTP. Try again later.</div>";
        }
    }
}

// =======================================================
// OTP Verification for Registration
// =======================================================
if (!isset($_GET['action']) && isset($_POST['verify_otp'])) {
    if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry'])) {
        $user_otp = $_POST['otp'];
        $current_time = date('Y-m-d H:i:s');
        if ($user_otp == $_SESSION['otp'] && $current_time <= $_SESSION['otp_expiry']) {
            $temp_user = $_SESSION['temp_user'];
            $sql = "INSERT INTO users3 (username, password, email, otp, otp_expiry, account_number) 
                    VALUES ('{$temp_user['username']}', '{$temp_user['password']}', '{$temp_user['email']}', 
                    '{$temp_user['otp']}', '{$temp_user['otp_expiry']}', '{$temp_user['account_number']}')";
            if ($conn->query($sql) === TRUE) {
                $message = "<div class='alert alert-success custom-alert'>Account verified and created successfully!</div>";
                unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user']);
            } else {
                $message = "<div class='alert alert-danger custom-alert'>Error: " . $conn->error . "</div>";
            }
        } else {
            $message = "<div class='alert alert-danger custom-alert'>Invalid OTP or OTP expired. Please retry registration.</div>";
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user']);
        }
    } else {
        $message = "<div class='alert alert-danger custom-alert'>OTP session data not found. Please start registration again.</div>";
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user']);
    }
}

// =======================================================
// Login Process (default view)
// =======================================================
if (!isset($_GET['action']) && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // First check in the admin table
    $stmtAdmin = $conn->prepare("SELECT * FROM admin WHERE aname = ?");
    $stmtAdmin->bind_param("s", $username);
    $stmtAdmin->execute();
    $resultAdmin = $stmtAdmin->get_result();
    
    if ($resultAdmin && $resultAdmin->num_rows > 0) {
        $admin = $resultAdmin->fetch_assoc();
        if (password_verify($password, $admin['pass'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['aname'];
            $message = "<div class='alert alert-success custom-alert'>Welcome Admin! Redirecting...</div>";
            echo $message;
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'admin_panel.php';
                }, 3000);
            </script>";
            exit();
        } else {
            $message = "<div class='alert alert-danger custom-alert'>Invalid admin password. Please try again.</div>";
        }
    } else {
        // Fallback to regular user login from users3 table
        $sql = "SELECT * FROM users3 WHERE username = '$username'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['account_number'] = $user['account_number'];
                
                $message = "<div class='alert alert-success custom-alert'>Welcome, " . $_SESSION['username'] . "! Redirecting...</div>";
                echo $message;
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'tyu.php';
                    }, 3000);
                </script>";
                exit();
            } else {
                $message = "<div class='alert alert-danger custom-alert'>Invalid password. Please try again.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger custom-alert'>No account found with that username. Please register.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Art of Cooking Information System</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cookie&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
      body {
          font-family: 'Poppins', sans-serif;
          background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('images/p.jpg') no-repeat center center fixed;
          background-size: cover;
          margin: 0;
          padding: 0;
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 100vh;
      }
      .card {
          background: rgba(255, 255, 255, 0.15);
          backdrop-filter: blur(15px);
          border: none;
          border-radius: 20px;
          box-shadow: 0 20px 40px rgba(0,0,0,0.2);
          animation: fadeInUp 0.8s ease-out;
          width: 100%;
          max-width: 420px;
          padding: 2rem;
      }
      @keyframes fadeInUp {
          from {
              opacity: 0;
              transform: translateY(40px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }
      h1 {
          font-family: 'Cookie', cursive;
          font-size: 3rem;
          color: #ffcb74;
          margin-bottom: 1rem;
      }
      h2 {
          color: #fff;
      }
      .form-control {
          background: rgba(255, 255, 255, 0.85);
          border: none;
          border-radius: 10px;
          transition: all 0.3s ease;
      }
      .form-control:focus {
          box-shadow: 0 0 8px rgba(211, 84, 0, 0.6);
          background: #fff;
      }
      .btn-primary {
          background-color: #d35400;
          border: none;
          border-radius: 10px;
          transition: background-color 0.3s ease, transform 0.2s;
      }
      .btn-primary:hover {
          background-color: #e67e22;
          transform: translateY(-2px);
      }
      .btn-logout, .btn-cancel {
          border-radius: 10px;
          transition: background-color 0.3s ease, transform 0.2s;
      }
      .btn-logout {
          background-color: #c0392b;
          color: #fff;
      }
      .btn-logout:hover {
          background-color: #e74c3c;
          transform: translateY(-2px);
      }
      .btn-cancel {
          background-color: #7f8c8d;
          color: #fff;
      }
      .btn-cancel:hover {
          background-color: #95a5a6;
          transform: translateY(-2px);
      }
      a {
          text-decoration: none;
          color: #ffcb74;
      }
      a:hover {
          text-decoration: underline;
      }
      .alert {
          border-radius: 10px;
          margin-top: 1rem;
          font-size: 1rem;
          transition: opacity 0.5s ease;
      }
      .custom-alert {
          padding: 0.75rem 1.25rem;
      }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="card mx-auto">
          <div class="card-body">
            <h1 class="text-center">Art of Cooking</h1>
            <?php
              if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
                  // Admin already logged in
                  if ($conn->query("SELECT * FROM admin WHERE id = '" . $_SESSION['user_id'] . "'")->num_rows > 0) {
                    echo "<h2 class='h6 text-center mb-3'>Are you sure you want to exit, " . $_SESSION['username'] . "?</h2>
                    <div class='d-flex justify-content-center'>
                        <a class='btn btn-logout me-2' href='?logout'>Logout</a>
                        <a class='btn btn-cancel' href='tyu.php'>Cancel</a>
                    </div>";
                  } else {
                      echo "<h2 class='h6 text-center mb-3'>Are you sure you want to exit, " . $_SESSION['username'] . "?</h2>
                            <div class='d-flex justify-content-center'>
                                <a class='btn btn-logout me-2' href='?logout'>Logout</a>
                                <a class='btn btn-cancel' href='tyu.php'>Cancel</a>
                            </div>";
                  }
              } elseif (isset($_GET['action']) && $_GET['action'] == 'forgot') {
                  ?>
                  <h2 class="h6 text-center mb-3">Forgot Password</h2>
                  <form method="POST" action="?action=forgot">
                    <div class="mb-3">
                      <label for="email" class="form-label">Enter your email</label>
                      <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="d-grid">
                      <input type="submit" name="forgot_password" class="btn btn-primary" value="Send OTP">
                    </div>
                  </form>
                  <div class="mt-3 text-center">
                    <a href="lop.php">Back to Login</a>
                  </div>
                  <?php
              } elseif (isset($_GET['action']) && $_GET['action'] == 'forgot_verify') {
                  ?>
                  <h2 class="h6 text-center mb-3">Verify OTP</h2>
                  <form method="POST" action="?action=forgot_verify">
                    <div class="mb-3">
                      <label for="otp" class="form-label">Enter OTP</label>
                      <input type="text" name="otp" id="otp" class="form-control" placeholder="OTP" required>
                    </div>
                    <div class="d-grid">
                      <input type="submit" name="verify_fp_otp" class="btn btn-primary" value="Verify OTP">
                    </div>
                  </form>
                  <div class="mt-3 text-center">
                    <a href="lop.php">Back to Login</a>
                  </div>
                  <?php
              } elseif (isset($_GET['action']) && $_GET['action'] == 'reset_password') {
                  ?>
                  <h2 class="h6 text-center mb-3">Reset Password</h2>
                  <form method="POST" action="?action=reset_password">
                    <div class="mb-3">
                      <label for="new_password" class="form-label">Enter New Password</label>
                      <input type="password" name="new_password" id="new_password" class="form-control" placeholder="New Password" required>
                    </div>
                    <div class="d-grid">
                      <input type="submit" name="reset_fp_password" class="btn btn-primary" value="Reset Password">
                    </div>
                  </form>
                  <div class="mt-3 text-center">
                    <a href="lop.php">Back to Login</a>
                  </div>
                  <?php
              } else {
                  if (isset($_SESSION['otp'])) {
                      ?>
                      <h2 class="h6 text-center mb-3">Verify OTP</h2>
                      <form method="POST">
                        <div class="mb-3">
                          <label for="otp" class="form-label">Enter OTP</label>
                          <input type="text" name="otp" id="otp" class="form-control" placeholder="OTP" required>
                        </div>
                        <div class="d-grid">
                          <input type="submit" name="verify_otp" class="btn btn-primary" value="Verify OTP">
                        </div>
                      </form>
                      <div class="mt-3 text-center">
                        <a href="lop.php">Back to Login</a>
                      </div>
                      <?php
                  } elseif (isset($_GET['register']) && $_GET['register'] == 'true') {
                      ?>
                      <h2 class="h6 text-center mb-3">Register</h2>
                      <form method="POST">
                        <div class="mb-3">
                          <label for="username" class="form-label">Username</label>
                          <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
                        </div>
                        <div class="mb-3">
                          <label for="password" class="form-label">Password</label>
                          <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        </div>
                        <div class="mb-3">
                          <label for="email" class="form-label">Email</label>
                          <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
                        </div>
                        <div class="d-grid">
                          <input type="submit" name="register" class="btn btn-primary" value="Register">
                        </div>
                      </form>
                      <div class="mt-3 text-center">
                        <a href="lop.php">Back to Login</a>
                      </div>
                      <?php
                  } else {
                      ?>
                      <h2 class="h6 text-center mb-3">Login</h2>
                      <form method="POST">
                        <div class="mb-3">
                          <label for="username" class="form-label">Username</label>
                          <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
                        </div>
                        <div class="mb-3">
                          <label for="password" class="form-label">Password</label>
                          <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        </div>
                        <div class="d-grid">
                          <input type="submit" name="login" class="btn btn-primary" value="Login">
                        </div>
                      </form>
                      <div class="mt-3 text-center">
                        <p>Don't have an account? <a href="?register=true">Register</a></p>
                        <p><a href="?action=forgot">Forgot Password?</a></p>
                      </div>
                      <?php
                  }
              }
              echo $message;
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.addEventListener('DOMContentLoaded', (event) => {
      const alertElem = document.querySelector('.alert');
      if (alertElem) {
        setTimeout(() => {
          alertElem.style.opacity = '0';
          setTimeout(() => alertElem.remove(), 500);
        }, 5000);
      }
    });
  </script>
</body>
</html>

<?php $conn->close(); ?>
