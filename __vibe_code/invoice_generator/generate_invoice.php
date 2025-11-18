<?php
require('fpdf.php'); // Dołącz FPDF

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
        $stmt = $pdo->prepare("SELECT invoice_number FROM invoices ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $last_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_invoice_number = $last_invoice ? (intval($last_invoice['invoice_number']) + 1) : 1;

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
        
        $sql = "INSERT INTO invoices (invoice_number_int, invoice_number, issue_date, seller_name_hash, seller_address_hash, buyer_name_hash, buyer_address_hash, service, quantity, unit_price, total)
        VALUES (:invoice_number, :issue_date, :seller_name_hash, :seller_address_hash, :buyer_name_hash, :buyer_address_hash, :service, :quantity, :unit_price, :total)";
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




// Inicjalizacja PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
// $pdf->SetDrawColor(255, 100, 0); //aby ustawić pomarańczowy kolor linii.

// $pdf->SetTextColor(255, 100, 0); //dla pomarańczowego tekstu.

// $pdf->SetFillColor(17, 17, 17);

$pdf->Cell(0,10,"Faktura nr $invoice_number_formatted",0,1,'C');

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,"Data wystawienia: {$data['issue_date']}",0,1);
$pdf->Cell(0,10,"Sprzedawca: {$data['seller']}",0,1);
if (!empty($data['seller_address'])) $pdf->Cell(0,10,"Adres sprzedawcy: {$data['seller_address']}",0,1);
$pdf->Cell(0,10,"Nabywca: {$data['buyer']}",0,1);
if (!empty($data['buyer_address'])) $pdf->Cell(0,10,"Adres nabywcy: {$data['buyer_address']}",0,1);
$pdf->Ln();

$pdf->Cell(60,10,'Nazwa uslugi',1);
$pdf->Cell(30,10,'Ilosc',1);
$pdf->Cell(40,10,'Cena jedn.',1);
$pdf->Cell(40,10,'Kwota',1,1);

$pdf->Cell(60,10,$data['service'],1);
$pdf->Cell(30,10,$data['quantity'],1);
$pdf->Cell(40,10,number_format($data['unit_price'],2),1);
$pdf->Cell(40,10,number_format($total,2),1,1);

$pdf->Ln();
$pdf->Cell(0,10,'Razem do zaplaty: '.number_format($total,2).' PLN',0,1);

$pdf->Ln();
$pdf->SetFont('Arial','I',10);
$pdf->MultiCell(0,8,"Sprzedawca korzysta ze zwolnienia podmiotowego z VAT.\nPodstawa: art. 113 ust. 1 i 9 ustawy o VAT.");

// Wysyła PDF do przeglądarki
$pdf->Output("Faktura_$new_invoice_number.pdf", 'I');
?>
