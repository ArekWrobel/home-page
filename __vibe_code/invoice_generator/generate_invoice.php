<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
// file_put_contents('debug_post.txt', print_r($_POST, true), FILE_APPEND);

// require('fpdf.php'); // Dołącz FPDF
require('tcpdf/tcpdf.php'); // Dołącz TCPDF

// Sprawdź parametr GET document_number
if (isset($_POST['document_number'])) {
    $new_invoice_number = intval($_POST['document_number']);
    // Pobierz dane z formularza
    $invoice_number_formatted = "TEST/{$new_invoice_number}/1/1900";
    $data = $_POST;
} else {
    // Dane do połączenia pobrane z pliku np. data.json
    $json = file_get_contents('../../config/config.json'); 

    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error reading the JSON file']);
        return;
    }

    $json_data = json_decode($json, true); 

    if ($json_data === null) {
        http_response_code(500);
        echo json_encode(['error' => 'Error decoding the JSON file']);
        return;
    }

    try {
        $pdo = new PDO(
            "mysql:host={$json_data['host']};dbname={$json_data['dbname']}",
            $json_data['user'],
            $json_data['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Pobranie ostatniego numeru faktury
        $stmt = $pdo->prepare("SELECT invoice_number_int FROM invoices ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $last_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_invoice_number = $last_invoice ? (intval($last_invoice['invoice_number_int']) + 1) : 1;

        function hashData($string) {
            return hash('sha256', $string);
        }
        $data = $_POST;
        $issue_date = $data['issue_date'];
        $dateString = $issue_date ?? '';

        $date_obj = DateTime::createFromFormat('Y-m-d', $dateString);
        $month = $date_obj->format('m');
        $year = $date_obj->format('Y');

        $invoice_number_formatted = "EZ/{$new_invoice_number}/{$month}/{$year}"; 
        // Anonimizacja danych osobowych
        $seller_name_hash = isset($data['seller']) ? hashData($data['seller']) : null;
        $seller_address_hash = !empty($data['seller_address']) ? hashData($data['seller_address']) : null;
        $buyer_name_hash = isset($data['buyer']) ? hashData($data['buyer']) : null;
        $buyer_address_hash = !empty($data['buyer_address']) ? hashData($data['buyer_address']) : null;
        $quantity = $data['quantity'];
        $unit_price = $data['unit_price'];
        $total = $data['unit_price'] * $data['quantity'];
        
        $sql = "INSERT INTO invoices (invoice_number_int, invoice_number, issue_date, seller_name_hash, seller_address_hash, buyer_name_hash, buyer_address_hash, service, quantity, unit_price, total)
        VALUES (:invoice_number_int, :invoice_number, :issue_date, :seller_name_hash, :seller_address_hash, :buyer_name_hash, :buyer_address_hash, :service, :quantity, :unit_price, :total)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':invoice_number_int'=> $new_invoice_number,
                ':invoice_number' => $invoice_number_formatted,
                ':issue_date' => $data['issue_date'],
                ':seller_name_hash' => $seller_name_hash,
                ':seller_address_hash' => $seller_address_hash,
                ':buyer_name_hash' => $buyer_name_hash,
                ':buyer_address_hash' => $buyer_address_hash,
                ':service' => $data['service'],
                ':quantity' => $quantity,
                ':unit_price' => $unit_price,
                ':total' => $total,
            ]);

        // Dodanie nowego numeru do bazy (kontynuacja ciągłości numeracji)
        //$stmt = $pdo->prepare("INSERT INTO invoices (invoice_number) VALUES (:inv)");
        //$stmt->execute([':inv' => $new_invoice_number]);

        // Pobierz dane z formularza
       

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        return;
    }
}

$total = $data['unit_price'] * $data['quantity'];
$quantity = $data['quantity'];
$issue_date = $data['issue_date'];
$unit_price = $data['unit_price'];
$seller = $data['seller'] ?? '';
$seller_address = $data['seller_address'] ?? '';
$buyer = $data['buyer'] ?? '';
$buyer_address = $data['buyer_address'] ?? '';
$service = $data['service'] ?? '';



