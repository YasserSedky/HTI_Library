<?php
$book = [
    'title' => 'مقدمة في الذكاء الاصطناعي',
    'author' => 'د. أحمد خالد',
    'section' => 'علوم',
    'category' => 'ذكاء اصطناعي',
    'publish_date' => '2024-01-01',
    'img' => 'https://placehold.co/200x280/34c759/fff?text=AI',
    'is_new' => true,
    'pdf' => 'assets/books/ai.pdf',
    'audio' => 'assets/audio/ai.mp3',
    'desc' => 'كتاب شامل يشرح مفاهيم الذكاء الاصطناعي من الصفر حتى الاحتراف...'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $book['title'] ?> | مكتبة HTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.2.67/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family:'Cairo',sans-serif; background:#f7faf7; }
        .book-cover { border-radius:14px; border:4px solid #e8fcee; box-shadow:0 4px 18px #34c75935; }
        .badge-new { background: #34c759; color:white; font-size:.85rem; border-radius:5px; }
        .main-title { color:#34c759; font-weight:bold; }
        .pdf-viewer { background:#24292f; border-radius: 10px; min-height: 500px; }
    </style>
</head>
<body>
    <div class="container py-4">
        <a href="index.php" class="btn btn-light mb-3"><i class="fa fa-arrow-right"></i> رجوع للرئيسية</a>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <img src="<?= $book['img'] ?>" class="book-cover mb-3" width="200" />
                <?php if($book['is_new']): ?><span class="badge badge-new">جديد</span><?php endif; ?>
            </div>
            <div class="col-md-8">
                <h2 class="main-title"> <?= $book['title'] ?> </h2>
                <div class="mb-2"> <b>المؤلف:</b> <?= $book['author'] ?> </div>
                <div class="mb-2"> <b>القسم:</b> <?= $book['section'] ?> | <b>الفئة:</b> <?= $book['category'] ?> </div>
                <div class="mb-2"> <b>تاريخ النشر:</b> <?= $book['publish_date'] ?> </div>
                <div class="mb-2"><b>الوصف:</b><br> <?= $book['desc'] ?> </div>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a href="#" class="btn btn-success px-4" id="btnViewPDF"><i class="fa fa-book-open"></i> تصفح الكتاب</a>
                    <a href="<?= $book['pdf'] ?>" download class="btn btn-outline-success"><i class="fa fa-download"></i> تنزيل نسخة PDF</a>
                    <?php if(!$book['audio']): ?><button class="btn btn-outline-warning" disabled><i class="fa fa-headphones"></i> لا يوجد نسخة صوتية</button><?php else: ?>
                    <button class="btn btn-warning" id="btnListen"><i class="fa fa-headphones"></i> سماع النسخة الصوتية</button>
                    <a href="<?= $book['audio'] ?>" download class="btn btn-outline-info"><i class="fa fa-cloud-download-alt"></i> تحميل صوتي</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <hr class="my-4" />
        <!-- PDF Viewer Modal -->
        <div id="pdfModal" style="display:none;">
            <h5 class="mb-3 main-title">تصفح الكتاب PDF</h5>
            <div class="pdf-viewer mb-3" id="pdfViewer"></div>
            <button class="btn btn-light" onclick="closePDF()">إغلاق</button>
        </div>
        <!-- Audio Modal -->
        <div id="audioModal" style="display:none;">
            <h5 class="mb-2 main-title">استماع للنسخة الصوتية</h5>
            <audio controls style="width:100%; max-width:424px;"><source src="<?= $book['audio'] ?>" type="audio/mp3"></audio>
            <button class="btn btn-light mt-2" onclick="closeAudio()">إغلاق</button>
        </div>
    </div>
    <script>
        // PDF.js عرض الكتاب داخل الصفحة
        function openPDF() {
            Swal.fire({
                html: $('#pdfModal').html(),
                width:'60vw',
                showCloseButton:true,
                showConfirmButton:false,
                didOpen: ()=>{
                    // تحميل PDF داخل SweetAlert
                    let url = '<?= $book['pdf'] ?>';
                    let pdfjsLib = window['pdfjs-dist/build/pdf'];
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.2.67/pdf.worker.min.js';
                    pdfjsLib.getDocument(url).promise.then(function(pdf) {
                        pdf.getPage(1).then(function(page) {
                            var viewport = page.getViewport({ scale:1.5 });
                            var canvas = document.createElement('canvas');
                            var ctx = canvas.getContext('2d');
                            canvas.height = viewport.height;
                            canvas.width = viewport.width;
                            page.render({canvasContext: ctx, viewport: viewport});
                            document.querySelector('.swal2-html-container #pdfViewer').appendChild(canvas);
                        });
                    });
                }
            });
        }
        $('#btnViewPDF').click(function(e){ e.preventDefault(); openPDF(); });
        function closePDF(){ Swal.close(); }
        // الصوت
        function openAudio() {
            Swal.fire({
                html: $('#audioModal').html(),
                width:'35vw',
                showCloseButton:true,
                showConfirmButton:false
            });
        }
        $('#btnListen').click(function(e){ e.preventDefault(); openAudio(); });
        function closeAudio(){ Swal.close(); }
    </script>
</body>
</html>
