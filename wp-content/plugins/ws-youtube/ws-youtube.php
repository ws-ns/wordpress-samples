<?php
/*
  Plugin Name: Youtube自動投稿
  Plugin URI: https://wtpage.info/
  Description: Youtubeの最新動画をAPIで取得して自動投稿
  Author: white-software
  Version: 0.0.7
  Author URI: https://wtpage.info/
*/




////////// インスタンスの作成
$_ws_youtube  = new WsYoutube;



/**
 *      メインのクラス
 */
class WsYoutube {

  public $ws_plugin_name        = '自動投稿Youtube';
  public $ws_plugin_version     = '0.0.7';
  public $ws_youtube_db_version = '0.0.7';
  public $ws_this_plugin = '';

  private $db_table_youtube = '';
  private $post_type_orig = '';
  private $ws_user_agent = 'ws user agent';

  private $google_api_key = '';


  /**
   *    コンストラクタ
   */
  public function __construct(){
    global $wpdb;

    // ログインのクッキー有効期限
    // add_filter( 'auth_cookie_expiration', array( $this, 'ws_auth_cookie_expiration' ) );

    // 管理メニューを設定
    add_action( 'admin_menu', array( $this, 'ws_admin_menu' ) );

    // プラグイン一覧に設定リンクを追加
    add_filter( 'plugin_action_links', array( $this, 'ws_plugin_action_links' ), 10, 2);

    // 管理バーメニューを設定
    // add_action( 'admin_bar_menu', array( $this, 'ws_admin_bar_menu' ), 9999 );

    // cssやJavascriptファイルを読み込ませる
    add_action('wp_enqueue_scripts', array( $this, 'ws_load_files' ) );
    add_action('admin_enqueue_scripts', array( $this, 'ws_load_admin_files' ) );

    // プラグイン有効化時にDBテーブルを作成
    if ( function_exists('register_activation_hook') ) {
      // 接頭辞（$wpdb->prefix）を付けてテーブル名を設定
      $this->db_table_youtube = $wpdb->prefix . 'ws_youtube';
      register_activation_hook( __FILE__, array($this, 'ws_plugin_start') );
    }

    // プラグイン無効化時にDBテーブルを削除
    if ( function_exists('register_deactivation_hook') ) {
      register_deactivation_hook( __FILE__, array($this, 'ws_plugin_finish') );
    }

    // cronのパターンを追加
    add_filter( 'cron_schedules', function ( $schedules ) {
      $schedules['every02hours'] = array( 'interval' =>  2*60, 'display' => __( 'every  2 hours' ) );
      $schedules['every03hours'] = array( 'interval' =>  3*60, 'display' => __( 'every  3 hours' ) );
      $schedules['every04hours'] = array( 'interval' =>  4*60, 'display' => __( 'every  4 hours' ) );
      $schedules['every06hours'] = array( 'interval' =>  6*60, 'display' => __( 'every  6 hours' ) );
      $schedules['every08hours'] = array( 'interval' =>  8*60, 'display' => __( 'every  8 hours' ) );
      return $schedules;
    });

    // cronを実行
    add_action('ws_youtube_run_cron', array( $this, 'ws_run_cron' ) );

    // 広告表示用ショートコード
    add_shortcode( 'ws_youtube_ads', array( $this, 'ws_shortcode_youtube_ads' ) );

    return;
  }


  /**
   * ログインクッキー期限
   */
  function ws_auth_cookie_expiration() {
    return (60*60*24*60); // 60日
  }


  /**
   * CSSファイルやJavascriptファイルを読み込ませる
   */
  function ws_load_files( $hook ) {

    $plugin_path = plugin_dir_path( __FILE__ );
    $plugin_url  = plugin_dir_url( __FILE__ );

    // スタイルシートの読み込み
    $css = $plugin_path.'css/common.css';
    if ( file_exists($css) ) {
      wp_enqueue_style( 'ws-youtube-css',
                        $plugin_url.'css/common.css',
                        array(),
                        filemtime( $css )
                       );
    }
 
    // JavaScript の読み込み
    $js = $plugin_path.'/js/common.js';
    if ( file_exists($js) ) {
      wp_enqueue_script( 'ws-youtube-js',
                         $plugin_url.'js/common.js',
                         array('jquery'),
                         filemtime( $js )
                       );
    }

    return;
  }


  /**
   * CSSファイルやJavascriptファイルを管理画面で読み込ませる
   */
  function ws_load_admin_files( $hook ) {

    $plugin_path = plugin_dir_path( __FILE__ );
    $plugin_url  = plugin_dir_url( __FILE__ );

    // スタイルシートの読み込み
    $css = $plugin_path.'css/admin.css';
    if ( file_exists($css) ) {
      wp_enqueue_style( 'ws-youtube-admin-css',
                        $plugin_url.'css/admin.css',
                        array(),
                        filemtime( $css )
                       );
    }
    $css = $plugin_path.'css/common.css';
    if ( file_exists($css) ) {
      wp_enqueue_style( 'ws-youtube-common-css',
                        $plugin_url.'css/common.css',
                        array(),
                        filemtime( $css )
                       );
    }
 
    // JavaScript の読み込み
    $js = $plugin_path.'/js/admin.js';
    if ( file_exists($js) ) {
      wp_enqueue_script( 'ws-youtube-admin-js',
                         $plugin_url.'js/admin.js',
                         array('jquery'),
                         filemtime( $js )
                       );
    }
    $js = $plugin_path.'/script/common.js';
    if ( file_exists($js) ) {
      wp_enqueue_script( 'ws-youtube-js',
                         $plugin_url.'js/common.js',
                         array('jquery'),
                         filemtime( $js )
                       );
    }

    return;
  }


  /**
   * プラグインを有効化したとき（テーブル作成）
   */
  public function ws_plugin_start() {

    // DBの作成
    $this->ws_create_table();

    return;
  }


  /**
   * プラグインを無効化したとき（テーブル削除）
   */
  public function ws_plugin_finish() {

    // DBの削除
    $this->ws_drop_table();

    // オプションデータの削除
    delete_option( 'ws_youtube_api_key' );
    delete_option( 'ws_youtube_cron_timing' );
    delete_option( 'ws_youtube_channel_id_list' );
    delete_option( 'ws_youtube_post_attr' );
    delete_option( 'ws_youtube_ads_code_list' );

    // cronの削除
    wp_clear_scheduled_hook( 'ws_youtube_run_cron' );

    return;
  }


  /**
   * cron用関数
   */
  public function ws_run_cron() {
    $this->ws_run_scrape(false);
    return;
  }


  /**
   * DBテーブル作成
   */
  public function ws_create_table() {
    global $wpdb;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php'); // dbDeltaを使う

    // テーブルがあるかないか確認
    if ( $wpdb->get_var('SHOW TABLES LIKE \''.$this->db_table_youtube.'\'') != $this->db_table_youtube ) {
      $sql  = 'CREATE TABLE IF NOT EXISTS ' . $this->db_table_youtube . ' ( ';
      $sql .= '    no        int NOT NULL AUTO_INCREMENT, ';
      $sql .= '    type      char(32), ';
      $sql .= '    postid    int, ';
      $sql .= '    channelid text, ';
      $sql .= '    videoid   text, ';
      $sql .= '    title     text, ';
      $sql .= '    date      char(16), ';
      $sql .= '    time      char(16), ';
      $sql .= '    dead      int, ';
      $sql .= '    UNIQUE KEY no(no) ';
      $sql .= ' ) '. $wpdb->get_charset_collate().';';
      dbDelta($sql);

      update_option( 'ws_youtube_db_version', $this->ws_youtube_db_version );
    }

    return;
  }


  /**
   * DBテーブル削除
   */
  public function ws_drop_table() {
    global $wpdb;

    // DBテーブルの削除
    $sql = 'DROP TABLE IF EXISTS '.$this->db_table_youtube;
    $wpdb->query($sql);

    delete_option( 'ws_youtube_db_version' );

    return;
  }


  /**
   * 管理画面メニュー
   */
  function ws_admin_menu() {

    // 誰がメニューを操作できるか
    $capability = 'manage_options'; // 管理者だけ
    // $capability = 'edit_posts'; // 寄稿者だけ

    add_menu_page( 'white-software', '自動投稿Youtube', $capability,
                   'ws_youtube_admin_menu1', array( $this, 'ws_menu_plugin_settings' ),
                   'dashicons-format-video' );
    add_submenu_page( 'ws_youtube_admin_menu1', '投稿設定', '投稿設定', $capability,
                      'ws_youtube_admin_menu1', array( $this, 'ws_menu_plugin_settings' ) );
    add_submenu_page( 'ws_youtube_admin_menu1', 'データ取得', 'データ取得', $capability,
                      'ws_youtube_admin_menu2', array( $this, 'ws_menu_get_contents' ) );
    add_submenu_page( 'ws_youtube_admin_menu1', 'データベース参照', 'データベース参照', $capability,
                      'ws_youtube_admin_menu3', array( $this, 'ws_menu_check_db' ) );
    add_submenu_page( 'ws_youtube_admin_menu1', '指定Youtube投稿', '指定Youtube投稿', $capability,
                      'ws_youtube_admin_menu4', array( $this, 'ws_menu_post_youtube' ) );
    add_submenu_page( 'ws_youtube_admin_menu1', '広告設定', '広告設定', $capability,
                      'ws_youtube_admin_menu5', array( $this, 'ws_menu_ads_settings' ) );

    return;
  }


  /**
   * 管理バーメニュー
   */
  function ws_admin_bar_menu( $wp_admin_bar ) {
    if ( !current_user_can( 'administrator' ) && !current_user_can( 'editor' ) ) {
      // $wp_admin_bar->remove_menu('comments');
    }
    return;
  }


  /**
   * プラグイン一覧に設定リンクを追加
   */
  function ws_plugin_action_links($links, $file) {
    if ( $this->ws_this_plugin == '' ) {
      $this->ws_this_plugin = plugin_basename(__FILE__);
    }

    if ( $file == $this->ws_this_plugin ) {
      $settings_link = '<a href="' . home_url('/wp-admin/admin.php?page=ws_youtube_admin_menu1') . '">設定</a>';
      array_unshift($links, $settings_link);
    }

    return $links;
  }


