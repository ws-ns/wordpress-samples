<?php //子テーマ用関数
if ( !defined( 'ABSPATH' ) ) exit;

//子テーマ用のビジュアルエディタースタイルを適用
add_editor_style();

//以下に子テーマ用の関数を書く


/**
 * Remove Cocoon Styles
 */
add_action( 'wp_enqueue_scripts', function() {
  // wp_dequeue_style( 'cocoon-style' );
  // wp_dequeue_style( 'cocoon-keyframes' );
  // wp_dequeue_style( 'font-awesome-style' );
  // wp_dequeue_style( 'font-awesome5-style-update-style' );
  // wp_dequeue_style( 'icomoon-style' );
  // wp_dequeue_style( 'code-highlight-style' );
  // wp_dequeue_style( 'baguettebox-style' );
  // wp_dequeue_style( 'cocoon-skin-style' );
  // wp_dequeue_style( 'cocoon-child-style' );
  // wp_dequeue_style( 'cocoon-child-keyframes-style' );
}, 999 );


/**
 * For Security
 */
remove_action('wp_head', 'wp_generator');// WordPressのバージョン
remove_action('wp_head', 'wp_shortlink_wp_head');// 短縮URLのlink
remove_action('wp_head', 'wlwmanifest_link');// ブログエディターのマニフェストファイル
remove_action('wp_head', 'rsd_link');// 外部から編集するためのAPI
remove_action('wp_head', 'feed_links_extra', 3);// フィードへのリンク
remove_action('wp_head', 'print_emoji_detection_script', 7);// 絵文字に関するJavaScript
remove_action('wp_head', 'rel_canonical');// カノニカル
remove_action('wp_print_styles', 'print_emoji_styles');// 絵文字に関するCSS
remove_action('admin_print_scripts', 'print_emoji_detection_script');// 絵文字に関するJavaScript
remove_action('admin_print_styles', 'print_emoji_styles');// 絵文字に関するCSS
add_filter( 'run_wptexturize', '__return_false' ); // 謎の空白が入るのを防止する


// ?author=n によるユーザー情報表示を禁止
function ws_disable_author_archive() {
    if ( is_admin() ) return;

    if ( isset($_GET['author']) || preg_match('#/author/.+#', $_SERVER['REQUEST_URI']) ) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        get_template_part(404); // 404.phpテンプレートを読み込み
        exit();
    }
}
add_action('init', 'ws_disable_author_archive');
add_filter('author_rewrite_rules', '__return_empty_array');


// Contact Form 以外のREST APIを停止
function ws_deny_rest_api_except_permitted( $result, $wp_rest_server, $request ){
  $permitted_routes = [ 'oembed', 'contact-form-7', 'akismet'];

  $route = $request->get_route();

  foreach ( $permitted_routes as $r ) {
    if ( strpos( $route, "/$r/" ) === 0 ) return $result;
  }

  // Gutenberg（ユーザーが投稿やページの編集が可能な場合）
  if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' )) {
    return $result;
  }

  return new WP_Error( 'rest_disabled', __( 'The REST API on this site has been disabled.' ), array( 'status' => rest_authorization_required_code() ) );
}
add_filter( 'rest_pre_dispatch', 'ws_deny_rest_api_except_permitted', 10, 3 );



/**
 * WebP, SVG, icoのアップロードを許可
 */
function ws_allow_file_type_upload( $mimes ) {
  $mimes['webp'] = 'image/webp';
  $mimes['svg']  = 'image/svg+xml';
  $mimes['ico']  = 'image/x-icon';
  return $mimes;
}
add_filter( 'upload_mimes', 'ws_allow_file_type_upload' );


/**
 * Contact Form 7 で自動挿入されるPタグ、brタグを削除
 */
add_filter( 'wpcf7_autop_or_not', '__return_false' );


/**
 * 投稿タイプ"post" のアーカイブを有効化し、スラッグ（URL）を設定
 */
// function ws_post_has_archive( $args, $post_type ) {
//   if ( 'post' == $post_type ) {
//     $args['rewrite'] = true;
//     $args['has_archive'] = 'archive'; // 任意のスラッグ（URL）
//   }
//   return $args;
// }
// add_filter( 'register_post_type_args', 'ws_post_has_archive', 10, 2 );


/**
 * Google Adsense を管理画面で無効化する
 */
add_action('admin_init', function() {
    // 管理画面でのAdSense関連スクリプトを完全に除去
    global $wp_scripts;
    if (isset($wp_scripts->registered)) {
        foreach ($wp_scripts->registered as $handle => $script) {
            if (strpos($script->src, 'adsbygoogle') !== false || 
                strpos($script->src, 'pagead') !== false) {
                wp_deregister_script($handle);
            }
        }
    }
});
