<?php
define( 'PikiForms_ptype', 'pikiform' );
define( 'PikiForms_metakey', 'PikiForms_fid' );

class PikiForms {

    // Início
    public static function init() {
        self::register_post_types();
        add_shortcode( 'pikiforms', array(  'PikiForms', 'shortcode_form' ) );
        add_shortcode( 'pikiforms_button', array(  'PikiForms', 'shortcode_button' ) );
    }

    // Registra os tipos de post
    public static function register_post_types(){
        
        // Forms option
        $types_options = get_option( 'pikiforms_ptypes' );
        
        // Tipos de conteúdo criados pelo plugin
        $toregister = explode( ',', $types_options );
        if( empty( $toregister ) || $toregister[ 0 ] == '' ):
            return;
        endif;
        
        // Faz o registro de todos os tipos
        foreach ( $toregister as $key => $ID ) {
            self::load_form_file( 'pikiform' . $ID );
            call_user_func( 'pikiform' . $ID . '_register' );
        }
    
    }

    // Recupera as configurações de um pikiform
    public static function get_form_settings( $compare ){

        $form_key = false;

        // Se for passado um POST
        if( is_object( $compare ) ):
            
            // Se não é um post
            if( !isset( $compare->post_type ) ):
                return false;
            endif;
            // Se é um pikiform
            if( $compare->post_type == PikiForms_ptype ):
                $form_key = 'pikiform' . $compare->ID;
            // Se é um post
            else:
                $form_key = self::get_post_type_form_key( $compare->post_type );
            endif;
        
        // Form key de um form já carregado
        elseif( function_exists( 'pikiform_' . $compare . '_settings' ) ):
            
            $form_key = $compare;

        // Post type passado
        elseif( is_string( $compare ) && post_type_exists( $compare ) ):

            $form_key = self::get_post_type_form_key( $compare );

        // Se foi passado um ID
        elseif( (int)$compare > 0 ):
            
            // Post
            $post = get_post( $compare );
            
            // Se o ID é inválido
            if( empty( $post ) ):
                return false;
            endif;
            
            // Se é um pikiform
            if( $post->post_type == PikiForms_ptype ):
                $form_key = 'pikiform' . $post->ID;
            
            // Se é um post
            else:
                $form_key = self::get_post_type_form_key( $post->post_type );
            endif;
        
        // Form key de um pikiform
        elseif( file_exists( plugin_dir_path( __FILE__ ) . '/forms/'. $compare .'.php' ) ):
            
            $form_key = $compare;
        
        endif;
        
        // Se não tem um form válido
        if( !$form_key ):
            return false;
        endif;

        // Carrega o arquivo, se existir
        self::load_form_file( $form_key );

        // Configurações do formulário
        $settings = call_user_func( 'pikiform_'. $form_key .'_settings' );
        
        // Método que carrega os campos do formulário
        $form_fields_function = 'pikiform_'. $form_key .'_fields';
        
        // Se existirem campos
        if( function_exists( $form_fields_function ) ):
            
            // Campos do formulário
            $fields = call_user_func( $form_fields_function );
            
            // Prepara os campos
            $settings[ 'fields' ] = PikiFields::prepare_fields( $fields, $settings );
        
        // Se não existem campos
        else:
            $settings[ 'fields' ] = false;
        endif;

        // Se o tipo de data não é informado
        if( !isset( $settings[ 'data_type' ] ) || empty( $settings[ 'data_type' ] ) ):
            $settings[ 'data_type' ] = 'post';
        endif;

        // Permite que outros plugins altere as configurações do formulário
        $settings = apply_filters( 'pikiform_settings', $settings, $form_key );

        return $settings;

    }

    // Carrega o arquivo de configurações do form
    public static function load_form_file( $form_key, $debug=true ){
        // Se a chave do formulário vem sem o ID
        if( $form_key == 'pikiform' ):
            return false;
        endif;
        // Se o método de configurações do form não foi carregado
        if( function_exists( 'pikiform_'. $form_key .'_settings' ) ):
            return true;
        else:
            $filepath = self::form_filepath( $form_key );
            if( file_exists( $filepath ) ):
                require_once( $filepath );
                return true;
            else:
                return false;
            endif;
        endif;
        return false;
    }

    // Regras de URL
    public static function create_rewrite_rules() {
        global $wp_rewrite; 
        $new_rules[ 'piki-forms/([^/]+)' ] = 'index.php?piki-forms=true&form=$matches[1]';
        $new_rules[ 'show-form/([^/]+)' ] = 'index.php?show-form=true&form=$matches[1]';
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }

