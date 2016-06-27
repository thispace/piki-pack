<?php

define( 'PIKIIMAGES_QUEUE_POSTS_TABLE', 'pikiimages_queue_posts' );
define( 'PIKIIMAGES_QUEUE_IMAGES_TABLE', 'pikiimages_queue_images' );

class PikiImages {

    private $options;
    private $images;
    private $api_host = 'https://api.pikiimages.com';

    # Init
    public static function init(){
        $i = new PikiImages();
        add_shortcode( 'pikiimages', array(  $i, 'shortcode' ) );
    }

    # Busca as imagens que precisam ser atualizadas e geradas
    public static function images_to_rebuild(){
        
        global $wpdb;

        # Posts por página
        $posts_per_page = 100;

        # Tipo de post
        $post_type = $_POST[ 'post_type' ];
        if( empty( $post_type ) ):
            Piki::error( 'O tipo de post não foi informado' );
        endif;
                
        # Campos
        $image_fields = self::get_post_type_image_fields( $post_type );
         
        # Se não existem campos, passamos para o próximo tipo de post
        if ( empty( $image_fields ) ):
            Piki::return_json(array(
                'status' => 'no_fields',
                'message' => 'Este tipo de post não tem nenhum campo de imagem.',
            ));
        endif;

        # Posts
        $paged = isset( $_POST[ 'paged' ] ) && (int)$_POST[ 'paged' ] > 1 ? $_POST[ 'paged' ] : 1;
        $posts = new WP_Query(array(
            'post_type' => $post_type,
            'post_status' => 'any',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'orderby' => 'ID',
            'order' => 'DESC',
        ));

        # Se não existe post para análise
        if( !$posts->have_posts() ):
            Piki::return_json(array(
                'status' => 'no_posts',
                'message' => 'Nenhum post a ser analisado.',
            ));
        endif;

        # Posts
        $total = $posts->found_posts;
        $posts = $posts->get_posts();

        # Se for a primeira requisição, limpamos a tabela
        if( $paged == 1 ):
            $wpdb->query( "TRUNCATE TABLE $wpdb->prefix" . PIKIIMAGES_QUEUE_POSTS_TABLE );
            $wpdb->query( "ALTER TABLE $wpdb->prefix" . PIKIIMAGES_QUEUE_POSTS_TABLE . " AUTO_INCREMENT = 1" );
        endif;
        
        # Colocando os posts na fila para análise
        foreach( $posts as $post ):
            $wpdb->insert( 
                $wpdb->prefix . PIKIIMAGES_QUEUE_POSTS_TABLE, 
                array( 
                    'post_id' => $post->ID, 
                    'post_type' => $post->post_type,
                    'post_title' => $post->post_title,
                ), 
                array( 
                    '%d', 
                    '%s',
                    '%s'
                ) 
            );
        endforeach;

        Piki::return_json(array(
            'status' => 'success',
            'total_posts' => $total,
            'next_page' => ( ( $posts_per_page * $paged ) < $total ) ? $paged+1 : false,
            'posts' => $posts,
        ));

    }

    # Busca campos de imagens de um tipo de post
    public static function get_post_type_image_fields( $post_type ){
        
        # Configurações do tipo de post
        $settings = PikiForms::get_form_settings( $post_type );

        # Se não existem campos para o tipo de post
        if( !$settings || empty( $settings[ 'fields' ] ) ):
            return false;
        endif;

        # Campos de imagens, atrelados ao tipo de post
        return PikiFields::extract_field( $settings[ 'fields' ], 'imagewp', 'ftype', false );
    
    }

    public static function proccess_post(){

        global $wpdb;

        $post_id = $_POST[ 'post_id' ];
        $post_type = $_POST[ 'post_type' ];

        # Campos
        $fields = self::get_post_type_image_fields( $post_type );

        # Objeto do campo
        $f = new imagewp();
        
        # Retorno
        $results = array();
        
        # Gera as imagens
        foreach ( $fields as $kf => $field ):
            
            # Valor atual do campo
            $value = PikiField::get_field_value( $field, $post_id );
            
            call_user_func( 'delete_' . $field[ 'data_type' ] . '_meta', $post_id, $field[ 'machine_name' ] );

            # Faz o update do valor, se houver, e gera as imagens
            if( is_array( $value ) ):
                $values = isset( $value[ 'ids' ] ) ? array( $value ) : $value;
                foreach( $values as $value_item ):
                    $results[] = $f->save_meta( $field, $post_id, $value_item );
                endforeach;
            endif;
        
        endforeach;

        # Remove o post da fila
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->prefix" . PIKIIMAGES_QUEUE_POSTS_TABLE . " WHERE post_id = %d",
            $post_id
        ));

        # Retorna o json de sucesso
        Piki::return_json(array(
            'status' => 'success',
            'result' => $results
        ));

    }

}
add_action( 'init' , array( 'PikiImages', 'init' ) );

