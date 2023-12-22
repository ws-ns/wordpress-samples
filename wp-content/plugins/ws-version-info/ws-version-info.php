<?php
/**
 * Extract Version Info for WordPress
 *
 * @package  ws
 * @author   White Software
 *
 * @wordpress-plugin
 * Plugin Name: Extract Version Info
 * Plugin URI:  https://white-software.site/
 * Description: Extract Version Info for WordPress
 * Author:      White Software
 * Version:     0.0.1
 * Author URI:  https://white-software.site/
 * Text Domain: ws-version-info
 * Domain Path: /languages/
 */


/**
 * 翻訳ファイルを読み込む
 */
load_plugin_textdomain(
  'ws-version-info',
  false,
  plugin_basename( __DIR__ ) . '/languages'
);


/**
 * 翻訳
 */
$_g_ws_global_vals = array(
  'plugine-name' => __( 'Extract Version Info', 'ws-version-info' ),
  'description' => __( 'Extract Version Info for WordPress', 'ws-version-info' ),
  'author' => __( 'White Software', 'ws-version-info' ),
);
 

/**
 * プラグイン一覧に設定メニューへのリンクを追加
 */
add_filter( 'plugin_action_links', 'ws_add_action_link_to_plugin_list', 10, 2 );

function ws_add_action_link_to_plugin_list( $links, $file ) {
  $settings_link = '<a href="' . admin_url('options-general.php?page=ws-version-info-menu1') . '">' . esc_html__('Settings', 'ws-version-infto') . '</a>';
  // $settings_link = '<a href="' . admin_url('admin.php?page=ws-version-info-menu1') . '">' . esc_html__('Settings', 'ws-version-info') . '</a>';
  if ( $file == 'ws-version-info/ws-version-info.php' ) {
     array_unshift( $links, $settings_link );
  }
  return $links;
}


/**
 * 「設定」メニュー内にメニューを追加
 */
add_action( 'admin_menu', 'ws_admin_menu' );

function ws_admin_menu() {
  // 表示する権限
  $capability = 'manage_options'; // 管理者以上

  // オプションページに表示する場合
  add_options_page( __('Version Info', 'ws-version-info'),
                    __('Version Info', 'ws-version-info'),
                    $capability,
                    'ws-version-info-menu1',
                    'ws_version_info_menu_page1',
                    );

  // 管理画面メニューのメインメニュー
  // add_menu_page( __('Version Info', 'ws-version-info'),
  //                __('Version Info', 'ws-version-info'),
  //                $capability,
  //                'ws-version-info-menu1',
  //                'ws_version_info_menu_page1',
  //                'dashicons-database'
  //                );

  // 管理画面メニューのサブメニュー
  // add_submenu_page( 'ws-version-info-menu1',
  //                   __('Manage Clinic DB', 'ws-version-info'),
  //                   __('Manage Clinic DB', 'ws-version-info'),
  //                   $capability,
  //                   'ws-version-info-menu1',
  //                   'ws_version_info_menu_page1');
  // add_submenu_page( 'ws-version-info-menu1',
  //                   __('Taxonomy Settings', 'ws-version-info'),
  //                   __('Taxonomy Settings', 'ws-version-info'),
  //                   $capability,
  //                   'ws-version-info-menu2',
  //                   'ws_version_info_menu_page2' );
  // add_submenu_page( 'ws-version-info-menu1',
  //                   __('Mail', 'ws-version-info'),
  //                   __('Mail', 'ws-version-info'),
  //                   $capability,
  //                   'ws-version-info-menu3',
  //                   'ws_version_info_menu_page3' );
  // add_submenu_page( 'ws-version-info-menu1',
  //                   __('Web Meeting', 'ws-version-info'),
  //                   __('Web Meeting', 'ws-version-info'),
  //                   $capability,
  //                   'ws-version-info-menu4',
  //                   'ws_version_info_menu_page4' );
  // add_submenu_page( 'ws-version-info-menu1',
  //                   __('For Admin', 'ws-version-info'),
  //                   __('For Admin', 'ws-version-info'),
  //                   $capability,
  //                   'ws-version-info-menu99',
  //                   'ws_version_info_menu_page99' );

  return;
}


/**
 * メニュー画面1
 */
function ws_version_info_menu_page1() {
  global $wp_version;

  $name1 = get_template();
  $name2 = get_stylesheet();

  $child_theme_name    = '';
  $child_theme_version = '';
  $parent_theme_name    = '';
  $parent_theme_version = '';

  if ( $name1 == $name2 ) {
    $child_theme_name    = '-';
    $child_theme_version = '-';
    $theme  = wp_get_theme();
    $parent_theme_name    = esc_html($theme->Name);
    $parent_theme_version = esc_html($theme->Version);
  }
  else {
    $theme  = wp_get_theme($name2);
    $child_theme_name    = esc_html($theme->Name);
    $child_theme_version = esc_html($theme->Version);
    $theme  = wp_get_theme($name1);
    $parent_theme_name    = esc_html($theme->Name);
    $parent_theme_version = esc_html($theme->Version);
  }

  $phpversion = phpversion();

  echo <<<END_OF_HTML

  <style>
    .ws-version-info {
      margin: 1.0rem 0;
      padding: 1.0rem;
      width: max-content;
      max-width: calc( 100% - 1.0rem );
      background: #fff;
      border: 1px solid #ccc;
      box-shadow: 0 0 4px #ccc;
      box-sizing: border-box;
    }
    .ws-version-info * {
      box-sizing: border-box;
    }
    .ws-version-info table {
      border-collapse: collapse;
      table-layout: fix;
    }
    .ws-version-info table thead tr th {
      padding: 0.5rem 1.0rem;
      font-size: 1.1rem;
      border: 1px solid #ccc;
      color: #fff;
      background: #333;
    }
    .ws-version-info table tbody tr th {
      padding: 0.5rem 1.0rem;
      font-size: 1.1rem;
      border: 1px solid #ccc;
    }
    .ws-version-info table tbody tr td {
      padding: 0.5rem 1.0rem;
      font-size: 1.0rem;
      border: 1px solid #ccc;
    }
    .ws-version-info table tbody tr td:nth-child(3) {
      text-align: right;
    }
  </style>

  <section class="ws-version-info">

    <table>
      <thead>
        <tr>
          <th>Component</th>
          <th>Name</th>
          <th>Version</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th>Script</th>
          <td>php</td>
          <td>{$phpversion}</td>
        </tr>
        <tr>
          <th>Platform</th>
          <td>WordPress</td>
          <td>{$wp_version}</td>
        </tr>
        <tr>
          <th>Parent Theme</th>
          <td>{$parent_theme_name}</td>
          <td>{$parent_theme_version}</td>
        </tr>
        <tr>
          <th>Child Theme</th>
          <td>{$child_theme_name}</td>
          <td>{$child_theme_version}</td>
        </tr>
END_OF_HTML;

  include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
  $plugins = get_plugins();
  if ( !empty($plugins) && is_array($plugins) ) {
    foreach ( $plugins as $path => $plugin ) {
      if ( !is_plugin_active( $path ) ) continue; // 有効なプラグインのみ
      echo '<tr>';
      echo '<th>Plugin</th>';
      echo '<td>', esc_html($plugin['Name'] ?? ''), '</td>';
      echo '<td>', esc_html($plugin['Version'] ?? ''), '</td>';
      echo '</tr>';
    }
  }

  echo <<<END_OF_HTML
      </tbody>
    </table>

  </section>

END_OF_HTML;

}
