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
 *  FrontLoginクラス：ショートコード用
 *
 * @class WsFrontLoginShortcode
 */
class WsFrontLoginShortcode extends WsFrontLogin {

  private $ws_cache_html = array();


  /**
   * コンストラクタ
   */
  public function __construct( $base = __FILE__ ) {
    parent::__construct($base);
    
    // キャッシュ
    $this->ws_cache_html = array();

    // ショートコード
    // add_shortcode( 'ws-clinic-search',   array( $this, 'ws_shortcode_search' ) );
    // add_shortcode( 'ws-clinic-page',     array( $this, 'ws_shortcode_page' ) );
    // add_shortcode( 'ws-clinic-archive',  array( $this, 'ws_shortcode_archive' ) );
    // add_shortcode( 'ws-clinic-single',   array( $this, 'ws_shortcode_single' ) );
    // add_shortcode( 'ws-clinic-taxonomy', array( $this, 'ws_shortcode_taxonomy' ) );

    add_shortcode( 'ws-front-login-form', array( $this, 'ws_shortcode_form' ) );

    add_shortcode( 'ws-front-login-loginout', function() {
      return wp_loginout( '', false );
    } );
    add_shortcode( 'ws-front-login-login-link', function() {
      if ( is_user_logged_in() ) return '';
      return wp_loginout( '', false );
    } );
    add_shortcode( 'ws-front-login-logout-link', function() {
      if ( !is_user_logged_in() ) return '';
      return wp_loginout( '', false );
    } );

    // POSTやGETの処理
    // add_action( 'wp', array( $this, 'ws_proc_get_and_post' ) );

    return;
  }


  /**
   * デストラクタ
   */
  public function __destruct() {
    return;
  }


  ////////////////////////////////////////
  //    GETやPOSTの処理
  function ws_proc_get_and_post() {
    global $post;

    // // 検索ページではリクエストをセッションに移し替え
    // if ( !empty($post->post_name) && $post->post_name == 'search-clinic' ) {

    //   if ( session_status() == PHP_SESSION_NONE ) {
    //     session_name('ws-clinic');
    //     session_start();
    //   }

    //   if ( !empty($_REQUEST) &&
    //        !empty($_REQUEST['ws-type']) &&
    //        $_REQUEST['ws-type'] == 'ws-clinic-search'
    //        ) {

    //     $_SESSION['ws-request'] = $_REQUEST;

    //     wp_safe_redirect( home_url('/search-clinic') );
    //     exit;
    //   }

    //   // 検索用js登録
    //   $js = $this->ws_plugin_path.'js/search.js';
    //   if ( file_exists($js) ) {
    //     wp_enqueue_script( 'ws-clinic-search-js',
    //                        $this->ws_plugin_url.'js/search.js',
    //                        array('jquery'),
    //                        filemtime($js)
    //                      );
    //   }
    // }

    return;
  }


  /**
   * ログインフォーム
   */
  function ws_shortcode_form( $atts, $content, $tag ) {
    $attsval = shortcode_atts( array(
      'ws-errors' => '',
    ), $atts );

    global $post;

    $errors = ( !empty($attsval['ws-errors']) ? explode(':::', $attsval['ws-errors']) : array() );

    $html = '';

    $html .= '<header>';
    $html .=   '<h1>ログイン</h1>';
    $html .= '</header>';

    $html .= '<main>';

    $html .=   '<form action="' . get_permalink($post) . '" method="post">';
    $html .=     wp_nonce_field( 'login', 'ws-login-nonce', true, false );
    $html .=     '<input type="hidden" name="redirect" value="' . esc_url( $_REQUEST['redirect'] ?? '' ) . '" />';

    if (
         !is_user_logged_in() &&
         is_plugin_active('miniorange-login-openid/miniorange_openid_sso_settings.php')
         ) {
      $html .= '<div class="login-input centered">';
      $html .=   do_shortcode('[miniorange_social_login]');
      $html .= '</div>';
    }

    $html .= '<div>';
    $html .=   do_shortcode('[ws-front-login-logout-link]');
    $html .= '</div>';

    if (
         !is_user_logged_in() &&
         !is_plugin_active('miniorange-login-openid/miniorange_openid_sso_settings.php')
         ) {
      $html .=     '<div class="login-input">';
      $html .=       '<label>ユーザーID <input type="text" name="ws-user" /></label>';
      $html .=     '</div>';
      $html .=     '<div class="login-input">';
      $html .=       '<label>パスワード <input type="password" name="ws-pass" /></label>';
      $html .=     '</div>';
    }

    if ( !empty($errors) ) {
      $html .= '<p class="error">' . ( is_array($errors) ? implode('<br/>', $errors) : $errors ) . '</p>';
    }

    if (
         !is_user_logged_in() &&
         !is_plugin_active('miniorange-login-openid/miniorange_openid_sso_settings.php')
         ) {
      $html .=     '<div class="login-input">';
      $html .=       '<button type="submit">ログイン</button>';
      $html .=     '</div>';
    }

    $html .=   '</form>';
    $html .= '</main>';

    $html .= PHP_EOL;

    return $html;
  }


