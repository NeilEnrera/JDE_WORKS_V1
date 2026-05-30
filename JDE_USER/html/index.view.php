<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JDE Works of Our Hands - Premium Tailoring Services</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/navbar.css">
</head>

<body>
    <?php
    $activePage = 'home';
    include '../backend/navbar.php';
    ?>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" type="text/css" href="../css/index.css?v=<?php echo time(); ?>">

    <div class="hero-image gold-hero" id="Home">
        <div class="hero-content">
            <div class="container-hero gold-card">
                <br><br>
                <h1>JDE Works of Our Hands</h1><br><br><br>
                <a href="product.php"><button class="btn">ORDER</button></a>
                <br><br>
            </div>
        </div>
    </div>

    <section class="Features">
        <div class="container">
            <div class="section-header text-center mb-4">
                <h1 class="section-title">Why Choose Us</h1>
                <p class="section-subtitle">Experience excellence in every stitch with our premium tailoring services
                </p>
            </div>
            <div class="features-grid">
                <div class="feature">
                    <div class="feature-icon">
                        <img src="../assets/img/scissor.png" alt="Customize">
                    </div>
                    <h2>Customize<br>to your liking</h2>
                    <p>Have the control and final decision of how you like your uniform.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <img src="../assets/img/measurrement.png" alt="Made to Measure">
                    </div>
                    <h2>Made to<br>Measure</h2>
                    <p>A guaranteed perfect fit for your comfort and confidence.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <img src="../assets/img/box.png" alt="Pick-up or Delivery">
                    </div>
                    <h2>Pick-up<br>or Delivery</h2>
                    <p>Get your orders delivered or pick them up in-store—your choice.</p>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="appointment.php" class="book-appointment-btn">BOOK AN APPOINTMENT</a>
            </div>
        </div>
    </section>

    <div class="Products" id="Products">
        <div class="container">
            <div class="section-header text-center mb-4">
                <h1 class="section-title">Our Products</h1>
                <p class="section-subtitle">Discover our range of high-quality garments and uniforms</p>
            </div>

            <div class="product-scroller">
                <button class="scroll-btn prev" aria-label="Previous">&#10094;</button>
                <div class="product-track">
                    <div class="product-item">
                        <img src="../assets/img/male/nursingm front.jpg" loading="lazy" alt="Business Suit">
                        <div class="item-text">
                            <h3>Nursing Uniform</h3>
                            <p>White duty uniform for clinicals</p>
                        </div>
                    </div>

                    <div class="product-item">
                        <img src="../assets/img/female/tourismf front.jpg" loading="lazy" alt="School Uniform">
                        <div class="item-text">
                            <h3>Tourism Uniform</h3>
                            <p>Features corporate or hospitality-focused attire</p>
                        </div>
                    </div>

                    <div class="product-item">
                        <img src="../assets/img/female/medtechf front.jpg" loading="lazy" alt="Corporate Wear">
                        <div class="item-text">
                            <h3>MedTech Uniform</h3>
                            <p>Strict, formal, or specialized white scrub uniforms</p>
                        </div>
                    </div>

                    <div class="product-item">
                        <img src="../assets/img/male/accntm front.jpg" loading="lazy" alt="T-shirt">
                        <div class="item-text">
                            <h3>Accountancy Uniform</h3>
                            <p>A corporate-style uniform</p>
                        </div>
                    </div>

                    <div class="product-item">
                        <img src="../assets/img/male/shs front.jpg" loading="lazy" alt="Custom-made Uniform">
                        <div class="item-text">
                            <h3>Senior Highschool Uniform</h3>
                            <p>Uniforms are crafted to provide a professional look</p>
                        </div>
                    </div>

                    <div class="product-item">
                        <img src="../assets/img/physical unif front.jpg" loading="lazy" alt="Custom-made Uniform">
                        <div class="item-text">
                            <h3>Physical Education Uniform</h3>
                            <p>comfortable, athletic set, typically featuring a breathable cotton</p>
                        </div>
                    </div>

                    <div class="product-item">
                        <img src="../assets/img/male/medtechm front.jpg" loading="lazy" alt="Custom-made Uniform">
                        <div class="item-text">
                            <h3>Custom-made Uniform</h3>
                            <p>Crafted with care, personalized details</p>
                        </div>
                    </div>
                </div>
                <button class="scroll-btn next" aria-label="Next">&#10095;</button>
            </div>
            <div class="product-dots" aria-label="Carousel indicators"></div>
        </div>
    </div>

    <div class="Our-Story" id="About">
        <div class="container">
            <div class="section-header text-center mb-4">
                <h1 class="section-title">Our Story</h1>
                <p class="section-subtitle">12 years of craftsmanship and dedication to quality</p>
            </div>

            <div class="story-layout">
                <div class="story-copy">
                    <p>
                        JDE Work of Our Hands has proudly served the Caloocan City community for 12 years, specializing
                        in high-quality tailoring services. Located in Adeline Homes, we initially focused on crafting
                        durable and comfortable school uniforms, becoming a trusted provider for local families. Over
                        the years, our expertise has expanded to include general alterations, catering to the diverse
                        tailoring needs of our neighborhood. Our deep understanding of the local market allows us to
                        provide tailored solutions and build lasting relationships with our customers.
                    </p>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number">12+</div>
                            <div class="stat-label">Years of Service</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">1000+</div>
                            <div class="stat-label">Happy Customers</div>
                        </div>
                    </div>
                </div>

                <div class="story-gallery">
                    <div class="story-image"><img src="../assets/img/st.jpg" alt="Workshop"></div>
                    <div class="story-image"><img src="../assets/img/st2.jpg" alt="Fabrics"></div>
                    <div class="story-image"><img src="../assets/img/st1.jpg" alt="Sewing"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="Contact" id="Contact">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h1 class="section-title">Get In Touch</h1>
                <p class="section-subtitle">Ready to create your perfect uniform? We'd love to hear from you!</p>
            </div>

            <!-- Quick Contact Cards -->
            <div class="quick-contact-grid">
                <div class="quick-contact-card">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <h4>Visit Us</h4>
                    <p>Hilltop Branch<br>
                        11 Esperanza, Novaliches<br>
                        Hilltop Subd. Greater Lagro<br>
                        Quezon City</p>
                </div>

                <div class="quick-contact-card">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                            </path>
                        </svg>
                    </div>
                    <h4>Call Us</h4>
                    <p>
                        <a href="tel:09335987864">0933-598-7864</a><br>
                        <a href="tel:09978927142">0997-892-7142</a><br>
                        <a href="tel:09075859069">0907-585-9069</a><br>
                        <a href="tel:09058763368">0905-876-3368</a><br>
                        <a href="tel:86513605">8651-3605</a>
                    </p>
                </div>

                <div class="quick-contact-card">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h4>Business Hours</h4>
                    <p>
                        <strong>Mon - Fri:</strong> 8:00 AM - 6:00 PM<br>
                        <strong>Saturday:</strong> 8:00 AM - 5:00 PM<br>
                        <strong>Sunday:</strong> Closed
                    </p>
                </div>
            </div>

            <!-- Map & Contact Form Section -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="contact-main-grid">
                    <div class="contact-form-container">
                        <h3>Send Us a Message</h3>
                        <p class="form-description">Fill out the form below and we'll get back to you as soon as possible.
                        </p>

                        <form class="contact-form" action="process_contact.php" method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" pattern="09[0-9]{9}" maxlength="11" required>
                                </div>
                                <div class="form-group">
                                    <label for="subject">Subject *</label>
                                    <select id="subject" name="subject" required>
                                        <option value="">Select a subject</option>
                                        <option value="uniform-inquiry">Uniform Inquiry</option>
                                        <option value="alteration">Alteration Service</option>
                                        <option value="custom-order">Custom Order</option>
                                        <option value="appointment">Book Appointment</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="message">Message *</label>
                                <textarea id="message" name="message" rows="5" required style="resize: none;"></textarea>
                            </div>

                            <button type="submit" class="contact-submit-btn">
                                <span>Send Message</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <div class="location-map-wrapper">
                        <div class="map-header">
                            <h3>Find Us Here</h3>
                        </div>
                        <div class="location-map">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d603.3558382712196!2d121.06990730426064!3d14.734815328185528!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b155c1031841%3A0x628522affbb6df1b!2sJDE%20Work%20of%20Our%20Hands!5e1!3m2!1sen!2sph!4v1752894339893!5m2!1sen!2sph"
                                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contact Success Modal -->
    <div class="modal fade" id="contactSuccessModal" tabindex="-1" aria-labelledby="contactSuccessModalLabel"
        aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center" style="border-radius: 20px; border: 1px solid var(--primary-gold);">
                <div class="modal-body p-5">
                    <div class="mb-4">
                        <div class="success-icon-wrapper mx-auto mb-4"
                            style="width: 80px; height: 80px; background: rgba(39, 174, 96, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #27ae60;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <h2 class="fw-bold" style="color: var(--primary-dark-blue);">Message Sent!</h2>
                        <p class="text-muted mt-3">Thank you for reaching out. We have received your message and will
                            get back to you as soon as possible via email.</p>
                    </div>
                    <button type="button" class="btn w-100" data-bs-dismiss="modal"
                        style="background: var(--primary-gold); color: var(--primary-dark-blue); font-weight: 700; border-radius: 12px; padding: 12px;">Great!</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Site Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> JDE Works of Our Hands. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
    <script src="../js/index.js?v=<?php echo time(); ?>"></script>
</body>

</html>