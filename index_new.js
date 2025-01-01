document.addEventListener("DOMContentLoaded", function () {
  
    // JavaScript to switch between languages (Polish and English)
    const languageSelect = document.getElementById("language-select");
    let currentLanguage = 'pl'; // Default to 'pl'

    if (languageSelect) {
        currentLanguage = languageSelect.value;
        languageSelect.addEventListener("change", function () {
            const currentLanguage = languageSelect.value;
            document.querySelectorAll("[data-lang]").forEach((element) => {
                element.style.display = element.dataset.lang === currentLanguage ? "block" : "none";
            });
        });
    }
  

    function toggleLanguage(lang) {
        // document.querySelectorAll('[data-lang=\'${lang}']').forEach((el) => (el.style.display = "none"));

        document.querySelectorAll("[data-lang]").forEach((element) => {
          element.style.display = element.dataset.lang === lang ? "block" : "none";
        });
      }
    
      // Initial toggle based on the selected language
      toggleLanguage(currentLanguage);
    
      // Update visibility when the language changes
      languageSelect.addEventListener("change", function () {
        toggleLanguage(languageSelect.value);
      });
      
  
    // Add "contact me" mailto link for each business offer
    document.querySelectorAll(".contact-offer").forEach((button) => {
      button.addEventListener("click", function () {
        const subject = encodeURIComponent(button.getAttribute("data-subject"));
        window.location.href = `mailto:arwrobel@gmail.com?subject=${subject}`;
      });
    });
  
// Fetch and display the latest blog posts from RSS feed
fetch("https://softwareveteran.dev/api/blog-proxy.php")
.then((response) => response.text())
.then((str) => {
  const parser = new DOMParser();
  const xml = parser.parseFromString(str, "text/xml");
  console.log(xml);
  const items = xml.querySelectorAll("item");
//   const items = xml.querySelectorAll("entry"); // RSS uses 'entry' tags for posts
  const blogContainer = document.getElementById("blog-container");

  // Clear previous content
  blogContainer.innerHTML = "";

  // Process and display the first three posts
  items.forEach((item, index) => {
    if (index < 4) {
      const title = item.querySelector("title").textContent;
      const link = item.querySelector("link").textContent;
      const published = new Date(item.querySelector("pubDate").textContent).toDateString();
      const summary = getFirstThreeSentences(item.querySelector("description")?.textContent || ""); // Optional

      const imageSrc = extractImageFromDescription(item.querySelector("description")?.textContent) || "https://via.placeholder.com/300";
    //   const mediaThumbnail = item.querySelector("media\\:thumbnail")?.getAttribute("url") || "https://via.placeholder.com/300";

      const blogCard = document.createElement("div");
      blogCard.classList.add("blog-card");
      blogCard.innerHTML = `
        <h2>${title}</h2>
        <img src="${imageSrc}" alt="Blog Thumbnail" class="blog-thumbnail"/>
        <p>${published}</p>
        <p>${summary}</p>
        <button class="button" data-lang="en" onclick="location.href='${link}';">Read More</button>
        <button class="button" data-lang="pl" onclick="location.href='${link}';">Czytaj dalej</button>
      `;
      blogContainer.appendChild(blogCard);
    }
  });
  toggleLanguage(currentLanguage);
})
.catch((error) => {
  console.error("Error fetching RSS feed:", error);
  const blogContainer = document.getElementById("blog-container");
  blogContainer.innerHTML = `<p>Unable to load blog posts. Please try again later.</p>`;
});

// Set default language to English on load
// document.querySelectorAll("[data-lang='en']").forEach((el) => (el.style.display = "none"));

function getFirstThreeSentences(content) {
    if (!content) return ''; // Handle empty or undefined content
    const sentences = content.match(/[^.!?]+[.!?]+/g); // Split content into sentences
    if (!sentences) return content; // If no punctuation is found, return the original content
    return sentences.slice(0, 3).join(' '); // Join the first three sentences
  }

  function extractImageFromDescription(description) {
    if (!description) return null;
  
    // Parse the HTML content
    const parser = new DOMParser();
    const doc = parser.parseFromString(description, "text/html");
  
    // Find the first image in the content
    const imgElement = doc.querySelector("img");
    return imgElement ? imgElement.getAttribute("src") : null;
  }
});
  