  /**
   * 管理メニュー：プラグイン設定
   */
  function ws_menu_plugin_settings() {
    global $wpdb;

    echo '<h2>', $this->ws_plugin_name, 'プラグイン ver ', $this->ws_plugin_version, '</h2>', PHP_EOL;
    echo '<h3>設定画面</h3>', PHP_EOL;

    //// Youtube Data API key
    echo '<hr class="ws-hr" />', PHP_EOL;
    echo '<h3>Youtube Data API Key</h3>', PHP_EOL;

    $apikey = '';
    if ( isset($_REQUEST['type']) && $_REQUEST['type'] == 'api-key' && check_admin_referer('white-software-youtube', '_wpnonce') ) {
      $apikey = ( isset($_REQUEST['api-key']) ? $_REQUEST['api-key'] : '' );

      if ( $apikey == '' ) {
        delete_option( 'ws_youtube_api_key' );
        echo '<div class="ws-save-message">API Key を削除しました</div>', PHP_EOL;
      }
      else {
        update_option( 'ws_youtube_api_key', $apikey );
        echo '<div class="ws-save-message">API Key を設定しました</div>', PHP_EOL;
      }
    }
    else {
      $apikey = get_option( 'ws_youtube_api_key', '' );
    }

    echo '<div class="ws-box">';
    echo '<form class="ws-admin-form" action="" method="post">';
    echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
    echo '<input type="hidden" name="type" value="api-key" />';
    echo '<div><input type="search" name="api-key" placeholder="Youtube Data API Key" style="min-width: 480px;" value="', ( !empty($apikey) ? $apikey : '' ), '" />';
    echo '<button type="submit" class="button button-primary" name="submit">保存する</button></div>';
    echo '</form>';
    echo '</div>', PHP_EOL;

    //// 実行タイミング
    echo '<hr class="ws-hr" />', PHP_EOL;
    echo '<h3>実行タイミング</h3>', PHP_EOL;

    $cronlist= array( 'hour' => '', 'every' => '' );
    if ( isset($_REQUEST['type']) && $_REQUEST['type'] == 'cron-timing' && check_admin_referer('white-software-youtube', '_wpnonce') ) {
      $hour  = ( isset($_REQUEST['hour'])  ? $_REQUEST['hour']  : '' );
      $every = ( isset($_REQUEST['every']) ? $_REQUEST['every'] : '' );

      if ( $hour == '' || $every == '' ) {
        delete_option( 'ws_youtube_cron_timing' );
        wp_clear_scheduled_hook( 'ws_youtube_run_cron' );

        echo '<div class="ws-save-message">cron設定を削除しました</div>', PHP_EOL;
      }
      else {
        $cronlist = array( 'hour' => $hour, 'every' => $every );
        update_option( 'ws_youtube_cron_timing', $cronlist );
        wp_clear_scheduled_hook( 'ws_youtube_run_cron' );

        $nowh = intval(wp_date('H'));
        if ( $nowh > intval($hour) ) {
          $crontime = sprintf('%s %02d:00:00', wp_date('Y/m/d', strtotime("+1day")), $hour);
        }
        else {
          $crontime = sprintf('%s %02d:00:00', wp_date('Y/m/d'), $hour);
        }

        // gmt時刻で設定する必要がある．
        $crontimegmt = strtotime(get_gmt_from_date($crontime));

        if ( $every == '24' ) {
          wp_schedule_event( $crontimegmt, 'daily', 'ws_youtube_run_cron' );
        }
        else if ( $every == '12' ) {
          wp_schedule_event( $crontimegmt, 'twicedaily', 'ws_youtube_run_cron' );
        }
        else if ( $every == '1' ) {
          wp_schedule_event( $crontimegmt, 'hourly', 'ws_youtube_run_cron' );
        }
        else {
          wp_schedule_event( $crontimegmt, sprintf('every%02dhours', $every), 'ws_youtube_run_cron' );
        }
        echo '<div class="ws-save-message">cronを設定しました</div>', PHP_EOL;
      }
    }
    else {
      $cronlist = get_option( 'ws_youtube_cron_timing', array( 'hour' => '', 'every' => '' ) );
    }

    if ( !is_array($cronlist) ) $cronlist= array( 'hour' => '', 'every' => '' );

    echo '<div class="ws-box">';
    echo '<form class="ws-admin-form" action="" method="post">';
    echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
    echo '<input type="hidden" name="type" value="cron-timing" />';
    echo '<div>';
    echo '<select name="hour">';
    echo '<option value="">－実行しない－</option>';
    for ( $i = 0 ; $i < 24 ; $i++ ) {
      if ( $cronlist['hour'] != '' && $i == intval($cronlist['hour'])  ) {
        echo '<option value="', $i, '" selected>', $i, '時から</option>';
      }
      else {
        echo '<option value="', $i, '">', $i, '時から</option>';
      }
    }
    echo '</select>';
    echo ' &nbsp; ';
    echo '<select name="every">';
    echo '<option value="">－実行しない－</option>';
    $tmpar = array( 1, 2, 3, 6, 12, 24 );
    foreach ( $tmpar as $every ) {
      if ( $cronlist['every'] == $every ) {
        echo '<option value="', $every, '" selected>', $every, '時間ごと</option>';
      }
      else {
        echo '<option value="', $every, '">', $every, '時間ごと</option>';
      }
    }
    echo '</select>';
    echo ' &nbsp; ';
    echo '<button type="submit" class="button button-primary" name="submit" value="設定">設定を保存</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>', PHP_EOL;

    //// フォーム
    echo '<hr class="ws-hr" />', PHP_EOL;
    $channel_id_list = array();
    if ( isset($_REQUEST['type']) && $_REQUEST['type'] == 'channel-id' && check_admin_referer('white-software-youtube', '_wpnonce') ) {
      $idlist = ( !empty($_REQUEST['channel-id-list']) ? $_REQUEST['channel-id-list'] : '' );

      if ( $idlist == '' ) {
        delete_option( 'ws_youtube_channel_id_list' );
        echo '<div class="ws-save-message">チャンネルIDを削除しました</div>', PHP_EOL;
      }
      else {
        $tmpar = preg_split( '/(\r|\n|\r\n)+/', $idlist );
        $channel_id_list = array_filter($tmpar);
        update_option( 'ws_youtube_channel_id_list', $channel_id_list );
        echo '<div class="ws-save-message">チャンネルIDを保存しました</div>', PHP_EOL;
      }
    }
    else {
      $channel_id_list = get_option( 'ws_youtube_channel_id_list', array() );
    }
    if ( isset($_REQUEST['type']) && $_REQUEST['type'] == 'post-attr' && check_admin_referer('white-software-youtube', '_wpnonce') ) {
      $submit        = ( !empty($_REQUEST['submit'])        ? $_REQUEST['submit']                : '' );
      $channelid     = ( !empty($_REQUEST['channelid'])     ? $_REQUEST['channelid']             : '' );
      $autopost      = ( !empty($_REQUEST['autopost'])      ? intval($_REQUEST['autopost'])      : 0 );
      $updatecomment = ( !empty($_REQUEST['updatecomment']) ? intval($_REQUEST['updatecomment']) : 0 );
      $maxcomment    = ( !empty($_REQUEST['maxcomment'])    ? intval($_REQUEST['maxcomment'])    : 20 );
      $userid        = ( !empty($_REQUEST['userid'])        ? $_REQUEST['userid']                : '' );
      $posttype      = ( !empty($_REQUEST['post_type'])     ? $_REQUEST['post_type']             : '' );
      $poststatus    = ( !empty($_REQUEST['post_status'])   ? $_REQUEST['post_status']           : 'draft' );
      $category      = ( !empty($_REQUEST['category'])      ? $_REQUEST['category']              : '' );
      $maxnum        = ( !empty($_REQUEST['maxnum'])        ? intval($_REQUEST['maxnum'])        : 0 );

      $post_attr = get_option( 'ws_youtube_post_attr', array( ) );
      if ( $submit == '削除' ) {
        unset($post_attr[$channelid]);
        $tmpar = array();
        foreach ( $channel_id_list as $channel ) {
          if ( strpos($channel, $channelid) === 0 ) {
          }
          else {
            $tmpar[] = $channel;
          }
        }
        $channel_id_list = $tmpar;
        update_option( 'ws_youtube_channel_id_list', $channel_id_list );
        echo '<div class="ws-save-message">チャンネルを削除しました</div>', PHP_EOL;
      }
      else {
        $post_attr[$channelid] = array(
          'userid'        => $userid,
          'autopost'      => $autopost,
          'updatecomment' => $updatecomment,
          'maxcomment'    => $maxcomment,
          'post_type'     => $posttype,
          'post_status'   => $poststatus,
          'category'      => $category,
          'maxnum'        => $maxnum
        );
        echo '<div class="ws-save-message">投稿設定を保存しました</div>', PHP_EOL;
      }
      update_option( 'ws_youtube_post_attr', $post_attr );
    }
    else {
      $post_attr = get_option( 'ws_youtube_post_attr', array( ) );
    }

    //// チャンネルIDを調べる
    echo '<h3>YoutubeチャンネルIDを調べて登録</h3>', PHP_EOL;
    echo '<div class="ws-box">';
    echo '<form class="ws-admin-form" action="" method="post">';
    echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
    echo '<input type="hidden" name="type" value="search-channel-id" />';
    echo '<div><input type="search" name="word" placeholder="検索ワード" style="min-width: 480px;" value="', ( !empty($_REQUEST['word']) ? $_REQUEST['word'] : '' ), '" />';
    echo '<button type="submit" class="button button-primary" name="submit" value="検索">検索する</button></div>';
    echo '</form>';
    echo '</div>', PHP_EOL;

    if ( isset($_REQUEST['type']) &&
         $_REQUEST['type'] == 'search-channel-id' &&
         $_REQUEST['word'] != '' &&
         check_admin_referer('white-software-youtube', '_wpnonce') ) {
      $response = $this->getYoutubeSearchChannelID( $_REQUEST['word'] );

      $json = json_decode( $response );

      if ( $json && isset($json->items) && count($json->items) > 0 ) {
        echo '<div style="margin: 0.5%;">';
        echo '<h4 style="margin:0;">検索結果</h4>';
        // echo '<pre>';
        // echo json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        // echo '</pre>';
        echo '<div style="padding: 0.5%; border:1px solid #cccccc; max-width:800px; height:400px; overflow-y:auto; background-color:#f8f8f8;">';
        foreach ( $json->items as $item ) {
          if ( isset($item->snippet) ) {
            $id    = ( isset($item->snippet->channelId)   ? $item->snippet->channelId   : '' );
            $title = ( isset($item->snippet->title)       ? $item->snippet->title       : '' );
            $desc  = ( isset($item->snippet->description) ? $item->snippet->description : '' );
            $image = ( isset($item->snippet->thumbnails->default->url) ? $item->snippet->thumbnails->default->url : '' );
            if ( $id != '' ) {
              echo '<table style="margin: 8px; border: 1px solid #cccccc; max-width: 800px; background-color: #ffffff;">';
              if ( $image != '' ) {
                echo '<tr>';
                echo '<td nowrap rowspan="3"><img src="', $image, '" /></td>';
                echo '<td nowrap>チャンネルID</td><td><input type="text" value="', $id, ' , ', esc_attr($title), '"  style="width:30em;" onclick="copyID(this.value)" /> ← クリックするとコピーします</td>';
                echo '</tr>';
                echo '<tr><td nowrap>チャンネル名</td><td>', esc_html($title), '</td></tr>';
                echo '<tr><td nowrap>説明文</td><td>', esc_html($desc), '</td></tr>';
              }
              else {
                echo '<tr><td nowrap>チャンネルID</td><td><input type="text" value="', $id, ',', esc_attr($title), '"  style="width:30em;" onclick="copyID(this.value)" /> ← クリックするとコピーします</td></tr>';
                echo '<tr><td nowrap>チャンネル名</td><td>', esc_html($title), '</td></tr>';
                echo '<tr><td nowrap>説明文</td><td>', esc_html($desc), '</td></tr>';
              }
              echo '</table>';
            }
          }
        }
        echo '</div>';
        echo '</div>';
      }
    }

    //// 対象とする Youtube チャンネル
    echo '<div class="ws-box">';
    echo '<form class="ws-admin-form" action="" method="post">';
    echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
    echo '<input type="hidden" name="type" value="channel-id" />';
    echo '<div style="margin: 0.5%;">';
    echo '<h4 style="margin:0;">登録済みチャンネル一覧</h4>';
    echo '<div><textarea name="channel-id-list" id="channel-id-list" placeholder="チャンネルのリスト" style="max-width: 800px;height:8.0em;">', esc_textarea(implode(PHP_EOL, $channel_id_list)), '</textarea></div>';
    echo '</div>';
    echo '<div><button type="submit" class="button button-primary" name="submit" value="保存">保存する</button>';
    echo ' &nbsp; ', count($channel_id_list), '件</div>';
    echo '</form>';
    echo '</div>', PHP_EOL;

    //// 投稿時の属性設定
    echo '<hr class="ws-hr" />', PHP_EOL;
    echo '<h3>投稿時の属性設定</h3>', PHP_EOL;

    // echo '<pre>';
    // print_r($post_attr);
    // echo '</pre>';

    $myuser = wp_get_current_user();

    $users = get_users( array(
      'roles'   => array( 'administrator', 'editor' ),
      'orderby' => 'ID',
      'order'   => 'ASC',
    ));

    $post_types = get_post_types(
      array( 'public' => true ),
      'objects'
    );
    $post_type_info = array();
    foreach ( $post_types as $post_type ) {
      $post_type_info[$post_type->name] = $post_type;
    }


    $taxonomies = get_taxonomies(
      array( 'public' => true ),
      'objects'
    );
    $category_info = array();
    foreach ( $taxonomies as $taxonomy ) {
      if ( is_array($taxonomy->object_type) && count($taxonomy->object_type) > 0 ) {
        foreach ( $taxonomy->object_type as $type ) {
          if ( !isset($category_info[$type]) ) $category_info[$type] = array();
          $category_info[$type][] = $taxonomy;
        }
      }
    }

    $channelid = ( !empty($_REQUEST['channelid']) ? $_REQUEST['channelid'] : ( count($channel_id_list) > 0 ? explode(' , ', $channel_id_list[0])[0] : '' ) );

    if ( count($channel_id_list) == 0 ) {
      echo '<p>チャンネルを登録してください</p>', PHP_EOL;
    }
    else {
      echo '<div class="ws-box">';
      // echo '<form class="ws-admin-form" action="" method="post">';
      echo '<form class="ws-admin-form" action="" method="post">';
      echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
      echo '<input type="hidden" name="type" value="post-attr" />';
      echo '<div><span>設定対象のチャンネルを選択</span> &nbsp; <span><select name="channelid" onchange="location.href=\'', get_admin_url('', 'admin.php?page=ws_youtube_admin_menu1'), '&channelid=\'+this.value;">';
      foreach ( $channel_id_list as $channel_info ) {
        list( $channel_id, $channel_name ) = explode(' , ', $channel_info);
        if ( $channel_id == $channelid ) {
          echo '<option value="', $channel_id, '" selected>', esc_html($channel_name), '</option>';
        }
        else {
          echo '<option value="', $channel_id, '">', esc_html($channel_name), '</option>';
        }
      }
      echo '</select></span></div>';
      echo '<table style="margin: 0.5%; padding: 0.5%; border: 1px solid #cccccc; background-color: #ffffff; border-radius: 4px;">';

      if ( isset($post_attr[$channelid]['autopost']) && $post_attr[$channelid]['autopost'] == 1 ) {
        echo '<tr><td>自動投稿を実施</td><td><input type="checkbox" name="autopost" id="autopost" value="1" checked /><label for="autopost">自動投稿する</label></td></tr>';
      }
      else {
        echo '<tr><td>自動投稿を実施</td><td><input type="checkbox" name="autopost" id="autopost" value="1" /><label for="autopost">自動投稿する</label></td></tr>';
      }

      if ( isset($post_attr[$channelid]['updatecomment']) && $post_attr[$channelid]['updatecomment'] == 1 ) {
        echo '<tr><td>コメントの更新</td><td><input type="checkbox" name="updatecomment" id="updatecomment" value="1" checked /><label for="updatecomment">自動更新する</label></td></tr>';
      }
      else {
        echo '<tr><td>コメントの更新</td><td><input type="checkbox" name="updatecomment" id="updatecomment" value="1" /><label for="updatecomment">自動更新する</label></td></tr>';
      }

      echo '<tr><td>チェックするコメント最大数</td><td><select name="maxcomment">';
      $flag = false;
      for ( $i = 10 ; $i <= 100 ; $i++ ) {
        if ( $flag == false && (
                                ( isset($post_attr[$channelid]['maxcomment']) && $post_attr[$channelid]['maxcomment'] == $i ) ||
                                ( !isset($post_attr[$channelid]['maxcomment']) && $i == 20 ) ) ) {
          echo '<option value="', $i, '" selected>', $i, '件までチェック</option>';
          $flag = true;
        }
        else {
          echo '<option value="', $i, '">', $i, '件までチェック</option>';
        }
      }
      echo '</select></td></tr>';

      echo '<tr><td>投稿ユーザーを選択</td><td><select name="userid">';
      $flag = false;
      foreach ( $users as $user ) {
        if ( $flag === false && isset($post_attr[$channelid]['userid']) && $user->ID == $post_attr[$channelid]['userid'] ) {
          echo '<option value="', $user->ID, '" selected>', $user->data->display_name, '</option>';
          $flag = true;
        }
        else {
          echo '<option value="', $user->ID, '">', $user->data->display_name, '</option>';
        }
      }
      echo '</select></td></tr>';

      echo '<tr><td>投稿状態を選択</td><td><select name="post_status">';
      if ( isset($post_attr[$channelid]['post_status']) && 'publish' == $post_attr[$channelid]['post_status'] ) {
        echo '<option value="draft">下書き</option>';
        echo '<option value="publish" selected>公開</option>';
      }
      else {
        echo '<option value="draft" selected>下書き</option>';
        echo '<option value="publish">公開</option>';
      }
      echo '</select></td></tr>';

      echo '<tr><td>投稿タイプを選択</td><td><select name="post_type">';
      $flag = false;
      foreach ( $post_types as $post_type ) {
        if ( in_array( $post_type->name, array('attachment','revision','nav_menu_item','tdb_templates'), true ) !== true ) {
          if ( $flag === false && isset($post_attr[$channelid]['post_type']) && $post_type->name == $post_attr[$channelid]['post_type'] ) {
            echo '<option value="', $post_type->name, '" selected>', $post_type->label, '</option>';
            $flag = true;
          }
          else {
            echo '<option value="', $post_type->name, '">', $post_type->label, '</option>';
          }
        }
      }
      echo '</select></td></tr>';

      echo '<tr><td>タクソノミーを選択</td><td><select name="category">';
      echo '<option value="">選択なし</option>';
      $flag = false;
      foreach ( $post_types as $post_type ) {
        if ( in_array( $post_type->name, array('attachment','revision','nav_menu_item','tdb_templates'), true ) !== true ) {
          if ( is_array($category_info[$post_type->name]) && count($category_info[$post_type->name]) > 0 ) {
            foreach ( $category_info[$post_type->name] as $cat ) {
              if ( in_array( $cat->name, array('post_tag','post_format'), true ) !== true ) {
                echo '<optgroup label="', $post_type->label, '：', $cat->label, '">';
                $terms = get_terms( array( $cat->name ), array( 'hide_empty' => '0') );
                foreach ( $terms as $term ) {
                  $val = $cat->name.','.$term->slug;
                  if ( $flag === false && isset($post_attr[$channelid]['category']) && $val == $post_attr[$channelid]['category'] ) {
                    echo '<option value="', $val, '" selected>', $term->name, '</option>';
                    $flag = true;
                  }
                  else {
                    echo '<option value="', $val, '">', $term->name, '</option>';
                  }
                }
              }
            }
            echo '</optgroup>';
          }
        }
      }
      echo '</select></td></tr>';

      echo '<tr><td>自動登録する動画最大数</td><td><select name="maxnum">';
      $flag = false;
      for ( $i = 0 ; $i <= 20 ; $i++ ) {
        if ( $i == 0 ) {
          if ( $flag == false && isset($post_attr[$channelid]['maxnum']) && $post_attr[$channelid]['maxnum'] == 0 ) {
            echo '<option value="', $i, '" selected>すべて登録</option>';
            $flag = true;
          }
          else {
            echo '<option value="', $i, '">すべて登録</option>';
          }
        }
        else {
          if ( $flag == false && isset($post_attr[$channelid]['maxnum']) && $post_attr[$channelid]['maxnum'] == $i ) {
            echo '<option value="', $i, '" selected>', $i, '件まで登録</option>';
            $flag = true;
          }
          else {
            echo '<option value="', $i, '">', $i, '件まで登録</option>';
          }
        }
      }
      echo '</select></td></tr>';

      echo '</table>';
      echo '<div>';
      echo '<button type="submit" class="button button-primary" name="submit" value="保存">投稿設定を保存</button>';
      echo ' &ensp; ';
      echo '<button type="submit" class="button button-secondary" name="submit" value="削除">チャンネルを削除</button>';
      echo '</div>';
      echo '</form>';
      echo '</div>', PHP_EOL;
    }

    return;
  }


