<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php'; // Adjust path based on file location
require __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/myTempdb.php';// Database connection

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;

// ─────────────────────────────────────────────────────────────
// Input Validation
// ─────────────────────────────────────────────────────────────

$cityId     = $_POST['city_id']     ?? null;
$startDate  = $_POST['start_date']  ?? null;
$endDate    = $_POST['end_date']    ?? null;

if (!$cityId || !$startDate || !$endDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// ─────────────────────────────────────────────────────────────
// Fetch Temperature Data
// ─────────────────────────────────────────────────────────────

try {
    $stmt = $pdo->prepare("
        SELECT dd.temperature_date AS date,
               HOUR(dtm.temperature_time) AS hour,
               dtemp.temperature_value
        FROM data_temperature dtemp
        JOIN data_city dc ON dtemp.id_city = dc.id
        JOIN data_date dd ON dtemp.id_date = dd.id
        JOIN data_time dtm ON dtemp.id_time = dtm.id
        WHERE dc.id = :cid AND dd.temperature_date BETWEEN :start AND :end
        ORDER BY dd.temperature_date, dtm.temperature_time
    ");
    $stmt->execute([
        ':cid'   => $cityId,
        ':start' => $startDate,
        ':end'   => $endDate
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ─────────────────────────────────────────────────────────
    // Group Data by Date
    // ─────────────────────────────────────────────────────────

    $grouped = [];
    foreach ($rows as $row) {
        $date = $row['date'];
        $hour = (int)$row['hour'];
        $temp = round((float)$row['temperature_value'], 4);

        if (!isset($grouped[$date])) {
            $grouped[$date] = array_fill(0, 24, null);
        }
        $grouped[$date][$hour] = $temp;
    }

    // ─────────────────────────────────────────────────────────
    // Prepare XLSX Writer
    // ─────────────────────────────────────────────────────────

    $writer = new Writer();
    $writer->openToBrowser('temperature_data.xlsx');

    // Header Row
    // Define border parts
        $borderTop = new BorderPart(Border::TOP);
        $borderBottom = new BorderPart(Border::BOTTOM);
        $borderLeft = new BorderPart(Border::LEFT);
        $borderRight = new BorderPart(Border::RIGHT);

        // Create full border
        $fullBorder = new Border($borderTop, $borderBottom, $borderLeft, $borderRight);

    // Define grey header style
        $headerStyle = (new Style())
            ->setBackgroundColor(Color::LIGHT_GREY)
            ->setFontColor(Color::BLACK)
            ->setFontBold()
            ->setBorder($fullBorder);// Add border to header style

    $header = ['Date'];
    $hcells = [Cell::fromValue($header[0], $headerStyle)];
    for ($h = 0; $h < 24; $h++) {
        $header = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ' Hrs';
        $hcells[] = Cell::fromValue($header, $headerStyle);
    }
    $writer->addRow(new Row($hcells));

    // Styles
   $hotStyle = (new Style())
        ->setBackgroundColor(Color::RED)
        ->setFontColor(Color::WHITE)
        ->setBorder($fullBorder);      // Add border to hot style
       

    $defaultStyle = (new Style())
        ->setBorder($fullBorder);// Add border to default style

    // ─────────────────────────────────────────────────────────
    // Write Data Rows
    // ─────────────────────────────────────────────────────────

    foreach ($grouped as $date => $temps) {
    $cells = [Cell::fromValue($date, $defaultStyle)];
    // Fill each hour with temperature values or null
    
    foreach ($temps as $temp) {
    $style = $temp > 35 ? $hotStyle : $defaultStyle;
    $cells[] = Cell::fromValue($temp, $style);
    }

    $writer->addRow(new Row($cells));
}

    $writer->close();
    exit;

} catch (Exception $e) {
    error_log("Export failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Export failed']);
    exit;
}