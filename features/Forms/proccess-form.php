<?php
class formProccess {

    var $posted;
    var $item;
    var $settings;
    var $steps;
    var $action;

    function __construct( $form_key, $post_item = false ){

        # Dados postados
        $this->posted = isset( $_POST[ $form_key ] ) ? $_POST[ $form_key ] : false;
        
        # Configurações do formulário
        $this->settings = PikiForms::get_form_settings( $form_key, $posted );

        # Ítem postado
        if( !empty( $post_item ) ):
            $this->item = $post_item;
        else:
            $this->item = self::get_posted_item();
        endif;
        $this->settings[ 'post_item' ] = $this->item;

        # Se o ítem vai ser atualizado, ou um novo será um novo
        $this->action = !$this->item ? 'insert' : 'update';
        $this->settings[ 'action' ] = $this->action;

        # Permissões do usuário
        PikiForms::user_can( $this->settings );

        # Verifica se o form tem passos. Se sim, valida apenas o passo atual
        $this->steps = form::get_steps( $this->settings[ 'fields' ] );

    }

    public function valida(){
        
        # Classe de validação
        $valida = new valida( $this->settings, $this->posted );
        
        # Validação dos campos
        if( $this->steps !== false ):
            
            # Chave do passo postado
            $posted_step = isset( $_POST[ 'form_step' ] ) && $_POST[ 'form_step' ] != '' ? $_POST[ 'form_step' ] : false;
            
            # Verifica se o passo existe
            if( !$posted_step || !isset( $steps[ $posted_step ] ) ):
                Piki::Error( 'O passo ' . $_POST[ 'form_step' ] . ' não existe no formulário ' . $this->settings[ 'key' ] );
            endif;
            
            # Setps status
            $validation = array();
            foreach ( $steps as $step_key => $step ):
                # Seta apenas os campos do passo para validação
                $valida->fields = $step[ 'fields' ];
                $validation[ $step_key ] = $valida->get_validation();
            endforeach;
            
            # Permite que outros plugins alterem a validação
            $validation = apply_filters( 'pikiform_valida', $validation, $this->settings, $this->posted );
            
            # Verifica se existem passos com erros
            $have_errors = false;
            foreach ( $steps as $step_key => $step ):
                # Se exite um erro, ativa a chave que reportará os mesmos
                if( $validation[ $step_key ][ 'status' ] == 'error' ):
                    $have_errors = true;
                endif;
            endforeach;
            
            # Salva os campos do passo postado
            //if( !empty( $validation[ $posted_step ][ 'valids' ] ) && is_object( $settings[ 'post_item' ] ) ):
                //$settings_to_save = $settings;
                //$settings_to_save[ 'fields' ] = $validation[ $posted_step ][ 'valids' ];
                //$saved = self::save_data( $settings_to_save, $posted );
            //endif;
            
            # Se há erros
            if( $have_errors ):
                
                # Permite que outros plugins façam algo antes de reportar os erros
                do_action( 'pikiform_error', $validation, $this->settings, $this->posted );

                # Retorno do resultado
                Piki::return_json(array(
                    'status' => 'error',
                    'error_type' => 'valida',
                    'errors' => $validation
                ));
            
            endif;
        
        else:

            # Valida o form sem passo
            $validation = $valida->get_validation();

            # Permite que outros plugins alterem a validação
            $validation = apply_filters( 'pikiform_valida', $validation, $this->settings, $this->posted );
            if( !empty( $validation[ 'errors' ] ) ):
                $validation[ 'status' ] = 'error';
            endif;
            
            # Mostra os erros, se existirem
            if( $validation[ 'status' ] === 'error' ):
                
                # Salva os campos válidos no caso de edição
                //if( !empty( $validation[ 'valids' ] ) && is_object( $settings[ 'post_item' ] ) ):
                    //$settings_to_save = $settings;
                    //$settings_to_save[ 'fields' ] = $validation[ 'valids' ];
                    //$saved = self::save_data( $settings_to_save, $posted );
                    //$settings[ 'post_item' ] = get_post( $saved[ 'ID' ] );
                //endif;

                # Permite que outros plugins façam algo antes de reportar os erros
                do_action( 'pikiform_error', $validation, $this->settings, $this->posted );
                Piki::return_json(array(
                    'status'    => 'error',
                    'error_type' => 'valida',
                    'errors'    => $validation[ 'errors' ]
                ));
                
            endif;

        endif;

        return true;


    }

