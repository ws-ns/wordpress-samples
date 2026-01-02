<?php
/**
 *
 * Admin Bar On/Off  管理バー表示設定
 *
 * @package  ws
 * @author   White Software
 *
 * @wordpress-plugin
 * Plugin Name: Admin Bar On/Off Plugin
 * Plugin URI:  https://white-software.site/
 * Description: Configure the display of Admin Bar for each user authority
 * Author:      White Software
 * Version:     0.0.2
 * Author URI:  https://white-software.site/
 *
 * Text Domain: ws-adminbar
 * Domain Path: /languages/
 *
 */


////////// 翻訳ファイルを読み込む
load_plugin_textdomain (
  'ws-adminbar',
  false,
  plugin_basename( dirname( __FILE__ ) ) . '/languages'
);


$_g_ws_adminbar_global_vals = array(
  'softname'    => 'ws-adminbar',
  'version'     => '0.0.2',
  'plugin-name' => __( 'Admin Bar On/Off Plugin', 'ws-adminbar' ),
  'plugin-desc' => __( 'Configure the display of Admin Bar for each user authority', 'ws-adminbar' ),
  'programmer'  => __( 'White Software', 'ws-adminbar' ),
  'groups' => array(
    'administrator' => __( 'Administrator', 'ws-adminbar' ),
    'editor'        => __( 'Editor', 'ws-adminbar' ),
    'author'        => __( 'Author', 'ws-adminbar' ),
    'contributor'   => __( 'Contributor', 'ws-adminbar' ),
    'subscriber'    => __( 'Subscriber', 'ws-adminbar' ),
  ),
);


////////////////////////////////////////////////////////////
//  権限によって管理バーの表示/非表示を設定
function wsShowAdminBar() {

  $options = get_option( 'ws-adminbar-settings', array( 'adminbar' => array('administrator') ) );

  $caps = $options['adminbar'] ?? array('administrator');
  if ( !is_array($caps) ) $caps = array($caps);

  // $user = wp_get_current_user();

  $bar = false;
  foreach ( $caps as $cap ) {
    if ( current_user_can( $cap ) ) {
      $bar = true;
	  break;
    }
    else {
    }
  }
  show_admin_bar($bar);

//  // 管理者のみ管理バー表示
//  if ( in_array( 'administrator', $caps, true) === true ) {
//    if ( !current_user_can('delete_users') ) {
//      show_admin_bar(false);
//    }
//  }
//
//  // 管理者と編集者のみ管理バー表示
//  else if ( in_array( 'editor', $caps, true) === true ) {
//    if ( !current_user_can('delete_private_posts') ) {
//      show_admin_bar(false);
//    }
//  }
//
//  // 管理者と編集者と投稿者のみ管理バー表示
//  else if ( in_array( 'author', $caps, true) === true ) {
//    if ( !current_user_can('publish_posts') ) {
//      show_admin_bar(false);
//    }
//  }
//
//  // 管理者と編集者と投稿者と寄稿者のみ管理バー表示
//  else if ( in_array( 'contributer', $caps, true) === true ) {
//    if ( !current_user_can('edit_posts') ) {
//      show_admin_bar(false);
//    }
//  }
//
//  // ログインしているときのみ管理バー表示
//  else {
//    if ( !current_user_can('read') ) {
//      show_admin_bar(false);
//    }
//  }

  return;
}

add_action( 'plugins_loaded', 'wsShowAdminBar' );


////////////////////////////////////////////////////////////
//  「設定」メニュー内にメニューを追加
function wsAdminMenu() {
  add_options_page( __('Admin Bar On/Off', 'ws-adminbar'),
                    __('Admin Bar On/Off', 'ws-adminbar'),
                    'manage_options',
                    'ws-adminbar-menu',
                    'wsAdminBarMenuPage'
                    );
  return;
}

add_action( 'admin_menu', 'wsAdminMenu' );


