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


/**
 * FrontLoginクラス : 管理画面用
 *
 * @class WsFrontLoginAdmin
 */
class WsFrontLoginAdmin extends WsFrontLogin {

  /**
   * コンストラクタ
   */
  public function __construct( $base = __FILE__ ) {
    parent::__construct($base);

    global $wpdb;
    
    // 管理画面メニューにメニューを追加
    add_action( 'admin_menu', array( $this, 'ws_admin_menu' ) );

    // プラグイン一覧に設定メニューへのリンクを追加
    add_filter( 'plugin_action_links', array( $this, 'ws_add_action_link_to_plugin_list' ), 10, 2 );

    // カスタム投稿タイプを追加
    // add_action( 'init', array( $this, 'ws_register_post_types' ) );
    // add_action( 'init', array( $this, 'ws_post_type_rewrite_rule' ) );
    // add_filter( 'post_type_link', array( $this, 'ws_post_type_permalink' ), 1, 3 );
    // add_filter( 'term_link',      array( $this, 'ws_custom_term_permalink' ), 10, 3);

    // クエリ
    // add_filter( 'query_vars', array( $this, 'ws_add_query_vars_filter' ) );
    // add_action( 'pre_get_posts', array( $this, 'ws_custom_query_vars' ) );

    // テスト
    // add_filter( 'the_content', array( $this, 'ws_the_content' ) );

    // cssやJavascriptファイルを読み込ませる
    add_action('wp_enqueue_scripts', array( $this, 'ws_load_common_files' ) );
    add_action('admin_enqueue_scripts', array( $this, 'ws_load_admin_files' ) );

    // カスタム投稿タイプ編集ページにメタ情報追加
    // add_action( 'add_meta_boxes', array( $this, 'ws_post_meta_box_add' ), 10, 2 );

    // カスタム投稿タイプ編集ページにメタ情報保存
    // add_action( 'save_post', array( $this, 'ws_post_meta_box_save' ), 10, 2 );

    // アーカイブ対策
    // add_action( 'pre_get_posts', array( $this, 'ws_set_posts_per_page' ) );

    // ユーザー一覧ページにカラムを追加
    // add_action( 'manage_users_columns', array( $this, 'ws_userlist_add_columns' ) );
    // add_action( 'manage_users_custom_column', array( $this, 'ws_userlist_set_colvals' ), 10, 3 );

    // ユーザープロフィールページに入力フォームを追加
    // add_action( 'show_user_profile', array( $this, 'ws_user_meta_box_add' ) );
    // add_action( 'edit_user_profile', array( $this, 'ws_user_meta_box_add' ) );

    // ユーザープロフィールページの入力フォームを保存
    // add_action( 'personal_options_update', array( $this, 'ws_user_meta_box_save' ) );
    // add_action( 'edit_user_profile_update', array( $this, 'ws_user_meta_box_save' ) );

    // ページを開く前にログイン状態をチェックして未ログインならログインページに飛ばす
    add_action( 'wp', array( $this, 'wsFrontLoginCheckLoggedIn' ), 1 );

    // ぱんぴーが管理画面へアクセスするのを阻止
    add_action( 'admin_init', array( $this, 'wsFrontLoginRejectAdminPage' ), 1 );

    return;
  }


  /**
   * デストラクタ
   */
  public function __destruct() {
    return;
  }



  /**
   * 管理画面メニューにメニューを追加
   */
  function ws_admin_menu() {

    // 表示する権限
    $capability = 'manage_options'; // 管理者以上

    // add_options_page( __($this->ws_plugin_name, 'ws-clinic'),
    //                   __($this->ws_plugin_name, 'ws-clinic'),
    //                   $capability,
    //                   'ws-clinic-menu1',
    //                   array( $this, 'ws_clinic_menu_page1' )
    //                   );

    // 管理画面メニューのメインメニュー
    add_menu_page( __('Front Login', 'ws-front-login'),
                   __('Front Login', 'ws-front-login'),
                   $capability,
                   'ws-front-login-menu1',
                   array( $this, 'ws_front_login_menu_page1' ),
                   $this->ws_plugin_url.'/images/icon-login.svg', // 'dashicons-database'
                   );

    // 管理画面メニューのサブメニュー
    add_submenu_page( 'ws-front-login-menu1',
                      __('Create Pages', 'ws-front-login'), // 固定ページ作成
                      __('Create Pages', 'ws-front-login'), // 固定ページ作成
                      $capability,
                      'ws-front-login-menu1',
                      array( $this, 'ws_front_login_menu_page1') );
    add_submenu_page( 'ws-front-login-menu1',
                      __('For Admin', 'ws-front-login'), // 管理用
                      __('For Admin', 'ws-front-login'), // 管理用
                      $capability,
                      'ws-front-login-menu99',
                      array( $this, 'ws_front_login_menu_page99') );

    return;
  }


