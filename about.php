<?php
// about.php - Ultra-Modern Art of Cooking Information System About Page with Advanced Design Features
session_start();
$backLink = isset($_SESSION['user_id']) ? 'tyu.php' : 't.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About - Art of Cooking Information System</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- AOS Library for Scroll Animations -->
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-gradient: linear-gradient(45deg, #ff6b6b, #f8e71c, #6bdfff);
      --bg-color: #e2e8f0;
      --text-color: #333;
    }
    /* Global Styles */
    body {
      background: radial-gradient(circle at top, #f3f4f6, var(--bg-color));
      font-family: 'Roboto', sans-serif;
      color: var(--text-color);
      margin: 0;
      padding: 0;
      scroll-behavior: smooth;
      position: relative;
    }
    /* Scroll Progress Indicator */
    #progress-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: rgba(0, 0, 0, 0.1);
      z-index: 9999;
    }
    #progress-bar {
      height: 5px;
      background: var(--primary-gradient);
      width: 0%;
      transition: width 0.25s ease-out;
    }
    .container {
      padding-top: 70px;
      max-width: 960px;
      margin: 0 auto;
    }
    /* Parallax Header */
    .about-header {
      position: relative;
      background: url('https://source.unsplash.com/1600x900/?cooking,food') center/cover no-repeat fixed;
      border-radius: 8px;
      margin-bottom: 0;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      height: 450px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .about-header::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1;
      transition: background 0.5s ease;
    }
    .about-header:hover::before {
      background: rgba(0, 0, 0, 0.3);
    }
    .header-content {
      position: relative;
      z-index: 2;
      text-align: center;
      animation: zoomIn 1s ease;
    }
    .about-header h1 {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 15px;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.7);
      letter-spacing: 1px;
    }
    .about-header p {
      font-size: 1.5rem;
      font-weight: 500;
      text-shadow: 1px 1px 4px rgba(0,0,0,0.7);
    }
    /* Decorative Divider */
    .divider {
      width: 100%;
      height: 100px;
      background: linear-gradient(45deg, transparent 50%, #f3f4f6 50%);
      clip-path: polygon(0 0, 100% 30%, 100% 70%, 0 100%);
      margin-bottom: -50px;
    }
    /* Cards with Enhanced Glassmorphism and Animated Gradient Borders */
    .card {
      position: relative;
      background: rgba(255, 255, 255, 0.55);
      border: none;
      border-radius: 15px;
      backdrop-filter: blur(10px);
      margin-bottom: 30px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      padding: 2px;
      overflow: hidden;
    }
    .card::before {
      content: "";
      position: absolute;
      top: -2px; left: -2px;
      right: -2px; bottom: -2px;
      background: var(--primary-gradient);
      background-size: 300% 300%;
      z-index: -1;
      border-radius: 18px;
      opacity: 0;
      transition: opacity 0.3s ease;
      animation: gradientShift 5s ease infinite;
    }
    .card:hover::before {
      opacity: 1;
    }
    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 25px rgba(0,0,0,0.2);
    }
    .card-body {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 13px;
      padding: 30px;
    }
    .section-title {
      border-bottom: 2px solid transparent;
      padding-bottom: 10px;
      margin-bottom: 20px;
      font-weight: 700;
      letter-spacing: 1px;
      position: relative;
      background-image: var(--primary-gradient);
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      animation: textGlow 2s ease infinite alternate;
    }
    @keyframes textGlow {
      from {
        text-shadow: 0 0 10px rgba(255,107,107,0.7);
      }
      to {
        text-shadow: 0 0 20px rgba(107,223,255,0.9);
      }
    }
    /* Footer */
    footer {
      margin-top: 50px;
      padding: 20px 0;
      border-top: 1px solid #ddd;
      text-align: center;
      font-size: 0.9rem;
      color: #555;
    }
    /* Back Button */
    .back-button {
      display: inline-block;
      margin-top: 20px;
      padding: 12px 25px;
      background: var(--primary-gradient);
      color: #fff;
      border: none;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 500;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .back-button i {
      margin-right: 8px;
      transition: transform 0.3s ease;
    }
    .back-button:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .back-button:hover i {
      transform: translateX(-3px);
    }
    /* Animations */
    @keyframes zoomIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    /* Responsive Enhancements */
    @media (max-width: 576px) {
      .about-header h1 {
        font-size: 2.5rem;
      }
      .about-header p {
        font-size: 1.2rem;
      }
      .card-body {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <!-- Scroll Progress Indicator -->
  <div id="progress-container">
    <div id="progress-bar"></div>
  </div>
  
  <div class="container">
    <!-- Parallax Header Section -->
    <header class="about-header" data-aos="fade-in">
      <div class="header-content">
        <h1>About Art of Cooking Information System</h1>
        <p>Where culinary passion meets innovative technology</p>
      </div>
    </header>
    
    <!-- Decorative Divider -->
    <div class="divider"></div>

    <!-- Content Cards -->
    <div class="card" data-aos="fade-up" data-aos-delay="100">
      <div class="card-body">
        <h2 class="section-title">Our Mission</h2>
        <p>
          The Art of Cooking Information System is dedicated to sharing innovative culinary techniques, recipes, and insights. We empower home cooks, enthusiasts, and professional chefs by blending traditional wisdom with modern technology.
        </p>
      </div>
    </div>

    <div class="card" data-aos="fade-up" data-aos-delay="200">
      <div class="card-body">
        <h2 class="section-title">What We Offer</h2>
        <ul>
          <li><i class="fa-solid fa-utensils"></i> Detailed cooking guides and interactive tutorials</li>
          <li><i class="fa-solid fa-lightbulb"></i> Expert advice and culinary tips</li>
          <li><i class="fa-solid fa-globe"></i> A curated collection of recipes from around the world</li>
          <li><i class="fa-solid fa-mobile-screen"></i> Responsive design for seamless use on any device</li>
        </ul>
      </div>
    </div>

    <div class="card" data-aos="fade-up" data-aos-delay="300">
      <div class="card-body">
        <h2 class="section-title">Our Story</h2>
        <p>
          Born from a passion for culinary arts and a love for innovation, our platform celebrates the timeless art of cooking. We continually evolve our offerings to ensure our community has access to both traditional recipes and modern techniques.
        </p>
      </div>
    </div>

    <!-- Back Button with session-based redirect -->
    <div class="text-center">
      <a href="<?php echo $backLink; ?>" class="back-button" title="Back to Previous Page">
        <i class="fa-solid fa-arrow-left"></i> Back
      </a>
    </div>

    <!-- Footer -->
    <footer>
      <p>&copy; <?php echo date("Y"); ?> Art of Cooking Information System. All rights reserved.</p>
    </footer>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- AOS JS for Animations -->
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script>
    AOS.init({
      duration: 800,
      once: true
    });
    // Scroll progress bar logic
    window.addEventListener('scroll', function() {
      const scrollTop = document.documentElement.scrollTop;
      const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const progress = (scrollTop / scrollHeight) * 100;
      document.getElementById('progress-bar').style.width = progress + '%';
    });
  </script>
</body>
</html>




