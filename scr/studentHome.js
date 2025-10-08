// ================== SIDEBAR TOGGLE ==================
document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.querySelector(".sidebar-toggle");
  const sidebar  = document.querySelector(".sidebar");
  const overlay  = document.querySelector(".overlay");

  if (toggleBtn && sidebar && overlay) {
    // Open / Close sidebar
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("active");
      overlay.classList.toggle("active");
    });

    // Close when clicking overlay
    overlay.addEventListener("click", () => {
      sidebar.classList.remove("active");
      overlay.classList.remove("active");
    });
  }
});

