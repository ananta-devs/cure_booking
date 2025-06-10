// Mobile Menu Toggle
const hamburgerMenu = document.getElementById("hamburgerMenu");
const navContainer = document.getElementById("navContainer");
const overlay = document.getElementById("overlay");

hamburgerMenu.addEventListener("click", function () {
  this.classList.toggle("active");
  navContainer.classList.toggle("active");
  overlay.classList.toggle("active");
  document.body.style.overflow = navContainer.classList.contains("active")
    ? "hidden"
    : "";
});

overlay.addEventListener("click", function () {
  hamburgerMenu.classList.remove("active");
  navContainer.classList.remove("active");
  this.classList.remove("active");
  document.body.style.overflow = "";
});

// Close menu when clicking on links
const navLinks = document.querySelectorAll(".nav-links a");
navLinks.forEach((link) => {
  link.addEventListener("click", function () {
    hamburgerMenu.classList.remove("active");
    navContainer.classList.remove("active");
    overlay.classList.remove("active");
    document.body.style.overflow = "";
  });
});