    // Adiciona variaveis de busca
    public static function add_query_vars( $qvars ) {
        $qvars[] = 'piki-forms';
        $qvars[] = 'show-form';
        $qvars[] = 'form';
        $qvars[] = 'action';
        $qvars[] = 'post-type';
        $qvars[] = 'post-id';
        return $qvars;
    }

    // Título das páginas com form
    public static function custom_wp_title( $title, $sep ){
        global $wp_query;
        $settings_form = self::get_form_settings( $wp_query->get( 'form' ) );
        return get_bloginfo( 'name' ) . ' ' . $sep . ' ' . $settings_form[ 'title' ];
    }
  
    // Redirecionar as páginas solicitadas para os devidos arquivos 
    public static function template_redirect_intercept(){
        
        global $wp_query, $wp_rewrite;

        // Páginas de formulários
        if ( $wp_query->get( "show-form" ) == 'true' ):
            
            $form_key = $wp_query->get( 'form' );
            if( (int)$form_key > 0 ):
                $form_key = 'pikiform'.$form_key;
            endif;

            add_filter( 'wp_title', array( 'PikiForms', 'custom_wp_title' ), 16, 3 );
            
            global $pikiform;
            $pikiform = self::get_form( $form_key );

            Piki::search_template( 'pikiform', $form_key, plugin_dir_path( __FILE__ ) );

            exit();
            
        // Outros casos de Pikiforms
        elseif( $wp_query->get( "piki-forms" ) == 'true' ):

            // Formulários
            if( $wp_query->get( "action" ) == 'insert' || $wp_query->get( "action" ) == 'edit' ):
                
                $post_type = $wp_query->get( "post-type" );
                self::show_form( $post_type, $form_content );
            
            // Exclusão
            elseif( $wp_query->get( "action" ) == 'excluir' ):
                
                // ID do contéudo
                $post_id = $wp_query->get( 'post-id' );
                // Tipo de conteúdo
                $post_type = $wp_query->get( "post-type" );
                // Remove o conteúdo
                require_once( plugin_dir_path( __FILE__ ) . '/proccess-form.php' );
                ProccessForm::exclude_item( $post_id, $post_type );
            
            // Submissões
            else:
                
                $action = isset( $_POST[ 'form_action' ] ) && $_POST[ 'form_action' ] != '' ? $_POST[ 'form_action' ] : false;
                $item_id = isset( $_POST[ 'item_id' ] ) && $_POST[ 'item_id' ] != '' ? $_POST[ 'item_id' ] : false;
                
                if( $item_id && $action == 'novo' ):
                    $action = 'update';
                endif;
                
                // Linha de fieldset
                if( $action == 'get_fieldset_row' ):

                    $form_key = isset( $_POST[ 'form_key' ] ) && $_POST[ 'form_key' ] != '' ? $_POST[ 'form_key' ] : false;
                    require_once( plugin_dir_path( __FILE__ ) . '/proccess-form.php' );
                    ProccessForm::fieldset_row( $form_key );
                
                // Submissão de formulário
                else:
                    
                    // Chave do formulário
                    $form_key = isset( $_POST[ 'form_key' ] ) && $_POST[ 'form_key' ] != '' ? $_POST[ 'form_key' ] : false;

                    // Token do formulário
                    $token = isset( $_POST[ $form_key ][ 'token' ] ) ? $_POST[ $form_key ][ 'token' ] : false;

                    if( empty( $token ) || !self::check_token( $form_key, $token ) ):
                        die( 'Sorry, your token did not verify.' );
                    endif;

                    // Arquivo de processamento de formulário
                    require_once( plugin_dir_path( __FILE__ ) . '/proccess-form.php' );
                    
                    // Executa o formulário
                    ProccessForm::proccess_form( $form_key, $action );
                
                endif;

            endif;
            
            exit();
        
        endif;
    }
    
