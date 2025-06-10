<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CureBooking | Surgery</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  </head>
  <body>
    <?php
      include '../include/header.php';
      include '../styles.php';
    ?>

    <section class="hero">
      <div class="container">
        <h1>We are experts in Surgical solutions.</h1>
        <p>
          Get the best surgeons and advanced treatments for your medical needs.
        </p>
        <form class="search-container">
          <input type="text" id="search-bar" placeholder="Search for surgeries...">
          <button type="submit" aria-label="Search"><i class="ri-search-line id="search-icon"></i></button>
        </form>
      </div>
    </section>

    <section class="filter-section">
      <div class="container">
        <div class="filter-buttons">
          <button class="filter-btn active" data-filter="all">
            All Surgeries
          </button>
          <button class="filter-btn" data-filter="general">General</button>
          <button class="filter-btn" data-filter="orthopedics">
            Orthopedics
          </button>
          <button class="filter-btn" data-filter="ophthalmology">
            Ophthalmology
          </button>
          <button class="filter-btn" data-filter="urology">Urology</button>
          <button class="filter-btn" data-filter="cosmetic">Cosmetic</button>
          <button class="filter-btn" data-filter="proctology">
            Proctology
          </button>
          <button class="filter-btn" data-filter="ent">ENT</button>
          <button class="filter-btn" data-filter="dermatology">
            Dermatology
          </button>
          <button class="filter-btn" data-filter="vascular">Vascular</button>
          <button class="filter-btn" data-filter="oncology">Oncology</button>
        </div>
      </div>
    </section>

    <section class="popular-surgeries">
      <div class="container">
        <div class="surgery-grid">
          <!-- Surgery cards remain the same -->
          <div class="surgery-card" data-category="general">
            <div class="icon-container bg-purple">
              <img src="images/piles.png" alt="Piles" />
            </div>
            <h3>Piles</h3>
          </div>
          <div class="surgery-card" data-category="general">
            <div class="icon-container bg-orange">
              <img src="images/hernia.png" alt="Hernia Treatment" />
            </div>
            <h3>Hernia Treatment</h3>
          </div>
          <div class="surgery-card" data-category="urology">
            <div class="icon-container bg-blue">
              <img src="images/kidney-stone.png" alt="Kidney Stone" />
            </div>
            <h3>Kidney Stone</h3>
          </div>
          <div class="surgery-card" data-category="ophthalmology">
            <div class="icon-container bg-light-blue">
              <img src="images/cataract.png" alt="Cataract" />
            </div>
            <h3>Cataract</h3>
          </div>
          <div class="surgery-card" data-category="urology">
            <div class="icon-container bg-orange">
              <img src="images/circumcision.png" alt="Circumcision" />
            </div>
            <h3>Circumcision</h3>
          </div>
          <div class="surgery-card" data-category="ophthalmology">
            <div class="icon-container bg-light-blue">
              <img src="images/lasik.png" alt="Lasik" />
            </div>
            <h3>Lasik</h3>
          </div>
          <div class="surgery-card" data-category="vascular">
            <div class="icon-container bg-light-blue">
              <img src="images/varicose-veins.png" alt="Varicose Veins" />
            </div>
            <h3>Varicose Veins</h3>
          </div>
          <div class="surgery-card" data-category="general">
            <div class="icon-container bg-purple">
              <img src="images/gallstone.png" alt="Gallstone" />
            </div>
            <h3>Gallstone</h3>
          </div>
          <div class="surgery-card" data-category="proctology">
            <div class="icon-container bg-red">
              <img src="images/anal-fistula.png" alt="Anal Fistula" />
            </div>
            <h3>Anal Fistula</h3>
          </div>
          <div class="surgery-card" data-category="cosmetic">
            <div class="icon-container bg-orange">
              <img src="images/gynecomastia.png" alt="Gynecomastia" />
            </div>
            <h3>Gynecomastia</h3>
          </div>
          <div class="surgery-card" data-category="proctology">
            <div class="icon-container bg-purple">
              <img src="images/anal-fissure.png" alt="Anal Fissure" />
            </div>
            <h3>Anal Fissure</h3>
          </div>
          <div class="surgery-card" data-category="cosmetic">
            <div class="icon-container bg-orange">
              <img src="images/lipoma.png" alt="Lipoma Removal" />
            </div>
            <h3>Lipoma Removal</h3>
          </div>
          <div class="surgery-card" data-category="dermatology">
            <div class="icon-container bg-blue">
              <img src="images/sebaceous-cyst.png" alt="Sebaceous Cyst" />
            </div>
            <h3>Sebaceous Cyst</h3>
          </div>
          <div class="surgery-card" data-category="ent">
            <div class="icon-container bg-red">
              <img src="images/pilonidal-sinus.png" alt="Pilonidal Sinus" />
            </div>
            <h3>Pilonidal Sinus</h3>
          </div>
          <div class="surgery-card" data-category="oncology">
            <div class="icon-container bg-light-blue">
              <img src="images/breast-lump.png" alt="Lump in Breast" />
            </div>
            <h3>Lump in Breast</h3>
          </div>
          <div class="surgery-card" data-category="urology">
            <div class="icon-container bg-blue">
              <img src="images/hydrocele.png" alt="Hydrocele" />
            </div>
            <h3>Hydrocele</h3>
          </div>
          <div class="surgery-card" data-category="orthopedics">
            <div class="icon-container bg-purple">
              <img src="images/knee-replacement.png" alt="Knee Replacement" />
            </div>
            <h3>Knee Replacement</h3>
          </div>
          <div class="surgery-card" data-category="cosmetic">
            <div class="icon-container bg-orange">
              <img src="images/hair-transplant.png" alt="Hair Transplant" />
            </div>
            <h3>Hair Transplant</h3>
          </div>
        </div>
      </div>
    </section>

    <section class="why-choose-us">
      <div class="container">
        <h2>Why Choose Our Surgical Solutions</h2>
        <div class="features">
          <div class="feature">
            <div class="feature-icon">
              <i class="fas fa-user-md"></i>
            </div>
            <h3>Expert Surgeons</h3>
            <p>
              Our network includes the top 5% of surgeons with extensive
              experience.
            </p>
          </div>
          <div class="feature">
            <div class="feature-icon">
              <i class="fas fa-hospital"></i>
            </div>
            <h3>Advanced Facilities</h3>
            <p>
              State-of-the-art hospitals with the latest medical technology.
            </p>
          </div>
          <div class="feature">
            <div class="feature-icon">
              <i class="fas fa-comment-medical"></i>
            </div>
            <h3>Free Consultation</h3>
            <p>Get a free consultation with our specialists before deciding.</p>
          </div>
          <div class="feature">
            <div class="feature-icon">
              <i class="fas fa-wallet"></i>
            </div>
            <h3>Transparent Pricing</h3>
            <p>No hidden costs, with detailed breakdown of all expenses.</p>
          </div>
        </div>
      </div>
    </section>
    <script src="script.js"></script>
  </body>
</html>
