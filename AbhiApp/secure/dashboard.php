<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/oauthDB.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// 1. Validate session token
$accessToken = $_SESSION['access_token'] ?? '';
$tokenExpiry = $_SESSION['token_expires'] ?? 0;

if (! $accessToken || time() >= $tokenExpiry) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// 2. Validate token in DB
$stmt = $pdo_user->prepare("SELECT user_id FROM access_tokens WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$accessToken]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (! $tokenRow) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized Access – Invalid or expired token"]);
    exit;
}

// 3. Fetch user details
$userID = $tokenRow['user_id'];
$stmt = $pdo_user->prepare("SELECT name, email, username FROM users WHERE id = ?");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (! $user) {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
    exit;
}

// 4. Prepare user data
$userName  = $user['name']  ?? 'Test User';
$userEmail = $user['email'] ?? 'test@test.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Temperature Dashboard</title>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/secure/assets/Style/myStyle.css?v=1.2"> <!-- My style sheet -->
  <script>
    window.tokenExpiry = <?php echo json_encode($tokenExpiry); ?>;
    window.userName    = <?php echo json_encode($userName); ?>;
    window.BASE_URL = <?php echo json_encode(BASE_URL); ?>;
  </script>

  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
  <script src="<?php echo BASE_URL; ?>/secure/assets/js/my.js?v=1.1" defer></script> <!-- My JS file -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
</head>
<body>

<h1 class="center-heading">Temperature Dashboard</h1>

<div class="user-panel">
  <div class="user-info">
    <div class="welcome-text">Welcome <span id="userName">Abhitej</span></div>
    <div id="countdown">Session expires in: <span class="token-timer">--:--</span></div>
    <button class="logout-btn" onclick="handleLogout()">Logout</button>
  </div>
</div>

<div class="card">
  <div class="form-row">
    <select id="citySelect">
      <option value="">Select City</option>
    </select>

    <div class="datepicker-wrapper">
      <input type="text" id="startDate" value="2015-01-01" class="date-picker">
      <span class="calendar-icon">📅</span>
    </div>
    <div class="datepicker-wrapper">
      <input type="text" id="endDate" value="2015-01-31" class="date-picker">
      <span class="calendar-icon">📅</span>
    </div>
  </div>

  <div class="form-row">
    <button onclick="fetchTemperature()">View Temperature Data</button>
    <button id="downloadMaxMinPdf" class="btn btn-outline-primary ms-2" onclick="downloadMaxMinPdf()">Max-Min PDF Download</button>
    <button id="downloadExcelBtn">Download Excel</button>
    <button id="downloadJsonBtn">Download JSON</button>
  </div>
</div>

<div id="loadingSpinner" style="display:none; text-align:center; margin-top:20px;">
  <div class="spinner"></div>
  <div style="font-weight:bold; margin-top:10px;">Fetching Temperature data...</div>
</div>

<div class="card">
  <div id="tableWrapper">
    <table id="tempTable">
      <thead></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Toast Notification -->
<div id="toast">
  <span class="icon">⚠️</span>
  <span class="message">End Date should be greater than Start Date</span>
  <button class="close-btn">&times;</button>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    loadCities();

    document.querySelector('#downloadExcelBtn').addEventListener('click', downloadExcel);
    document.querySelector('#downloadJsonBtn').addEventListener('click', downloadJson);

    const nameSpan = document.getElementById('userName');
    if (nameSpan && window.userName) {
      nameSpan.textContent = window.userName;
    }

    window.timerElement = document.querySelector('.token-timer');
    if (window.tokenExpiry && window.timerElement) {
      setInterval(updateCountdown, 1000);
      updateCountdown();
    }
  });

  $(function () {
    $("#startDate, #endDate").datepicker({
      minDate: new Date(2015, 0, 1),
      maxDate: new Date(2099, 11, 31),
      dateFormat: "yy-mm-dd",
      yearRange: "2015:2099",
      changeMonth: true,
      changeYear: true,
      onSelect: validateDates
    });
  });

  function validateDates() {
    const startVal = $("#startDate").val();
    const endVal   = $("#endDate").val();

    const startDate = $.datepicker.parseDate("yy-mm-dd", startVal);
    const endDate   = $.datepicker.parseDate("yy-mm-dd", endVal);

    if (endDate < startDate) {
      $("#endDate").val(startVal);
      showToast("End Date should be greater than Start Date");
    }
  }

  function showToast(message) {
    const toast = $("#toast");
    toast.find(".message").text(message);
    toast.addClass("show");

    setTimeout(() => toast.removeClass("show"), 3000);
  }

  $(".close-btn").on("click", () => $("#toast").removeClass("show"));
</script>

</body>
</html>