<?php
class PKFAdmin {

    var $form_key;
    var $settings;
    var $action;

    // Add options page
    public function add_plugin_page() {
        add_submenu_page(
            'piki-dashboard', 
            'Forms', 
            'Forms', 
            'manage_options', 
            'edit.php?post_type=' . PikiForms_ptype
        );
    }

   	// Campos dos formulários do ADMIN
    public function admin_form_fields(){
        
        // Página carregada
        global $pagenow;
        
        // Ação
        $this->action = isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] != '' ? $_GET[ 'action' ] : false;
        
        // Páginas permitidas
        $edit_pages = array( 'post-new.php', 'post.php' );
        
        // Tipo de post
        $post_type = false;
        if( $pagenow == 'post-new.php' ):
            $post_type = $_GET[ 'post_type' ];
        elseif( $pagenow =='post.php' && isset( $_GET[ 'post' ] ) ):
            $post_type = get_post_type( $_GET[ 'post' ] );
        endif;
        
        // Não registra os campos na página de configuração do formulário
        if( !$post_type || $post_type == 'pikiform' || !in_array( $pagenow, $edit_pages ) ):
            return;
        endif;
       	
        // Chave do formulário
        $this->form_key = PikiForms::get_post_type_form_key( $post_type );
        
        // Não existe formulário para este tipo de post
        if( !$this->form_key ):
            return;
        endif;

        // Configurações do formuláro
        $this->settings = PikiForms::get_form_settings( $this->form_key );
        
        // Se não existe configurações para este tipo de conteúdo
        if( empty( $this->settings ) || empty( $this->settings[ 'fields' ] ) ):
            return;
        endif;

        // Remove o campo de título se existir
        PikiFields::remove_fields( $this->settings[ 'fields' ], array( 'title', 'body', 'excerpt', 'taxonomy' ) );

        // Metabox
        add_meta_box( 
            'formfields', 
            'Informações', 
            array( $this, 'register_form_fields' ), 
            $post_type, 
            'advanced', 
            'default'
        );
    
    }

    // Registra os campos dos formulários do ADMIN
    public function register_form_fields( $post ){

        // Post
        $this->settings[ 'data' ] = $post;                
        
        // Constroi os campos do form
        $form = new form( $this->settings );
        $rendered = $form->get_rendered_form();
        
        // HTML
        echo $rendered[ 'fields' ];
        echo '<input type="hidden" name="form_key" id="form_key" value="'. $this->form_key .'" class="form-key" />';
        //echo '<input type="hidden" name="action" id="action" value="admin_form_validate" class="form-action" />';
        //echo '<input type="hidden" name="item_id" id="item_id" value="'. $post->ID .'" />';
        
        // Scripts and Styles
        PikiForms::add_files( true );
    
    }

    // Salva os campos extras dos posts
    public static function save_custom_fields( $post_id ){
        if( defined( '__PIKIFORM_SAVING_CUSTOM_FIELDS__' ) && __PIKIFORM_SAVING_CUSTOM_FIELDS__ === true ):
            return;
        endif;
        define( '__PIKIFORM_SAVING_CUSTOM_FIELDS__', true );
        // Post
        $post = get_post( $post_id );
        // Autosave or not pikiform
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $post->post_status == 'auto-draft' || $post->post_status == 'trash' ):
            return $post_id;
        endif;
        // Configurações do pikiform
        $pikiform_settings = PikiForms::get_form_settings( $post );
        // Se existe configurações de formulário para o tipo de post
        if( $pikiform_settings ):
            // Processa os valores do formulário
            PKFAdmin::proccess_fields( $post, $pikiform_settings );
        endif;
    }
    
    // Valida o formulário
    public static function form_validate() {

        // Classe de processamento do form
        require_once( plugin_dir_path( __FILE__ ) . '/proccess-form.php' );

        // Chave do formulário
        $form_key = isset( $_POST[ 'form_key' ] ) ? $_POST[ 'form_key' ] : false;
        if( empty( $form_key ) ):
            Piki::error( 'Chave do formulário não informada.' );
        endif;
        
        // Ítem postado
        $post_ID = $_POST[ 'post_ID' ];
        if( empty( $post_ID ) ):
            Piki::error( 'ID do post não informado.' );
        endif;
        $post_item = PKMeta::db_data( $post_ID );
        if( empty( $post_item ) ):
            Piki::error( 'ID do post inválido.' );
        endif;

        // Inicia a classe de processamento 
        $proccess = new formProccess( $form_key, $post_item );

        // Valida os campos
        $validation = $proccess->valida();

        // Retorno
        if( $validation === true ):
            Piki::success();
        endif;

    }

    // Processa os campos do formulário
    public static function proccess_fields( $post, $settings ){
        
        // Dados postados
        $posted = isset( $_POST[ $settings[ 'key' ] ] ) ? $_POST[ $settings[ 'key' ] ] : false;

        // Se nada foi postado
        if( empty( $posted ) ):
            return;
        endif;
                
        // Remove o campo de título se existir
        PikiFields::remove_fields( $settings[ 'fields' ], array( 'title', 'excerpt', 'body', 'taxonomy' ) );

        // Valida os campos
        //$valida = new valida( $settings, $posted );
        //$validation = $valida->get_validation();
        //if( is_array( $validation ) && !empty( $validation ) ):
        //    PikiForms::set_notice( $validation );
        //endif;

        // Campos com suas configurações
        $fields = PikiFields::prepare_fields( $settings[ 'fields' ] );

        // Remove os valores antigos
        PKMeta::delete_post_meta( $post->ID, $fields );
        
        // Salva os valores
        $saved = PKMeta::save_post_meta( 
            $post->ID, 
            $fields, 
            $posted, 
            $settings[ 'data_type' ], 
            $settings[ 'table' ] 
        );
    
    }

	// Permite o upload de imagens
    public function edit_form_tag(){
        # Mensagem padrão de erro
        $error_message = isset( $this->settings[ 'error_message' ] ) && !empty( $this->settings[ 'error_message' ] ) ? $this->settings[ 'error_message' ] : 'Preencha corretamento os campos marcados';
        # Error messages
        $attr_error_messages = '';
        if( is_array( $this->settings[ 'error_messages' ] ) ):
            foreach ( $this->settings[ 'error_messages' ] as $key_em => $em ):
                $attr_error_messages .= 'pikiform-message-' . $key_em . '="true" ';
            endforeach;
        endif;
        # Adicionando atributos
        echo ' enctype="multipart/form-data" class="pikiform" error-message="', $error_message, '"', $attr_error_messages;
    }

}
$PKFAdmin = new PKFAdmin();

// Ajax de validação
add_action( 'wp_ajax_admin_form_validate', array( 'PKFAdmin', 'form_validate' ) );
// Adiciona o link no menu
add_action( 'admin_menu', array( $PKFAdmin, 'add_plugin_page' ) );
// Registra os campos no formulário do wordpresss
add_action( 'admin_menu', array( $PKFAdmin, 'admin_form_fields' ) );
// Permite o upload de imagens
add_action( 'post_edit_form_tag' , array( $PKFAdmin, 'edit_form_tag' ) );
// Salva os campos adicionais no formulário do admin
add_action( 'save_post', array( 'PKFAdmin', 'save_custom_fields' ), 20 );

