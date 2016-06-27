<?php
class PikiFloater {

    public static function init() {
        add_shortcode( 'pikifloater', array(  'pikifloater', 'shortcode' ) );
    }

    # Arquivos
    public static function add_files(){
        Piki::add_library( 'fancybox' );
        wp_enqueue_script( 'PikiFloater-scripts', plugins_url( '/scripts.js', __FILE__ ), array( 'jquery' ) );
        wp_enqueue_style( 'PikiFloater-styles', plugins_url( '/styles.css', __FILE__ ) );
    }

    public static function url( $id ){
        if( is_string( $id ) && !is_numeric( $id ) ):
            $url = $id;
        else:
            $url = get_permalink( $id );
        endif;
        return $url;
    }

    public static function shortcode( $atts ) {
        extract( shortcode_atts( array(
            'id' => false,
            'url' => false,
            'label' => false,
            'add_class' => false,
            'callback' => false,
            'callback_args' => false,
        ), $atts ) );
        
        # Se não foi informado nem o ID nem a URL
        if( !isset( $atts[ 'url' ] ) && !isset( $atts[ 'id' ] ) ):
            return '';
        endif;

        if( isset( $atts[ 'id' ] ) ):
            $url = get_permalink( $atts[ 'id' ] );
        else:
            $url = str_replace( '%home%', get_bloginfo( 'url' ),  $atts[ 'url' ] );
            $url = Piki::http( $url );
        endif;

        $label = isset( $atts[ 'label' ] ) && $atts[ 'label' ] != '' ? $atts[ 'label' ] : 'Abrir';
        $add_class = isset( $atts[ 'add_class' ] ) && $atts[ 'add_class' ] != '' ? $atts[ 'add_class' ] : '';
        $callback = isset( $atts[ 'callback' ] ) && $atts[ 'callback' ] != '' ? $atts[ 'callback' ] : NULL;
        $callback_args = isset( $atts[ 'callback_args' ] ) && $atts[ 'callback_args' ] != '' ? $atts[ 'callback_args' ] : NULL;

        $better_token = uniqid();

        self::add_files();

        return '<a href="'. self::url( $url ) .'" class="button piki-page-floater ' . $add_class . '"'. ( !is_null( $callback ) ? ' --callback="'. $callback .'"' : '' ). ( !is_null( $callback_args ) ? ' --callback-args="'. $callback_args .'"' : '' ) .' rel="floater-'. $better_token .'">'. $label .'</a>';

    }

} 

add_action( 'init', array( 'PikiFloater', 'init' ) );