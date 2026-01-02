// あ

jQuery(function($) {

    var text = $('code[class="sc_preview_text"]').html();
    if ( text != undefined ) {
        $('code[class="sc_preview_text"]').html( text.replace( '[/sc]', '' ) );
    }


 
    ////////// 管理画面でメディアアップローダーを呼び出す
    var ws_custom_uploader;
 
    $('input:button[name="media"]').click(function(e) {
 
        let id = $(this).attr('data-id');

        e.preventDefault();
 
        if ( ws_custom_uploader ) {
 
            ws_custom_uploader.open();
            return;
 
        }
 
        // wp_enqueue_media(); を呼び出しておく必要がある
        ws_custom_uploader = wp.media({
 
            title: '画像を選択',
 
            /* ライブラリの一覧は画像のみにする */
            library: {
                type: 'image'
            },
 
            button: {
                text: '画像を選択'
            },
 
            /* 選択できる画像は 1 つだけにする */
            multiple: false
 
        });
 
        ws_custom_uploader.on("select", function() {
 
            var images = ws_custom_uploader.state().get("selection");
 
            /* file の中に選択された画像の各種情報が入っている */
            images.each(function(file){
 
                /* テキストフォームと表示されたサムネイル画像があればクリア */
                $('input:text[name="ws-media-'+id+'"]').val('');
                $('#media-'+id+'-img').empty();
 
                /* テキストフォームに画像の ID を表示 */
                $('input:text[name="ws-media-'+id+'"]').val(file.id);
 
                /* プレビュー用に選択されたサムネイル画像を表示 */
                $('#media-'+id+'-img').append('<img src="'+file.attributes.sizes.thumbnail.url+'" />');
 
            });
        });
 
        ws_custom_uploader.open();
 
    });
 
    /* クリアボタンを押した時の処理 */
    $('input:button[name="media-clear"]').click(function() {
        let id = $(this).attr('data-id');
        $('input:text[name="ws-media-'+id+'"]').val('');
        $('#media-'+id+'-img').empty();
    });
 
});
