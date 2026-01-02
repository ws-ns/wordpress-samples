<?php

// あ

if ( !defined( 'ABSPATH' ) ) exit;

$errors = array();

if ( !empty($_POST['ws-login-nonce']) ) {
  $userid   = $_POST['ws-user'] ?? '';
  $password = $_POST['ws-pass'] ?? '';
  $redirect = $_REQUEST['redirect'] ?? home_url();

  // nonceチェック
  if ( !wp_verify_nonce( $_POST['ws-login-nonce'], 'login' ) ) {
    $errors[] = '不正な遷移です';
  }
  // 入力チェック
  else {
    if ( empty($userid) ||
         empty($password) ) {
      $errors[] = 'ユーザーIDとパスワードを入力してください'; 
    }
  }

  if ( empty($errors) ) {
    // ユーザー取得
    $user = get_user_by( 'email', $userid );
    if ( $user === false ) {
      $user = get_user_by( 'login', $userid );
    }
    if ( $user === false ) {
      $errors[] = 'ユーザーIDまたはパスワードが間違っています';
    }
    else {
      $credit = array(
        'user_login'    => $user->data->user_login,
        'user_password' => $password,
        'remember'      => true
      );
      $user = wp_signon( $credit, false );      
      if ( is_wp_error( $user ) ) {
        // ログイン失敗
        if ( array_key_exists( 'incorrect_password', $user->errors ) ) {
          $errors[] = 'ユーザーIDまたはパスワードが間違っています';
        }
        else {
          $errors[] = 'ログインに失敗しました';
        }
      }
      else {
        // ログイン成功 → リダイレクト
        wp_safe_redirect( $redirect );
      }
    }
  }
}

// ヘッダ
get_header('ws-login');

// 本文
echo do_shortcode('[ws-front-login-form ws-errors="' . esc_attr( !empty($errors) ? implode(':::', $errors) : '' ) . '"]');

// フッタ
get_footer('ws-login');

?>

