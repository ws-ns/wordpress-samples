<?php

////////////////////////////////////////////////////////////
//      記事公開時に通知を送信
////////////////////////////////////////////////////////////
class WsNotifyPost {

  public $ws_plugin_name    = 'ws-notify-post';
  public $ws_plugin_version = '0.0.2';

  public $ws_plugin_path = '';
  public $ws_plugin_url = '';


  //// コンストラクタ
  public function __construct() {

    $this->ws_plugin_path = plugin_dir_path( __FILE__ );
    $this->ws_plugin_url  = plugin_dir_url( __FILE__ );

    // 管理メニューに追加
    add_action( 'admin_menu', array( $this, 'ws_admin_menu' ) );

    // cssやJavascriptファイルを読み込ませる
    add_action( 'wp_enqueue_scripts', array( $this, 'ws_load_files' ) );

    // プラグイン一覧に設定へのリンクを追加
    add_filter( 'plugin_action_links', array( $this, 'ws_add_action_link_to_plugin_list' ), 10, 2 );

    // 記事公開時に通知
    add_action( 'transition_post_status', array( $this, 'ws_transition_post_status'), 10, 3 );

    // 記事更新時に通知
    add_action( 'save_post', array( $this, 'ws_save_post'), 10, 3 );

    // フォーム処理
    add_action( 'admin_init', array( $this, 'ws_admin_init_proc_form' ) );

  }


  ////////// 管理画面メニュー
  function ws_admin_menu() {

    // 誰がメニューを操作できるか
    $capability = 'manage_options'; // 管理者だけ
    // $capability = 'edit_posts'; // 寄稿者だけ

    add_options_page( __('Notification Settings', 'ws-notify-post'),
                      __('Notification Settings', 'ws-notify-post'),
                      $capability,
                      'ws-notify-post-menu',
                      array( $this, 'ws_admin_menu_page1' ),
                      );

    return;
  }


  ////////// フォーム処理
  function ws_admin_init_proc_form() {

    // 権限チェック
    if ( ! current_user_can( 'manage_options' ) ) {
      // 権限がない場合は処理を中断
      return;
    }

    // Nonceチェック
    if ( !isset( $_POST['ws-notify-post-nonce'] ) ||! wp_verify_nonce( $_POST['ws-notify-post-nonce'], 'ws-notify-post-action' ) ) {
      // Nonceが無効な場合は処理を中断
      return;
    }

    // フォームが送信されたか
    if ( !empty($_REQUEST['ws-type']) &&
         $_REQUEST['ws-type'] == 'ws-notify-post' ) {

      $options = get_option( 'ws-notify-post-settings', array() );
      $arr = array(
        'ws-notify-publish' => __('Send notification when an article has been published', 'ws-notify-post'),
        'ws-notify-future'  => __('Send notification when an article has been reserved for publication', 'ws-notify-post'),
        'ws-notify-save'    => __('Send notification when an article has been saved', 'ws-notify-post'),
      );

      foreach ( $arr as $key => $val ) {
        $options[$key] = ( !empty($_REQUEST[$key]) ? true : false );
      }
      $options['ws-notify-mails'] = $_REQUEST['ws-notify-mails'] ?? '';
      update_option( 'ws-notify-post-settings', $options );

      // echo '<div style="margin: 1.0rem 0; padding: 1.0rem; background: #cff;">', __('Saved', 'ws-notify-post'), '</div>';
      add_settings_error(
        'ws-notify-post-messages',
        'ws-notify-post-saved',
        '保存しました ',
        'updated'
      );
    }

    return;
  }


