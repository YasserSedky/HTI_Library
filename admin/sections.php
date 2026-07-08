<?php
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: login.php');
$db=new PDO('mysql:host=localhost;dbname=hti_library;charset=utf8mb4','root','');
$msg='';
$search = $_GET['search'] ?? '';
//------------- حذف قسم أو فئة -------------
if(isset($_GET['del_section'])){
  $sid=intval($_GET['del_section']);
  $db->prepare('DELETE FROM sections WHERE id=?')->execute([$sid]);
  $msg='تم حذف القسم بنجاح!';
  header('Location: sections.php?deleted=1'); exit;
}
if(isset($_GET['del_category'])){
  $cid=intval($_GET['del_category']);
  $db->prepare('DELETE FROM categories WHERE id=?')->execute([$cid]);
  $msg='تم حذف الفئة بنجاح!';
  header('Location: sections.php?deleted=1'); exit;
}
//------------- إضافة/تعديل قسم -------------
if(isset($_POST['action']) && $_POST['action']==='add_section'){
  $name=trim($_POST['sname']);
  if(isset($_POST['sid']) && $_POST['sid']){
    $db->prepare('UPDATE sections SET name=? WHERE id=?')->execute([$name,intval($_POST['sid'])]);
    $msg='تم التعديل بنجاح!';
  }else{
    $db->prepare('INSERT INTO sections (name) VALUES (?)')->execute([$name]);
    $msg='تمت الاضافة بنجاح!';
  }
  header('Location: sections.php?saved=1'); exit;
}
//------------- إضافة/تعديل فئة -------------
if(isset($_POST['action']) && $_POST['action']==='add_category'){
  $name=trim($_POST['cname']);
  $sid=intval($_POST['csid']);
  if(isset($_POST['cid']) && $_POST['cid']){
    $db->prepare('UPDATE categories SET name=?,section_id=? WHERE id=?')->execute([$name,$sid,intval($_POST['cid'])]);
    $msg='تم التعديل بنجاح!';
  }else{
    $db->prepare('INSERT INTO categories(name,section_id) VALUES (?,?)')->execute([$name,$sid]);
    $msg='تمت الاضافة بنجاح!';
  }
  header('Location: sections.php?saved=1'); exit;
}
$sections = $db->query('SELECT * FROM sections ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$cats = [];
foreach($db->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC) as $c){ $cats[$c['section_id']][]=$c; }
if(isset($_GET['saved'])) $msg='تم الحفظ بنجاح!';
if(isset($_GET['deleted'])) $msg='تم الحذف بنجاح!';
// فلترة النتائج في العرض (php)
if($search){
  $sections = array_filter($sections, function($s) use($search, $cats){
    // تحقّق من وجود البحث في اسم القسم أو أحد الفئات أسفل القسم
    $foundSection = stripos($s['name'], $search) !== false;
    $foundCat = false;
    if(isset($cats[$s['id']])){
      foreach($cats[$s['id']] as $c){
        if(stripos($c['name'], $search) !== false) $foundCat = true;
      }
    }
    return $foundSection || $foundCat;
  });
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الأقسام والفئات - لوحة تحكم مكتبة HTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Cairo',sans-serif;background:#f7faf7;}
        .stat-card{border-radius:13px;background:#fff;box-shadow:0 1px 8px #34c75915;}
        .main-title{color:#34c759;font-weight:bold;}
        .c-section{background:#eafefa;border-radius:8px;padding:12px;}
        .c-category{margin-right:35px;}
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm mb-3">
  <div class="container">
    <span class="navbar-brand mb-0 h1"><i class="fa fa-layer-group"></i> إدارة الأقسام والفئات</span>
    <a href="dashboard.php" class="btn btn-light mx-1">الرئيسية</a>
    <a href="logout.php" class="btn btn-outline-danger">تسجيل خروج</a>
  </div>
</nav>
<div class="container-fluid pb-5">
  <div class="stat-card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="main-title"><i class="fa fa-layer-group"></i> الأقسام والفئات</h4>
      <form method="get" class="d-flex" style="gap:7px">
        <input type="text" class="form-control" style="width:170px" name="search" value="<?=htmlspecialchars($search)?>" placeholder="بحث قسم/فئة" />
        <button class="btn btn-info" type="submit"><i class="fa fa-filter"></i></button>
        <?php if ($search): ?><a href="sections.php" class="btn btn-light"><i class="fa fa-times"></i></a><?php endif; ?>
      </form>
      <div>
        <a href="#" class="btn btn-success me-2" onclick="showAddSection()"><i class="fa fa-plus"></i> قسم جديد</a>
        <a href="#" class="btn btn-info" onclick="showAddCategory()"><i class="fa fa-plus"></i> فئة جديدة</a>
      </div>
    </div>
    <?php if($msg): ?><div class="alert alert-success"> <?= $msg ?> </div><?php endif; ?>
    <?php foreach($sections as $s): ?>
      <div class="c-section mb-2"><b><i class="fa fa-layer-group"></i> <?= htmlspecialchars($s['name']) ?> </b>
        <a href="#" onclick="editSection('<?= $s['id'] ?>','<?=htmlspecialchars($s['name'])?>')" class="btn btn-sm btn-info mx-1"><i class="fa fa-edit"></i></a>
        <a href="sections.php?del_section=<?=$s['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('تأكيد حذف القسم والفئات المرتبطة؟')"><i class="fa fa-trash"></i></a>
        <ul class="mt-2">
        <?php if(isset($cats[$s['id']])) foreach($cats[$s['id']] as $c): ?>
          <li class="c-category"> <i class="fa fa-cube text-success"></i> <?=htmlspecialchars($c['name'])?> 
            <a href="#" onclick="editCategory('<?=$c['id']?>','<?=htmlspecialchars($c['name'])?>','<?=$s['id']?>')" class="btn btn-sm btn-info mx-1"><i class="fa fa-edit"></i></a>
            <a href="sections.php?del_category=<?=$c['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('تأكيد حذف هذه الفئة؟')"><i class="fa fa-trash"></i></a>
          </li>
        <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>
  <!-- modals add/edit section & category -->
  <div class="modal fade" id="sectionModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><div class="modal-header"><h5 class="modal-title" id="sectionModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
    <input type="hidden" name="sid" id="sid" />
    <input type="text" name="sname" class="form-control" id="sname" placeholder="اسم القسم" required />
    <input type="hidden" name="action" value="add_section" />
  </div><div class="modal-footer"><button type="submit" class="btn btn-success">حفظ</button><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button></div></form></div></div>
  <div class="modal fade" id="catModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><div class="modal-header"><h5 class="modal-title" id="catModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
    <input type="hidden" name="cid" id="cid" />
    <input type="text" name="cname" class="form-control mb-2" id="cname" placeholder="اسم الفئة" required />
    <select name="csid" class="form-select" id="csid" required><option value="">اختر القسم التابع</option><?php foreach($sections as $s): ?><option value="<?= $s['id'] ?>"><?= $s['name'] ?></option><?php endforeach; ?></select>
    <input type="hidden" name="action" value="add_category" />
  </div><div class="modal-footer"><button type="submit" class="btn btn-success">حفظ</button><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button></div></form></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showAddSection(){ document.getElementById('sectionModalTitle').innerText='إضافة قسم جديد'; document.getElementById('sid').value=''; document.getElementById('sname').value=''; new bootstrap.Modal(document.getElementById('sectionModal')).show(); }
function editSection(id, name){ document.getElementById('sectionModalTitle').innerText='تعديل قسم'; document.getElementById('sid').value=id; document.getElementById('sname').value=name; new bootstrap.Modal(document.getElementById('sectionModal')).show(); }
function showAddCategory(){ document.getElementById('catModalTitle').innerText='إضافة فئة جديدة'; document.getElementById('cid').value=''; document.getElementById('cname').value=''; document.getElementById('csid').value=''; new bootstrap.Modal(document.getElementById('catModal')).show(); }
function editCategory(id, name, sid){ document.getElementById('catModalTitle').innerText='تعديل فئة'; document.getElementById('cid').value=id; document.getElementById('cname').value=name; document.getElementById('csid').value=sid; new bootstrap.Modal(document.getElementById('catModal')).show(); }
</script>
</body>
</html>
