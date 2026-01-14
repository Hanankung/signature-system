<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Models\PdfDocument;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    public function index()
    {
        return view('pdf');
    }

    public function preview($id)
    {
        $doc = PdfDocument::findOrFail($id);
        return view('pdf', compact('doc'));
    }

    public function signDocument($id)
    {
        $doc = PdfDocument::findOrFail($id);

        $pdfPath = storage_path('app/public/pdfs/' . $doc->filename);
        $signPath = storage_path('app/signatures/sign.png'); // ลายเซ็น

        $pageMarkers = $doc->page_markers; // มาจาก DB

        $fpdi = new \setasign\Fpdi\Tcpdf\Fpdi();
        $pageCount = $fpdi->setSourceFile($pdfPath);

        // =================== 🔽 ตรงนี้แหละ STEP 6 ===================
        for ($page = 1; $page <= $pageCount; $page++) {

            $template = $fpdi->importPage($page);
            $size = $fpdi->getTemplateSize($template);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($template);

            // ถ้ามี marker ในหน้านี้ → ค่อยเซ็น
            if (isset($pageMarkers[$page])) {
                foreach ($pageMarkers[$page] as $p) {
                    $fpdi->Image(
                        $signPath,
                        $p['x'] * 0.75,
                        $p['y'] * 0.75,
                        40
                    );
                }
            }
        }

        // =================== 🔼 STEP 6 จบ ===================

        $output = storage_path('app/signed_' . $doc->id . '.pdf');
        $fpdi->Output($output, 'F');

        return response()->download($output);
    }


    public function upload(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf'
        ]);

        $file = $request->file('pdf');
        $filename = time() . '.pdf';

        // 1) บันทึกไฟล์ลง public disk
        Storage::disk('public')->putFileAs('pdfs', $file, $filename);

        // 2) ได้ path จริงบน disk
        $realPath = storage_path('app/public/pdfs/' . $filename);

        // 3) เช็คว่ามีจริง
        if (!file_exists($realPath)) {
            dd("FILE NOT FOUND", $realPath);
        }

        // 4) นับจำนวนหน้า
        $fpdi = new Fpdi();
        $pageCount = $fpdi->setSourceFile($realPath);

        // 5) บันทึก DB
        $doc = PdfDocument::create([
            'name' => 'เอกสารลงนาม',
            'filename' => $filename,
            'total_pages' => $pageCount,
            'saved_at' => now()
        ]);

        return redirect('/pdf/preview/' . $doc->id);
    }

    public function saveMarkers(Request $request, $id)
    {
        $doc = PdfDocument::findOrFail($id);

        $markers = $request->markers;

        $request->validate([
            'markers' => 'required|array'
        ]);


        $pageMarkers = collect($markers)->groupBy('page');

        $doc->update([
            'markers' => $markers,
            'page_markers' => $pageMarkers,
            'marker_counter' => count($markers)
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function uploadSignature(Request $request, $id)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg'
        ]);

        $doc = PdfDocument::findOrFail($id);

        // เก็บลายเซ็นแบบแยกตามเอกสาร
        $filename = 'sign_' . $doc->id . '.png';

        Storage::disk('public')->putFileAs('signatures', $request->file('signature'), $filename);

        // เก็บชื่อไฟล์ไว้ใน DB
        $doc->update([
            'signature_file' => $filename
        ]);

        return back()->with('success', 'อัปโหลดลายเซ็นเรียบร้อย');
    }
}
