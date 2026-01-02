<?php

// あ ヘッダー

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$theme_uri  = get_theme_file_uri();  // 有効なテーマまでのURL  : 末尾スラッシュなし
$theme_path = get_theme_file_path(); // 有効なテーマまでのパス : 末尾スラッシュなし

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <?php wp_head(); ?>

<?php
  if ( is_admin_bar_showing() ) {
    echo <<<END_OF_STYLE

  <style>
    html {
      margin-top: 32px !important;
    }
    @media screen and ( max-width: 782px ) {
      html {
        margin-top: 46px !important;
      }
	}
  </style>
END_OF_STYLE;
  }
?>
</head>


<body class="white-software ws-login">