    public function submit(){

    }

    # Recupera o ítem postado
    public function get_posted_item(){
        $item_id = !isset( $_POST[ 'item_id' ] ) || (int)$_POST[ 'item_id' ] < 1 ? false : $_POST[ 'item_id' ];
        if( !$item_id ):
            return false;
        endif;            
        $data_table = isset( $settings[ 'table' ] ) ? $settings[ 'table' ] : null;
        $item = PKMeta::db_data( $item_id, $this->settings[ 'fields' ], $this->settings[ 'data_type' ], $data_table );
        if( !$item || empty( $item ) || is_null( $item ) ):
            return false;
        endif;
        return $item;
    }

}

class ProccessForm extends PikiForms {
   
    # Processa o formulário
    public static function proccess_form( $form_key ){

        # Dados postados
        $posted = isset( $_POST[ $form_key ] ) ? $_POST[ $form_key ] : false;
        
        # Configurações do formulário
        $settings = parent::get_form_settings( $form_key, $posted );
        
        # Ítem postado
        if( !empty( $post_item ) ):
            $settings[ 'post_item' ] = $post_item;
        else:
            $settings[ 'post_item' ] = self::get_posted_item( $settings );
        endif;

        # Tabela
        if( !isset( $settings[ 'table' ] ) ):
            $settings[ 'table' ] = false;
        endif;
        
        # Se o ítem vai ser atualizado, ou um novo será um novo
        $settings[ 'action' ] = isset( $settings[ 'post_item' ] ) && $settings[ 'post_item' ] !== false ? 'update' : 'insert';
        
        # Permissões do usuário
        parent::user_can( $settings );
        
        # Verifica se o form tem passos. Se sim, valida apenas o passo atual
        $steps = form::get_steps( $settings[ 'fields' ] );
        
        # Classe de validação
        $valida = new valida( $settings, $posted );
        
        # Validação dos campos
        if( $steps !== false ):
            
            # Chave do passo postado
            $posted_step = isset( $_POST[ 'form_step' ] ) && $_POST[ 'form_step' ] != '' ? $_POST[ 'form_step' ] : false;
            
            # Verifica se o passo existe
            if( !$posted_step || !isset( $steps[ $posted_step ] ) ):
                Piki::Error( 'O passo ' . $_POST[ 'form_step' ] . ' não existe no formulário ' . $settings[ 'key' ] );
            endif;
            
            # Setps status
            $validation = array();
            foreach ( $steps as $step_key => $step ):
                # Seta apenas os campos do passo para validação
                $valida->fields = $step[ 'fields' ];
                $validation[ $step_key ] = $valida->get_validation();
            endforeach;
            
            # Permite que outros plugins alterem a validação
            $validation = apply_filters( 'pikiform_valida', $validation, $settings, $posted );
            
            # Verifica se existem passos com erros
            $have_errors = false;
            foreach ( $steps as $step_key => $step ):
                # Se exite um erro, ativa a chave que reportará os mesmos
                if( $validation[ $step_key ][ 'status' ] == 'error' ):
                    $have_errors = true;
                endif;
            endforeach;
            
            # Salva os campos do passo postado
            if( !empty( $validation[ $posted_step ][ 'valids' ] ) && is_object( $settings[ 'post_item' ] ) ):
                //$settings_to_save = $settings;
                //$settings_to_save[ 'fields' ] = $validation[ $posted_step ][ 'valids' ];
                //$saved = self::save_data( $settings_to_save, $posted );
            endif;
            
            # Se há erros
            if( $have_errors ):
                # Permite que outros plugins façam algo antes de reportar os erros
                do_action( 'pikiform_error', $validation, $settings, $posted );

                # Retorno do resultado
                Piki::return_json(array(
                    'status' => 'error',
                    'error_type' => 'valida',
                    'errors' => $validation
                ));
            endif;
        
        else:

            # Valida o form sem passo
            $validation = $valida->get_validation();

            # Permite que outros plugins alterem a validação
            $validation = apply_filters( 'pikiform_valida', $validation, $settings, $posted );
            if( !empty( $validation[ 'errors' ] ) ):
                $validation[ 'status' ] = 'error';
            endif;
            
            # Mostra os erros, se existirem
            if( $validation[ 'status' ] === 'error' ):
                
                # Salva os campos válidos no caso de edição
                //if( !empty( $validation[ 'valids' ] ) && is_object( $settings[ 'post_item' ] ) ):
                    //$settings_to_save = $settings;
                    //$settings_to_save[ 'fields' ] = $validation[ 'valids' ];
                    //$saved = self::save_data( $settings_to_save, $posted );
                    //$settings[ 'post_item' ] = get_post( $saved[ 'ID' ] );
                //endif;

                # Permite que outros plugins façam algo antes de reportar os erros
                do_action( 'pikiform_error', $validation, $settings, $posted );
                Piki::return_json(array(
                    'status'    => 'error',
                    'error_type' => 'valida',
                    'errors'    => $validation[ 'errors' ]
                ));
                
            endif;

        endif;

        # Submete os dados
        self::submit( $settings, $posted );

    }

