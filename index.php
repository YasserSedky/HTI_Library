<?php
$db = new PDO('mysql:host=localhost;dbname=hti_library;charset=utf8mb4', 'root', '');
$latestNotifs = $db->query('SELECT n.*, b.title AS book_title FROM notifications n LEFT JOIN books b ON n.book_id=b.id ORDER BY n.id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = $db->query('SELECT COUNT(*) FROM notifications WHERE is_read=0')->fetchColumn();
// جلب كل الكتب مع القسم والفئة
$booksQ = $db->query('SELECT b.*, s.name section, c.name category FROM books b LEFT JOIN sections s ON b.section_id=s.id LEFT JOIN categories c ON b.category_id=c.id ORDER BY b.id DESC');
$booksArr = [];
while ($row = $booksQ->fetch(PDO::FETCH_ASSOC)) {
    // use default image if not available
    if (empty($row['cover_img'])) $row['cover_img'] = "https://placehold.co/120x170/34c759/fff?text=Book";
    $booksArr[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'author' => $row['author'],
        'section' => $row['section'] ?? 'غير مُحدد',
        'category' => $row['category'] ?? 'غير مُحدد',
        'publish_date' => $row['publish_date'],
        'img' => $row['cover_img'],
        'is_new' => (bool)$row['is_new'],
        'pdf_path' => $row['pdf_path'],
        'audio_path' => $row['audio_path']
    ];
}
$sectionsQ = $db->query('SELECT * FROM sections');
$sectionsArr = $sectionsQ->fetchAll(PDO::FETCH_ASSOC);
$categoriesQ = $db->query('SELECT * FROM categories');
$categoriesArr = $categoriesQ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة HTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fa-solid fa-book-open-reader me-2"></i> مكتبة HTI</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item me-3 position-relative">
                    <a class="nav-link" href="#" id="notifIcon">
                        <i class="fa-regular fa-bell fa-lg"></i>
                        <span class="noti-dot <?= $unreadCount ? '' : 'd-none' ?>" id="notifDot"></span>
                    </a>
                </li>
                <li class="nav-item me-2">
                    <button class="btn btn-outline-secondary px-3" id="themeToggle" aria-label="تبديل وضع الصفحة">
                        <i class="fa-regular fa-moon" id="themeIcon"></i>
                    </button>
                </li>
                <li class="nav-item me-2">
                    <button class="btn btn-success px-3" data-bs-toggle="modal" data-bs-target="#aiAssistantModal"><i class="fa fa-wand-magic-sparkles"></i> مساعد الذكاء</button>
                </li>
            </ul>
        </div>
    </nav>

    <!-- وصف أعلى الصفحة -->
    <div class="container">
        <div class="alert alert-success d-flex align-items-center mt-2 mb-4 fw-bold shadow-sm wow fadeInDown" role="alert" style="background: linear-gradient(91deg,#e8fff0 0%, #dbffef 60%, #fcfff9 100%); border-radius: 18px; border:none; font-size: 1.09em;">
            <i class="fa fa-book-open-reader me-2 fa-xl text-success"></i>
            <span class="flex-grow-1">مرحبًا بك في مكتبة HTI — المنصة الموثوقة للمعرفة الرقمية والبحث العلمي والكتب المتخصصة في الهندسة وعلوم الحاسوب.</span>
        </div>
    </div>

    <!-- AI Assistant Modal -->
    <div class="modal fade" id="aiAssistantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-sparkles me-2 text-success"></i> مساعد الذكاء الاصطناعي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="aiChat" class="ai-chat-wrapper">
                        <div id="aiMessages" class="ai-messages"></div>
                        <div class="ai-input-area">
                            <div class="input-group">
                                <textarea id="aiInput" class="form-control" rows="1" placeholder="اكتب سؤالك أو اطلب تلخيصًا... (Shift+Enter للسطر)"></textarea>
                                <button id="aiSend" class="btn btn-success" type="button"><i class="fa fa-paper-plane"></i></button>
                            </div>
                            <div class="ai-hint text-muted small mt-1">نصيحة: اكتب "لخص النص التالي:" ثم الصق النص.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="container mb-4">
        <div class="row align-items-center filter-bar p-3 mx-1">
            <div class="col-lg-4 mb-2 mb-lg-0">
                <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن كتاب/مؤلف...">
            </div>
            <div class="col-lg-3 mb-2 mb-lg-0">
                <select class="form-select" id="sectionFilter">
                    <option>كل الأقسام</option>
                </select>
            </div>
            <div class="col-lg-3 mb-2 mb-lg-0">
                <select class="form-select" id="categoryFilter">
                    <option>كل الفئات</option>
                </select>
            </div>
            <div class="col-lg-2">
                <button class="btn btn-success w-100" onclick="applyFilters()"><i class="fa fa-search"></i> بحث</button>
            </div>
        </div>
    </div>

    <!-- Books Grid -->
    <div class="container">
        <div class="row" id="booksGrid">
            <!-- الكتب تظهر بالـJS فقط-->
        </div>
    </div>

    <!-- إشعارات -->
    <div id="notificationsBox" style="display:none; position:fixed; top:85px; left:20px; max-width:330px; z-index:1020;">
        <div class="card shadow">
            <div class="card-header text-success fw-bold"><i class="fa fa-bell"></i> أحدث الإشعارات</div>
            <ul class="list-group list-group-flush" style="max-height:350px;overflow:auto;">
                <?php if (count($latestNotifs)): foreach ($latestNotifs as $nt): ?>
                        <li class="list-group-item <?= !$nt['is_read'] ? 'bg-light' : '' ?>">
                            <div class="mb-1"><i class="fa fa-bolt text-success"></i> <?= htmlspecialchars($nt['text']) ?></div>
                            <?php if ($nt['book_id']): ?><div class="text-secondary" style="font-size:.93em"><i class="fa fa-book text-info"></i> <?= htmlspecialchars($nt['book_title']) ?></div><?php endif; ?>
                            <small class="text-muted"> <?= date('Y-m-d', strtotime($nt['created_at'])) ?> </small>
                        </li>
                    <?php endforeach;
                else: ?>
                    <li class="list-group-item text-center">لا يوجد إشعارات حالياً!</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // تمرير كل الكتب والأقسام والفئات لـJS من PHP مباشرة
        let books = <?= json_encode($booksArr, JSON_UNESCAPED_UNICODE) ?>;
        let demoSections = <?= json_encode($sectionsArr, JSON_UNESCAPED_UNICODE) ?>;
        let demoCategories = <?= json_encode($categoriesArr, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/ai.js"></script>
</body>

</html>