<?php

// あ フッター

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$theme_uri  = get_theme_file_uri();  // 有効なテーマまでのURL  : 末尾スラッシュなし
$theme_path = get_theme_file_path(); // 有効なテーマまでのパス : 末尾スラッシュなし

wp_footer();

?>

</body>
</html>
