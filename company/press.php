<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Press & Media | CureBooking</title>
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>

<body>
    <?php include '../include/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero" data-aos="fade-up">
        <div class="container">
            <h1>Press & Media</h1>
            <p>Latest news, press releases, and media resources about CureBooking's journey in transforming healthcare</p>
        </div>
    </section>

    <!-- Press Releases Section -->
    <section class="press-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Latest Press Releases</h2>
            <div class="press-grid">
                <div class="press-item" data-aos="fade-up">
                    <div class="press-date">March 15, 2024</div>
                    <h3 class="press-title">CureBooking Secures $50M Series B Funding to Expand Telemedicine Services</h3>
                    <p class="press-excerpt">CureBooking announced today that it has raised $50 million in Series B funding led by HealthTech Ventures, with participation from existing investors. The funding will be used to expand telemedicine capabilities and enter new markets across India.</p>
                    <div class="press-meta">
                        <span class="press-source">Business Today</span>
                        <a href="#" class="read-more" data-article="funding">Read More →</a>
                    </div>
                </div>

                <div class="press-item" data-aos="fade-up">
                    <div class="press-date">February 28, 2024</div>
                    <h3 class="press-title">CureBooking Partners with 500+ Hospitals to Strengthen Healthcare Network</h3>
                    <p class="press-excerpt">In a major expansion move, CureBooking has partnered with over 500 hospitals across 25 cities, making it one of the largest healthcare booking platforms in India. This partnership aims to provide seamless healthcare access to millions of users.</p>
                    <div class="press-meta">
                        <span class="press-source">Economic Times</span>
                        <a href="#" class="read-more" data-article="hospitals">Read More →</a>
                    </div>
                </div>

                <div class="press-item" data-aos="fade-up">
                    <div class="press-date">January 20, 2024</div>
                    <h3 class="press-title">CureBooking Launches AI-Powered Symptom Checker for Better Diagnosis</h3>
                    <p class="press-excerpt">CureBooking introduces an advanced AI-powered symptom checker that helps users identify potential health issues and connects them with appropriate specialists. The feature uses machine learning to provide accurate preliminary assessments.</p>
                    <div class="press-meta">
                        <span class="press-source">TechCrunch India</span>
                        <a href="#" class="read-more" data-article="ai">Read More →</a>
                    </div>
                </div>

                <div class="press-item" data-aos="fade-up">
                    <div class="press-date">December 10, 2023</div>
                    <h3 class="press-title">CureBooking Crosses 1 Million User Milestone</h3>
                    <p class="press-excerpt">CureBooking celebrates reaching 1 million registered users within two years of launch. The platform has facilitated over 500,000 appointments and delivered medicines to more than 200,000 patients across India.</p>
                    <div class="press-meta">
                        <span class="press-source">Hindu BusinessLine</span>
                        <a href="#" class="read-more" data-article="milestone">Read More →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal -->
    <div id="articleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle"></h2>
                <div class="modal-date" id="modalDate"></div>
            </div>
            <div class="modal-body">
                <div class="modal-article" id="modalArticle"></div>
            </div>
            <div class="modal-footer">
                <div class="modal-source" id="modalSource"></div>
            </div>
        </div>
    </div>

    <!-- Media Kit Section -->
    <section class="media-kit">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Media Kit</h2>
            <div class="media-grid">
                <div class="media-item" data-aos="flip-up">
                    <div class="media-icon">📄</div>
                    <h3>Press Kit</h3>
                    <p>Complete press kit with company information, executive bios, and key statistics</p>
                    <a href="#" class="download-btn">Download</a>
                </div>

                <div class="media-item" data-aos="flip-up">
                    <div class="media-icon">🖼️</div>
                    <h3>Brand Assets</h3>
                    <p>Official logos, brand colors, typography guidelines, and usage instructions</p>
                    <a href="#" class="download-btn">Download</a>
                </div>

                <div class="media-item" data-aos="flip-up">
                    <div class="media-icon">📊</div>
                    <h3>Company Stats</h3>
                    <p>Latest company statistics, user demographics, and growth metrics</p>
                    <a href="#" class="download-btn">Download</a>
                </div>

                <div class="media-item" data-aos="flip-up">
                    <div class="media-icon">🎥</div>
                    <h3>Video Resources</h3>
                    <p>Product demos, CEO interviews, and promotional videos for media use</p>
                    <a href="#" class="download-btn">Download</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Awards Section -->
    <section class="awards-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Awards & Recognition</h2>
            <div class="awards-grid">
                <div class="award-item" data-aos="zoom-in">
                    <div class="award-icon">🏆</div>
                    <h3 class="award-title">Best HealthTech Startup</h3>
                    <p class="award-year">2024</p>
                    <p class="award-description">Recognized as the Best HealthTech Startup at the India Digital Health Summit for innovative healthcare solutions.</p>
                </div>

                <div class="award-item" data-aos="zoom-in">
                    <div class="award-icon">🥇</div>
                    <h3 class="award-title">Digital Innovation Award</h3>
                    <p class="award-year">2023</p>
                    <p class="award-description">Winner of the Digital Innovation Award for revolutionizing healthcare accessibility through technology.</p>
                </div>

                <div class="award-item" data-aos="zoom-in">
                    <div class="award-icon">⭐</div>
                    <h3 class="award-title">Customer Choice Award</h3>
                    <p class="award-year">2023</p>
                    <p class="award-description">Voted as the preferred healthcare booking platform by over 100,000 users in the Healthcare Excellence Awards.</p>
                </div>

                <div class="award-item" data-aos="zoom-in">
                    <div class="award-icon">🚀</div>
                    <h3 class="award-title">Fastest Growing Startup</h3>
                    <p class="award-year">2023</p>
                    <p class="award-description">Recognized for achieving 500% growth in user base and expanding to 25 cities within 18 months.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Media Contact Section -->
    <!-- <section class="media-contact">
        <div class="container">
            <h2 class="section-title">Media Contact</h2>
            <p class="contact-subtitle">For press inquiries, interviews, and media partnerships</p>
            
            <div class="contact-grid">
                <div class="contact-item">
                    <h3>Press Inquiries</h3>
                    <p><strong>Email:</strong> press@curebooking.com</p>
                    <p><strong>Phone:</strong> +91 98765 43211</p>
                </div>

                <div class="contact-item">
                    <h3>Partnership Inquiries</h3>
                    <p><strong>Email:</strong> partnerships@curebooking.com</p>
                    <p><strong>Phone:</strong> +91 98765 43212</p>
                </div>

                <div class="contact-item">
                    <h3>Investor Relations</h3>
                    <p><strong>Email:</strong> investors@curebooking.com</p>
                    <p><strong>Phone:</strong> +91 98765 43213</p>
                </div>
            </div>
        </div>
    </section> -->

    <?php include '../include/footer.php'; ?>

    <script src="script.js"></script>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>

</html>