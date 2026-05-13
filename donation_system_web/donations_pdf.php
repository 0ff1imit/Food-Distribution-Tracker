<?php
session_start();
define('BASE_URL', '/donation_system');
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';
requireLogin();

// Require FPDF: https://www.fpdf.org/ – place in vendor/fpdf/fpdf.php
require_once __DIR__.'/vendor/fpdf/fpdf.php';

$donations = $pdo->query("
    SELECT d.*, dn.name AS donor_name
    FROM donations d JOIN donors dn ON dn.id=d.donor_id
    ORDER BY d.donation_date DESC")->fetchAll();

$totalAmount   = array_sum(array_column($donations,'amount'));
$totalReceived = count(array_filter($donations, fn($d)=>$d['status']==='Received'));
$totalPending  = count(array_filter($donations, fn($d)=>$d['status']==='Pending'));

class DonationPDF extends FPDF {
    function Header() {
        $this->SetFillColor(30, 58, 95);
        $this->Rect(0, 0, 297, 30, 'F');
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 8);
        $this->Cell(0, 10, 'DonateMS - Donation Management System', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(200, 220, 255);
        $this->SetX(10);
        $this->Cell(0, 6, 'Donation Report  |  Generated: '.date('F d, Y  h:i A'), 0, 1, 'C');
        $this->Ln(8);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(130,130,130);
        $this->Cell(0,10,'Page '.$this->PageNo().' | DonateMS Confidential Report',0,0,'C');
    }
    function SectionTitle($title) {
        $this->SetFont('Arial','B',10);
        $this->SetFillColor(37,99,235);
        $this->SetTextColor(255,255,255);
        $this->Cell(0,8,' '.$title,0,1,'L',true);
        $this->SetTextColor(0,0,0);
        $this->Ln(2);
    }
    function TableHeader($cols, $widths) {
        $this->SetFont('Arial','B',8);
        $this->SetFillColor(241,245,249);
        $this->SetTextColor(100,116,139);
        $this->SetDrawColor(226,232,240);
        foreach($cols as $i=>$col) {
            $this->Cell($widths[$i],7,$col,1,0,'C',true);
        }
        $this->Ln();
        $this->SetTextColor(30,41,59);
    }
}

$pdf = new DonationPDF('L','mm','A4');
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

// Summary boxes
$pdf->SetFont('Arial','B',9);
$boxes=[
    ['Total Donations', count($donations), [219,234,254],[30,64,175]],
    ['Total Amount (Received)', '&#8369;'.number_format($totalAmount,2), [220,252,231],[6,78,59]],
    ['Received', $totalReceived, [220,252,231],[6,78,59]],
    ['Pending', $totalPending, [254,249,195],[146,64,14]],
];
$x = 10; $bw = 63;
foreach($boxes as $b) {
    [$label,$val,$bg,$fg] = $b;
    $pdf->SetFillColor($bg[0],$bg[1],$bg[2]);
    $pdf->SetDrawColor(200,200,200);
    $pdf->SetXY($x, $pdf->GetY());
    $pdf->Cell($bw, 16, '', 1, 0, 'C', true);
    $pdf->SetXY($x+1, $pdf->GetY()-16+2);
    $pdf->SetTextColor($fg[0],$fg[1],$fg[2]);
    $pdf->SetFont('Arial','B',14); $pdf->Cell($bw-2, 7, html_entity_decode($val,ENT_HTML5,'UTF-8'), 0, 2, 'C');
    $pdf->SetFont('Arial','',7); $pdf->SetTextColor(80,80,80);
    $pdf->SetX($x+1); $pdf->Cell($bw-2, 5, $label, 0, 0, 'C');
    $x += $bw + 4;
}
$pdf->Ln(22);
$pdf->SetTextColor(0,0,0);

// Table
$pdf->SectionTitle('DONATION RECORDS');
$cols   = ['#','Donor','Amount (₱)','Item','Qty','Date','Status','Notes'];
$widths = [8, 45, 28, 40, 12, 28, 22, 94];
$pdf->TableHeader($cols, $widths);

$rowEven = false;
foreach($donations as $i=>$d) {
    $pdf->SetFont('Arial','',8);
    $fill = $rowEven ? [248,250,252] : [255,255,255];
    $pdf->SetFillColor($fill[0],$fill[1],$fill[2]);
    $statusColors=['Pending'=>[254,243,199],'Received'=>[209,250,229],'Distributed'=>[219,234,254]];
    $sc = $statusColors[$d['status']] ?? [255,255,255];

    $maxH = 6;
    $pdf->Cell($widths[0],$maxH,$i+1,'LB',0,'C',true);
    $pdf->Cell($widths[1],$maxH,substr($d['donor_name'],0,32),'B',0,'L',true);
    $pdf->SetTextColor($d['amount']>0?0:150, $d['amount']>0?128:150, $d['amount']>0?0:150);
    $pdf->Cell($widths[2],$maxH,$d['amount']>0?number_format($d['amount'],2):'—','B',0,'R',true);
    $pdf->SetTextColor(30,41,59);
    $pdf->Cell($widths[3],$maxH,substr($d['item']??'—',0,28),'B',0,'L',true);
    $pdf->Cell($widths[4],$maxH,$d['quantity']?:0,'B',0,'C',true);
    $pdf->Cell($widths[5],$maxH,date('M d, Y',strtotime($d['donation_date'])),'B',0,'C',true);
    $pdf->SetFillColor($sc[0],$sc[1],$sc[2]);
    $pdf->Cell($widths[6],$maxH,$d['status'],'B',0,'C',true);
    $pdf->SetFillColor($fill[0],$fill[1],$fill[2]);
    $pdf->Cell($widths[7],$maxH,substr($d['notes']??'',0,50),'RB',1,'L',true);
    $rowEven = !$rowEven;
}

// Totals row
$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(30,58,95);
$pdf->SetTextColor(255,255,255);
$pdf->Cell($widths[0]+$widths[1],7,'TOTAL',1,0,'C',true);
$pdf->Cell($widths[2],7,'&#8369;'.number_format($totalAmount,2),1,0,'R',true);
$pdf->Cell(array_sum(array_slice($widths,3)),7,count($donations).' donation(s)',1,1,'C',true);

$pdf->SetTextColor(0,0,0);
$pdf->Ln(5);
$pdf->SetFont('Arial','I',7);
$pdf->SetTextColor(150,150,150);
$pdf->Cell(0,5,'This report was automatically generated by DonateMS on '.date('F d, Y \a\t h:i A').'. Confidential.',0,1,'C');

$pdf->Output('D','DonationReport_'.date('Ymd_His').'.pdf');