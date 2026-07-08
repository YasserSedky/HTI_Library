$(document).ready(function(){
    const messages = [];

    function addMessage(role, text, isTyping=false){
        const safeText = $('<div/>').text(text || '').html().replace(/\n/g,'<br/>');
        const $msg = $('<div/>').addClass('ai-msg').addClass(role).toggleClass('ai-typing', isTyping);
        const $role = $('<div/>').addClass('role').text(role === 'user' ? 'أنت' : 'المساعد');
        const $body = $('<div/>').addClass('body').html(safeText || (isTyping ? 'يكتب...' : ''));
        $msg.append($role).append($body);
        $('#aiMessages').append($msg);
        $('#aiMessages').scrollTop($('#aiMessages')[0].scrollHeight);
        return $msg;
    }

    function send(text){
        if(!text || !text.trim()) return;
        messages.push({ role: 'user', content: text });
        addMessage('user', text);
        const typingEl = addMessage('assistant', 'يكتب...', true);

        $.ajax({
            url: 'ai_assistant.php',
            method: 'POST',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({ provider: 'openai', mode: 'qa', question: text, context: messages.filter(m=>m.role==='assistant').map(m=>m.content).slice(-3).join('\n\n') }),
            success: function(res){
                let answer = 'حدث خطأ في المعالجة.';
                if(res && res.ok) answer = res.answer || 'لا يوجد رد.';
                messages.push({ role: 'assistant', content: answer });
                typingEl.removeClass('ai-typing');
                typingEl.find('.body').html($('<div/>').text(answer).html().replace(/\n/g,'<br/>'));
            },
            error: function(xhr){
                let msg = 'تعذر الاتصال بالخادم.';
                try {
                    const r = xhr.responseJSON || JSON.parse(xhr.responseText);
                    if(r){
                        if(r.error) msg += ' ('+ r.error +')';
                        if(r.status) msg += ' [HTTP '+ r.status +']';
                        if(r.raw){
                            try {
                                const rb = (typeof r.raw === 'string') ? JSON.parse(r.raw) : r.raw;
                                if(rb && rb.error && rb.error.message) msg += ': ' + rb.error.message;
                            } catch(_) {}
                        }
                        if(r.curl) msg += ' {'+ r.curl +'}';
                    }
                } catch(e){}
                typingEl.removeClass('ai-typing');
                typingEl.find('.body').text(msg);
            }
        });
    }

    // إرسال من الزر
    $('#aiSend').on('click', function(){
        const val = $('#aiInput').val();
        $('#aiInput').val('');
        send(val);
    });
    // إدخال عبر Enter مع دعم Shift+Enter لسطر جديد
    $('#aiInput').on('keydown', function(e){
        if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); $('#aiSend').click(); }
    });

    // تهيئة رسالة ترحيبية عند فتح المودال لأول مرة
    $('#aiAssistantModal').on('shown.bs.modal', function(){
        if(!$('#aiMessages').children().length){
            addMessage('assistant', 'مرحبًا! أنا مساعد مكتبة HTI. اسألني أو اطلب تلخيص نص.');
            $('#aiInput').focus();
        }
    });
});


