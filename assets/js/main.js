$(document).ready(function(){
    // تعبئة قوائم الفلاتر بالقيم الحقيقية
    $('#sectionFilter').html('<option>كل الأقسام</option>');
    demoSections.forEach(sec=> $('#sectionFilter').append(`<option value="${sec.name}">${sec.name}</option>`));
    $('#categoryFilter').html('<option>كل الفئات</option>');
    demoCategories.forEach(cat=> $('#categoryFilter').append(`<option value="${cat.name}">${cat.name}</option>`));

    // عرض الكتب مجمعة حسب القسم
    function showBooksBySection(booksArr, sectionsArr) {
        $('#booksGrid').html('');
        if (!booksArr.length) {
            $('#booksGrid').html('<div class="alert alert-warning text-center">لا توجد كتب</div>');
            if (window.AOS) AOS.refresh();
            return;
        }
        // ترتيب وعرض الأقسام
        sectionsArr.forEach(section => {
            let sectionBooks = booksArr.filter(book => book.section === section.name);
            if (sectionBooks.length) {
                $('#booksGrid').append(`
                <div class="books-section mb-5" data-aos="fade-up" data-aos-duration="700">
                    <h4 class="fw-bold mb-3 section-title"><i class="fa fa-cubes text-success me-1"></i> ${section.name}</h4>
                    <div class="row books-section-grid"></div>
                </div>
                `);
                let container = $('#booksGrid .books-section:last .books-section-grid');
                sectionBooks.forEach((book, idx) => {
                    const delay = (idx % 6) * 80;
                    container.append(`
                        <div class="col-md-4 col-lg-3 mb-4" data-aos="zoom-in" data-aos-duration="500" data-aos-delay="${delay}">
                            <div class="book-card card h-100">
                                <img src="${book.img}" class="card-img-top" style="height:190px; object-fit:cover; border-radius:14px 14px 0 0;" alt="${book.title}">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold">
                                        ${book.title}
                                        ${book.is_new?'<span class="badge badge-new ms-1">جديد</span>':''}
                                    </h5>
                                    <div class="mb-2 fs-6 text-secondary"><i class="fa fa-user graduate me-1"></i>${book.author} · <i class="fa fa-cubes me-1"></i> ${book.section}</div>
                                    <div class="mb-3"><i class="fa fa-calendar-day me-1"></i> ${book.publish_date??''}</div>
                                    <div class="actions-bar">
                                        ${book.pdf_path?`<a href="${book.pdf_path}" class="btn btn-outline-success btn-sm" target="_blank"><i class="fa fa-book-open"></i> تصفح</a>`:''}
                                        ${book.pdf_path?`<a href="${book.pdf_path}" download class="btn btn-success btn-sm"><i class="fa fa-download"></i> تنزيل</a>`:''}
                                        ${book.audio_path?`<a href="${book.audio_path}" target="_blank" class="btn btn-warning btn-sm"><i class="fa fa-headphones"></i> سماع</a>`:''}
                                    </div>
                                    ${book.audio_path?`<a href="${book.audio_path}" download class="btn btn-info btn-audio-full"><i class="fa fa-cloud-download-alt"></i> تنزيل صوتي</a>`:''}
                                </div>
                            </div>
                        </div>
                    `);
                });
            }
        });
        if (window.AOS) AOS.refresh();
    }

    // عرض الكتب افتراضياً حسب القسم
    showBooksBySection(books, demoSections);

    // البحث والفلاتر
    window.applyFilters = function() {
        let term = $('#searchInput').val().toLowerCase();
        let sec = $('#sectionFilter').val();
        let cat = $('#categoryFilter').val();
        let filtered = books.filter(x=>
            (sec==='كل الأقسام'||x.section===sec)&&
            (cat==='كل الفئات'||x.category===cat)&&
            (term===''||x.title.toLowerCase().includes(term)||x.author.toLowerCase().includes(term))
        );
        showBooksBySection(filtered, demoSections);
    };

    // تهيئة AOS
    if (window.AOS) {
        AOS.init({
            duration: 650,
            easing: 'ease-out-cubic',
            once: true,
            offset: 24,
        });
        setTimeout(() => AOS.refresh(), 350);
    }
    // إشعار Popup خاص بالجرس
    $('#notifIcon').click(function(e){ e.preventDefault(); $('#notificationsBox').toggle(); });
    $(document).mouseup(function(e){
        if(!$(e.target).closest('#notificationsBox,.nav-link').length)
            $('#notificationsBox').hide();
    });

    // Dark Mode Toggle
    function applyThemeIcon() {
        if ($('body').hasClass('dark')) {
            $('#themeIcon').removeClass('fa-moon').addClass('fa-sun');
        } else {
            $('#themeIcon').removeClass('fa-sun').addClass('fa-moon');
        }
    }
    function setTheme(mode){
        if(mode==='dark') {
            $('body').addClass('dark');
            localStorage.setItem('theme','dark');
        } else {
            $('body').removeClass('dark');
            localStorage.setItem('theme','light');
        }
        applyThemeIcon();
    }
    const savedTheme = localStorage.getItem('theme');
    setTheme(savedTheme==='dark'?'dark':'light');
    $('#themeToggle').on('click', function(){
        const isDark = $('body').hasClass('dark');
        setTheme(isDark ? 'light' : 'dark');
    });
});
