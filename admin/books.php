<?php
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: login.php');
$db=new PDO('mysql:host=localhost;dbname=hti_library;charset=utf8mb4','root','');
// فلاتر البحث
$filter = [
    'title' => $_GET['title'] ?? '',
    'author' => $_GET['author'] ?? '',
    'section_id' => $_GET['section_id'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'is_new' => $_GET['is_new'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];
$where = [];
$params = [];
if ($filter['title'])       { $where[] = "b.title LIKE ?"; $params[] = "%{$filter['title']}%"; }
if ($filter['author'])      { $where[] = "b.author LIKE ?"; $params[] = "%{$filter['author']}%"; }
if ($filter['section_id'])  { $where[] = "b.section_id=?"; $params[] = $filter['section_id']; }
if ($filter['category_id']) { $where[] = "b.category_id=?"; $params[] = $filter['category_id']; }
if ($filter['is_new'] !== '' && $filter['is_new'] !== null) { $where[] = "b.is_new=?"; $params[] = $filter['is_new']; }
if ($filter['date_from'])   { $where[] = "b.publish_date >= ?"; $params[] = $filter['date_from']; }
if ($filter['date_to'])     { $where[] = "b.publish_date <= ?"; $params[] = $filter['date_to']; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$sql = "SELECT b.id, b.title, b.author, b.publish_date, s.name section, c.name category, b.is_new, b.copies, b.cover_img, b.pdf_path, b.audio_path, b.description, b.section_id, b.category_id FROM books b LEFT JOIN sections s ON b.section_id=s.id LEFT JOIN categories c ON b.category_id=c.id $whereSql ORDER BY b.id DESC";
$st = $db->prepare($sql);
$st->execute($params);
$books = $st->fetchAll(PDO::FETCH_ASSOC);
$sections = $db->query('SELECT * FROM sections')->fetchAll(PDO::FETCH_ASSOC);
$categories = $db->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
$msg = '';
$editBook = null;
// حذف كتاب إذا طُلب
if(isset($_GET['delete'])){
  $id = intval($_GET['delete']);
  $b = $db->prepare('SELECT cover_img, pdf_path, audio_path FROM books WHERE id=?');
  $b->execute([$id]);
  $file = $b->fetch(PDO::FETCH_ASSOC);
  $db->prepare('DELETE FROM books WHERE id=?')->execute([$id]);
  foreach(['cover_img','pdf_path','audio_path'] as $f){
    if($file[$f] && file_exists(__DIR__.'/../'.$file[$f])) unlink(__DIR__.'/../'.$file[$f]);
  }
  $msg = 'تم حذف الكتاب بنجاح!';
  header('Location: books.php?deleted=1'); exit;
}
// عرض بيانات كتاب للتعديل
if(isset($_GET['edit'])){
  $id=intval($_GET['edit']);
  $q=$db->prepare('SELECT * FROM books WHERE id=?');
  $q->execute([$id]);
  $editBook = $q->fetch(PDO::FETCH_ASSOC);
}
// إضافة/تعديل كتاب
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $section_id = intval($_POST['section_id']);
    $category_id = intval($_POST['category_id']);
    $publish_date = $_POST['publish_date'];
    $desc = $_POST['desc'] ?? '';
    $is_new = isset($_POST['is_new']) ? intval($_POST['is_new']) : 0;
    $copies = intval($_POST['copies']);
    $dir_uploads = __DIR__ . '/../uploads/';
    if(!is_dir($dir_uploads)) mkdir($dir_uploads,0775,true);
    $cover_img = $id && isset($_POST['old_cover_img']) ? $_POST['old_cover_img'] : '';
    if(isset($_FILES['cover_img']) && $_FILES['cover_img']['error']===0){
        $ext = pathinfo($_FILES['cover_img']['name'], PATHINFO_EXTENSION);
        $cover_img = 'cover_'.uniqid().'.'.$ext;
        move_uploaded_file($_FILES['cover_img']['tmp_name'], $dir_uploads.$cover_img);
        if($id && $_POST['old_cover_img'] && file_exists(__DIR__.'/../'.$_POST['old_cover_img'])) unlink(__DIR__.'/../'.$_POST['old_cover_img']);
        $cover_img = 'uploads/'.$cover_img;
    }
    $pdf_path = $id && isset($_POST['old_pdf']) ? $_POST['old_pdf'] : '';
    if(isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error']===0){
        $ext = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        if(strtolower($ext)!=='pdf') $msg='الملف يجب أن يكون PDF.';
        else {
          $pdf_path = 'book_'.uniqid().'.pdf';
          move_uploaded_file($_FILES['pdf_file']['tmp_name'], $dir_uploads.$pdf_path);
          if($id && $_POST['old_pdf'] && file_exists(__DIR__.'/../'.$_POST['old_pdf'])) unlink(__DIR__.'/../'.$_POST['old_pdf']);
          $pdf_path = 'uploads/'.$pdf_path;
        }
    }
    $audio_path = $id && isset($_POST['old_audio']) ? $_POST['old_audio'] : '';
    if(isset($_FILES['audio_file']) && $_FILES['audio_file']['error']===0){
        $ext = pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION);
        if(!in_array(strtolower($ext),['mp3','mpeg']))
           $msg='الملف الصوتي يجب أن يكون بصيغة mp3.';
        else {
          $audio_path = 'audio_'.uniqid().'.mp3';
          move_uploaded_file($_FILES['audio_file']['tmp_name'], $dir_uploads.$audio_path);
          if($id && $_POST['old_audio'] && file_exists(__DIR__.'/../'.$_POST['old_audio'])) unlink(__DIR__.'/../'.$_POST['old_audio']);
          $audio_path = 'uploads/'.$audio_path;
        }
    }
    if(!$msg){
      if($id){ // تعديل
        $q = $db->prepare('UPDATE books SET title=?, author=?, section_id=?, category_id=?, publish_date=?, cover_img=?, pdf_path=?, audio_path=?, is_new=?, description=?, copies=? WHERE id=?');
        $q->execute([$title,$author,$section_id,$category_id,$publish_date,$cover_img,$pdf_path,$audio_path,$is_new,$desc,$copies,$id]);
        $msg = 'تم تعديل الكتاب بنجاح!';
        header('Location: books.php?updated=1'); exit;
      }else{ // إضافة
        $q = $db->prepare('INSERT INTO books(title,author,section_id,category_id,publish_date,cover_img,pdf_path,audio_path,is_new,description,copies,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())');
        $q->execute([
          $title,
          $author,
          $section_id,
          $category_id,
          $publish_date,
          $cover_img,
          $pdf_path,
          $audio_path,
          $is_new,
          $desc,
          $copies
        ]);
        $msg = 'تمت إضافة الكتاب بنجاح!';
        header('Location: books.php?done=1'); exit;
      }
    }
}
if(isset($_GET['done'])) $msg='تمت إضافة الكتاب بنجاح!';
if(isset($_GET['updated'])) $msg='تم تعديل الكتاب بنجاح!';
if(isset($_GET['deleted'])) $msg='تم حذف الكتاب بنجاح!';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الكتب - لوحة تحكم مكتبة HTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Cairo',sans-serif;background:#f7faf7;}
        .stat-card { border-radius:13px; background:#fff; box-shadow:0 1px 8px #34c75915; }
        .main-title{color:#34c759;font-weight:bold;}
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm mb-3">
        <div class="container">
            <span class="navbar-brand mb-0 h1"><i class="fa fa-cogs"></i> لوحة تحكم الإدارة</span>
            <a href="dashboard.php" class="btn btn-light mx-1">الرئيسية</a>
            <a href="logout.php" class="btn btn-outline-danger">تسجيل خروج</a>
        </div>
    </nav>
    <div class="container-fluid pb-5">
        <div class="stat-card p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="main-title"><i class="fa fa-book"></i> إدارة الكتب</h4>
                <a href="books.php" class="btn btn-success" id="btnAddBook"><i class="fa fa-plus"></i> <?=(isset($editBook)?'إضافة كتاب جديد':'كتاب جديد')?> </a>
            </div>
            <!-- فلاتر البحث المتقدمة -->
            <form class="row g-2 mb-3 filter-bar-admin" method="get" action="">
                <div class="col-md-2">
                    <input type="text" class="form-control" name="title" value="<?=$filter['title']?>" placeholder="اسم الكتاب" />
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="author" value="<?=$filter['author']?>" placeholder="المؤلف" />
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="section_id">
                        <option value="">كل الأقسام</option>
                        <?php foreach($sections as $s): ?><option value="<?=$s['id']?>" <?=($filter['section_id']==$s['id']?'selected':'')?>><?=$s['name']?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="category_id">
                        <option value="">كل الفئات</option>
                        <?php foreach($categories as $c): ?><option value="<?=$c['id']?>" <?=($filter['category_id']==$c['id']?'selected':'')?>><?=$c['name']?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <select class="form-select" name="is_new">
                        <option value="">كل الحالات</option>
                        <option value="1" <?=($filter['is_new']==='1'?'selected':'')?>>جديد</option>
                        <option value="0" <?=($filter['is_new']==='0'?'selected':'')?>>قديم</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="date" class="form-control" name="date_from" value="<?=$filter['date_from']?>" placeholder="من" />
                </div>
                <div class="col-md-1">
                    <input type="date" class="form-control" name="date_to" value="<?=$filter['date_to']?>" placeholder="إلى" />
                </div>
                <div class="col-md-1 text-end">
                    <button class="btn btn-info px-3 w-100" type="submit"><i class="fa fa-filter"></i></button>
                </div>
                <div class="col-md-1 text-end">
                    <a href="books.php" class="btn btn-light w-100"><i class="fa fa-times"></i></a>
                </div>
            </form>
            <?php if($msg): ?><div class="alert alert-<?=(isset($_GET['done'])||isset($_GET['updated'])||isset($_GET['deleted'])?'success':'danger')?>"> <?= $msg ?> </div><?php endif; ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle bg-white mt-3">
                    <thead class="table-success"><tr>
                        <th>#</th>
                        <th>العنوان</th>
                        <th>عدد النسخ</th>
                        <th>المؤلف</th>
                        <th>القسم</th>
                        <th>الفئة</th>
                        <th>تاريخ النشر</th>
                        <th>جديد</th>
                        <th>إجراءات</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach($books as $b): ?>
                        <tr>
                            <td><?= $b['id'] ?></td>
                            <td><?= htmlspecialchars($b['title']) ?></td>
                            <td><?= (int)$b['copies'] ?></td>
                            <td><?= htmlspecialchars($b['author']) ?></td>
                            <td><?= htmlspecialchars($b['section']) ?></td>
                            <td><?= htmlspecialchars($b['category']) ?></td>
                            <td><?= $b['publish_date'] ?></td>
                            <td><?= $b['is_new'] ? '<span class="badge bg-success">جديد</span>' : '-' ?></td>
                            <td>
                                <a href="books.php?edit=<?= $b['id'] ?>" class="btn btn-sm btn-info me-1"><i class="fa fa-edit"></i></a>
                                <a href="books.php?delete=<?= $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الكتاب؟')"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; if(!count($books)): ?>
                        <tr><td colspan="9" class="text-center">لا توجد كتب حالياً.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Modal for Add/Edit Book -->
        <div class="modal fade show" id="addBookModal" tabindex="-1" style="display: <?=isset($editBook)?'block':'none'?>; background:#0002;" aria-modal="true" role="dialog">
          <div class="modal-dialog modal-lg">
            <form class="modal-content" method="POST" enctype="multipart/form-data" id="addBookForm">
              <div class="modal-header">
                <h5 class="modal-title main-title"><i class="fa fa-<?=isset($editBook)?'edit':'plus'?>"></i> <?=isset($editBook)?'تعديل كتاب':'إضافة كتاب جديد'?></h5>
                <a href="books.php" class="btn-close"></a>
              </div>
              <div class="modal-body">
                <div class="row g-3">
                  <?php if(isset($editBook)): ?><input type="hidden" name="edit_id" value="<?= $editBook['id'] ?>" /><?php endif; ?>
                  <input type="hidden" name="old_cover_img" value="<?= $editBook['cover_img']??'' ?>" />
                  <input type="hidden" name="old_pdf" value="<?= $editBook['pdf_path']??'' ?>" />
                  <input type="hidden" name="old_audio" value="<?= $editBook['audio_path']??'' ?>" />
                  <div class="col-md-6">
                    <label>العنوان</label>
                    <input type="text" name="title" class="form-control" required value="<?= $editBook['title']??'' ?>" />
                  </div>
                  <div class="col-md-6">
                    <label>المؤلف</label>
                    <input type="text" name="author" class="form-control" required value="<?= $editBook['author']??'' ?>" />
                  </div>
                  <div class="col-md-4">
                    <label>القسم</label>
                    <select name="section_id" class="form-select" required>
                      <option value="">اختر القسم</option>
                      <?php foreach($sections as $s): ?><option value="<?= $s['id'] ?>" <?=isset($editBook)&&$editBook['section_id']==$s['id']?'selected':''?>><?= $s['name'] ?></option><?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label>الفئة</label>
                    <select name="category_id" class="form-select" required>
                      <option value="">اختر الفئة</option>
                      <?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?=isset($editBook)&&$editBook['category_id']==$c['id']?'selected':''?>><?= $c['name'] ?></option><?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label>تاريخ النشر</label>
                    <input type="date" name="publish_date" class="form-control" required value="<?= $editBook['publish_date']??'' ?>" />
                  </div>
                  <div class="col-md-12">
                    <label>وصف الكتاب (اختياري)</label>
                    <textarea name="desc" class="form-control" rows="2"><?= $editBook['description']??'' ?></textarea>
                  </div>
                  <div class="col-md-4">
                    <label>غلاف الكتاب (صورة) <?=isset($editBook)&&$editBook['cover_img']?'<span class=\'badge bg-info\'>حالي</span>':''?></label>
                    <input type="file" name="cover_img" accept="image/*" class="form-control" <?=isset($editBook)?'':'required'?> />
                  </div>
                  <div class="col-md-4">
                    <label>ملف PDF <?=isset($editBook)&&$editBook['pdf_path']?'<span class=\'badge bg-info\'>حالي</span>':''?></label>
                    <input type="file" name="pdf_file" accept="application/pdf" class="form-control" <?=isset($editBook)?'':'required'?> />
                  </div>
                  <div class="col-md-4">
                    <label>كتاب صوتي (mp3) (اختياري) <?=isset($editBook)&&$editBook['audio_path']?'<span class=\'badge bg-info\'>حالي</span>':''?></label>
                    <input type="file" name="audio_file" accept="audio/mp3,audio/mpeg" class="form-control" />
                  </div>
                  <div class="col-md-3">
                    <label>عدد النسخ (المخزون)</label>
                    <input type="number" name="copies" class="form-control" required min="1" value="<?= isset($editBook)?(int)$editBook['copies']:1 ?>" />
                  </div>
                  <div class="col-md-3">
                    <label>حالة الكتاب</label>
                    <select name="is_new" class="form-select">
                      <option value="0" <?=isset($editBook)&&!$editBook['is_new']?'selected':''?>>قديم</option>
                      <option value="1" <?=isset($editBook)&&$editBook['is_new']?'selected':''?>>جديد</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <a href="books.php" class="btn btn-light">إلغاء</a>
                <button type="submit" class="btn btn-success"><?= isset($editBook)?'حفظ التعديلات':'إضافة' ?></button>
              </div>
            </form>
          </div>
        </div>
        <!-- نهاية المودال -->
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // فتح نافذة إضافة فقط
      document.getElementById('btnAddBook').onclick = function(e){
        if(!<?=isset($editBook)?'false':'true'?>) return;
        var m = new bootstrap.Modal(document.getElementById('addBookModal'));
        m.show();
        e.preventDefault();
      };
    </script>
    <?php if(isset($editBook)): ?>
    <script>
      // فتح المودال تلقائيا
      window.onload=function(){ document.getElementById('addBookModal').style.display='block'; }
    </script>
    <?php endif; ?>
</body>
</html>
