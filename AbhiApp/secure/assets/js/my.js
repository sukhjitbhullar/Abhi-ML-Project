// Ensure BASE_URL is injected via PHP in HTML
const BASE_URL = window.BASE_URL || '';

// Load Cities into Dropdown
function loadCities() {
  fetch(`${BASE_URL}/index.php?route=fetch_cities`)
    .then(res => res.json())
    .then(cities => {
      const citySelect = document.getElementById('citySelect');
      if (!citySelect) return;
      cities.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.city_name;
        citySelect.appendChild(option);
      });
    })
    .catch(err => {
      console.error('Error fetching cities:', err);
      alert('Failed to load cities.');
    });
}

// Countdown Timer
function updateCountdown() {
  const remaining = tokenExpiry - Math.floor(Date.now() / 1000);
  if (remaining <= 0) {
    showToast("Session expired. Redirecting to login...", 3000);
    setTimeout(() => {
      window.location.href = `${BASE_URL}/index.php`;
    }, 3000);
    return;
  }

  const minutes = Math.floor(remaining / 60);
  const seconds = remaining % 60;
  if (timerElement) {
    timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  }
}

// Generate Table Header
function generateTableHeader() {
  const thead = document.querySelector('#tempTable thead');
  thead.innerHTML = '';

  const headerRow = document.createElement('tr');
  headerRow.appendChild(createHeaderCell('DateTime'));

  for (let h = 0; h < 24; h++) {
    headerRow.appendChild(createHeaderCell(`${h.toString().padStart(2, '0')} Hrs`));
  }

  thead.appendChild(headerRow);
}

function createHeaderCell(text) {
  const th = document.createElement('th');
  th.textContent = text;
  return th;
}

// Fill Temperature Table
function fillTemperatureTable(data) {
  const tbody = document.querySelector('#tempTable tbody');
  const tableWrapper = document.getElementById('tableWrapper');
  tbody.innerHTML = '';

  let rowCount = 0;

  Object.entries(data).forEach(([date, hours]) => {
    const row = document.createElement('tr');
    row.appendChild(createCell(date));

    for (let h = 0; h < 24; h++) {
      const hourStr = h.toString().padStart(2, '0');
      const temp = hours[hourStr];
      const cell = createCell(temp !== null ? temp : '--');
      if (temp !== null && parseFloat(temp) > 35) {
        cell.classList.add('hot-temp');
      }
      row.appendChild(cell);
    }

    tbody.appendChild(row);
    rowCount++;
  });

  tableWrapper.classList.toggle('scrollable-table-wrapper', rowCount > 20);
}

function createCell(text) {
  const td = document.createElement('td');
  td.textContent = text;
  return td;
}

// Fetch Temperature Data
function fetchTemperature() {
  const cityId = document.getElementById('citySelect').value;
  const startDate = document.getElementById('startDate').value;
  const endDate = document.getElementById('endDate').value;

  if (!cityId || !startDate || !endDate) {
    alert("Please select city and date range.");
    return;
  }

  document.getElementById('loadingSpinner').style.display = 'block';

  fetch(`${BASE_URL}/index.php?route=fetch_temperature`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ city_id: cityId, start_date: startDate, end_date: endDate })
  })
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        alert(`Error: ${data.error}`);
        if (data.redirect) window.location.href = data.redirect;
      } else {
        generateTableHeader();
        fillTemperatureTable(data);
      }
    })
    .catch(err => {
      console.error('Error fetching temperature:', err);
      alert('Failed to fetch temperature data.');
    })
    .finally(() => {
      document.getElementById('loadingSpinner').style.display = 'none';
    });
}

// Download Max-Min PDF
async function downloadMaxMinPdf() {
  const cityId = document.getElementById('citySelect').value;
  const startDate = document.getElementById('startDate').value;
  const endDate = document.getElementById('endDate').value;
  const CityName = document.getElementById('citySelect').selectedOptions[0].text;
  const reportTitle = `Max-Min Temperature Report for ${CityName}`;

  if (!cityId || !startDate || !endDate) {
    alert('Please select city and date range first.');
    return;
  }

  try {
    const response = await fetch(`${BASE_URL}/index.php?route=fetch_temperature`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `city_id=${cityId}&start_date=${startDate}&end_date=${endDate}`
    });

    const data = await response.json();
    if (data.error) throw new Error(data.error);

    const rows = [['Date', 'Max Temp (°C)', 'Min Temp (°C)']];
    for (const [date, hours] of Object.entries(data)) {
      const temps = Object.values(hours).filter(v => v !== null).map(parseFloat);
      if (temps.length > 0) {
        rows.push([date, Math.max(...temps).toFixed(4), Math.min(...temps).toFixed(4)]);
      }
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.setFontSize(14);

    const pageWidth = doc.internal.pageSize.getWidth();
    const textWidth = doc.getTextWidth(reportTitle);
    const x = (pageWidth - textWidth) / 2;
    doc.text(reportTitle, x, 20);

    doc.autoTable({
      startY: 30,
      head: [rows[0]],
      body: rows.slice(1),
      theme: 'grid',
      styles: { fontSize: 10, halign: 'center', valign: 'middle' },
      headStyles: { fillColor: [0, 70, 10], textColor: 255, fontStyle: 'bold' }
    });

    const pageCount = doc.getNumberOfPages();
    doc.setFontSize(8);
    doc.setFont('helvetica', 'italic');

    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i);
      const pageHeight = doc.internal.pageSize.getHeight();
      const footerText = `Page ${i} of ${pageCount}`;
      const footerX = (pageWidth - doc.getTextWidth(footerText)) / 2;
      doc.text(footerText, footerX, pageHeight - 10);
      doc.text("Abhitej's Machine Learning Project", 10, pageHeight - 10);
    }

    doc.save(`${CityName}_MaxMin_${startDate}_to_${endDate}.pdf`);
  } catch (err) {
    console.error('PDF generation failed:', err);
    alert(`Failed to generate PDF: ${err.message}`);
  }
}

// Logout Handler
function handleLogout() {
  showToast("Logging out...");
  setTimeout(() => {
    window.location.href = `${BASE_URL}/index.php?route=logout`;
  }, 1500);
}

// Excel Download
function downloadExcel() {
   submitDownloadForm(`${BASE_URL}/index.php?route=download_excel`);
}

// JSON Download
function downloadJson() {
    submitDownloadForm(`${BASE_URL}/index.php?route=download_json`);
}
// Helper to submit form for downloads
function submitDownloadForm(actionUrl) {
  const cityId = document.getElementById('citySelect').value;
  const startDate = document.getElementById('startDate').value;
  const endDate = document.getElementById('endDate').value;
  if (!cityId || !startDate || !endDate) {
    alert("Please select city and date range first.");
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = actionUrl;
  form.style.display = 'none';

  ['city_id', 'start_date', 'end_date'].forEach((name, i) => {
    const input = document.createElement('input');
    input.name = name;
    input.value = [cityId, startDate, endDate][i];
    form.appendChild(input);
  });

  document.body.appendChild(form);
  form.submit();
}

// Expose functions globally
window.downloadExcel = downloadExcel;
window.downloadJson = downloadJson;
window.downloadMaxMinPdf = downloadMaxMinPdf;
window.fetchTemperature = fetchTemperature;
window.handleLogout = handleLogout;