    # Faz a submissão do form
    public static function submit( $settings, $posted ){
        
        $return = array();
        $type_error = '';
        
        # Passos do formulário, se existirem
        $steps = form::get_steps( $settings[ 'fields' ] );
        
        # Ação passada pelo form
        $posted_action = $_POST[ 'form_action' ];
        
        if( $steps !== false ):
            # Passo postado
            $posted_step = isset( $_POST[ 'form_step' ] ) && $_POST[ 'form_step' ] != '' ? $_POST[ 'form_step' ] : false;
            # Último passo
            $steps_keys = array_keys( $steps );
            $last_step = array_pop( $steps_keys );
            # Faz alguma ação depois de um passo
            do_action( 'pikiform_submit_step', $settings, $posted, $posted_step );
            # Se não é o último passo, retorna success
            if( $last_step != $posted_step ):
                Piki::success();
            endif;
        endif;
        
        # Redirecionamento
        if( $settings[ 'success_redirect' ] != '' ):
            if( strpos( $settings[ 'success_redirect' ], '/' ) === 0 ):
                $success_redirect = get_site_url( NULL, $settings[ 'success_redirect' ] );
            else:
                $success_redirect = Piki::http( $settings[ 'success_redirect' ] );
            endif;
        else:
            $success_redirect = false;
        endif;
        
        # Mensagem
        if( $settings[ 'action' ] == 'update' ):
            $success_message = $settings[ 'edit_success_message' ] != '' ? $settings[ 'edit_success_message' ] : 'Dados atualizados com sucesso';
        else:
            $success_message = $settings[ 'success_message' ] != '' ? $settings[ 'success_message' ] : 'Cadastro realizado com sucesso';
        endif;
        
        self::message_shortcodes( $success_message, $settings, $posted );
        
        # Se a ação for para publicar o post
        if( $settings[ 'data_type' ] === 'post' && $posted_action === 'publish' ):
            
            # Publica o post
            self::publish_post( $settings[ 'post_item' ] );
            
            # Envia o email
            if( $settings[ 'send_email' ] == 'on' ):
                $email_sent = self::send_email( $settings, $posted );
            endif;
            
            # Mensagem de sucesso
            Piki::success( $success_message );
        
        endif;

        # Gravar em banco
        if( 
        # Posts
        ( on( $settings, 'post_type_active' ) && $settings[ 'post_type' ] !== '' )
        ||
        # Custom 
        ( $settings[ 'data_type' ] === 'custom' && !empty( $settings[ 'table' ] ) ) 
        ):

            # Salvando os dados
            $saving = self::save_data( $settings, $posted );

            if ( !is_array( $saving ) ) {
                Piki::error( 'Ocorreu um problema inesperado. Por favor, tente mais tarde', $saving[ 'type_error' ] );
            }

            # Guardamos o ítem salvo
            $settings[ 'post_item' ] = get_post( $saving[ 'ID' ] );
        
        endif;
        
        # Se a ação é pra pré visualizar o post
        if ( $posted_action == 'preview' ):
            global $form_settings;
            $form_settings = $settings;
            Piki::search_template( 'piki-forms-preview', str_replace( '_', '-', $settings[ 'key' ] ), plugin_dir_path( __FILE__ ) );
            exit();
        endif;

        # Se o form deve disparar um email
        if( $settings[ 'email' ][ 'send' ] == 'on' && $settings[ 'action' ] == 'insert' ):
            $email_sent = self::send_email( $settings, $posted );
        endif;
        
        do_action( 'pikiform_submit', $settings, $posted );
        
        $response = array(
            'message' => $success_message,
            'redirect' => $success_redirect,
            'action' => $settings[ 'action' ],
        );

        if( isset( $settings[ 'post_item' ] ) ):
            $response[ 'post' ] = $settings[ 'post_item' ];
        endif;
       
        Piki::success( $response );
    }


