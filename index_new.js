document.addEventListener("DOMContentLoaded", function () {
    // JavaScript to switch between languages (Polish and English)
    const languageSelect = document.getElementById("language-select");
  
    languageSelect.addEventListener("change", function () {
      const currentLanguage = languageSelect.value;
      document.querySelectorAll("[data-lang]").forEach((element) => {
        element.style.display = element.dataset.lang === currentLanguage ? "block" : "none";
      });
    });
  
    // Set default language to English on load
    document.querySelectorAll("[data-lang='pl']").forEach((el) => (el.style.display = "none"));
  
    // Add "contact me" mailto link for each business offer
    document.querySelectorAll(".contact-offer").forEach((button) => {
      button.addEventListener("click", function () {
        const subject = encodeURIComponent("Business Offer Inquiry");
        window.location.href = `mailto:arwrobel@gmail.com?subject=${subject}`;
      });
    });
  
    // Fetch and display the latest blog posts
    fetch("https://blog.softwareveteran.dev/search?max-results=3")
      .then((response) => response.text())
      .then((data) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, "text/html");
        const blogPosts = doc.querySelectorAll(".post"); // Assuming posts are marked with class 'post'
        const blogContainer = document.getElementById("blog-container");
  
        blogPosts.forEach((post, index) => {
          if (index < 3) { // Limit to 3 posts
            const title = post.querySelector(".post-title").textContent;
            const link = post.querySelector("a").href;
            const imageSrc = post.querySelector("img").src;
  
            const blogCard = document.createElement("div");
            blogCard.classList.add("blog-card");
            blogCard.innerHTML = `
              <h2>${title}</h2>
              <img src="${imageSrc}" alt="Blog Icon">
              <p><a href="${link}" target="_blank" class="button">Read More</a></p>
            `;
            blogContainer.appendChild(blogCard);
          }
        });
      })
      .catch((error) => {
        console.error("Error fetching blog posts:", error);
      });
  });
  