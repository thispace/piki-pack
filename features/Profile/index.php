<?php
# Papel de usuário padrão
define( 'PERFIL_ROLE', 'member' );

class PKPerfil {

    public static function init(){
        $user = wp_get_current_user();
        # Bloqueia o admin para os membros
        if ( is_admin() && in_array( PKPerfil_role, $user->roles ) ) {
            wp_redirect( get_bloginfo( 'home' ) . '/minha-programacao/' );
            die();
        }
        # Esconde a barra de administração do front end
        show_admin_bar( false );
    }

    public static function create_rewrite_rules() {
        global $wp_rewrite; 
        $new_rules[ 'login' ] = 'index.php?perfil=true&action=login';
        $new_rules[ 'resetpass' ] = 'index.php?perfil=true&action=resetpass';
        $new_rules[ 'perfil/([^/]+)/([^/]+)' ] = 'index.php?perfil=true&usuario=$matches[1]&action=$matches[2]';
        $new_rules[ 'perfil/([^/]+)' ] = 'index.php?perfil=true&usuario=$matches[1]';
        $new_rules[ 'perfil' ] = 'index.php?perfil=true';
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }

    # Adiciona variaveis de busca
    public static function add_query_vars( $qvars ) {
        $qvars[] = 'cadastro';
        $qvars[] = 'perfil';
        $qvars[] = 'usuario';
        $qvars[] = 'action';
        $qvars[] = 'logout';
        $qvars[] = 'login';
        $qvars[] = 'resetpass';
        return $qvars;
    }
  
    // Redirecionar as páginas solicitadas para os devidos arquivos 
    public static function template_redirect_intercept(){
        global $wp_query;
        
        # Página de cadastro
        //if( $wp_query->get( 'cadastro' ) == 'true' ):
        //    
        //    add_filter( 'body_class', array( 'PKPerfil', 'add_body_class' ), 10, 2 );
        //    Piki::search_template( 'perfil', 'cadastro', dirname( __FILE__ ) );
        
        # Página de perfil do usuário
        if( $wp_query->get( 'perfil' ) == 'true' ):
            
            # Login
            if( $wp_query->get( 'action' ) == 'login' ):
                add_filter( 'body_class', array( 'PKPerfil', 'add_body_class' ), 10, 2 );
                Piki::search_template( 'perfil', 'login', dirname( __FILE__ ) );
            
            # Login
            elseif( $wp_query->get( 'action' ) == 'resetpass' ):
                self::reset_password();
            
            # Outras ações do perfil
            else:
                add_filter( 'body_class', array( 'PKPerfil', 'add_body_class' ), 10, 2 );
                self::page_perfil( $wp_query->get( 'usuario' ), $wp_query->get( 'action' ) );
            
            endif;
        
        # Logout
        elseif( $wp_query->get( 'logout' ) == 'true' ):

            # URL de redirecionamento após o logout
            $redirect = self::get_redirect( 'redirect' );
            
            # Faz o logout
            wp_logout();
            
            # Redireciona
            wp_redirect( $redirect ); 
            exit;
       
       endif;
    
    }

    # Configurações dos forms
    public static function form_write_settings( $settings, $post_id ){
        # Status do plugin no formulário
        $status = get_post_meta( $post_id, 'perfil_status', true );
        # Se não está ativo
        if( $status !== 'on' && $status !== true ):
            $settings[ 'perfil' ] = false;
            return $settings;
        endif;
        $settings[ 'perfil' ] = array(
            'roles' => get_post_meta( $post_id, 'perfil_roles', true ),
            'redirect_signin' => get_post_meta( $post_id, 'perfil_redirect_signin', true ),
            'redirect_login' => get_post_meta( $post_id, 'perfil_redirect_login', true ),
        );
        return $settings;
    }

    # Adicionando classes na tag body
    public static function add_body_class( $classes, $class ) {
        global $wp_query;
        $classes[] = 'perfil';
        if( $wp_query->get( 'action' ) != '' ):
            $classes[] = $wp_query->get( 'action' );
        endif;
        if( $wp_query->get( 'cadastro' ) == 'true' ):
            $classes[] = 'cadastro';
        endif;
        return $classes;
    }

