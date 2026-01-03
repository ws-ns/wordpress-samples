// あ --- emacsの文字コード誤判定防止用


////////// 初期実行処理
jQuery(function($) {

    ////// 管理画面の送信ボタン
    $('form.ws-admin-form').submit(function() {
        $('input[type="submit"]').css({ opacity: 0.5}).prop('disabled', true);
        return true;
    });

});



////////// VideoIDをコピー
function copyID(text) {
    if ( text == undefined || text == '' ) return;
    // 一応クリップボードにコピー
    if ( navigator.clipboard ) {
        navigator.clipboard.writeText(text);
    }
    let elem = document.getElementById('channel-id-list');
    if ( elem ) {
        if ( elem.value.indexOf(text) < 0 ) {
            if ( elem.value != '' ) {
                elem.value += '\n' + text;
            }
            else {
                elem.value = text;
            }
        }
    }
    return;
}
