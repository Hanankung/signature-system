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

        if (!$doc->signature_file) {
            return back()->with('error', 'กรุณาอัปโหลดลายเซ็นก่อน');
        }

        if (!$doc->page_markers || count($doc->page_markers) == 0) {
            return back()->with('error', 'กรุณาเลือกตำแหน่งเซ็นก่อน');
        }

        $pdfPath = storage_path('app/public/pdfs/' . $doc->filename);
        $signPath = storage_path('app/public/signatures/' . $doc->signature_file);

        if (!file_exists($pdfPath)) dd("PDF not found", $pdfPath);
        if (!file_exists($signPath)) dd("SIGN not found", $signPath);

        $fpdi = new Fpdi();
        $pageCount = $fpdi->setSourceFile($pdfPath);

        foreach (range(1, $pageCount) as $page) {
            $tpl = $fpdi->importPage($page);
            $size = $fpdi->getTemplateSize($tpl);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($tpl);

            if (isset($doc->page_markers[$page])) {
                foreach ($doc->page_markers[$page] as $p) {
                    $canvasWidth  = $p['canvas_width'];
                    $canvasHeight = $p['canvas_height'];

                    $pdfX = ($p['x'] / $canvasWidth)  * $size['width'];
                    $pdfY = ($p['y'] / $canvasHeight) * $size['height'];

                    $fpdi->Image(
                        $signPath,
                        $pdfX,
                        $pdfY,
                        40
                    );
                }
            }
        }

        // 🔥 บันทึกไฟล์ลง public disk
        $signedFilename = 'signed_' . $doc->id . '.pdf';
        $signedPath = storage_path('app/public/signed/' . $signedFilename);

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists(dirname($signedPath))) {
            mkdir(dirname($signedPath), 0775, true);
        }

        $fpdi->Output($signedPath, 'F');

        // ตรวจว่ามีไฟล์จริง
        if (!file_exists($signedPath)) {
            dd("SIGN FILE NOT CREATED", $signedPath);
        }

        return response()->download($signedPath);
    }



    public function upload(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf',
            'signature' => 'required|image|mimes:png,jpg,jpeg'
        ]);

        // ===== 1. บันทึก PDF =====
        $pdfFile = $request->file('pdf');
        $pdfName = time() . '.pdf';

        Storage::disk('public')->putFileAs('pdfs', $pdfFile, $pdfName);
        $pdfPath = storage_path('app/public/pdfs/' . $pdfName);

        if (!file_exists($pdfPath)) {
            dd("PDF NOT SAVED", $pdfPath);
        }

        // ===== 2. นับหน้า =====
        $fpdi = new Fpdi();
        $pageCount = $fpdi->setSourceFile($pdfPath);

        // ===== 3. สร้าง record =====
        $doc = PdfDocument::create([
            'name' => 'เอกสารลงนาม',
            'filename' => $pdfName,
            'total_pages' => $pageCount,
            'saved_at' => now()
        ]);

        // ===== 4. บันทึกลายเซ็น =====
        $signName = 'sign_' . $doc->id . '.png';

        Storage::disk('public')->putFileAs(
            'signatures',
            $request->file('signature'),
            $signName
        );

        $doc->update([
            'signature_file' => $signName
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

        $filename = 'sign_' . $doc->id . '.png';

        Storage::disk('public')->putFileAs(
            'signatures',
            $request->file('signature'),
            $filename
        );

        $doc->update([
            'signature_file' => $filename
        ]);

        return back()->with('success', 'อัปโหลดลายเซ็นเรียบร้อย');
    }
}