    # Campo de redirecionamento enviado
    public static function get_redirect( $field_name ){
        if( isset( $_REQUEST[ $field_name ] ) && ( $custom_redirect = $_REQUEST[ $field_name ] ) != '' ):
            if( strpos( $custom_redirect, '/' ) === 0 ):
                return get_site_url( NULL, $custom_redirect );
            else:
                Piki::http( $custom_redirect );
            endif;
        endif;
        return home_url();
    }

    public static function perfil( $user=false ){
        if( !$user ){
            global $usuario;
            $user = $usuario;
        }
        return $usuario;
    }

    # URL do perfil do usuário
    public static function perfil_url( $source ){
        # Se for um ID
        if( is_numeric( $source ) ):
            $user = get_user_by( 'id', $user );
        # Se for um objeto de usuário
        elseif( isset( $source->data ) && $source->exists() ):
            $user = $source;
        # Se for um POST
        elseif( isset( $source->post_type ) ):
            $user = get_user_by( 'id', $source->post_author );
        endif;
        # Se o usuário não existe
        if( !$user->exists ): return false; endif;
        # Retorn a URl do perfil do usuário
        return bloginfo( 'url' ) . '/perfil/' . $user->data->user_nicename;
    }

    # Página de perfil
    public static function page_perfil( $usuario='', $action='' ){

        global $usuario;

        $current_user = wp_get_current_user();

        # Perfil do usuário logado
        if( $usuario == '' || ( $current_user->exists() && $usuario == $current_user->data->user_nicename ) ):
            $usuario = $current_user;
            $usuario->current = true;
        else:
            $usuario = get_user_by( 'slug', urldecode( $usuario ) );
            $usuario->current = false;
        endif;

        if( !$usuario || !is_object( $usuario ) || !$usuario->exists() ):
            return Piki::set404();
        endif;

        $form_id = get_option( 'pkperfil_fid' );

        $extras_fields = get_post_meta( $form_id, PikiField_metaid, true );
        if( $extras_fields && !empty( $extras_fields ) ):
            $extras_fields = unserialize(base64_decode( $extras_fields ));
        endif;

        PikiForms::inlcude_class( 'show' );
        $s = new show();
        $s->set_data_type( 'user' );
        $usuario->perfil = $s->get_post_meta_view( $usuario->ID, $extras_fields );
        $usuario->perfil->show_info = get_user_meta( $usuario->ID, 'publicar_informacoes', true ) != 'on' ? false : true;

        Piki::search_template( 'perfil', $action, dirname( __FILE__ ) );

    }

    # Login customizado
    public static function custom_login(){
        $action = isset( $_POST[ 'perfil-action' ] ) && !empty( $_POST[ 'perfil-action' ] ) ? $_POST[ 'perfil-action' ] : false;
        if( $action && $action == 'login' && !is_user_logged_in() ):
            $login = self::login();
            if( !is_wp_error( $login ) ):
                $to_redirect = self::get_redirect( 'redirect' );
                if( Piki::ajax() ):
                    echo json_encode(array(
                        'status' => 'success',
                        'redirect' => $to_redirect,
                    ));
                else:
                    wp_redirect( $to_redirect );
                endif;
                die();
            endif;
        else:
            self::clear_login();
        endif;
    }

