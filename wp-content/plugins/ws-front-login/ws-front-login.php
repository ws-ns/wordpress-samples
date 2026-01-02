<?php
/**
 * Front Login
 *
 * @package  ws
 * @author   White Software
 *
 * @wordpress-plugin
 * Plugin Name: Front Login
 * Plugin URI:  https://white-software.site/
 * Description: Support logging from the front page
 * Author:      White Software
 * Version:     0.0.1
 * Author URI:  https://white-software.site/
 *
 * Text Domain: ws-front-login
 * Domain Path: /languages/
 *
 */


/**
 * 定数
 */
require_once( __DIR__ . '/inc/defines.php' );


/**
 * クラス定義
 */
require_once( __DIR__ . '/inc/class-front-login.php' );
require_once( __DIR__ . '/inc/class-admin.php' );
require_once( __DIR__ . '/inc/class-shortcode.php' );


/**
 * 翻訳ファイルを読み込む
 */
load_plugin_textdomain (
  'ws-front-login',
  false,
  plugin_basename( dirname( __FILE__ ) ) . '/languages'
);


/**
 * インスタンス作成
 */
global $_g_ws_front_login_admin;
$_g_ws_front_login_admin = new WsFrontLoginAdmin( __FILE__ );
global $_g_ws_clinic_sc;
$_g_ws_front_login_sc = new WsFrontLoginShortcode( __FILE__ );

?>