// あ

jQuery(function($) {
    if ( location.href.indexOf('#') >= 0 ) {
//        alert('ある');
    }
});



//// 確認
function checkSubmit(text) {
    return confirm(text);
}
function checkSendmail() {
    let id;
    let error = new Array();

    id = '#wt-name';
    let val = jQuery(id).val();
    if ( !val ) {
        error.push('お名前を入力してください');
        jQuery(id).addClass('error');
    }
    else if ( localStorage ) {
        localStorage.setItem('wtcustom-form-name', val);
    }

    id = '#wt-mail';
    val = jQuery(id).val();
    let reg = /^[A-Za-z0-9]{1}[A-Za-z0-9_.-]*@{1}[A-Za-z0-9_.-]{1,}.[A-Za-z0-9]{1,}$/;
    if ( !val ) {
        error.push('メールアドレスを入力してください');
        jQuery(id).addClass('error');
    }
    else if ( reg.test(val) ) {
        if ( localStorage ) {
            localStorage.setItem('wtcustom-form-email', val);
        }
    }
    else {
        error.push('メールアドレスが誤っています');
        jQuery(id).addClass('error');
    }

    id = '#wt-subject';
    val = jQuery(id).val();
    if ( !val ) {
        error.push('ご用件を入力してください');
        jQuery(id).addClass('error');
    }

    id = '#wt-body';
    val = jQuery(id).val();
    if ( !val ) {
        error.push('内容を入力してください');
        jQuery(id).addClass('error');
    }

    if ( error.length > 0 ) {
        alert( error.join("\n") );
        return false;
    }

    if ( confirm('メールを送信します') ) {
        return true;
    }
    else {
        return false;
    }
}


////////// 年間予定表
function nowScheduleMonth() {
    jQuery('[name^="wtcustom-month-"]').hide();

    let elem = document.getElementById('now-month');
    if ( elem ) {
        let nowmonth = elem.value;
        jQuery('[name="wtcustom-month-'+nowmonth+'"]').show();

        let now = new Date();
        document.getElementById('show-year').value  = now.getFullYear();
        document.getElementById('show-month').value = now.getMonth()+1;

        jQuery('[class="wtcustom-nowmonth"]').html(now.getFullYear()+'年'+(now.getMonth()+1)+'月');
    }

    return;
}
function prevScheduleMonth() {
    jQuery('[name^="wtcustom-month-"]').hide();

    let elem = document.getElementById('show-year');
    if ( elem ) {
        let showyear  = elem.value;
        elem = document.getElementById('show-month');
        if ( elem ) {
            let showmonth = elem.value;
            showmonth--;
            if ( showmonth < 1 ) {
                showmonth = 12;
                showyear--;
            }
            jQuery('[name="wtcustom-month-'+showyear+('00'+showmonth).slice(-2)+'"]').show();
            document.getElementById('show-year').value = showyear;
            document.getElementById('show-month').value = showmonth;

            jQuery('[class="wtcustom-nowmonth"]').html(showyear+'年'+showmonth+'月');
        }
    }

    return;
}
function nextScheduleMonth() {
    jQuery('[name^="wtcustom-month-"]').hide();

    let elem = document.getElementById('show-year');
    if ( elem ) {
        let showyear  = elem.value;
        elem = document.getElementById('show-month');
        if ( elem ) {
            let showmonth = elem.value;
            showmonth++;
            if ( showmonth > 12 ) {
                showmonth = 1;
                showyear++;
            }
            jQuery('[name="wtcustom-month-'+showyear+('00'+showmonth).slice(-2)+'"]').show();
            document.getElementById('show-year').value = showyear;
            document.getElementById('show-month').value = showmonth;

            jQuery('[class="wtcustom-nowmonth"]').html(showyear+'年'+showmonth+'月');
        }
    }

    return;
}


////////// 初期実行
jQuery(function($) {
    // 年間予定表
    nowScheduleMonth();

    // フォーム
    if ( localStorage ) {
        let val = localStorage.getItem('wtcustom-form-name');
        jQuery('#wt-name').val(val);
        val = localStorage.getItem('wtcustom-form-email');
        jQuery('#wt-mail').val(val);
    }

    // テーマ名を削除する
    let elem = document.getElementById('copyright');
    if ( elem ) {
        let div = document.createElement('div');
        div.setAttribute( 'id', 'footer-shadow' );
        elem.appendChild(div);
    }

    // 日付更新のチェック
    var _g_pre_time = new Date();
    setInterval( function() {
        var _now_time = new Date();
        if ( _g_pre_time.getDate() != _now_time.getDate() ) {
            location.reload();
        }
        _g_pre_time = _now_time;
    }, 1000*60 );
});
