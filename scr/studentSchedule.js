document.addEventListener("DOMContentLoaded", () => {
  const todayName = new Date().toLocaleString("en-US", { weekday: "long" });
  const weekBtn = document.getElementById("filter-week");
  const todayBtn = document.getElementById("filter-today");
  const scheduleContainer = document.querySelector(".schedule-container");
  const allCards = document.querySelectorAll(".day-card");

  // Extract each day's HTML (from PHP)
  const dayData = Array.from(allCards).map(card => ({
    day: card.dataset.day,
    html: card.innerHTML.trim()
  }));

  // Utility: Creates a schedule panel  
  function createPanel(title, innerHTML, isToday = false) {
    const panel = document.createElement("div");
    panel.className = "day-card";
    panel.innerHTML = `<h3>${title}</h3>${innerHTML}`;
    
    if (isToday) {
      panel.querySelectorAll(".inner-day").forEach(div => div.classList.add("today"));
    }
    return panel;
  }


   // Show the entire week’s schedule
  function renderWeekView() {
    scheduleContainer.innerHTML = "";

    // Use HTML directly from PHP to avoid duplication
    const weekHTML = dayData
      .map(d => d.html || `<p class="no-class-msg">No classes this day</p>`)
      .join("");

    const weekPanel = createPanel("Entire Week", weekHTML);
    scheduleContainer.appendChild(weekPanel);

    setActiveButton(weekBtn);
  }

  // Show only today's schedule
 
  function renderTodayView() {
    scheduleContainer.innerHTML = "";

    const todayDataObj = dayData.find(d => d.day === todayName);
    const content = todayDataObj && todayDataObj.html.includes("<li>")
      ? todayDataObj.html
      : `<p class="no-class-msg">No classes today</p>`;

    scheduleContainer.appendChild(createPanel(todayName, content, true));
    setActiveButton(todayBtn);
  }
  // Toggle active button styles
  function setActiveButton(activeBtn) {
    [weekBtn, todayBtn].forEach(btn => btn.classList.remove("active"));
    activeBtn.classList.add("active");
  }

  // Auto-detect initial view
  const todayHasClass = dayData.some(d => d.day === todayName && d.html.includes("<li>"));
  if (todayHasClass) renderTodayView();
  else renderWeekView();

  // Button listeners
  weekBtn.addEventListener("click", renderWeekView);
  todayBtn.addEventListener("click", renderTodayView);
});
