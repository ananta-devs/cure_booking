<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us | CureBooking</title>
  <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png" />
  <link rel="stylesheet" href="style.css" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
</head>
<body>
  <?php include '../include/header.php'; ?>

  <!-- Hero Section -->
  <section class="hero" data-aos="fade-up">
    <div class="container">
      <h1>About CureBooking</h1>
      <p>Revolutionizing healthcare accessibility through innovative technology and compassionate care</p>
    </div>
  </section>

  <!-- About Content -->
  <section class="about-content">
    <div class="container">
      <h2 class="section-title" data-aos="fade-up">Our Story</h2>

      <div class="content-grid">
        <div class="content-card" data-aos="fade-right">
          <h3>Our Mission</h3>
          <p>At CureBooking, we believe healthcare should be accessible, convenient, and reliable for everyone...</p>
        </div>
        <div class="content-card" data-aos="fade-left">
          <h3>Our Vision</h3>
          <p>We envision a world where quality healthcare is within everyone's reach...</p>
        </div>
        <div class="content-card" data-aos="fade-up">
          <h3>Our Values</h3>
          <p>We are guided by core values of integrity, innovation, and compassion...</p>
        </div>
      </div>

      <!-- Stats Section -->
      <div class="stats-section" data-aos="zoom-in">
        <div class="container">
          <h2 class="section-title">Our Impact</h2>
          <div class="stats-grid">
            <div class="stat-item" data-aos="fade-up"><span class="stat-number">50,000+</span><div class="stat-label">Happy Patients</div></div>
            <div class="stat-item" data-aos="fade-up" data-aos-delay="100"><span class="stat-number">2,500+</span><div class="stat-label">Verified Doctors</div></div>
            <div class="stat-item" data-aos="fade-up" data-aos-delay="200"><span class="stat-number">100+</span><div class="stat-label">Partner Hospitals</div></div>
            <div class="stat-item" data-aos="fade-up" data-aos-delay="300"><span class="stat-number">25+</span><div class="stat-label">Cities Served</div></div>
          </div>
        </div>
      </div>

      <!-- What We Offer -->
      <h2 class="section-title" data-aos="fade-up">What We Offer</h2>
      <div class="content-grid">
        <div class="content-card" data-aos="fade-up">
          <h3>Doctor Appointments</h3>
          <p>Find and book appointments with qualified healthcare professionals...</p>
        </div>
        <div class="content-card" data-aos="fade-up" data-aos-delay="100">
          <h3>Medicine Delivery</h3>
          <p>Order medicines and health products online with doorstep delivery...</p>
        </div>
        <div class="content-card" data-aos="fade-up" data-aos-delay="200">
          <h3>Lab Tests</h3>
          <p>Book diagnostic tests and pathology services with home sample collection...</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Team Section -->
  <section class="team-section">
    <div class="container">
      <h2 class="section-title" data-aos="fade-up">Our Leadership Team</h2>
      <div class="team-grid">
        <div class="team-member" data-aos="flip-left">
          <div class="member-photo">DR</div>
          <div class="member-name">Dr. Rajesh Kumar</div>
          <div class="member-role">Chief Executive Officer</div>
          <div class="member-bio">With over 15 years in healthcare technology...</div>
        </div>
        <div class="team-member" data-aos="flip-left" data-aos-delay="100">
          <div class="member-photo">PS</div>
          <div class="member-name">Priya Sharma</div>
          <div class="member-role">Chief Technology Officer</div>
          <div class="member-bio">A seasoned tech expert who ensures our platform remains secure...</div>
        </div>
        <div class="team-member" data-aos="flip-left" data-aos-delay="200">
          <div class="member-photo">AM</div>
          <div class="member-name">Dr. Amit Mehta</div>
          <div class="member-role">Chief Medical Officer</div>
          <div class="member-bio">Leading our medical advisory board, Dr. Mehta ensures the highest standards...</div>
        </div>
        <div class="team-member" data-aos="flip-left" data-aos-delay="300">
          <div class="member-photo">SK</div>
          <div class="member-name">Sneha Kapoor</div>
          <div class="member-role">VP, Operations</div>
          <div class="member-bio">Manages day-to-day operations ensuring seamless service delivery...</div>
        </div>
      </div>
    </div>
  </section>

  <?php include '../include/footer.php'; ?>

  <!-- AOS Library -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({ duration: 1000, once: true });
  </script>
</body>
</html>