class PikiImagesSettingsPage {
   
    private $options;
    private $images;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }
    # Add options page
    public function add_plugin_page() {
        add_submenu_page(
            'piki-dashboard',
            'Images',
            'Images',
            'manage_options',
            'piki-images',
            array( $this, 'admin_images' )
        );
    }
    # Página de administração
    public function admin_images() {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Pikiforms - Images</h2>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'pikiforms_images_group' );   
                do_settings_sections( 'pikiforms-images-settings' );
                //submit_button();
                ?>
            </form> 
        </div>
    <?php
    }
    # Register and add settings
    public function page_init() {
        
        wp_enqueue_script( 'piki-images-scripts', plugin_dir_url( __FILE__ ) . '/images.js', array( 'jquery' ) );
        wp_enqueue_style( 'piki-images-styles', plugin_dir_url( __FILE__ ) . '/images.css' );

        register_setting(
            'pikiforms_images_group', // Option group
            'pikiforms_images_settings', // Option name
            array( $this, 'sanitize_images' ) // Sanitize
        );
        add_settings_section(
            'pikiforms_images_settings', // ID
            'Geração de imagens', // Title
            NULL, // Callback
            'pikiforms-images-settings' // Page
        );  
        add_settings_field(
            'status', // ID
            'Refazer imagens', // Title 
            array( $this, 'field_images_rebuild' ), // Callback
            'pikiforms-images-settings', // Page
            'pikiforms_images_settings' // Section           
        );
    }

    # Valida os valores
    public function sanitize_images( $input ) {
        $new_input = array();

        echo '<pre>';
        var_dump( $input[ 'preset-versions' ] );
        exit;

        if( isset( $input[ 'imagens' ] ) ):
            $new_input['imagens'] = array_slice( $input[ 'imagens' ], 0, $this->options[ 'total' ] );
        endif;
        return $new_input;
    }

    # Status
    public function field_images_rebuild() {
        
        # Selectbox
        $s = new select();
        
        # Tipos de post
        $post_types = get_post_types(array( '_builtin' => false ));
        
        # Tipos de posts que não entram
        $excludes = array( 'pikiform', 'sp_newsletters', 'sp_report', 'sptemplates', 'sendpress_list' );        
        
        # Options para o select
        $options = array( '' => 'Selecione um tipo de Post' );
        foreach( $post_types as $type ):
            if( in_array( $type, $excludes ) ):
                continue;
            endif;
            $_type = get_post_type_object( $type );
            $options[ $type ] = $_type->labels->singular_name;
        endforeach;

        echo 
        '<div id="pikiforms_images_rebuild">',
            '<div class="post-type-selection">',
                $s->get_field(array(
                    'machine_name'  => 'post_types',
                    'ftype'         => 'select',
                    'id'            => 'piki_images_post_type',
                    'name_html'     => 'piki_images_post_type',
                    'options'       => $options
                )),
            '</div>',
            '<input type="button" id="field_images_rebuild" name="pikiforms_options[field_images_rebuild]" value="Iniciar geração de imagens" class="button button-primary" />',
            '<div class="status"></div>
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>';
    }

}
if( is_admin() ):
    # Inicia a página de administração
    $pikiimages_settings_page = new PikiImagesSettingsPage();
    # Recupera os posts que tem imagens a ser geradas
    add_action( 'wp_ajax_piki_images_to_rebuild', array( 'PikiImages', 'images_to_rebuild' ) );
    # Processa um post
    add_action( 'wp_ajax_piki_images_proccess_post', array( 'PikiImages', 'proccess_post' ) );
endif;