  ////////// 管理画面
  function ws_admin_menu_page1() {

    echo '<h1>', __('Notification Settings', 'ws-notify-post'), '</h1>', PHP_EOL;

    // settings_errors( 'ws-notify-post-messages' );

    $options = get_option( 'ws-notify-post-settings', array() );

    $arr = array(
      'ws-notify-publish' => __('Send notification when an article has been published', 'ws-notify-post'),
      'ws-notify-future'  => __('Send notification when an article has been reserved for publication', 'ws-notify-post'),
      'ws-notify-save'    => __('Send notification when an article has been saved', 'ws-notify-post'),
    );

//    if (
//         !empty($_REQUEST['ws-type']) &&
//         $_REQUEST['ws-type'] == 'ws-notify-post' &&
//         check_admin_referer( 'ws-notify-post' )
//         ) {
//      foreach ( $arr as $key => $val ) {
//        $options[$key] = ( !empty($_REQUEST[$key]) ? true : false );
//      }
//      $options['ws-notify-mails'] = $_REQUEST['ws-notify-mails'] ?? '';
//      update_option( 'ws-notify-post-settings', $options );
//      echo '<div style="margin: 1.0rem 0; padding: 1.0rem; background: #cff;">', __('Saved', 'ws-notify-post'), '</div>';
//    }

    $html = wp_nonce_field( 'ws-notify-post-action', 'ws-notify-post-nonce', true, true );
    foreach ( $arr as $key => $val ) {
      $html .= '<div><label><input type="checkbox" name="' . esc_attr($key) . '" value="1" ' . ( !empty($options[$key]) ? 'checked' : '' ) . ' /> ' . esc_html($val) . '</label></div>';
    }

    $str_save  = __('Save', 'ws-notify-post');
    $str_mails = esc_attr( $options['ws-notify-mails'] ?? '' );

    echo <<<END_OF_HTML

  <section class="ws-form" style="margin: 1.0rem 0; padding: 1.0rem; background: #fff; border-radius: 4px; max-width: 640px;">
    <form action="" method="post">
      <input type="hidden" name="ws-type" value="ws-notify-post" />
      {$html}
      <hr />
      <div><label>メールアドレス（空欄の場合はWordPress管理者宛, 複数の場合はカンマ区切り）<br/><input type="text" name="ws-notify-mails" value="{$str_mails}" style="width:100%;" /></label></div>
      <div><input type="submit" class="button button-primary" value="{$str_save}" style="margin: 1.0rem 0 0;" />
    </form>
  </section>

  <footer style="padding: 1.0rem; font-size: 0.8rem; font-style: italic; text-align: right;">
    {$this->ws_plugin_name} ver {$this->ws_plugin_version}
  </footer>

END_OF_HTML;

    return;
  }


