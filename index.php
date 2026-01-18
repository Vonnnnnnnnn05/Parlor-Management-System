<?php
require_once 'conn.php';

// Get active services
$services_query = "SELECT * FROM services WHERE is_active = 1 ORDER BY service_name ASC";
$services_result = mysqli_query($conn, $services_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shiena Belmes Beauty Parlor | Parlor Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #8a5a7a;
            --primary-light: #f9f0f6;
            --secondary: #f5b3cd;
            --accent: #d4a5b5;
            --dark: #3a2c34;
            --light: #fff;
            --gray: #f8f9fa;
            --success: #7bc8a5;
        }

        body {
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header & Navigation */
        header {
            background-color: var(--light);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .logo i {
            color: var(--secondary);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .cta-button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .cta-button:hover {
            background-color: var(--dark);
            transform: translateY(-2px);
        }

        .mobile-menu-btn {
            display: none;
            font-size: 1.5rem;
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            padding: 150px 0 100px;
            background: linear-gradient(135deg, var(--primary-light) 0%, #fff 100%);
            overflow: hidden;
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
        }

        .hero-text {
            flex: 1;
        }

        .hero-text h1 {
            font-size: 3.2rem;
            color: var(--dark);
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero-text h1 span {
            color: var(--primary);
        }

        .hero-text p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
            max-width: 600px;
        }

        .hero-image {
            flex: 1;
            text-align: center;
        }

        .hero-image img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(138, 90, 122, 0.2);
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: var(--gray);
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .section-title p {
            color: #777;
            max-width: 700px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background-color: white;
            padding: 40px 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            background-color: var(--primary-light);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: var(--primary);
            font-size: 1.8rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .feature-card p {
            color: #666;
        }

        /* Services Section */
        .services-section {
            padding: 100px 0;
            background-color: white;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .service-card {
            background: white;
            border: 2px solid var(--primary-light);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(138, 90, 122, 0.2);
            border-color: var(--primary);
        }

        .service-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
            color: white;
        }

        .service-card h3 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .service-description {
            color: #666;
            margin-bottom: 20px;
            min-height: 60px;
        }

        .service-details {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding-top: 20px;
            border-top: 2px solid var(--primary-light);
        }

        .service-price,
        .service-duration {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--primary);
        }

        .service-price i,
        .service-duration i {
            color: var(--secondary);
        }

        /* How It Works */
        .how-it-works {
            padding: 100px 0;
            background-color: var(--gray);
        }

        .steps {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 40px;
            margin-top: 50px;
        }

        .step {
            flex: 1;
            min-width: 250px;
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 25px;
        }

        .step h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .step p {
            color: #666;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 30px;
            right: -20px;
            width: 40px;
            height: 2px;
            background-color: var(--accent);
        }

        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--dark) 100%);
            color: white;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }

        .cta-button.light {
            background-color: white;
            color: var(--primary);
        }

        .cta-button.light:hover {
            background-color: var(--primary-light);
        }

        /* Login Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1200;
        }

        .modal-overlay.is-open {
            display: flex;
        }

        .modal {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            padding: 30px;
            position: relative;
        }

        .modal h3 {
            font-size: 1.6rem;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .modal p {
            color: #666;
            margin-bottom: 20px;
        }

        .modal label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .modal input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 1rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }

        .modal-close {
            background: transparent;
            border: 1px solid #ddd;
            color: #555;
            padding: 10px 18px;
            border-radius: 30px;
            cursor: pointer;
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 70px 0 30px;
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 40px;
            margin-bottom: 50px;
        }

        .footer-column {
            flex: 1;
            min-width: 250px;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 25px;
            color: var(--secondary);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--secondary);
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #aaa;
            font-size: 0.9rem;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            
        }
            .mobile-menu-toggle {
                display: block;
            }

            .navbar {
                padding: 15px 0;
            }

            .logo {
                font-size: 1.4rem;
            
            .hero-content {
                flex-direction: column;
            }
            
            .hero-text h1 {
                font-size: 2.8rem;
            }
            
            .step:not(:last-child)::after {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .hero {
                padding: 130px 0 80px;
            }
            
            .hero-text h1 {
                font-size: 2.3rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .cta-section h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .hero-text h1 {
                font-size: 2rem;
            }
            
            .features, .how-it-works, .cta-section {
                padding: 70px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header & Navigation -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="#" class="logo">
                    <i class="fas fa-spa"></i>
                    Shiena Belmes Beauty Parlor
                </a>
                
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="nav-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
                
                <button type="button" class="cta-button" data-open-login>Login</button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Streamline Your <span>Parlor</span> Management</h1>
                    <p>A comprehensive native PHP solution to centralize service management, process multi-service transactions, monitor daily income against quotas, and generate insightful reports for data-driven business decisions.</p>
                    <a href="#features" class="cta-button">Explore Features</a>
                </div>
                <div class="hero-image">
                    <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Parlor Management Dashboard">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Powerful Features</h2>
                <p>Our native PHP system is designed to handle all aspects of modern parlor management</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h3>Service Management</h3>
                    <p>Efficiently organize and manage all parlor services with pricing, duration, and staff assignments in one centralized system.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <h3>Transaction Processing</h3>
                    <p>Record multi-service transactions seamlessly with support for various payment methods and automatic receipt generation.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Income Monitoring</h3>
                    <p>Track daily sales performance against customizable income quotas with real-time alerts and performance dashboards.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Reporting & Analytics</h3>
                    <p>Generate comprehensive reports on revenue, popular services, staff performance, and customer trends for informed decision-making.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="services">
        <div class="container">
            <div class="section-title">
                <h2>Our Services</h2>
                <p>Explore our range of professional beauty and parlor services</p>
            </div>
            
            <div class="services-grid">
                <?php while ($service = mysqli_fetch_assoc($services_result)): ?>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                    <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                    <div class="service-details">
                        <div class="service-price">
                            <i class="fas fa-tag"></i>
                            <span>₱<?php echo number_format($service['price'], 2); ?></span>
                        </div>
                        <div class="service-duration">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $service['duration_minutes']; ?> min</span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Simple implementation process to get your parlor management system up and running quickly</p>
            </div>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>System Setup</h3>
                    <p>Install the native PHP application on your server and configure basic business settings, services, and staff information.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Daily Operations</h3>
                    <p>Use the intuitive interface to manage appointments, process transactions, and track real-time business performance.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Monitor & Analyze</h3>
                    <p>Review daily income against quotas, generate performance reports, and make data-driven decisions to grow your business.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Manage Your Parlor?</h2>
            <p>Sign in to access your dashboard, manage services, and track daily income with real-time reporting.</p>
            <button type="button" class="cta-button light" data-open-login>Login to Your Account</button>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal-overlay" id="login-modal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="login-title">
            <h3 id="login-title">Login</h3>
            <p>Enter your credentials to continue.</p>
            <form action="auth.php" method="post">
                <label for="login-username">Username</label>
                <input type="text" id="login-username" name="username" required>

                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" required>

                <div class="modal-actions">
                    <button type="button" class="modal-close" data-close-login>Cancel</button>
                    <button type="submit" class="cta-button">Login</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Shiena Belmes Beauty Parlor</h3>
                    <p>A powerful native PHP parlor management system designed to help beauty businesses thrive through efficient operations and data-driven insights.</p>
                </div>
                
              
                
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> test@gmail.com</li>
                        <li><i class="fas fa-phone"></i> +63 9123456789</li>
                        <li><i class="fas fa-map-marker-alt"></i> Tacurong City, Sultan Kudarat</li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2023 Shiena Belmes Beauty Parlor Management System. All rights reserved. | Developed with pure native PHP</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Check for notifications
        const urlParams = new URLSearchParams(window.location.search);
        
        // Login error
        if (urlParams.get('error') === '1') {
            Toastify({
                text: "❌ Invalid username or password!",
                duration: 4000,
                gravity: "top",
                position: "right",
                backgroundColor: "#ffffff",
                className: "toast-error",
                stopOnFocus: true,
                style: {
                    background: "#ffffff",
                    color: "#dc2626",
                    border: "2px solid #dc2626",
                    borderRadius: "12px",
                    boxShadow: "0 8px 24px rgba(220, 38, 38, 0.25)",
                    fontWeight: "600",
                    fontSize: "15px",
                    padding: "16px 24px"
                }
            }).showToast();
        }
        
        // Logout success
        if (urlParams.get('logout') === 'success') {
            Toastify({
                text: "Logged out successfully!",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#ffffff",
                stopOnFocus: true,
                style: {
                    background: "#ffffff",
                    color: "#1f2937",
                    border: "2px solid #6366f1",
                    borderRadius: "12px",
                    boxShadow: "0 8px 24px rgba(99, 102, 241, 0.25)",
                    fontWeight: "600",
                    fontSize: "15px",
                    padding: "16px 24px"
                }
            }).showToast();
        }

        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                if (this.hasAttribute('data-open-login')) {
                    return;
                }

                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    if(window.innerWidth <= 768) {
                        document.querySelector('.nav-links').style.display = 'none';
                    }
                }
            });
        });

        // Login modal behavior
        const loginModal = document.getElementById('login-modal');
        const openLoginButtons = document.querySelectorAll('[data-open-login]');
        const closeLoginButtons = document.querySelectorAll('[data-close-login]');

        const openLoginModal = () => {
            loginModal.classList.add('is-open');
            loginModal.setAttribute('aria-hidden', 'false');
        };

        const closeLoginModal = () => {
            loginModal.classList.remove('is-open');
            loginModal.setAttribute('aria-hidden', 'true');
        };

        openLoginButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                openLoginModal();
            });
        });

        closeLoginButtons.forEach(button => {
            button.addEventListener('click', function() {
                closeLoginModal();
            });
        });

        loginModal.addEventListener('click', function(e) {
            if (e.target === loginModal) {
                closeLoginModal();
            }
        });
        
        // Change navbar style on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if(window.scrollY > 100) {
                header.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.1)';
            } else {
                header.style.boxShadow = '0 2px 15px rgba(0, 0, 0, 0.1)';
            }
        });
    </script>
</body>
</html>
 