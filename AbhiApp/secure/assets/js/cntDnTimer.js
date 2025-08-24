
  function updateCountdown() {
    const now = Date.now();
    const remaining = tokenExpiry - now;

    if (remaining <= 0) {
      alert("Session expired. Redirecting to login...");
      window.location.href = "index.php";
      return;
    }

    const minutes = Math.floor(remaining / 60000);
    const seconds = Math.floor((remaining % 60000) / 1000);
    document.getElementById("timer").textContent =
      `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  }

  setInterval(updateCountdown, 1000);
  updateCountdown(); // Initial call