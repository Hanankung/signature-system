<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfController extends Controller
{
    public function index()
    {
        return view('pdf');
    }

    public function sign(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf',
            'signature' => 'required|file|mimes:png'
        ]);

        // ========== ตรวจสอบว่าไฟล์ถูกส่งมาจริงไหม ==========
        if (!$request->hasFile('pdf')) {
            return 'ไม่ได้เลือกไฟล์ PDF';
        }

        if (!$request->file('pdf')->isValid()) {
            return 'ไฟล์ PDF อัปโหลดไม่สำเร็จ';
        }

        if (!$request->hasFile('signature')) {
            return 'ไม่ได้เลือกรูปเซ็น';
        }

        if (!$request->file('signature')->isValid()) {
            return 'ไฟล์ลายเซ็นอัปโหลดไม่สำเร็จ';
        }
        // ====================================================


        // ==== อัปโหลดไฟล์ ====
        $pdfFile = $request->file('pdf');
        $signFile = $request->file('signature');

        $pdfName = 'input.pdf';
        $signName = 'sign.png';

        $pdfFile->move(storage_path('app/pdfs'), $pdfName);
        $signFile->move(storage_path('app/signatures'), $signName);

        $fullPdfPath = storage_path('app/pdfs/' . $pdfName);
        $fullSignPath = storage_path('app/signatures/' . $signName);

        if (!file_exists($fullPdfPath)) {
            return "PDF ไม่ถูกอัปโหลดเข้า storage/app/pdfs";
        }

        // ==== อ่าน PDF ====
        $parser = new Parser();
        $pdf = $parser->parseFile($fullPdfPath);
        $text = $pdf->getText();

        if (strpos($text, "ลงนาม") === false) {
            return "ไม่พบคำว่า ลงนาม";
        }

        // ==== วางลายเซ็น ====
        $fpdi = new \setasign\Fpdi\Tcpdf\Fpdi();
        $pageCount = $fpdi->setSourceFile($fullPdfPath);

        for ($i = 1; $i <= $pageCount; $i++) {

            $template = $fpdi->importPage($i);
            $size = $fpdi->getTemplateSize($template);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($template);

            if ($i == 1) {
                $fpdi->Image($fullSignPath, 120, 240, 40);
            }
        }

        $output = storage_path('app/signed.pdf');
        $fpdi->Output($output, 'F');

        return response()->download($output);
    }
}
