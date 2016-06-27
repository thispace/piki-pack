 <?php
# Paginação
class Pager {

    public static function show( $object=false, $ajax_label=false, $target=false, $onscroll=false, $pager_key=false ){ 
        
        global $wp_query;

        # Se o objeto não é informado, fazemos a paginação para a query mãe
        if( !!$object ):
            $saved_query = $wp_query;
            $wp_query = $object;
        endif;

        # Paginação atual
        $paged = $wp_query->get( 'paged' ) != '' ? $wp_query->get( 'paged' ) : 1;
        # Se existe uma home para a listagem
        $with_home = isset( $wp_query->with_home ) ? $wp_query->with_home : false;
        # Se estamos na home da listagem
        $is_home = isset( $wp_query->home_contents ) ? $wp_query->home_contents : false;
        # Se não temos paginação a mostrar
        if( ( $wp_query->max_num_pages < 2 && !$is_home ) || $wp_query->found_posts == 0 ):
            return '';
        endif;
        # Target
        $target = !$target ? '#content .pager-items' : $target;
        ?>

        <nav class="piki-ajaax-nav" role="navigation" pan-onscroll="<?php var_export( $onscroll ); ?>" pan-target="<?php echo $target; ?>">
        <h1 class="screen-reader-text">Navegação</h1>
        
        <?php if( $paged > 1 ): ?>
        <div class="nav-previous"><a href="<?php echo get_pagenum_link( $paged - 1 ); ?>" class="button cor3 size3">Anteriores</a></div>
        <?php endif; ?>
        
        <?php 
        if( $paged < $wp_query->max_num_pages || $is_home ):
            $real_paged = $paged + 1;
            if( $with_home ) $real_paged++;
            $next_url = get_pagenum_link( $real_paged );
            if( !!$pager_key ):
                $next_url .= ( strpos( $next_url, '?' ) !== false  ? '&' : '?' ) . 'target=' . $target;
            endif;
            ?>
            <div class="nav-next"><a href="<?php echo $next_url; ?>" class="button cor3 size3" ajax-label="<?php echo( !!$ajax_label ? $ajax_label : 'Ver mais' ); ?>">Próximos</a></div>
            <?php 
        endif; 
        ?>
        </nav>

        <?php
        self::add_files();
        
        if( !!$object ):
            $wp_query = $saved_query;
            unset( $saved_query );
        endif;
        
    }

    public static function add_files(){
        $filesdir = plugins_url( '/' , __FILE__ );
        wp_enqueue_script( 'piki-pager-scripts', $filesdir . 'scripts.js', array( 'jquery' ), false, true );
        wp_enqueue_style( 'piki-pager-styles', $filesdir . 'styles.css' );

    }

}
