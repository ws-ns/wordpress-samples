<?php
/**
 * Front Login
 *
 * @package  ws
 * @author   White Software
 *
 * @wordpress-plugin
 *
 */


/*
 *  FrontLoginクラス
 *
 * @class WsFrontLogin
 */
class WsFrontLogin {

  public $ws_plugin_name = '';
  public $ws_plugin_desc = '';
  public $ws_plugin_ver  = WS_FRONT_LOGIN_PLUGIN_VERSION;
  public $ws_plugin_path = '';
  public $ws_plugin_url  = '';


  /**
   * コンストラクタ
   *
   * @param {string} $base プラグインファイル絶対パス
   */
  public function __construct( $base = __FILE__ ) {
    global $wpdb;
    
    $this->ws_plugin_name = __('Front Login', 'ws-front-login');
    $this->ws_plugin_desc = __( 'Support logging from the front page', 'ws-front-login' );
    $this->ws_plugin_path = plugin_dir_path( $base );
    $this->ws_plugin_url  = plugin_dir_url( $base );

    return;
  }


  ////////////////////////////////////////
  //    デストラクタ
  public function __destruct() {
    return;
  }


} // class WsFrontLogin


?>