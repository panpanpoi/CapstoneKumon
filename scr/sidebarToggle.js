document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector(".sidebar");
  const toggleBtn = document.getElementById("sidebarToggle");
  const overlay = document.getElementById("overlay");

  if (!sidebar || !toggleBtn) return;

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
    toggleBtn.classList.toggle("active");
  });

  overlay?.addEventListener("click", () => {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    toggleBtn.classList.remove("active");
  });
});
