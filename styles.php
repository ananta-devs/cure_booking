<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: #f4f4f4;
    color: #333;
    width: 100%;
    overflow-x: hidden;
}

/* .container {
    max-width: 1200px;
    margin: 0 auto;
    /* padding: 20px; */
    /* width: 100%; */
/* }  */


/* Hero Section */
.hero {
    background-color: #e8f4ff;
    padding: 60px 0;
    text-align: center;
}

.hero h1 {
    font-size: clamp(24px, 5vw, 36px);
    margin-bottom: 15px;
    color: #333;
}

.hero p {
    font-size: clamp(16px, 3vw, 18px);
    color: #666;
    margin-bottom: 30px;
}

.search-container {
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    border-radius: 30px;
    overflow: hidden;
}

.search-container input {
    flex: 1;
    min-width: 200px;
    padding: 15px 20px;
    border: none;
    font-size: 16px;
    outline: none;
}

.search-container button {
    padding: 15px 25px;
    border: none;
    background-color: #007bff;
    color: #fff;
    cursor: pointer;
}

.search-container button:hover {
    background-color: #0069d9;
}


/* end */
h1 {
    text-align: center;
    margin-bottom: 30px;
    color: #2d3748;
    font-size: clamp(1.5rem, 4vw, 2.5rem);
}

.search-container {
    display: flex;
    margin-bottom: 20px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    position: relative;
    width: 100%;
    flex-wrap: nowrap;
}

.search-container button i {
    display: inline-block;
    font-size: 18px;
}

#search-bar:focus {
    border-color: #4a5568;
}

#search-icon {
    color: #ffffff; 
    font-size: 18px;
}

.search-container #search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #ffffff;
    cursor: pointer;
    font-size: 18px;
    display: block;
}

.loading-indicator {
    text-align: center;
    padding: 15px;
    font-size: 16px;
    color: #4a5568;
}

.error-message {
    background-color: #fed7d7;
    color: #c53030;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    text-align: center;
}

.hidden {
    display: none;
}

.results-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
    width: 100%;
}

.result-card {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
}

.result-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.details {
    padding: 20px;
    padding-bottom: 0;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    position: relative;
    border-top: 4px solid #512da8;
    background: linear-gradient(to bottom, #fff, #fafafa);
}

.name {
    font-size: 18px;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 10px;
    line-height: 1.3;
}

.lab-sample,
.medicine-manufacturer {
    font-size: 14px;
    color: #4a5568;
    margin-bottom: 10px;
    font-weight: 500;
    display: flex;
    align-items: center;
}

.lab-sample::before
,.medicine-manufacturer::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    margin-right: 8px;
    background-color: #512da8;
    border-radius: 50%;
}

.price {
    font-size: 14px;
    color: #4a5568;
    margin-bottom: 10px;
    font-style: italic;
    /* padding-left: 0px; */
    position: relative;
}

.price:before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    margin-right: 8px;
    background-color: #512da8;
    border-radius: 50%;
}

.lab-desc,
.medicine-composition {
    font-size: 14px;
    color: #718096;
    margin-bottom: 15px;
    line-height: 1.5;
    flex-grow: 1;
    padding-top: 8px;
    border-top: 1px dashed #e2e8f0;
}

.label {
    display: inline-block;
    padding: 4px 10px;
    background-color: #e9d8fd;
    color: #553c9a;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: auto;
}

/* book-btn */


.pagination-container {
    text-align: center;
    margin-top: 20px;
    margin-bottom: 40px;
}

#load-more-btn {
    padding: 10px 20px;
    background-color: #4a5568;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

#load-more-btn:hover {
    background-color: #2d3748;
}

#load-more-btn:disabled {
    background-color: #a0aec0;
    cursor: not-allowed;
}

.book-now-btn {
    width: 100%;
    background-color: #512da8;
    color: white;
    text-align: center;
    padding: 12px 0;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: auto;
    opacity: 0;
    transform: translateY(100%);
}

.result-card:hover .book-now-btn {
    opacity: 4;
    transform: translateY(0);
}

.book-now-btn:hover {
    background-color: #351189;
}

/* surgery page */
/* Filter Section */
.filter-section {
    padding: 30px 0;
    background-color: #fff;
    overflow-x: auto;
}

