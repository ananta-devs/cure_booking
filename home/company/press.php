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

    <?php include '../include/footer.php'; ?>

    
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
    <script>
        // Article content data
        const articleData = {
        funding: {
            title:
            "CureBooking Secures $50M Series B Funding to Expand Telemedicine Services",
            date: "March 15, 2024",
            source: "Business Today",
            content: `
                            <p><strong>Mumbai, India</strong> - CureBooking, India's leading healthcare booking platform, announced today that it has successfully raised $50 million in Series B funding, led by HealthTech Ventures with participation from existing investors including Accel Partners and Sequoia Capital India.</p>
                            
                            <h3>Strategic Growth Initiative</h3>
                            <p>The funding round will primarily focus on expanding CureBooking's telemedicine capabilities and scaling operations to reach tier-2 and tier-3 cities across India. The company plans to onboard an additional 1,000 healthcare providers and launch specialized telemedicine services for chronic disease management.</p>
                            
                            <div class="quote">
                                "This funding represents a significant milestone in our mission to make quality healthcare accessible to every Indian. We're excited to leverage this investment to expand our telemedicine services and reach underserved communities," said Dr. Rajesh Kumar, CEO and Co-founder of CureBooking.
                            </div>
                            
                            <h3>Market Expansion Plans</h3>
                            <p>With this new capital, CureBooking plans to:</p>
                            <ul>
                                <li>Launch operations in 15 new cities by the end of 2024</li>
                                <li>Develop AI-powered diagnostic tools for remote consultations</li>
                                <li>Expand the team by hiring 200+ healthcare professionals and technology experts</li>
                                <li>Establish partnerships with rural healthcare centers</li>
                            </ul>
                            
                            <div class="stats">
                                <h4>Key Performance Metrics:</h4>
                                <ul>
                                    <li>Over 1.2 million registered users</li>
                                    <li>600,000+ successful appointments booked</li>
                                    <li>Partnership with 500+ hospitals and clinics</li>
                                    <li>95% customer satisfaction rate</li>
                                    <li>30% month-over-month growth rate</li>
                                </ul>
                            </div>
                            
                            <p>Sarah Williams, Managing Partner at HealthTech Ventures, commented: "CureBooking has demonstrated exceptional growth and innovation in the healthcare technology space. Their comprehensive platform addresses critical gaps in healthcare accessibility, particularly in remote areas. We're thrilled to support their expansion journey."</p>
                            
                            <h3>Technology Innovation</h3>
                            <p>The company also announced plans to invest heavily in artificial intelligence and machine learning technologies to enhance diagnostic accuracy and personalize healthcare recommendations. A new AI research lab will be established in Bangalore to focus on developing cutting-edge healthcare solutions.</p>
                            
                            <p>Founded in 2021, CureBooking has quickly established itself as a trusted name in digital healthcare, serving over 1.2 million users across 25 cities in India. The platform offers comprehensive healthcare services including doctor consultations, medicine delivery, lab test bookings, and telemedicine services.</p>
                        `,
        },
        hospitals: {
            title:
            "CureBooking Partners with 500+ Hospitals to Strengthen Healthcare Network",
            date: "February 28, 2024",
            source: "Economic Times",
            content: `
                            <p><strong>New Delhi</strong> - CureBooking today announced a major milestone in its expansion strategy, successfully partnering with over 500 hospitals and healthcare facilities across 25 cities in India. This strategic alliance positions CureBooking as one of the largest healthcare booking platforms in the country.</p>
                            
                            <h3>Comprehensive Healthcare Network</h3>
                            <p>The partnership includes major hospital chains, specialty clinics, and community healthcare centers, creating a robust network that serves diverse medical needs. This expansion enables CureBooking users to access a wide range of healthcare services, from routine check-ups to specialized treatments.</p>
                            
                            <div class="stats">
                                <h4>Network Expansion Highlights:</h4>
                                <ul>
                                    <li>500+ partner hospitals and clinics</li>
                                    <li>2,500+ specialist doctors available</li>
                                    <li>Coverage across 25 major cities</li>
                                    <li>24/7 emergency consultation services</li>
                                    <li>Multi-language support in 8 regional languages</li>
                                </ul>
                            </div>
                            
                            <div class="quote">
                                "Our goal has always been to bridge the gap between patients and quality healthcare. This partnership network ensures that no matter where our users are located, they can access reliable medical care through our platform," stated Dr. Priya Sharma, Chief Medical Officer at CureBooking.
                            </div>
                            
                            <h3>Enhanced Service Offerings</h3>
                            <p>The expanded network will enable CureBooking to offer:</p>
                            <ul>
                                <li>Same-day appointment bookings at premium hospitals</li>
                                <li>Specialized treatment packages for chronic conditions</li>
                                <li>Emergency consultation services</li>
                                <li>Health checkup packages with partner labs</li>
                                <li>Post-treatment follow-up care coordination</li>
                            </ul>
                            
                            <p>Dr. Anil Gupta, Medical Director at Apollo Hospitals, one of the key partners, said: "CureBooking has demonstrated exceptional commitment to improving healthcare accessibility. Their technology platform seamlessly integrates with our hospital systems, making it easier for patients to schedule appointments and access our services."</p>
                            
                            <h3>Digital Health Revolution</h3>
                            <p>This partnership represents a significant step forward in India's digital health transformation. By connecting traditional healthcare providers with modern technology platforms, CureBooking is helping to modernize the healthcare delivery system and improve patient outcomes.</p>
                            
                            <p>The company has also introduced innovative features such as virtual queue management, contactless check-ins, and digital health records integration to enhance the overall patient experience across all partner facilities.</p>
                            
                            <div class="quote">
                                "We're witnessing a fundamental shift in how healthcare is delivered in India. Our partnership model creates a win-win situation where hospitals can optimize their operations while patients enjoy seamless access to quality care," added Vikram Patel, Chief Business Officer at CureBooking.
                            </div>
                            
                            <p>Looking ahead, CureBooking plans to expand this network to 1,000+ healthcare facilities by the end of 2024, with a particular focus on reaching underserved rural and semi-urban areas across India.</p>
                        `,
        },
        ai: {
            title:
            "CureBooking Launches AI-Powered Symptom Checker for Better Diagnosis",
            date: "January 20, 2024",
            source: "TechCrunch India",
            content: `
                            <p><strong>Bangalore</strong> - CureBooking has unveiled its latest innovation: an advanced AI-powered symptom checker that leverages machine learning algorithms to help users identify potential health issues and connect them with appropriate medical specialists. This groundbreaking feature represents a significant leap forward in digital healthcare technology.</p>
                            
                            <h3>Advanced AI Technology</h3>
                            <p>The symptom checker utilizes natural language processing and deep learning models trained on millions of medical cases and peer-reviewed research papers. The system can analyze user-reported symptoms, medical history, and demographic factors to provide accurate preliminary health assessments.</p>
                            
                            <div class="stats">
                                <h4>AI Symptom Checker Features:</h4>
                                <ul>
                                    <li>Recognition of 500+ symptoms and conditions</li>
                                    <li>85% accuracy rate in preliminary assessments</li>
                                    <li>Multi-language support for regional languages</li>
                                    <li>Integration with electronic health records</li>
                                    <li>Real-time specialist recommendations</li>
                                </ul>
                            </div>
                            
                            <div class="quote">
                                "Our AI symptom checker represents years of research and development in healthcare AI. It's designed to empower users with preliminary health insights while ensuring they receive appropriate medical care from qualified professionals," explained Dr. Arjun Reddy, Chief Technology Officer at CureBooking.
                            </div>
                            
                            <h3>How It Works</h3>
                            <p>Users can interact with the AI system through a conversational interface, describing their symptoms in natural language. The AI asks relevant follow-up questions to gather comprehensive information before providing:</p>
                            <ul>
                                <li>Potential condition assessments with confidence scores</li>
                                <li>Urgency level recommendations (emergency, urgent, or routine care)</li>
                                <li>Suggested specialist types for consultation</li>
                                <li>Nearby healthcare facility recommendations</li>
                                <li>Self-care and prevention tips</li>
                            </ul>
                            
                            <h3>Clinical Validation</h3>
                            <p>The AI system has undergone extensive testing and validation in collaboration with leading medical institutions. A clinical study involving 10,000 cases showed that the AI symptom checker achieved an 85% accuracy rate in identifying the correct medical specialty needed for treatment.</p>
                            
                            <div class="quote">
                                "This technology doesn't replace doctors but rather serves as an intelligent triage system that helps patients understand when and where to seek appropriate medical care. It's particularly valuable in areas with limited access to healthcare professionals," noted Dr. Meera Krishnan, Head of Clinical Research.
                            </div>
                            
                            <p>The feature is now available to all CureBooking users through the mobile app and web platform, with plans to integrate it with wearable devices and IoT health monitors in the coming months.</p>
                        `,
        },
        milestone: {
            title: "CureBooking Crosses 1 Million User Milestone",
            date: "December 10, 2023",
            source: "Hindu BusinessLine",
            content: `
                            <p><strong>Mumbai</strong> - CureBooking, India's fastest-growing healthcare booking platform, today celebrated a major milestone of crossing 1 million registered users within just two years of its launch. This achievement underscores the platform's rapid adoption and the growing demand for digital healthcare solutions in India.</p>
                            
                            <h3>Remarkable Growth Journey</h3>
                            <p>Since its inception in 2021, CureBooking has experienced exponential growth, adding over 50,000 new users monthly. The platform has successfully facilitated more than 500,000 medical appointments and delivered medicines to over 200,000 patients across India.</p>
                            
                            <div class="stats">
                                <h4>Key Milestones Achieved:</h4>
                                <ul>
                                    <li>1,000,000+ registered users</li>
                                    <li>500,000+ successful appointments</li>
                                    <li>200,000+ medicine deliveries</li>
                                    <li>150,000+ lab test bookings</li>
                                    <li>25 cities with active operations</li>
                                    <li>98% customer satisfaction rate</li>
                                </ul>
                            </div>
                            
                            <div class="quote">
                                "Reaching one million users is not just a number for us – it represents one million lives we've touched and made healthcare more accessible for. This milestone motivates us to continue innovating and expanding our services to reach even more people across India," said Dr. Rajesh Kumar, CEO and Co-founder of CureBooking.
                            </div>
                            
                            <h3>User Demographics and Insights</h3>
                            <p>The platform's user base spans across diverse demographics and geographic locations:</p>
                            <ul>
                                <li>65% of users are from tier-1 cities, 35% from tier-2 and tier-3 cities</li>
                                <li>Average user age: 35 years</li>
                                <li>55% female, 45% male users</li>
                                <li>Most popular services: General consultation (40%), Specialist appointments (35%), Medicine delivery (25%)</li>
                                <li>Peak usage hours: 9-11 AM and 6-8 PM</li>
                            </ul>
                            
                            <h3>Impact on Healthcare Accessibility</h3>
                            <p>CureBooking's growth has significantly impacted healthcare accessibility in India. The platform has reduced average appointment waiting times from 7 days to 2 days and made healthcare services available 24/7 through its telemedicine offerings.</p>
                            
                            <div class="quote">
                                "CureBooking has revolutionized how we deliver healthcare services. The platform has helped us reach patients who previously had limited access to quality medical care, especially in remote areas," commented Dr. Sanjay Agarwal, Medical Director at Max Healthcare.
                            </div>
                            
                            <h3>Future Expansion Plans</h3>
                            <p>Building on this success, CureBooking plans to double its user base to 2 million by the end of 2024. The company is also exploring international expansion opportunities in Southeast Asian markets.</p>
                            
                            <p>Recent user testimonials highlight the platform's impact: "CureBooking made it incredibly easy for me to find a specialist for my mother's condition. What used to take days of calling different hospitals now takes just a few minutes," shared Priya Singh, a user from Delhi.</p>
                            
                            <p>The platform continues to innovate with upcoming features including AI-powered health monitoring, personalized wellness programs, and expanded rural healthcare services to reach India's underserved populations.</p>
                        `,
        },
        };

        // Modal functionality
        const modal = document.getElementById("articleModal");
        const modalTitle = document.getElementById("modalTitle");
        const modalDate = document.getElementById("modalDate");
        const modalArticle = document.getElementById("modalArticle");
        const modalSource = document.getElementById("modalSource");
        const closeBtn = document.getElementsByClassName("close")[0];

        // Add event listeners to all "Read More" buttons
        document.querySelectorAll(".read-more").forEach((button) => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const articleKey = this.getAttribute("data-article");
            const article = articleData[articleKey];

            if (article) {
            modalTitle.textContent = article.title;
            modalDate.textContent = article.date;
            modalArticle.innerHTML = article.content;
            modalSource.textContent = `Source: ${article.source}`;
            modal.style.display = "block";
            document.body.style.overflow = "hidden"; // Prevent background scrolling
            }
        });
        });

        // Close modal when clicking the X button
        closeBtn.onclick = function () {
        modal.style.display = "none";
        document.body.style.overflow = "auto"; // Restore scrolling
        };

        // Close modal when clicking outside of it
        window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
            document.body.style.overflow = "auto"; // Restore scrolling
        }
        };

        // Close modal with Escape key
        document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal.style.display === "block") {
            modal.style.display = "none";
            document.body.style.overflow = "auto"; // Restore scrolling
        }
        });

    </script>
</body>

</html>