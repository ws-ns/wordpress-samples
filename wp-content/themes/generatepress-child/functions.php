<?php

/**
 * Child Theme Settings
 */
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
  // wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
  // wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style') );
}


/**
 * Remove Generatepress Styles
 */
add_action( 'wp_enqueue_scripts', function() {
  wp_dequeue_style( 'generate-style' );
  wp_dequeue_style( 'generate-child' );
}, 999 );


/**
 * ACFで絵文字を保存できない問題対策
 */ 
// 保存前にエンコード
function my_acf_update_value( $value ) {
	return stripslashes(esc_attr(mb_encode_numericentity($value, [0x10000, 0x10FFFF, 0, 0xFFFFFF], 'UTF-8')));
}

// 読み込み後にデコード
function my_acf_load_value( $value ) {
	return esc_attr(mb_decode_numericentity($value, [0x10000, 0x10FFFF, 0, 0xFFFFFF], 'UTF-8'));
}

// acf/update_value、acf/load_valueともに
// 修飾子が使えるので、必要なフィールドタイプを指定する。
add_filter('acf/update_value/type=text', 'my_acf_update_value', 10, 1);
add_filter('acf/load_value/type=text', 'my_acf_load_value', 10, 1);


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

?>