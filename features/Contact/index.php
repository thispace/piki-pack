<?php
# Chave do formulário de compartilhametno por email
define( 'CONTACT_KEY', 'contact' );

class contact {

    var $settings;

    function __construct(){
        $this->settings = get_option( 'newslettter_settings' );
    }

    public function init(){
        # Share buttons
        add_shortcode( CONTACT_KEY, array( 'contact', 'shortcode' ) );
    }

    # Faz o cadastro do usuário
    public function form_submit( $settings, $posted ){
        
        # Checa se é o formulário correto
        if( $settings[ 'key' ] != CONTACT_KEY ):
            return;
        endif;
        
        # Email postado
        $email = trim( $posted[ 'email' ] );

        # Mailchimp
        if( $this->settings[ 'mailchimp' ][ 'active' ] === true  ):
            $this->mailchimp_subscribe( $email );
        endif;

        # Sendpress
        if( $this->settings[ 'sendpress' ][ 'active' ] === true  ):
            $this->sendpress_subscribe( $email );
        endif;
            
    }

    # Mailchimp
    public function mailchimp_subscribe( $email ){

        # API Key
        if( empty( $this->settings[ 'mailchimp' ][ 'api_key' ] ) ):
            Piki::error( 'A API Key do Mailchimp não foi definida' );
        endif;

        # List ID
        if( empty( $this->settings[ 'mailchimp' ][ 'list_id' ] ) ):
            Piki::error( 'O ID da lista do Mailchimp não foi definida' );
        endif;

        # API Mailchimp
        include( plugin_dir_path( __FILE__ ) . 'Mailchimp.php');
        $this->mc = new Mailchimp( $this->settings[ 'mailchimp' ][ 'api_key' ] );
        
        # Tenta cadastrar o usuário
        try {
            $this->mc->lists->subscribe( $this->settings[ 'mailchimp' ][ 'list_id' ], array( 'email' => $email ) );
        } 
        catch ( Mailchimp_Error $e ) {
            # Se o cadastro já existe, fazemos o update
            if( $e->getCode() == 214 ):
                $this->mc->lists->updateMember( $this->settings[ 'mailchimp' ][ 'list_id' ], array( 'email' => $email ), null );
            endif;
        }

    }

    # Sendpress
    public function sendpress_subscribe( $email ){

        # Se o usuário já existe
        $exists = SendPress_Data::get_subscriber_by_email( $email );

        # Se o usuário não foi cadastrado
        if( empty( $exists ) ):

            # Listas
            $lists = isset( $this->settings[ 'sendpress' ][ 'lists' ] ) ? implode( ',', array_keys( $this->settings[ 'sendpress' ][ 'lists' ] ) ) : false;
 
            # Outros plugins podem modificar as listas
            $lists = apply_filters( 'contact_sendpress_lists', $lists );

            # Primeiro nome, de acordo com o email
            $peaces_email = explode( '@', $email );

            # Insere o usuário na lista
            $response = SendPress_Data::subscribe_user( $lists, $email, array_shift( $peaces_email ), '' );

        endif;

    }


    /* Statics */
        
        public static function shortcode( $atts ){

            # Opções
            $defaults = array(
                'title' => false,
                'description' => false,
                'icon' => false,
            );
            $options = shortcode_atts( $defaults, $atts );
            $options = array_merge( $defaults, $options );

            $shortcode = '[pikiforms fid="'. CONTACT_KEY .'" class="contact"';
            if( !empty( $options[ 'title' ] ) ):
                $shortcode .= ' title="'. $options[ 'title' ] .'"';
            endif;
            if( !empty( $options[ 'description' ] ) ):
                $shortcode .= ' description="'. $options[ 'description' ] .'"';
            endif;
            $shortcode .= ']';

            # HTML
            $html = '<div class="contact-form-wrapper clearfix"><div class="contact-form-content clearfix">' . do_shortcode( $shortcode ) . '</div></div>';
            
            # Aditional files
            self::add_files();

            return $html;

        }

        public static function add_files(){
            # Scripts e estilos
            $path = plugins_url( '/' , __FILE__ );
            wp_enqueue_script( 'contact-scripts', ( $path . 'contact.js' ), array( 'jquery' ) );
            wp_enqueue_style( 'contact-styles', ( $path . 'contact.css' ) );
        }

}
$contact = new contact();
# Inicio
add_action( 'init', array( $contact, 'init' ) );
# Submissão dos formulários
add_action( 'pikiform_submit', array( $contact, 'form_submit' ), 10, 3 );

# Conteúdo dos emails automáticos
//add_filter( 'pikiforms_email_configs', array( 'contact', 'email_content' ), 2, 3 );

# Dados do formulário de cadastro
function pikiform_contact_settings(){
    return array(
        'allways_edit' => false,
        'preview' => false,
        'moderate' => false,
        'placeholders' => true,
        'pid' => false,
        'key' => CONTACT_KEY,
        'title' => '<h2>Cadastre-se</h2>',
        'description' => '<span class="text">Receba em 1ª mão as informações e opiniões mais relevantes</span>',
        'edit_redirect' => '',
        'success_redirect' => '',
        'exclude_redirect' => '',
        'error_message' => 'Ops! Você precisa preencher o seu e-mail corretamente.',
        'success_message' => '<i class="icon icon-check"></i><span class="text">Muito Obrigado por assinar nossa contact!</span>',
        'edit_success_message' => '',
        'classname' => '',
        'attributes' => '',
        'submit_button_label' => 'Cadastrar',
        'edit_button_label' => '',
        'email' => array(
            'send' => false,
            'subject' => 'Cadastro de contact',
            'sender' => get_option( 'admin_email' ),
            'to' => '[field_email]',
            'replyto' => '[field_email]',
        ),
        'public' => true,
        'post_type' => NULL,
        'post_type_active' => true,
    );
}

# Campos do formulário de cadastro
function pikiform_contact_fields(){
    return array(
        /*
        'nome' => array(
            'label' => 'Seu nome',
            'required' => 'on',
            'ftype' => 'text',
            'machine_name' => 'nome',
            'attr' => array( 'placeholder' => 'Seu nome' )
        ),
        */
        'email' => array(
            'required' => 'on',
            'label' => 'Digite seu email',
            'ftype' => 'email',
            'machine_name' => 'email',
            'attr' => array( 'placeholder' => 'Digite seu email' ),
            'messages' => array(
                'required' => 'Ops! Você precisa informar seu endereço de email',
                'value' => 'Ops! Você precisa preencher o seu e-mail corretamente.',
                'invalid' => 'Ops! Você precisa preencher o seu e-mail corretamente.',
            ),
       ),
    );
}

# Apenas ADMIN
if( is_admin() ):
    # Página de administração
    require_once( plugin_dir_path( __FILE__ ) . '/admin.php' );
endif;
