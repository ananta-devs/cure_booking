-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2025 at 01:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cure_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adm_id` int(11) NOT NULL,
  `adm_name` text NOT NULL,
  `adm_email` text NOT NULL,
  `adm_ph` bigint(11) NOT NULL,
  `adm_img` text NOT NULL,
  `adm_pass` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adm_id`, `adm_name`, `adm_email`, `adm_ph`, `adm_img`, `adm_pass`) VALUES
(1, 'Admin', 'admin@gmail.com', 0, '', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `doctor_name` varchar(255) NOT NULL,
  `doctor_specialization` varchar(255) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `clinic_name` varchar(255) DEFAULT NULL,
  `patient_name` varchar(255) NOT NULL,
  `patient_phone` varchar(20) NOT NULL,
  `patient_email` varchar(255) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `booked_by_email` varchar(255) NOT NULL,
  `booked_by_name` varchar(255) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `clinic_id` int(11) NOT NULL,
  `clinic_name` varchar(255) NOT NULL,
  `clinic_email` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `location` text NOT NULL,
  `available_timing` varchar(100) NOT NULL,
  `clinic_pass` varchar(255) NOT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doc_id` int(11) NOT NULL,
  `doc_name` varchar(255) NOT NULL,
  `doc_specia` varchar(255) NOT NULL,
  `doc_email` varchar(255) DEFAULT NULL,
  `doc_img` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `fees` decimal(10,0) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `doc_pass` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_clinic_assignments`
--

CREATE TABLE `doctor_clinic_assignments` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `availability_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`availability_schedule`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_orders`
--

CREATE TABLE `lab_orders` (
  `id` int(11) NOT NULL,
  `booking_id` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `sample_collection_date` date NOT NULL,
  `time_slot` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Confirmed','Sample Collected','In Progress','Completed','Cancelled','Upload Done') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `booked_by_email` varchar(255) DEFAULT '',
  `booked_by_name` varchar(255) DEFAULT '',
  `clinic_id` int(11) DEFAULT NULL,
  `clinic_name` varchar(255) DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `report_uploaded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_order_items`
--

CREATE TABLE `lab_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `test_price` decimal(10,2) NOT NULL,
  `sample_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sample_type` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`id`, `name`, `sample_type`, `price`, `description`) VALUES
(1, 'Complete Blood Count (CBC)', 'Blood', 35.00, 'Measures different components and features of blood including red cells, white cells, and platelets to evaluate overall health and detect disorders.'),
(2, 'Basic Metabolic Panel (BMP)', 'Blood', 45.00, 'Measures glucose, calcium, and electrolyte levels as well as kidney function to monitor overall metabolic health.'),
(3, 'Comprehensive Metabolic Panel (CMP)', 'Blood', 65.00, 'An expanded version of BMP that includes liver function tests along with the basic metabolic measurements.'),
(4, 'Lipid Panel', 'Blood', 40.00, 'Measures cholesterol levels (total, HDL, LDL) and triglycerides to assess cardiovascular health risk.'),
(5, 'Thyroid Stimulating Hormone (TSH)', 'Blood', 35.00, 'Screens for thyroid disorders by measuring the amount of TSH in the blood.'),
(6, 'Hemoglobin A1C', 'Blood', 45.00, 'Measures average blood glucose levels over the past 2-3 months to diagnose and monitor diabetes.'),
(7, 'Urinalysis', 'Urine', 25.00, 'Examines physical and chemical properties of urine to detect various disorders including kidney disease and diabetes.'),
(8, 'Liver Function Tests', 'Blood', 55.00, 'Measures enzymes and proteins to evaluate liver health and detect liver damage or disease.'),
(9, 'C-Reactive Protein (CRP)', 'Blood', 40.00, 'Measures inflammation levels in the body which can indicate infection or chronic disease.'),
(10, 'Vitamin D Test', 'Blood', 60.00, 'Measures the level of vitamin D in blood to detect deficiency or excess.'),
(11, 'Ferritin Test', 'Blood', 50.00, 'Measures iron storage levels in the body to detect iron deficiency or overload.'),
(12, 'Prostate-Specific Antigen (PSA)', 'Blood', 70.00, 'Screens for prostate cancer and other prostate disorders in men.'),
(13, 'COVID-19 PCR Test', 'Nasal swab', 120.00, 'Detects genetic material of the SARS-CoV-2 virus to diagnose active COVID-19 infection.'),
(14, 'Complete Urine Culture', 'Urine', 65.00, 'Identifies bacteria in urine to diagnose urinary tract infections.'),
(15, 'Strep Throat Test', 'Throat swab', 30.00, 'Detects streptococcal bacteria to diagnose strep throat infections.'),
(16, 'Allergy Panel (Basic)', 'Blood', 150.00, 'Tests for common environmental allergens to identify specific allergic reactions.'),
(17, 'Testosterone Level', 'Blood', 80.00, 'Measures testosterone levels in blood to evaluate hormonal health.'),
(18, 'Estrogen Level', 'Blood', 80.00, 'Measures estrogen levels in blood to evaluate hormonal health and fertility.'),
(19, 'Vitamin B12 Test', 'Blood', 55.00, 'Measures vitamin B12 levels to detect deficiency which can cause anemia and neurological issues.'),
(20, 'Folate Test', 'Blood', 50.00, 'Measures folate levels to detect deficiency which can cause anemia.'),
(21, 'Prothrombin Time (PT)', 'Blood', 45.00, 'Measures how long it takes blood to clot to evaluate bleeding disorders or monitor blood thinners.'),
(22, 'Partial Thromboplastin Time (PTT)', 'Blood', 45.00, 'Tests how long it takes blood to clot to detect bleeding disorders.'),
(23, 'International Normalized Ratio (INR)', 'Blood', 35.00, 'Standardized measurement of blood clotting time used to monitor warfarin therapy.'),
(24, 'Hepatitis Panel', 'Blood', 120.00, 'Screens for various types of hepatitis (A, B, C) to diagnose viral liver infections.'),
(25, 'HIV Antibody Test', 'Blood', 65.00, 'Detects antibodies to HIV virus to diagnose HIV infection.'),
(26, 'Human Papillomavirus (HPV) Test', 'Cervical swab', 85.00, 'Screens for high-risk HPV strains that can cause cervical cancer.'),
(27, 'Blood Type and Rh Factor', 'Blood', 30.00, 'Determines blood type (A, B, AB, O) and Rh factor for transfusion compatibility.'),
(28, 'Blood Culture', 'Blood', 90.00, 'Tests for bacteria or fungi in the blood to diagnose bloodstream infections.'),
(29, 'Mononucleosis Test', 'Blood', 35.00, 'Screens for Epstein-Barr virus to diagnose mononucleosis.'),
(30, 'Glucose Tolerance Test', 'Blood', 85.00, 'Measures body response to glucose to diagnose diabetes and prediabetes.'),
(31, 'Semen Analysis', 'Semen', 125.00, 'Evaluates male fertility by measuring sperm count, motility, and morphology.'),
(32, 'Pregnancy Test (Blood)', 'Blood', 35.00, 'Detects hCG hormone in blood to confirm pregnancy.'),
(33, 'Progesterone Level', 'Blood', 70.00, 'Measures progesterone levels to evaluate fertility and pregnancy health.'),
(34, 'Amylase Test', 'Blood', 45.00, 'Measures amylase enzyme levels to diagnose pancreatic disorders.'),
(35, 'Lipase Test', 'Blood', 45.00, 'Measures lipase enzyme levels to diagnose pancreatic disorders.'),
(36, 'Erythrocyte Sedimentation Rate (ESR)', 'Blood', 35.00, 'Measures inflammation in the body by testing how quickly red blood cells settle.'),
(37, 'H. Pylori Test', 'Breath', 65.00, 'Detects H. pylori bacteria to diagnose stomach ulcers and gastritis.'),
(38, 'Rheumatoid Factor', 'Blood', 55.00, 'Tests for antibodies associated with rheumatoid arthritis.'),
(39, 'Anti-Nuclear Antibody Test (ANA)', 'Blood', 75.00, 'Screens for autoimmune disorders like lupus and rheumatoid arthritis.'),
(40, 'Lyme Disease Test', 'Blood', 85.00, 'Detects antibodies to Borrelia bacteria to diagnose Lyme disease.'),
(41, 'Iron Panel', 'Blood', 65.00, 'Measures iron, ferritin, and other markers to evaluate iron status.'),
(42, 'Magnesium Level', 'Blood', 40.00, 'Measures magnesium levels in blood to detect deficiency or excess.'),
(43, 'Calcium Level', 'Blood', 35.00, 'Measures calcium levels in blood to detect bone or parathyroid disorders.'),
(44, 'Potassium Level', 'Blood', 30.00, 'Measures potassium levels in blood to detect electrolyte imbalances.'),
(45, 'Sodium Level', 'Blood', 30.00, 'Measures sodium levels in blood to detect electrolyte imbalances.'),
(46, 'Chloride Level', 'Blood', 30.00, 'Measures chloride levels in blood to detect electrolyte imbalances.'),
(47, 'Bicarbonate Level', 'Blood', 30.00, 'Measures bicarbonate levels in blood to assess acid-base balance.'),
(48, 'Creatinine Test', 'Blood', 35.00, 'Measures creatinine levels to evaluate kidney function.'),
(49, 'Blood Urea Nitrogen (BUN)', 'Blood', 35.00, 'Measures the amount of urea nitrogen in blood to assess kidney function.'),
(50, 'Uric Acid Test', 'Blood', 40.00, 'Measures uric acid levels to diagnose gout and kidney disorders.'),
(51, 'Troponin Test', 'Blood', 75.00, 'Detects troponin protein in blood to diagnose heart attacks.'),
(52, 'Creatine Kinase (CK)', 'Blood', 60.00, 'Measures CK enzyme levels to detect heart or muscle damage.'),
(53, 'B-type Natriuretic Peptide (BNP)', 'Blood', 95.00, 'Measures BNP hormone to diagnose heart failure.'),
(54, 'D-dimer Test', 'Blood', 85.00, 'Detects blood clots to diagnose deep vein thrombosis or pulmonary embolism.'),
(55, 'Thyroid Antibodies Test', 'Blood', 95.00, 'Tests for antibodies that attack thyroid tissue to diagnose autoimmune thyroid disorders.'),
(56, 'Free T4 Test', 'Blood', 50.00, 'Measures free thyroxine hormone to evaluate thyroid function.'),
(57, 'Free T3 Test', 'Blood', 50.00, 'Measures free triiodothyronine hormone to evaluate thyroid function.'),
(58, 'Cortisol Test', 'Blood', 75.00, 'Measures cortisol hormone levels to diagnose adrenal gland disorders.'),
(59, 'ACTH Stimulation Test', 'Blood', 120.00, 'Tests adrenal gland function by measuring response to ACTH hormone.'),
(60, 'Parathyroid Hormone (PTH)', 'Blood', 85.00, 'Measures PTH levels to diagnose parathyroid disorders.'),
(61, 'CA-125 Test', 'Blood', 95.00, 'Measures CA-125 protein to monitor ovarian cancer treatment.'),
(62, 'Carcinoembryonic Antigen (CEA)', 'Blood', 85.00, 'Measures CEA protein to monitor colorectal cancer treatment.'),
(63, 'Alpha-Fetoprotein (AFP)', 'Blood', 85.00, 'Measures AFP protein to screen for liver cancer and monitor treatment.'),
(64, 'CA 19-9 Test', 'Blood', 95.00, 'Measures CA 19-9 protein to monitor pancreatic cancer treatment.'),
(65, 'HER2/neu Test', 'Tissue', 250.00, 'Tests for HER2 protein in breast cancer tissue to guide treatment decisions.'),
(66, 'BRCA Gene Testing', 'Blood', 850.00, 'Tests for BRCA gene mutations to assess breast and ovarian cancer risk.'),
(67, 'Cystic Fibrosis Genetic Test', 'Blood', 350.00, 'Tests for genetic mutations associated with cystic fibrosis.'),
(68, 'Sickle Cell Anemia Test', 'Blood', 125.00, 'Tests for hemoglobin S to diagnose sickle cell anemia.'),
(69, 'PKU (Phenylketonuria) Test', 'Blood', 95.00, 'Screens newborns for PKU, a genetic disorder affecting metabolism.'),
(70, 'Tay-Sachs Disease Test', 'Blood', 225.00, 'Tests for genetic mutations associated with Tay-Sachs disease.'),
(71, 'Down Syndrome Screening', 'Blood', 175.00, 'Prenatal screening for Down syndrome risk.'),
(72, 'Amniocentesis', 'Amniotic fluid', 950.00, 'Tests amniotic fluid to diagnose chromosomal abnormalities in the fetus.'),
(73, 'Chorionic Villus Sampling (CVS)', 'Placental tissue', 950.00, 'Tests placental tissue to diagnose chromosomal abnormalities in the fetus.'),
(74, 'Non-Invasive Prenatal Testing (NIPT)', 'Blood', 795.00, 'Analyzes fetal DNA in maternal blood to screen for chromosomal abnormalities.'),
(75, 'Paternity Test', 'Blood or cheek swab', 350.00, 'Compares DNA to determine biological parentage.'),
(76, 'Drug Panel (5-panel)', 'Urine', 65.00, 'Screens for common drugs of abuse in urine.'),
(77, 'Drug Panel (10-panel)', 'Urine', 95.00, 'Comprehensive screening for drugs of abuse in urine.'),
(78, 'Alcohol Level (Blood)', 'Blood', 45.00, 'Measures blood alcohol concentration.'),
(79, 'Blood Lead Level', 'Blood', 55.00, 'Measures lead concentration in blood to detect lead poisoning.'),
(80, 'Mercury Level', 'Blood', 75.00, 'Measures mercury concentration in blood to detect mercury poisoning.'),
(81, 'Arsenic Level', 'Blood', 75.00, 'Measures arsenic concentration in blood to detect arsenic poisoning.'),
(82, 'Cadmium Level', 'Blood', 75.00, 'Measures cadmium concentration in blood to detect cadmium exposure.'),
(83, 'Pesticide Screen', 'Blood or urine', 145.00, 'Tests for pesticide metabolites in blood or urine.'),
(84, 'Allergen-Specific IgE Test', 'Blood', 85.00, 'Tests for specific allergen sensitivities by measuring IgE antibodies.'),
(85, 'Tryptase Test', 'Blood', 95.00, 'Measures tryptase enzyme to diagnose mast cell disorders and anaphylaxis.'),
(86, 'Immunoglobulin Panel', 'Blood', 125.00, 'Measures various antibody types (IgG, IgM, IgA, IgE) to evaluate immune function.'),
(87, 'Complement C3 and C4', 'Blood', 85.00, 'Measures complement proteins to evaluate immune function.'),
(88, 'CD4 Count', 'Blood', 125.00, 'Counts CD4 T-cells to monitor HIV progression and immune function.'),
(89, 'CD8 Count', 'Blood', 125.00, 'Counts CD8 T-cells to evaluate immune function.'),
(90, 'Antistreptolysin O Titer (ASO)', 'Blood', 55.00, 'Tests for antibodies to streptococcal bacteria to diagnose recent infection.'),
(91, 'Rubella Antibody Test', 'Blood', 45.00, 'Tests for immunity to rubella (German measles).'),
(92, 'Varicella Antibody Test', 'Blood', 45.00, 'Tests for immunity to varicella (chickenpox).'),
(93, 'Measles Antibody Test', 'Blood', 45.00, 'Tests for immunity to measles.'),
(94, 'Mumps Antibody Test', 'Blood', 45.00, 'Tests for immunity to mumps.'),
(95, 'Tuberculosis Skin Test (PPD)', 'Skin', 35.00, 'Screens for tuberculosis infection.'),
(96, 'QuantiFERON-TB Gold Test', 'Blood', 95.00, 'Blood test to detect tuberculosis infection.'),
(97, 'Malarial Parasite Test', 'Blood', 65.00, 'Microscopic examination of blood to detect malaria parasites.'),
(98, 'Stool Ova and Parasite Test', 'Stool', 75.00, 'Examines stool for parasitic eggs and organisms.'),
(99, 'Stool Culture', 'Stool', 65.00, 'Tests stool for bacterial infections.'),
(100, 'C. difficile Toxin Test', 'Stool', 85.00, 'Tests stool for C. difficile toxins to diagnose infection.'),
(101, 'Rotavirus Test', 'Stool', 50.00, 'Tests stool for rotavirus to diagnose infection.'),
(102, 'Norovirus Test', 'Stool', 65.00, 'Tests stool for norovirus to diagnose infection.'),
(103, 'Fecal Occult Blood Test', 'Stool', 25.00, 'Tests stool for hidden blood to screen for colorectal cancer.'),
(104, 'Fecal Immunochemical Test (FIT)', 'Stool', 35.00, 'Tests stool for blood to screen for colorectal cancer.'),
(105, 'Fecal Calprotectin', 'Stool', 95.00, 'Measures inflammation in digestive tract to diagnose inflammatory bowel disease.'),
(106, 'Lactose Tolerance Test', 'Blood', 85.00, 'Tests for lactose intolerance by measuring blood glucose after lactose consumption.'),
(107, 'Hydrogen Breath Test', 'Breath', 125.00, 'Tests for lactose or fructose intolerance by measuring hydrogen in breath.'),
(108, 'Celiac Disease Panel', 'Blood', 145.00, 'Tests for antibodies associated with celiac disease.'),
(109, 'Tissue Transglutaminase Antibody (tTG-IgA)', 'Blood', 75.00, 'Tests for antibodies associated with celiac disease.'),
(110, 'Sweat Chloride Test', 'Sweat', 175.00, 'Measures chloride in sweat to diagnose cystic fibrosis.'),
(111, 'Spirometry', 'Breath', 95.00, 'Measures lung function to diagnose respiratory disorders.'),
(112, 'Pulse Oximetry', 'Blood', 25.00, 'Measures oxygen saturation in blood.'),
(113, 'Arterial Blood Gas (ABG)', 'Blood', 95.00, 'Measures oxygen and carbon dioxide levels in arterial blood.'),
(114, 'Methacholine Challenge Test', 'Breath', 175.00, 'Tests for asthma by measuring lung function after methacholine exposure.'),
(115, 'Sleep Study (Polysomnogram)', 'Multiple', 1500.00, 'Records brain waves, oxygen levels, and breathing during sleep to diagnose sleep disorders.'),
(116, 'Electrocardiogram (ECG/EKG)', 'Electrical activity', 85.00, 'Records electrical activity of the heart to detect abnormalities.'),
(117, 'Echocardiogram', 'Ultrasound', 450.00, 'Uses ultrasound to image the heart and evaluate function.'),
(118, 'Stress Test', 'Heart function', 350.00, 'Evaluates heart function during exercise.'),
(119, 'Holter Monitor', 'Electrical activity', 225.00, 'Records heart rhythm continuously for 24-48 hours.'),
(120, 'Electroencephalogram (EEG)', 'Electrical activity', 350.00, 'Records electrical activity in the brain to diagnose seizures and other disorders.'),
(121, 'Electromyography (EMG)', 'Electrical activity', 375.00, 'Tests electrical activity of muscles to diagnose neuromuscular disorders.'),
(122, 'Nerve Conduction Study', 'Electrical activity', 325.00, 'Tests nerve function to diagnose neuropathy and other disorders.'),
(123, 'Lumbar Puncture Analysis', 'Cerebrospinal fluid', 550.00, 'Analyzes cerebrospinal fluid to diagnose neurological disorders.'),
(124, 'Bone Density Test (DEXA Scan)', 'Imaging', 225.00, 'Measures bone density to diagnose osteoporosis.'),
(125, 'Mammogram', 'Imaging', 175.00, 'X-ray imaging of breast tissue to screen for breast cancer.'),
(126, 'Pap Smear', 'Cervical cells', 85.00, 'Screens for cervical cancer by examining cervical cells.'),
(127, 'Colonoscopy', 'Visual examination', 1250.00, 'Examines the colon to screen for colorectal cancer and other disorders.'),
(128, 'Upper Endoscopy', 'Visual examination', 1150.00, 'Examines the upper digestive tract to diagnose disorders.'),
(129, 'Bronchoscopy', 'Visual examination', 1350.00, 'Examines the airways to diagnose lung disorders.'),
(130, 'Cystoscopy', 'Visual examination', 1250.00, 'Examines the bladder and urethra to diagnose urinary disorders.'),
(131, 'Colposcopy', 'Visual examination', 350.00, 'Examines the cervix to diagnose abnormalities.'),
(132, 'Audiometry', 'Sound waves', 125.00, 'Tests hearing function to diagnose hearing loss.'),
(133, 'Tympanometry', 'Sound waves', 95.00, 'Tests middle ear function to diagnose ear disorders.'),
(134, 'Visual Acuity Test', 'Visual examination', 65.00, 'Tests vision sharpness to diagnose vision problems.'),
(135, 'Tonometry', 'Eye pressure', 45.00, 'Measures intraocular pressure to screen for glaucoma.'),
(136, 'Retinal Imaging', 'Imaging', 125.00, 'Photographs the retina to diagnose eye disorders.'),
(137, 'Fluorescein Angiography', 'Imaging', 350.00, 'Images blood vessels in the eye to diagnose retinal disorders.'),
(138, 'Visual Field Test', 'Visual examination', 95.00, 'Tests peripheral vision to diagnose glaucoma and other disorders.'),
(139, 'Slit Lamp Examination', 'Visual examination', 85.00, 'Examines eye structures to diagnose disorders.'),
(140, 'Skin Biopsy', 'Tissue', 225.00, 'Removes and examines skin tissue to diagnose disorders.'),
(141, 'Bone Marrow Biopsy', 'Tissue', 750.00, 'Removes and examines bone marrow to diagnose disorders.'),
(142, 'Lymph Node Biopsy', 'Tissue', 650.00, 'Removes and examines lymph node tissue to diagnose disorders.'),
(143, 'Liver Biopsy', 'Tissue', 850.00, 'Removes and examines liver tissue to diagnose disorders.'),
(144, 'Kidney Biopsy', 'Tissue', 850.00, 'Removes and examines kidney tissue to diagnose disorders.'),
(145, 'Muscle Biopsy', 'Tissue', 650.00, 'Removes and examines muscle tissue to diagnose disorders.'),
(146, 'Fine Needle Aspiration', 'Tissue', 350.00, 'Removes and examines cells from a lump or mass to diagnose disorders.'),
(147, 'Patch Test', 'Skin', 175.00, 'Tests for skin allergies by applying allergens to the skin.'),
(148, 'Erythropoietin (EPO) Level', 'Blood', 85.00, 'Measures EPO hormone to diagnose anemia and other disorders.'),
(149, 'Hemoglobin Electrophoresis', 'Blood', 95.00, 'Separates hemoglobin types to diagnose hemoglobinopathies.'),
(150, 'Homocysteine Level', 'Blood', 75.00, 'Measures homocysteine to assess cardiovascular risk.'),
(151, 'Lipoprotein(a) Test', 'Blood', 65.00, 'Measures Lp(a) to assess cardiovascular risk.'),
(152, 'apoB Test', 'Blood', 65.00, 'Measures apolipoprotein B to assess cardiovascular risk.'),
(153, 'C-peptide Test', 'Blood', 85.00, 'Measures C-peptide to evaluate insulin production.'),
(154, 'Insulin Level', 'Blood', 75.00, 'Measures insulin to diagnose hypoglycemia and insulin resistance.'),
(155, 'DHEA-Sulfate Level', 'Blood', 75.00, 'Measures DHEA-S hormone to diagnose adrenal disorders.'),
(156, 'Prolactin Level', 'Blood', 75.00, 'Measures prolactin hormone to diagnose pituitary disorders.'),
(157, 'Growth Hormone Level', 'Blood', 95.00, 'Measures growth hormone to diagnose pituitary disorders.'),
(158, 'IGF-1 Level', 'Blood', 95.00, 'Measures insulin-like growth factor 1 to diagnose growth disorders.'),
(159, 'Aldosterone Level', 'Blood', 95.00, 'Measures aldosterone hormone to diagnose adrenal disorders.'),
(160, 'Renin Activity', 'Blood', 85.00, 'Measures renin enzyme to diagnose hypertension and adrenal disorders.'),
(161, 'Catecholamines Test', 'Blood or urine', 125.00, 'Measures epinephrine and norepinephrine to diagnose adrenal disorders.'),
(162, 'Metanephrines Test', 'Blood or urine', 125.00, 'Measures metanephrines to diagnose pheochromocytoma.'),
(163, '17-Hydroxyprogesterone', 'Blood', 85.00, 'Measures 17-OHP to diagnose congenital adrenal hyperplasia.'),
(164, 'Androstenedione Level', 'Blood', 85.00, 'Measures androstenedione hormone to diagnose adrenal and gonadal disorders.'),
(165, 'Dehydroepiandrosterone (DHEA) Level', 'Blood', 85.00, 'Measures DHEA hormone to diagnose adrenal disorders.'),
(166, 'Sex Hormone Binding Globulin (SHBG)', 'Blood', 75.00, 'Measures SHBG protein to evaluate hormone levels.'),
(167, 'Free Testosterone', 'Blood', 85.00, 'Measures unbound testosterone to evaluate hormonal health.'),
(168, 'Total Testosterone', 'Blood', 75.00, 'Measures total testosterone to evaluate hormonal health.'),
(169, 'Free Estradiol', 'Blood', 85.00, 'Measures unbound estradiol to evaluate hormonal health.'),
(170, 'Total Estradiol', 'Blood', 75.00, 'Measures total estradiol to evaluate hormonal health.'),
(171, 'Progesterone Level', 'Blood', 75.00, 'Measures progesterone to evaluate fertility and pregnancy health.'),
(172, 'Luteinizing Hormone (LH)', 'Blood', 75.00, 'Measures LH to evaluate fertility and menopause.'),
(173, 'Follicle-Stimulating Hormone (FSH)', 'Blood', 75.00, 'Measures FSH to evaluate fertility and menopause.'),
(174, 'Anti-Müllerian Hormone (AMH)', 'Blood', 125.00, 'Measures AMH to evaluate ovarian reserve.'),
(175, 'Inhibin B', 'Blood', 125.00, 'Measures inhibin B to evaluate ovarian reserve.'),
(176, 'Estrone Level', 'Blood', 85.00, 'Measures estrone hormone to evaluate hormonal health.'),
(177, 'Estriol Level', 'Blood', 85.00, 'Measures estriol hormone to evaluate pregnancy health.'),
(178, 'Human Chorionic Gonadotropin (hCG)', 'Blood or urine', 65.00, 'Measures hCG hormone to confirm pregnancy and monitor certain cancers.'),
(179, 'PAPP-A Test', 'Blood', 85.00, 'Measures pregnancy-associated plasma protein-A for prenatal screening.'),
(180, 'Alpha-Fetoprotein (AFP) Maternal', 'Blood', 85.00, 'Measures AFP in maternal blood for prenatal screening.'),
(181, 'Unconjugated Estriol (uE3)', 'Blood', 85.00, 'Measures estriol for prenatal screening.'),
(182, 'Fetal Fibronectin', 'Cervical swab', 175.00, 'Tests for fetal fibronectin to assess risk of preterm birth.'),
(183, 'Group B Streptococcus (GBS) Screen', 'Vaginal swab', 65.00, 'Tests for GBS in pregnant women to prevent transmission to newborns.'),
(184, 'Fragile X Syndrome Test', 'Blood', 350.00, 'Tests for FMR1 gene mutations associated with Fragile X syndrome.'),
(185, 'Factor V Leiden Test', 'Blood', 225.00, 'Tests for Factor V Leiden mutation which increases clotting risk.'),
(186, 'Prothrombin G20210A Test', 'Blood', 225.00, 'Tests for prothrombin gene mutation which increases clotting risk.'),
(187, 'MTHFR Gene Test', 'Blood', 225.00, 'Tests for MTHFR gene mutations associated with various disorders.'),
(188, 'HLA-B27 Test', 'Blood', 175.00, 'Tests for HLA-B27 antigen associated with ankylosing spondylitis.'),
(189, 'HLA Typing', 'Blood', 350.00, 'Tests for HLA antigens to determine transplant compatibility.'),
(190, 'Crossmatch Test', 'Blood', 175.00, 'Tests donor and recipient blood compatibility for transfusion.'),
(191, 'Direct Coombs Test', 'Blood', 65.00, 'Tests for antibodies attached to red blood cells to diagnose hemolytic anemia.'),
(192, 'Indirect Coombs Test', 'Blood', 65.00, 'Tests for antibodies in serum to diagnose hemolytic disease of the newborn.'),
(193, 'Reticulocyte Count', 'Blood', 45.00, 'Counts immature red blood cells to evaluate bone marrow function.'),
(194, 'Peripheral Blood Smear', 'Blood', 55.00, 'Microscopic examination of blood cells to diagnose disorders.'),
(195, 'Platelet Function Test', 'Blood', 95.00, 'Tests platelet function to diagnose bleeding disorders.'),
(196, 'von Willebrand Factor (vWF) Test', 'Blood', 125.00, 'Tests for vWF to diagnose von Willebrand disease.'),
(197, 'Factor VIII Assay', 'Blood', 125.00, 'Measures Factor VIII to diagnose hemophilia A.'),
(198, 'Factor IX Assay', 'Blood', 125.00, 'Measures Factor IX to diagnose hemophilia B.'),
(199, 'Factor XIII Assay', 'Blood', 125.00, 'Measures Factor XIII to diagnose Factor XIII deficiency.'),
(200, 'Protein C Activity', 'Blood', 95.00, 'Measures Protein C to diagnose clotting disorders.'),
(201, 'Protein S Activity', 'Blood', 95.00, 'Measures Protein S to diagnose clotting disorders.'),
(202, 'Antithrombin III Activity', 'Blood', 95.00, 'Measures antithrombin III to diagnose clotting disorders.'),
(203, 'Fibrinogen Level', 'Blood', 65.00, 'Measures fibrinogen to diagnose bleeding and clotting disorders.'),
(204, 'Activated Protein C Resistance', 'Blood', 95.00, 'Tests for resistance to activated protein C to diagnose clotting disorders.'),
(205, 'Lupus Anticoagulant Test', 'Blood', 95.00, 'Tests for lupus anticoagulant to diagnose antiphospholipid syndrome.'),
(206, 'Anticardiolipin Antibody Test', 'Blood', 95.00, 'Tests for anticardiolipin antibodies to diagnose antiphospholipid syndrome.'),
(207, 'Anti-Beta-2 Glycoprotein Antibody', 'Blood', 95.00, 'Tests for anti-β2GP1 antibodies to diagnose antiphospholipid syndrome.'),
(208, 'Anti-dsDNA Antibody', 'Blood', 85.00, 'Tests for anti-dsDNA antibodies to diagnose lupus.'),
(209, 'Anti-Sm Antibody', 'Blood', 85.00, 'Tests for anti-Sm antibodies to diagnose lupus.'),
(210, 'Anti-RNP Antibody', 'Blood', 85.00, 'Tests for anti-RNP antibodies to diagnose mixed connective tissue disease.'),
(211, 'Anti-Ro/SSA Antibody', 'Blood', 85.00, 'Tests for anti-Ro antibodies to diagnose syndrome and lupus.'),
(212, 'Anti-La/SSB Antibody', 'Blood', 85.00, 'Tests for anti-La antibodies to diagnose syndrome and lupus.'),
(213, 'Anti-Scl-70 Antibody', 'Blood', 85.00, 'Tests for anti-Scl-70 antibodies to diagnose scleroderma.'),
(214, 'Anti-Centromere Antibody', 'Blood', 85.00, 'Tests for anti-centromere antibodies to diagnose limited scleroderma.'),
(215, 'Anti-Jo-1 Antibody', 'Blood', 85.00, 'Tests for anti-Jo-1 antibodies to diagnose polymyositis.'),
(216, 'Anti-CCP Antibody', 'Blood', 95.00, 'Tests for anti-CCP antibodies to diagnose rheumatoid arthritis.'),
(217, 'Anti-Neutrophil Cytoplasmic Antibody (ANCA)', 'Blood', 95.00, 'Tests for ANCA to diagnose vasculitis.'),
(218, 'Anti-Glomerular Basement Membrane Antibody', 'Blood', 95.00, 'Tests for anti-GBM antibodies to diagnose Goodpasture syndrome.'),
(219, 'Anti-Mitochondrial Antibody (AMA)', 'Blood', 85.00, 'Tests for AMA to diagnose primary biliary cholangitis.'),
(220, 'Anti-Smooth Muscle Antibody (ASMA)', 'Blood', 85.00, 'Tests for ASMA to diagnose autoimmune hepatitis.'),
(221, 'Anti-Liver Kidney Microsomal Antibody (anti-LKM)', 'Blood', 85.00, 'Tests for anti-LKM antibodies to diagnose autoimmune hepatitis.'),
(222, 'Anti-Parietal Cell Antibody', 'Blood', 85.00, 'Tests for anti-parietal cell antibodies to diagnose pernicious anemia.'),
(223, 'Anti-Intrinsic Factor Antibody', 'Blood', 85.00, 'Tests for anti-intrinsic factor antibodies to diagnose pernicious anemia.'),
(224, 'Anti-Islet Cell Antibody', 'Blood', 95.00, 'Tests for anti-islet cell antibodies to diagnose type 1 diabetes.'),
(225, 'Anti-Insulin Antibody', 'Blood', 95.00, 'Tests for anti-insulin antibodies to diagnose insulin autoimmune syndrome.'),
(226, 'Anti-GAD65 Antibody', 'Blood', 95.00, 'Tests for anti-GAD65 antibodies to diagnose type 1 diabetes.'),
(227, 'Anti-IA2 Antibody', 'Blood', 95.00, 'Tests for anti-IA2 antibodies to diagnose type 1 diabetes.'),
(228, 'Anti-Thyroid Peroxidase Antibody (anti-TPO)', 'Blood', 75.00, 'Tests for anti-TPO antibodies to diagnose autoimmune thyroid disease.'),
(229, 'Anti-Thyroglobulin Antibody (anti-Tg)', 'Blood', 75.00, 'Tests for anti-Tg antibodies to diagnose autoimmune thyroid disease.'),
(230, 'Anti-TSH Receptor Antibody (TRAb)', 'Blood', 95.00, 'Tests for TRAb to diagnose Graves disease.'),
(231, 'Anti-Phospholipid Antibody Panel', 'Blood', 175.00, 'Tests for various phospholipid antibodies to diagnose antiphospholipid syndrome.'),
(232, 'Angiotensin-Converting Enzyme (ACE)', 'Blood', 75.00, 'Measures ACE levels to diagnose sarcoidosis.'),
(233, 'Alpha-1 Antitrypsin Level', 'Blood', 75.00, 'Measures alpha-1 antitrypsin to diagnose alpha-1 antitrypsin deficiency.'),
(234, 'Alpha-1 Antitrypsin Phenotyping', 'Blood', 175.00, 'Determines alpha-1 antitrypsin variants to diagnose alpha-1 antitrypsin deficiency.'),
(235, 'Ceruloplasmin Level', 'Blood', 75.00, 'Measures ceruloplasmin to diagnose Wilsons disease.'),
(236, 'Copper Level (Serum)', 'Blood', 65.00, 'Measures serum copper to diagnose Wilsons disease and other disorders.'),
(237, 'Copper Level (Urine)', 'Urine', 65.00, 'Measures urine copper to diagnose Wilsons disease.'),
(238, 'Zinc Level', 'Blood', 65.00, 'Measures zinc to diagnose deficiency or excess.'),
(239, 'Selenium Level', 'Blood', 75.00, 'Measures selenium to diagnose deficiency or excess.'),
(240, 'Phosphorus Level', 'Blood', 35.00, 'Measures phosphorus to diagnose metabolic and kidney disorders.'),
(241, 'Ammonia Level', 'Blood', 55.00, 'Measures ammonia to diagnose liver disorders and urea cycle defects.'),
(242, 'Lactic Acid Level', 'Blood', 55.00, 'Measures lactic acid to diagnose lactic acidosis.'),
(243, 'Pyruvate Level', 'Blood', 65.00, 'Measures pyruvate to diagnose metabolic disorders.'),
(244, 'Beta-Hydroxybutyrate', 'Blood', 65.00, 'Measures ketone body to diagnose diabetic ketoacidosis.'),
(245, 'Acetone Level', 'Blood', 55.00, 'Measures ketone body to diagnose diabetic ketoacidosis.'),
(246, 'pH (Blood)', 'Blood', 45.00, 'Measures blood pH to diagnose acid-base disorders.'),
(247, 'pH (Urine)', 'Urine', 25.00, 'Measures urine pH to diagnose kidney and metabolic disorders.'),
(248, 'Osmolality (Serum)', 'Blood', 55.00, 'Measures serum osmolality to diagnose kidney and metabolic disorders.'),
(249, 'Osmolality (Urine)', 'Urine', 55.00, 'Measures urine osmolality to diagnose kidney and endocrine disorders.'),
(250, 'Specific Gravity (Urine)', 'Urine', 25.00, 'Measures urine concentration to evaluate kidney function.'),
(251, 'Creatinine Clearance', 'Blood/Urine', 75.00, 'Measures kidney function by comparing blood and urine creatinine.'),
(252, 'Microalbumin (Urine)', 'Urine', 45.00, 'Measures small amounts of albumin in urine to detect early kidney damage.'),
(253, 'Protein/Creatinine Ratio (Urine)', 'Urine', 55.00, 'Measures protein excretion to evaluate kidney disease.'),
(254, '24-Hour Urine Protein', 'Urine', 85.00, 'Measures protein excretion over 24 hours to evaluate kidney disease.'),
(255, 'Cystatin C', 'Blood', 85.00, 'Measures cystatin C protein to evaluate kidney function.'),
(256, 'Estimated Glomerular Filtration Rate (eGFR)', 'Blood', 25.00, 'Calculated measurement of kidney function based on creatinine and other factors.'),
(257, 'Beta-2 Microglobulin', 'Blood/Urine', 75.00, 'Measures β2M protein to evaluate kidney function and certain cancers.'),
(258, 'N-Acetyl-Beta-D-Glucosaminidase (NAG)', 'Urine', 85.00, 'Measures NAG enzyme in urine to detect early kidney damage.'),
(259, 'Vanillylmandelic Acid (VMA)', 'Urine', 95.00, 'Measures VMA in urine to diagnose pheochromocytoma and neuroblastoma.'),
(260, 'Homovanillic Acid (HVA)', 'Urine', 95.00, 'Measures HVA in urine to diagnose neuroblastoma.'),
(261, '5-Hydroxyindoleacetic Acid (5-HIAA)', 'Urine', 95.00, 'Measures 5-HIAA in urine to diagnose carcinoid syndrome.'),
(262, 'Porphyrins (Urine)', 'Urine', 125.00, 'Measures porphyrins in urine to diagnose porphyrias.'),
(263, 'Delta-Aminolevulinic Acid (ALA)', 'Urine', 95.00, 'Measures ALA in urine to diagnose porphyrias and lead poisoning.'),
(264, 'Bilirubin (Urine)', 'Urine', 35.00, 'Tests for bilirubin in urine to diagnose liver and bile duct disorders.'),
(265, 'Urobilinogen (Urine)', 'Urine', 35.00, 'Tests for urobilinogen in urine to diagnose liver and bile duct disorders.'),
(266, 'Nitrite (Urine)', 'Urine', 25.00, 'Tests for nitrites in urine to detect bacterial infection.'),
(267, 'Leukocyte Esterase (Urine)', 'Urine', 25.00, 'Tests for white blood cells in urine to detect infection.'),
(268, 'Ketones (Urine)', 'Urine', 25.00, 'Tests for ketones in urine to diagnose diabetic ketoacidosis.'),
(269, 'Glucose (Urine)', 'Urine', 25.00, 'Tests for glucose in urine to diagnose diabetes and kidney disorders.'),
(270, 'Bence Jones Protein', 'Urine', 75.00, 'Tests for Bence Jones proteins in urine to diagnose multiple myeloma.'),
(271, 'Serum Protein Electrophoresis (SPEP)', 'Blood', 95.00, 'Separates proteins in blood to diagnose multiple myeloma and other disorders.'),
(272, 'Urine Protein Electrophoresis (UPEP)', 'Urine', 95.00, 'Separates proteins in urine to diagnose multiple myeloma and other disorders.'),
(273, 'Immunofixation Electrophoresis (IFE)', 'Blood/Urine', 125.00, 'Identifies specific immunoglobulins to diagnose multiple myeloma and related disorders.'),
(274, 'Serum Free Light Chain Assay', 'Blood', 175.00, 'Measures kappa and lambda light chains to diagnose and monitor multiple myeloma.'),
(275, 'Haptoglobin Level', 'Blood', 65.00, 'Measures haptoglobin protein to diagnose hemolytic anemia.'),
(276, 'Lactate Dehydrogenase (LDH)', 'Blood', 45.00, 'Measures LDH enzyme to diagnose tissue damage and certain cancers.'),
(277, 'Aspartate Aminotransferase (AST)', 'Blood', 35.00, 'Measures AST enzyme to diagnose liver damage.'),
(278, 'Alanine Aminotransferase (ALT)', 'Blood', 35.00, 'Measures ALT enzyme to diagnose liver damage.'),
(279, 'Alkaline Phosphatase (ALP)', 'Blood', 35.00, 'Measures ALP enzyme to diagnose liver and bone disorders.'),
(280, 'Gamma-Glutamyl Transferase (GGT)', 'Blood', 35.00, 'Measures GGT enzyme to diagnose liver and bile duct disorders.'),
(281, '5\'-Nucleotidase', 'Blood', 55.00, 'Measures 5\'-nucleotidase enzyme to diagnose liver and bile duct disorders.'),
(282, 'Total Bilirubin', 'Blood', 35.00, 'Measures total bilirubin to diagnose liver and bile duct disorders.'),
(283, 'Direct Bilirubin', 'Blood', 35.00, 'Measures conjugated bilirubin to diagnose liver and bile duct disorders.'),
(284, 'Indirect Bilirubin', 'Blood', 35.00, 'Measures unconjugated bilirubin to diagnose liver and bile duct disorders.'),
(285, 'Albumin (Serum)', 'Blood', 35.00, 'Measures albumin protein to evaluate liver function and nutritional status.'),
(286, 'Total Protein (Serum)', 'Blood', 35.00, 'Measures all proteins in blood to evaluate liver function and nutritional status.'),
(287, 'Albumin/Globulin Ratio', 'Blood', 25.00, 'Calculates ratio of albumin to globulin proteins to evaluate liver function.'),
(288, 'Prealbumin', 'Blood', 75.00, 'Measures prealbumin protein to evaluate nutritional status.'),
(289, 'Transferrin', 'Blood', 65.00, 'Measures transferrin protein to evaluate iron status and nutritional status.'),
(290, 'Total Iron Binding Capacity (TIBC)', 'Blood', 55.00, 'Measures iron binding capacity to evaluate iron status.'),
(291, 'Unsaturated Iron Binding Capacity (UIBC)', 'Blood', 55.00, 'Measures unbound iron binding capacity to evaluate iron status.'),
(292, 'Transferrin Saturation', 'Blood', 25.00, 'Calculates percentage of transferrin saturated with iron to evaluate iron status.'),
(293, 'Hemoglobin', 'Blood', 25.00, 'Measures hemoglobin protein to diagnose anemia and polycythemia.'),
(294, 'Hematocrit', 'Blood', 25.00, 'Measures percentage of blood composed of red blood cells to diagnose anemia and polycythemia.'),
(295, 'Red Blood Cell Count (RBC)', 'Blood', 25.00, 'Counts red blood cells to diagnose anemia and polycythemia.'),
(296, 'White Blood Cell Count (WBC)', 'Blood', 25.00, 'Counts white blood cells to diagnose infection and blood disorders.'),
(297, 'Platelet Count', 'Blood', 25.00, 'Counts platelets to diagnose bleeding disorders and thrombocytosis.'),
(298, 'Mean Corpuscular Volume (MCV)', 'Blood', 25.00, 'Measures average size of red blood cells to classify anemia.'),
(299, 'Mean Corpuscular Hemoglobin (MCH)', 'Blood', 25.00, 'Measures average hemoglobin content of red blood cells to classify anemia.'),
(300, 'Mean Corpuscular Hemoglobin Concentration (MCHC)', 'Blood', 25.00, 'Measures average hemoglobin concentration in red blood cells to classify anemia.'),
(301, 'Red Cell Distribution Width (RDW)', 'Blood', 25.00, 'Measures variation in red blood cell size to diagnose anemia.'),
(302, 'Mean Platelet Volume (MPV)', 'Blood', 25.00, 'Measures average size of platelets to evaluate platelet production.'),
(303, 'Neutrophil Count', 'Blood', 25.00, 'Counts neutrophils to diagnose infection and immune disorders.'),
(304, 'Lymphocyte Count', 'Blood', 25.00, 'Counts lymphocytes to diagnose infection and immune disorders.'),
(305, 'Monocyte Count', 'Blood', 25.00, 'Counts monocytes to diagnose infection and immune disorders.'),
(306, 'Eosinophil Count', 'Blood', 25.00, 'Counts eosinophils to diagnose allergies and parasitic infections.'),
(307, 'Basophil Count', 'Blood', 25.00, 'Counts basophils to diagnose allergic reactions and leukemia.'),
(308, 'Neutrophil Percentage', 'Blood', 25.00, 'Calculates percentage of neutrophils in white blood cells.'),
(309, 'Lymphocyte Percentage', 'Blood', 25.00, 'Calculates percentage of lymphocytes in white blood cells.'),
(310, 'Monocyte Percentage', 'Blood', 25.00, 'Calculates percentage of monocytes in white blood cells.'),
(311, 'Eosinophil Percentage', 'Blood', 25.00, 'Calculates percentage of eosinophils in white blood cells.'),
(312, 'Basophil Percentage', 'Blood', 25.00, 'Calculates percentage of basophils in white blood cells.'),
(313, 'Nucleated Red Blood Cell Count', 'Blood', 35.00, 'Counts immature red blood cells in peripheral blood to diagnose severe anemia.'),
(314, 'Immature Granulocyte Count', 'Blood', 35.00, 'Counts immature white blood cells to diagnose infection and leukemia.'),
(315, 'Absolute Neutrophil Count (ANC)', 'Blood', 25.00, 'Calculates total neutrophil count to evaluate infection risk.'),
(316, 'Absolute Lymphocyte Count (ALC)', 'Blood', 25.00, 'Calculates total lymphocyte count to evaluate immune function.'),
(317, 'Immature Platelet Fraction (IPF)', 'Blood', 35.00, 'Measures percentage of immature platelets to evaluate platelet production.'),
(318, 'Fibrin D-dimer', 'Plasma', 75.00, 'Measures D-dimer protein to diagnose thrombosis and DIC.'),
(319, 'Fibrin Degradation Products (FDP)', 'Plasma', 75.00, 'Measures FDPs to diagnose thrombosis and DIC.'),
(320, 'Thrombin Time', 'Plasma', 55.00, 'Measures time for thrombin to convert fibrinogen to fibrin to diagnose coagulation disorders.'),
(321, 'Reptilase Time', 'Plasma', 65.00, 'Measures time for reptilase to convert fibrinogen to fibrin to diagnose fibrinogen disorders.'),
(322, 'Factor II Activity', 'Plasma', 95.00, 'Measures Factor II to diagnose bleeding disorders.'),
(323, 'Factor V Activity', 'Plasma', 95.00, 'Measures Factor V to diagnose bleeding disorders.'),
(324, 'Factor VII Activity', 'Plasma', 95.00, 'Measures Factor VII to diagnose bleeding disorders.'),
(325, 'Factor X Activity', 'Plasma', 95.00, 'Measures Factor X to diagnose bleeding disorders.'),
(326, 'Factor XI Activity', 'Plasma', 95.00, 'Measures Factor XI to diagnose bleeding disorders.'),
(327, 'Factor XII Activity', 'Plasma', 95.00, 'Measures Factor XII to diagnose bleeding disorders.'),
(328, 'Plasminogen Activity', 'Plasma', 95.00, 'Measures plasminogen to diagnose fibrinolytic disorders.'),
(329, 'Alpha-2-Antiplasmin Activity', 'Plasma', 95.00, 'Measures α2-antiplasmin to diagnose fibrinolytic disorders.'),
(330, 'Plasma Viscosity', 'Plasma', 65.00, 'Measures plasma viscosity to diagnose multiple myeloma and other disorders.'),
(331, 'Bleeding Time', 'Blood', 45.00, 'Measures time for a standardized skin puncture to stop bleeding.'),
(332, 'PFA-100 Closure Time', 'Blood', 95.00, 'Measures platelet function to diagnose platelet disorders.'),
(333, 'Thromboelastography (TEG)', 'Blood', 175.00, 'Measures viscoelastic properties of blood clotting to diagnose coagulation disorders.'),
(334, 'Rotational Thromboelastometry (ROTEM)', 'Blood', 195.00, 'Measures viscoelastic properties of blood clotting to diagnose coagulation disorders.'),
(335, 'Chromogenic Factor VIII Assay', 'Plasma', 145.00, 'Measures Factor VIII activity to diagnose hemophilia A.'),
(336, 'Chromogenic Factor IX Assay', 'Plasma', 145.00, 'Measures Factor IX activity to diagnose hemophilia B.'),
(337, 'Chromogenic Factor X Assay', 'Plasma', 145.00, 'Measures Factor X activity to diagnose Factor X deficiency.'),
(338, 'von Willebrand Factor Antigen', 'Plasma', 125.00, 'Measures vWF protein to diagnose von Willebrand disease.'),
(339, 'von Willebrand Factor Activity', 'Plasma', 145.00, 'Measures vWF function to diagnose von Willebrand disease.'),
(340, 'von Willebrand Factor Multimers', 'Plasma', 175.00, 'Analyzes vWF multimer structure to diagnose von Willebrand disease.'),
(341, 'ADAMTS13 Activity', 'Plasma', 195.00, 'Measures ADAMTS13 enzyme to diagnose thrombotic thrombocytopenic purpura.'),
(342, 'Anti-ADAMTS13 Antibody', 'Serum', 195.00, 'Tests for antibodies against ADAMTS13 to diagnose acquired TTP.'),
(343, 'JAK2 V617F Mutation', 'Blood', 275.00, 'Tests for JAK2 gene mutation to diagnose myeloproliferative neoplasms.'),
(344, 'BCR-ABL1 Test', 'Blood', 295.00, 'Tests for BCR-ABL1 fusion gene to diagnose chronic myeloid leukemia.'),
(345, 'PML-RARA Test', 'Blood', 295.00, 'Tests for PML-RARA fusion gene to diagnose acute promyelocytic leukemia.'),
(346, 'FLT3 Mutation', 'Blood', 275.00, 'Tests for FLT3 gene mutations to diagnose acute myeloid leukemia.'),
(347, 'NPM1 Mutation', 'Blood', 275.00, 'Tests for NPM1 gene mutations to diagnose acute myeloid leukemia.'),
(348, 'CEBPA Mutation', 'Blood', 275.00, 'Tests for CEBPA gene mutations to diagnose acute myeloid leukemia.'),
(349, 'IDH1/IDH2 Mutation', 'Blood', 275.00, 'Tests for IDH1/IDH2 gene mutations to diagnose acute myeloid leukemia.'),
(350, 'KIT Mutation', 'Blood', 275.00, 'Tests for KIT gene mutations to diagnose acute myeloid leukemia and GIST.'),
(351, 'MPL Mutation', 'Blood', 275.00, 'Tests for MPL gene mutations to diagnose myeloproliferative neoplasms.'),
(352, 'CALR Mutation', 'Blood', 275.00, 'Tests for CALR gene mutations to diagnose myeloproliferative neoplasms.'),
(353, 'ASXL1 Mutation', 'Blood', 275.00, 'Tests for ASXL1 gene mutations to diagnose myeloid neoplasms.'),
(354, 'TP53 Mutation', 'Blood', 275.00, 'Tests for TP53 gene mutations to diagnose various cancers.'),
(355, 'BRAF V600E Mutation', 'Blood', 275.00, 'Tests for BRAF V600E mutation to diagnose melanoma and other cancers.'),
(356, 'KRAS Mutation', 'Blood', 275.00, 'Tests for KRAS gene mutations to diagnose colorectal and lung cancers.'),
(357, 'NRAS Mutation', 'Blood', 275.00, 'Tests for NRAS gene mutations to diagnose melanoma and other cancers.'),
(358, 'EGFR Mutation', 'Tissue', 295.00, 'Tests for EGFR gene mutations to diagnose lung cancer.'),
(359, 'ALK Rearrangement', 'Tissue', 295.00, 'Tests for ALK gene rearrangements to diagnose lung cancer.'),
(360, 'ROS1 Rearrangement', 'Tissue', 295.00, 'Tests for ROS1 gene rearrangements to diagnose lung cancer.'),
(361, 'RET Rearrangement', 'Tissue', 295.00, 'Tests for RET gene rearrangements to diagnose lung and thyroid cancers.'),
(362, 'MET Amplification', 'Tissue', 295.00, 'Tests for MET gene amplification to diagnose lung cancer.'),
(363, 'HER2 Amplification', 'Tissue', 275.00, 'Tests for HER2 gene amplification to diagnose breast and gastric cancers.'),
(364, 'PIK3CA Mutation', 'Tissue', 275.00, 'Tests for PIK3CA gene mutations to diagnose breast and other cancers.'),
(365, 'BRCA1/BRCA2 Sequencing', 'Blood', 950.00, 'Sequences BRCA1/2 genes to assess hereditary breast and ovarian cancer risk.'),
(366, 'BRCA1/BRCA2 Deletion/Duplication', 'Blood', 750.00, 'Tests for large deletions/duplications in BRCA1/2 genes.'),
(367, 'Lynch Syndrome Gene Panel', 'Blood', 950.00, 'Tests for mutations in MLH1, MSH2, MSH6, PMS2, and EPCAM genes.'),
(368, 'Microsatellite Instability (MSI) Testing', 'Tissue', 275.00, 'Tests for microsatellite instability to diagnose Lynch syndrome.'),
(369, 'MLH1 Promoter Methylation', 'Tissue', 275.00, 'Tests for MLH1 gene methylation to diagnose colorectal cancer.'),
(370, 'PD-L1 Expression', 'Tissue', 295.00, 'Tests for PD-L1 protein expression to guide immunotherapy.'),
(371, 'Tumor Mutational Burden (TMB)', 'Tissue', 495.00, 'Measures number of mutations in tumor DNA to guide immunotherapy.'),
(372, 'Circulating Tumor DNA (ctDNA)', 'Blood', 495.00, 'Tests for tumor DNA in blood to monitor cancer and detect recurrence.'),
(373, 'Circulating Tumor Cells (CTC)', 'Blood', 495.00, 'Tests for cancer cells in blood to monitor cancer and detect recurrence.'),
(374, 'Next-Generation Sequencing Cancer Panel', 'Tissue', 1250.00, 'Sequences multiple cancer-related genes to guide targeted therapy.'),
(375, 'Whole Exome Sequencing', 'Blood', 2500.00, 'Sequences all protein-coding regions of the genome to diagnose genetic disorders.'),
(376, 'Whole Genome Sequencing', 'Blood', 3500.00, 'Sequences the entire genome to diagnose genetic disorders.'),
(377, 'Chromosomal Microarray', 'Blood', 1250.00, 'Tests for chromosomal deletions and duplications to diagnose genetic disorders.'),
(378, 'Karyotype', 'Blood', 650.00, 'Analyzes chromosomes to diagnose genetic disorders.'),
(379, 'Fluorescence In Situ Hybridization (FISH)', 'Blood', 375.00, 'Tests for specific chromosomal abnormalities to diagnose genetic disorders.'),
(380, 'Fragile X PCR', 'Blood', 275.00, 'Tests for FMR1 gene expansion to diagnose Fragile X syndrome.'),
(381, 'Duchenne/Becker Muscular Dystrophy Testing', 'blood', 375.00, 'Tests for dystrophin gene mutations to diagnose muscular dystrophy.'),
(382, 'Spinal Muscular Atrophy (SMA) Testing', 'blood', 275.00, 'Tests for SMN1 gene mutations to diagnose spinal muscular atrophy.'),
(383, 'Huntington Disease Testing', 'blood', 275.00, 'Tests for HTT gene expansion to diagnose Huntington disease.'),
(384, 'Myotonic Dystrophy Testing', 'blood', 275.00, 'Tests for DMPK gene expansion to diagnose myotonic dystrophy.'),
(385, 'Friedreich Ataxia Testing', 'blood', 275.00, 'Tests for FXN gene expansion to diagnose Friedreich ataxia.'),
(386, 'Spinocerebellar Ataxia Panel', 'blood', 750.00, 'Tests for gene mutations associated with spinocerebellar ataxias.'),
(387, 'Charcot-Marie-Tooth Disease Panel', 'blood', 750.00, 'Tests for gene mutations associated with Charcot-Marie-Tooth disease.'),
(388, 'Hereditary Neuropathy Panel', 'blood', 750.00, 'Tests for gene mutations associated with hereditary neuropathies.'),
(389, 'Limb-Girdle Muscular Dystrophy Panel', 'blood', 750.00, 'Tests for gene mutations associated with limb-girdle muscular dystrophy.'),
(390, 'Congenital Muscular Dystrophy Panel', 'blood', 750.00, 'Tests for gene mutations associated with congenital muscular dystrophy.'),
(391, 'Hereditary Spastic Paraplegia Panel', 'blood', 750.00, 'Tests for gene mutations associated with hereditary spastic paraplegia.'),
(392, 'Amyotrophic Lateral Sclerosis (ALS) Panel', 'blood', 750.00, 'Tests for gene mutations associated with ALS.'),
(393, 'Parkinson Disease Panel', 'blood', 750.00, 'Tests for gene mutations associated with Parkinson disease.'),
(394, 'Alzheimer Disease Panel', 'blood', 750.00, 'Tests for gene mutations associated with Alzheimer disease.'),
(395, 'Frontotemporal Dementia Panel', 'blood', 750.00, 'Tests for gene mutations associated with frontotemporal dementia.'),
(396, 'Epilepsy Panel', 'blood', 750.00, 'Tests for gene mutations associated with epilepsy.'),
(397, 'Retinitis Pigmentosa Panel', 'blood', 750.00, 'Tests for gene mutations associated with retinitis pigmentosa.');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer_name` varchar(255) NOT NULL,
  `pack_size` varchar(255) NOT NULL,
  `composition1` varchar(255) NOT NULL,
  `composition2` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `manufacturer_name`, `pack_size`, `composition1`, `composition2`, `price`) VALUES
(1, 'Augmentin 625 Duo Tablet', 'Glaxo SmithKline Pharmaceuticals Ltd', 'strip of 10 tablets', 'Amoxycillin  (500mg)', 'Clavulanic Acid (125mg)', 223.50),
(2, 'Azithral 500 Tablet', 'Alembic Pharmaceuticals Ltd', 'strip of 5 tablets', 'Azithromycin (500mg)', NULL, 189.75),
(3, 'Ascoril LS Syrup', 'Glenmark Pharmaceuticals Ltd', 'bottle of 100 ml Syrup', 'Ambroxol (30mg/5ml)', 'Levosalbutamol (1mg/5ml)', 98.50),
(4, 'Allegra 120mg Tablet', 'Sanofi India  Ltd', 'strip of 10 tablets', 'Fexofenadine (120mg)', NULL, 156.25),
(5, 'Avil 25 Tablet', 'Sanofi India  Ltd', 'strip of 15 tablets', 'Pheniramine (25mg)', NULL, 35.60),
(6, 'Allegra-M Tablet', 'Sanofi India  Ltd', 'strip of 10 tablets', 'Montelukast (10mg)', 'Fexofenadine (120mg)', 245.80),
(7, 'Amoxyclav 625 Tablet', 'Abbott', 'strip of 10 tablets', 'Amoxycillin  (500mg)', 'Clavulanic Acid (125mg)', 210.25),
(8, 'Azee 500 Tablet', 'Cipla Ltd', 'strip of 5 tablets', 'Azithromycin (500mg)', NULL, 185.90),
(9, 'Atarax 25mg Tablet', 'Dr Reddys Laboratories Ltd', 'strip of 15 tablets', 'Hydroxyzine (25mg)', NULL, 75.40),
(10, 'Ascoril D Plus Syrup Sugar Free', 'Glenmark Pharmaceuticals Ltd', 'bottle of 100 ml Syrup', 'Phenylephrine (5mg)', 'Chlorpheniramine Maleate (2mg)', 110.75),
(11, 'Aciloc 150 Tablet', 'Cadila Pharmaceuticals Ltd', 'strip of 30 tablets', 'Ranitidine (150mg)', NULL, 82.30),
(12, 'Alex Syrup', 'Glenmark Pharmaceuticals Ltd', 'bottle of 100 ml Syrup', 'Phenylephrine (5mg/5ml)', 'Chlorpheniramine Maleate (2mg/5ml)', 89.50),
(13, 'Anovate Cream', 'USV Ltd', 'tube of 20 gm Cream', 'Phenylephrine (0.10% w/w)', 'Beclometasone (0.025% w/w)', 125.60),
(14, 'Augmentin Duo Oral Suspension', 'Glaxo SmithKline Pharmaceuticals Ltd', 'bottle of 30 ml Oral Suspension', 'Amoxycillin  (200mg)', 'Clavulanic Acid (28.5mg)', 168.75),
(15, 'Ambrodil-S Syrup', 'Aristo Pharmaceuticals Pvt Ltd', 'bottle of 100 ml Syrup', 'Ambroxol (15mg/5ml)', 'Salbutamol (1mg/5ml)', 95.25),
(16, 'Arkamin Tablet', 'Torrent Pharmaceuticals Ltd', 'strip of 30 tablets', 'Clonidine (100mcg)', NULL, 145.50),
(17, 'Avomine Tablet', 'Abbott', 'strip of 10 tablets', 'Promethazine (25mg)', NULL, 42.80),
(18, 'Asthakind-DX Syrup Sugar Free', 'Mankind Pharma Ltd', 'bottle of 60 ml Syrup', 'Phenylephrine (5mg/5ml)', 'Chlorpheniramine Maleate (2mg/5ml)', 78.90),
(19, 'Allegra 180mg Tablet', 'Sanofi India  Ltd', 'strip of 10 tablets', 'Fexofenadine (180mg)', NULL, 186.40),
(20, 'Albendazole 400mg Tablet', 'Cadila Pharmaceuticals Ltd', 'strip of 1 Tablet', 'Albendazole (400mg)', NULL, 15.75),
(21, 'Asthalin Syrup', 'Cipla Ltd', 'bottle of 100 ml Syrup', 'Salbutamol (2mg/5ml)', NULL, 85.30),
(22, 'Alprax 0.25 Tablet', 'Torrent Pharmaceuticals Ltd', 'strip of 15 tablets', 'Alprazolam (0.25mg)', NULL, 56.25),
(23, 'Altraday Capsule SR', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 capsule sr', 'Aceclofenac (200mg)', 'Rabeprazole (20mg)', 145.80),
(24, 'Ativan 2mg Tablet', 'Pfizer Ltd', 'strip of 30 tablets', 'Lorazepam (2mg)', NULL, 210.50),
(25, 'Ascoril LS Junior Syrup', 'Glenmark Pharmaceuticals Ltd', 'bottle of 60 ml Syrup', 'Ambroxol (15mg/5ml)', 'Levosalbutamol (0.5mg/5ml)', 65.30),
(26, 'Asthalin 100mcg Inhaler', 'Cipla Ltd', 'packet of 200 MDI Inhaler', 'Salbutamol (100mcg)', NULL, 235.75),
(27, 'Almox 500 Capsule', 'Alkem Laboratories Ltd', 'strip of 10 capsules', 'Amoxycillin (500mg)', NULL, 45.60),
(28, 'Atarax 10mg Tablet', 'Dr Reddys Laboratories Ltd', 'strip of 15 tablets', 'Hydroxyzine (10mg)', NULL, 56.80),
(29, 'Aciloc RD 20 Tablet', 'Cadila Pharmaceuticals Ltd', 'strip of 15 tablets', 'Domperidone (10mg)', 'Omeprazole (20mg)', 115.25),
(30, 'Aldactone Tablet', 'RPG Life Sciences Ltd', 'strip of 15 tablets', 'Spironolactone (25mg)', NULL, 85.90),
(31, 'Allegra Suspension Raspberry & Vanilla', 'Sanofi India  Ltd', 'bottle of 100 ml Oral Suspension', 'Fexofenadine (30mg/5ml)', NULL, 175.40),
(32, 'Atarax Syrup', 'Dr Reddys Laboratories Ltd', 'bottle of 100 ml Syrup', 'Hydroxyzine (10mg)', NULL, 95.60),
(33, 'Amlokind-AT Tablet', 'Mankind Pharma Ltd', 'strip of 15 tablets', 'Amlodipine (5mg)', 'Atenolol (50mg)', 78.75),
(34, 'Axcer  90mg Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 14 tablets', 'Ticagrelor (90mg)', NULL, 452.30),
(35, 'Ativan 1mg Tablet', 'Pfizer Ltd', 'strip of 30 tablets', 'Lorazepam (1mg)', NULL, 178.50),
(36, 'Alkasol Oral Solution', 'Stadmed Pvt Ltd', 'bottle of 100 ml Oral Solution', 'Disodium Hydrogen Citrate (1.4gm/5ml)', NULL, 85.25),
(37, 'Aldigesic P 100mg/325mg Tablet', 'Alkem Laboratories Ltd', 'strip of 15 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 65.80),
(38, 'Alfoo 10mg Tablet PR', 'Dr Reddys Laboratories Ltd', 'strip of 30 Tablet pr', 'Alfuzosin (10mg)', NULL, 356.25),
(39, 'Alprax 0.5mg Tablet', 'Torrent Pharmaceuticals Ltd', 'strip of 15 tablets', 'Alprazolam (0.5mg)', NULL, 68.90),
(40, 'Arachitol 6L Injection', 'Abbott', 'packet of 6 injections', 'Vitamin D3 (600000IU)', NULL, 325.75),
(41, 'Anafortan 25 mg/300 mg Tablet', 'Abbott', 'strip of 15 tablets', 'Camylofin (25mg)', 'Paracetamol (300mg)', 85.60),
(42, 'Alex Junior Syrup', 'Glenmark Pharmaceuticals Ltd', 'bottle of 60 ml Syrup', 'Chlorpheniramine Maleate (2mg/5ml)', 'Dextromethorphan Hydrobromide (5mg/5ml)', 58.30),
(43, 'Azithral 200 Liquid', 'Alembic Pharmaceuticals Ltd', 'bottle of 15 ml Oral Suspension', 'Azithromycin (200mg/5ml)', NULL, 98.40),
(44, 'AB Phylline Capsule', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 capsules', 'Acebrophylline (100mg)', NULL, 125.50),
(45, 'Althrocin 500 Tablet', 'Alembic Pharmaceuticals Ltd', 'strip of 10 tablets', 'Erythromycin (500mg)', NULL, 78.60),
(46, 'Augmentin DDS Suspension', 'Glaxo SmithKline Pharmaceuticals Ltd', 'bottle of 30 ml Oral Suspension', 'Amoxycillin  (400mg/5ml)', 'Clavulanic Acid (57mg/5ml)', 210.75),
(47, 'Azicip 500 Tablet', 'Cipla Ltd', 'strip of 3 tablets', 'Azithromycin (500mg)', NULL, 165.40),
(48, 'Aldigesic-SP Tablet', 'Alkem Laboratories Ltd', 'strip of 10 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 48.90),
(49, 'Amoxycillin 500mg Capsule', 'Jagsonpal Pharmaceuticals Ltd', 'strip of 10 capsules', 'Amoxycillin (500mg)', NULL, 42.30),
(50, 'Asthakind Expectorant Sugar Free', 'Mankind Pharma Ltd', 'bottle of 60 ml Expectorant', 'Guaifenesin (50mg)', 'Terbutaline (1.25mg)', 68.75),
(51, 'Acemiz Plus Tablet', 'Lupin Ltd', 'strip of 10 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 52.60),
(52, 'Aceclo Plus Tablet', 'Aristo Pharmaceuticals Pvt Ltd', 'strip of 15 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 72.50),
(53, 'Anobliss Cream', 'Samarth Life Sciences Pvt Ltd', 'tube of 30 gm Rectal Cream', 'Lidocaine (1.5% w/w)', 'Nifedipine (0.3% w/w)', 185.90),
(54, 'Alex Cough Lozenges Lemon Ginger', 'Glenmark Pharmaceuticals Ltd', 'strip of 10 lozenges', 'Dextromethorphan Hydrobromide (5mg)', NULL, 32.75),
(55, 'Asthalin Respules', 'Cipla Ltd', 'packet of 2.5 ml Respules', 'Salbutamol (2.5mg)', NULL, 168.40),
(56, 'Avil Injection', 'Sanofi India  Ltd', 'vial of 10 ml Injection', 'Pheniramine (22.75mg)', NULL, 52.30),
(57, 'Azee 200mg Dry Syrup', 'Cipla Ltd', 'bottle of 15 ml Oral Suspension', 'Azithromycin (200mg/5ml)', NULL, 96.75),
(58, 'Atorva Tablet', 'Zydus Cadila', 'strip of 15 tablets', 'Atorvastatin (10mg)', NULL, 85.60),
(59, 'Asthakind-LS Expectorant Cola Sugar Free', 'Mankind Pharma Ltd', 'bottle of 100 ml Expectorant', 'Ambroxol (30mg/5ml)', 'Levosalbutamol (1mg/5ml)', 98.50),
(60, 'Ascoril LS Drops', 'Glenmark Pharmaceuticals Ltd', 'bottle of 15 ml Oral Drops', 'Ambroxol (7.5mg/ml)', 'Levosalbutamol (0.25mg/ml)', 75.30),
(61, 'Azmarda 50mg Tablet', 'J B Chemicals and Pharmaceuticals Ltd', 'strip of 14 tablets', 'Sacubitril (24mg)', 'Valsartan (26mg)', 410.25),
(62, 'Amixide-H Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Amitriptyline (12.5mg)', 'Chlordiazepoxide (5mg)', 87.60),
(63, 'AB-Flo-N Tablet', 'Lupin Ltd', 'strip of 10 tablets', 'Acebrophylline (100mg)', 'Acetylcysteine (600mg)', 175.50),
(64, 'AF Kit Tablet', 'Systopic Laboratories Pvt Ltd', 'strip of 4 tablets', 'Azithromycin (1000mg)', 'Ornidazole (750mg)', 225.80),
(65, 'Amlokind 5 Tablet', 'Mankind Pharma Ltd', 'strip of 15 tablets', 'Amlodipine (5mg)', NULL, 56.25),
(66, 'Amlong Tablet', 'Micro Labs Ltd', 'strip of 15 tablets', 'Amlodipine (5mg)', NULL, 58.90),
(67, 'Akt 4 Kit', 'Lupin Ltd', 'packet of 1 Kit', 'Isoniazid (300mg)', 'Rifampicin (450mg)', 245.75),
(68, 'Ascoril D Junior Cough Syrup', 'Glenmark Pharmaceuticals Ltd', 'bottle of 60 ml Syrup', 'Phenylephrine (5mg/5ml)', 'Chlorpheniramine Maleate (2mg/5ml)', 65.40),
(69, 'Amitone 10mg Tablet', 'Intas Pharmaceuticals Ltd', 'strip of 10 tablets', 'Amitriptyline (10mg)', NULL, 36.80),
(70, 'Aulin 100mg Tablet', 'Elder Pharmaceuticals Ltd', 'strip of 10 tablets', 'Nimesulide (100mg)', NULL, 48.25),
(71, 'Amikacin Sulphate 500mg Injection', 'Sun Pharmaceutical Industries Ltd', 'vial of 2 ml Injection', 'Amikacin (500mg)', NULL, 105.60),
(72, 'Ambrodil-LX Syrup', 'Aristo Pharmaceuticals Pvt Ltd', 'bottle of 100 ml Syrup', 'Ambroxol (30mg/5ml)', 'Levosalbutamol (1mg/5ml)', 99.75),
(73, 'Aquasol A Capsule', 'USV Ltd', 'bottle of 30 capsules', 'Vitamin A (25000IU)', NULL, 125.40),
(74, 'AB Phylline SR 200 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablet sr', 'Acebrophylline (200mg)', NULL, 165.30),
(75, 'Azoran Tablet', 'RPG Life Sciences Ltd', 'strip of 10 tablets', 'Azathioprine (50mg)', NULL, 210.75),
(76, 'Amaryl 1mg Tablet', 'Sanofi India  Ltd', 'strip of 30 tablets', 'Glimepiride (1mg)', NULL, 95.60),
(77, 'Aztor 10 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 15 tablets', 'Atorvastatin (10mg)', NULL, 88.25),
(78, 'Atorva 40 Tablet', 'Zydus Cadila', 'strip of 10 tablets', 'Atorvastatin (40mg)', NULL, 120.50),
(79, 'Azax 500 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 3 tablets', 'Azithromycin (500mg)', NULL, 165.80),
(80, 'Alex Syrup Sugar Free', 'Glenmark Pharmaceuticals Ltd', 'bottle of 100 ml Syrup', 'Phenylephrine (5mg/5ml)', 'Chlorpheniramine Maleate (2mg/5ml)', 110.40),
(81, 'Anxit 0.5 Tablet', 'Micro Labs Ltd', 'strip of 15 tablets', 'Alprazolam (0.5mg)', NULL, 65.75),
(82, 'Anxit 0.25mg Tablet', 'Micro Labs Ltd', 'strip of 15 tablets', 'Alprazolam (0.25mg)', NULL, 52.30),
(83, 'Acitrom 2 Tablet', 'Abbott', 'strip of 30 tablets', 'Acenocoumarol (2mg)', NULL, 115.60),
(84, 'Angispan - TR 2.5mg Capsule', 'USV Ltd', 'bottle of 25 capsule tr', 'Nitroglycerin (2.5mg)', NULL, 175.80),
(85, 'Azeflo Nasal Spray', 'Lupin Ltd', 'packet of 7 ml Nasal Spray', 'Fluticasone Propionate (50mcg)', 'Azelastine (140mcg)', 325.40),
(86, 'Acemiz -MR Tablet', 'Lupin Ltd', 'strip of 10 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 78.75),
(87, 'Akurit 4 Tablet', 'Lupin Ltd', 'strip of 10 tablets', 'Isoniazid (75mg)', 'Rifampicin (150mg)', 156.90),
(88, 'Aerocort Inhaler', 'Cipla Ltd', 'packet of 200 MDI Inhaler', 'Levosalbutamol (50mcg)', 'Beclometasone (50mcg)', 410.25),
(89, 'Adaferin Gel', 'Galderma India Pvt Ltd', 'tube of 15 gm Gel', 'Adapalene (0.1% w/w)', NULL, 245.60),
(90, 'Acivir 400 DT Tablet', 'Cipla Ltd', 'strip of 5 tablet dt', 'Acyclovir (400mg)', NULL, 145.30),
(91, 'Aptimust Syrup', 'Mankind Pharma Ltd', 'bottle of 200 ml Syrup', 'Cyproheptadine (2mg/5ml)', 'Tricholine Citrate (275mg/5ml)', 165.75),
(92, 'Augmentin 1000 Duo Tablet', 'Glaxo SmithKline Pharmaceuticals Ltd', 'strip of 10 tablets', 'Amoxycillin  (875mg)', 'Clavulanic Acid (125mg)', 285.40),
(93, 'Ambrodil Syrup', 'Aristo Pharmaceuticals Pvt Ltd', 'bottle of 100 ml Syrup', 'Ambroxol (30mg/5ml)', NULL, 78.60),
(94, 'Acogut Tablet', 'Lupin Ltd', 'strip of 15 tablets', 'Acotiamide (100mg)', NULL, 125.75),
(95, 'Atarax Drops', 'Dr Reddys Laboratories Ltd', 'bottle of 15 ml Syrup', 'Hydroxyzine (6mg)', NULL, 68.90),
(96, 'Amlip 5 Tablet', 'Cipla Ltd', 'strip of 10 tablets', 'Amlodipine (5mg)', NULL, 52.30),
(97, 'AntiD 300mcg/ml Injection', 'Bharat Serums & Vaccines Ltd', 'vial of 1 Injection', 'Anti Rh D Immunoglobulin (300mcg/ml)', NULL, 1850.60),
(98, 'Alerid Syrup', 'Cipla Ltd', 'bottle of 30 ml Syrup', 'Cetirizine (5mg/5ml)', NULL, 45.75),
(99, 'Aldactone 50 Tablet', 'RPG Life Sciences Ltd', 'strip of 15 tablets', 'Spironolactone (50mg)', NULL, 110.40),
(100, 'Ampoxin 500 Capsule', 'Torrent Pharmaceuticals Ltd', 'strip of 15 capsules', 'Ampicillin (250mg)', 'Cloxacillin (250mg)', 110.25),
(101, 'Azee 250 Tablet', 'Cipla Ltd', 'strip of 10 tablets', 'Azithromycin (250mg)', NULL, 145.60),
(102, 'Alerid Tablet', 'Cipla Ltd', 'strip of 10 tablets', 'Cetirizine (10mg)', NULL, 35.80),
(103, 'Augmentin 375 Tablet', 'Glaxo SmithKline Pharmaceuticals Ltd', 'strip of 10 tablets', 'Amoxycillin  (250mg)', 'Clavulanic Acid (125mg)', 165.40),
(104, 'Acenac-P  Tablet', 'Medley Pharmaceuticals', 'strip of 10 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 55.75),
(105, 'Acivir Cream', 'Cipla Ltd', 'tube of 5 gm Cream', 'Acyclovir (5% w/w)', NULL, 85.30),
(106, 'AB-Flo Capsule', 'Lupin Ltd', 'strip of 10 capsules', 'Acebrophylline (100mg)', NULL, 125.75),
(107, 'AB Phylline N Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Acebrophylline (100mg)', 'Acetylcysteine (600mg)', 175.60),
(108, 'Amifru 40 Tablet', 'Torrent Pharmaceuticals Ltd', 'strip of 10 tablets', 'Furosemide (40mg)', 'Amiloride (5mg)', 68.90),
(109, 'Avamys Nasal Spray', 'Glaxo SmithKline Pharmaceuticals Ltd', 'packet of 10 gm Nasal Spray', 'Fluticasone Furoate (0.05% w/w)', NULL, 355.40),
(110, 'Alkasol Oral Solution Sugar Free', 'Stadmed Pvt Ltd', 'bottle of 100 ml Oral Solution', 'Disodium Hydrogen Citrate (1.4gm/5ml)', NULL, 95.25),
(111, 'Aciloc 300 Tablet', 'Cadila Pharmaceuticals Ltd', 'strip of 20 tablets', 'Ranitidine (300mg)', NULL, 110.50),
(112, 'Aziderm 20% Cream', 'Micro Labs Ltd', 'tube of 15 gm Cream', 'Azelaic Acid (20% w/w)', NULL, 185.75),
(113, 'Ace Proxyvon Tablet', 'Dr Reddys Laboratories Ltd', 'strip of 10 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 56.90),
(114, 'Avil Injection', 'Sanofi India  Ltd', 'vial of 2 ml Injection', 'Pheniramine (22.75mg)', NULL, 25.60),
(115, 'Ano Metrogyl Cream', 'Lekar Pharma Ltd', 'tube of 20 gm Rectal Cream', 'Lidocaine (4% w/w)', 'Metronidazole (1% w/w)', 145.80),
(116, 'Admenta 5 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Memantine (5mg)', NULL, 310.25),
(117, 'Aceclo-MR Tablet', 'Aristo Pharmaceuticals Pvt Ltd', 'strip of 10 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 75.60),
(118, 'Alerid-D Tablet', 'Cipla Ltd', 'strip of 10 tablets', 'Cetirizine (5mg)', 'Phenylephrine (10mg)', 48.30),
(119, 'Acemiz-S Tablet', 'Lupin Ltd', 'strip of 10 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 58.75),
(120, 'Allercet-DC Tablet', 'Micro Labs Ltd', 'strip of 10 tablets', 'Cetirizine (10mg)', 'Phenylephrine (10mg)', 55.90),
(121, 'Aztolet  10 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Atorvastatin (10mg)', 'Clopidogrel (75mg)', 185.25),
(122, 'Amaryl 2mg Tablet', 'Sanofi India  Ltd', 'strip of 30 tablets', 'Glimepiride (2mg)', NULL, 125.60),
(123, 'Augmentin 1.2gm Injection', 'Glaxo SmithKline Pharmaceuticals Ltd', 'vial of 1 Powder for Injection', 'Amoxycillin  (1000mg)', 'Clavulanic Acid (200mg)', 325.80),
(124, 'Ambrodil-Plus RF Syrup', 'Aristo Pharmaceuticals Pvt Ltd', 'bottle of 100 ml Syrup', 'Ambroxol (15mg/5ml)', 'Pseudoephedrine (30mg/5ml)', 110.40),
(125, 'Amlopres-AT Tablet', 'Cipla Ltd', 'strip of 15 tablets', 'Amlodipine (5mg)', 'Atenolol (50mg)', 82.75),
(126, 'Ajaduo 25mg/5mg Tablet', 'Lupin Ltd', 'strip of 10 tablets', 'Empagliflozin (25mg)', 'Linagliptin (5mg)', 450.30),
(127, 'Avanair 100 Tablet', 'Cipla Ltd', 'strip of 4 tablets', 'Avanafil (100mg)', NULL, 510.25),
(128, 'Acton-OR Tablet SR', 'Apex Laboratories Pvt Ltd', 'strip of 10 tablet ir', 'Paracetamol (300mg)', 'Paracetamol (700mg)', 84.60),
(129, 'Aquazide 12.5 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Hydrochlorothiazide (12.5mg)', NULL, 35.75),
(130, 'Alaspan AM Tablet', 'Bayer Pharmaceuticals Pvt Ltd', 'strip of 10 tablets', 'Ambroxol (60mg)', 'Loratadine (5mg)', 85.90),
(131, 'Althrocin 250 Tablet', 'Alembic Pharmaceuticals Ltd', 'strip of 10 tablets', 'Erythromycin (250mg)', NULL, 58.40),
(132, 'Azikem 500mg Tablet', 'Alkem Laboratories Ltd', 'strip of 3 tablets', 'Azithromycin (500mg)', NULL, 172.50),
(133, 'Aldosmin 500mg Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Diosmin (450mg)', 'Hesperidin (50mg)', 195.60),
(134, 'Amlovas 5 Tablet', 'Macleods Pharmaceuticals Pvt Ltd', 'strip of 15 tablets', 'Amlodipine (5mg)', NULL, 58.75),
(135, 'Acitrom 1 Tablet', 'Abbott', 'strip of 30 tablets', 'Acenocoumarol (1mg)', NULL, 90.40),
(136, 'AF 150 Tablet DT', 'Systopic Laboratories Pvt Ltd', 'strip of 1 Tablet DT', 'Fluconazole (150mg)', NULL, 45.25),
(137, 'Ascoril SF Expectorant', 'Glenmark Pharmaceuticals Ltd', 'bottle of 100 ml Expectorant', 'Guaifenesin (50mg)', 'Terbutaline (1.25mg)', 96.60),
(138, 'Amaryl M  1mg Tablet PR', 'Sanofi India  Ltd', 'strip of 20 Tablet pr', 'Glimepiride (1mg)', 'Metformin (500mg)', 110.75),
(139, 'Azithral XL 200 Liquid', 'Alembic Pharmaceuticals Ltd', 'bottle of 30 ml Oral Suspension', 'Azithromycin (200mg/5ml)', NULL, 125.30),
(140, 'AF 400 Tablet', 'Systopic Laboratories Pvt Ltd', 'strip of 1 Tablet', 'Fluconazole (400mg)', NULL, 95.40),
(141, 'Amantrel Tablet', 'Cipla Ltd', 'strip of 15 tablets', 'Amantadine (100mg)', NULL, 210.75),
(142, 'Atorva 20 Tablet', 'Zydus Cadila', 'strip of 15 tablets', 'Atorvastatin (20mg)', NULL, 98.60),
(143, 'Acenac-MR Tablet', 'Medley Pharmaceuticals', 'strip of 10 tablets', 'Thiocolchicoside (4mg)', 'Aceclofenac (100mg)', 85.30),
(144, 'Ambrolite-S Expectorant', 'Tablets India Limited', 'bottle of 100 ml Expectorant', 'Ambroxol (30mg)', 'Guaifenesin (50mg)', 92.75),
(145, 'Asthalin 4 Tablet', 'Cipla Ltd', 'strip of 30 tablets', 'Salbutamol (4mg)', NULL, 72.40),
(146, 'Abiways Tablet', 'Mankind Pharma Ltd', 'strip of 10 tablets', 'Acebrophylline (100mg)', 'Acetylcysteine (600mg)', 168.50),
(147, 'Amaryl M  2mg Tablet PR', 'Sanofi India  Ltd', 'strip of 20 Tablet pr', 'Glimepiride (2mg)', 'Metformin (500mg)', 125.90),
(148, 'Azoran Tablet', 'RPG Life Sciences Ltd', 'strip of 20 tablets', 'Azathioprine (50mg)', NULL, 395.25),
(149, 'Ampilox Capsule', 'Biochem Pharmaceutical Industries', 'strip of 15 capsules', 'Ampicillin (250mg)', 'Dicloxacillin (250mg)', 105.60),
(150, 'Adrenaline Tartrate Injection', 'Harson Laboratories', 'vial of 1 ml Injection', 'Adrenaline (NA)', NULL, 45.30),
(151, 'Acogut 300 ER Tablet', 'Lupin Ltd', 'strip of 10 tablet er', 'Acotiamide (300mg)', NULL, 315.75),
(152, 'Acuvin Tablet', 'Abbott', 'strip of 15 tablets', 'Paracetamol/Acetaminophen  (325mg)', 'Tramadol (37.5mg)', 85.40),
(153, 'AB-Flo SR Tablet', 'Lupin Ltd', 'strip of 10 tablet sr', 'Acebrophylline (200mg)', NULL, 165.25),
(154, 'Air-M Tablet', 'Systopic Laboratories Pvt Ltd', 'strip of 10 tablets', 'Montelukast (10mg)', 'Fexofenadine (120mg)', 215.60),
(155, 'Atorlip-F Tablet', 'Cipla Ltd', 'strip of 15 tablets', 'Atorvastatin (10mg)', 'Fenofibrate (145mg)', 165.30),
(156, 'Advent Forte 457mg Syrup Tangy Orange', 'Cipla Ltd', 'bottle of 30 ml Syrup', 'Amoxycillin  (400mg/5ml)', 'Clavulanic Acid (57mg/5ml)', 215.75),
(157, 'Amlodac 5 Tablet', 'Zydus Cadila', 'strip of 30 tablets', 'Amlodipine (5mg)', NULL, 98.40),
(158, 'Aztor 40 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 15 tablets', 'Atorvastatin (40mg)', NULL, 155.25),
(159, 'Ascoril Plus Expectorant', 'Glenmark Pharmaceuticals Ltd', 'bottle of 200 ml Expectorant', 'Bromhexine (2mg/5ml)', 'Guaifenesin (50mg/5ml)', 145.60),
(160, 'Alupent 10mg Tablet', 'Zydus Cadila', 'strip of 10 tablets', 'Orciprenaline (10mg)', NULL, 78.30),
(161, 'Amlip AT Tablet', 'Cipla Ltd', 'strip of 10 tablets', 'Amlodipine (5mg)', 'Atenolol (50mg)', 65.75),
(162, 'Asthakind-P Drops', 'Mankind Pharma Ltd', 'packet of 15 ml Oral Drops', 'Ambroxol (7.5mg)', 'Guaifenesin (12.5mg)', 54.90),
(163, 'Allegra Nasal Spray', 'Sanofi India  Ltd', 'bottle of 120 MDI Nasal Spray', 'Fluticasone Furoate (27.5mcg)', NULL, 365.40),
(164, 'Addnok 0.2mg Tablet', 'Rusan Pharma Ltd', 'strip of 20 tablets', 'Buprenorphine (0.2mg)', NULL, 165.25),
(165, 'Aldosmin 1000mg Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Diosmin (900mg)', 'Hesperidin (100mg)', 275.60),
(166, 'Andre I-Kul Eye Drop', 'Intas Pharmaceuticals Ltd', 'packet of 10 ml Eye Drop', 'Camphor (0.01% w/v)', 'Menthol (0.005% w/v)', 65.30),
(167, 'Antiflu 75mg Capsule', 'Cipla Ltd', 'strip of 10 capsules', 'Oseltamivir Phosphate (75mg)', NULL, 425.40),
(169, 'Alex-L Cough Syrup Mango', 'Glenmark Pharmaceuticals Ltd', 'bottle of 100 ml Oral Suspension', 'Levocloperastine (20mg/5ml)', NULL, 125.00),
(170, 'Atropine Sulphate Injection', 'Samarth Life Sciences Pvt Ltd', 'vial of 10 ml Injection', 'Atropine (0.6mg)', NULL, 85.50),
(171, 'Aquaviron Injection 1ml', 'Abbott', 'vial of 1 Injection', 'Testosterone (25mg)', NULL, 240.00),
(172, 'Aciclovir 400 Tablet', 'Care Formulation Labs Pvt Ltd', 'strip of 5 tablets', 'Acyclovir (400mg)', NULL, 175.25),
(173, 'Alkof DX Syrup', 'Alkem Laboratories Ltd', 'bottle of 100 ml Syrup', 'Chlorpheniramine Maleate (4mg)', 'Dextromethorphan Hydrobromide (10mg)', 110.50),
(174, 'ATM 500 Tablet', 'Indoco Remedies Ltd', 'strip of 5 tablets', 'Azithromycin (500mg)', NULL, 320.75),
(175, 'Assurans Tablet', 'Cipla Ltd', 'strip of 10 tablets', 'Sildenafil (20mg)', NULL, 450.00),
(176, 'Ambrolite D Syrup', 'Tablets India Limited', 'bottle of 100 ml Syrup', 'Phenylephrine (5mg/5ml)', 'Chlorpheniramine Maleate (2mg/5ml)', 98.50),
(177, 'Actrapid HM 100IU/ml Penfill', 'Novo Nordisk India Pvt Ltd', 'penfill of 3 ml Solution for Injection', 'Human insulin (100IU)', NULL, 525.00),
(178, 'Aten 50 Tablet', 'Zydus Cadila', 'strip of 14 tablets', 'Atenolol (50mg)', NULL, 75.25),
(179, 'Azmarda 100mg Tablet', 'J B Chemicals and Pharmaceuticals Ltd', 'strip of 14 tablets', 'Sacubitril (49mg)', 'Valsartan (51mg)', 475.50),
(180, 'Asthalin AX Syrup', 'Cipla Ltd', 'bottle of 100 ml Syrup', 'Ambroxol (30mg/5ml)', 'Levosalbutamol (1mg/5ml)', 145.00),
(181, 'Alzolam 0.5mg Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Alprazolam (0.5mg)', NULL, 125.50),
(182, 'A Kare Combipack', 'DKT India Ltd', 'packet of 1 Kit', 'Mifepristone (200mg)', 'Misoprostol (200mcg)', 850.00),
(183, 'Ambulax Tablet', 'Unimarck Pharma India Ltd', 'strip of 15 tablets', 'Alprazolam (0.25mg)', 'Propranolol (20mg)', 160.75),
(184, 'Amlovas-AT Tablet', 'Macleods Pharmaceuticals Pvt Ltd', 'strip of 15 tablets', 'Amlodipine (5mg)', 'Atenolol (50mg)', 120.50),
(185, 'Asthakind-DX Junior Syrup Cherry', 'Mankind Pharma Ltd', 'bottle of 60 ml Syrup', 'Phenylephrine (2.5mg/5ml)', 'Chlorpheniramine Maleate (1mg/5ml)', 85.25),
(186, 'Advent 228.5mg Dry Syrup', 'Cipla Ltd', 'bottle of 30 ml Dry Syrup', 'Amoxycillin  (200mg/5ml)', 'Clavulanic Acid (28.5mg/5ml)', 245.00),
(187, 'Acivir 800 DT Tablet', 'Cipla Ltd', 'strip of 5 tablet dt', 'Acyclovir (800mg)', NULL, 320.50),
(188, 'Alfusin Tablet PR', 'Cipla Ltd', 'strip of 15 Tablet pr', 'Alfuzosin (10mg)', NULL, 275.00),
(189, 'Aziwok 500 Tablet', 'Dr Reddys Laboratories Ltd', 'strip of 5 tablets', 'Azithromycin (500mg)', NULL, 325.75),
(190, 'Alkacitral Liquid', 'Alembic Pharmaceuticals Ltd', 'bottle of 100 ml Syrup', 'Disodium Hydrogen Citrate (1.25gm/5ml)', NULL, 95.25),
(191, 'Almox-CV 625 Tablet', 'Cachet Pharmaceuticals Pvt Ltd', 'strip of 6 tablets', 'Amoxycillin  (500mg)', 'Clavulanic Acid (125mg)', 225.50),
(192, 'Azithral 100 Liquid', 'Alembic Pharmaceuticals Ltd', 'bottle of 15 ml Oral Suspension', 'Azithromycin (20mg/ml)', NULL, 175.00),
(193, 'Azee 100mg Dry Syrup Peppermint', 'Cipla Ltd', 'bottle of 15 ml Suspension', 'Azithromycin (100mg/5ml)', NULL, 190.25),
(194, 'Ace-Proxyvon CR Tablet', 'Dr Reddys Laboratories Ltd', 'strip of 10 tablet cr', 'Aceclofenac (200mg)', 'Rabeprazole (20mg)', 195.75),
(195, 'Ascoril C  Syrup', 'Glenmark Pharmaceuticals Ltd', 'bottle of 120 ml Syrup', 'Chlorpheniramine Maleate (4mg)', 'Codeine (10mg)', 155.50),
(196, 'Aprezo 30mg Tablet', 'Glenmark Pharmaceuticals Ltd', 'strip of 10 tablets', 'Apremilast (30mg)', NULL, 780.00),
(197, 'Apidra 100IU Cartridge', 'Sanofi India  Ltd', 'cartridge of 3 ml Solution for Injection', 'Insulin Glulisine (100IU)', NULL, 525.50),
(198, 'Amlosafe 3D Tablet', 'Aristo Pharmaceuticals Pvt Ltd', 'strip of 7 tablets', 'Telmisartan (40mg)', 'Amlodipine (5mg)', 195.25),
(199, 'Andial 2mg Tablet', 'Veritaz Healthcare Ltd', 'strip of 10 tablets', 'Loperamide (2mg)', NULL, 65.50),
(200, 'Acenext P 100mg/325mg Tablet', 'Cadila Pharmaceuticals Ltd', 'strip of 10 tablets', 'Aceclofenac (100mg)', 'Paracetamol (325mg)', 145.00),
(201, 'Acnesol A Nano Gel', 'Systopic Laboratories Pvt Ltd', 'tube of 15 gm Gel', 'Adapalene (0.1% w/w)', 'Clindamycin (1% w/w)', 265.75),
(202, 'Ascoril Flu Syrup', 'Glenmark Pharmaceuticals Ltd', 'bottle of 60 ml Syrup', 'Chlorpheniramine Maleate (2mg/5ml)', 'Phenylephrine (5mg/5ml)', 110.25),
(203, 'Asomex 2.5 Tablet', 'Emcure Pharmaceuticals Ltd', 'strip of 15 tablets', 'S-Amlodipine (2.5mg)', NULL, 110.50),
(204, 'Angiplat 2.5 Capsule TR', 'Micro Labs Ltd', 'bottle of 25 capsule tr', 'Nitroglycerin (2.5mg)', NULL, 220.00),
(205, 'Alkof Cofgel  Tablet', 'Alkem Laboratories Ltd', 'strip of 10 tablets', 'Guaifenesin (NA)', 'Bromhexine (NA)', 85.25),
(206, 'Azithral 250mg DT Tablet', 'Alembic Pharmaceuticals Ltd', 'strip of 10 tablet dt', 'Azithromycin (250mg)', NULL, 245.50),
(207, 'Alzolam 0.25mg Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Alprazolam (0.25mg)', NULL, 95.25),
(208, 'Advent 625 Tablet', 'Cipla Ltd', 'strip of 10 tablets', 'Amoxycillin  (500mg)', 'Clavulanic Acid (125mg)', 245.00),
(209, 'Alsita M 50mg/500mg Tablet', 'Alkem Laboratories Ltd', 'strip of 10 tablets', 'Sitagliptin  (50mg)', 'Metformin (500mg)', 425.50),
(210, 'Axogurd-SR Tablet', 'Alembic Pharmaceuticals Ltd', 'strip of 10 tablet sr', 'Methylcobalamin (1500mcg)', 'Pregabalin (75mg)', 285.25),
(211, 'Azoflox UTI Tablet', 'Mac Millon Pharmaceuticals Pvt Ltd', 'strip of 10 tablets', 'Ofloxacin (200mg)', 'Flavoxate (200mg)', 220.50),
(212, 'Azulix 2 MF Tablet PR', 'Torrent Pharmaceuticals Ltd', 'strip of 15 Tablet pr', 'Glimepiride (2mg)', 'Metformin (500mg)', 175.75),
(213, 'Amaryl MV 2mg Tablet SR', 'Sanofi India  Ltd', 'strip of 15 tablet sr', 'Glimepiride (2mg)', 'Metformin (500mg)', 245.25),
(214, 'Azulix 1 MF Tablet PR', 'Torrent Pharmaceuticals Ltd', 'strip of 15 Tablet pr', 'Glimepiride (1mg)', 'Metformin (500mg)', 165.50),
(215, 'Aten 25 Tablet', 'Zydus Cadila', 'strip of 14 tablets', 'Atenolol (25mg)', NULL, 65.00),
(216, 'Azibact 500 Tablet', 'Ipca Laboratories Ltd', 'strip of 5 tablets', 'Azithromycin (500mg)', NULL, 310.75),
(217, 'Amlopres TL Tablet', 'Cipla Ltd', 'strip of 15 tablets', 'Telmisartan (40mg)', 'Amlodipine (5mg)', 185.25),
(218, 'Ambrolite Syrup', 'Tablets India Limited', 'bottle of 100 ml Syrup', 'Ambroxol (30mg/5ml)', NULL, 95.50),
(219, 'Actapro Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Acotiamide (100mg)', NULL, 340.00),
(220, 'Aztor 20 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 15 tablets', 'Atorvastatin (20mg)', NULL, 175.25),
(221, 'Alkof Syrup', 'Alkem Laboratories Ltd', 'bottle of 100 ml Syrup', 'Guaifenesin (50mg)', 'Terbutaline (1.25mg)', 105.75),
(222, 'Atorfit CV 10 Capsule', 'Ajanta Pharma Ltd', 'strip of 15 capsules', 'Atorvastatin (10mg)', 'Clopidogrel (75mg)', 295.50),
(223, 'Acnesol Gel', 'Systopic Laboratories Pvt Ltd', 'tube of 20 gm Gel', 'Clindamycin (1% w/w)', NULL, 145.00),
(224, 'Amtas 5 Tablet', 'Intas Pharmaceuticals Ltd', 'strip of 30 tablets', 'Amlodipine (5mg)', NULL, 125.25),
(225, 'Alrgee 120mg Tablet', 'Morepen Laboratories Ltd', 'strip of 10 tablets', 'Fexofenadine (120mg)', NULL, 165.50),
(226, 'Avil 50mg Tablet', 'Sanofi India  Ltd', 'strip of 15 tablets', 'Pheniramine (50mg)', NULL, 75.00),
(227, 'Arip MT 5 Tablet', 'Torrent Pharmaceuticals Ltd', 'strip of 15 tablets', 'Aripiprazole (5mg)', NULL, 395.75),
(228, 'Alivher Tablet', 'Akumentis Healthcare Ltd', 'strip of 10 tablets', 'Sildenafil (25mg)', NULL, 495.25),
(229, 'Apdrops LP  Eye Drop BAK Free', 'Ajanta Pharma Ltd', 'bottle of 5 ml Eye Drop', 'Loteprednol etabonate (0.5% w/v)', 'Moxifloxacin (0.5% w/v)', 325.50),
(230, 'Amrox-LS Syrup', 'Leeford Healthcare Ltd', 'bottle of 60 ml Expectorant', 'Ambroxol (30mg)', 'Levosalbutamol (1mg)', 115.00),
(231, 'Altonil 3mg Tablet', 'Alteus Biogenics Pvt Ltd', 'strip of 15 tablets', 'Melatonin (3mg)', NULL, 185.25),
(232, 'Allercet Cold Tablet', 'Micro Labs Ltd', 'strip of 10 tablets', 'Levocetirizine (5mg)', 'Phenylephrine (10mg)', 135.50),
(233, 'Atorlip 10 Tablet', 'Cipla Ltd', 'strip of 15 tablets', 'Atorvastatin (10mg)', NULL, 135.00),
(234, 'Admenta 10 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Memantine (10mg)', NULL, 345.75),
(235, 'Amox 500mg Capsule', 'Synchem Lab', 'strip of 10 capsules', 'Amoxycillin (500mg)', NULL, 95.25),
(236, 'Azee 250 Tablet', 'Cipla Ltd', 'strip of 6 tablets', 'Azithromycin (250mg)', NULL, 175.50),
(237, 'Asthalin Respirator Solution', 'Cipla Ltd', 'bottle of 15 ml Solution for inhalation', 'Salbutamol (5mg)', NULL, 120.00),
(238, 'Add Tears Lubricant Eye Drop', 'Cipla Ltd', 'bottle of 10 ml Eye Drop', 'Carboxymethylcellulose (0.5% w/v)', NULL, 175.25),
(239, 'Almox 250 Capsule', 'Alkem Laboratories Ltd', 'strip of 10 capsules', 'Amoxycillin (250mg)', NULL, 75.50),
(240, 'Aztor Asp 75 Capsule', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 capsules', 'Atorvastatin (10mg)', 'Aspirin (75mg)', 225.00),
(241, 'Alkacip Syrup', 'Cipla Ltd', 'bottle of 100 ml Syrup', 'Disodium Hydrogen Citrate (1.53gm/5ml)', NULL, 95.75),
(242, 'Arbitel-Trio 50 Tablet ER', 'Micro Labs Ltd', 'strip of 10 tablet er', 'Cilnidipine (10mg)', 'Metoprolol Succinate (50mg)', 275.50),
(243, 'Acivir 200 DT Tablet', 'Cipla Ltd', 'strip of 10 tablet dt', 'Acyclovir (200mg)', NULL, 165.00),
(244, 'Augmentin ES Oral Suspension', 'Glaxo SmithKline Pharmaceuticals Ltd', 'bottle of 50 ml Oral Suspension', 'Amoxycillin  (600mg/5ml)', 'Clavulanic Acid (42.9mg/5ml)', 395.25),
(245, 'Amrolstar Cream', 'Oaknet Healthcare Pvt Ltd', 'tube of 30 gm Cream', 'Amorolfine (0.25% w/w)', NULL, 245.50),
(246, 'Adalene Nanogel Gel', 'Zydus Cadila', 'tube of 15 gm Gel', 'Adapalene (0.1% w/w)', 'Clindamycin (1% w/w)', 275.00),
(247, 'Apigat 2.5 Tablet', 'Natco Pharma Ltd', 'bottle of 30 tablets', 'Apixaban (2.5mg)', NULL, 975.75),
(248, 'Aeromont-B Tablet', 'Aristo Pharmaceuticals Pvt Ltd', 'strip of 10 tablets', 'Bilastine (20mg)', 'Montelukast (10mg)', 275.25),
(249, 'Amlopres 5 Tablet', 'Cipla Ltd', 'strip of 30 tablets', 'Amlodipine (5mg)', NULL, 150.50),
(250, 'Abevia-N Tablet', 'Mankind Pharma Ltd', 'strip of 10 tablets', 'Acebrophylline (100mg)', 'Acetylcysteine (600mg)', 285.00),
(251, 'Acamprol Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 6 tablets', 'Acamprosate (333mg)', NULL, 395.75),
(252, 'Ancool  Oral Suspension Sugar Free', 'Zuventus Healthcare Ltd', 'bottle of 170 ml Oral Suspension', 'Aluminium Hydroxide (300mg/5ml)', 'Milk Of Magnesia (150mg/5ml)', 115.25),
(253, 'Atormac CV10 Capsule', 'Macleods Pharmaceuticals Pvt Ltd', 'strip of 10 capsules', 'Atorvastatin (10mg)', 'Clopidogrel (75mg)', 275.50),
(254, 'Apdrops PD Eye Drop', 'Ajanta Pharma Ltd', 'bottle of 10 ml Eye Drop', 'Moxifloxacin (0.5% w/v)', 'Prednisolone (1% w/v)', 295.00),
(255, 'Amnurite  10 Tablet SR', 'Health N U Therapeutics Pvt Ltd', 'strip of 10 tablet sr', 'Amitriptyline (10mg)', 'Methylcobalamin (1500mcg)', 215.25),
(256, 'Atonide Gel', 'Curatio Healthcare India Pvt Ltd', 'tube of 20 gm Gel', 'Desonide (0.05% w/w)', NULL, 195.50),
(257, 'Amlong-A Tablet', 'Micro Labs Ltd', 'strip of 15 tablets', 'Amlodipine (5mg)', 'Atenolol (50mg)', 125.00),
(258, 'Acivir 500 Infusion', 'Cipla Ltd', 'vial of 1 Infusion', 'Acyclovir (500mg)', NULL, 575.75),
(259, 'AF-K Lotion', 'Systopic Laboratories Pvt Ltd', 'bottle of 100 ml Lotion', 'Ketoconazole (2% w/v)', 'Zinc pyrithione (1% w/v)', 195.25),
(260, 'Amoxyclav 375 Tablet', 'Abbott', 'strip of 10 tablets', 'Amoxycillin  (250mg)', 'Clavulanic Acid (125mg)', 215.50),
(261, 'Abd 400mg Tablet', 'Intas Pharmaceuticals Ltd', 'strip of 1 Tablet', 'Albendazole (400mg)', NULL, 45.00),
(262, 'Amlong 2.5 Tablet', 'Micro Labs Ltd', 'strip of 15 tablets', 'Amlodipine (2.5mg)', NULL, 95.75),
(263, 'Alburel 20gm Solution for Infusion', 'Reliance Life Sciences', 'bottle of 100 ml Injection', 'Albumin (20%)', NULL, 2750.50),
(264, 'Anacin Tablet', 'Pfizer Ltd', 'strip of 10 tablets', 'Caffeine (50mg)', 'Paracetamol (500mg)', 85.00),
(265, 'Azithro 250mg Tablet', 'Ind Swift Laboratories Ltd', 'strip of 6 tablets', 'Azithromycin (250mg)', NULL, 195.75),
(266, 'Azimax 500 Tablet', 'Cipla Ltd', 'strip of 5 tablets', 'Azithromycin (500mg)', NULL, 325.25),
(267, 'Amlosafe-AT Tablet', 'Aristo Pharmaceuticals Pvt Ltd', 'strip of 10 tablets', 'Amlodipine (5mg)', 'Atenolol (50mg)', 120.50),
(268, 'Air 180 Tablet', 'Systopic Laboratories Pvt Ltd', 'strip of 10 tablets', 'Fexofenadine (180mg)', NULL, 185.00),
(269, 'Adilip 135 Tablet DR', 'Intas Pharmaceuticals Ltd', 'strip of 10 Tablet DR', 'Choline fenofibrate (135mg)', NULL, 345.75),
(270, 'Apdrops Eye Drop', 'Ajanta Pharma Ltd', 'bottle of 5 ml Eye Drop', 'Moxifloxacin (0.5% w/v)', NULL, 245.25),
(271, 'Amlosafe 5 Tablet', 'Aristo Pharmaceuticals Pvt Ltd', 'strip of 10 tablets', 'Amlodipine (5mg)', NULL, 95.50),
(272, 'Apigat 5 Tablet', 'Natco Pharma Ltd', 'bottle of 30 tablets', 'Apixaban (5mg)', NULL, 1475.00),
(273, 'Amlogard 5mg Tablet', 'Pfizer Ltd', 'strip of 30 tablets', 'Amlodipine (5mg)', NULL, 165.75),
(274, 'Amaryl MV 1mg Tablet SR', 'Sanofi India Ltd', 'strip of 15 tablet sr', 'Glimepiride (1mg)', 'Metformin (500mg)', 215.25),
(275, 'Azee 500 Tablet', 'Cipla Ltd', 'strip of 3 tablets', 'Azithromycin (500mg)', NULL, 295.50),
(276, 'ALERGIN L TABLET', 'Cipla Ltd', 'strip of 10 tablets', 'Levocetirizine (5mg)', NULL, 95.00),
(277, 'Aciloc Injection', 'Cadila Pharmaceuticals Ltd', 'vial of 2 ml Injection', 'Ranitidine (25mg)', NULL, 45.75),
(278, 'Alex P Syrup', 'Glenmark Pharmaceuticals Ltd', 'bottle of 60 ml Syrup', 'Chlorpheniramine Maleate (0.5mg/5ml)', 'Paracetamol (125mg/5ml)', 85.25),
(279, 'AZR Tablet', 'Ipca Laboratories Ltd', 'strip of 10 tablets', 'Azathioprine (50mg)', NULL, 425.50),
(280, 'Angicam-Beta Tablet', 'Blue Cross Laboratories Ltd', 'strip of 15 tablets', 'Amlodipine (5mg)', 'Atenolol (50mg)', 125.00),
(281, 'Amicobal 10mg/1500mcg Tablet', 'Ergos Life Sciences', 'strip of 10 tablets', 'Amitriptyline (10mg)', 'Methylcobalamin (1500mcg)', 215.75),
(282, 'Actibile 300 Tablet', 'Zydus Cadila', 'strip of 10 tablets', 'Ursodeoxycholic Acid (300mg)', NULL, 425.25),
(283, 'Ambrolite Levo Syrup', 'Tablets India Limited', 'bottle of 100 ml Syrup', 'Ambroxol (30mg/5ml)', 'Levosalbutamol (1mg/5ml)', 135.50),
(284, 'Amaryl M Forte 2mg Tablet PR', 'Sanofi India Ltd', 'strip of 15 Tablet pr', 'Glimepiride (2mg)', 'Metformin (1000mg)', 275.00),
(285, 'Acivir Cream', 'Cipla Ltd', 'tube of 10 gm Cream', 'Acyclovir (5% w/w)', NULL, 175.75),
(286, 'Azicip 250 Tablet', 'Cipla Ltd', 'strip of 10 tablets', 'Azithromycin (250mg)', NULL, 215.25),
(287, 'Algesia CR 200mg/20mg Capsule', 'Macleods Pharmaceuticals Pvt Ltd', 'strip of 10 capsules', 'Aceclofenac (200mg)', 'Rabeprazole (20mg)', 195.50),
(288, 'Ascoril Expectorant', 'Glenmark Pharmaceuticals Ltd', 'bottle of 200 ml Expectorant', 'Bromhexine (4mg)', 'Guaifenesin (100mg)', 165.00),
(289, 'Atorva 80 Tablet', 'Zydus Cadila', 'strip of 10 tablets', 'Atorvastatin (80mg)', NULL, 245.75),
(290, 'Acitrom 4 Tablet', 'Abbott', 'strip of 30 tablets', 'Acenocoumarol (4mg)', NULL, 275.25),
(291, 'Alsita 100mg Tablet', 'Alkem Laboratories Ltd', 'strip of 10 tablets', 'Sitagliptin (100mg)', NULL, 395.50),
(292, 'Amnurite 5 mg/1500 mcg Tablet', 'Health N U Therapeutics Pvt Ltd', 'strip of 10 tablets', 'Amitriptyline (5mg)', 'Methylcobalamin (1500mcg)', 195.00),
(293, 'Airz Capsule', 'Glenmark Pharmaceuticals Ltd', 'packet of 30 capsules', 'Glycopyrrolate (50mcg)', NULL, 595.75),
(294, 'Asomex 5 Tablet', 'Emcure Pharmaceuticals Ltd', 'strip of 15 tablets', 'S-Amlodipine (5mg)', NULL, 145.25),
(295, 'Axcer 90mg Tablet', 'Sun Pharmaceutical Industries Ltd', 'bottle of 180 tablets', 'Ticagrelor (90mg)', NULL, 2850.50),
(296, 'Anaspas Tablet', 'Abbott', 'strip of 10 tablets', 'Camylofin (50mg)', 'Diclofenac (50mg)', 165.00),
(297, 'Ascabiol Emulsion', 'Abbott', 'bottle of 100 ml Solution', 'Lindane (1% w/v)', 'Cetrimide (0.1% w/v)', 345.75),
(298, 'Addwize 10mg Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Methylphenidate (10mg)', NULL, 495.25),
(299, 'Ascoril Plus Expectorant', 'Glenmark Pharmaceuticals Ltd', 'bottle of 120 ml Expectorant', 'Bromhexine (2mg/5ml)', 'Guaifenesin (50mg/5ml)', 145.50),
(300, 'Amlong 10 Tablet', 'Micro Labs Ltd', 'strip of 15 tablets', 'Amlodipine (10mg)', NULL, 125.00),
(301, 'Abd Plus Tablet', 'Intas Pharmaceuticals Ltd', 'strip of 1 Tablet', 'Ivermectin (6mg)', 'Albendazole (400mg)', 75.75),
(302, 'Allercet-M Tablet', 'Micro Labs Ltd', 'strip of 10 tablets', 'Levocetirizine (5mg)', 'Montelukast (10mg)', 195.25),
(303, 'Augpen -DS Suspension', 'Zuventus Healthcare Ltd', 'bottle of 30 ml Suspension', 'Amoxycillin (400mg/5ml)', 'Clavulanic Acid (57mg/5ml)', 235.50),
(304, 'Althrocin Liquid', 'Alembic Pharmaceuticals Ltd', 'bottle of 60 ml Oral Suspension', 'Erythromycin (125mg/5ml)', NULL, 145.00),
(305, 'Atocor 10 Tablet', 'Dr Reddys Laboratories Ltd', 'strip of 14 tablets', 'Atorvastatin (10mg)', NULL, 135.75),
(306, 'Augpen 625 BID Tablet', 'Zuventus Healthcare Ltd', 'strip of 10 tablets', 'Amoxycillin (500mg)', 'Clavulanic Acid (125mg)', 245.25),
(307, 'Atchol-F Tablet', 'Aristo Pharmaceuticals Pvt Ltd', 'strip of 10 tablets', 'Atorvastatin (10mg)', 'Fenofibrate (160mg)', 245.50),
(308, 'Alensol-D Tablet', 'Medsol India Overseas Pvt Ltd', 'strip of 4 tablets', 'Alendronic Acid (70mg)', 'Vitamin D3 (5600IU)', 285.00),
(309, 'Arbitel-Trio 25 Tablet ER', 'Micro Labs Ltd', 'strip of 10 tablet er', 'Cilnidipine (10mg)', 'Metoprolol Succinate (25mg)', 255.75),
(310, 'Acivir Eye Ointment', 'Cipla Ltd', 'tube of 5 gm Eye Ointment', 'Acyclovir (3% w/w)', NULL, 195.25),
(311, 'Aceclo Tablet', 'Aristo Pharmaceuticals Pvt Ltd', 'strip of 10 tablets', 'Aceclofenac (100mg)', NULL, 85.50),
(312, 'Actrapid 100 IU/ml Flexpen', 'Novo Nordisk India Pvt Ltd', 'flexpen of 3 ml Solution for Injection', 'Human insulin (100IU)', NULL, 525.00),
(313, 'Amitone 25mg Tablet', 'Intas Pharmaceuticals Ltd', 'strip of 10 tablets', 'Amitriptyline (25mg)', NULL, 85.75),
(314, 'Anafortan Syrup', 'Abbott', 'bottle of 60 ml Syrup', 'Camylofin (12.5mg/5ml)', 'Paracetamol (125mg/5ml)', 125.25),
(315, 'Acera-D Capsule SR', 'Ipca Laboratories Ltd', 'strip of 10 capsule sr', 'Domperidone (30mg)', 'Rabeprazole (20mg)', 215.50),
(316, 'Aerodil SF Expectorant', 'Zydus Cadila', 'bottle of 100 ml Expectorant', 'Ambroxol (15mg/5ml)', 'Guaifenesin (50mg/5ml)', 115.00),
(317, 'Aculip H 12.5 mg/5 mg Tablet', 'Shine Pharmaceuticals Ltd', 'strip of 20 tablets', 'Amitriptyline (12.5mg)', 'Chlordiazepoxide (5mg)', 175.75),
(318, 'Aztolet 20 Tablet', 'Sun Pharmaceutical Industries Ltd', 'strip of 10 tablets', 'Atorvastatin (20mg)', 'Clopidogrel (75mg)', 295.25),
(319, 'Amlopin 5 Tablet', 'USV Ltd', 'strip of 10 tablets', 'Amlodipine (5mg)', NULL, 95.50);

-- --------------------------------------------------------

--
-- Table structure for table `medicine_orders`
--

CREATE TABLE `medicine_orders` (
  `id` int(11) NOT NULL,
  `booked_by_user_id` int(11) DEFAULT 0,
  `booked_by_email` varchar(255) DEFAULT '',
  `booked_by_name` varchar(255) DEFAULT '',
  `order_number` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_order_items`
--

CREATE TABLE `medicine_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `medicine_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adm_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_clinic_id` (`clinic_id`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_appointment_time` (`appointment_time`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_booking_date` (`booking_date`),
  ADD KEY `idx_doctor_date_time` (`doctor_id`,`appointment_date`,`appointment_time`),
  ADD KEY `idx_patient_email` (`patient_email`),
  ADD KEY `idx_appointment_datetime` (`appointment_date`,`appointment_time`),
  ADD KEY `unique_user_appointment_slot` (`doctor_id`,`appointment_date`,`appointment_time`,`booked_by_email`) USING BTREE;

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`clinic_id`),
  ADD UNIQUE KEY `clinic_email` (`clinic_email`),
  ADD KEY `idx_clinic_email` (`clinic_email`),
  ADD KEY `idx_clinic_status` (`status`),
  ADD KEY `idx_clinic_location` (`location`(100));

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`doc_id`);

--
-- Indexes for table `doctor_clinic_assignments`
--
ALTER TABLE `doctor_clinic_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `lab_orders`
--
ALTER TABLE `lab_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `lab_order_items`
--
ALTER TABLE `lab_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicine_orders`
--
ALTER TABLE `medicine_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`);

--
-- Indexes for table `medicine_order_items`
--
ALTER TABLE `medicine_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_is_verified` (`is_verified`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `clinic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `doctor_clinic_assignments`
--
ALTER TABLE `doctor_clinic_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lab_orders`
--
ALTER TABLE `lab_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lab_order_items`
--
ALTER TABLE `lab_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=398;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=320;

--
-- AUTO_INCREMENT for table `medicine_orders`
--
ALTER TABLE `medicine_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `medicine_order_items`
--
ALTER TABLE `medicine_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doc_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_clinic_assignments`
--
ALTER TABLE `doctor_clinic_assignments`
  ADD CONSTRAINT `doctor_clinic_assignments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_clinic_assignments_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_orders`
--
ALTER TABLE `lab_orders`
  ADD CONSTRAINT `clinic_id` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `medicine_order_items`
--
ALTER TABLE `medicine_order_items`
  ADD CONSTRAINT `medicine_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `medicine_orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