  /**
   * 管理メニュー：データ取得
   */
  function ws_menu_get_contents() {
    global $wpdb;

    echo '<h2>', $this->ws_plugin_name, 'プラグイン ver ', $this->ws_plugin_version, '</h2>', PHP_EOL;
    echo '<h3>データ取得</h3>', PHP_EOL;

    echo '<hr class="ws-hr" />', PHP_EOL;

    echo '<div>';
    echo '<form class="ws-admin-form" action="" method="post">';
    echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
    echo '<input type="hidden" name="type" value="api-test" />';
    echo '<div><input type="submit" class="button button-primary" value="データ取得テスト" /> ※ 設定にしたがって投稿します</div>';
    echo '</form>';
    echo '</div>';
    echo '<p>内部でAPI呼び出す度に1秒のウェイトをとっているため，それなりに時間がかかります</p>';

    if ( isset($_REQUEST['type']) && $_REQUEST['type'] == 'api-test' && check_admin_referer('white-software-youtube', '_wpnonce') ) {
      $this->ws_run_scrape(true);
    }

    return;
  }


  /**
   * 管理メニュー：データベース参照
   */
  function ws_menu_check_db() {
    global $wpdb;

    echo '<h2>', $this->ws_plugin_name, 'プラグイン ver ', $this->ws_plugin_version, '</h2>', PHP_EOL;
    echo '<h3>データベース参照</h3>', PHP_EOL;

    //// データベース初期化
    echo '<hr class="ws-hr" />', PHP_EOL;
    echo '<h3>データベースの初期化</h3>', PHP_EOL;
    echo '<p>APIで情報を取得し，自動投稿したものを記録したデータベースです</p>', PHP_EOL;

    if ( isset($_REQUEST['type']) && $_REQUEST['type'] == 'db-clear' && check_admin_referer('white-software-youtube', '_wpnonce') ) {
      $this->ws_drop_table();
      $this->ws_create_table();
      echo '<div class="ws-save-message">データベースを初期化しました</div>', PHP_EOL;
    }

    echo '<div class="ws-box">';
    echo '<form class="ws-admin-form" action="" method="post" onsubmit="return confirm(\'データベースの初期化を実施します\')">';
    echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
    echo '<input type="hidden" name="type" value="db-clear" />';
    echo '<div><input type="submit" class="button button-primary" value="データベース初期化" /></div>';
    echo '</form>';
    echo '</div>', PHP_EOL;


    //// データベース内容一覧
    echo '<hr class="ws-hr" />', PHP_EOL;
    echo '<h3>データベース内容一覧</h3>', PHP_EOL;

    $results = $wpdb->get_results('SELECT * FROM '.$this->db_table_youtube.' ORDER BY date DESC, time DESC');

    echo '<div class="ws-box">';
    echo '<div>', count($results), '件</div>';
    echo '<table style="table-layout:fixed;">';
    echo '<thead><tr><th>PostID</th><th>ChannelID</th><th>対象動画</th><th>更新日時</th></tr></thead>';
    echo '<tbody>';
    foreach ( $results as $val ) {
      echo '<tr>';
      $post = get_post($val->postid);
      echo '<td style="width:10em;text-align:left;">', ( $post ? '<a href="'.get_permalink($val->postid).'">'.$val->postid.' : '.esc_html($post->post_title).'</a>' : '' ), '</a></td>';
      echo '<td style="width:10em;text-align:center;">', esc_html($val->channelid), '</a></td>';
      echo '<td style="text-align:left;"><a href="https://youtube.com/watch?v=', esc_attr($val->videoid), '" target="_blank">', esc_html($val->title), '</a></td>';
      echo '<td>', $val->date, ' ', $val->time, '</td>';
      echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>', PHP_EOL;

    return;
  }


  /**
   * 管理メニュー：指定Youtube投稿
   */
  function ws_menu_post_youtube() {
    global $wpdb;

    echo '<h2>', $this->ws_plugin_name, 'プラグイン ver ', $this->ws_plugin_version, '</h2>', PHP_EOL;
    echo '<h3>指定Youtube投稿</h3>', PHP_EOL;

    echo '<hr class="ws-hr" />', PHP_EOL;

    settings_errors( 'ws-custom-tool-messages' );

    echo '<div>';
    echo '<form class="ws-admin-form" action="" method="post">';
    echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
    echo '<input type="hidden" name="type" value="dev-test" />';
    echo '<div style="margin: 0.2em 0;"><span>Youtube URL</span> <input type="url" style="width: calc( 100% - 6.0em ); max-width: 640px;" name="ws-youtube-url" value="', esc_url($_REQUEST['ws-youtube-url'] ?? ''), '" placeholder="Youtube URL = https://youtube.com/watch?v=XXXXXX" />';
    echo '<input type="submit" style="width:10.0em;" class="button button-primary" value="指定Youtube投稿" /></div>';
    echo '</form>';
    echo '</div>';

    if ( isset($_REQUEST['type']) &&
         $_REQUEST['type'] == 'dev-test' &&
         !empty($_REQUEST['ws-youtube-url']) &&
         check_admin_referer('white-software-youtube', '_wpnonce') ) {
      echo '<div style="margin: 2.0em 0 0;">';

      // ==========================================
      // 設定
      // ==========================================
      $apiKey    = get_option( 'ws_youtube_api_key', '' );
      $targetUrl = $_REQUEST['ws-youtube-url'] ?? '';
      $maxNum    = 10;

      echo '<div>Target Youtube URL = <a href="', $targetUrl, '">', $targetUrl, '</a></div>';

      // ==========================================
      // 関数定義
      // ==========================================

      // 動画IDを抽出する関数
      // function getYoutubeVideoId($url) {
      //     $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
      //     if (preg_match($pattern, $url, $matches)) {
      //         return $matches[1];
      //     }
      //     return false;
      // }

      // APIリクエストを実行してJSONを返す関数
      // function fetchYoutubeApi($url) {
      //     // サーバー設定によっては file_get_contents で外部URLを開けない場合があるため、
      //     // エラー抑制演算子(@)をつけています。本番環境ではcURL推奨です。
      //     $response = @file_get_contents($url);
      // 
      //     if ( $response === false ) {
      //         return ['error' => '通信エラー: APIへの接続に失敗しました。'];
      //     }
      // 
      //     return json_decode($response, true);
      // }

      // ==========================================
      // メイン処理
      // ==========================================

      // 1. 動画IDを取得
      // $videoId = getYoutubeVideoId($targetUrl);
      $videoId = '';
      $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
      if ( preg_match($pattern, $targetUrl, $matches) ) {
          $videoId = $matches[1];
      }

      if ( empty($videoId) ) {
        echo '<div style="color:red;">Error: 無効なYoutubeURLです</div>';
      }
      else {
        echo '<h2>YouTubeデータ取得結果</h2>';

        // ------------------------------------------
        // 2. 動画の基本情報 (Metadata) を取得
        // ------------------------------------------
        $videoApiUrl = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
            'id'   => $videoId,
            'key'  => $apiKey,
            'part' => 'snippet,statistics',
            'fields' => 'items(snippet(title,description,thumbnails,publishedAt,channelId,channelTitle),statistics(viewCount,likeCount))'
        ]);

        // エラー時もレスポンスを取得する設定
        $options = [
            'http' => [
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($options);

        // API実行
        $response = file_get_contents($videoApiUrl, false, $context);
        $videoData = null; // 初期化

        // 1. 通信そのものが失敗、またはHTTPステータスが200以外の場合
        if ($response === false || strpos($http_response_header[0], '200') === false) {
            
            $status = isset($http_response_header[0]) ? $http_response_header[0] : 'Unknown Status';
            $errorDetails = '詳細不明';
            
            if ($response) {
                $json = json_decode($response, true);
                if (isset($json['error']['message'])) {
                    $errorDetails = $json['error']['message'];
                } elseif (isset($json['error']['errors'][0]['reason'])) {
                    $errorDetails = $json['error']['errors'][0]['reason'];
                }
            }

            echo '<div style="color:red; border:1px solid red; padding:10px; margin-bottom:10px;">';
            echo '<strong>動画情報の取得に失敗しました (API Error)</strong><br>';
            echo 'Status: ' . htmlspecialchars($status) . '<br>';
            echo 'Message: ' . htmlspecialchars($errorDetails);
            echo '</div>';

        }
        else {
          $videoData = json_decode($response, true);

          if ( empty($videoData['items']) ) {
            echo '<div style="color:red;">Error: 動画が見つかりませんでした</div>';
          }
          else {
            $snippet = $videoData['items'][0]['snippet'];
            $stats   = $videoData['items'][0]['statistics'];

            $channel_id   = $snippet['channelId'] ?? '';
            $channel_name = $snippet['channelTitle'] ?? '';
            $date         = $snippet['publishedAt'] ?? '';
            $title        = $snippet['title'] ?? '';
            $image        = ( isset($snippet['thumbnails']['defult']['url']) ? $snippet['thumbnails']['defult']['url'] : '' );
            $image        = ( isset($snippet['thumbnails']['medium']['url']) ? $snippet['thumbnails']['medium']['url'] : $image );
            $image        = ( isset($snippet['thumbnails']['high']['url'])   ? $snippet['thumbnails']['high']['url'] : $image );
            $caption      = '';

            echo '<h2>動画情報</h2>';
            echo '<ul>';
            echo '<li><strong>タイトル:</strong> ', htmlspecialchars($title), '</li>';
            echo '<li><strong>公開日:</strong> ', htmlspecialchars($date), '</li>';
            echo '<li><strong>再生回数:</strong> ', number_format($stats['viewCount']), '回</li>';
            echo '<li><strong>いいね数:</strong> ', number_format($stats['likeCount'] ?? 0), '</li>';
            echo '<li><strong>サムネイル:</strong><br>';
            echo '<img src="', esc_url($image), '"></li>';
            echo '</ul>';

            // echo '<pre style="background-color:#f8f8f8;">';
            // print_r( $snippet );
            // echo '</pre>';

            // ------------------------------------------
            // 3. コメント (CommentThreads) を$maxNum件取得
            // ------------------------------------------
            $comment_arr = array();
            $commentApiUrl = "https://www.googleapis.com/youtube/v3/commentThreads?" . http_build_query([
                'videoId'    => $videoId,
                'key'        => $apiKey,
                'part'       => 'snippet',      // コメントの本文などを含む
                'maxResults' => $maxNum,        // 取得件数
                'order'      => 'relevance',    // 並び順: relevance(関連順), time(新しい順)
                'textFormat' => 'plainText',    // htmlタグを含まないテキストで取得
                'fields'     => 'items(snippet(topLevelComment(snippet(authorDisplayName,authorProfileImageUrl,textDisplay,likeCount,publishedAt))))'
            ]);

            // 【変更点1】4xx, 5xxエラー時でもレスポンス本文(エラー詳細JSON)を取得する設定
            $options = [
                'http' => [
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($options);

            // 【変更点2】@を外し、コンテキスト($context)を第3引数に渡す
            $response = file_get_contents($commentApiUrl, false, $context);

            $errorFlag = false;

            // 【変更点3】レスポンスヘッダーを確認して成功/失敗を判定
            // $http_response_header は file_get_contents 実行時に自動生成される変数です
            if ($response === false || strpos($http_response_header[0], '200') === false) {
                
                // レスポンスがあればJSONを解析
                $json = $response ? json_decode($response, true) : null;
                
                // エラー理由(reason)を取得
                // ネストが深いため、存在チェックをしつつ取得します
                $reason = '';
                if (isset($json['error']['errors'][0]['reason'])) {
                    $reason = $json['error']['errors'][0]['reason'];
                }

                // --- ここで判定します ---
                if ($reason === 'commentsDisabled') {
                    // コメントオフの場合の特別な表示
                    echo '<div style="color:orange;">この動画はコメント機能が無効（オフ）になっています。</div>';
                    $errorFlag = false;
                }
                else {
                    // それ以外の通常エラー（APIキー間違い、通信エラーなど）
                    $status = isset($http_response_header[0]) ? $http_response_header[0] : 'Unknown Status';
                    $message = isset($json['error']['message']) ? $json['error']['message'] : '詳細不明';
                    
                    echo '<div style="color:red; border:1px solid red; padding:10px;">';
                    echo '<strong>APIエラー:</strong> ' . htmlspecialchars($status) . '<br>';
                    echo 'Reason: ' . htmlspecialchars($reason) . '<br>';
                    echo 'Message: ' . htmlspecialchars($message);
                    echo '</div>';
                    $errorFlag = true;
                }

                $commentData = null;
            }
            else {
              $commentData = json_decode($response, true);
            }

            if ( $errorFlag === false ) {

              echo '<h2>コメント (上位', $maxNum, '件)</h2>';

              if ( $commentData === null ) {
                echo '<div style="color:red;">Error: コメントはオフです</div>';
              }
              else if ( isset($commentData['error']) ) {
                // 通信エラーまたはAPIエラー（コメント無効など）
                echo '<div style="color:red;">Error: コメントを取得できませんでした。</div>';
                if ( isset($commentData['error']['message']) ) { // APIからのエラーメッセージがある場合
                   echo '<div>Reason: ', htmlspecialchars($commentData['error']['message']), '</div>'; // 例: commentsDisabled
                }
              }
              else if ( empty($commentData['items']) ) {
                echo '<div style="color:red;">Error: コメントがまだありません</div>';
              }
              else {
                echo '<ul>';
                foreach ( $commentData['items'] as $item ) {
                  // ネストが深いため変数に代入して整理
                  $commentSnippet = $item['snippet']['topLevelComment']['snippet'] ?? array();
                      
                  $cauthor = $commentSnippet['authorDisplayName'] ?? '';
                  $ctitle  = $commentSnippet['textDisplay'] ?? '';
                  $clike   = $commentSnippet['likeCount'] ?? '';
                  $cimage  = $commentSnippet['authorProfileImageUrl'] ?? '';
                  $cdate   = date('Y/m/d H:i', strtotime($commentSnippet['publishedAt'] ?? ''));
                  $creply  = $commentSnippet['totalReplyCount'] ?? 0;
              
                  $comment_arr[] = array(
                    'date'    => $cdate,
                    'title'   => $ctitle,
                    'like'    => $clike,
                    'reply'   => $creply,
                    'replies' => array(), // $replies,
                    'user'    => $cauthor,
                    'image'   => $cimage,
                  );

                  echo '<li style="margin-bottom: 15px;">';
                  echo '<strong>', htmlspecialchars($cauthor), '</strong> (', $cdate, ') [いいね: ', $clikes, ']<br>';
                  echo nl2br(htmlspecialchars($ctitle));
                  echo '</li>';
                }
                echo '</ul>';
              }

              $sql = $wpdb->prepare('SELECT no,postid,channelid,videoid,title FROM '.$this->db_table_youtube.' WHERE videoid=%s', $videoId);
              $results = $wpdb->get_results($sql);
              // 旧投稿がある
              if ( count($results) > 0 ) {
                $postid = intval($results[0]->postid);
                // データを更新する
                if ( $postid > 0 ) {
                  $this->ws_update_post( $postid, $channel_id, $channel_name, $videoId, $date, $title, $image, $comment_arr, $caption );
                  // add_settings_error(
                  //   'ws-custom-tool-messages',
                  //   'ws-custom-tool-settings-saved',
                  //   '投稿データを更新しました : PostID = ' . $postid,
                  //   'updated'
                  // );
                  echo '<div style="padding: 1.0em; background: #cff;">投稿データを更新しました : PostID = ', $postid, '</div>';
                }
                if ( $postid > 0 && $results[0]->date.' '.$results[0]->time < date('Y/m/d H:i:s', strtotime($date)) ) {
                  $wpdb->update(
                    $this->db_table_youtube,
                    array(
                      'channelid' => $channel_id,
                      'videoid'   => $videoId,
                      'title'     => $title,
                      'date'      => date('Y/m/d', strtotime($date)),
                      'time'      => date('H:i:s', strtotime($date))
                    ),
                    array(
                     'no' => $postid,
                    ),
                    array(
                      '%s', '%s', '%s', '%s'
                    ),
                    array(
                      '%d',
                    ),
                  );
                }
              }
              // 新規投稿
              else {
                $postid = 0;
                $postid = $this->ws_update_post( 0, $channel_id, $channel_name, $videoId, $date, $title, $image, $comment_arr, $caption );

                // add_settings_error(
                //   'ws-custom-tool-messages',
                //   'ws-custom-tool-settings-saved',
                //   '投稿データを作成しました : PostID = ' . $postid,
                //   'updated'
                // );
                echo '<div style="padding: 1.0em; background: #cff;">投稿データを作成しました : PostID = ', $postid, '</div>';

                $sql = $wpdb->prepare('INSERT INTO ' . $this->db_table_youtube . ' ( postid, channelid, videoid, title, date, time, dead ) VALUES( %d, %s, %s, %s, %s, %s, %d )',
                                      $postid, $channel_id, $videoId, $title, date('Y/m/d', strtotime($date)), date('H:i:s', strtotime($date)), 0 );
                $wpdb->query($sql);
 
                if ( $image != '' ) {
                  $tok = strtok( $image, '?' );
                  if ( $tok !== false ) $image = $tok;
                }
   
              }
            }
          }
        }
      }

      echo '</div>';
    }

    return;
  }


  /**
   * 管理メニュー：広告設定
   */
  function ws_menu_ads_settings() {
    echo '<h2>', $this->ws_plugin_name, 'プラグイン ver ', $this->ws_plugin_version, '</h2>', PHP_EOL;
    echo '<h3>広告設定</h3>', PHP_EOL;

    $ads_code_list = get_option( 'ws_youtube_ads_code_list', array( '', '', '' ) );

    if ( isset($_REQUEST['ws-type']) && $_REQUEST['ws-type'] == 'ads-code' && check_admin_referer('white-software-youtube', '_wpnonce') ) {
      $ads_code_1 = ( !empty($_REQUEST['ads-code-1']) ? wp_unslash($_REQUEST['ads-code-1']) : '' );
      $ads_code_2 = ( !empty($_REQUEST['ads-code-2']) ? wp_unslash($_REQUEST['ads-code-2']) : '' );
      $ads_code_3 = ( !empty($_REQUEST['ads-code-3']) ? wp_unslash($_REQUEST['ads-code-3']) : '' );

      if ( $ads_code_1 == '' && $ads_code_2 == '' && $ads_code_3 == '' ) {
        delete_option( 'ws_youtube_ads_code_list' );
        echo '<div class="ws-save-message">広告のHTMLコードを削除しました</div>', PHP_EOL;
      }
      else {
        $ads_code_list[0] = $ads_code_1;
        $ads_code_list[1] = $ads_code_2;
        $ads_code_list[2] = $ads_code_3;
        update_option( 'ws_youtube_ads_code_list', $ads_code_list );
        echo '<div class="ws-save-message">広告のHTMLコードを保存しました</div>', PHP_EOL;
      }
    }

    echo '<div class="ws-box">';
    echo '<form class="ws-admin-form" action="" method="post">';
    echo wp_nonce_field('white-software-youtube', '_wpnonce', true, false);
    echo '<input type="hidden" name="ws-type" value="ads-code" />';
    echo '<h3>PC用広告HTML(1280px～)</h3>';
    echo '<div><textarea name="ads-code-1" id="ads-code-1" placeholder="広告のHTMLコード" style="max-width: 800px;height:8.0em;">', ( !empty($ads_code_list[0]) ? esc_html($ads_code_list[0]) : '' ), '</textarea></div>';
    echo '<h3>タブレット用広告HTML(768px～1279px)</h3>';
    echo '<div><textarea name="ads-code-2" id="ads-code-2" placeholder="広告のHTMLコード" style="max-width: 800px;height:8.0em;">', ( !empty($ads_code_list[1]) ? esc_html($ads_code_list[1]) : '' ), '</textarea></div>';
    echo '<h3>スマホ用広告HTML(～767px)</h3>';
    echo '<div><textarea name="ads-code-3" id="ads-code-3" placeholder="広告のHTMLコード" style="max-width: 800px;height:8.0em;">', ( !empty($ads_code_list[2]) ? esc_html($ads_code_list[2]) : '' ), '</textarea></div>';
    echo '<div><button type="submit" class="button button-primary" name="submit" value="保存">保存する</button>';
    echo '</form>';
    echo '</div>', PHP_EOL;

    return;
  }


  /**
   * 最新動画取得処理
   */
  function ws_run_scrape($echo) {
    set_time_limit(0);

    global $wpdb;

    $post_attr = get_option( 'ws_youtube_post_attr', array( ) );
    $channel_id_list = get_option( 'ws_youtube_channel_id_list', array() );

    $max_videos   = 20;

    foreach ( $channel_id_list as $channel_info ) {
      list( $channel_id, $channel_name ) = explode(' , ', $channel_info);

      $max_comments = ( isset($post_attr[$channel_id]['maxcomment']) ? $post_attr[$channel_id]['maxcomment'] : 10 );

      // 指定件数ゲットする
      $maxnum = ( isset($post_attr[$channel_id]['maxnum']) && $post_attr[$channel_id]['maxnum'] == 0 ? $max_videos : $post_attr[$channel_id]['maxnum'] );
      $response1 = $this->getYoutubeSearchChannelVideos( $channel_id, $maxnum );
      $json1 = json_decode( $response1 );

      $total = intval($json1->pageInfo->resultsPerPage);

      if ( $echo ) echo '<div>', $total, '件</div>';

      if ( $echo ) echo '<div class="ws-box">';
      if ( $echo ) echo '<table style="margin: 8px; border: 1px solid #cccccc; background-color: #ffffff; max-width: calc( 100% - 16px );">';
      if ( $echo ) echo '<caption style="text-align:left;font-weight:bold;">', $channel_name , ' (', $channel_id, ')</caption>';

      if ( isset($json1->items) && count($json1->items) > 0 ) {
        $items = $json1->items;

        usort( $items, function( $a, $b ) {
          $datea = ( isset($a->snippet->publishedAt) ? date('Y/m/d H:i:s', strtotime($a->snippet->publishedAt)) : '' );
          $dateb = ( isset($b->snippet->publishedAt) ? date('Y/m/d H:i:s', strtotime($b->snippet->publishedAt)) : '' );
          if ( $datea < $dateb ) return -1;
          if ( $datea > $dateb ) return 1;
          return 0;
        });

        foreach ( $items as $item ) {
          if ( isset($item->id) && isset($item->snippet) ) {
            $status  = ( isset($item->snippet->liveBroadcastContent) ? $item->snippet->liveBroadcastContent : '' );
            $videoid = ( isset($item->id->videoId)          ? $item->id->videoId : '' );
            $date    = ( isset($item->snippet->publishedAt) ? $item->snippet->publishedAt : '' );
            $title   = ( isset($item->snippet->title)       ? $item->snippet->title : '' );
            $image   = ( isset($item->snippet->thumbnails->defult->url) ? $item->snippet->thumbnails->default->url : '' );
            $image   = ( isset($item->snippet->thumbnails->medium->url) ? $item->snippet->thumbnails->medium->url : $image );
            $image   = ( isset($item->snippet->thumbnails->high->url)   ? $item->snippet->thumbnails->high->url : $image );

            if ( $status != 'upcoming' && $videoid != '' ) {

              sleep(1);

              $response = $this->getYoutubeVideoComment( $videoid, $max_comments );
              $json_comment = json_decode($response);

              $comments = array();
              if ( isset($json_comment->items) && count($json_comment->items) > 0 ) {
                $comments = $json_comment->items;
                usort( $comments, function( $a, $b ) {
                  $vala = $a->snippet->topLevelComment->snippet->likeCount;
                  $valb = $b->snippet->topLevelComment->snippet->likeCount;
                  if ( $vala < $valb ) return 1;
                  if ( $vala > $valb ) return -1;
                  return 0;
                });
              }

              $response = $this->getYoutubeVideoCaption($videoid);

              $caption = '';
              try {
                $xmldata = @new SimpleXMLElement($response);
                foreach ( $xmldata->text as $text ) {
                  $caption .= (string)$text;
                }
              }
              catch ( Exception $e ) {
              }

              if ( $echo ) echo '<tr>';

              if ( $echo ) echo '<td style="width:120px;text-align:center;margin:0;padding:0;">';
              if ( $echo ) echo '<div>', $videoid, '</div>';
              if ( $echo ) echo '<div><img style="width: 100%; height:auto;" src="', $image, '" /></div>';
              if ( $echo ) echo '<div><iframe style="width:120px;height:70px;" src="https://www.youtube.com/embed/', $videoid, '"></iframe></div>';
              if ( $echo ) echo '</td>';

              if ( $echo ) echo '<td style="width:8em;">', date('Y/m/d H:i:s', strtotime($date)), '</td>';

              if ( $echo ) echo '<td style="width:16em;"><a href="https://youtube.com/watch?v=', $videoid, '" target="_blank">', esc_html($title), '</a></td>';

              $commentar = array();
              if ( $echo ) echo '<td><div>コメント ', count($comments), ' 件</div><ul>';
              for ( $i = 0 ; $i < 20 && $i < count($comments) ; $i++ ) {
                $comment = $comments[$i];
                $cdate  = ( isset($comment->snippet->topLevelComment->snippet->updatedAt) ? $comment->snippet->topLevelComment->snippet->updatedAt : '' );
                $ctitle = ( isset($comment->snippet->topLevelComment->snippet->textDisplay) ? $comment->snippet->topLevelComment->snippet->textDisplay : '' );
                $clike  = ( isset($comment->snippet->topLevelComment->snippet->likeCount) ? intval($comment->snippet->topLevelComment->snippet->likeCount) : 0 );
                $creply = ( isset($comment->snippet->totalReplyCount) ? intval($comment->snippet->totalReplyCount) : 0 );
                $cuser  = ( isset($comment->snippet->topLevelComment->snippet->authorDisplayName) ? $comment->snippet->topLevelComment->snippet->authorDisplayName : '' );
                $cimage = ( isset($comment->snippet->topLevelComment->snippet->authorProfileImageUrl) ? $comment->snippet->topLevelComment->snippet->authorProfileImageUrl : '' );
                if ( $echo ) echo '<li style="margin-left: 1.0em;list-style:disc;">', esc_html($ctitle), '</li>';

                $replies = array();
                if ( $creply > 0 && isset($comment->replies) && isset($comment->replies->comments) ) {
                  $rep_ar = $comment->replies->comments;
                  usort( $rep_ar, function( $a, $b ) {
                    $vala = $a->snippet->likeCount;
                    $valb = $b->snippet->likeCount;
                    if ( $vala < $valb ) return 1;
                    if ( $vala > $valb ) return -1;
                    return 0;
                  });
                  foreach ( $rep_ar as $rep ) {
                    $rdate  = ( isset($rep->snippet->updatedAt) ? $rep->snippet->updatedAt : '' );
                    $rtitle = ( isset($rep->snippet->textDisplay) ? $rep->snippet->textDisplay : '' );
                    $rlike  = ( isset($rep->snippet->likeCount) ? intval($rep->snippet->likeCount) : 0 );
                    $rreply = 0;
                    $ruser  = ( isset($rep->snippet->authorDisplayName) ? $rep->snippet->authorDisplayName : '' );
                    $rimage = ( isset($rep->snippet->authorProfileImageUrl) ? $rep->snippet->authorProfileImageUrl : '' );

                    $replies[] = array(
                      'date'  => $rdate,
                      'title' => $rtitle,
                      'like'  => $rlike,
                      'reply' => $rreply,
                      'user'  => $ruser,
                      'image' => $rimage,
                    );
                  }
                }

                $commentar[] = array(
                  'date'    => $cdate,
                  'title'   => $ctitle,
                  'like'    => $clike,
                  'reply'   => $creply,
                  'replies' => $replies,
                  'user'    => $cuser,
                  'image'   => $cimage,
                );
              }
              if ( $echo ) echo '</ul></td>';
              if ( $echo ) echo '<td>';
              if ( $echo ) echo ( $caption != '' ? esc_html($caption) : '(字幕なし)' );
              if ( $echo ) echo '</td>';
              if ( $echo ) echo '</tr>';

              $sql = $wpdb->prepare('SELECT no,postid,channelid,videoid,title FROM '.$this->db_table_youtube.' WHERE videoid=%s', $videoid);
              $results = $wpdb->get_results($sql);
              // 旧投稿がある
              if ( count($results) > 0 ) {
                $postid = intval($results[0]->postid);
                // データを更新する
                if ( $postid > 0 && isset($post_attr[$channel_id]['updatecomment']) && $post_attr[$channel_id]['updatecomment'] ) {
                  $this->ws_update_post( $postid, $channel_id, $channel_name, $videoid, $date, $title, $image, $commentar, $caption );
                }
                if ( $postid > 0 && $results[0]->date.' '.$results[0]->time < date('Y/m/d H:i:s', strtotime($date)) ) {
                  $wpdb->update(
                    $this->db_table_youtube,
                    array(
                      'channelid' => $channel_id,
                      'videoid'   => $videoid,
                      'title'     => $title,
                      'date' => date('Y/m/d', strtotime($date)),
                      'time' => date('H:i:s', strtotime($date))
                    ),
                    array(
                     'no' => $postid,
                    ),
                    array(
                      '%s', '%s', '%s', '%s'
                    ),
                    array(
                      '%d',
                    ),
                  );
                }
              }
              // 新規投稿
              else {
                $postid = 0;
                if ( $post_attr[$channel_id]['autopost'] ) {
                  $postid = $this->ws_update_post( 0, $channel_id, $channel_name, $videoid, $date, $title, $image, $commentar, $caption );
                }

                $sql = $wpdb->prepare('INSERT INTO ' . $this->db_table_youtube . ' ( postid, channelid, videoid, title, date, time, dead ) VALUES( %d, %s, %s, %s, %s, %s, %d )',
                                      $postid, $channel_id, $videoid, $title, date('Y/m/d', strtotime($date)), date('H:i:s', strtotime($date)), 0 );
                $wpdb->query($sql);
 
                if ( $image != '' ) {
                  $tok = strtok( $image, '?' );
                  if ( $tok !== false ) $image = $tok;
                }
   
              }
            }
          }
        }
      }

      if ( $echo ) echo '</table>';
      if ( $echo ) echo '</div>';
    }

    return;
  }


  /**
   * 投稿への追加
   */
  function ws_update_post( $postid, $channel_id, $channel_name, $videoid, $date, $title, $image, $commentar, $caption ) {
    global $wpdb;

    $plugin_path = plugin_dir_path( __FILE__ );
    $plugin_url  = plugin_dir_url( __FILE__ );

    $post_attr     = get_option( 'ws_youtube_post_attr',     array( ) );
    // $ads_code_list = get_option( 'ws_youtube_ads_code_list', array( ) );

    if ( $title != '' && $channel_id != '' && $videoid != '' ) {
      $contents  = '<section class="ws-youtube">';
      // $contents .= '<h2 class="youtube-title">'.$title.'</h2>';

      // $contents .= '<div class="youtube-video">';
      // $contents .= '<iframe src="https://www.youtube.com/embed/' . $videoid . '"></iframe>';
      // $contents .= '</div>';

      $contents .= '<figure class="wp-block-embed-youtube wp-block-embed is-type-video is-provider-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">';
      $contents .= '<div class="wp-block-embed__wrapper">';
      $contents .= '<iframe width="900" height="506" src="https://www.youtube.com/embed/'.$videoid.'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
      $contents .= '</div></figure>';

      $contents .= '<div class="youtube-date">'.date('Y年n月j日 H:i', strtotime($date)).'</div>';

      if ( !empty($channel_name) ) {
        $contents .= '<div class="youtube-date"><a href="'.esc_url('https://youtube.com/channel/'.$channel_id).'" target="_blank">'.esc_html($channel_name).'</a></div>';
      }

      // 広告表示ショートコード
      $contents .= '<div class="youtube-ads">[ws_youtube_ads]</div>';
      // if ( count($ads_code_list) > 0 && isset($ads_code_list[0]) ) {
      // }

      if ( count($commentar) > 0 ) {
        $max = count($commentar);
        $contents .= '<div class="youtube-comments">';
        for ( $i = 0 ; $i < 10 && $i < $max ; $i++ ) {
          if ( !isset($commentar[$i]) ) break;
          $comment = $commentar[$i];
          $contents .= '<div class="youtube-comment">';
          $contents .= '<div class="youtube-comment-user"><img src="'.$comment['image'].'" /> &nbsp; '.$comment['user'].'</div>';
          $contents .= '<div class="youtube-comment-text">'.$comment['title'].'</div>';
          $contents .= '<div class="youtube-comment-memo">'.date('Y年n月j日 H:i', strtotime($comment['date'])).' &nbsp; いいね'.$comment['like'].'件</div>';
          // $contents .= '<div class="youtube-comment-memo">'.date('Y年n月j日 H:i', strtotime($comment['date'])).' &nbsp; いいね'.$comment['like'].'件 &nbsp; 返信'.$comment['reply'].'件</div>';
          // if ( $comment['reply'] > 0 && count($comment['replies']) > 0 ) {
          //   $contents .= '<div class="accordion-click"><img src="'.$plugin_url.'images/icon-down-black.svg" /> '.count($comment['replies']).'件の返信を表示</div>';
          //   $contents .= '<div class="accordion-body" style="display:none;">';
          //   foreach ( $comment['replies'] as $rep ) {
          //     $contents .= '<div class="youtube-comment">';
          //     $contents .= '<div class="youtube-comment-user"><img src="'.$rep['image'].'" /> &nbsp; '.$rep['user'].'</div>';
          //     $contents .= '<div class="youtube-comment-text">'.$rep['title'].'</div>';
          //     $contents .= '<div class="youtube-comment-memo">'.date('Y年n月j日 H:i', strtotime($rep['date'])).' &nbsp; いいね'.$rep['like'].'件</div>';
          //     $contents .= '</div>';
          //  }
          //   $contents .= '</div>';
          // }
          $contents .= '</div>';
        }
        $contents .= '</div>';
        if ( $max > 10 ) {
          $contents .= '<div class="accordion-click read-more">もっと見る &nbsp; <img src="'.$plugin_url.'images/icon-down-black.svg" /></div>';
          $contents .= '<div class="accordion-body" style="display:none;">';
          for ( $i = 10 ; $i < 20 && $i < $max ; $i++ ) {
            if ( !isset($commentar[$i]) ) break;
            $comment = $commentar[$i];
            $contents .= '<div class="youtube-comment">';
            $contents .= '<div class="youtube-comment-user"><img src="'.$comment['image'].'" /> &nbsp; '.$comment['user'].'</div>';
            $contents .= '<div class="youtube-comment-text">'.$comment['title'].'</div>';
            $contents .= '<div class="youtube-comment-memo">'.date('Y年n月j日 H:i', strtotime($comment['date'])).' &nbsp; いいね'.$comment['like'].'件 &nbsp; 返信'.$comment['reply'].'件</div>';
            if ( $comment['reply'] > 0 && count($comment['replies']) > 0 ) {
              $contents .= '<div class="accordion-click"><img src="'.$plugin_url.'images/icon-down-black.svg" /> '.count($comment['replies']).'件の返信を表示</div>';
              $contents .= '<div class="accordion-body" style="display:none;">';
              foreach ( $comment['replies'] as $rep ) {
                $contents .= '<div class="youtube-comment">';
                $contents .= '<div class="youtube-comment-user"><img src="'.$rep['image'].'" /> &nbsp; '.$rep['user'].'</div>';
                $contents .= '<div class="youtube-comment-text">'.$rep['title'].'</div>';
                $contents .= '<div class="youtube-comment-memo">'.date('Y年n月j日 H:i', strtotime($rep['date'])).' &nbsp; いいね'.$rep['like'].'件</div>';
                $contents .= '</div>';
              }
              $contents .= '</div>';
            }
            $contents .= '</div>';
          }
          $contents .= '</div>';
        }
      }

      if ( $caption != '' ) {
        $contents .= '<div class="youtube-caption">';
        $contents .= $caption;
        $contents .= '</div>';
      }
      $contents .= '</section>' . PHP_EOL;

      // $fp = fopen( './'.wp_date('Ymd-His').'txt', 'w' );
      // fwrite( $fp, $contents );
      // fclose($fp);

      $post = array(
        'post_title'    => $title,
        'post_content'  => $contents,
      );

      // カテゴリ
      if ( !empty($post_attr[$channel_id] ) ) {
        if ( $post_attr[$channel_id]['post_type'] == 'post' ) {
          if ( $post_attr[$channel_id]['category'] != '' ) {
            list( $taxslug, $termslug ) = explode(',', $post_attr[$channel_id]['category']);
            $cat = get_category_by_slug( $termslug );
            if ( $cat ) {
              $cat_id = $cat->cat_ID;
              $post['post_category'] = array( $cat_id );
            }
          }
        }
        // カスタムタクソノミーは後で設定
        else if ( $post_attr[$channel_id]['post_type'] != '' ) {
          if ( $post_attr[$channel_id]['category'] != '' ) {
          }
        }
      }

      // 保存前に一旦サニタイズをオフに
      remove_filter('content_save_pre', 'wp_filter_post_kses');
      remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
 
      // 投稿を更新
      if ( $postid > 0 ) {
        $post['ID']                = $postid;
        $post['post_modified']     = wp_date('Y-m-d H:i:s');
        $post['post_modified_gmt'] = get_gmt_from_date(wp_date('Y-m-d H:i:s'));

        $ret = wp_update_post( $post, true );
        // if ( is_wp_error( $ret ) ) {
        //   $errors = $ret->get_error_messages();
        //   foreach ( $errors as $error ) {
        //     echo '<div>Error : ', $error, '</div>';
        //   }
        // }
      }

      // 投稿を作成
      else {
        $post['post_date']     = wp_date('Y-m-d H:i:s');
        $post['post_date_gmt'] = get_gmt_from_date(wp_date('Y-m-d H:i:s'));
        $post['post_author']   = ( isset($post_attr[$channel_id]['userid']) ? $post_attr[$channel_id]['userid'] : 0 );
        $post['post_status']   = ( isset($post_attr[$channel_id]['post_status']) ? $post_attr[$channel_id]['post_status'] : 'draft' );
        $post['post_type']     = ( isset($post_attr[$channel_id]['post_type']) ? $post_attr[$channel_id]['post_type'] : 'post' );

        $postid = wp_insert_post( $post );
 
        if ( $postid > 0 ) {
          // コンテンツの設定
          // $post = array( 'ID' => $postid, 'post_content' => $contents );
          // wp_update_post( $post );

          // $wpdb->prepare( 'UPDATE '.$wpdb->prefix.'posts SET post_content=%s WHERE ID=%d', $contents, $postid );
          // $wpdb->query($sql);

          // $wpdb->update( $wpdb->prefix.'posts', array( 'post_content' => $contents ), array( 'ID' => $postid ), array( '%s' ), array( '%d' ) );

          // アイキャッチ画像の設定
          if (  $image != '' ) {
            $this->ws_add_thumbnail($postid, $image);
          }
          // カスタムタクソノミーの設定：この方法であればユーザー権限を気にしない
          if ( $post_attr[$channel_id]['post_type'] != 'post' ) {
            if ( $post_attr[$channel_id]['category'] != '' ) {
              list( $taxslug, $termslug ) = explode(',', $post_attr[$channel_id]['category']);
              wp_set_object_terms( $postid, $termslug, $taxslug );
            }
          }
        }
      }

      // セキュリティの都合上保存が終わったらサニタイズをすぐに戻す
      add_filter('content_save_pre', 'wp_filter_post_kses');
      add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    }

    return $postid;
  }


  /**
   * 画像URLをpost_idのアイキャッチに登録する
   */
  function ws_add_thumbnail( $postid, $image ) {
 
    // アップロードディレクトリ取得
    $wp_upload_dir = wp_upload_dir();
 
    // ファイル名取得
    $filename = basename( $image );
    $tok = strtok($filename, '?');
    if ( $tok !== false ) $filename = $tok;
 
    // ダウンロード後ファイルパス
    $filename = $wp_upload_dir['path'] . '/' . $postid . '-' . $filename;
 
    // 画像をダウンロード＆保存
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $image);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    $imagedata = curl_exec($curl);
    curl_close($curl);

    if ( $imagedata ) {
      // $fp = fopen( __DIR__ . '/' . basename($filename), 'w');
      // fwrite($fp, $imagedata);
      // fclose($fp);

      $fp = fopen($filename, 'w');
      fwrite($fp, $imagedata);
      fclose($fp);
 
      // ファイル属性取得
      $wp_filetype = wp_check_filetype($filename, null );
 
      // 添付ファイル情報設定
      $attachment = array(
        'guid'           => $wp_upload_dir['url'] . '/' . basename($filename),
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => $filename,
        'post_content'   => '',
        'post_status'    => 'inherit'
      );
 
      // 添付ファイル登録
      $attach_id = wp_insert_attachment( $attachment, $filename, $postid );
 
      // サムネイル画像作成
      require_once( ABSPATH . 'wp-admin/includes/image.php' );
      $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
      wp_update_attachment_metadata( $attach_id, $attach_data );
 
      // サムネイルID登録
      set_post_thumbnail( $postid, $attach_id );
    }
 
    return;
  }


  /**
   * 広告表示ショートコード
   */
  function ws_shortcode_youtube_ads($attr, $content, $tag) {
    $html = '';

    $attrval = shortcode_atts( array(
      'no' => 0,
    ), $attr);

    $ads_code_list = get_option( 'ws_youtube_ads_code_list', array() );

    if ( count($ads_code_list) > 0 ) {
      if ( isset($ads_code_list[0]) ) {
        $html .= '<nav class="youtube-ads-pc">';
        $html .= $ads_code_list[0];
        $html .= '</nav>';
      }
      if ( isset($ads_code_list[1]) ) {
        $html .= '<nav class="youtube-ads-tb">';
        $html .= $ads_code_list[1];
        $html .= '</nav>';
      }
      if ( isset($ads_code_list[2]) ) {
        $html .= '<nav class="youtube-ads-sp">';
        $html .= $ads_code_list[2];
        $html .= '</nav>';
      }
    }

    return $html;
  }


  /**
   * チャネル情報の取得
   */
  function getYoutubeChannelInfo( $id ) {

    $target = 'https://www.googleapis.com/youtube/v3/channels';

    $apikey = get_option( 'ws_youtube_api_key', '' );

    $params = array(
      // 'part'       => 'id,snippet',
      'part'       => 'id,snippet,brandingSettings,contentDetails,statistics,topicDetails',
      'id'         => $id,
      'maxResults' => 100,
      // 'key'        => $this->google_api_key
      'key'        => $apikey
    );

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $url = $target . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }


  /**
   * チャネルセクション？の取得
   */
  function getYoutubeChannelSections( $id ) {

    $target = 'https://www.googleapis.com/youtube/v3/channelSections';

    $apikey = get_option( 'ws_youtube_api_key', '' );

    $params = array(
      'part'       => 'id,snippet',
      'channelId'  => $id,
      'maxResults' => 100,
      // 'key'        => $this->google_api_key
      'key'        => $apikey
    );

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $url = $target . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }


  /**
   * リスト取得：チャネル
   */
  function getYoutubeSearchChannelVideos( $id, $maxnum = 50, $pageToken = '' ) {

    $target = 'https://www.googleapis.com/youtube/v3/search';

    $apikey = get_option( 'ws_youtube_api_key', '' );

    $params = array(
      'part'       => 'id,snippet',
      'channelId'  => $id,
      'maxResults' => $maxnum,
      'order'      => 'date',
      'type'       => 'video',
      // 'key'        => $this->google_api_key
      'key'        => $apikey
    );

    if ( $pageToken != '' ) {
      $params['pageToken'] = $pageToken;
    }

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $url = $target . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }


  /**
   * リスト取得：キーワード
   */
  function getYoutubeSearchKeywordVideos( $word, $pageToken = '' ) {

    $target = 'https://www.googleapis.com/youtube/v3/search';

    $apikey = get_option( 'ws_youtube_api_key', '' );

    $params = array(
      'part'       => 'id,snippet',
      'q'          => $word,
      'maxResults' => 100,
      'order'      => 'date',
      // 'key'        => $this->google_api_key
      'key'        => $apikey
    );
  
    if ( $pageToken != '' ) {
      $params['pageToken'] = $pageToken;
    }

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $url = $target . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }


  /**
   * チャンネル情報取得
   */
  function getYoutubeSearchChannelID( $word, $pageToken = '' ) {

    $target = 'https://www.googleapis.com/youtube/v3/search';

    $apikey = get_option( 'ws_youtube_api_key', '' );

    $params = array(
      'part'       => 'id,snippet',
      'q'          => $word,
      'maxResults' => 50,
      // 'order'      => 'date',
      'type'       => 'channel',
      // 'key'        => $this->google_api_key
      'key'        => $apikey
    );

    if ( $pageToken != '' ) {
      $params['pageToken'] = $pageToken;
    }

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $url = $target . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }


  /**
   * リスト取得：動画情報
   */
  function getYoutubeVideoInfo( $id, $pageToken = '' ) {

    $target = 'https://www.googleapis.com/youtube/v3/videos';

    $apikey = get_option( 'ws_youtube_api_key', '' );

    $params = array(
      'part'       => 'id,snippet,contentDetails,statistics',
      'id'         => $id,
      // 'key'        => $this->google_api_key
      'key'        => $apikey
    );

    if ( $pageToken != '' ) {
      $params['pageToken'] = $pageToken;
    }

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $url = $target . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }


  /**
   * コメント一覧
   */
  function getYoutubeVideoComment( $id, $maxnum = 100, $pageToken = '' ) {

    $target = 'https://www.googleapis.com/youtube/v3/commentThreads';

    $apikey = get_option( 'ws_youtube_api_key', '' );

    $params = array(
      'part'       => 'id,snippet,replies',
      'videoId'    => $id,
      'maxResults' => $maxnum,
      // 'key'        => $this->google_api_key
      'key'        => $apikey
    );

    if ( $pageToken != '' ) {
      $params['pageToken'] = $pageToken;
    }

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $url = $target . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }


  /**
   * コメント取得
   */
  function getYoutubeComment( $id, $pageToken = '' ) {

    $target = 'https://www.googleapis.com/youtube/v3/comments';

    $apikey = get_option( 'ws_youtube_api_key', '' );

    $params = array(
      'part'       => 'id,snippet',
      'id'         => $id,
      'maxResults' => 10,
      'order'      => 'date',
      // 'key'        => $this->google_api_key
      'key'        => $apikey
    );

    if ( $pageToken != '' ) {
      $params['pageToken'] = $pageToken;
    }

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $url = $target . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }


  /**
   * リスト取得：キャプション
   */
  function getYoutubeVideoCaption( $id, $pageToken = '' ) {

    $url = 'https://www.youtube.com/api/timedtext?lang=ja&name=&v='.$id;

    $agent   = $this->ws_user_agent;
    $referer = home_url();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_REFERER, $referer);

    $response = curl_exec($ch);

    return $response;

    // $target = 'https://www.googleapis.com/youtube/v3/captions';

    // $params = array(
    //   'part'       => 'id,snippet',
    //   'videoId'    => $id,
    //   'maxResults' => 10,
    //   'order'      => 'date',
    //   'key'        => $this->google_api_key
    // );

    // if ( $pageToken != '' ) {
    //   $params['pageToken'] = $pageToken;
    // }

    // $agent   = $this->ws_user_agent;
    // $referer = home_url();

    // $url = $target . '?' . http_build_query($params);

    // $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $url);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    // curl_setopt($ch, CURLOPT_REFERER, $referer);

    // $response = curl_exec($ch);

    // curl_close($ch);

    // return $response;
  }


  /**
   * キャプション取得
   */
  function getYoutubeCaption( $id, $pageToken = '' ) {

//    $target = 'https://www.googleapis.com/youtube/v3/captions';
//
//    $params = array(
//  //    'part'       => 'id,snippet',
//  //    'videoId'    => $id,
//  //    'maxResults' => 10,
//  //    'order'      => 'date',
//  //    'key'        => $this->google_api_key
//    );
//
//    if ( $pageToken != '' ) {
//      $params['pageToken'] = $pageToken;
//    }
//
//    $agent   = $this->ws_user_agent;
//    $referer = home_url();
//
//    // $url = $target . '?' . http_build_query($params);
//    $url = $target . '/' . $id;
//
//    $ch = curl_init();
//    curl_setopt($ch, CURLOPT_URL, $url);
//    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
//    curl_setopt($ch, CURLOPT_REFERER, $referer);
//
//    $response = curl_exec($ch);
//
//    curl_close($ch);
//
//    return $response;
  }

} // class WsYoutube

?>