  /**
   *  メニュー画面1
   */
  function ws_front_login_menu_page1() {

    $options = get_option( 'ws-front-login-settings', array() );

    echo '<section class="white-software">';

    echo '<h1>', __('Front Login', 'ws-front-login'), '</h1>', PHP_EOL;
    echo '<hr/>';


    //// ログインページの設定
    echo '<h2>', __('Select a page for login', 'ws-front-login'), '</h2>', PHP_EOL;

    if ( !empty($_REQUEST['ws-type']) &&
         $_REQUEST['ws-type'] == 'ws-front-login-set-login-page' &&
         check_admin_referer( 'ws-front-login' ) ) {
      // echo '<pre>';
      // print_r($_REQUEST);
      // echo '</pre>';
      $options = array(
         'page-id' => $_REQUEST['ws-login-page-id'],
       );
      update_option( 'ws-front-login-settings', $options );
      echo '<div style="margin: 1.0rem 0; padding: 1.0rem; background: #cff;">', __('Saved', 'ws-front-login'), '</div>';
    }

    $login_page_id = $options['page-id'] ?? 0;

    $args = array(
      'post_type'      => 'page',
      'post_status'    => 'publish',
      'posts_per_page' => -1,
    );

    $pages = get_posts( $args );

    echo '<form action="" method="post">';
    echo wp_nonce_field('ws-front-login', '_wpnonce', true, false);
    echo '<input type="hidden" name="ws-type" value="ws-front-login-set-login-page" />';
    echo '<select name="ws-login-page-id">';
    echo '<option value="">指定なし（機能無効化）</option>';
    foreach ( $pages as $p ) {
      if ( $p->ID == $login_page_id ) {
        echo '<option value="', $p->ID, '" selected>', esc_html($p->post_title), '(', esc_html($p->post_name), ')</option>';
      }
      else {
        echo '<option value="', $p->ID, '">', esc_html($p->post_title), '(', esc_html($p->post_name), ')</option>';
      }
    }
    echo '</select>';
    echo '<button type="submit" class="button-primary button">', esc_html__('Save', 'ws-front-login'), '</button>';
    echo '</form>';

    echo '<hr/>';


    //// 固定ページの作成
    echo '<h2>', __('Create Pages', 'ws-front-login'), '</h2>', PHP_EOL;

    $pages = array(
      'ws-login' => array(
        'parent'  => '',
        'title'   => 'ログイン',
        'info'    => 'ログインページ',
        'content' => '',
        'files'   => array(
          'page-ws-login.php',
          'header-ws-login.php',
          'footer-ws-login.php',
        ),
      ),
    );
    
    if ( !empty($_REQUEST['ws-type']) &&
         $_REQUEST['ws-type'] == 'ws-front-login-create-pages' &&
         check_admin_referer( 'ws-front-login' )
        ) {
      // 必要だけど存在しないページを作成する
      if ( $_REQUEST['ws-submit'] == __('Create a page that is needed but does not exist', 'ws-front-login') ) { // '必要だけど存在しないページを作成する'
        $names = array();
        foreach ( $pages as $name => $arr ) {
          $path = ( !empty($arr['parent']) ? $arr['parent'].'/' : '' ) . $name;
          $post = get_page_by_path( $path );
          if ( empty($post) ) {
            $parent_id = 0;
            if ( !empty($arr['parent']) ) {
              $parent = get_page_by_path( $arr['parent'] );
              $parent_id = ( !empty($parent) ? $parent->ID : 0 );
            }
            $args = array(
              'post_name'    => $name,
              'post_type'    => 'page',
              'post_title'   => $arr['title'] ?? $name,
              'post_content' => $arr['content'] ?? '',
              'post_parent'  => $parent_id,
              'post_status'  => 'publish',
            );
            $postid = wp_insert_post($args, true);
            if ( !empty($postid) ) {
              $names[] = $name;
              // add_post_meta( $post_id, '_thumbnail_id' ,416, true);
              if ( !empty($arr['files']) && is_array($arr['files']) ) {
                foreach ( $arr['files'] as $file ) {
                  $src_file = $this->ws_plugin_path . 'themes/' . $file;
                  if ( !file_exists($src_file) ) continue;
                  $dst_file = get_theme_file_path() . '/' . $file;
                  copy( $src_file, $dst_file );
                }
              }
            }
          }
        }
        if ( !empty($names) ) {
          echo '<div class="ws-save-message">', esc_html__('Created', 'ws-front-login'), ' : ', esc_html( implode(', ', $names) ), '</div>';
        }
      }
      // すべてのページを再生成する
      else if ( $_REQUEST['ws-submit'] == esc_attr__('Recreate all pages', 'ws-front-login') ) { // 'すべてのページを再生成する'
        foreach ( $pages as $name => $arr ) {
          $path = ( !empty($arr['parent']) ? $arr['parent'].'/' : '' ) . $name;
          $post = get_page_by_path( $path );
          if ( empty($post) ) {
            $parent_id = 0;
            if ( !empty($arr['parent']) ) {
              $parent = get_page_by_path( $arr['parent'] );
              $parent_id = ( !empty($parent) ? $parent->ID : 0 );
            }
            $args = array(
              'post_name'    => $name,
              'post_type'    => 'page',
              'post_title'   => $arr['title'] ?? $name,
              'post_content' => $arr['content'] ?? '',
              'post_parent'  => $parent_id,
              'post_status'  => 'publish',
            );
            $post_id = wp_insert_post($args);
            if ( !empty($post_id) ) {
              // add_post_meta( $post_id, '_thumbnail_id' ,416, true);
              if ( !empty($arr['files']) && is_array($arr['files']) ) {
                foreach ( $arr['files'] as $file ) {
                  $src_file = $this->ws_plugin_path . 'themes/' . $file;
                  if ( !file_exists($src_file) ) continue;
                  $dst_file = get_theme_file_path() . '/' . $file;
                  copy( $src_file, $dst_file );
                }
              }
            }
          }
          else {
            $parent_id = 0;
            if ( !empty($arr['parent']) ) {
              $parent = get_page_by_path( $arr['parent'] );
              $parent_id = ( !empty($parent) ? $parent->ID : 0 );
            }
            $args = array(
              'ID' => $post->ID,
              'post_name'    => $name,
              'post_type'    => 'page',
              'post_title'   => $arr['title'] ?? $name,
              'post_content' => $arr['content'] ?? '',
              'post_parent'  => $parent_id,
              'post_status'  => 'publish',
            );
            wp_update_post( $args );
            // add_post_meta( $post->ID, '_thumbnail_id' ,416, true);
            if ( !empty($arr['files']) && is_array($arr['files']) ) {
              foreach ( $arr['files'] as $file ) {
                $src_file = $this->ws_plugin_path . 'themes/' . $file;
                if ( !file_exists($src_file) ) continue;
                $dst_file = get_theme_file_path() . '/' . $file;
                copy( $src_file, $dst_file );
              }
            }
          }
        }
        echo '<div class="ws-save-message">', esc_html__('Created', 'ws-front-login'), '</div>';
      }
    }

    // echo '<pre>';
    // echo get_theme_file_uri();
    // echo '</pre>';
    // echo '<pre>';
    // echo get_theme_file_path();
    // echo '</pre>';

    echo '<section class="ws-front-login-admin-box">';
    echo '<table class="ws-list">';
    echo '<thead>';
    echo '<th>Slug</th>';
    echo '<th>Post ID</th>';
    echo '<th>Post Title</th>';
    echo '<th>Description</th>';
    echo '</thead>';
    echo '<tbody>';
    // 固定ページをチェックしてありなし判定
    foreach ( $pages as $name => $arr ) {
      $path = ( !empty($arr['parent']) ? $arr['parent'].'/' : '' ) . $name;
      $post = get_page_by_path( $path );
      echo '<tr>';
      if ( empty($post) ) {
        echo '<td>', $name, '</td>';
        echo '<td class="center">なし</td>';
      }
      else {
        echo '<td><a href="', get_permalink($post->ID), '" target="_blank">', $name, '</a></td>';
        echo '<td class="center">', $post->ID, '</td>';
      }
      echo '<td>', $arr['title'], '</td>';
      echo '<td>', $arr['info'], '</td>';
      echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>', PHP_EOL;

    echo '<form action="" method="post">';
    echo wp_nonce_field( 'ws-front-login', '_wpnonce', true, false );
    echo '<input type="hidden" name="ws-type" value="ws-front-login-create-pages" />';
    echo '<p><input type="submit" name="ws-submit" class="button button-primary" value="', esc_attr__('Create a page that is needed but does not exist', 'ws-front-login'), '" /></p>';
    echo '<p><input type="submit" name="ws-submit" class="button button-primary" value="', esc_attr__('Recreate all pages', 'ws-front-login'), '" /></p>';
    echo '</form>', PHP_EOL;

    echo '</section>';

    echo '</section>', PHP_EOL; // class="white-software"

    return;
  }


  /**
   *  メニュー画面99
   */
  function ws_front_login_menu_page99() {
    global $wpdb;

    echo '<section class="white-software">';

    echo '<h1>', __($this->ws_plugin_name, 'ws-front-login'), '</h1>', PHP_EOL;
    echo '<h2>', __('For Admin', 'ws-front-login'), '</h2>', PHP_EOL;

    echo '</section>', PHP_EOL; // class="white-software"

    return;
  }


  /**
   * プラグイン一覧に設定メニューへのリンクを追加
   */
  function ws_add_action_link_to_plugin_list( $links, $file ) {
    // $settings_link = '<a href="' . admin_url('options-general.php?page=ws-front-login-menu1') . '">' . esc_html__('Settings', 'ws-front-login') . '</a>';
    $settings_link = '<a href="' . admin_url('admin.php?page=ws-front-login-menu1') . '">' . esc_html__('Settings', 'ws-front-login') . '</a>';
    if ( $file == 'ws-front-login/ws-front-login.php' ) {
       array_unshift( $links, $settings_link );
    }
    return $links;
  }


  /**
   * CSSファイルやJavascriptファイルを読み込ませる
   */
  function ws_load_common_files( $hook ) {

    // スタイルシートの読み込み
    $css = $this->ws_plugin_path.'css/common.css';
    if ( file_exists($css) ) {
      wp_enqueue_style( 'ws-front-login-common-css',
                        $this->ws_plugin_url.'css/common.css',
                        array(),
                        filemtime($css)
                       );
    } 

    // JavaScript の読み込み
    $js = $this->ws_plugin_path.'js/common.js';
    if ( file_exists($js) ) {
      wp_enqueue_script( 'ws-front-login-common-js',
                         $this->ws_plugin_url.'js/common.js',
                         array('jquery'),
                         filemtime($js)
                       );
    }

    return;
  }


  /**
   * 管理画面にてCSSファイルやJavascriptファイルを読み込ませる
   */
  function ws_load_admin_files( $hook ) {

    // スタイルシートの読み込み
    $css = $this->ws_plugin_path.'css/admin.css';
    if ( file_exists($css) ) {
      wp_enqueue_style( 'ws-front-login-admin-css',
                        $this->ws_plugin_url.'css/admin.css',
                        array(),
                        filemtime($css)
                       );
    } 

    // JavaScript の読み込み
    $js = $this->ws_plugin_path.'js/admin.js';
    if ( file_exists($js) ) {
       wp_enqueue_script( 'ws-front-login-admin-js',
                          $this->ws_plugin_url.'js/admin.js',
                          array('jquery'),
                          filemtime($js)
                        );
    }

    return;
  }


  /**
   * ページを開く前にログイン状態をチェックして未ログインならログインページに飛ばす
   */
  function wsFrontLoginCheckLoggedIn() {
    $options = get_option( 'ws-front-login-settings', array() );
    $login_page_id = intval( $options['page-id'] ?? 0 );

    if ( $login_page_id == 0 ) {
      return;
    }

    $login_post = get_post( $login_page_id );
    if ( $login_post === false ) {
      return;
    }

    if ( is_page( $login_post->post_name ) ) {
      return;
    }

    if (
         !is_user_logged_in() &&
         !preg_match( '/^(wp-login\.php|async-upload\.php)/', basename( $_SERVER['REQUEST_URI'] ) ) &&
         !( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
         !( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
      if ( $login_page_id > 0 ) {
        if ( !empty($_SERVER['REQUEST_URI']) ) {
          wp_safe_redirect( get_permalink( $login_page_id ) . '?redirect=' . rawurlencode($_SERVER['REQUEST_URI']) );
        }
        else {
          wp_safe_redirect( get_permalink( $login_page_id ) );
        }
        exit;
      }
    }

    return;
  }
   

  /**
   * ぱんぴーが管理画面へアクセスするのを阻止
   */
  function wsFrontLoginRejectAdminPage() {
    if ( !current_user_can('administrator' ) &&
         !current_user_can('editor' ) &&
         !current_user_can('author' ) &&
         !( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
         !( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
      wp_safe_redirect( home_url(), 301 );
      exit;
    }
  }



} // class WsFrontLoginAdmin


?>