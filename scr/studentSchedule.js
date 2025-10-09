document.addEventListener("DOMContentLoaded", () => {
  const todayName = new Date().toLocaleString("en-US", { weekday: "long" });
  const weekBtn = document.getElementById("filter-week");
  const todayBtn = document.getElementById("filter-today");
  const scheduleContainer = document.querySelector(".schedule-container");
  const allCards = document.querySelectorAll(".day-card");

  // Extract each day's HTML (so PHP doesn't have to rerender)
  const dayData = Array.from(allCards).map(card => ({
    day: card.dataset.day,
    html: card.innerHTML.trim()
  }));

  /**
   * Utility: Creates a schedule panel with a title and inner content
   */
  function createPanel(title, innerHTML) {
    const panel = document.createElement("div");
    panel.className = "day-card";
    panel.innerHTML = `
      <h3>${title}</h3>
      ${innerHTML}
    `;
    return panel;
  }

  /**
   * Show the entire week’s schedule in one card
   */
  function renderWeekView() {
    scheduleContainer.innerHTML = "";

    const weekHTML = dayData
      .map(({ day, html }) => {
        const content = html.includes("<li>")
          ? html
          : `<p class="no-class-msg">No classes this day</p>`;
        return `
          <div class="inner-day">
            <h4>${day}</h4>
            ${content}
          </div>
        `;
      })
      .join("");

    const weekPanel = createPanel("Entire Week", weekHTML);
    scheduleContainer.appendChild(weekPanel);

    setActiveButton(weekBtn);
  }

  /**
   * Show only today’s schedule in a single card
   */
  function renderTodayView() {
    scheduleContainer.innerHTML = "";

    const todayData = dayData.find(d => d.day === todayName);
    if (!todayData) {
      scheduleContainer.appendChild(
        createPanel(todayName, `<p class="no-class-msg">No classes today</p>`)
      );
      setActiveButton(todayBtn);
      return;
    }

    const hasClasses = todayData.html.includes("<li>");
    const content = hasClasses
      ? todayData.html
      : `<p class="no-class-msg">No classes today</p>`;

    scheduleContainer.appendChild(createPanel(todayName, content));
    setActiveButton(todayBtn);
  }

  /**
   * Toggle active button states
   */
  function setActiveButton(activeBtn) {
    [weekBtn, todayBtn].forEach(btn => btn.classList.remove("active"));
    activeBtn.classList.add("active");
  }

  /**
   * Initialize: auto-detect whether to show today or the full week
   */
  const todayHasClass = dayData.some(
    d => d.day === todayName && d.html.includes("<li>")
  );

  if (todayHasClass) renderTodayView();
  else renderWeekView();

  // Button Event Listeners
  weekBtn.addEventListener("click", renderWeekView);
  todayBtn.addEventListener("click", renderTodayView);
});
