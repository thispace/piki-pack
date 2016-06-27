<?php
define( 'PikiSlides_ptype', 'slide' );
define( 'PikiSlides_field_url', 'pikislides_url' );
define( 'PikiSlides_field_target', 'pikislides_target' );
define( 'PikiSlides_field_image', 'pikislides_imagem' );
define( 'PikiSlides_taxonomy', 'locais_slides' );

class PikiSlides {

	# Ítems
	var $items;
	
	# Método construtor
	function __construct( $getPikiSlides=false ){
		if ( $getPikiSlides !== false && is_numeric( $getPikiSlides ) ) {
			$this->items = PikiSlides::get_slides( $getPikiSlides );
		}
		else if( $getPikiSlides === true ){
			$this->items = PikiSlides::get_slides();
		}
	}
	
	# Registra o tipo de post
	public static function activation_plugin() {
		# Post type
		PikiSlides::register_post_type();
		# Taxonomy
		PikiSlides::register_taxonomy();
		# Shortcode
		add_shortcode( 'pikislides', array(  'PikiSlides', 'shortcode' ) );
		# Flushing rules
		flush_rewrite_rules();
	}
	
	# Tipo de post
	public static function register_post_type(){
	    
	    $labels = array(
	        'name' => __('Slides', 'post type general name'),
	        'singular_name' => __('Slide', 'post type singular name'),
	        'add_new' => __('Adicionar novo', 'book'),
	        'add_new_item' => __('Adicionar novo Slide'),
	        'edit_item' => __('Editar Slide'),
	        'new_item' => __('Novo Slide'),
	        'view_item' => __('Ver Slide'),
	        'search_items' => __('Buscar Slides'),
	        'not_found' =>  __('Nenhum Slide encontrado'),
	        'not_found_in_trash' => __('Nenhum slide na lixeira'),
	        'parent_item_colon' => ''
	    );

	    $args = array(
	        'labels' => $labels,
	        'public' => true,
	        'publicly_queryable' => true,
	        'show_ui' => true, 
	        'query_var' => true,
	        'rewrite' => true,
	        'capability_type' => 'post',
	        'map_meta_cap' => true,
	        'hierarchical' => false,
            'exclude_from_search' => true,
	        'menu_position' => 4,
	        'capability_type' => 'post',
	        'supports' => array( 'title', 'excerpt') ,
	        'menu_icon' => plugins_url( '/images/menu-icon.png' , __FILE__ )
	    );
	      
	    register_post_type( PikiSlides_ptype, $args );
	
	}
	
	# Registra a Taxonomia
	public static function register_taxonomy(){
		
		$taxonomy_settings=array(
			'hierarchical' => true,
			'labels' => array(
				'name' => 'Locais dos slides',
				'singular_name' => 'Local do slide',
				'all_items' => 'Todos os locais',
				'edit_item' => 'Editar local',
				'view_item' => 'Ver local',
				'update_item' => 'Atualizar local',
				'add_new_item' => 'Novo local',
				'new_item_name' => 'Novo nome de local',
				'parent_item' => 'Local pai',
				'parent_item_colon' => 'Local pai',
				'search_items' => 'Buscar locais',
				'popular_items' => 'Popular locais',
				'separate_items_with_commas' => 'Separar ítems com vírgulas',
				'add_or_remove_items' => 'Adicionar ou remover locais',
				'choose_from_most_used' => 'Escolher entre os locais mais usados',
				'not_found' => 'Nenhum local encontrado.',
				'menu_name' => 'Locais',
			),
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'rewrite' => array(
				'slug' => 'locais-dos-slides',
			),
		);
		register_taxonomy( PikiSlides_taxonomy, array( PikiSlides_ptype ), $taxonomy_settings );
	
	}
	