    # Faz o replace dos shortcodes
    public static function message_shortcodes( &$message, $settings, $posted, $renderize=true ){

        $message = str_replace( '[home]', get_bloginfo( 'url' ), $message );
        # Data
        $post = isset( $settings[ 'post_item' ] ) ? $settings[ 'post_item' ] : false;
        preg_match_all('/\[\w*\]/', $message, $matches );
        $rpcts = array_shift( $matches );
        
        # Se não existem códigos
        if( empty( $rpcts ) ):
            return $message;
        endif;
        $user = wp_get_current_user();
        foreach ( $rpcts as $key => $shortcode ) {
            
            $cleaned = str_replace( array( '[', ']' ), '', $shortcode );
            
            # Dados dos campos
            if( strpos( $shortcode, '[field_' ) === 0 ):
                
                $fieldname = str_replace( 'field_', '', $cleaned );
               
                $field = PikiFields::extract_field( $settings[ 'fields' ], $fieldname );
                
                # Se o shortcode está incorreto, e não representa um campo válido
                if( !$field ) continue;
                
                # Valor do banco
                if( !!$post ):
                    $toinsert = PKMeta::get_field_value( $field, $post->ID );
                
                # Valor postado
                else:
                    $toinsert = PikiField::get_posted_value( $field, $settings[ 'fields' ], $posted );
                endif;
                
                # Renderização
                if( $renderize ):
                    if( method_exists( $field[ 'ftype' ], 'renderize_values' ) ):
                        $toinsert = call_user_func( array( $field[ 'ftype' ], 'renderize_values' ), $field, $toinsert );
                    else:
                        $toinsert = is_array( $toinsert ) ? array_shift( $toinsert ) : $toinsert;
                    endif;
                endif;

                # Faz a substituição
                $message = str_replace( $shortcode, $toinsert, $message );

            # Dados do usuário
            elseif( strpos( $shortcode, '[user_' ) === 0 ):
                
                $message = str_replace( $shortcode, $user->data->$cleaned, $message );
            
            endif;

        }
    }

    # Publica um post
    public static function publish_post( $post ){
        if( !$post ):
            Piki::error( array( 'O post não existe', 'no_post_id' ) );
        elseif( !current_user_can( 'publish_' . $post->post_type . 's', $post->ID ) ):
            Piki::error( array( 'Você não tem permissão para publicar este post', 'permission' ) );
        endif;
        $to_update = array(
            'ID' => $post->ID,
            'post_status' => 'publish'
        );
        return wp_update_post( $to_update );
        return true;
    }

    # Recupera o ítem postado
    public static function get_posted_item( $settings ){

        $item_id = !isset( $_POST[ 'item_id' ] ) || (int)$_POST[ 'item_id' ] < 1 ? false : $_POST[ 'item_id' ];
        
        if( !$item_id ):
            return false;
        endif;            

        $data_table = isset( $settings[ 'table' ] ) ? $settings[ 'table' ] : null;
        
        $item = PKMeta::db_data( $item_id, $settings[ 'fields' ], $settings[ 'data_type' ], $data_table );
                
        if( !$item || empty( $item ) || is_null( $item ) ):
            return false;
        endif;
        
        return $item;

    }