  ////////////////////////////////////////
  //    検索ショートコード
  function ws_shortcode_search( $atts, $content, $tag ) {

    $attsval = shortcode_atts( array(
      'post-type' => 'ws-clinic',
    ), $atts );

    $post_type = $attsval['post-type'];

    if ( !empty($this->ws_cache_html['search-'.$post_type]) ) {
      return $this->ws_cache_html['search-'.$post_type];
    }

    $html  = '<section class="ws-clinic-area">';

    // $html .= '<pre>';
    // $html .= print_r( $_SESSION, true );
    // $html .= '</pre>';

    $search_keyword = $_SESSION['ws-request']['ws-search-keyword'] ?? '';
    $search_area    = $_SESSION['ws-request']['ws-search-area'] ?? '';
    $search_cure    = $_SESSION['ws-request']['ws-search-cure'] ?? '';

    // 何も検索条件がない
    if ( empty($search_keyword) && empty($search_area) && empty($search_cure) ) {
      return $html;
    }

    $paged = get_query_var('paged') ? get_query_var('paged') : 1;

    // キーワードが空の場合 → 普通にタクソノミー検索
    if ( empty($search_keyword) ) {

      $args = array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => 2,
        'order'          => 'DESC',
        'orderby'        => 'post_date',
        'paged'          => $paged,
      );

      if ( !empty($search_area) && !empty($search_cure) ) {
        $args['tax_query'] = array(
          'relation' => 'AND',
          array(
            'taxonomy' => 'ws-clinic-area',
            'field' => 'term_id',
            'terms' => array( $search_area ),
          ),
          array(
            'taxonomy' => 'ws-clinic-cure',
            'field' => 'term_id',
            'terms' => array( $search_cure ),
          ),
        );
      }
      else if ( !empty($search_area) ) {
        $args['tax_query'] = array(
          array(
            'taxonomy' => 'ws-clinic-area',
            'field' => 'term_id',
            'terms' => array( $search_area ),
          ),
        );
      }
      else if ( !empty($search_cure) ) {
        $args['tax_query'] = array(
          array(
            'taxonomy' => 'ws-clinic-cure',
            'field' => 'term_id',
            'terms' => array( $search_cure ),
          ),
        );
      }

      $query = new WP_Query($args);
      
      if ( $query->have_posts() ) {
        foreach ( $query->posts as $post ) {
          // $query->the_post();
          $html .= $this->ws_show_clinic_one( $post );
          // $html .= '<div class="ws-clinic-item">';
          // $html .= '<h3><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
          // $html .= '</div>' . PHP_EOL; // ws-clinic-item
        }
      }
      wp_reset_postdata();

      $big = 9999999999;
      $html .= paginate_links(array(
        'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'show_all'  => true,
        'type'      => 'list',
        'format'    => '?paged=%#%',
        'current'   => max(1, get_query_var('paged')),
        'total'     => $query->max_num_pages,
        'prev_text' => '前へ',
        'next_text' => '次へ',
      ));
    }

    // キーワードがある場合
    else {

      // キーワードのみの場合 → SQL で LIKE を使った検索
      if ( empty($search_area) && empty($search_cure) ) {

      }

      // キーワードとタクソノミーの複合検索
      else {

      }

    }


//    $html .= '<h2>Search Page</h2>';
//    $html .= '<h3>Post Type = ' . $post_type . '</h3>';

//    $html .= '<div class="ws-clinic-box">';
//    $html .=  wp_nonce_field( 'ws-clinic', '_wpnonce', true, false );
//    $html .=  '<input type="hidden" name="ws-type" value="ws-clinic-search" />';
//    $html .=  '<input type="hidden" name="post-type" value="' . $post_type . '" />';
//    $html .=  '<div>都道府県 <select id="ws-pref" name="ws-pref">';
//    $html .=   '<option value="">～ 選択 ～</option>';
//    $html .=   '<option value="北海道">北海道</option>';
//    $html .=   '<option value="青森県">青森県</option>';
//    $html .=   '<option value="岩手県">岩手県</option>';
//    $html .=   '<option value="宮城県">宮城県</option>';
//    $html .=   '<option value="秋田県">秋田県</option>';
//    $html .=   '<option value="山形県">山形県</option>';
//    $html .=   '<option value="福島県">福島県</option>';
//    $html .=   '<option value="茨城県">茨城県</option>';
//    $html .=   '<option value="栃木県">栃木県</option>';
//    $html .=   '<option value="群馬県">群馬県</option>';
//    $html .=   '<option value="埼玉県">埼玉県</option>';
//    $html .=   '<option value="千葉県">千葉県</option>';
//    $html .=   '<option value="東京都">東京都</option>';
//    $html .=   '<option value="神奈川県">神奈川県</option>';
//    $html .=   '<option value="新潟県">新潟県</option>';
//    $html .=   '<option value="富山県">富山県</option>';
//    $html .=   '<option value="石川県">石川県</option>';
//    $html .=   '<option value="福井県">福井県</option>';
//    $html .=   '<option value="山梨県">山梨県</option>';
//    $html .=   '<option value="長野県">長野県</option>';
//    $html .=   '<option value="岐阜県">岐阜県</option>';
//    $html .=   '<option value="静岡県">静岡県</option>';
//    $html .=   '<option value="愛知県">愛知県</option>';
//    $html .=   '<option value="三重県">三重県</option>';
//    $html .=   '<option value="滋賀県">滋賀県</option>';
//    $html .=   '<option value="京都府">京都府</option>';
//    $html .=   '<option value="大阪府">大阪府</option>';
//    $html .=   '<option value="兵庫県">兵庫県</option>';
//    $html .=   '<option value="奈良県">奈良県</option>';
//    $html .=   '<option value="和歌山県">和歌山県</option>';
//    $html .=   '<option value="鳥取県">鳥取県</option>';
//    $html .=   '<option value="島根県">島根県</option>';
//    $html .=   '<option value="岡山県">岡山県</option>';
//    $html .=   '<option value="広島県">広島県</option>';
//    $html .=   '<option value="山口県">山口県</option>';
//    $html .=   '<option value="徳島県">徳島県</option>';
//    $html .=   '<option value="香川県">香川県</option>';
//    $html .=   '<option value="愛媛県">愛媛県</option>';
//    $html .=   '<option value="高知県">高知県</option>';
//    $html .=   '<option value="福岡県">福岡県</option>';
//    $html .=   '<option value="佐賀県">佐賀県</option>';
//    $html .=   '<option value="長崎県">長崎県</option>';
//    $html .=   '<option value="熊本県">熊本県</option>';
//    $html .=   '<option value="大分県">大分県</option>';
//    $html .=   '<option value="宮崎県">宮崎県</option>';
//    $html .=   '<option value="鹿児島県">鹿児島県</option>';
//    $html .=   '<option value="沖縄県">沖縄県</option>';
//    $html .=  '</select></div>';
//    $html .=  '<div>キーワード <input type="text" id="ws-keyword" name="ws-keyword" value="" /></div>';
//    $html .=  '<div><button id="ws-clinic-run-search" class="ws-clinic-submit">検索</button></div>';
//    $html .= '</div>';

//    $html .= '<div class="ws-clinic-search-result" id="ws-clinic-search-result">';
//    $html .= '</div>';

