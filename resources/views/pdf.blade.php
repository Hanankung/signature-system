<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>PDF Sign System</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f1f3f6;
        }

        header {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            padding: 16px 30px;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .2);
        }

        .container {
            display: flex;
            height: calc(100vh - 70px);
        }

        .sidebar {
            width: 320px;
            background: white;
            padding: 20px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }

        .content {
            flex: 1;
            padding: 20px;
            overflow: auto;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .05);
        }

        .btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 8px;
            cursor: pointer;
        }

        .btn-blue {
            background: #2a5298;
            color: white;
        }

        .btn-red {
            background: #e74c3c;
            color: white;
        }

        .btn-gray {
            background: #ddd;
        }

        canvas {
            border: 1px solid #aaa;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
            cursor: crosshair;
        }

        .marker-row {
            font-size: 13px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .stat {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin: 5px 0;
        }
    </style>
</head>

<body>

    <header>
        📄 ระบบลงนามเอกสารอิเล็กทรอนิกส์
    </header>

    @if (!isset($doc))
        <div class="container">
            <div class="sidebar">
                <div class="card">
                    <h3>อัปโหลดเอกสาร</h3>
                    <form action="/pdf/upload" method="POST" enctype="multipart/form-data">
                        @csrf
                        <label>📄 PDF</label>
                        <input type="file" name="pdf" required><br><br>
                        <label>✍️ ลายเซ็น</label>
                        <input type="file" name="signature" accept="image/*" required><br><br>
                        <button class="btn btn-blue">เริ่มต้นลงนาม</button>
                    </form>
                </div>
            </div>
            <div class="content">
                <div class="card">
                    <h2>ยินดีต้อนรับ</h2>
                    <p>อัปโหลดไฟล์ PDF และลายเซ็น เพื่อเริ่มกระบวนการลงนามเอกสาร</p>
                </div>
            </div>
        </div>
    @else
        <div class="container">

            <div class="sidebar">
                <div class="card">
                    <h3>📊 สถานะเอกสาร</h3>
                    <div class="stat"><span>หน้า</span><span><span id="currentPage">1</span> /
                            {{ $doc->total_pages }}</span></div>
                    <div class="stat"><span>จุดเซ็น</span><span id="markerCount">0</span></div>
                </div>

                <div class="card">
                    <h3>🛠 เครื่องมือ</h3>
                    <button class="btn btn-gray" onclick="clearThisPage()">ล้างจุดหน้านี้</button>
                    <button class="btn btn-red" onclick="clearAll()">ล้างทั้งหมด</button>
                    <button class="btn btn-gray" onclick="renderPage(pageNum)">รีเฟรช</button>
                    <button class="btn btn-blue" onclick="saveMarkers()">บันทึกตำแหน่ง</button>

                    <button class="btn btn-blue" onclick="signNow()">ยืนยันและลงนาม</button>
                </div>

                <div class="card">
                    <h3>📍 รายการจุดเซ็น</h3>
                    <div id="markerList"></div>
                </div>

                <div class="card">
                    <h3>📖 วิธีใช้งาน</h3>
                    <p>1. คลิกบน PDF เพื่อวางลายเซ็น</p>
                    <p>2. กดบันทึกตำแหน่ง</p>
                    <p>3. กดยืนยันเพื่อลงนาม</p>
                </div>
            </div>

            <div class="content">
                <button onclick="prevPage()">◀</button>
                <button onclick="nextPage()">▶</button><br><br>
                <canvas id="pdfCanvas"></canvas>
            </div>
        </div>
    @endif

    <script>
        let pdfDoc = null,
            pageNum = 1;
        let canvas = document.getElementById('pdfCanvas');
        let ctx = canvas.getContext('2d');
        let markers = [];

        if (canvas) {
            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            pdfjsLib.getDocument('/storage/pdfs/{{ $doc->filename ?? '' }}').promise.then(pdf => {
                pdfDoc = pdf;
                renderPage(1);
            });
        }

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
                drawMarkers();
            });
        }

        function drawMarkers() {
            markers.filter(m => m.page === pageNum).forEach(m => {
                ctx.beginPath();
                ctx.arc(m.x, m.y, 6, 0, 2 * Math.PI);
                ctx.fillStyle = 'red';
                ctx.fill();
            });
            updateList();
        }

        canvas?.addEventListener('click', e => {
            let r = canvas.getBoundingClientRect();
            let m = {
                page: pageNum,
                x: e.clientX - r.left,
                y: e.clientY - r.top,
                canvas_width: canvas.width,
                canvas_height: canvas.height
            };
            markers.push(m);
            drawMarkers();
        });

        function updateList() {
            document.getElementById('markerCount').innerText = markers.length;
            let list = document.getElementById('markerList');
            list.innerHTML = '';
            markers.forEach((m, i) => {
                list.innerHTML +=
                    `<div class="marker-row">#${i+1} หน้า ${m.page} (${Math.round(m.x)},${Math.round(m.y)})</div>`;
            });
        }

        function clearThisPage() {
            markers = markers.filter(m => m.page !== pageNum);
            renderPage(pageNum);
        }

        function clearAll() {
            Swal.fire({
                title: "ลบจุดทั้งหมด?",
                text: "ต้องการลบตำแหน่งลายเซ็นทั้งหมดหรือไม่",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "ลบ",
                cancelButtonText: "ยกเลิก"
            }).then((res) => {
                if (res.isConfirmed) {
                    markers = [];
                    renderPage(pageNum);
                }
            });

        }

        function nextPage() {
            if (pageNum < pdfDoc.numPages) {
                pageNum++;
                renderPage(pageNum)
            }
        }

        function prevPage() {
            if (pageNum > 1) {
                pageNum--;
                renderPage(pageNum)
            }
        }

        function saveMarkers() {
            fetch('/pdf/save-markers/{{ $doc->id ?? '' }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    markers
                })
            }).then(() => {
                Swal.fire("บันทึกแล้ว", "ตำแหน่งลายเซ็นถูกบันทึกเรียบร้อย", "success");
            });

        }

        function signNow() {
            const docId = "{{ $doc->id ?? '' }}";

            if (!docId) {
                Swal.fire("ยังไม่มีเอกสาร", "กรุณาอัปโหลดเอกสารก่อน", "warning");
                return;
            }

            if (markers.length === 0) {
                Swal.fire("ยังไม่ได้เลือกจุดเซ็น", "กรุณาคลิกบนเอกสารเพื่อเลือกตำแหน่งลายเซ็น", "warning");
                return;
            }

            Swal.fire({
                title: "ยืนยันการลงนาม?",
                text: "คุณต้องการลงนามเอกสารฉบับนี้หรือไม่",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#2a5298",
                cancelButtonColor: "#aaa",
                confirmButtonText: "ลงนาม",
                cancelButtonText: "ยกเลิก"
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: "กำลังลงนาม...",
                    text: "กรุณารอสักครู่",
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/pdf/sign/${docId}`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        }
                    })
                    .then(res => res.blob())
                    .then(blob => {
                        // ดาวน์โหลดไฟล์
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.href = url;
                        a.download = "signed_document.pdf";
                        a.click();

                        Swal.fire({
                            title: "สำเร็จ 🎉",
                            text: "ลงนามเอกสารเรียบร้อยแล้ว",
                            icon: "success",
                            confirmButtonText: "ตกลง"
                        }).then(() => {
                            window.location.href = "/pdf";
                        });
                    });
            });
        }
    </script>

</body>

</html>