// Inicjalizacja PDF
// $pdf = new FPDF();
// $pdf->SetFont('dejaVuSans', '', 12);
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('freesans','B',16);
// $pdf->SetTextColor(255, 100, 0); // Pomarańczowy
$pdf->Cell(0,10,"FAKTURA NR ".$invoice_number_formatted,0,1,'C');

// Separator
// $pdf->SetDrawColor(255, 100, 0);
// $pdf->Line(10, 32, 200, 32);

$pdf->SetFont('freesans','',11);


// Sekcja sprzedawcy
$pdf->SetFont('freesans','B',10);
$pdf->Cell(0,6,"SPRZEDAWCA:",0,1);
$pdf->SetFont('freesans','',9);
$pdf->Cell(0,5,$seller,0,1);
if (!empty($seller_address)) {
    $pdf->Cell(0,5,$seller_address,0,1);
}


// Sekcja nabywcy
$pdf->SetFont('freesans','B',10);
$pdf->Cell(0,6,"NABYWCA:",0,1);
$pdf->SetFont('freesans','',9);
$pdf->Cell(0,5,$buyer,0,1);
if (!empty($buyer_address)) {
    $pdf->Cell(0,5,$buyer_address,0,1);
}
$pdf->Ln(5);

// Data
$pdf->SetFont('freesans','',9);
$pdf->Cell(0,5,"Data wystawienia: ".$issue_date,0,1);
// $pdf->Ln(5);

// Tabela z produktami
$pdf->SetFont('freesans','B',9);
$pdf->SetFillColor(255, 100, 0);
$pdf->SetTextColor(17, 17, 17); // Ciemny tekst na tle
$pdf->Cell(100, 7, 'NAZWA USŁUGI', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'ILOŚĆ', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'CENA JED.', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'RAZEM', 1, 1, 'C', true);

// Dane produktu
$pdf->SetFont('freesans','',9);
$pdf->SetTextColor(21, 21, 21); // Biały tekst
$pdf->SetFillColor(255, 255, 255); // Ciemne tło

// Zawinięcie tekstu dla "Nazwa usługi"
$service_wrapped = wordwrap($service, 150, "\n");
$service_lines = explode("\n", $service_wrapped);
$max_lines = count($service_lines);

// Wysokość wiersza dla multiline
$row_height = 5 * $max_lines + $max_lines;// Dodatkowa przestrzeń między liniami

$x_start = $pdf->GetX();
$y_start = $pdf->GetY();

// Komórka z nazwą usługi (zawinięty tekst)

$pdf->MultiCell(100, 5, $service_wrapped, 1, 'L', true);


$y_after_service = $pdf->GetY();
$pdf->SetXY($x_start + 100, $y_start);

// Resztę kolumn na poziomie ostatniej linii tekstu
$pdf->Cell(25, $row_height, $quantity, 1, 0, 'C', true);
$pdf->Cell(35, $row_height, number_format($unit_price, 2, '.', ''), 1, 0, 'C', true);
$pdf->Cell(30, $row_height, number_format($total, 2, '.', ''), 1, 1, 'C', true);

$pdf->Ln(5);

// Separator
$pdf->SetDrawColor(180, 180, 180);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(3);

// Razem
$pdf->SetFont('freesans','B',11);
// $pdf->SetTextColor(255, 100, 0);
$pdf->Cell(140, 7, 'RAZEM DO ZAPŁATY:', 0, 0, 'R');
$pdf->SetFont('freesans','B',12);
$pdf->Cell(30, 7, number_format($total, 2, '.', '') . ' PLN', 0, 1, 'R');

$pdf->Ln(8);

// Adnotacja VAT
$pdf->SetFont('freesans','I',8);
$pdf->SetTextColor(180, 180, 180);
$pdf->MultiCell(140, 8, "Sprzedawca korzysta ze zwolnienia podmiotowego z VAT.\nPodstawa: art. 113 ust. 1 i 9 ustawy o VAT.",  1, 'L', true);

// Wysyła PDF do przeglądarki
$pdf->Output("Faktura_".$invoice_number_formatted.".pdf", 'I');
?>