    # Salva o dados do formulário
    public static function save_data( $settings, $posted ){

        global $wpdb;
                
        # Permite que outros plugins alterem as configurações antes de serem inseridos no banco
        $settings = apply_filters( 'pikiform_presave_settings', $settings, $posted );
        
        # Permite que outros plugins alterem os dados antes de serem inseridos no banco
        $posted = apply_filters( 'pikiform_presave_posted', $posted, $settings );
                
        if( empty( $settings[ 'post_item' ] ) ):
            
            $action = 'insert';
            
            # Status
            $status = ( $settings[ 'preview' ] == 'on' || $settings[ 'moderate' ] == 'on' ) ? 'draft' : 'publish';
            
            # User ID
            $owner = get_current_user_id();

            # Cria o ítem
            switch( $settings[ 'data_type' ] ):

                # Custom
                case 'custom':

                    # Cria o ítem
                    $wpdb->insert( 
                        $wpdb->prefix . $settings[ 'table' ], 
                        array( 'created' => date( 'Y-m-d H:i:s' ) ), 
                        array( '%s' ) 
                    );
                    
                    # ID do ítem criado
                    $item_id = $wpdb->insert_id;
                    
                    # Recupera o ítem criado
                    $settings[ 'post_item' ] = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $wpdb->prefix" . $settings[ 'table' ] . " WHERE ID = %d",
                        $item_id
                    ));

                break;

                 # Post
                default:

                    # Campo de título
                    $ftitle = PikiFields::extract_field( $settings[ 'fields' ], 'title', 'ftype', true );
                    $title = PikiField::get_posted_value( $ftitle, $settings[ 'fields' ], $posted );

                    # Novo post
                    $new_item = array(
                        'post_title' => $title,
                        'post_type' => $settings[ 'post_type' ],
                        'post_status' => $status,
                        'post_author' => $owner,
                        'comment_status' => 'closed',
                    );

                    # Body
                    $fbody = PikiFields::extract_field( $settings[ 'fields' ], 'body', 'ftype', true );
                    if( $fbody ):
                        $new_item[ 'post_content' ] = PikiField::get_posted_value( $fbody, $settings[ 'fields' ], $posted );
                    endif;

                    # Excerpt
                    $fexcerpt = PikiFields::extract_field( $settings[ 'fields' ], 'excerpt', 'ftype', true );
                    if( $fexcerpt ):
                        $new_item[ 'post_excerpt' ] = PikiField::get_posted_value( $fexcerpt, $settings[ 'fields' ], $posted );
                    endif;
                    
                    # Parent
                    $parent = isset( $_POST[ 'item_parent' ] ) && (int)$_POST[ 'item_parent' ] > 0 ? $_POST[ 'item_parent' ] : false;
                    if( $parent ):
                        $new_item[ 'post_parent' ] = $parent;
                    endif;
                    
                    # Insert the post into the database
                    $return = wp_insert_post( $new_item, true );
                    if( is_wp_error( $return ) ){
                        Piki::error( $return->get_error_message() );
                    }
                    
                    # Item
                    $settings[ 'post_item' ] = get_post( $return );

                break;
           
           endswitch;
            
        else:
            
            # Action
            $action = 'edit';

            # Edita o ítem
            switch( $settings[ 'data_type' ] ):
                
                # Custom data type
                case 'custom':
                    $update = $wpdb->update( 
                        $wpdb->prefix . $settings[ 'table' ], 
                        array( 'modified' => date( 'Y-m-d H:i:s' ) ), 
                        array( 'ID' => $settings[ 'post_item' ]->ID ), 
                        array( '%s' ), 
                        array( '%d' )
                    );
                break;
                
                # Post
                default:
                    wp_update_post(array(
                        'ID' => $settings[ 'post_item' ]->ID,
                    ));          
                break;

            endswitch;
        
        endif;

        # Salva os valores
        $saved = PKMeta::save_post_meta( 
            $settings[ 'post_item' ]->ID, # ID do ítem
            $settings[ 'fields' ], # Fields
            $posted, # Data postada
            $settings[ 'data_type' ], # Tipo de data 
            $settings[ 'table' ] # Tabela no banco, no caso de custom type
        );
        
        # Retorno
        $return = array( 
            'ID' => $settings[ 'post_item' ]->ID,
            'item' => $settings[ 'post_item' ]->ID,
            'action' => $action,
        );

        if( $settings[ 'data_type' ] === 'post' ):
            $return[ 'post' ] = $settings[ 'post_item' ];
        endif;

        return $return;

    }

    # Busca um campo padrão
    public static function search_default_field( $fields, $values, $ftype ){
        foreach( $fields as $key => $field ):
            if( $field[ 'ftype' ] == 'fieldset' ):
                foreach ( $field[ 'subfields' ] as $subkey => $subfield ) {
                    if( $subfield[ 'ftype' ] == $ftype ):
                        return $values[ $key ][ $subkey ][ 0 ];
                    endif;
                }
            elseif( $field[ 'ftype' ] == $ftype ):
                return $values[ $key ];
            endif;
        endforeach;
        return false;
    }


    public static function send_email( $settings, $posted ){
        
        # Defaults
        $email_configs = $settings[ 'email' ];
        $email_configs[ 'content' ] = '';

        # Email de destino
        $email_configs[ 'to' ] = trim( str_replace( ' ', '', $email_configs[ 'to' ] ) );
        if( $email_configs[ 'to' ] && $email_configs[ 'to' ] != '' ):
            self::message_shortcodes( $email_configs[ 'to' ], $settings, $posted, false );
        endif;
        
        # Reply To
        if( isset( $email_configs[ 'replyto' ] ) && $email_configs[ 'replyto' ] != '' ):
            self::message_shortcodes( $email_configs[ 'replyto' ], $settings, $posted, false );
        else:
            $email_configs[ 'replyto' ] = false;
        endif;

        # Sender
        if( strpos( $email_configs[ 'sender' ], '[' ) === 0 ):
            self::message_shortcodes( $email_configs[ 'sender' ], $settings, $posted, false );
        endif;
        
        # Assunto do email
        if( $email_configs[ 'subject' ] == '' ):
            $email_configs[ 'subject' ] = get_option( 'blogname' );
        endif;
        self::message_shortcodes( $email_configs[ 'subject' ], $settings, $posted, false );

        # Conteúdo default para o envio do email
        $email_configs[ 'content' ] = show::format_for_email( $settings, $posted );
        
        # Permite que outros plugins modifiquem o conteúdo do email
        $email_configs = apply_filters( 'pikiforms_email_configs', $email_configs, $settings, $posted );

        do_action( 'pikiforms_send_email', $email_configs, $settings, $posted );

        return Pikiforms::mail( $email_configs[ 'to' ],  $email_configs[ 'subject' ],  $email_configs[ 'content' ], $email_configs[ 'sender' ],  $email_configs[ 'replyto' ] );
        
    }

    public static function exclude_item( $post_id, $post_type ){
        global $wpdb;
        # Verifica se o usuário pode excluir o conteúdo
        if( !current_user_can( 'delete_' . $post_type . 's', $post_id ) ):
            Piki::error( 'Você não tem permissão para excluir este conteúdo.' );
        endif;
        # URL de redirecionamento
        $redirect = false;
        # ID do post do formulário
        $pikiform_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'ptype' AND meta_value='%s' ORDER BY post_id DESC", $post_type ));
        # Se há realmente um ID para o formulário, buscamos a url customizada
        if( $pikiform_id && !is_null( $pikiform_id ) ):
            $custom_url = get_post_meta( $pikiform_id, 'pikiform_exclude_redirect', true );
            if( $custom_url != '' ):
                $redirect = str_replace( "%home%", get_bloginfo( 'url' ), $custom_url );
            endif;
        endif;
        # Se não existe uma custom URL, enviaos para a listagem padrão de posts
        if( !$redirect ):
            $redirect = get_post_type_archive_link( $post_type );
        endif;
        # Remove o contéudo
        wp_delete_post( $post_id );
        # Mensagem de sucesso
        Piki::success( 'O conteúdo foi removido com sucesso.', $redirect );
    }

    # Remove um post
    public static function delete_post( $post_id ){
        $post = get_post( $post_id );
        if( is_null( $post ) || $post->post_type != PikiForms_ptype ):
            return;
        endif;
        parent::disable_post_type( get_post_type( $post->ID ) );
    }

}
?>
