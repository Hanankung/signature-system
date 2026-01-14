{{-- resources/views/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>PDF Sign System</title>

    {{-- PDF.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <style>
        canvas {
            border: 1px solid #000;
            cursor: crosshair;
        }
    </style>
</head>

<body>

    <h3>ระบบเซ็นเอกสาร PDF</h3>

    @if (!isset($doc))
        {{-- ================== ส่วนอัปโหลด ================== --}}
        <h4>อัปโหลดเอกสาร</h4>

        <form action="/pdf/upload" method="POST" enctype="multipart/form-data">
            @csrf

            <p>📄 เลือกไฟล์ PDF</p>
            <input type="file" name="pdf" required>

            <p>✍️ เลือกลายเซ็น (PNG/JPG)</p>
            <input type="file" name="signature" accept="image/*" required>

            <br><br>
            <button type="submit">อัปโหลดและไปเลือกตำแหน่งเซ็น</button>
        </form>
    @else
        {{-- ================== ส่วน Preview + Marker ================== --}}
        <h4>ดูเอกสารก่อนเซ็น</h4>

        <p>
            หน้า <span id="currentPage">1</span> /
            {{ $doc->total_pages }}
        </p>

        <canvas id="pdfCanvas"></canvas>

        <br><br>
        <button onclick="prevPage()">◀ หน้าก่อน</button>
        <button onclick="nextPage()">หน้าถัดไป ▶</button>

        <br><br>
        <button onclick="saveMarkers()">บันทึกตำแหน่งเซ็น</button>
        <form action="/pdf/sign/{{ $doc->id }}" method="POST">
            @csrf
            <button type="submit">✅ ยืนยันเซ็นเอกสาร</button>
        </form>

        <script>
            let pdfDoc = null;
            let pageNum = 1;
            let canvas = document.getElementById('pdfCanvas');
            let ctx = canvas.getContext('2d');
            let markers = [];

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            pdfjsLib.getDocument('/storage/pdfs/{{ $doc->filename }}').promise.then(pdf => {
                pdfDoc = pdf;
                renderPage(pageNum);
            });

            function renderPage(num) {
                pdfDoc.getPage(num).then(page => {
                    let viewport = page.getViewport({
                        scale: 1.5
                    });
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    page.render({
                        canvasContext: ctx,
                        viewport: viewport
                    });

                    document.getElementById('currentPage').innerText = num;

                    markers.filter(m => m.page === pageNum).forEach(m => {
                        drawMarker(m.x, m.y);
                    });
                });
            }

            function nextPage() {
                if (pageNum < pdfDoc.numPages) {
                    pageNum++;
                    renderPage(pageNum);
                }
            }

            function prevPage() {
                if (pageNum > 1) {
                    pageNum--;
                    renderPage(pageNum);
                }
            }

            canvas.addEventListener('click', function(e) {
                let rect = canvas.getBoundingClientRect();
                let x = e.clientX - rect.left;
                let y = e.clientY - rect.top;

                let marker = {
                    page: pageNum,
                    x: x,
                    y: y,
                    canvas_width: canvas.width,
                    canvas_height: canvas.height
                };


                markers.push(marker);
                drawMarker(x, y);
            });

            function drawMarker(x, y) {
                ctx.beginPath();
                ctx.arc(x, y, 6, 0, 2 * Math.PI);
                ctx.fillStyle = 'red';
                ctx.fill();
            }

            function saveMarkers() {
                fetch('/pdf/save-markers/{{ $doc->id }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            markers
                        })
                    })
                    .then(res => res.json())
                    .then(() => alert('บันทึกตำแหน่งเรียบร้อยแล้ว'));
            }
        </script>
    @endif

</body>

</html>