    // Salva os ítems de formulários
    public static function save_pikiform_post( $post_id ){

        global $wpdb;

        // Post
        $post = get_post( $post_id );

        // Nothing to do
        if( $post->post_type !== PikiForms_ptype ):
            return;
        endif;

        // Autosave or not pikiform
        if ( ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $post->post_status == 'auto-draft' || $post->post_type != PikiForms_ptype ):
            return $post_id;
        endif;

        // Muda o post_name dos forms para não confligar com os slugs
        if( strpos( $post->post_name, 'pikiform' ) !== 0 ):
            $new_slug = wp_unique_post_slug( 'pikiform', $post_id, $post->post_status, $post->post_type, $post->post_parent  );
            $wpdb->query($wpdb->prepare( "UPDATE $wpdb->posts SET post_name = '%s' WHERE ID = %d", array( $new_slug, $post_id )));
        endif;

        $br = "\n";
        $towrite  = '<?php' . $br;

        // Chave do form
        $pikiform_key = 'pikiform' . $post->ID;

        // Campos
        $fields = get_post_meta( $post->ID, 'formfields', true );
        $fields = unserialize( base64_decode( $fields ) );

        // Post type settings
        if( ( $post_type = $_POST[ 'ptype_name' ] ) != '' && isset( $_POST[ 'ptype_active' ] ) ):

            // Habilita o tipo de post
            self::enable_post_type( $post_id );

            $towrite .= 'function ' . $pikiform_key . '_register(){' . $br;

            // Post type slug
                
                $post_type_slug = $_POST[ 'ptype_slug' ] == '' ? $post_type.'s' : $_POST[ 'ptype_slug' ];
            
            // Post type supports
                
                $post_type_supports = isset( $_POST[ 'ptype_supports' ] ) ? $_POST[ 'ptype_supports' ] : array();
                
                // Title
                $post_type_supports[] = 'title';
                
                // Body
                if( PikiFields::extract_field( $fields, 'body', 'ftype' ) ):
                    $post_type_supports[] = 'editor';
                endif;
                
                // Excerpt
                if( PikiFields::extract_field( $fields, 'excerpt', 'ftype' ) ):
                    $post_type_supports[] = 'excerpt';
                endif;
                $post_type_supports = array_unique( $post_type_supports );
           
            // Labels
                
                $post_type_labels = get_post_meta( $post->ID, 'ptype_labels', true );
                $post_type_labels = unserialize( base64_decode( $post_type_labels ) );
            
            // Menu icon
                
                if( ( $post_type_icon = trim( $_POST[ 'ptype_menu_icon' ] ) ) == '' ):
                    $post_type_icon = 'dashicons-edit';
                endif;
                if( ( $icon_file_id = (int)$_POST[ 'ptype_menu_icon_image' ][ 'ids' ] ) > 0 ):
                    $url_icon = wp_make_link_relative( wp_get_attachment_url( $icon_file_id ) );
                    $post_type_icon = substr( $url_icon, strpos( $url_icon, '/wp-content' ) );
                endif;

            // Configurações gerais
                
                $post_type_settings = 'array(' . $br;
                $post_type_settings .= '\'can_export\'=>' . ( isset( $_POST[ 'ptype_can_export' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'capability_type\'=>\'' . $post_type . '\',' . $br;
                $post_type_settings .= '\'description\'=>'. var_export( $_POST[ 'ptype_description' ], true ) .',' . $br;
                $post_type_settings .= '\'exclude_from_search\'=>'. ( isset( $_POST[ 'ptype_exclude_from_search' ] ) ? 'true' : 'false' ) .',' . $br;
                $post_type_settings .= '\'has_archive\'=>'. ( isset( $_POST[ 'ptype_has_archive' ] ) ? 'true' : 'false' ) .',' . $br;
                $post_type_settings .= '\'hierarchical\'=>false,' . $br;
                $post_type_settings .= '\'labels\'=>'. var_export(  $post_type_labels, true ) .',' . $br;
                $post_type_settings .= '\'map_meta_cap\'=>true,' . $br;
                $post_type_settings .= '\'menu_icon\'=>'. ( $icon_file_id > 0 ? 'WP_SITEURL.' : '' ) . '\'' . $post_type_icon . '\',' . $br;
                $post_type_settings .= '\'menu_position\'=>\''. ( $_POST[ 'ptype_menu_position' ] == '' ? 5 : (int)$_POST[ 'ptype_menu_position' ] ) . '\',' . $br;
                $post_type_settings .= '\'name\'=>\''. $post_type . '\',' . $br;
                $post_type_settings .= '\'can_export\'=>' . ( isset( $_POST[ 'ptype_can_export' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'public\'=>' . ( isset( $_POST[ 'ptype_public' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'publicly_queryable\'=>' . ( isset( $_POST[ 'ptype_publicly_queryable' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'query_var\'=>' . ( isset( $_POST[ 'ptype_query_var' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'rewrite\'=>array( \'slug\' => \'' . $post_type_slug . '\' ),' . $br;
                $post_type_settings .= '\'show_ui\'=>' . ( isset( $_POST[ 'ptype_show_ui' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'show_in_admin_bar\'=>' . ( isset( $_POST[ 'ptype_show_in_admin_bar' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'show_in_nav_menus\'=>' . ( isset( $_POST[ 'ptype_show_in_nav_menus' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'show_in_menu\'=>' . ( isset( $_POST[ 'ptype_show_in_menu' ] ) ? 'true' : 'false' ) . ',' . $br;
                $post_type_settings .= '\'supports\'=>' . ( empty( $post_type_supports ) ? 'false' : var_export( $post_type_supports, true ) ) . ',' . $br;
                $post_type_settings .= '\'taxonomies\'=>' . ( !isset( $_POST[ 'ptype_taxonomys' ] ) ? 'array()' : var_export( $_POST[ 'ptype_taxonomys' ], true ) ) . ',' . $br;
                $post_type_settings .= '\'slugtax\'=>' . ( empty( $_POST[ 'ptype_taxonomy_slug' ] ) ? 'false' : "'" . $_POST[ 'ptype_taxonomy_slug' ] . "'" ) . ',' . $br;
                $post_type_settings .= ')' . $br;
                $towrite .= '   $post_type_settings=' . $post_type_settings . ';' . $br;
                $towrite .= '   register_post_type( \''. $post_type .'\', $post_type_settings );' . $br;
   
            $towrite .= '}' . $br;

            // Taxonomia no slug
            $slugtax = empty( $_POST[ 'ptype_taxonomy_slug' ] ) ? false : $_POST[ 'ptype_taxonomy_slug' ];
            if( $slugtax ):
                update_option( 'pikiform_slugtax_' . $post_type, $slugtax );
            else:
                delete_option( 'pikiform_slugtax_' . $post_type );
            endif;

        else:

            // Desabilita o tipo de post
            self::disable_post_type( $post_id );

        endif;

        // Form settings
        $form_settings = array(
            'allways_edit'          => isset( $_POST[ 'ptype_allways_edit' ] ),
            'preview'               => isset( $_POST[ 'pikiform_preview' ] ),
            'moderate'              => isset( $_POST[ 'ptype_moderate' ] ),
            'placeholders'          => isset( $_POST[ 'pikiform_labels_inside' ] ),
            'pid'                   => $post->ID,
            'key'                   => $pikiform_key,
            'title'                 => $_POST[ 'pikiform_form_title' ],
            'description'           => $_POST[ 'pikiform_form_description' ],
            'edit_redirect'         => $_POST[ 'pikiform_edit_redirect' ],
            'success_redirect'      => $_POST[ 'pikiform_success_redirect' ],
            'exclude_redirect'      => $_POST[ 'pikiform_exclude_redirect' ],
            'success_message'       => $_POST[ 'pikiform_success_message' ],
            'edit_success_message'  => $_POST[ 'pikiform_edit_success_message' ],
            'error_message'         => $_POST[ 'pikiform_error_message' ],
            'error_messages'        => $_POST[ 'pikiform_error_messages' ],
            'classname'             => $_POST[ 'pikiform_classname' ],
            'attributes'            => $_POST[ 'pikiform_attributes' ],
            'submit_button_label'   => $_POST[ 'pikiform_submit_button_label' ],
            'edit_button_label'     => $_POST[ 'pikiform_edit_button_label' ],
            'email'                 => array(
                'send'              => isset( $_POST[ 'send_email' ] ),
                'subject'           => $_POST[ 'email_subject' ],
                'sender'            => $_POST[ 'email_sender' ],
                'to'                => $_POST[ 'email_to' ],
                'replyto'           => $_POST[ 'email_reply_to' ],
            ),
            'public' => isset( $_POST[ 'pikiform_public' ] ),
            'post_type' => $_POST[ 'ptype_name' ] == '' ? false : $_POST[ 'ptype_name' ],
            'post_type_active' => ( isset( $_POST[ 'ptype_active' ] ) && $_POST[ 'ptype_name' ] != '' )
        );

        // Permite que outros plugins altere as configurações do formulário
        $form_settings = apply_filters( 'pikiform_settings_write', $form_settings, $post_id );

        $settings_to_write = var_export( $form_settings, true );
        $settings_to_write = stripslashes( stripslashes( $settings_to_write ) );
        $settings_to_write = str_replace( 
            array( 'array (' ) , 
            array( 'array(' ),
            $settings_to_write
        );

        $towrite .= 'function pikiform_' . $pikiform_key . '_settings(){' . $br;
        $towrite .= '    return ' . $settings_to_write . ';' . $br;
        $towrite .= '}' . $br;
        $towrite .= 'function pikiform_' . $pikiform_key . '_fields(){' . $br;
        $towrite .= '    return ' . str_replace( 'array (', 'array(', var_export( $fields, true ) ) . ';' . $br;
        $towrite .= '}';

        // Caminho do arquivo
        $pathfile = self::form_filepath( $pikiform_key );
        
        // Escreve o arquivo
        if( !file_put_contents( $pathfile, $towrite, FILE_TEXT ) ):
            Piki::error( 'O arquivo ' . $pathfile . ' não pôde ser criado.' );
        endif;

        flush_rewrite_rules( true );

    }
    
    // Recupera um campo de um tipo de post  
    public static function get_ptype_field( $post_type, $field_name ){
        // ID do post do formulário
        $form_post_id = self::get_post_type_form_id( $post_type );
        // Campos do tipo de post
        $fields = self::get_ptype_form_fields( 'pikiform' . $form_post_id );
        // Campo desejado
        if( ( $founded = PikiFields::extract_field( $fields, $field_name ) ) !== false ):
            return $founded;
        endif;
        return false;
    }

    // Toogle post type
    public static function toogle_post_type( $ID, $action='disable' ){
        $post_types_option = get_option( 'pikiforms_ptypes', '' );
        $posts_types = $post_types_option == '' ? array() : explode( ',', $post_types_option );
        $key_exists = array_search( $ID, $posts_types );
        if( $action == 'disable' && $key_exists ):
            unset( $posts_types[ $key_exists ] );
        elseif( $action == 'enable' && !$key_exists ):
            $posts_types[] = $ID;
        endif;
        $unique = array_unique( $posts_types );
        update_option( 'pikiforms_ptypes', implode( ',', $unique ) );
    }

    // Enable post type
    public static function enable_post_type( $ID ){
        self::toogle_post_type( $ID, 'enable' );
    }

    // Disable post type
    public static function disable_post_type( $ID ){
        self::toogle_post_type( $ID, 'disable' );
    }

    public static function get_ptype_form_fields( $form_key ){

        if( $form_key == 'pikiform' ):
            echo '<pre>';
            var_dump( debug_backtrace() );
            exit;
        endif;

        // Se foi fornecido o ID do post do Form
        if( is_numeric( $form_key ) ):
            $form_key = 'pikiform'.$form_key;
        endif;
        $fields = call_user_func( 'pikiform_' . $form_key . '_fields' );
        return $fields;
    }

    // Mostra um formulário
    public static function show_form( $post_type, $content=false, $parent=false ){
       
        // ID do post do formulário
        $form_key = self::get_post_type_form_key( $post_type );
        
        // Pikirom para renderização
        global $pikiform;
        $pikiform = self::get_form( $form_key, $content, $parent );
       
        // Template
        Piki::search_template( 'pikiform', $form_key, plugin_dir_path( __FILE__ ) );
   
    }

    // Templates customizados
    public static function custom_template( $template ){
        global $wp_query;
        if ( isset( $wp_query->query_vars[ 'post_type' ] ) &&  $wp_query->query_vars[ 'post_type' ] == 'pikiform' ) {
            $template_exists = locate_template( 'pikiform-' . $wp_query->query_vars[ 'name' ] . '.php' );
            if ( $template_exists == '' ) { 
                $template = plugin_dir_path( __FILE__ ) . '/template.php';
            }
        }
        return $template;
    }

    // Carrega os arquivos necessários
    public static function add_files( $admin = false ){
        // Pikiforms files
        self::add_main_files();
        // Scripts
        wp_enqueue_script( 'jquery-ui-tooltip' );
        // Libraries
        Piki::add_library( 'jquery-form' );
        Piki::add_library( 'jquery-ui' );
        Piki::add_library( 'scroll' );
        Piki::add_library( 'fields-masks' );

        if( $admin ):
            wp_enqueue_script( 'pikiforms-admin-scripts', plugins_url( '/admin.js' , __FILE__ ), array( 'jquery' ), false, true );
        endif;

    }

    // Retorna um form
    public static function get_form( $form_key, $data=false, $parent=false ){

        if( is_array( $form_key ) ):
            $extra_settings = $form_key;
            $form_key = $extra_settings[ 'form_key' ];
        endif;
        
        // Configurações do formulário
        $settings = self::get_form_settings( $form_key );

        // Configurações adicionais
        if( isset( $extra_settings ) ):
            $settings = array_merge( $settings, $extra_settings );
        endif;

        // Se não há configurações para o form
        if( !$settings || empty( $settings ) ):
            return 'O formulário não existe';
        endif;

        // Se os dados serão salvos no banco, a permissão é verificada
        if( !self::user_can( $settings ) ):
            if( emtpy( $data ) ):
                Piki::error( 'Você não tem permissão para criar este tipo de conteúdo', 'permission' );
            else:
                Piki::error( 'Você não tem permissão para editar este conteúdo.', 'permission' );
            endif;
        endif;
            
        // Post pai
        if( $parent && (int)$parent > 0 ):
            $settings[ 'post_parent' ] = $parent;
        endif;
        
        // Atribui os dados ao array de configurações
        if( ( !isset( $settings[ 'data' ] ) || empty( $settings[ 'data' ] ) ) && !empty( $data ) ):
            $settings[ 'data' ] = $data;
        endif;

        // Token
        $settings[ 'token' ] = self::get_token( $form_key );
        
        // Instancia a classe que constrói o formuláro
        $form = new form( $settings );
        
        # Permite que outros plugins adicione arquivos adicionais
        if( $settings[ 'post_type' ] !== '' ):
            do_action( 'pikiforms_get_form_' . $settings[ 'post_type' ] );
        endif;
        
        // Formulário renderizado
        $rendered = $form->get_rendered_form();

        $settings[ 'html' ] = '';
        
        // Header do Form, se existir
        if( $rendered[ 'title' ] != '' || $rendered[ 'description' ]  != '' ):
            $settings[ 'html' ] .= '<header class="form-header">'. $rendered[ 'title' ] . $rendered[ 'description' ] .'</header>';
        endif;
        
        // Corpo do Form
        $settings[ 'html' ] .= $rendered[ 'header' ] . $rendered[ 'fields' ] . $rendered[ 'footer' ] ;

        // Adiciona os scripts e estilos
        PikiForms::add_files();
        
        // Retorna o formulário renderizado
        return $settings;
    
    }

    // Cria um Token
    public static function get_token( $form_key ){
        return wp_create_nonce( $form_key . '-' . basename( __FILE__ ) );
    }

    // Valida um token
    public static function check_token( $form_key, $token ){
        return wp_verify_nonce( $token, $form_key . '-' . basename( __FILE__ ) );
    }

    // Recupera o último post não publicado do usuário logado
    // Se não existir, um novo é criado
    public static function get_current_post_user( $settings ){
        $user = wp_get_current_user();
        $current = get_posts(array(
            'post_type' => $settings[ 'post_type' ],
            'post_status' => 'draft',
            'posts_per_page' => 1,
            'author' => $user->ID,
        ));
        if( empty( $current ) ):
            $new_post = array(
                'post_title' => 'Rascunho de ' . $user->data->display_name,
                'post_status' => 'draft',
                'post_date' => date('Y-m-d H:i:s'),
                'post_author' => $user->ID,
                'post_type' => $settings[ 'post_type' ],
            );
            return wp_insert_post($new_post);
        endif;
        return array_shift( $current );
    }

    // Shortcode que mostra formulários
    public static function shortcode_form( $atts ) {

        // Opções
        $defaults = array(
            'title' => false,
            'description' => false,
            'fid' => false,
        );
        $options = shortcode_atts( $defaults, $atts );
        $options = array_merge( $defaults, $options );
        
        // Chave do formulário
        if( $options[ 'fid' ] == '' ):
            return 'O ID do formulário não foi informado.';
        endif;
        if( (int)$options[ 'fid' ] > 0 ):
            $form_key = 'pikiform' . $options[ 'fid' ];
        else:
            $form_key = $options[ 'fid' ];
        endif;

        // Configurações para recuperação do formulário
        $settings_form = array( 'form_key' => $form_key );
        if( !empty( $options[ 'title' ] ) ):
            $settings_form[ 'title' ] = $options[ 'title' ];
        endif;
        if( !empty( $options[ 'description' ] ) ):
            $settings_form[ 'description' ] = $options[ 'description' ];
        endif;

        // Formulário
        $form = self::get_form( $settings_form );

        // Se o form não existe
        if( !is_array( $form ) ) return 'O form com o ID ' . $options[ 'fid' ] . ' não existe.';
        return $form[ 'html' ];
    }

    // Shortcode que mostra formulários
    public static function shortcode_button( $atts ) {

        // Opções
        $defaults = array(
            'form_key' => false,
            'class' => '',
            'label' => 'Abrir formulário',
            'icon' => false,
        );
        $options = shortcode_atts( $defaults, $atts );
        $options = array_merge( $defaults, $options );
        
        if( $options[ 'form_key' ] == '' ):
            return 'A chave do formulário não foi informada.';
        endif;
        
        // Chave do formulário
        if( (int)$options[ 'form_key' ] > 0 ):
            $form_key = 'pikiform' . $options[ 'form_key' ];
        else:
            $form_key = $options[ 'form_key' ];
        endif;

        // Label
        $label = !!$options[ 'icon' ] ? '<i class="icon icon-'. $options[ 'icon' ] .'"></i>' : $options[ 'label' ];
        $html = '<a href="'. get_site_url() .'/show-form/' . $form_key . '/" title="'. $options[ 'label' ] .'" class="pikiform-ajax-button '. $options[ 'class' ] .'">'. $label .'</a>';

        // Adiciona os scripts e estilos
        add_action( 'wp_enqueue_scripts', array( 'PikiForms', 'add_files' ) );

        return $html;
        
    }

    // Verifica as permissões do usuário
    public static function user_can( $settings ){

        global $wp_query;

        // Administradores
        if( current_user_can( 'manage_options' ) === true ):
            return true;
        endif;

        // Action
        $action = isset( $settings[ 'action' ] ) ? $settings[ 'action' ] : false;
        
        // Cadastro público
        if( on( $settings, 'public' ) && $action === 'insert'  ):
            return true;
        endif;
        
        // Form de cadastro
        if( $action == 'insert' ):
            if( !current_user_can( 'edit_' . $settings[ 'post_type' ] . 's'  ) ):
                Piki::error( 'Você não tem permissão para postar este conteúdo.', 'permission' );
            endif;
        elseif( $wp_query->get( "action" ) == 'edit' ):
            $post_id = $wp_query->get( 'post-id' );
            if( !current_user_can( 'edit_' . $settings[ 'post_type' ] . 's', $settings[ 'post_item' ]->ID ) ):
                Piki::error( 'Você não tem permissão para editar este conteúdo.', 'permission' );
            endif;
        endif;
        
        return true;
    
    }

    // Add no header
    public static function head_configs(){
        echo '<script type="text/javascript">PikiForms={
        imagesURL:"' . plugins_url( 'images/' , __FILE__ ) . '",
        pluginurl:"' . get_bloginfo( 'url' ) . '/piki-forms"
        }</script>';
    }

    // Arquivos principais dos forms
    public static function add_main_files(){
        Piki::add_library( 'jquery-form' );
        wp_enqueue_script( 'PikiForms-scripts', plugins_url( '/forms.js' , __FILE__ ), array( 'jquery' ), false, true );
        wp_enqueue_style( 'PikiForms-styles', plugins_url( '/forms.css' , __FILE__ ) );
    }

    // Path do arquivo de configurações do pikiform
    public static function form_filepath( $pikiform_key ){
        return plugin_dir_path( __FILE__ ) . 'forms/' . $pikiform_key . '.php';
    }

    // ID do Pikiform de determinado tipo de conteúdo
    public static function get_post_type_form_id( $post_type ){
        global $wpdb;
        $form_post_id = $wpdb->get_var($wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta AS META WHERE META.meta_key = 'ptype_name' AND META.meta_value = %s", $post_type ) );
        if( (int)$form_post_id > 0 ):
            return $form_post_id;
        endif;
        return false;

    }

    // Chave do formulário por tipo de post
    public static function get_post_type_form_key( $post_type ){

        $function = 'pikiform_' . $post_type . '_settings';
        if( function_exists( $function ) ):
            return $post_type;
        endif;

        $key_by_id = self::get_post_type_form_id( $post_type );
        if( $key_by_id ):
            return 'pikiform'.$key_by_id;
        endif;

        return false;

    }

    // Adiciona um iframe para algumas submissões
    public static function add_iframe(){?>
        <iframe name="pikiform-iframe" id="pikiform-iframe" style="display:none;" src="about:blank"></iframe>
    <?php }

    // Envio de emails
    public static function mail( $to, $subject, $message, $from=false, $replyto=false ){

        global $phpmailer;

        if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
            require_once ABSPATH . WPINC . '/class-phpmailer.php';
            require_once ABSPATH . WPINC . '/class-smtp.php';
            $phpmailer = new PHPMailer( true );
        }

        $phpmailer->SMTPDebug = false;

        // Empty out the values that may be set
        $phpmailer->ClearAllRecipients();
        $phpmailer->ClearAttachments();
        $phpmailer->ClearCustomHeaders();
        $phpmailer->ClearReplyTos();

        $phpmailer->ContentType = 'text/html';
        $phpmailer->CharSet = get_bloginfo( 'charset' );
        $phpmailer->IsHTML( true );


        // From
        if( empty( $from ) ):
            $from = get_option( 'admin_email' );
        endif;
        if( strpos( $from, '<' ) > -1 ):
            list( $from_name, $from_email ) = explode( '<', str_replace( '>', '', $from ) );
        else:
            $from_email = $from;
            $from_name = '';
        endif;
        $phpmailer->SetFrom ( $from_email, $from_name );
        $phpmailer->Sender = $phpmailer->From;

        // To
        if( !is_array( $to ) ):
            $to = explode( ',', $to );
        endif;
        foreach( $to as $kto => $item_to ):
            $phpmailer->AddAddress ( trim( $item_to ) );
        endforeach;

        // Reply To
        if( $replyto ):
            $phpmailer->AddReplyTo( $replyto );
        endif;

        // Subject
        $phpmailer->Subject = '=?UTF-8?B?' . base64_encode( $subject ) . '?=';

        // Body
        $phpmailer->MsgHTML( $message );

        // SMTP
        $smtp_options = get_option( 'piki_smtp_options' );

        if( isset( $smtp_options[ 'status' ] ) && $smtp_options[ 'status' ] == 'on' ):

            $phpmailer->Mailer = 'smtp';
            $phpmailer->SMTPSecure = false;
            $phpmailer->Host = $smtp_options[ 'host' ];
            $phpmailer->Port = $smtp_options[ 'port' ];
            $phpmailer->SMTPAuth = TRUE;
            $phpmailer->Username = $smtp_options[ 'username' ];
            $phpmailer->Password = $smtp_options[ 'password' ];

        endif;

        $response = $phpmailer->Send();

        return $response;

    }
    
}

// Autoload
function piki_forms_autoload( $className ){
    $classes = array( 'calendar', 'files', 'form', 'images', 'negocio', 'show', 'valida' );
    if( in_array( $className, $classes ) ):
        require_once( plugin_dir_path( __FILE__ ) . '/class/class.' . $className . '.inc' );
    endif;
}
spl_autoload_register( 'piki_forms_autoload' );

// Remove atributos das tags html
add_filter( 'cmb_validate_wysiwyg', 'premier_cmb_validate_wysiwyg' );
function premier_cmb_validate_wysiwyg( $new ) {
    $new = str_replace( '\"', '"', $new );
    $new = preg_replace('#\s(id|required|class|valign|align|nowrap|width|height|style|border|padding|cellpadding|cellspacing)="[^"]+"#', '', $new);
    $new = str_replace( '"', '\"', $new );
    return $new;
}

// Init
add_action( 'init', array( 'PikiForms', 'init' ) );

// Header and Footer actions
add_action( 'wp_head', array('PikiForms', 'head_configs') );
add_action( 'admin_head', array('PikiForms', 'head_configs') );
add_action( 'wp_footer', array( 'PikiForms', 'add_iframe' ) );

// Scripts and Styls
add_action( 'wp_enqueue_scripts', array( 'PikiForms', 'add_main_files' ), 0 );
add_action( 'admin_enqueue_scripts', array( 'PikiForms', 'add_main_files' ), 0 );

// Pages register
add_filter( 'query_vars', array( 'PikiForms', 'add_query_vars' ) );
//add_filter( 'the_content', array( 'PikiForms', 'content_filter' ), 20 );
add_action( 'generate_rewrite_rules', array( 'PikiForms', 'create_rewrite_rules' ) );
add_action( 'template_redirect', array( 'PikiForms', 'template_redirect_intercept' ) );

// Posts hooks
add_action( 'save_post', array( 'PikiForms', 'save_pikiform_post' ), 20 );
//add_action( 'delete_post', array( 'PikiForms', 'delete_post' ), 20 );


// Apenas ADMIN
if( is_admin() ):
    // Tipo de conteúdo Piki Form
    require_once( plugin_dir_path( __FILE__ ) . '/forms-ctype.php' );
    // Formulários no admin
    require_once( plugin_dir_path( __FILE__ ) . '/admin.php' );
endif;

