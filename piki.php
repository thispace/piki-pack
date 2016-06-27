<?php
/*
	Plugin Name: Piki
	Description: Adicina algumas features usadas em vários Piki's temas e plugins
	Version: 0.1
	Author: Thiago Borges (Piki)
	Author URI: http://pikiweb.com.br
	Author Email: thiago@pikiweb.com.br
*/

# Inclui as features obrigatórias
require_once( plugin_dir_path( __FILE__ ) . 'features/Meta/index.php' );

# Caminho para o arquivo de include de features
define( 'PIKI_FEATURES_FILE', plugin_dir_path( __FILE__ ) . 'features.php' );
# Carrega as Features
require_once( PIKI_FEATURES_FILE );

class Piki {

    public static function jpeg_quality(){
        return 100;
    }

    public static function init(){
        if( isset( $_GET[ 'piki-empty-cache' ] ) ):
            self::cache_clear();
        endif;
        if( !is_admin() ):
            show_admin_bar( false );
        endif;
    }

    public static function thumbnail( $postID, $width, $height=null, $zc=1 ){
        $image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $postID ), 'full' );
        if ( !$image_url ):
            return false;       
        endif;
        return self::image_resize( $width, $height, $image_url[0], $zc );
    }
    public static function image_resize( $width, $height, $img_url, $zc=1 ){
        $quality = 100;
        $url = get_template_directory_uri();
        $sizes = '';
        if( $width !== 0 || $height === 0 ):
          $sizes .= '&w='.$width;
        endif;
        if( $height !== 0 ):
          $sizes .= '&h='.$height;
        endif;
        $img_url = $url.'/timthumb.php?src='.$img_url.$sizes.'&zc='.$zc.'&q='.$quality;
        return urldecode($img_url);
    }

    public static function get_post_image( $postID, $width, $height, $render=false, $rel=false ){

        $image_id = get_post_thumbnail_id( $postID );

        if( empty( $image_id ) ):
          return '';
        endif;

        $image = get_post( $image_id );
        if ( !is_object($image) ):
            return false;        
        endif;
        $url_image = self::image_resize( $width, $height, $image->guid );
        if ( !$render ):
            return array(
                'url' => $url_image,
                'title' => $image->post_title,
                'caption' => $image->post_excerpt,
            );
        else:
            return '<a href="'.$image->guid.'"'.(!$rel?'':' rel="'.$rel.'[]"').'><img src="'.$url_image.'" alt="'.$image->post_title.'" /></a>';     
        endif;
    }

    public static function get_cover( $postID=false ){
        if( !$postID ) $postID = get_the_ID();
        $image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $postID ), 'full');
        if( !$image_url ){
          return false;
        }
        return $image_url[ 0 ];
    }

    public static function post_images( $ID=false ){
        $attachments = get_posts( array(
          'post_type' => 'attachment',
          'posts_per_page' => -1,
          'post_parent' => !$ID ? get_the_ID() : $ID,
          'post_mime_type' => 'image',
        ));
        if( !$attachments ):
          return false;
        endif;
        return $attachments;
    }

    public static function attach_src( $attach_id, $width, $height=0 ){
        $image_data = wp_get_attachment_image_src( $attach_id, 'full' );
        return self::image_resize( $width, $height, $image_data[ 0 ] );
    }

    public static function get_attach_url( $postID, $meta_key, $size=full ){
      $meta = get_post_meta( $postID, $meta_key, true );
      $image_url = wp_get_attachment_image_src( $meta, $size );
      return $image_url[ 0 ];
    }

    public static function get_alphabetic_indice(){
      $letters = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
      $html = '<ul class="alpha-indice clearfix">';
      foreach ( $letters as $key => $letter ) {
        $html .= '<li><a href="#'.$letter.'" rel="'.$letter.'">'.$letter.'</a></li>';
      }
      $html .= '</ul>';
      return $html;
    }

    # Pages menu tree
    public static function get_menu_pages_tree( $parent ){
      $linkpre = $parent->post_content != '' ? '<a href="'. get_permalink( $parent->ID ) .'">' : '';
      $linkpos = $parent->post_content != '' ? '</a>' : '';
      return '<h2 class="ui-state-active">'.$linkpre.$parent->post_title.$linkpos.'</h2><ul>' . wp_list_pages(array(
        'child_of'    =>  $parent->ID,
        'depth'       =>  1,
        'sort_column' => 'menu_order',
        'echo'        =>  false,
        'title_li'    =>  '',
        'link_before' => '<span class="ico"></span>',
      )) . '</ul>';
    }

    # Retorna um array como json
    public static function return_json( $array ){
        header('HTTP/1.1 200 OK');
        header( 'Content-type: application/json; charset=utf-8' );
        echo json_encode( $array );
        die();
    }

    # Erro em json
    public static function error( $error_message=false, $error_type=false, $force_ajax=false ){
        if( $force_ajax || self::ajax() ):
            self::return_json(array( 
                'status' => 'error',
                'error_type' => $error_type,
                'error_message' => $error_message,
            ));
        else:
            $error = '<strong>ERROR</strong>';
            if ( $error_type ):
                $error .= '<br />Type: ' . $error_type;
            endif;
            if ( $error_message ):
                $error .= '<br />Message: ' . $error_message;
            endif;
            wp_die( __( $error ) );
        endif;
    }

    # Erro em json
    public static function success( $message=false, $redirect=false, $force_ajax=false, $extras=false ){
        if( $force_ajax || self::ajax() ):
            if( is_array( $message ) ):
                $message[ 'status' ] = 'success';
                self::return_json( $message );
            else:
                self::return_json(array( 
                    'status' => 'success',
                    'message' => $message,
                    'redirect' => $redirect,
                ));
            endif;
        else:
            $text = '<strong>SUCCESS</strong>';
            if ( $message ):
                $text .= '<br />' . $message;
            endif;
            if ( $redirect ):
                $text .= '<a href="'.$redirect.'">'.$redirect.'</a>';
            endif;
            wp_die( __( $text ) );
        endif;
    }

    public static function set404(){
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        return false;
    }

    # Javascripts
    public static function add_scripts(){
        self::add_library( 'loader-mask' );
        wp_enqueue_script( 'piki-scripts', plugins_url( '/piki.js', __FILE__ ), array( 'jquery' ), false, true );
        wp_enqueue_style( 'piki-styles', plugins_url( '/piki.css', __FILE__ ) );
    }
    
    # Javascript do header
    public static function head_configs(){
        echo '<script type="text/javascript">Piki={ blogurl : "'. get_site_url() .'", themeurl : "'. get_template_directory_uri() .'", pluginsurl : "'. plugins_url() .'", ajaxurl : "'. admin_url( 'admin-ajax.php' ) . '", is_admin : '. ( is_admin() ? 'true' : 'false' ) .' };</script>';
    }

    # Slug #home# para ítems do menu
    public static function nav_menu_items( $items ) {
        $items = str_replace( '#home#', get_bloginfo('url'), $items );
        return $items;
    }

    # Adiciona arquivos de uma library
    public static function add_library( $lib, $add_files=false ){

        if( is_array( $lib ) ):
            $dirname = trim( $lib[ 0 ] );
            $filename = trim( $lib[ 1 ] );
        else:
            $dirname = trim( $lib );
            $filename = trim( $lib );
        endif;
        $lib_dir = plugin_dir_path( __FILE__ ) . 'libraries/' . $dirname . '/';
        
        # Se a library existe
        if( !is_dir( $lib_dir ) ):
            return false;
        endif;

        # URL dos arquivos
        $lib_url = plugins_url( '/' , __FILE__ ) . 'libraries/' . $dirname . '/';
                
        # Javascript
        $js_min = $lib_url . $filename . '-min.js';
        $js_full = $lib_url . $filename . '.js';
        $toinclude = WP_DEBUG === TRUE || !file_exists( $js_min ) ? $js_full : $js_min;
        $toinclude = $js_full;
        wp_enqueue_script( $filename . '-script', $toinclude, array( 'jquery' ), false, true );
        
        # Styles
        if( file_exists( $lib_dir . $filename . '.css' ) ):
            wp_enqueue_style( $filename . '-style', $lib_url . $filename . '.css' );
        endif;
        
        # Arquivos adicionais
        if( is_array( $add_files ) && !empty( $add_files ) ):
            foreach( $add_files as $key => $filename ):
                $extension = pathinfo( $filename, PATHINFO_EXTENSION );
                $filename = pathinfo( $filename, PATHINFO_FILENAME );
                $add_url = $lib_url . $filename;
                if( $extension == 'js' ):
                    if( is_file( $add_url . '-min.js' ) ):
                        $add_url .= '-min';
                    endif;
                    wp_enqueue_script( $filename . '-scripts', $add_url . '.js', array( 'jquery' ) );
                else:
                    wp_enqueue_style( $filename . '-styles', $add_url . '.css' );
                endif;
            endforeach;
        endif;

    }

    public static function slug( $string, $length = -1, $separator = '_' ) {
        # transliterate
        $string = transliterate( $string );
        # replace non alphanumeric and non underscore charachters by separator
        $string = preg_replace('/[^a-z0-9]/i', $separator, $string);
        # replace multiple occurences of separator by one instance
        $string = preg_replace( '/' . preg_quote($separator) .'['. preg_quote($separator) .']*/', $separator, $string );
        # cut off to maximum length
        if ( $length > -1 && strlen( $string ) > $length ):
            $string = substr($string, 0, $length);
        endif;
        # remove separator from start and end of string
        $string = preg_replace( '/' . preg_quote( $separator ) . '$/', '', $string );
        $string = preg_replace( '/^' . preg_quote( $separator ) . '/', '', $string );
        return $string;
    }

    public static function ajax(){
      return isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest';
    }

    public static function http( $link ){
      $link = trim( $link );
      if( strpos( $link, 'http://' ) !== 0 ):
        $link = 'http://' . $link;
      endif;
      return $link;
    }

    public static function search_template( $basename, $subname=false, $plugin_uri, $exit=true ){
        
        # URI do tema atual
        $theme_uri = get_template_directory() . '/';
        # URI da pasta de templates do plugin
        $plugin_uri = rtrim( $plugin_uri, '/' ) . '/templates/';
        # Nome do arquivo
        $name_file = $basename . '.tpl.php';

        # Nome mais profundo do arquivo
        if( $subname ):
            $subname_file = $basename . '-' . $subname . '.tpl.php';
        endif;

        # Busca o template com a extensão na pasta do tema
        if( $subname && is_file( $theme_uri . $subname_file ) ):
            
            load_template( $theme_uri . $subname_file, false );
            if( $exit ): exit(); endif;
        
        # Busca o template genérico na pasta do tema
        elseif( is_file( $theme_uri . $name_file ) ):
            
            load_template( $theme_uri . $name_file, false );
            if( $exit ): exit(); endif;
        
        # Busca o template com a extensção na pasta do plugin
        elseif( is_file( $plugin_uri . $subname_file ) ):
            
            load_template( $plugin_uri . $subname_file, false );
            if( $exit ): exit(); endif;
        
        # Busca o template com a extensção na pasta do plugin
        else:
            
            load_template( $plugin_uri . $name_file, false );
            if( $exit ): exit(); endif;
        
        endif;

    }

    public static function check_http_method( $method, $die=true ){
        if ( strtoupper( $method ) != strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) ) {
            if( !$die ):
                return false;
            else:
                wp_die( __( 'O método usado não é permitido neste caso' ) );
            endif;
        }
        return true;
    }

    public static function plural( $total, $sing, $plural, $vazio='Nenhum ítem encontrado' ){
        if( (int)$total === 0 ){
            return $vazio;
        }
        return sprintf( _n( '<strong>1</strong> '. $sing, '<strong>%s</strong> '.$plural, $total, 'twentythirteen' ), $total );
    }

    public static function get_url_var( $url, $var ){
        $my_array_of_vars = array();
        parse_str( parse_url( $url, PHP_URL_QUERY ), $my_array_of_vars );
        if( isset( $my_array_of_vars[ $var ] ) ):
            return $my_array_of_vars[ $var ];
        else:
            return false;
        endif;
    }

    public static function trim( $text, $length = 55, $type = 'words' ){
        $no_shortcodes = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $text );
        $no_tags = wp_strip_all_tags( $text );
        if( $type == 'chars' ):
            if( strlen( $text ) <= $length ):
                return $text;
            endif;
            $striped = substr( $text, 0, $length );
        else:
            $striped = wp_trim_words( $no_tags, $length );
        endif;
        return $striped;
    }

    public static function last_comment_date( $post_id=false, $format=false ){
        global $wpdb;
        if( !$post_id ):
            $post_id = get_the_ID();
        endif;
        $date = $wpdb->get_var($wpdb->prepare("SELECT comment_date FROM $wpdb->comments WHERE comment_post_ID = %d ORDER BY comment_date DESC LIMIT 1", $post_id));
        return date_i18n( 'd M Y', strtotime( $date ) );
    }

    public static function url_to_path( $url ){
        $relative = ltrim( wp_make_link_relative( $url ), '/' );    
        if( ( $wpcontent = strpos( $relative, 'wp-content' ) ) !== 0 ):
            $relative = substr( $relative, $wpcontent );
        endif;
        return rtrim( ABSPATH, '/' ) . '/' . $relative;
        //$this->directory = rtrim( dirname( $this->realpath ), '/' );
    }

    public static function asu( $default, $cleaned ){
        # URL do site
        $baseurl = get_bloginfo( 'url' );
        # URL
        if( get_option( 'permalink_structure' ) == '' ):
            return $baseurl . $default;
        else:
            return $baseurl . $cleaned;
        endif;
    }

    public static function ago( $time=false ){

        if( !$time ):
            $time = get_the_time( 'U', true );
        endif;
       
        $periods = array( "segundo", "minuto", "hora", "dia", "semana", "mês", "ano", "década" );
        $lengths = array( "60", "60", "24", "7", "4.35", "12", "10" );

        $now = time();

        $difference     = $now - $time;
        $tense         = "atrás";

        for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
            $difference /= $lengths[$j];
        }

        $difference = round($difference);

        if($difference != 1) {
            $periods[$j].= "s";
        }

        return "$difference $periods[$j] atrás ";
    }

    public static function post_type(){
        static $post_types, $labels = '';
        empty( $post_types ) AND $post_types = get_post_types( 
            array( 
                'show_in_menu' => true,
                '_builtin'     => false,
            ),
            'objects'
        );
        empty( $labels ) AND $labels = wp_list_pluck( $post_types, 'labels' );
        $names = wp_list_pluck( $labels, 'singular_name' );
        $names[ 'page' ] = 'Página';
        $name = $names[ get_post_type() ];
        return $name;
    }

    public static function wp_head(){

        global $post;

        if( is_front_page() || is_null( $post ) ):
            
            $title = get_bloginfo( 'name' );
            $image = false;
            $description = get_bloginfo( 'description' );
            $url = get_site_url();
        
        else:

            $title = $post->post_title;
            $image = Piki::get_cover( $post->ID );

            # para peças
            if( $post->post_type == 'peca' ):
            
                $description = get_post_meta( $post->ID, 'chamada', true ) . '. ' . get_post_meta( $post->ID, 'detalhes_da_peca', true );
                $description = trim( str_replace( '..', '.', $description ) );

            # Restante
            else:

                $description = strip_tags( strip_shortcodes( $post->post_content ) );
                if( $description == '' ):
                    $description = get_post_meta( $post->ID, 'descricao', true );
                endif;

            endif;
            
            $url = get_permalink( $post->ID );
        endif;

        # Imagem padrão
        if( !$image ):
            $image = get_site_url() . '/logo-site.png';
        endif;

        # Open Graph Tags
        if( !is_admin() ):
        $og_tags = '<link rel="canonical" href="' . get_the_permalink() . '" />
        <meta property="og:locale" content="pt_BR" />
        <meta property="og:type" content="article" />
        <meta property="og:title" content="' . $title . '" />
        <meta property="og:description" content="' . $description . '" />
        <meta property="og:url" content="' . rtrim( $url, '/' ). '/" />
        <meta property="og:site_name" content="' . get_bloginfo( 'name' ) . '" />
        <meta property="og:image" content="' . $image . '" />';

        $og_tags = apply_filters( 'piki_graph_tags', $og_tags );

        echo $og_tags;

        endif;
    }

    public static function cache_clear(){
        global $wpdb;
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'piki_cache_%'" );
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'" );
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_%'" );
    }

    public static function cache_key( $key ){
        global $post;
        if( !$key ):
            $page = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
            return 'piki_cache_' . md5( $post->ID . '_page' . $page . '_' . $_SERVER['QUERY_STRING'] );
        else:
            return 'piki_cache_' . md5( $key );
        endif;
    }

    public static function cache_start( $key ){

        global $post;

        $cache_key = self::cache_key( $key );

        $cache_time = (int)get_option( $cache_key );
        
        $expires = $cache_time + ( 30 * MINUTE_IN_SECONDS );
        
        if( time() > $expires ):
            define( $cache_key . '_STARTED', true );
            ob_start();
            return false;
        else:
            $cache_dir = get_template_directory() . '/cache/piki';
            require_once( $cache_dir . '/' . $cache_key . '.html' );
            return true;
        endif;
    }

    public static function cache_end( $key ){
        
        global $post;

        $cache_key = self::cache_key( $key );

        # Cria o cache
        if( defined( $cache_key . '_STARTED' ) ):
            $content = ob_get_contents();
            ob_clean();
            $cache_dir = get_template_directory() . '/cache/piki';
            $pathfile = $cache_dir . '/' . $cache_key . '.html';
            # Escreve o arquivo
            if( file_exists( $pathfile ) ):
                try { unlink( $pathfile ); } 
                catch ( Exception $e ) { Piki::error( $e->getMessage() ); }
            endif;
            file_put_contents( $pathfile, $content, FILE_TEXT );
            require_once( $pathfile );
            update_option( $cache_key, time() );
        endif;
    }

    public static function fix_autop( $content ) {
        $html = trim( $content );
        if ( $html === '' ):
            return '';  
        endif;
        $blocktags = 'address|article|aside|audio|blockquote|canvas|caption|center|col|del|dd|div|dl|fieldset|figcaption|figure|footer|form|frame|frameset|h1|h2|h3|h4|h5|h6|header|hgroup|iframe|ins|li|nav|noframes|noscript|object|ol|output|pre|script|section|table|tbody|td|tfoot|thead|th|tr|ul|video';
        $html = preg_replace( '~<p>\s*<('.$blocktags.')\b~i', '<$1', $html );
        $html = preg_replace( '~</('.$blocktags.')>\s*</p>~i', '</$1>', $html );
        return $html;
    }

    public static function user_can( $post_type, $action = 'edit', $post_ID = FALSE ){
        
        # Post type object
        $ptype_object = get_post_type_object( $post_type );
        
        # check permissions
        if( empty( $post_ID ) ):
            
            return current_user_can( $action . '_' . $ptype_object->capability_type . 's' );
        
        else:
            
            return current_user_can( $action . '_' . $ptype_object->capability_type, $post_ID );
        
        endif;
    
    }

    // Function to get the client IP address
    public static function client_ip() {

        $ipaddress = '';
        
        if ( isset( $_SERVER[ 'HTTP_CLIENT_IP' ] ) ):
            $ipaddress = $_SERVER[ 'HTTP_CLIENT_IP' ];
        elseif( isset( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ):
            $ipaddress = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
        elseif( isset( $_SERVER[ 'HTTP_X_FORWARDED' ] ) ):
            $ipaddress = $_SERVER[ 'HTTP_X_FORWARDED' ];
        elseif( isset( $_SERVER[ 'HTTP_FORWARDED_FOR' ] ) ):
            $ipaddress = $_SERVER[ 'HTTP_FORWARDED_FOR' ];
        elseif( isset( $_SERVER[ 'HTTP_FORWARDED'] ) ):
            $ipaddress = $_SERVER[ 'HTTP_FORWARDED'];
        elseif( isset( $_SERVER[ 'REMOTE_ADDR' ] ) ):
            $ipaddress = $_SERVER[ 'REMOTE_ADDR' ];
        else:
            $ipaddress = 'UNKNOWN';
        endif;
        
        return $ipaddress;
    
    }

    public static function home_urls( $content ){
        return str_replace( '#home#', get_site_url(), $content );
    }

}

