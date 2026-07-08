<?php
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: login.php');
$db=new PDO('mysql:host=localhost;dbname=hti_library;charset=utf8mb4','root','');
$booksCount = $db->query('SELECT COUNT(*) FROM books')->fetchColumn();
$borrowsCount = $db->query('SELECT COUNT(*) FROM borrows')->fetchColumn();
$newBooks = $db->query('SELECT id,title,author,created_at FROM books WHERE is_new=1 ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
// --------- إعداد بيانات الرسم البياني -----------
$labels = $booksData = $borrowsData = [];
for($i=11;$i>=0;$i--) {
  $labels[] = date('Y-m', strtotime("-{$i} month"));
}
$bl = implode(",",array_fill(0,count($labels),'?'));
$bksStmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') m, COUNT(*) c FROM books WHERE created_at >= ? GROUP BY m");
$bksStmt->execute([date('Y-m-01',strtotime('-11 month'))]);
$bksArr = $bksStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$borStmt = $db->prepare("SELECT DATE_FORMAT(date_borrowed, '%Y-%m') m, COUNT(*) c FROM borrows WHERE date_borrowed >= ? GROUP BY m");
$borStmt->execute([date('Y-m-01',strtotime('-11 month'))]);
$borArr = $borStmt->fetchAll(PDO::FETCH_KEY_PAIR);
foreach($labels as $m) {
  $booksData[] = isset($bksArr[$m]) ? (int)$bksArr[$m] : 0;
  $borrowsData[] = isset($borArr[$m]) ? (int)$borArr[$m] : 0;
}
// فقط الشهور بشر/ascii باسم شهر
$labelsTxt = array_map(fn($m)=>date('M Y',strtotime($m.'-01')), $labels);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم الإدارة - مكتبة HTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
    <style>
        body{font-family:'Cairo',sans-serif;background:#f7faf7;}
        .sidebar {
            background:#fff;
            border-radius:13px;
            box-shadow:0 2px 12px #34c75922;
            min-height:80vh;
        }
        .main-title{color:#34c759;font-weight:bold;}
        .stat-card { border-radius:13px; background:#fff; box-shadow:0 1px 8px #34c75915; }
        .stat-cnt {font-size:2.8rem; color:#34c759; font-weight:700;}
        .nav-link.active { color: #34c759!important; font-weight: bold; }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm mb-3">
        <div class="container">
            <span class="navbar-brand mb-0 h1"><i class="fa fa-cogs"></i> لوحة تحكم الإدارة</span>
            <span>مرحبًا، <b><?= $_SESSION['admin_username'] ?></b></span>
            <a href="logout.php" class="btn btn-outline-danger ms-2">تسجيل خروج</a>
        </div>
    </nav>
    <div class="container-fluid">
      <div class="row g-3">
        <div class="col-lg-2">
          <div class="sidebar p-3">
            <h6 class="main-title mb-3"><i class="fa fa-gears"></i> القائمة</h6>
            <div class="list-group">
              <a href="dashboard.php" class="list-group-item nav-link active"><i class="fa fa-chart-bar"></i> لوحة التحكم</a>
              <a href="books.php" class="list-group-item nav-link"><i class="fa fa-book"></i> إدارة الكتب</a>
              <a href="sections.php" class="list-group-item nav-link"><i class="fa fa-layer-group"></i> الأقسام والفئات</a>
              <a href="borrows.php" class="list-group-item nav-link"><i class="fa fa-hand-holding"></i> الاستعارات</a>
              <a href="notifications.php" class="list-group-item nav-link"><i class="fa fa-bell"></i> الإشعارات</a>
            </div>
          </div>
        </div>
        <div class="col-lg-10">
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <div class="stat-card p-3 text-center"><i class="fa fa-book fa-2x mb-2 text-success"></i>
                  <div class="stat-cnt"> <?= $booksCount ?> </div><b>عدد الكتب</b></div>
              </div>
              <div class="col-md-4">
                <div class="stat-card p-3 text-center"><i class="fa fa-headphones fa-2x mb-2 text-warning"></i>
                  <div class="stat-cnt">-</div> <b>عدد مرات الاستماع</b></div>
              </div>
              <div class="col-md-4">
                <div class="stat-card p-3 text-center"><i class="fa fa-hand-holding fa-2x mb-2 text-primary"></i>
                  <div class="stat-cnt"> <?= $borrowsCount ?> </div><b>عدد الاستعارات</b></div>
              </div>
            </div>
            <div class="stat-card p-3 mb-4">
              <h5 class="main-title mb-3"><i class="fa fa-star"></i> أحدث الكتب المضافة</h5>
              <table class="table table-sm align-middle">
                <thead><tr><th>#</th><th>العنوان</th><th>المؤلف</th><th>تاريخ الإضافة</th></tr></thead>
                <tbody>
                <?php foreach($newBooks as $book): ?>
                <tr><td><?= $book['id'] ?></td><td><?= htmlspecialchars($book['title']) ?></td><td><?= htmlspecialchars($book['author']) ?></td><td><?= $book['created_at'] ?></td></tr>
                <?php endforeach; if(!count($newBooks)): ?>
                  <tr><td colspan="4" class="text-center">لا يوجد كتب جديدة حالياً.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="stat-card p-3">
              <h6 class="main-title mb-3"><i class="fa fa-chart-pie"></i> إحصائية تفاعلية - آخر ١٢ شهر</h6>
              <canvas id="statsChart" height="100"></canvas>
              <div class="text-end small text-secondary mt-1">
                <span style="color:#34c759;">■ كتب</span> / <span style="color:#2578fb;">■ استعارات</span>
              </div>
            </div>
        </div>
      </div>
    </div>
    <script>
const ctx = document.getElementById('statsChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?=json_encode($labelsTxt)?>,
    datasets: [
      {
        label: 'كتب جديدة',
        data: <?=json_encode($booksData)?>,
        backgroundColor: '#34c759cc',
        borderRadius: 7
      },
      {
        label: 'استعارات',
        data: <?=json_encode($borrowsData)?>,
        backgroundColor: '#2578fbcc',
        borderRadius: 7
      }
    ]
  },
  options: {
    plugins:{legend:{display:true}},
    interaction: {mode:'index',intersect:false},
    scales: {y:{beginAtZero:true, precision:0}}
  }
});
    </script>
</body>
</html>