    # Valida o login
    public static function login(){
        # Nome de usuário
        $username = self::get_posted_username();
        # Senha
        $password = ( isset( $_POST[ 'pwd' ] ) && !empty( $_POST[ 'pwd' ] ) ) ? $_POST[ 'pwd' ] : false;
        if( !$username ):
            return self::login_set_error( 'empty_user' );
        elseif( !$password ):
            return self::login_set_error( 'empty_pass' );
        endif;
        $creds = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true,
        );
        $user = wp_signon( $creds, false );
        if ( is_wp_error( $user ) ):
            return self::login_set_error( $user->get_error_code() );
        endif;
        self::clear_login();
        return $user;
    }

    # Reset de password
    public static function reset_password(){

        //set POST variables
        $url = get_site_url() . '/wp-login.php?action=lostpassword';

        // Campos
        $fields_string = 'user_login=' . $_POST[ 'user_login' ];

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec( $ch );

        //close connection
        curl_close( $ch );

        if( $result == '' ):            
            Piki::success( 'Consulte a mensagem com o link de confirmação no seu email.' );
        else:
            $doc = new DOMDocument();
            $doc->loadHTML( $result );
            $error = $doc->getElementById( 'login_error' );
            Piki::error( trim( $error->nodeValue ) );
        endif;
   
   }

    # Username postado
    public static function get_posted_username(){
        return ( isset( $_POST[ 'user_login' ] ) && !empty( $_POST[ 'user_login' ] ) ) ? $_POST[ 'user_login' ] : false;
    }

    # Grava a sessão com o erro
    public static function login_set_error( $type ){
        @session_start();
        $_SESSION[ 'perfil-login' ] = $type;
        # Se for ajax, retorna o Json
        if( Piki::ajax() ):
            exit(json_encode(array(
                'status' => 'error',
                'type' => $type,
                'error_message' => self::get_error_message( $type ),
            )));
        endif;
        return new WP_Error( $type, __( self::get_error_message( $type ) ) );
    }

    # Recupera o erro no login
    public static function login_get_error(){
        @session_start();
        return !isset( $_SESSION[ 'perfil-login' ] ) ? false : $_SESSION[ 'perfil-login' ];
    }

    # Limpa os erros de login
    public static function clear_login(){
        @session_start();
        unset( $_SESSION[ 'perfil-login' ] );
    }

    # Mensagens de erro no login
    public static function get_error_message( $type ){
        if( !$type ):
            return '';
        endif;
        $errors = array(
            'empty_user' => 'Informe seu email ou apelido',
            'empty_pass' => 'Informe sua senha',
            'invalid_username' => 'Email ou apelido inválido',
            'incorrect_password' => 'Senha inválida',
        );
        return $errors[ $type ];
    }

    # Seta o tipo de data do formulário
    # Se logado, informa o ID do usuário ao form
    public static function form_settings( $settings, $form_key ){
        if( isset( $settings[ 'perfil' ] ) && !empty( $settings[ 'perfil' ] ) ):
            $settings[ 'data_type' ] = 'user';
            if( is_user_logged_in() ):
                $settings[ 'data' ] = get_current_user_id();
            endif;
        endif;
        return $settings;
    }

    # Extrai os campos extras
    public static function extract_meta_fields( $fields ){
        unset( $fields[ 'user_email' ] );
        if( isset( $fields[ 'user_pass' ] ) ):
            unset( $fields[ 'user_pass' ], $fields[ 'user_pass_confirm' ] );
        endif;
        if( isset( $fields[ 'user_login' ] ) ):
            unset( $fields[ 'user_login' ] );
        endif;
        return $fields;
    }

    # Validação do formulário
    public static function form_valida( $validation, $settings, $posted ){

        # Apenas formulários com perfil ativado
        if( !isset( $settings[ 'perfil' ] ) || empty( $settings[ 'perfil' ] ) ):
            return $validation;
        endif;

        # Se ainda existirem erros do form anterior
        if( $validation[ 'status' ] != 'success' || !empty( $validation[ 'errors' ] ) ):
            return $validation;
        endif;

        # Dados do usuário atual
        $user = (object)array(
            'data' => (object)array(
                'user_email' => false,
                'user_login' => false,
            ),
        );

        # Se o usuário está logado
        if ( is_user_logged_in() ):
            $user = wp_get_current_user();
        endif;

        # Email postado
        $email = $posted[ 'email' ];
        $new_validation = array();

        # Se o email já foi cadastrado
        if ( email_exists( $email ) && $email != $user->data->user_email ):
            $new_validation[] = array(
                'field' => $settings[ 'fields' ][ 'email' ],
                'error' => 'Email já cadastrado',
            );
        endif;

        # Se o login já existe
        if ( !is_user_logged_in() ):
            $username = $posted[ 'username' ];
            if( username_exists( $username ) ):
                $new_validation[] = array(
                    'field' => $settings[ 'fields' ][ 'username' ],
                    'error' => 'O nome de usuário já existe',
                );            
            endif;
        endif;

        # Se não há nenhum erro, retorna true
        if( empty( $new_validation ) ):
            return true;
        endif;

        $validation[ 'errors' ] = $new_validation;

        return $validation;
    }

    # Salva os dados postados
    public static function form_submit( $settings, $posted ){

        # Apenas formulários com perfil ativado
        if( !isset( $settings[ 'perfil' ] ) || empty( $settings[ 'perfil' ] ) ):
            return true;
        endif;

        # Dados postados
        $userinfo = self::get_posted_user_info( $posted );

        # Usuário existente
        if( is_user_logged_in() ):
                        
            # ID do usuário
            $user_id = get_current_user_id();
                        
            # Valores atualizados
            $to_update = array();
            
            # Email
            if( !empty( $userinfo[ 'user_email' ] ) ):
                $to_update[ 'user_email' ] = $userinfo[ 'user_email' ];
            endif;
            
            # Nome
            if( !empty( $userinfo[ 'nome_completo' ] ) ):
                $to_update[ 'display_name' ] = $userinfo[ 'nome_completo' ];
                $to_update[ 'first_name' ] = $userinfo[ 'first_name' ];
                $to_update[ 'last_name' ] = $userinfo[ 'last_name' ];
            endif;
           
            # Senha a ser modificada
            $user_pass = self::check_update_pass( $userinfo );
            if( $user_pass ):
                $to_update[ 'user_pass' ] = $user_pass;
            endif;
            
            # Se há algo a ser mudado
            if( !empty( $to_update ) ):
                
                # ID do usuário
                $to_update[ 'ID' ] = $user_id;
                
                # Faz a atualização do usuário
                wp_update_user( $to_update );
            
            endif;


        # Novo usuário
        else:
            
            # Senha enviada pelo usuário
            $user_pass = $userinfo[ 'password' ];
            
            # Nome de usuário informado pelo usuário
            if( isset( $settings[ 'fields' ][ 'username' ] ) ):
                $user_login = sanitize_user( $userinfo[ 'username' ], true );
            
            # Nome de usuári pelo nome
            else:
                $user_login = sanitize_user( $userinfo[ 'nome_completo' ], true );
                $user_login = apply_filters( 'pre_user_login', $user_login );
                $user_login = trim( $user_login );
            endif;
            
            # Nome amigável
            $user_nicename = sanitize_title( $userinfo[ 'first_name' ] );
            $user_nicename = apply_filters( 'pre_user_nicename', $userinfo[ 'first_name' ] );
            
            # Email do usuário
            $user_email = apply_filters( 'pre_user_email', $userinfo[ 'email' ] );

            # Papel de usuário atribuído
            $role = is_array( $settings[ 'perfil' ][ 'roles' ] ) ? array_shift( $settings[ 'perfil' ][ 'roles' ] ) : $settings[ 'perfil' ][ 'roles' ];

            # Cria o usuário com os campos obrigatórios
            $user_id = wp_insert_user(array(
                'display_name' => $nome_completo,
                'user_login' => $user_login,
                'user_email' => $user_email,
                'user_pass' => $user_pass,
                'display_name' => $nome_completo,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'user_nicename' => $user_nicename,
                'user_registered' => date( "Y-m-d H:i:s" ),
                'role' => $role,
            ));

            # Notifição do cadastro
            wp_new_user_notification( $user_id, $user_pass );

            # Logando o usuário
            $creds = array(
                'user_login' => $user_login,
                'user_password' => $user_pass,
                'remember' => true,
            );
            wp_signon( $creds, false );

        
        endif;
        
        # Salvando meta dados do usuário
        $others_fields = self::extract_meta_fields( $settings[ 'fields' ] );
        $saved = PKMeta::save_post_meta(
            $user_id,
            $others_fields,
            $posted,
            'user'
        );

    }

    # Dados postados pelo usuário
    public static function get_posted_user_info( $posted ){

        $return = array();
 
        # Nome completo
        $return[ 'nome_completo' ] = $posted[ 'complete_name' ];
        # Nome explodido
        $peaces_nome = explode( ' ', $nome_completo );
        # Primeiro nome
        $return[ 'first_name' ] = array_shift( $peaces_nome );
        # Último nome
        $return[ 'last_name' ] = !empty( $peaces_nome ) ? implode( ' ', $peaces_nome ) : '';

        # Nome de usuário
        if( isset( $posted[ 'username' ] ) ):
            $return[ 'user_login' ] = sanitize_user( $posted[ 'username' ], true );
        else:
            $return[ 'user_login' ] = sanitize_user( $return[ 'nome_completo' ], true );
            $return[ 'user_login' ] = apply_filters( 'pre_user_login', $user_login );
            $return[ 'user_login' ] = trim( $user_login );
        endif;
        
        # Nicename
        $return[ 'user_nicename' ] = sanitize_title( $first_name );
        $return[ 'user_nicename' ] = apply_filters( 'pre_user_nicename', $first_name );
        
        # Senha enviada pelo usuário
        $return[ 'user_pass' ] = $posted[ 'password' ];
        
        # Email
        $return[ 'user_email' ] = apply_filters( 'pre_user_email', $posted[ 'email' ] );

        return $return;

    }

    # Verifica se o usuário postou uma nova senha
    public static function check_update_pass( $data ){
        $senha = isset( $data[ 'password' ] ) && $data[ 'password' ] != '' ? $data[ 'password' ] : false;
        $confirm = isset( $data[ 'password_confirm' ] ) && $data[ 'password_confirm' ] != '' ? $data[ 'password_confirm' ] : false;
        if( !$senha || !$confirm || $confirm !=  $senha ):
            return false;
        else:
            return $senha;
        endif;
    }

    # Avatar do usuário
    public static function get_avatar( $user ){
        $user_id = is_object( $user ) ? $user->ID : $user;
        $meta = get_user_meta( $user_id, 'avatar', true );
        if( is_array( $meta ) ):
            $meta = array_shift( $meta );
        endif;
        $a = new avatar();
        return $a->get_item_url( $meta );
    }

    # Scripts e Estilos
    public static function add_files(){
        # Libraries
        Piki::add_library( 'jquery-ui' );
        # Scripts
        $filesdir = plugins_url( '/' , __FILE__ );
        wp_enqueue_script( 'jquery-form' );
        wp_enqueue_script( 'PikiPerfil-scripts', $filesdir . 'scripts.js', array( 'jquery' ) );
        # Styles
        wp_enqueue_style( 'PikiPerfil-styles', $filesdir . 'styles.css' );
    }

    # Total de posts do usuário
    public static function count_user_posts( $userid, $post_type = 'post' ) {
        global $wpdb;
        $where = get_posts_by_author_sql( $post_type, true, $userid );
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );
        return apply_filters( 'get_usernumposts', $count, $userid );
    }

    # Total de comentários do usuário
    public static function count_user_comments( $userid ) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->comments WHERE user_id = %d", array( $userid )));
    }

    # Permite acentuação UTF-8 nos logins
    public static function sanitize_user( $raw_username, $username, $strict ){
        $raw_username = $username;
        $username = strip_tags( $username );
        $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
        $username = preg_replace('/&.+?;/', '', $username); // Kill entities
        if ( $strict ):
            $username = mb_ereg_replace('|[^a-z0-9 _.-@]|i', '', $username);
        endif;
        return apply_filters('sanitize_user_mbstring', $username, $raw_username, $strict);
    }

    # Altera a URL de registro
    public static function register_url( $url ){
        return home_url() . '/cadastro/';
    }

    # CAMPOS DO FORM DE FORMS
    public static function form_settings_fields( $metaboxes ){
        
        $metaboxes[] = array(
            'id'         => 'pikiform_perfil',
            'title'      => 'Perfil de usuário',
            'post_types' => array( PikiForms_ptype ),
            'context'    => 'advanced',
            'priority'   => 'low',
            'show_names' => true,
            'fields'     => array(
                'perfil_status' => array(
                    'machine_name' => 'perfil_status',
                    'ftype' => 'boolean',
                    'label' => 'Ativar perfil?',
                    'label_option' => 'Sim',
                ),
                'perfil_roles' => array(
                    'machine_name' => 'perfil_roles',
                    'ftype' => 'roles',
                    'multiple' => false,
                    'label' => 'Papel atribuido'
                ),
                'perfil_redirect_signin' => array(
                    'machine_name' => 'perfil_redirect_signin',
                    'ftype' => 'text',
                    'label' => 'URL de destino no cadastro'
                ),
                'perfil_redirect_login' => array(
                    'machine_name' => 'perfil_redirect_login',
                    'ftype' => 'text',
                    'label' => 'URL de destino no login'
                ),
                'perfil_requireds_text' => array(
                    'machine_name' => 'perfil_requireds_text',
                    'ftype' => 'boxtext',
                    'label' => 'Instruções:',
                    'content' => '<strong>Para usar o formulário como perfil, você precisa ter os seguintes campos no formulário:</strong><br />
                    nickname (text): Nome completo do usuário<br />
                    user_login (username): Login do usuário,<br />
                    user_email (email): Email do usuário,<br />
                    user_pass : (password): Senha de acesso do usuário.<br/>
                    <strong>São opcionais os seguintes campos:</strong><br />
                    avatar (avatar): Avatar do usuário,<br />
                    user_url (url): URL para o site do usuáro,<br />
                    terms_of_use (boolean): Checkbox de aceitação dos termos de uso
                    ',
                ),
            ),
        );
        
        return $metaboxes;

    }

    
}