# Shorcode para a home no conteúdo dos posts
add_filter( 'the_content', array( 'Piki', 'home_urls' ) );
add_filter( 'the_excerpt', array( 'Piki', 'home_urls' ) );

# Normalizando as tags P do conteúdo
//add_filter('the_content', array( 'Piki', 'fix_autop' ) );
//add_filter('the_excerpt', array( 'Piki', 'fix_autop' ) );

# Qualidade das imagens
add_filter( 'jpeg_quality', array( 'Piki', 'jpeg_quality' ) );
add_action( 'init', array( 'Piki', 'init' ) );
add_action( 'wp_head', array( 'Piki', 'wp_head' ) );
add_action( 'wp_enqueue_scripts', array( 'Piki', 'add_scripts' ) );
add_action( 'admin_enqueue_scripts', array( 'Piki', 'add_scripts' ) );
add_action( 'admin_head', array( 'Piki', 'head_configs' ) );
add_action( 'wp_head', array( 'Piki', 'head_configs' ) );
add_filter( 'wp_nav_menu_items', array( 'Piki', 'nav_menu_items' ) );

# Apenas ADMIN
if( is_admin() ):
    # Página de administração
    require_once( plugin_dir_path( __FILE__ ) . '/admin.php' );
    # Métodos para administração
    require_once( plugin_dir_path( __FILE__ ) . '/includes/admin-features.php' );