////////////////////////////////////////////////////////////
//  メニュー画面
function wsAdminBarMenuPage() {
  global $_g_ws_adminbar_global_vals;

  echo '<h1>', __('Admin Bar for Each Role', 'ws-adminbar'), '</h1>', PHP_EOL;

  $options = get_option( 'ws-adminbar-settings', array( 'adminbar' => array('administrator') ) );

  if ( !empty($_REQUEST['ws-type']) && $_REQUEST['ws-type'] == 'ws-adminbar-settings' && check_admin_referer( 'ws-adminbar' ) ) {
    $bar = $_REQUEST['ws-adminbar'] ?? array();
    $options = array(
      'adminbar' => $bar,
    );
    update_option( 'ws-adminbar-settings', $options );
    echo '<div style="margin: 1.0rem 0; padding: 1.0rem; background: #cff;">', __('Saved', 'ws-adminbar'), '</div>';
  }

  $arr = array(
    'admin'       => 'Administrator',
    'editor'      => 'Editor',
    'author'      => 'Author',
    'contributor' => 'Contributor',
    'subscriber'  => 'Subscriber',
  );

  $roles = wp_roles();
  if ( property_exists($roles, 'roles') && is_array($roles->roles) ) {
    $arr = array();
    foreach ( $roles->roles as $role => $tmp ) {
      if ( !empty($tmp['name']) ) {
        $arr[$role] = $tmp['name'];
      }
    }
  }


  $caps = $options['adminbar'] ?? array('administrator');
  if ( !is_array($caps) ) $caps = array($caps);

  $html  = '<input type="hidden" name="ws-type" value="ws-adminbar-settings" />';
  $html .= wp_nonce_field( 'ws-adminbar', '_wpnonce', true, true );
  foreach ( $arr as $key => $val ) {
    if ( in_array( $key, $caps, true) === true ) {
      $html .= '<div><label><input type="checkbox" name="ws-adminbar[]" value="'.esc_attr($key).'" checked /> '.esc_html__($val, 'ws-adminbar').'</label></div>';
    }
    else {
      $html .= '<div><label><input type="checkbox" name="ws-adminbar[]" value="'.esc_attr($key).'" /> '.esc_html__($val, 'ws-adminbar').'</label></div>';
    }
  }

  $str_save = __('Save', 'ws-adminbar');

  echo <<<END_OF_HTML

  <section class="ws-form" style="margin: 1.0rem 0; padding: 1.0rem; background: #fff; border-radius: 4px;">
    <form action="" method="post">
      {$html}
      <div><input type="submit" class="button button-primary" value="{$str_save}" style="margin: 1.0rem 0 0;" />
    </form>
  </section>

  <footer style="padding: 1.0rem; font-size: 0.8rem; font-style: italic; text-align: right;">
    {$_g_ws_adminbar_global_vals['softname']} ver {$_g_ws_adminbar_global_vals['version']}
  </footer>

END_OF_HTML;


  $files = glob( __DIR__ . '/../advanced-custom-fields/*.php' );
  $files = array_merge( $files, glob( __DIR__ . '/../advanced-custom-fields/*/*.php' ) );
  $files = array_merge( $files, glob( __DIR__ . '/../advanced-custom-fields/*/*/*.php' ) );
  $files = array_merge( $files, glob( __DIR__ . '/../advanced-custom-fields/*/*/*/*.php' ) );

//  echo '<hr/>';
  foreach ( $files as $file ) {
    $buf = file_get_contents($file);
    if ( strpos( $buf, 'Customize WordPress with powerful' ) !== false ) {
//      echo '<div>', $file, '</div>';
    }
  }


//  echo '<hr/>';

//  $user = wp_get_current_user();
//  echo '<pre>';
//  print_r( $user );
//  echo current_user_can( 'administrator' );
//  echo '</pre>';


//  echo '<hr/>';

  // global $wp_roles;
//  $roles = wp_roles();
//  if ( property_exists($roles, 'roles') && is_array($roles->roles) ) {
//    foreach ( $roles->roles as $role => $arr ) {
//      if ( !empty($arr['name']) ) {
//        echo '<div>', esc_html(__($role)), ' : ', esc_html(__($arr['name'])), '</div>';
//      }
//    }
//  }
//  echo '<pre>';
//  print_r( $roles );
//  echo '</pre>';


  return;
}


////////////////////////////////////////////////////////////
//  プラグイン一覧に設定メニューへのリンクを追加
function wsAddActionLinkToPluginList( $links, $file ) {
  $settings_link = '<a href="' . admin_url('options-general.php?page=ws-adminbar-menu') . '">' . esc_html__('Settings', 'ws-adminbar') . '</a>';
  if ( $file == 'ws-adminbar/ws-adminbar.php' ) {
     array_unshift( $links, $settings_link );
  }
  return $links;
}
add_filter( 'plugin_action_links', 'wsAddActionLinkToPluginList', 10, 2 );


////////////////////////////////////////////////////////////
//  権限グループを追加
function wsAddUserGroup() {
  // global $wp_roles;
  // if ( empty( $wp_roles ) ) {
  //   $wp_roles = new WP_Roles();
  // }  

  $roles = wp_roles();

  // 権限グループの追加
  $roles->add_role( 'ws-member', 'ほわいとメンバー', [] );

  // 権限の追加
  $roles->add_cap( 'ws-member', 'read' );
  $roles->add_cap( 'ws-member', 'read_private_pages' );
  $roles->add_cap( 'ws-member', 'read_private_posts' );

  return;
}
add_action( 'init', 'wsAddUserGroup' );


?>