# Início
add_action( 'init', array( 'PKPerfil', 'init') );
add_action( 'after_setup_theme', array( 'PKPerfil', 'custom_login') );

# Campos de configurações do formuláro
add_filter( 'pkmeta_register_fields', array( 'PKPerfil', 'form_settings_fields' ) );

# Opções de perfil nas configurações dos forms
add_filter( 'pikiform_settings_write', array( 'PKPerfil', 'form_write_settings' ), 10, 2 );

# Modifica a URL para registro do usuário
add_filter( 'register_url', array( 'PKPerfil', 'register_url' ) );

# Permite acentos nos nomes de usuário
add_filter( 'sanitize_user', array( 'PKPerfil', 'sanitize_user' ), 10, 3 );

# Altera dados dos formulários
add_filter( 'pikiform_valida', array( 'PKPerfil', 'form_valida' ), 10, 3 );
//add_filter( 'pikiform_post_meta', array( 'PKPerfil', 'complete_post_meta' ), 10, 2 );
add_filter( 'pikiform_settings', array( 'PKPerfil', 'form_settings' ), 10, 2 );

# Ação para cadastro do formulário
add_action( 'pikiform_submit', array( 'PKPerfil', 'form_submit' ), 10, 3 );

# URL's
add_filter( 'query_vars', array( 'PKPerfil', 'add_query_vars' ) );
add_action( 'generate_rewrite_rules', array( 'PKPerfil', 'create_rewrite_rules' ) );
add_action( 'template_redirect', array( 'PKPerfil', 'template_redirect_intercept' ) );