endif;

# Organiza um array de ítems pelo campo 'parent'
function piki_organize_by_taxonomy( $items, $taxonomy ){
    if ( !is_array( $items ) || empty( $items ) ):
        return false;
    endif;
    $return = array();
    foreach ( $items as $key => $item ):
        $idades = wp_get_post_terms( $item->ID, $taxonomy );
        foreach ( $idades as $key => $idade ):
            if ( !array_key_exists( $idade->slug, $return ) ):
                $return[ $idade->slug ] = $idade;
                $return[ $idade->slug ]->items = array();
            endif;
            $return[ $idade->slug ]->items[] = $item;
        endforeach;
    endforeach;
    return $return;
}

# Recupera as páginas filhas de uma determinada página
function piki_get_page_childs( $parent ){
    $args = array(
        'sort_order' => 'ASC',
        'sort_column' => 'menu_order',
        'hierarchical' => false,
        'exclude' => '',
        'child_of' => $parent,
        'post_type' => 'page',
        'post_status' => 'publish'
    ); 
    return get_pages( $args ); 
}

# Recupera e salva uma imagem
function piki_rack_file( $fileurl, $path, $newFileName=false ){
    
    # Se o path for indicado com um / no final, ela é removida
    $path = rtrim( $path, '/' );
    $return  = array(
        'status' => 'error',
        'message' => '',
    );

    # Exists
    $fheaders = @get_headers( $fileurl );
    $fstatus = strtoupper( $fheaders[ 0 ] );
    if( strpos( $fstatus, 'NOT FOUND' ) !== false ):
        Piki::error( 'A url informada não de um arquivo válido, ou o arquivo não pode ser copiado.' );
    endif;
    
    # Verifica se o diretório de destino existe
    if ( !is_dir( $path ) && !@mkdir( $path )  ):
        Piki::error( 'A pasta informada não existe e não pôde ser criada.' );
    endif;  
    
    # Verifica se o diretório de destino está apto a receber o arquivo
    if ( !is_writable( $path ) && !@chmod( $path, 0775 ) ):
        Piki::error( 'A pasta informada não tem permissão de escrita e não pode ser alterada.' );
    endif;
        
    # Nome do arquivo
    $fileName = basename( $fileurl );

    # Tipo de arquivo
    $fileType = strtolower( pathinfo( $fileurl, PATHINFO_EXTENSION ) );
    
    # Novo nome do arquivo
    if( !$newFileName ):
        $newFileName = get_new_file_name( $path, $fileName );
    endif;

    # Absolute path
    $newPath = $path . '/' . $newFileName;
    
    # Pega a imagem
    $userAgent = 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0';
    $origem = curl_init( $fileurl );
    $destino = fopen( $newPath, 'wb' );
    curl_setopt( $origem, CURLOPT_FILE, $destino );
    curl_setopt( $origem, CURLOPT_HEADER, 0 );
    curl_setopt( $origem, CURLOPT_USERAGENT, $userAgent );
    curl_exec( $origem );
    curl_close( $origem );
    fclose( $destino );
    
    $return = array(
        'status' => 'success',
        'filepath' => $newPath,
        'filename' => $newFileName,
        'type' => $fileType,
    );

    if( $fileType === 'jpg' || $fileType === 'jpeg' || $fileType === 'gif' || $fileType === 'png' || $fileType === 'bmp' ):
        $imgInfo = getimagesize( $newPath );
        if( !empty( $imgInfo ) ):
            $return[ 'width' ] = $imgInfo[ 0 ];
            $return[ 'height' ] = $imgInfo[ 1 ];
        endif;
    endif;

    return $return;

}

