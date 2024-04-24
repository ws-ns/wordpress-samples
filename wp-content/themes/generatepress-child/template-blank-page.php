<?php
/**
 * Template Name: 空の固定ページ
 */
if ( !defined( 'ABSPATH' ) ) exit;

?><!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
  </head>

  <body>

<?php

if ( have_posts() ) {
  the_post();
  the_content();
}

wp_footer();

?>

  </body>

</html>