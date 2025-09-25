<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillSwap - Peer-to-Peer Skill Exchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --gradient-dark: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark);
            line-height: 1.6;
        }

        .navbar {
            padding: 1rem 0;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary);
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark);
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }

        .nav-link:hover {
            color: var(--primary);
            transform: translateY(-2px);
        }

        .hero-section {
            background: var(--gradient-primary), 
                        url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80') center/cover;
            background-blend-mode: overlay;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            font-weight: 300;
        }

        .btn-hero {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        .btn-outline-light:hover {
            background: white;
            color: var(--primary);
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .section-subtitle {
            color: var(--secondary);
            font-size: 1.2rem;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }

        .skill-badge {
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            font-weight: 500;
            margin: 0.3rem;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .skill-badge:hover {
            transform: scale(1.05);
        }

        .community-stats {
            background: var(--gradient-dark);
            color: white;
            border-radius: 20px;
            padding: 3rem;
            margin: 4rem 0;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: white;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }

        .footer {
            background: var(--gradient-dark);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-brand {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            margin-bottom: 0.5rem;
        }

        .footer-link:hover {
            color: white;
            transform: translateX(5px);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
            margin-top: 3rem;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Image styling */
        .section-image {
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            max-width: 100%;
            height: auto;
        }

        .section-image:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
        }

        .image-placeholder {
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            color: var(--secondary);
            font-weight: 500;
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 2rem;
            }
            
            .community-stats {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-exchange-alt me-2"></i>
                SkillSwap
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#community">Community</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#skills">Skills</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white px-3" href="pages/register.php">
                            Get Started
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6 hero-content animate">
                    <h1 class="hero-title">Swap Skills, Build Connections</h1>
                    <p class="hero-subtitle">Share what you know, learn what you don't. Connect with like-minded individuals and grow together in a community built on knowledge exchange.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="pages/register.php" class="btn btn-hero btn-primary">Start Learning</a>
                        <a href="#how-it-works" class="btn btn-hero btn-outline-light">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center animate" style="animation-delay: 0.2s;">
                    <div class="image-container">
                        <img src="https://images.unsplash.com/photo-1571260899304-425eee4c7efc?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" 
                             alt="People learning and sharing skills together" 
                             class="section-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="image-placeholder" style="display: none;">
                            <i class="fas fa-users fa-3x me-3"></i>
                            <span>Skill Sharing Community</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5 animate">
                <h2 class="section-title">How SkillSwap Works</h2>
                <p class="section-subtitle">Simple steps to start your skill exchange journey</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4 animate" style="animation-delay: 0.1s;">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4>1. Create Your Profile</h4>
                        <p class="text-muted">Sign up and list the skills you can teach and the skills you want to learn. Build a comprehensive profile that showcases your expertise.</p>
                    </div>
                </div>
                <div class="col-md-4 animate" style="animation-delay: 0.2s;">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4>2. Find Perfect Matches</h4>
                        <p class="text-muted">Our smart matching algorithm connects you with people who have the skills you need and want the skills you offer.</p>
                    </div>
                </div>
                <div class="col-md-4 animate" style="animation-delay: 0.3s;">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4>3. Connect & Exchange</h4>
                        <p class="text-muted">Chat with your matches, schedule sessions, and start exchanging knowledge. Learn and teach in a supportive community.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Community Stats -->
    <section id="community" class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5 animate">
                <h2 class="section-title">Join Our Growing Community</h2>
                <p class="section-subtitle">Thousands of learners and teachers are already exchanging skills</p>
            </div>
            
            <div class="community-stats animate">
                <div class="row text-center">
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">5,000+</div>
                        <div class="stat-label">Active Members</div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">15,000+</div>
                        <div class="stat-label">Skills Exchanged</div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">4.8/5</div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-lg-6 animate" style="animation-delay: 0.1s;">
                    <h3 class="mb-4">Why Our Community Loves SkillSwap</h3>
                    <div class="d-flex align-items-start mb-3">
                        <div class="feature-icon" style="width: 50px; height: 50px; margin-right: 1rem;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h5>Diverse Community</h5>
                            <p class="text-muted">Connect with people from various backgrounds and expertise levels.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <div class="feature-icon" style="width: 50px; height: 50px; margin-right: 1rem;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h5>Safe Environment</h5>
                            <p class="text-muted">Verified profiles and secure messaging ensure a safe learning environment.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <div class="feature-icon" style="width: 50px; height: 50px; margin-right: 1rem;">
                            <i class="fas fa-star"></i>
                        </div>
                        <div>
                            <h5>Quality Exchanges</h5>
                            <p class="text-muted">Rating system helps maintain high-quality teaching and learning experiences.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 animate" style="animation-delay: 0.2s;">
                    <div class="image-container">
                        <img src="https://images.unsplash.com/photo-1521737852567-6949f3f9f2b5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" 
                             alt="Community of people learning together" 
                             class="section-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="image-placeholder" style="display: none;">
                            <i class="fas fa-user-friends fa-3x me-3"></i>
                            <span>Learning Community</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Skills -->
    <section id="skills" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5 animate">
                <h2 class="section-title">Popular Skills on SkillSwap</h2>
                <p class="section-subtitle">Discover the most sought-after skills in our community</p>
            </div>
            
            <div class="row mb-5">
                <div class="col-12 animate" style="animation-delay: 0.1s;">
                    <div class="image-container text-center">
                        <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" 
                             alt="Various skills being taught and learned" 
                             class="section-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="image-placeholder" style="display: none;">
                            <i class="fas fa-graduation-cap fa-3x me-3"></i>
                            <span>Skills Marketplace</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4 animate" style="animation-delay: 0.2s;">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Most Taught Skills</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap">
                                <span class="skill-badge">Web Development</span>
                                <span class="skill-badge">Graphic Design</span>
                                <span class="skill-badge">Photography</span>
                                <span class="skill-badge">Cooking</span>
                                <span class="skill-badge">Language Tutoring</span>
                                <span class="skill-badge">Music Lessons</span>
                                <span class="skill-badge">Yoga & Meditation</span>
                                <span class="skill-badge">Digital Marketing</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4 animate" style="animation-delay: 0.3s;">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Most Wanted Skills</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap">
                                <span class="skill-badge">Data Science</span>
                                <span class="skill-badge">UI/UX Design</span>
                                <span class="skill-badge">Content Writing</span>
                                <span class="skill-badge">Public Speaking</span>
                                <span class="skill-badge">App Development</span>
                                <span class="skill-badge">SEO Optimization</span>
                                <span class="skill-badge">Financial Planning</span>
                                <span class="skill-badge">Video Editing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5 animate" style="animation-delay: 0.4s;">
                <h4 class="mb-3">Can't find your skill? Don't worry!</h4>
                <p class="text-muted mb-4">Our community is constantly growing with new skills being added every day.</p>
                <a href="pages/register.php" class="btn btn-primary btn-lg">Join and Add Your Skills</a>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="py-5" style="background: var(--gradient-primary);">
        <div class="container py-5 text-center text-white">
            <h2 class="display-4 fw-bold mb-3 animate">Ready to Start Your Skill Journey?</h2>
            <p class="lead mb-4 animate" style="animation-delay: 0.1s;">Join thousands of learners and teachers who are already transforming their lives through skill exchange.</p>
            <div class="animate" style="animation-delay: 0.2s;">
                <a href="pages/register.php" class="btn btn-light btn-lg px-5 me-3">Get Started Free</a>
                <a href="#how-it-works" class="btn btn-outline-light btn-lg px-5">Learn How It Works</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h3 class="footer-brand">
                        <i class="fas fa-exchange-alt me-2"></i>
                        SkillSwap
                    </h3>
                    <p class="text-light">Connecting people through knowledge exchange. Learn, teach, and grow together in a supportive community.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-white mb-3">Quick Links</h5>
                    <a href="#home" class="footer-link">Home</a>
                    <a href="#how-it-works" class="footer-link">How It Works</a>
                    <a href="#community" class="footer-link">Community</a>
                    <a href="#skills" class="footer-link">Popular Skills</a>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-white mb-3">Resources</h5>
                    <a href="#" class="footer-link">Blog</a>
                    <a href="#" class="footer-link">Tutorials</a>
                    <a href="#" class="footer-link">Success Stories</a>
                    <a href="#" class="footer-link">FAQ</a>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-white mb-3">Support</h5>
                    <a href="#" class="footer-link">Help Center</a>
                    <a href="#" class="footer-link">Contact Us</a>
                    <a href="#" class="footer-link">Privacy Policy</a>
                    <a href="#" class="footer-link">Terms of Service</a>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-white mb-3">Get Started</h5>
                    <a href="pages/login.php" class="footer-link">Login</a>
                    <a href="pages/register.php" class="footer-link">Sign Up</a>
                    <a href="#" class="footer-link">Become a Mentor</a>
                    <a href="#" class="footer-link">Enterprise Plans</a>
                </div>
            </div>
            
            <div class="copyright text-center">
                <p>&copy; 2024 SkillSwap. All rights reserved. Made with <i class="fas fa-heart text-danger"></i> for the learning community.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animation on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.section-title, .feature-card, .community-stats, .skill-badge, .section-image').forEach(el => {
            observer.observe(el);
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });

        // Image error handling - fallback to placeholder
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                this.style.display = 'none';
                const placeholder = this.nextElementSibling;
                if (placeholder && placeholder.classList.contains('image-placeholder')) {
                    placeholder.style.display = 'flex';
                }
            });
        });
    </script>
</body>
</html>