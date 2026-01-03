<?php
/*
 * Notify Post Plugin   記事を公開すると通知を送信するプラグイン
 * 
 * @package  ws
 * @author   white Software
 *
 * @wordpress-plugin
 * Plugin Name: Notify Post Plugin
 * Plugin URI: https://white-software.site/plugins/ws-notify-post.php
 * Description: Send notification when an article is posted
 * Author: White Software
 * Version: 0.0.2
 * Author URI: https://white-software.site/
 *
 * Text Domain: ws-notify-post
 * Domain Path: /languages/
 *
 */


////////// 翻訳ファイルを読み込む
load_plugin_textdomain (
  'ws-notify-post',
  false,
  plugin_basename( dirname( __FILE__ ) ) . '/languages'
);


include_once( __DIR__ . '/inc/class-main.php' );


////////// インスタンスの作成
$_g_ws_notify_post = new WsNotifyPost();



// ws-notify-post-ja.po
// 
// #. Description of the plugin
// msgid "Send notification when an article is posted"
// msgstr "記事を公開すると通知を送信"
// 
// #. Plugin Name of the plugin
// msgid "Notify Post Plugin"
// msgstr "記事公開通知プラグイン"
// 

?>