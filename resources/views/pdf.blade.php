{{-- resources/views/pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>PDF Sign System</title>
</head>
<body>
<h3>อัปโหลด PDF และลายเซ็น</h3>

<form action="/pdf/sign" method="POST" enctype="multipart/form-data">
    @csrf

    <p>PDF:</p>
    <input type="file" name="pdf" required>

    <p>Signature (PNG โปร่งใส):</p>
    <input type="file" name="signature" required>

    <button type="submit">ประมวลผล</button>
</form>

</body>
</html>
