// あ --- emacsの文字コード誤判定防止用


////////// iOSでリンクを2回タップしないと反応しない対策
window.ontouchstart = function() {};


////////// 初期実行処理
jQuery(function($) {

    ////// アコーディオン機能の実装
    $('div[class="accordion-click"], div[class="accordion-click read-more"]').click(function() {
        if ( $(this).next().is(':visible') ) {
            // $(this).children('.accordion-arrow').removeClass('deg180');
        }
        else {
            // $(this).children('.accordion-arrow').addClass('deg180');
        }
        $(this).fadeOut(200);
        $(this).next().slideToggle(500);
    });
    $('span[class="youtube-comment-reply"]').click(function() {
        if ( $(this).parent().next().is(':visible') ) {
            // $(this).children('.accordion-arrow').removeClass('deg180');
        }
        else {
            // $(this).children('.accordion-arrow').addClass('deg180');
        }
        // $(this).slideToggle();
        $(this).parent().next().slideToggle(500);
    });

});
