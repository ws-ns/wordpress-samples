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


/**
 * WebPやSVGのアップロードを許可
 */
function ws_allow_file_type_upload( $mimes ) {
  $mimes['webp'] = 'image/webp';
  $mimes['webp'] = 'image/svg+xml';
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
