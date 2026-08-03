document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector(".site-header");
  const setHeader = () => header && header.classList.toggle("scrolled", window.scrollY > 28);
  setHeader();
  window.addEventListener("scroll", setHeader, { passive: true });

  document.querySelectorAll("[name=form_started_at]").forEach((input) => {
    input.value = Date.now();
  });

  const menuButton = document.querySelector(".menu-toggle");
  const nav = document.querySelector("#mainnav");
  if (menuButton && nav) {
    menuButton.addEventListener("click", () => {
      const isOpen = nav.classList.toggle("open");
      menuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  }

  const serviceSelect = document.querySelector("[data-service-select]");
  function updateConditionalFields() {
    document.querySelectorAll("[data-conditional]").forEach((field) => {
      field.style.display = serviceSelect && /monthly|long-term|employee|fleet/i.test(serviceSelect.value) ? "grid" : "none";
    });
  }
  if (serviceSelect) {
    serviceSelect.addEventListener("change", updateConditionalFields);
    updateConditionalFields();
  }

  document.querySelectorAll("details").forEach((details) => {
    details.addEventListener("toggle", () => {
      if (details.open) {
        details.parentElement.querySelectorAll("details").forEach((other) => {
          if (other !== details) other.open = false;
        });
      }
    });
  });

  const icon = '<span class="card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg></span>';
  document.querySelectorAll(".card:not(:has(img)) > div").forEach((cardBody) => {
    if (!cardBody.querySelector(".card-icon")) cardBody.insertAdjacentHTML("afterbegin", icon);
  });

  document.querySelectorAll(".btn, .iconlink, .textlink").forEach((button) => {
    button.addEventListener("pointerdown", (event) => {
      const ripple = document.createElement("span");
      ripple.className = "ripple";
      const rect = button.getBoundingClientRect();
      ripple.style.left = `${event.clientX - rect.left}px`;
      ripple.style.top = `${event.clientY - rect.top}px`;
      button.appendChild(ripple);
      window.setTimeout(() => ripple.remove(), 650);
    });
  });

  const revealItems = document.querySelectorAll(".section-head, .card, .split, .pill-grid, .logo-grid, .faq details, .cta");
  revealItems.forEach((item) => item.classList.add("reveal"));
  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealItems.forEach((item) => observer.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add("visible"));
  }

  const galleryImages = document.querySelectorAll(".gallery-masonry img");
  if (galleryImages.length) {
    const lightbox = document.createElement("div");
    lightbox.className = "lightbox";
    lightbox.innerHTML = '<button type="button" aria-label="Close gallery preview">Close</button><img alt="">';
    document.body.appendChild(lightbox);
    const lightboxImage = lightbox.querySelector("img");
    const close = () => lightbox.classList.remove("open");
    lightbox.addEventListener("click", close);
    lightbox.querySelector("button").addEventListener("click", close);
    galleryImages.forEach((image) => {
      image.tabIndex = 0;
      image.addEventListener("click", () => {
        lightboxImage.src = image.src;
        lightboxImage.alt = image.alt;
        lightbox.classList.add("open");
      });
      image.addEventListener("keydown", (event) => {
        if (event.key === "Enter") image.click();
      });
    });
  }
});
