document.addEventListener("DOMContentLoaded", function () {
  // Variables for search and filtering
  const searchInput = document.querySelector(".search-container input");
  const searchButton = document.querySelector(".search-container button");
  const filterButtons = document.querySelectorAll(".filter-btn");
  const surgeryCards = document.querySelectorAll(".surgery-card");

  // Search functionality
  searchButton.addEventListener("click", (e) => {
    e.preventDefault();
    performSearch();
  });

  searchInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      performSearch();
    }
  });

  function performSearch() {
    const searchTerm = searchInput.value.toLowerCase().trim();

    if (searchTerm === "") {
      const activeFilter =
        document.querySelector(".filter-btn.active").dataset.filter;
      filterSurgeries(activeFilter);
      return;
    }

    let resultsFound = false;
    surgeryCards.forEach((card) => {
      const cardTitle = card.querySelector("h3").textContent.toLowerCase();
      card.style.display = cardTitle.includes(searchTerm) ? "block" : "none";
      if (cardTitle.includes(searchTerm)) resultsFound = true;
    });

    resultsFound ? removeNoResultsMessage() : showNoResultsMessage();
  }

  // Filter functionality
  filterButtons.forEach((button) => {
    button.addEventListener("click", function () {
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      this.classList.add("active");
      filterSurgeries(this.dataset.filter);
      searchInput.value = "";
    });
  });

  function filterSurgeries(filter) {
    removeNoResultsMessage();

    if (filter === "all") {
      surgeryCards.forEach((card) => (card.style.display = "block"));
      return;
    }

    let cardsShown = 0;
    surgeryCards.forEach((card) => {
      const isMatch = card.dataset.category === filter;
      card.style.display = isMatch ? "block" : "none";
      if (isMatch) cardsShown++;
    });

    if (cardsShown === 0) showNoResultsMessage();
  }

  function showNoResultsMessage() {
    removeNoResultsMessage();

    const noResults = document.createElement("div");
    noResults.id = "no-results-message";
    noResults.style.textAlign = "center";
    noResults.style.padding = "30px";
    noResults.style.width = "100%";
    noResults.innerHTML =
      "<h3>No surgeries found</h3><p>Please try another search term or category.</p>";

    document.querySelector(".surgery-grid").appendChild(noResults);
  }

  function removeNoResultsMessage() {
    const existingMessage = document.getElementById("no-results-message");
    if (existingMessage) existingMessage.remove();
  }

  // Initialize with all surgeries shown
  filterSurgeries("all");
});