	# Registra os campos do tipo de conteúdo
    public static function register_fields( $meta_boxes ){
        
        $meta_boxes[] = array(
            'id'         => 'destino',
            'title'      => 'Destino',
            'post_types' => array( PikiSlides_ptype ), // Post type
            'context'    => 'normal',
            'priority'   => 'high',
            'show_names' => true,
            'fields'     => array(
                array(
                    'label' => 'URL de destino',
	                'description' => 'URL para onde o usuário será direciondo quando clicar no conteúdo',
                    'machine_name'   => PikiSlides_field_url,
                    'ftype' => 'text',
                ),
	            array(
	                'label' => 'Janela de destino',
	                'machine_name'   => PikiSlides_field_target,
	                'ftype'    => 'select',
	                'options' => array(
	                    '_self' => 'Mesma janela',
	                    '_blank' => 'Outra janela',
	                ),
	            ),
            ),
        );
        
        $meta_boxes[] = array(
            'id'         => 'imagem',
            'title'      => 'Imagem',
            'post_types' => array( PikiSlides_ptype ), // Post type
            'context'    => 'normal',
            'priority'   => 'high',
            'show_names' => true,
            'fields'     => array(
                array(
                    'label' => false,
                    'machine_name'   => PikiSlides_field_image,
                    'ftype' => 'imagewp',
                    'crop' => array(
	                    'ratio' => '1300x473',
	                    'status' => 'on',
                    ),
                    'cover' => true,
                ),
            ),
        );
        return $meta_boxes;
    
    }
    
    # Recupera os meta dados de cada slide
	public static function get_meta_values( $postID ){

		$meta = get_post_meta( $postID );

		$return = array(
			'url' => array_shift( $meta[ PikiSlides_field_url ] ),
			'target' => array_shift( $meta[ PikiSlides_field_target ] ),
			'image' => array_shift( $meta[ PikiSlides_field_image ] ),
		);

		# URL de destino
		if( !empty( $return[ 'url' ] ) ):
			$return[ 'url' ] = array(
				'id' => PikiSlides_field_url,
				'title' => 'URL',
				'value' => str_replace( array( '#home#', '%home%' ), get_bloginfo( 'url' ), $return[ 'url' ] ),
			);
		endif;

		# Target
		if( $return[ 'target' ] === '_blank' ):
			$return[ 'target' ] = array(
				'id' => PikiSlides_field_target,
				'title' => 'Janela de destino',
				'value' => '_blank',
			);
		endif;
		
		# Imagem
		$return[ 'image' ] = unserialize( base64_decode( $return[ 'image' ] ) );
		$child = array_shift( $return[ 'image' ][ 'childs' ] );
		$return[ 'image' ] = array(
			'id' => PikiSlides_field_image,
			'title' => 'Imagem',
			'value' => array(
				'width' => $return[ 'image' ][ 'width' ],
				'height' => $return[ 'image' ][ 'height' ],
				'url' => $child[ 'resized' ],
			),
		);
		
		return $return;

	}
	
	# Recupera os slides
	public static function get_slides( $total_items, $local=false ){

		# Parametros gerais
		$params = array(
			'posts_per_page'   => $total_items,
			'orderby'          => 'post_date',
			'order'            => 'DESC',
			'post_type'        => PikiSlides_ptype,
			'post_status'      => 'publish',
		);

		# Local
		if( $local ):
			$params[ 'tax_query' ] = array(
				array(
					'taxonomy' => PikiSlides_taxonomy,
					'field'    => 'slug',
					'terms'    => $local,
				)
			);
		endif;

		$slides = get_posts( $params );

		if ( !is_array( $slides ) || empty( $slides ) ) {
			return false;
		}
		foreach ($slides as $key => $slide) {
			$slides[ $key ]->meta = PikiSlides::get_meta_values( $slide->ID );
		}
		return $slides;
	}
    
    # Scripts e Estilos
    public static function add_files(){

    	Piki::add_library( 'slick' );

        $filesdir = plugins_url( '/' , __FILE__ );
        # Scripts
        wp_enqueue_script( 'pikislides-script', $filesdir . 'slides.js', array( 'jquery' ) );
        # Styles
        wp_enqueue_style( 'pikislides-styles', $filesdir . 'slides.css' );
    
    }