# Remoção do usuário
//add_action( 'delete_user', array( 'PKPerfil', 'delete_user' ) );


// Redefine user notification function
if ( !function_exists('wp_new_user_notification') ) {
    function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {
        
        $user = new WP_User( $user_id );

        $user_login = stripslashes( $user->user_login );
        $user_email = stripslashes( $user->user_email );

        $message  = sprintf( __( 'New user registration on your site %s:' ), get_option( 'blogname' ) ) . "\r\n\r\n";
        $message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
        $message .= sprintf( __( 'E-mail: %s' ), $user_email) . "\r\n";

        wp_mail( get_option('admin_email'), sprintf(__('[%s] New User Registration'), get_option('blogname')), $message );

        if ( empty( $plaintext_pass ) ):
            return;
        endif;

        $message  = __( 'Bem vindo ao portal Lank.!' ) . "\r\n\r\n";
        $message .= __( 'Esses são seus dados para login no site:' ) . "\r\n\r\n";
        $message .= sprintf(__('Apelido: %s'), $user_login) . "\r\n";
        $message .= sprintf(__('Senha: %s'), $plaintext_pass) . "\r\n\r\n";
        $message .=__( 'Esperamos sua visita :-)' ) . "\r\n\r\n";
        $message .= __('Equipe Lank.') . "\r\n";
        $message .= get_site_url() . "\r\n\r\n";
        wp_mail( $user_email, sprintf(__( 'Informações para login' ), get_option( 'blogname' ) ), $message );
    
    }
}

