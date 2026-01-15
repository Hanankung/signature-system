<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>PDF Sign System</title>

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --thai-blue: #102a43;
            --thai-blue2: #1c4d7a;
            --thai-gold: #d4af37;
            --soft-bg: #f4f7fb;
        }

        * {
            box-sizing: border-box;
            font-family: "Sarabun", sans-serif;
        }

        body {
            margin: 0;
            background: var(--soft-bg);
        }

        /* ===== HEADER ===== */
        header {
            background: linear-gradient(90deg, #0b2540, #163f68);
            color: white;
            padding: 20px 40px;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .3);
            border-bottom: 4px solid var(--thai-gold);
        }

        /* ===== LAYOUT ===== */
        .container {
            display: flex;
            min-height: calc(100vh - 85px);
        }


        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 340px;
            background: #ffffff;
            padding: 20px;
            border-right: 2px solid #e0e6f0;
            overflow: visible;
            align-self: stretch;
        }


        /* ===== CONTENT ===== */
        .content {
            flex: 1;
            padding: 30px;
            background: #f6f9ff;
            overflow-y: auto;
        }


        /* ===== CARD ===== */
        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
            border-left: 6px solid var(--thai-blue);
            position: relative;
        }

        .card::after {
            content: "";
            position: absolute;
            right: 12px;
            top: 12px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--thai-gold);
            opacity: .3;
        }

        .card h3 {
            margin-top: 0;
            font-size: 18px;
            color: var(--thai-blue);
            border-bottom: 1px solid #e1e8f0;
            padding-bottom: 8px;
        }

        /* ===== STAT ===== */
        .stat {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 15px;
            color: #333;
        }

        /* ===== BUTTONS ===== */
        .btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            margin-top: 10px;
            cursor: pointer;
            transition: .2s;
        }

        .btn-blue {
            background: linear-gradient(90deg, #0b2540, #1c4d7a);
            color: white;
            box-shadow: 0 4px 12px rgba(28, 77, 122, .4);
        }

        .btn-blue:hover {
            filter: brightness(1.15);
        }

        .btn-red {
            background: linear-gradient(90deg, #b8322a, #e74c3c);
            color: white;
        }

        .btn-gray {
            background: #e4ebf4;
            color: #333;
        }

        /* ===== CANVAS ===== */
        canvas {
            border-radius: 14px;
            border: 2px solid #cfd8e6;
            background: white;
            box-shadow: 0 15px 35px rgba(16, 42, 67, .2);
        }

        /* ===== PAGE CONTROL ===== */
        .content button {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #cfd8e6;
            background: white;
            font-weight: 500;
        }

        /* ===== MARKERS ===== */
        .marker-row {
            font-size: 13px;
            padding: 7px 0;
            border-bottom: 1px dashed #ccc;
        }

        /* ===== FILE INPUT ===== */
        input[type=file] {
            width: 100%;
            padding: 9px;
            border-radius: 8px;
            border: 1px solid #cfd8e6;
            background: #f8fbff;
        }
    </style>
</head>

<body>

    <header>
        🏛 ระบบลงนามเอกสารอิเล็กทรอนิกส์
        <span style="font-size:14px;opacity:.8">
            (Government Digital Signature Platform)
        </span>
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
                    <h2 style="color:#102a43">ระบบลงนามเอกสารอิเล็กทรอนิกส์</h2>
                    <p style="color:#444">
                        แพลตฟอร์มสำหรับหน่วยงานภาครัฐ
                        ใช้ในการลงนามเอกสาร PDF แบบดิจิทัล
                        เพื่อเพิ่มความรวดเร็ว ความถูกต้อง และความปลอดภัยของเอกสารราชการ
                    </p>

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
