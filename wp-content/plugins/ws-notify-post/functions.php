<?php

// あ

////////// カテゴリー一覧をテーブルで表示


////////// ショートコード定義
//  [ws_show_categories] : カテゴリー一覧（名前，スラッグ，ID，説明，投稿数）
//  [ws_show_tags]       : タグ一覧（名前，スラッグ，ID，投稿数）
add_shortcode( 'ws_show_categories', 'ws_show_categories' );
add_shortcode( 'ws_show_tags',       'ws_show_tags' );


////////// カテゴリー一覧
//  <table class="ws-category-table"> というタグで出力されるので自由に整形してください
function ws_show_categories($attr, $content, $tag) {

  $html = '';

  // カテゴリー一覧を取得
  $cats = get_categories( array( 'orderby' => 'term_id', 'order' => 'ASC', 'hide_empty' => 0 ) );

  if ( $cats && count($cats) > 0 ) {
    // カテゴリーデータを整形．カテゴリーIDで親子関係を引っ張ってこれるようにする
    $cat_data = array();
    foreach ( $cats as $cat ) {
      $cat_id     = $cat->term_id;
      $cat_parent = $cat->category_parent;
      if ( !isset($cat_data[$cat_parent]) ) $cat_data[$cat_parent] = array();
      $cat_data[$cat_parent][$cat_id] = $cat;
    }

    // 整理したカテゴリーデータを使って再帰関数にて表示
    $html .= '<table class="ws-category-table">';
    $html .= '<thead><th>カテゴリー名</th><th>slug</th><th>ID</th><th>説明</th><th>投稿数</th></thead>';
    list( $num, $parts_html ) = ws_show_category_child( 0, 0, $cat_data );
    $html .= '<tbody>';
    $html .= $parts_html;
    $html .= '</tbody>';
    $html .= '</table>';
  }
  else {
    $html = '<p>カテゴリーはありません</p>';
  }

  return $html;
}


////////// カテゴリーのツリー構造を再帰関数にて表示
//  $level    : 親子関係の深さ
//  $cat_id   : 親カテゴリーID
//  $cat_data : 全カテゴリーデータ
function ws_show_category_child( $level, $cat_id, $cat_data ) {
  $num = 0;
  $html = '';

  $show_link = true; // カテゴリーアーカイブへのリンクが不要なら false にする

  // 子カテゴリーがあるなら表示する
  if ( isset($cat_data[$cat_id]) ) {
    foreach ( $cat_data[$cat_id] as $cat ) {
      $link = get_category_link($cat->term_id);
      // テーブルの一行はじまり
      $html .= '<tr>';
      // カテゴリー名
      if ( $show_link && $link != '' ) {
        $html .= '<td>';
        for ( $i = 0 ; $i < $level ; $i++ ) $html .= ' &nbsp; ';
        if ( $level > 0 ) $html .= ' ┗ ';
        $html .= '<a href="'.$link.'">' . $cat->name . '</a>';
        $html .= '</td>';
      }
      else {
        $html .= '<td>' . $cat->name . '</td>';
      }
      // カテゴリースラッグ
      $html .= '<td>' . $cat->slug . '</td>';
      // カテゴリーID
      $html .= '<td align="right">' . $cat->term_id . '</td>';
      // カテゴリー説明
      $html .= '<td>' . $cat->category_description . '</td>';
      // 下層カテゴリーの投稿数とHTMLを取得
      list( $child_num, $parts_html ) = ws_show_category_child( $level+1, $cat->term_id, $cat_data );
      // カテゴリー投稿数
      $html .= '<td>';
      $html .= 'このカテゴリのみ : ' . intval($cat->count);
      $sum = (intval($cat->count)+intval($child_num));
      if ( intval($child_num) > 0 ) $html .= '<br/>下層カテゴリ含む : ' . $sum;
      $html .= '</td>';
      // テーブルの一行おわり
      $html .= '</tr>';
      // 下層カテゴリーのHTMLを追記
      $html .= $parts_html;
      // 下層カテゴリーの投稿数を積算
      $num += $sum;
    }
  }

  return array( intval($num), $html );
}


////////// タグ一覧
//  <table class="ws-tag-table"> というタグで出力されるので自由に整形してください
function ws_show_tags($attr, $content, $tag) {
  $html = '';

  // タグ一覧を取得
  $tags = get_tags( array( 'orderby' => 'term_id', 'order' => 'ASC', 'hide_empty' => 0 ) );

  $show_link = true; // タグアーカイブへのリンクが不要なら false にする

  if ( $tags && count($tags) > 0 ) {
    // テーブルはじまり
    $html .= '<table class="ws-tag-table">';
    $html .= '<thead><th>タグ名</th><th>slug</th><th>ID</th><th>説明</th><th>投稿数</th></thead>';
    $html .= '<tbody>';
    foreach ( $tags as $tag ) {
      $link = get_tag_link($tag->term_id);
      // テーブルの一行はじまり
      $html .= '<tr>';
      // タグ名
      if ( $show_link && $link != '' ) {
        $html .= '<td><a href="'.$link.'">' . $tag->name . '</a></td>';
      }
      else {
        $html .= '<td>' . $tag->name . '</td>';
      }
      // タグスラッグ
      $html .= '<td>' . $tag->slug . ( $tag->slug != urldecode($tag->slug) ? '<br/>('.urldecode($tag->slug).')' : '' ) . '</td>';
      // タグID
      $html .= '<td align="right">' . $tag->term_id . '</td>';
      // タグ説明
      $html .= '<td>' . $tag->description . '</td>';
      // タグ投稿数
      $html .= '<td>' . $tag->count . '</td>';
      // テーブルの一行おわり
      $html .= '</tr>';
    }
    $html .= '</tbody>';
    // テーブルおわり
    $html .= '</table>';
  }
  else {
    $html .= '<p>タグはありません</p>';
  }

  return $html;
}

?>