function get_perfil_url( $user = false ){
    if( !$user ):
        $nicename = get_the_author_meta( 'user_nicename' );
    elseif( is_numeric( $user ) ):
        $nicename = get_the_author_meta( 'user_nicename', $user );
    else:
        $nicename = $user->data->user_nicename;
    endif;
    return get_bloginfo( 'url' ) . '/perfil/' . $nicename . '/';
}
function the_perfil_url( $user=false ){
    echo get_perfil_url( $user );
}

# Email de registro

    # Muda  o nome do sender
    function perfil_change_from_email_name() {
        return get_option( 'blogname' );
    }
    add_filter( 'wp_mail_from_name', 'perfil_change_from_email_name' );
    # Muda  o email do sender
    function perfil_hange_from_email() {
       return get_option( 'admin_email' );
    }
    add_filter( 'wp_mail_from', 'perfil_hange_from_email' );

# Permite o login do usuário através do email também

    function perfil_email_login_authenticate( $user, $username, $password ) {
        if ( is_a( $user, 'WP_User' ) ):
            return $user;
        endif;
        if ( !empty( $username ) ):
            $username = str_replace( '&', '&amp;', stripslashes( $username ) );
            $user = get_user_by( 'email', $username );
            if ( isset( $user, $user->user_login, $user->user_status ) && 0 == (int) $user->user_status )
                $username = $user->user_login;
        endif;
        return wp_authenticate_username_password( null, $username, $password );
    }
    remove_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
    add_filter( 'authenticate', 'perfil_email_login_authenticate', 20, 3 );