    # Shortcode que mostra formulários
    public static function shortcode( $atts ) {
        
    	# Valores padrão
    	$defaults = array(
            'total_items' => 6,
            'local' => false,
            'arrows_nav' => false,
            'pager' => false,
            'effect' => 'scrollHorz',
            'timeout' => 6000,
            'speed' => 1000,
            'height' => 410,
            'width' => 0,
            'titles' => true,
            'excerpt' => false,
            'excerpt_field' => false,
            'excerpt_trim' => 8
    	);
    	# Extrai os parâmetros
		$options = shortcode_atts( $defaults, $atts, 'pikislides' );
        $options = array_merge( $defaults, $options );

        # Dimensions

	        $width = ( isset( $options[ 'width' ] ) && $options[ 'width' ] != '' ) ? $options[ 'width' ] : 0;
	        $height = ( isset( $options[ 'height' ] ) && $options[ 'height' ] != '' ) ? $options[ 'height' ] : 0;
	        if( $width == 0 && $height == 0 ):
	        	echo( 'O Slide deve ter o tamanho vertical ou horizontal fornecido.' );
	       	endif;

		# Slides
		$slides = self::get_slides( $options[ 'total_items' ], $options[ 'local' ] );

		if( $slides && !empty( $slides ) ):

			# Files
			self::add_files();
			
			$styles = array();
			$return = '
			<div id="full-banner" class="slider-wrapper">
				<div class="slider-slideshow" slider-pager=\''. $options[ 'pager' ] .'\' slider-arrows=\''. $options[ 'arrows_nav' ] .'\' slider-effect=\''. $options[ 'effect' ] .'\' slider-speed=\''. $options[ 'speed' ] .'\' slider-timeout=\''. $options[ 'timeout' ] . '\'>';
				    
				    foreach( $slides as $slide ):

				    	# Slide ID
				    	$slide_id = $better_token = uniqid('');

				    	# Se tem url, é um link
				    	$return .= !$slide->meta[ 'url' ] ? '<div class="slide-item" id="slide-'. $slide_id .'">' : '<a href="'. $slide->meta[ 'url' ][ 'value' ] .'" class="slide-item" id="slide-'. $slide_id .'">';

					    	# Imgem
				    		$imgsrc = get_site_url() . '/'. $slide->meta[ 'image' ][ 'value' ][ 'url' ];
					    	$styles[] = '#slide-'.  $slide_id . '{background-image:url(\'' . $imgsrc . '\');}';
				    		
				    		# Textos
				    		if( $options[ 'titles' ] ):
						    	$return .= '<div class="texts"><h2 class="title">'. str_replace( '  ', '<br />', $slide->post_title ) .'</h2>';
					    		if( $options[ 'excerpt' ] ):
					    			$description = !$options[ 'excerpt_field' ] ? get_post_field( 'post_excerpt', $slide->ID ) : get_post_meta( $slide->ID, $options[ 'excerpt_field' ], true );
					    			if( $description != '' ):
							    		$return .= '<p class="excerpt">'. nl2br( $description ) .'</p>';
					    			endif;
					    		endif;
							    $return .= '</div>';
					    	endif;
				    	
				    	# Se tem url, é um link
				    	$return .= !$slide->meta[ 'url' ] ? '</div>' : '</a>';

					endforeach;
			    
			    $return .= '</div>';
			
			$return .= 
			'</div>
			';

			$return .= '<style type="text/css">'. implode('', $styles ) .'</style>';
		
		else:

			$return = '';

		endif;

        return $return;

    }

}
# Ativação do plugin
add_action( 'init', array( 'PikiSlides', 'activation_plugin' ) );
# Campos extras no formulário
add_filter( 'pkmeta_register_fields', array( 'PikiSlides', 'register_fields' ) );
?>