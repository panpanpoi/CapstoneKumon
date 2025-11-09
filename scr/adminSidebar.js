(() => {
  // Sidebar Dropdown
  document.querySelectorAll('.subnavbtn').forEach(btn => {
    btn.addEventListener('click', () => {
      const content = btn.nextElementSibling;
      content?.classList.toggle('show');

      const caret = btn.querySelector('.caret-icon');
      caret?.classList.toggle('rotate');
    });
  });

  // Optional: highlight current menu item
  const currentUrl = window.location.pathname.split("/").pop();
  document.querySelectorAll('.nav-menu a').forEach(link => {
    if (link.getAttribute('href') === currentUrl) {
      link.classList.add('active');
    }
  });

  // User profile toggle (if you have a dropdown for user settings)
  const userProfile = document.querySelector('.user-profile');
  if (userProfile) {
    userProfile.addEventListener('click', () => {
      const menu = userProfile.querySelector('.user-menu');
      menu?.classList.toggle('show');
    });
  }
})();