    // $html .= '<h4>$_REQUEST</h4>';
    // $html .= '<pre>';
    // $html .= print_r($_REQUEST ?? array(), true);
    // $html .= '</pre>';

    // $html .= '<h4>$_SESSION</h4>';
    // $html .= '<pre>';
    // $html .= print_r($_SESSION ?? array(), true);
    // $html .= '</pre>';

    // $html .= '<h4>$_SERVER</h4>';
    // $html .= '<pre>';
    // $html .= print_r($_SERVER ?? array(), true);
    // $html .= '</pre>';

//    $html .= '<script>';
//    $html .= "_g_ws_clinic_plugin_url = '" . $this->ws_plugin_url . "'";
//    $html .= '</script>';

    $html .= '</section>' . PHP_EOL; // class="ws-clinic-area"

    $this->ws_cache_html['search-'.$post_type] = $html;

    return $html;
  }


  ////////////////////////////////////////
  //    固定ページショートコード
  function ws_shortcode_page( $atts, $content, $tag ) {

    $attsval = shortcode_atts( array(
      'post-name' => 'ws-clinic',
    ), $atts );

    global $post;

    $post_name = $attsval['post-name'];

    if ( !empty($this->ws_cache_html['page-'.$post_name]) ) {
      return $this->ws_cache_html['page-'.$post_name];
    }

    $metas = ( function_exists('get_fields') ? get_fields($post->ID) : arrray() ); 

    $html = '';

    $html .= '<section class="ws-content bg-white">';
    $html .= '  <h2 class="ws-head">';
    $html .=     '<div><img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-icon1-1x.png" width="30" height="26" class="h2-icon" alt="" /></div>';
    $html .=     '<div>コラム</div>';
    $html .= '  </h2>';

    $args = array(
      'posts_per_page' => 3,
      'post_status' => 'publish',
      'post_type' => 'post',
      'orderby' => 'DATE',
      'order' => 'DESC',
    );
    $posts = get_posts($args);
    if ( !empty($posts) && is_array($posts) ) {
      $html .= '<div class="ws-columns">';
      foreach ( $posts as $p ) {
        $html .= '<a class="ws-column" href="' . get_permalink($p) . '">';
        $html .= '<div class="ws-column-eyecatch">';
        if ( has_post_thumbnail($p) ) {
          $html .=  '<img src="' . get_the_post_thumbnail_url( $p->ID, 'thumb' ) . '" alt="eyecatch" />';
        }
        else {
          $html .=  '<img src="' . $this->ws_plugin_url . '/ws-clinic/images/no-image.png" alt="eyecatch" />';
        }
        $html .= '</div>';
        $html .= '<div class="ws-column-date">';
        $html .= date('Y/m/d', strtotime($p->post_date));
        $html .= '</div>';
        $html .= '<div class="ws-column-title">';
        $html .= esc_html($p->post_title);
        $html .= '</div>';
        $html .= '</a>';
      }
      $html .= '</div>';
    }

    $html .= '  <a href="#" class="more-button">';
    $html .=     '<picture>';
    $html .=     '  <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/icon-more-1x.png" width="127" height="15" alt="more icon" />';
    $html .=     '</picture>';
    $html .= '  </a>';
    $html .= '</section>';

    $html .= '<section class="ws-content bg-cyan">';
    $html .= '  <h2 class="ws-head sp-small">';
    $html .=     '<div><img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-icon2-1x.png" width="30" height="26" class="h2-icon" alt="" /></div>';
    $html .=     '<div>全国で活躍する歯科医師のインタビュー</div>';
    $html .= '  </h2>';

    if ( !empty($metas['ws-pickups']) && is_array($metas['ws-pickups']) ) {
      $html .= '<div class="ws-doctors">';
      foreach ( $metas['ws-pickups'] as $pickup ) {
        $html .= '<a class="ws-doctor" href="' . get_permalink($pickup) . '">';
        $html .= '<div class="ws-doctor-eyecatch">';
        if ( has_post_thumbnail($pickup) ) {
          $html .=  '<img src="' . get_the_post_thumbnail_url( $pickup->ID, 'thumb' ) . '" alt="eyecatch" />';
        }
        else {
          $html .=  '<img src="' . $this->ws_plugin_url . '/ws-clinic/images/no-image.png" alt="eyecatch" />';
        }
        $html .= '</div>';
        $html .= '<div class="ws-doctor-date">';
        $html .= date('Y/m/d', strtotime($pickup->post_date));
        $html .= '</div>';
        $html .= '<div class="ws-doctor-title">';
        $html .= esc_html($pickup->post_title);
        $html .= '</div>';
        $html .= '</a>';
      }
      $html .= '</div>';
    }

    $html .= '  <a href="#" class="more-button">';
    $html .=     '<picture>';
    $html .=     '  <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/icon-more-1x.png" width="127" height="15" alt="more icon" />';
    $html .=     '</picture>';
    $html .= '  </a>';

    $html .= '  <h3 class="ws-head">エリアから歯科医院を探す</h3>';
    $html .= '  <div class="area-buttons">';
    $html .=     '<a href="' . esc_url(home_url('/area/area-hokkaido')) . '" class="area-button">';
    $html .=     '  北海道(5)';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/area/area-tohoku')) . '" class="area-button">';
    $html .=     '  東北(2)';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/area/area-kanto')) . '" class="area-button">';
    $html .=     '  関東(100)';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/area/area-tokai-koshinetsu-hokuriku')) . '" class="area-button">';
    $html .=     '  東海・甲信越・北陸(17)';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/area/area-kinki')) . '" class="area-button">';
    $html .=     '  近畿(10)';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/area/area-chugoku')) . '" class="area-button">';
    $html .=     '  中国(3)';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/area/area-shikoku')) . '" class="area-button">';
    $html .=     '  四国(5)';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/area/area-kyushu')) . '" class="area-button">';
    $html .=     '  九州・沖縄(25)';
    $html .=     '</a>';
    $html .= '  </div>';
    $html .= '</section>';

    $html .= '<section class="ws-content bg-white">';
    $html .= '  <h2 class="ws-head">';
    $html .=     '<div><img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-icon3-1x.png" width="30" height="26" class="h2-icon" alt="" /></div>';
    $html .=     '<div>治療メニュー</div>';
    $html .= '  </h2>';
    $html .= '  <div class="cure-box">';
    $html .=     '<a href="' . esc_url(home_url('/cure/tooth-decay')) . '" class="cure-menu">';
    $html .=     '  <div class="cure-img">';
    $html .=     '    <picture>';
    $html .=     '      <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-menu1-1x.jpg" width="142" height="71" alt="menu img" />';
    $html .=     '    </picture>';
    $html .=     '  </div>';
    $html .=     '  <div class="cure-title">';
    $html .=     '    虫歯治療';
    $html .=     '  </div>';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/cure/aesthetic')) . '" class="cure-menu">';
    $html .=     '  <div class="cure-img">';
    $html .=     '    <picture>';
    $html .=     '      <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-menu5-1x.jpg" width="142" height="71" alt="menu img" />';
    $html .=     '    </picture>';
    $html .=     '  </div>';
    $html .=     '  <div class="cure-title">';
    $html .=     '    審美治療';
    $html .=     '  </div>';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/cure/pediatric')) . '" class="cure-menu">';
    $html .=     '  <div class="cure-img">';
    $html .=     '    <picture>';
    $html .=     '      <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-menu2-1x.jpg" width="142" height="71" alt="menu img" />';
    $html .=     '    </picture>';
    $html .=     '  </div>';
    $html .=     '  <div class="cure-title">';
    $html .=     '    小児歯科';
    $html .=     '  </div>';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/cure/orthodontics')) . '" class="cure-menu">';
    $html .=     '  <div class="cure-img">';
    $html .=     '    <picture>';
    $html .=     '      <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-menu6-1x.jpg" width="142" height="71" alt="menu img" />';
    $html .=     '    </picture>';
    $html .=     '  </div>';
    $html .=     '  <div class="cure-title">';
    $html .=     '    矯正治療';
    $html .=     '  </div>';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/cure/preventive')) . '" class="cure-menu">';
    $html .=     '  <div class="cure-img">';
    $html .=     '    <picture>';
    $html .=     '      <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-menu3-1x.jpg" width="142" height="71" alt="menu img" />';
    $html .=     '    </picture>';
    $html .=     '  </div>';
    $html .=     '  <div class="cure-title">';
    $html .=     '    予防歯科';
    $html .=     '  </div>';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/cure/implant')) . '" class="cure-menu">';
    $html .=     '  <div class="cure-img">';
    $html .=     '    <picture>';
    $html .=     '      <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-menu7-1x.jpg" width="142" height="71" alt="menu img" />';
    $html .=     '    </picture>';
    $html .=     '  </div>';
    $html .=     '  <div class="cure-title">';
    $html .=     '    インプラント治療';
    $html .=     '  </div>';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/cure/periodontal')) . '" class="cure-menu">';
    $html .=     '  <div class="cure-img">';
    $html .=     '    <picture>';
    $html .=     '      <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-menu4-1x.jpg" width="142" height="71" alt="menu img" />';
    $html .=     '    </picture>';
    $html .=     '  </div>';
    $html .=     '  <div class="cure-title">';
    $html .=     '    歯周病治療';
    $html .=     '  </div>';
    $html .=     '</a>';
    $html .=     '<a href="' . esc_url(home_url('/cure/root-canal')) . '" class="cure-menu">';
    $html .=     '  <div class="cure-img">';
    $html .=     '    <picture>';
    $html .=     '      <img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-menu8-1x.jpg" width="142" height="71" alt="menu img" />';
    $html .=     '    </picture>';
    $html .=     '  </div>';
    $html .=     '  <div class="cure-title">';
    $html .=     '    根管治療';
    $html .=     '  </div>';
    $html .=     '</a>';
    $html .= '  </div>';
    $html .= '</section>';

    $html .= '<section class="ws-content bg-cyan">';
    $html .= '  <h2 class="ws-head">';
    $html .=     '<div><img decoding="async" loading="lazy" src="' . $this->ws_plugin_url . '/ws-clinic/images/top-icon4-1x.png" width="30" height="26" class="h2-icon" alt="" /></div>';
    $html .=     '<div>クリニック検索</div>';
    $html .= '  </h2>';
    $html .= '  <p>';
    $html .=     '施術内容やそこで働く先生のインタビュー記事を掲載しています。';
    $html .= '  </p>';
    $html .= '  <div class="search-box">';
    $html .=     '<div class="search-map">';
    $html .=     '  <input type="checkbox" class="map-check" id="map-hokkaido" />';
    $html .=     '  <label class="map hokkaido" for="map-hokkaido">北海道</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/hokkaido')) . '">北海道</a>';
    $html .=     '  </div>';
    $html .=     '  <input type="checkbox" class="map-check" id="map-tohoku" />';
    $html .=     '  <label class="map tohoku"   for="map-tohoku"  >東北</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/aomori'))  . '">青森</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/iwate'))   . '">岩手</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/miyagi'))  . '">宮城</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/akita'))   . '">秋田</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/yamagata')) . '">山形</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/fukushima')) . '">福島</a>';
    $html .=     '  </div>';
    $html .=     '  <input type="checkbox" class="map-check" id="map-kanto" />';
    $html .=     '  <label class="map kanto"    for="map-kanto"   >関東</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/tokyo'))   . '">東京</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/kanagawa')) . '">神奈川</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/chiba'))   . '">千葉</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/saitama')) . '">埼玉</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/ibaraki')) . '">茨城</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/tochigi')) . '">栃木</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/gunma'))   . '">群馬</a>';
    $html .=     '  </div>';
    $html .=     '  <input type="checkbox" class="map-check" id="map-hokuriku" />';
    $html .=     '  <label class="map hokuriku" for="map-hokuriku">甲信越・北陸</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/yamanashi')) . '">山梨</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/nagano'))   . '">長野</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/niigata'))  . '">新潟</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/toyama'))   . '">富山</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/ishikawa')) . '">石川</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/fukui'))    . '">福井</a>';
    $html .=     '  </div>';
    $html .=     '  <input type="checkbox" class="map-check" id="map-tokai" />';
    $html .=     '  <label class="map tokai"    for="map-tokai"   >東海</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/aichi'))   . '">愛知</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/gifu'))    . '">岐阜</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/mie'))     . '">三重</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/shizuoka')) . '">静岡</a>';
    $html .=     '  </div>';
    $html .=     '  <input type="checkbox" class="map-check" id="map-kansai" />';
    $html .=     '  <label class="map kansai"   for="map-kansai"  >関西</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/osaka'))  . '">大阪</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/hyogo'))  . '">兵庫</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/kyoto'))  . '">京都</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/shiga'))  . '">滋賀</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/nara'))   . '">奈良</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/wakayam')) . '">和歌山</a>';
    $html .=     '  </div>';
    $html .=     '  <input type="checkbox" class="map-check" id="map-chugoku" />';
    $html .=     '  <label class="map chugoku"  for="map-chugoku" >中国</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/hiroshima')) . '">広島</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/okayama'))  . '">岡山</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/tottori'))  . '">鳥取</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/shimane'))  . '">島根</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/yamaguchi')) . '">山口</a>';
    $html .=     '  </div>';
    $html .=     '  <input type="checkbox" class="map-check" id="map-shikoku" />';
    $html .=     '  <label class="map shikoku"  for="map-shikoku" >四国</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/kagawa'))   . '">香川</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/tokushima')) . '">徳島</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/ehime'))    . '">愛媛</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/kochi'))    . '">高知</a>';
    $html .=     '  </div>';
    $html .=     '  <input type="checkbox" class="map-check" id="map-kyushu" />';
    $html .=     '  <label class="map kyushu"   for="map-kyushu"  >九州・沖縄</label>';
    $html .=     '  <div class="ws-popup">';
    $html .=     '    <a href="' . esc_url(home_url('/area/fukuoka'))  . '">福岡</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/saga'))     . '">佐賀</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/nagasaki')) . '">長崎</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/kumamoto')) . '">熊本</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/oita'))     . '">大分</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/miyagi'))   . '">宮城</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/kagoshima')) . '">鹿児島</a>';
    $html .=     '    <a href="' . esc_url(home_url('/area/okinawa'))  . '">沖縄</a>';
    $html .=     '  </div>';
    $html .=     '  <label class="map okinawa"  for="map-okinawa" ></label>';
    $html .=     '</div>';
    $html .=     '<div class="search-area">';
    $html .=     '  <div class="area-flex">';
    $html .=     '    <div class="area-name">';
    $html .=     '      北海道・東北';
    $html .=     '    </div>';
    $html .=     '    <div class="area-pref">';
    $html .=     '      <a href="' . esc_url(home_url('/area/hokkaido')) . '">北海道</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/aomori'))   . '">青森</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/iwate'))    . '">岩手</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/miyagi'))   . '">宮城</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/akita'))    . '">秋田</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/yamagata')) . '">山形</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/fukushima')) . '">福島</a>';
    $html .=     '    </div>';
    $html .=     '  </div>';
    $html .=     '  <div class="area-flex">';
    $html .=     '    <div class="area-name">';
    $html .=     '      関東';
    $html .=     '    </div>';
    $html .=     '    <div class="area-pref">';
    $html .=     '      <a href="' . esc_url(home_url('/area/tokyo'))   . '">東京</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/kanagawa')) . '">神奈川</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/chiba'))   . '">千葉</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/saitama')) . '">埼玉</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/ibaraki')) . '">茨城</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/tochigi')) . '">栃木</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/gunma'))   . '">群馬</a>';
    $html .=     '    </div>';
    $html .=     '  </div>';
    $html .=     '  <div class="area-flex">';
    $html .=     '    <div class="area-name">';
    $html .=     '      甲信越・北陸';
    $html .=     '    </div>';
    $html .=     '    <div class="area-pref">';
    $html .=     '      <a href="' . esc_url(home_url('/area/yamanashi')) . '">山梨</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/nagano'))   . '">長野</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/niigata'))  . '">新潟</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/toyama'))   . '">富山</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/ishikawa')) . '">石川</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/fukui'))    . '">福井</a>';
    $html .=     '    </div>';
    $html .=     '  </div>';
    $html .=     '  <div class="area-flex">';
    $html .=     '    <div class="area-name">';
    $html .=     '      東海';
    $html .=     '    </div>';
    $html .=     '    <div class="area-pref">';
    $html .=     '      <a href="' . esc_url(home_url('/area/aichi'))   . '">愛知</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/gifu'))    . '">岐阜</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/mie'))     . '">三重</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/shizuoka')) . '">静岡</a>';
    $html .=     '    </div>';
    $html .=     '  </div>';
    $html .=     '  <div class="area-flex">';
    $html .=     '    <div class="area-name">';
    $html .=     '      関西';
    $html .=     '    </div>';
    $html .=     '    <div class="area-pref">';
    $html .=     '      <a href="' . esc_url(home_url('/area/osaka'))   . '">大阪</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/hyogo'))   . '">兵庫</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/kyoto'))   . '">京都</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/shiga'))   . '">滋賀</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/nara'))    . '">奈良</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/wakayama')) . '">和歌山</a>';
    $html .=     '    </div>';
    $html .=     '  </div>';
    $html .=     '  <div class="area-flex">';
    $html .=     '    <div class="area-name">';
    $html .=     '      中国・四国';
    $html .=     '    </div>';
    $html .=     '    <div class="area-pref">';
    $html .=     '      <a href="' . esc_url(home_url('/area/hiroshima')) . '">広島</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/okayama'))  . '">岡山</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/tottori'))  . '">鳥取</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/shimane'))  . '">島根</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/yamaguchi')) . '">山口</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/kagawa'))   . '">香川</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/tokushima')) . '">徳島</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/ehime'))    . '">愛媛</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/kochi'))    . '">高知</a>';
    $html .=     '    </div>';
    $html .=     '  </div>';
    $html .=     '  <div class="area-flex">';
    $html .=     '    <div class="area-name">';
    $html .=     '      九州・沖縄';
    $html .=     '    </div>';
    $html .=     '    <div class="area-pref">';
    $html .=     '      <a href="' . esc_url(home_url('/area/fukuoka'))  . '">福岡</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/saga'))     . '">佐賀</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/nagasaki')) . '">長崎</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/kumamoto')) . '">熊本</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/oita'))     . '">大分</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/miyagi'))   . '">宮城</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/kagoshima')) . '">鹿児島</a>';
    $html .=     '      <a href="' . esc_url(home_url('/area/okinawa'))  . '">沖縄</a>';
    $html .=     '    </div>';
    $html .=     '  </div>';
    $html .=     '</div>';
    $html .= '  </div>';
    $html .= '</section>';

    $html .= PHP_EOL;

    $this->ws_cache_html['page-'.$post_name] = $html;

    return $html;
  }


  ////////////////////////////////////////
  //    アーカイブショートコード
  function ws_shortcode_archive( $atts, $content, $tag ) {

    $attsval = shortcode_atts( array(
      'post-type' => 'ws-clinic',
    ), $atts );

    $post_type = $attsval['post-type'];

    if ( !empty($this->ws_cache_html['archive-'.$post_type]) ) {
      return $this->ws_cache_html['archive-'.$post_type];
    }

    $html  = '<section class="ws-clinic-area">';

    // $html .= '<h2>Archive Page</h2>';
    // $html .= '<h3>Post Type = ' . $post_type . '</h3>';

    $paged = get_query_var('paged') ? get_query_var('paged') : 1;

    $args = array(
      'post_type'      => array( $post_type ),
      'post_status'    => array( 'publish' ),
      'order'          => 'DESC',
      'orderby'        => 'post_date',
      'paged'          => $paged,
      'posts_per_page' => $this->ws_posts_per_page,
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
      foreach ( $query->posts as $post ) {
        $html .= $this->ws_show_clinic_one( $post );
      }
    }

    $big = 9999999999;
    $html .= paginate_links(array(
      'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
      'show_all'  => true,
      'type'      => 'list',
      'format'    => '?paged=%#%',
      'current'   => max(1, get_query_var('paged')),
      'total'     => $query->max_num_pages,
      'prev_text' => '≪', // '前へ',
      'next_text' => '≫', // '次へ',
    ));


    $html .= '<pre>';
    $html .= print_r($query, true);
    $html .= '</pre>';

    $html .= '</section>' . PHP_EOL; // class="ws-clinic-area"

    $this->ws_cache_html['archive-'.$post_type] = $html;

    return $html;
  }


  ////////////////////////////////////////
  //    投稿ページショートコード
  function ws_shortcode_single( $atts, $content, $tag ) {

    $attsval = shortcode_atts( array(
      'post-type' => 'ws-clinic',
    ), $atts );

    $post_type = $attsval['post-type'];

    if ( !empty($this->ws_cache_html['single-'.$post_type]) ) {
      return $this->ws_cache_html['single-'.$post_type];
    }

    $html  = '<section class="ws-clinic-area">';

    // $html .= '<h2>Single Page</h2>';
    // $html .= '<h3>Post Type = ' . $post_type . '</h3>';

    global $wpdb;
    global $post;

    // 医院情報カスタム投稿タイプ
    if ( $post_type == $this->ws_post_type ) {

      // 医院情報DBから取得
      $results = $this->ws_get_clinic_db( array( $post->ID, $post->ID ) );

      $html .= $this->ws_show_clinic_detail( $post, ( !empty($results) && !empty($results[0]) ? $results[0] : false ) );

    }

    // インタビューカスタム投稿タイプ
    else if ( $post_type == $this->ws_post_type_interview ) {

      $html .= $this->ws_show_interview($post);
      // $html .= '<div>' . apply_filters( 'the_content', $post->post_content ) . '</div>';

    }

    $html .= '</section>' . PHP_EOL; // class="ws-clinic-area"

    $this->ws_cache_html['single-'.$post_type] = $html;

    return $html;
  }


  ////////////////////////////////////////
  //    タクソノミーショートコード
  function ws_shortcode_taxonomy( $atts, $content, $tag ) {

    $attsval = shortcode_atts( array(
      'taxonomy' => 'ws-clinic-area',
    ), $atts );

    $taxonomy = $attsval['taxonomy'];

    if ( !empty($this->ws_cache_html['taxonomy-'.$taxonomy]) ) {
      return $this->ws_cache_html['taxonomy-'.$taxonomy];
    }

    $html  = '<section class="ws-clinic-area">';

    $term_name = single_term_title('', false);

    $html .= '<h2>' . $taxonomy . ' : ' . $term_name . '</h2>';

    // $html .= '<h2>Taxonomy Archive Page</h2>';
    // $html .= '<h3>Taxonomy = ' . $taxonomy . '</h3>';
    // $term = get_queried_object();
    // $html .= '<pre>' . print_r($term, true) . '</pre>';    

    $paged = get_query_var('paged') ? get_query_var('paged') : 1;

    $args = array(
      'post_type'      => array( 'ws-clinic' ),
      'post_status'    => array( 'publish' ),
      'order'          => 'DESC',
      'orderby'        => 'post_date',
      'paged'          => $paged,
      'posts_per_page' => $this->ws_posts_per_page,
      'tax_query' => array(
        array(
          'taxonomy' => $taxonomy,
          'field' => 'name',
          'terms' => $term_name,
        ),
      ),
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
      foreach ( $query->posts as $post ) {
        $html .= $this->ws_show_clinic_one( $post );
      }
    }

    $big = 9999999999;
    $html .= paginate_links(array(
      'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
      'show_all'  => true,
      'type'      => 'list',
      'format'    => '?paged=%#%',
      'current'   => max(1, get_query_var('paged')),
      'total'     => $query->max_num_pages,
      'prev_text' => '≪', // '前へ',
      'next_text' => '≫', // '次へ',
    ));

    // $term = get_the_term();
    // $html .= '<pre>';
    // $html .= print_r($term, true);
    // $html .= '</pre>';

    $html .= '</section>' . PHP_EOL; // class="ws-clinic-area"

    $this->ws_cache_html['taxonomy-'.$taxonomy] = $html;

    return $html;
  }


  ////////////////////////////////////////////////////////////
  //    医院詳細表示
  function ws_show_clinic_detail( $post = false, $result = false ) {
    if ( $post === false ) return '';

    $html = '';

    $html .= '<div class="ws-clinic-single">';

    // 医院名
    $html .= '<h1><a href="' . get_permalink($post->ID) . '">' . esc_html($post->post_title) . '</a></h1>';

    // 地域
    $terms = wp_get_object_terms( $post->ID, $this->ws_clinic_area );
    if ( !empty($terms) ) {
      $arr = array();
      foreach ( $terms as $term ) {
        $link = get_term_link($term);
        $arr[] = '<a href="' . esc_url($link) . '">' . esc_html($term->name) . '</a>';
      }
      $html .= '<div class="ws-head">地域</div>';
      $html .= '<div class="ws-cats">' . implode('', $arr ) . '</div>';
    }

    // 治療メニュー
    $terms = wp_get_object_terms( $post->ID, $this->ws_clinic_cure );
    if ( !empty($terms) ) {
      $arr = array();
      foreach ( $terms as $term ) {
        $link = get_term_link($term);
        $arr[] = '<a href="' . esc_url($link) . '">' . esc_html($term->name) . '</a>';
      }
      $html .= '<div class="ws-head">治療メニュー</div>';
      $html .= '<div class="ws-cats">' . implode('', $arr ) . '</div>';
    }

    // 診療科目
    $terms = wp_get_object_terms( $post->ID, $this->ws_clinic_speciality );
    if ( !empty($terms) ) {
      $arr = array();
      foreach ( $terms as $term ) {
        $link = get_term_link($term);
        $arr[] = '<a href="' . esc_url($link) . '">' . esc_html($term->name) . '</a>';
      }
      $html .= '<div class="ws-head">診療科目</div>';
      $html .= '<div class="ws-cats">' . implode('', $arr ) . '</div>';
    }

    if ( $result !== false ) {

      // 画像
      if ( !empty($result['photo1']) ) {
        $html .= '<div class="ws-head">医院画像</div>';
        $html .= '<div class="ws-photos">';
        for ( $no = 1 ; $no <= 4 ; $no++ ) {
          if ( !empty($result['photo'.$no]) ) {
            $html .= '<div class="ws-photo">';
            $html .= '<img src="' . wp_get_attachment_image_url( $result['photo'.$no], 'full' ) . '" />';
            $html .= '</div>';
          }
        }
        $html .= '</div>';
      }

      // 住所
      if ( !empty($result['zipcode']) && !empty($result['address']) ) {
        $html .= '<div class="ws-head">住所</div>';
        $html .= '<div class="ws-contents">';
        $html .=  esc_html( $result['zipcode'] ?? '' );
        $html .=  esc_html( $result['address'] ?? '' );
        $html .= '</div>';
      }

      // 電話番号
      if ( !empty($result['tel1']) ) {
        $html .= '<div class="ws-head">電話番号</div>';
        $html .= '<div class="ws-contents">';
        $html .=  '<a href="tel:' . esc_attr($result['tel1']) . '">' . esc_html( $result['tel1'] ) . '</a>';
        $html .= '</div>';
      }
    }

    $html .= '</div>' . PHP_EOL; // class="ws-clinic-single"

    return $html;
  }


  ////////////////////////////////////////////////////////////
  //    医院１アイテム表示
  function ws_show_clinic_one( $post = false, $result = false ) {
    $html  = '<div class="ws-clinic-item">';

    if ( $post !== false ) {
      $html .= '<h3><a href="' . get_permalink($post->ID) . '">' . esc_html($post->post_title) . '</a></h3>';

      $terms = wp_get_object_terms( $post->ID, $this->ws_clinic_area );
      if ( !empty($terms) ) {
        $html .= '<p>地域 : ';
        $arr = array();
        foreach ( $terms as $term ) {
          $arr[] = $term->name;
        }
        $html .= esc_html( implode(', ', $arr ) ) . '</p>';
      }

      $terms = wp_get_object_terms( $post->ID, $this->ws_clinic_cure );
      if ( !empty($terms) ) {
        $html .= '<p>治療メニュー : ';
        $arr = array();
        foreach ( $terms as $term ) {
          $arr[] = $term->name;
        }
        $html .= esc_html( implode(', ', $arr ) ) . '</p>';
      }

      $terms = wp_get_object_terms( $post->ID, $this->ws_clinic_speciality );
      if ( !empty($terms) ) {
        $html .= '<p>診療科目 : ';
        $arr = array();
        foreach ( $terms as $term ) {
          $arr[] = $term->name;
        }
        $html .= esc_html( implode(', ', $arr ) ) . '</p>';
      }
    }
    if ( $result !== false ) {
      for ( $no = 1 ; $no <= 4 ; $no++ ) {
        if ( !empty($result['photo'.$no]) ) {
          $html .= '<p>画像'.$no.' : <img style="width:auto;height:4.0rem;" src="' . wp_get_attachment_image_url( $result['photo'.$no], 'thumbnail' ) . '" /></p>';
        }
      }
      // $html .= '<p>都道府県 : ' . esc_html( $result['area'] ?? '' ) . '</p>';
      $html .= '<p>郵便番号 : ' . esc_html( $result['zipcode'] ?? '' ) . '</p>';
      $html .= '<p>住所 : ' . esc_html( $result['address'] ?? '' ) . '</p>';
      $html .= '<p>電話1 : ' . esc_html( $result['tel1'] ?? '' ) . '</p>';
      $html .= '<p>電話2 : ' . esc_html( $result['tel2'] ?? '' ) . '</p>';
    }

    $html .= '</div>' . PHP_EOL; // class="ws-clinic-item"

    return $html;
  }


  ////////////////////////////////////////////////////////////
  //    インタビュー表示
  function ws_show_interview( $post = false ) {

    $html = '';

    if ( $post !== false ) {

      $metas = ( function_exists('get_fields') ? get_fields($post->ID) : array() );

      $html .= '<ul class="ws-clinic-breadcrumbs">';
      $html .=  '<li><a href="' . esc_url(home_url()) . '"><img src="' . $this->ws_plugin_url. 'images/icon-home.svg" /></a></li>';
      $html .=  '<li>&gt;</li>';
      $html .=  '<li><a href="' . esc_url(home_url('/interview')) . '">インタビュー</a></li>';
      $html .=  '<li>&gt;</li>';
      $html .=  '<li>' . esc_html($post->post_title). '</li>';
      $html .= '</ul>';

      $html .= '<div class="ws-clinic-interview">';

      // サムネ（アイキャッチ）
      if ( has_post_thumbnail($post) ) {
        $html .= '<div class="ws-clinic-eyecatch">';
        $html .= '<img src="' . get_the_post_thumbnail_url( $post->ID, 'full' ) . '" alt="eyecatch" />';
        $html .= '</div>';
      }

      // 関連する医院情報
      $related = $metas['ws-related-post'] ?? false;
      if ( $related ) {
        $terms = wp_get_object_terms( $related->ID, $this->ws_clinic_area );
        if ( $terms ) {
          $html .= '<div class="ws-clinic-areas">';
          foreach ( $terms as $term ) {
            $html .= '<a href="' . get_term_link($term) .  '" class="ws-clinic-area">';
            $html .= esc_html($term->name);
            $html .= '</a>';
          }
          $html .= '</div>';
        }
      }

      // 医院名
      $html .= '<h1 class="ws-clinic-h1">';
      $html .=   esc_html($post->post_title);
      $html .= '</h1>';

      if ( false ) :

      $html .= '<div class="ws-interview-mv-small">- 歯科医師インタビュー -</div>';
      $html .= '<div class="ws-interview-mv-title">INTERVIEW</div>';

      // 関連する医院情報
      $related = $metas['ws-related-post'] ?? false;
      // if ( !empty($related) ) {
      //   $html .= '<h1><a href="' . get_permalink($related->ID) .  '">' . esc_html($related->post_title) . '</a></h1>';
      // }

      // 医師紹介
      if ( !empty($metas['ws-doctor']) ) {
        $html .= '<div class="ws-clinic-doctor">';
        $html .=  '<div class="ws-doctor-box">';
        $html .=   '<h2>歯科医師紹介</h2>';
        $html .=   '<div class="ws-doctor-flex">';
        $html .=    '<div class="ws-doctor-img">';
        if ( !empty($metas['ws-doctor']['ws-photo']) ) {
          $html .=    '<img src="' . esc_url($metas['ws-doctor']['ws-photo']) . '" />';
        }
        $html .=   '</div>';
        $html .=   '<div class="ws-doctor-info">';
        $html .=    '<div class="ws-doctor-head">' . esc_html( $related ? ( $related->post_title ?? '' ) : '' ) . '</div>';
        $html .=    '<div class="ws-doctor-head">' . esc_html( $metas['ws-doctor']['ws-work-title'] ) . ' ' . esc_html( $metas['ws-doctor']['ws-name'] ) . '</div>';
        $html .=    '<hr/>';
        $html .=    '<h5>経歴</h5>';
        $arr = preg_split( '/\r\n|\r|\n/', $metas['ws-doctor']['ws-career'] ?? '');
        $tmphtml = '';
        foreach ( $arr as $line ) {
          $tmphtml .= esc_html($line) . '<br/>';
        }
        $html .=    '<div class="ws-doctor-text">' . $tmphtml . '</div>';
        $html .=    '<h5>所属学会等</h5>';
        $arr = preg_split( '/\r\n|\r|\n/', $metas['ws-doctor']['ws-academy'] ?? '');
        $tmphtml = '';
        foreach ( $arr as $line ) {
          $tmphtml .= esc_html($line) . '<br/>';
        }
        $html .=    '<div class="ws-doctor-text">' . $tmphtml . '</div>';
        $html .=   '</div>';
        $html .=  '</div>';
        $html .= '</div>';
      }

      // サムネ（アイキャッチ）
      if ( has_post_thumbnail($post) ) {
        $html .= '<div class="ws-clinic-eyecatch">';
        $html .= '<img src="' . get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) . '" alt="eyecatch" />';
        $html .= '</div>';
      }

      endif;

      // 質問と回答
      for ( $no = 1; $no < 10; $no++ ) {
        if ( !empty($metas['ws-faq-'.$no]) ) {
          if ( !empty($metas['ws-faq-'.$no]['ws-faq-q']) &&
               $metas['ws-faq-'.$no]['ws-faq-q'] != '～ 選択してください ～' ) {
            $html .= '<h2>' . esc_html($metas['ws-faq-'.$no]['ws-faq-q']) . '</h2>';
          }
          if ( !empty($metas['ws-faq-'.$no]['ws-faq-a']) ) {
            $html .= '<div class="ws-interview-contents">' . $metas['ws-faq-'.$no]['ws-faq-a'] . '</div>';
          }
        }
      }

      // 関係する個別ページ
      if ( !empty($related) ) {
        $results = $this->ws_get_clinic_db( array( $related->ID, $related->ID ) );
        $html .= $this->ws_show_clinic_detail( $related, ( !empty($results) && !empty($results[0]) ? $results[0] : false ) );
      }

      $html .= '</div>' . PHP_EOL; // class="ws-clinic-interview"

    } // if $post

    return $html;
  }


  ////////////////////////////////////////////////////////////
  //    データベースから医院情報を取得
  function ws_get_clinic_db( $post_ids = false ) {

    if ( $post_ids === false ) return false;

    global $wpdb;

    if ( is_array($post_ids) && count($post_ids) > 1 ) {
      $wheres = array_fill( 0, count($post_ids), '%d' );
      $sql   = 'SELECT * FROM '.$this->ws_db_table1.
               ' WHERE postid IN (' . implode(',', $wheres) . ')';
      $query = $wpdb->prepare( $sql,
                               $post_ids,
                              );
    }
    else {
      $sql   = 'SELECT * FROM '.$this->ws_db_table1.
               ' WHERE postid=%d';
      $query = $wpdb->prepare( $sql,
                               array(
                                 $post_ids,
                               )
                             );
    }

    $results = $wpdb->get_results($query, ARRAY_A);

    return $results;
  }


} // class WsFrontLoginShortcode


?>