.filter-buttons {
    display: flex;
    flex-wrap: nowrap;
    justify-content: flex-start;
    gap: 10px;
    padding-bottom: 10px;
    white-space: nowrap;
}

.filter-btn {
    padding: 8px 16px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 20px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover, .filter-btn.active {
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
}

/* Popular Surgeries Section */
.popular-surgeries {
    padding: 20px 0 40px;
    background-color: #fff;
}

.surgery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 20px;
}

.surgery-card {
    text-align: center;
    padding: 15px;
    border-radius: 8px;
    transition: transform 0.3s ease;
    cursor: pointer;
}

.surgery-card:hover {
    transform: translateY(-5px);
}

.icon-container {
    width: 70px;
    height: 70px;
    margin: 0 auto 15px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-container img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.bg-purple { background-color: #f0e5ff; }
.bg-orange { background-color: #fff0e5; }
.bg-blue { background-color: #e5f0ff; }
.bg-light-blue { background-color: #e5f9ff; }
.bg-red { background-color: #ffe5e5; }

.surgery-card h3 {
    font-size: 16px;
    margin-top: 10px;
    color: #333;
    font-weight: 500;
}

/* Why Choose Us Section */
.why-choose-us {
    padding: 40px 0;
    background-color: #fff;
}

.why-choose-us h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: clamp(22px, 4vw, 30px);
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.feature {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.feature:hover {
    transform: translateY(-5px);
}

.feature-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 20px;
    background-color: #e8f4ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-icon i {
    font-size: 30px;
    color: #007bff;
}

.feature h3 {
    margin-bottom: 15px;
    color: #333;
}

.feature p {
    color: #666;
}

/* Responsive design for different screen sizes */
@media (min-width: 992px) {
    .results-container {
        grid-template-columns: repeat(3, 1fr);
    }
    .surgery-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}

@media (min-width: 768px) and (max-width: 991px) {
    .results-container {
        grid-template-columns: repeat(2, 1fr);
    }
    .features {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 767px) {
    .container {
        padding: 15px;
    }
    
    .results-container {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .details {
        padding: 15px;
    }
    
    .name {
        font-size: 16px;
    }
    
    
    .book-now-btn {
        font-size: 14px;
        padding: 10px 0;
    }
}

@media (max-width: 576px) {
    .container {
        padding: 10px;
    }
    
    .search-container {
        max-width: 100%;
    }
    
    #search-icon {
        display: block;
    }
    
    #search-bar {
        padding-right: 40px;
    }
    
    .results-container {
        grid-template-columns: 1fr;
    }
    
    .result-card {
        max-width: 100%;
    }
    .surgery-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
    
    .icon-container {
        width: 60px;
        height: 60px;
    }
    
    .icon-container img {
        width: 30px;
        height: 30px;
    }
    
    .surgery-card h3 {
        font-size: 14px;
    }
    
    .feature {
        padding: 15px;
    }
}

@media (max-width: 450px) {    
    .surgery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .filter-section{
        display: none;
    }
}

@media (max-width: 375px) {
    h1 {
        font-size: 1.3rem;
        margin-bottom: 20px;
    }
    
    #search-bar {
        font-size: 14px;
        padding: 10px 12px;
    }
    
    .name {
        font-size: 15px;
    }
    
    .details {
        padding: 12px;
    }
    
    .lab-sample,
    .medicine-manufacturer,
    .price,
    .medicine-pack,
    .lab-desc,.medicine-composition {
        font-size: 13px;
        margin-bottom: 6px;
    }
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    width: 90%;
    max-width: 600px;
    position: relative;
    animation: modalopen 0.3s;
}

.modal-content h2{
    text-align: center;
}
.test-summary,
.book-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background-color: #f5f6fa;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.test-summary-info h3,
.book-summary-info h3 {
    margin-bottom: 5px;
    font-size: clamp(16px, 5vw, 18px);
}

.test-summary-price .price,
.book-summary-price .price {
    font-size: 20px;
}

.test-summary-price .price::before,
.book-summary-price .price::before{
    background-color: #ffffff;
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-row .form-group {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: var(--border-radius);
    font-size: 16px;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}

textarea {
    height: 100px;
    resize: vertical;
}

</style>