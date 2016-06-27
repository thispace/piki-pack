<?php
class Relateds {

    var $taxonomy = 'post_tag';
    var $title = 'Veja também:';
    var $more_button = false;
    var $more_button_label = 'Ver todos';
    var $template = false;
    var $total = 6;
    var $meta_query = false;
    var $orderby = false;
    var $order = 'DESC';
    
    function __construct( $options = array() ){

        global $post;

        # Post
        if( isset( $options[ 'post' ] ) ):
            $this->post = $options[ 'post' ];
        else:
            $this->post = $post;
        endif;

        # Taxonomy
        if( isset( $options[ 'taxonomy' ] ) ):
            $this->taxonomy = $options[ 'taxonomy' ];
        endif;

        # Meta query
        if( isset( $options[ 'meta_query' ] ) ):
            $this->meta_query = $options[ 'meta_query' ];
        endif;

        # Título
        if( isset( $options[ 'title' ] ) ):
            $this->title = $options[ 'title' ];
        endif;

        # Total
        if( isset( $options[ 'total' ] ) ):
            $this->total = $options[ 'total' ];
        endif;

        # More button
        if( isset( $options[ 'more_button' ] ) ):
            $this->more_button = $options[ 'more_button' ] === true;
        endif;

        # More button label
        if( isset( $options[ 'more_button_label' ] ) ):
            $this->more_button_label = $options[ 'more_button_label' ];
        endif;

        # Template
        if( isset( $options[ 'order' ] ) ):
            $this->template = $options[ 'template' ];
            $this->show( $this->template );        
        endif;

        # Template
        if( isset( $options[ 'template' ] ) ):
            $this->template = $options[ 'template' ];
            $this->show( $this->template );        
        endif;
        
    }

    public function show( $template = false ){
        
        $args = array(
            'post_type'         => $this->post->post_type,
            'post__not_in'      => array( $this->post->ID ),
            'post_status'       => 'publish',
            'orderby'           => 'post_date',
            'order'             => 'DESC',
            'posts_per_page'    => $this->total,
        );
       
        # Taxonomy
        if( $this->taxonomy && taxonomy_exists( $this->taxonomy ) ):
            
            $terms = wp_get_post_terms( $this->post->ID, $this->taxonomy );

            if( !empty( $terms ) ):
                
                $slugs = array();
                
                foreach ( $terms as $key => $term ):
                    $slugs[] = $term->slug;
                endforeach;
                
                $args[ 'tax_query' ] = array(
                    
                    array(
                        'taxonomy' => $this->taxonomy,
                        'field' => 'slug',
                        'terms' => $slugs,
                    ),

                );

            endif;

        endif;

        # Meta query
        if( !empty( $this->meta_query ) ):
            $args[ 'meta_query' ] = $this->meta_query;
        endif;
        
        # Do search
        query_posts( $args );

        if( have_posts() ):
            if( $template !== false ):
                locate_template( $template, true, false );
            else:                
                $this->write_html();
            endif;        
        endif;

        wp_reset_query();
        wp_reset_postdata();

    }

    public function write_html(){
        ?>
        <div class="widget" id="widget_participe">
           
            <h2 class="widget-title"><?php echo $this->title; ?></h2>
       
            <div class="list-items zebra">
                <?php 
                $cont = 0;
                while( have_posts() ): the_post(); ?>
                <a href="<?php the_permalink(); ?>" class="item <?php echo ($cont%2?'even':'odd'); ?>" title="<?php the_title(); ?>">
                    <span class="title"><?php the_title(); ?></span>
                </a>
                <?php 
                $cont++;
                endwhile; 
                ?>              
            </div>
            <?php if( $this->more_button ): ?>
            <a href="<?php echo get_post_type_archive_link( $this->post->post_type ); ?>" class="button full" title="<?php echo $this->more_button_label; ?>"><?php echo $this->more_button_label; ?></a>
            <?php endif; ?>
        </div>
        <?php
    }
}