  ////////// プラグイン一覧に設定メニューへのリンクを追加
  function ws_add_action_link_to_plugin_list( $links, $file ) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=ws-notify-post-menu') . '">' . esc_html__('Settings', 'ws-notify-post') . '</a>';
    if ( $file == 'ws-notify-post/ws-notify-post.php' ) {
       array_unshift( $links, $settings_link );
    }
    return $links;
  }


  ////////// CSSファイルやJavascriptファイルを読み込ませる
  function ws_load_files( $hook ) {

    // スタイルシートの読み込み
    $css = $this->ws_plugin_path . 'css/common.css';
    if ( file_exists($css) ) {
      wp_enqueue_style( 'ws-notify-post',
                        $this->ws_plugin_url . 'css/common.css',
                        array(),
                        filemtime($css)
                       );
    } 

    // javascriptの読み込み
    $js = $this->ws_plugin_path . 'js/common.js';
    if ( file_exists($js) ) {
      wp_enqueue_script( 'ws-notify-post',
                        $this->ws_plugin_url . 'js/common.js',
                        array(),
                        filemtime($js)
                       );
    } 

    return;
  }


  ////////////////////////////////////////
  //    記事更新時に通知
  ////////////////////////////////////////
  function ws_save_post( $post_id, $post, $update ) {
    $options = get_option( 'ws-notify-post-settings', array() );

    $flag_save = $options['ws-notify-save'] ?? false;

    if (
         $flag_save &&
         $update === true &&
         $post->post_status == 'publish' &&
         !in_array( $post->post_type, array( 'attachment' ), true ) &&
         is_singular( $post->post_type )
         ) {

      $mail_to = $options['ws-notify-mails'] ?? '';

      $admin_email = get_bloginfo('admin_email');
      $admin_name  = get_bloginfo('name');

      if ( !empty($mail_to) || !empty($admin_email) ) {
        $headers = array(
          // 'From: ' . $admin_email,
          'From: =?UTF-8?B?' . base64_encode($admin_name) . '?= <' . $admin_email . '>',
          'Content-Type: text/plain; charset="UTF-8"',
        );

        $subject = '【'.get_bloginfo('name').'】 ' . __('Post Saved', 'ws-notify-post'); // '記事を更新'

        $bodies = array();
        $bodies[] = __('The following article has been saved:', 'ws-notify-post'); // '以下の記事を保存しました';
        $bodies[] = '';
        // $bodies[] = 'ID     : ' . $post->ID;
        // $bodies[] = 'Type   : ' . $post->post_type;
        // $bodies[] = 'State  : ' . $post->post_status;
        // $bodies[] = 'Title  : ' . $post->post_title;
        // $bodies[] = 'Date   : ' . $post->post_date;
        // $bodies[] = 'Link   : ' . get_permalink($post->ID);
        // $bodies[] = 'Author : ' . $userinfo->display_name;
        $bodies = array_merge( $bodies, $this->ws_notification_mail_body( $post ) );

        wp_mail(
          ( !empty($mail_to) ? preg_split('/\s*,\s*/', $mail_to) : $admin_email ),
          $subject,
          implode(PHP_EOL, $bodies),
          $headers,
        );
      }
    }
    return;
  }


  ////////////////////////////////////////
  //    記事公開時に通知
  ////////////////////////////////////////
  function ws_transition_post_status( $new_status, $old_status, $post ) {
    $options = get_option( 'ws-notify-post-settings', array() );

    $flag_publish = $options['ws-notify-publish'] ?? false;
    $flag_future  = $options['ws-notify-future']  ?? false;

    // 下書きまたは申請待ち状態から公開されたら処理実行
    if (
         in_array($old_status, array( 'new', 'draft', 'pending', 'future' ), true) &&
         (
           ( $flag_publish && in_array($new_status, array( 'publish' ), true ) ) ||
           ( $flag_future  && in_array($new_status, array( 'future'  ), true ) )
           ) &&
         !in_array( $post->post_type, array( 'attachment' ), true ) &&
         is_singular( $post->post_type )
         ) {

      $mail_to = $options['ws-notify-mails'] ?? '';

      $admin_email = get_bloginfo('admin_email');
      $admin_name  = get_bloginfo('name');

      if ( !empty($mail_to) || !empty($admin_email) ) {
        $headers = array(
          // 'From: ' . $admin_email,
          'From: =?UTF-8?B?' . base64_encode($admin_name) . '?= <' . $admin_email . '>',
          'Content-Type: text/plain; charset="UTF-8"',
        );

        $subject = '【'.get_bloginfo('name').'】 ' . ( $new_status == 'future' ? __('Publication Reserved', 'ws-notify-post') : __('Post Published', 'ws-notify-post') );

        $bodies = array();
        if ( $new_status == 'future' ) {
          $bodies[] = __('The following article has been reserved for publication:', 'ws-notify-post'); // '以下の記事を予約されました';
        }
        else {
          $bodies[] = __('The following article has been published:', 'ws-notify-post'); // '以下の記事を公開しました';
        }
        $bodies[] = '';
        // $bodies[] = 'ID     : ' . $post->ID;
        // $bodies[] = 'Type   : ' . $post->post_type;
        // $bodies[] = 'State  : ' . $post->post_status; // new_state;
        // $bodies[] = 'Title  : ' . $post->post_title;
        // $bodies[] = 'Date   : ' . $post->post_date;
        // $bodies[] = 'Link   : ' . get_permalink($post->ID);
        // $bodies[] = 'Author : ' . $userinfo->display_name;
        $bodies = array_merge( $bodies, $this->ws_notification_mail_body( $post ) );

        wp_mail(
          ( !empty($mail_to) ? preg_split('/\s*,\s*/', $mail_to) : $admin_email ),
          $subject,
          implode(PHP_EOL, $bodies),
          $headers
        );
      }
    }
  }


  ////////// メール通知の本文
  function ws_notification_mail_body( $post ) {
    if ( empty($post) ) return array();

    $userinfo = get_userdata( $post->post_author ?? '' );

    $bodies = array();

    $bodies[] = 'ID     : ' . ( $post->ID ?? '' );
    $bodies[] = 'Type   : ' . ( $post->post_type ?? '' );
    $bodies[] = 'State  : ' . ( $post->post_status ?? '' ); // new_state;
    $bodies[] = 'Title  : ' . ( $post->post_title ?? '' );
    $bodies[] = 'Date   : ' . ( $post->post_date ?? '' );
    $bodies[] = 'Link   : ' . ( !empty($post->ID) ? get_permalink($post->ID) : '' );
    $bodies[] = 'Author : ' . ( !empty($userinfo) && !empty($userinfo->display_name) ? $userinfo->display_name : '' );

    return $bodies;
  }


} // class WsNotifyPost


?>