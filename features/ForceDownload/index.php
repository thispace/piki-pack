<?php
# DOWNLOAD DE ARQUIVOS
Class forceDownload {
    public static function url( $post ){
        # POST
        if( is_numeric( $post ) ):
            $post = get_post( $post );
        endif;
        # Só baixa arquivos da galeria
        if( $post->post_type != 'attachment' ):
            exit( 'O Post informado não é um arquivo' );
        endif;
        return get_bloginfo( 'url' ) . '/download/' . $post->ID;
    }
    public static function generate_rewrite_rules() {
        global $wp_rewrite; 
        $new_rules[ 'download/([^/]+)' ] = 'index.php?download=true&post_id=$matches[1]';
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }
    public static function query_vars( $qvars ) {
        $qvars[] = 'download';
        $qvars[] = 'post_id';
        return $qvars;
    }
    public static function template_redirect_intercept(){
        global $wp_query;
        if( $wp_query->get( 'download' ) == 'true' ):
            # ID do post
            $post_id = $wp_query->get( "post_id" );
            # Tipos para download
            $candown = array( 'audio/mpeg', 'video/mp4', 'image/jpg', 'image/jpeg', 'image/png', 'image/gif', 'application/pdf' );
            # Mimetype do arquivo
            $mimetype = get_post_mime_type( $post_id );
            # Verifica se é um tipo de arquivo que pode ser baixado
            if( in_array( $mimetype, $candown ) ):
                $url = wp_get_attachment_url( $post_id );
                header( "Content-Description: Transferência de arquivo" ); 
                header( "Content-Transfer-Encoding: Binary"); 
                header( "Content-disposition: attachment; filename=" . basename( $url ) );
                header( "Content-type: " . $mimetype );
                readfile( $url );
                exit;
            endif;
        endif;
    }
}
add_action( 'generate_rewrite_rules', array( 'forceDownload', 'generate_rewrite_rules' ) );
add_filter( 'query_vars', array( 'forceDownload', 'query_vars' ) );
add_action( 'template_redirect', array( 'forceDownload', 'template_redirect_intercept' ) );