# Widget do header

    class Perfil_Widget extends WP_Widget {

        public function __construct() {
            parent::__construct(
                'perfil_widget', // Base ID
                __('Perfil - Login', 'text_domain'), // Name
                array( 'description' => __( 'Box de login do usuário', 'text_domain' ), ) // Args
            );
        }
        public function widget( $args, $instance=array(), $args=array() ) {

            
            PKPerfil::add_files();

            echo '<div id="perfil-userbox" class="clearfix">';

            if( !is_user_logged_in() ): ?>

                <?php 
                $title = isset( $instance[ 'title' ] ) && !empty( $instance[ 'title' ] ) ? $instance[ 'title' ] : "Login";
                $redirect = isset( $instance[ 'redirect' ] ) && !empty( $instance[ 'redirect' ] ) ? $instance[ 'redirect' ] : '';
                $error = PKPerfil::login_get_error();
                $username = PKPerfil::get_posted_username();
                $action = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : 'login';
                ?>
                <div class="box-title clearfix">
                    <span class="login-title"><b><?php echo $title; ?></b></span>
                    <span class="reset-title" style="display:none;"><b>ESQUECI MINHA SENHA</b> - Uma nova senha será enviada.</span>
                </div>
                <form action="./" method="POST" id="perfil-userform">
                    <fieldset class="login">
                        <input type="text" class="type-text username" name="user_login" id="perfil-username" placeholder="Email ou apelido" value="<?php echo( !$username ? '' : $username ); ?>" /> 
                        <input type="password" class="type-text pass" name="pwd" id="perfil-passowrd" placeholder="Senha" /> 
                        <input type="submit" value="Entrar" retrieve-label="Solicitar nova senha" class="type-submit button" name="perfil-submit" id="perfil-submit" />
                        <input type="hidden" name="perfil-action" id="perfil-action" value="<?php echo $action; ?>" />
                        <input type="hidden" name="redirect" id="redirect" value="<?php echo $redirect; ?>" />
                    </fieldset>
                </form>
                <div class="status"><span><?php echo PKPerfil::get_error_message( $error ); ?></span></div>
                <div class="extras-links clearfix">
                    <a href="<?php echo wp_registration_url(); ?>" class="btn-cadastro" title="Clique para fazer seu cadastro">Faça seu cadastro</a> 
                    <span>|</span>
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="btn-esqueci" class="Clique para recuperar sua senha">Esqueci a senha</a>
                    <a href="<?php echo wp_login_url(); ?>" class="btn-login" title="Clique para faze o login" style="display:none;">Login</a>
                </div>

            <?php else: ?>

                <?php $user = wp_get_current_user(); ?>
                <a href="<?php echo bloginfo( 'url' ); ?>/logout" class="button logout-button">Sair</a>
                <div class="clearfix avatar-nome">
                    <img src="<?php echo PKPerfil::get_avatar( $user ); ?>" class="avatar" alt="<?php echo $user->data->display_name; ?>" />
                    <p class="nome-user">Olá, <?php echo $user->data->user_login; ?></p>
                </div>
                <a href="<?php echo bloginfo( 'url' ); ?>/perfil/" class="button profile-button">Meu perfil</a>
                
                <?php do_action( 'perfil_userbox', $user ); ?>
            
            <?php endif;
        
            echo '</div>';
        }
    }
    add_action( 'widgets_init', create_function( '', 'return register_widget("Perfil_Widget");' ) );


# Return path do wordpress
function perfil_mail_fix( $phpmailer ) {
    $phpmailer->Sender = get_option( 'admin_email' );
}
add_action( 'phpmailer_init', 'perfil_mail_fix' );    