# Redirecionando para a listagem, depois de salvar o ítem
/*
function piki_redirect_after_save( $location, $post_id ) {
    # Partes da URL
    list( $url, $qstring ) = explode( '?', $location );
    # Parsing query string
    parse_str( $qstring, $GET );
    # Página carregada
    $page = basename( $url );
    if( $page === 'post.php' && is_array( $GET ) && isset( $GET[ 'action' ] ) && $GET[ 'action' ] === 'edit' ):
        $post_type = get_post_type( $post_id );
        $location = admin_url( 'edit.php?post_type=' . $post_type );
        if( isset( $GET[ 'message' ] ) ):
            $location .= '&message=' . $GET[ 'message' ];
        endif;
    endif;
    return $location;
}
add_filter( 'redirect_post_location', 'piki_redirect_after_save', 10, 2 );
*/

# Retorna o nome do arquivo a ser gravado
function get_new_file_name( $dir, $file_name ){

    # Remove extenções no nome
    if( strpos( $file_name, '?' ) ):
        $paces_name = explode( '?', $file_name );
        $file_name = array_shift( $paces_name );
    endif;

    # Pedaços do nome
    $peaces = explode( ".", $file_name );
    
    # Extensão do arquivo
    $extensao = array_pop( $peaces );
    
    # Nome consolidado
    $name = implode( '_', $peaces );
    $name = Piki::slug( $name );
    
    # Novo nome
    $file_name = $name . '.' . $extensao;
    
    # URL default do arquivo
    $url_default = $dir . '/' . $file_name;
    
    # Se o arquivo já existe, é gerado um novo nome
    if( is_file( $url_default ) ):

        $new_name = '';
       
        $cont = 1;
        
        while( $new_name == '' ){
            
            $name_test = $name . '(' . $cont . ').' . $extensao;
           
            $url_test = $dir . '/' . $name_test;
            
            if( !is_file( $url_test ) ):
                $new_name = $name_test;
            endif;
            
            $cont++;
        
        }
    
    else:
        
        $new_name = $file_name;     
    
    endif;
    
    return $new_name;

}

function isempty( $val ){
    if ( !isset( $val ) || is_null( $val ) || $val === false ):
        return true;
    elseif( ( is_string( $val ) && $val == '' ) || ( is_array( $val ) && count( $val ) == 0 ) ):
        return true;
    else:
        return false;
    endif;
}

function transliterate( $string, $nohiphens=true ) {
    # Remove accents
    $no_accents = remove_accents( $string );
    # Remove white spaces
    $no_white_spaces = str_replace( array(' ','-'), '_', $no_accents );
    # Just numbers and letters
    $translitated = preg_replace("/[^A-Za-z0-9_]/", "", $no_white_spaces );
    # transliterate
    return strtolower( $translitated );
}

function off( $array, $variable ){
    $array = (array)$array;
    return !is_array( $array ) || !isset( $array[ $variable ] ) || $array[ $variable ]  !== 'on';
}

function on( $array, $variable ){
    $array = (array)$array;
    return is_array( $array ) && isset( $array[ $variable ] ) && ( $array[ $variable ]  === 'on' || $array[ $variable ]  === true );
}
