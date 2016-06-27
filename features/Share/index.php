<?php
# Chave do formulário de compartilhametno por email
define( 'PIKISHARE_FORM_KEY', 'pikishare' );
# Networks suportadas
$PIKISHARE_NETWORKS = array(
    'facebook'  => 'Facebook',
    'twitter'   => 'Twitter',
    'google'    => 'Google+',
    'youtube'   => 'Youtube',
    'pinterest' => 'Pinterest',
    'linkedin'  => 'LinkedIn',
    'flickr'    => 'Flickr'
);

class PikiShare {

    public static function init(){
        # Share buttons
        add_shortcode( 'pikishare', array( 'PikiShare', 'shortcode' ) );
        # Social buttons
        add_shortcode( 'networks_links', array( 'PikiShare', 'networks_links' ) );
        # Adicionar os arquivos necessários
        self::add_files();
    }

    public static function networks_links( $atts ){

        # Redes suportadas
        global $PIKISHARE_NETWORKS;

        # Redes informadas no admin
        $social_settings = get_option( 'pikishare_options' );

        # Opções
        $defaults = array(
            'services' => array_keys( $PIKISHARE_NETWORKS ),
            'size' => '',
            'target' => $social_settings[ 'target' ]
        );
        $options = shortcode_atts( $defaults, $atts );
        $options = array_merge( $defaults, $options );

        # Services
        if( empty( $options[ 'services' ] ) ):
            $options[ 'services' ] = array_keys( $PIKISHARE_NETWORKS );
        elseif( !is_array( $options[ 'services' ] ) ):
            $options[ 'services' ] = explode( ',', $options[ 'services' ] );
        endif;

        # Constrói os links
        $html = '';
        foreach( $options[ 'services' ] as $service ):
            
            if( isset( $social_settings[ $service ] ) && $social_settings[ $service ] != '' ):
               
                $html .= '<li class="'. $service .'"><a href="'. $social_settings[ $service ] .'" title="'. $PIKISHARE_NETWORKS[ $service ] .'" rel="'. $options[ 'target' ] .'"><i class="icon-'. $service .'">'. $PIKISHARE_NETWORKS[ $service ] .'</i></a></li>';
            
            endif;
        
        endforeach;

        if( $html == '' ) return '';

        return '<div class="piki-social-links"><ul class="clearfix">'. $html .'</ul></div>';

    }

    public static function shortcode( $atts ){

        # Opções
        $defaults = array(
            'services' => false,
            'size' => false,
            'url' => false,
            'title' => false,
            'description' => false,
        );
        $options = shortcode_atts( $defaults, $atts );
        $options = array_merge( $defaults, $options );

        /*
        echo '<pre>';
        var_dump( $options );
        exit;
        */

        # Patterns
        $patterns = array( '/{URL}/', '/{TITLE}/', '/{TEXT}/' );
        
        # URL's dos serviços
        $services = array(
            'facebook' => array(
                'url' => 'https://www.facebook.com/sharer/sharer.php?u={URL}',
                'title' => 'Facebook',
            ),
            'twitter' => array(
                'url' => 'http://twitter.com/share?url={URL}&text={TEXT}', 
                'title' => 'Twitter',
            ),
            'gplus' => array( 
                'url' => 'https://plus.google.com/share?url={URL}&content={TEXT}', 
                'title' => 'Google+',
            ),
            'pinterest' => array(
                'url' => 'http://pinterest.com/pin/create/button/?url={URL}&media={URL_MEDIA}&description={TEXT}',
                'title' => 'Pinterest',
            )
        );

        # Serviços
        if( $atts[ 'services' ] != '' ):
            $selecteds = explode( ',', $atts[ 'services' ] );
        else:
            $selecteds = array_keys( $services );
        endif;

        # URL
        $url = isset( $atts[ 'url' ] ) && $atts[ 'url' ] != '' ? $atts[ 'url' ] : get_permalink() . $_SERVER[ 'REQUEST_URI' ];
        # URL
        $title = isset( $atts[ 'title' ] ) && $atts[ 'title' ] != '' ? $atts[ 'title' ] : get_the_title();
        # Descrição
        $text = isset( $atts[ 'text' ] ) && $atts[ 'text' ] != '' ? $atts[ 'text' ] : get_bloginfo( 'text' );
        # Descrição
        $subject = isset( $atts[ 'subject' ] ) && $atts[ 'subject' ] != '' ? $atts[ 'subject' ] : get_bloginfo( 'name' );
        # Email content
        $email_content = isset( $atts[ 'email_content' ] ) && $atts[ 'email_content' ] != '' ? $atts[ 'email_content' ] : $text;

        $html = '<ul class="pikishare clearfix" share-url="'. $url .'" share-title="'. $title .'" share-text="'. $text .'">';
        $html .= '  <h3>Compartilhe:</h3>';

        foreach( $selecteds as $key => $service ):
        $url = preg_replace( $patterns, array( $url, $title, $text ), $services[ $service ][ 'url' ] );
        $html .= '  <li class="'. $service .'"><a href="'. $url .'" title="'. $services[ $service ][ 'title' ] .'" class="'. $service .'">' . $services[ $service ][ 'title' ] . '</a></li>';
        endforeach;
        $html .= '  <li class="email" subject="'. $subject .'" content="'. $email_content .'">' . do_shortcode( '[pikiforms_button form_key="'. PIKISHARE_FORM_KEY .'" class="email" label="Email"]' ) . '</li>';
        $html .= '</ul>';

        self::add_files();

        return $html;
    }

    public static function add_files(){
        # Scripts e estilos
        $path = plugins_url( '/' , __FILE__ );
        wp_enqueue_script( 'pikishare-scripts', ( $path . 'piki-share.js' ), array( 'jquery' ) );
        wp_enqueue_style( 'pikishare-styles', ( $path . 'piki-share.css' ) );
        # Addthis
        //wp_enqueue_script( 'pikishare-addthis-scripts', '//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5550c3692113d356', array( 'jquery' ), false, true );
    }

    public static function email_content( $email_configs, $settings, $posted ){

        # Modifica apenas o formulário de compartilhamento
        if( $settings[ 'key' ] != 'pikishare' ):
            return $email_configs;
        endif;
        
        $images = WP_PLUGIN_URL . '/demillus/images/';
        $transparent = '<img src="'. $images .'/transparent.png" border="0" width="1" height="1" style="display:block;border:0;width:1px;height:1px;" />';
        $br = '<br /><img src="'. $images .'/transparent.png" border="0" width="1" height="26" style="display:block;border:0;width:1px;height:26px;" />';

        $html = '
        <table width="500" cellpadding="0" cellspacing="0" border="0" style="border:0; border-collapse:collapse;">
            <tr>
                <td width="500" height="107" colspan="3" bgcolor="#eb1753"><img src="'. $images .'email_header.jpg" border="0" width="500" height="107" style="display:block;border:0;" /></td>
            </tr>
            <tr><td width="500" colspan="3" bgcolor="#e5e5e5" height="18">'. $transparent .'</td></tr>
            <tr>
                <td width="500" colspan="3" bgcolor="#e5e5e5" align="center">
                    <strong><font face="arial" color="#000000" size="1" style="font-size: 12px; color: #000000 ;">Demillus - Sugestão</font></strong>
                </td>
            </tr>
            <tr><td width="500" colspan="3" bgcolor="#e5e5e5" height="18">'. $transparent .'</td></tr>
            <tr><td width="500" colspan="3" bgcolor="#ffffff" height="46">'. $transparent .'</td></tr>
            <tr>
                <td width="50" bgcolor="#ffffff">'. $transparent .'</td>
                <td width="400">
                    <font face="arial" color="#000000" size="2" style="font-size:14px;color:#000000;">

                        '. $posted[ 'nome' ][ 0 ] .' indicou para você a Revista DeMillus Shopping. Clicando no link abaixo, você vai acessar mais de 400 modelos: lingerie para os diversos biotipos, meias Dantelle, linha masculina, infantil e cosméticos.
                    
                        Assunto: <br /><br />
                        Mensagem: '. $posted[ 'mensagem' ][ 0 ] .'<br /><br />
                        URL: <a href="'. $posted[ 'url' ][ 0 ] .'" target="_blank">'. $posted[ 'url' ][ 0 ] .'</a>
                    
                    </font>
                </td>
                <td width="50" bgcolor="#ffffff">'. $transparent .'</td>
            </tr>
            <tr><td width="500" colspan="3" bgcolor="#ffffff" height="46">'. $transparent .'</td></tr>
            <tr><td width="500" bgcolor="#0f0f0f" colspan="3" height="12">'. $transparent .'</td></tr>
            <tr>
                <td width="50" bgcolor="#0f0f0f">'. $transparent .'</td>
                <td bgcolor="#0f0f0f"><font face="arial" color="#ffffff" size="1" style="font-size:12px;color:#ffffff;text-decoration:none;">
                    SAC DeMillus: (21) 3545-5000<br />
                    <a href="mailto:sac@demillus.com.br" target="_blank" style="text-decoration:none;"><font face="arial" color="#ffffff" size="1" style="font-size:12px;color: #ffffff;text-decoration:none;">sac@demillus.com.br</font></a><br />
                    <a href="http://www.demillus.com.br" target="_blank" style="text-decoration:none;"><font face="arial" color="#ffffff" size="1" style="font-size:12px;color: #ffffff;text-decoration:none;">www.demillus.com.br</font></a>
                </font></td>
                <td width="50" bgcolor="#0f0f0f">'. $transparent .'</td>
            </tr>
            <tr><td width="500" bgcolor="#0f0f0f" colspan="3" height="12">'. $transparent .'</td></tr>
        </table>
        ';

        $email_configs[ 'content' ] = $html;
        return $email_configs;

    }

}
add_action( 'init', array( 'PikiShare', 'init' ) );
# Conteúdo dos emails automáticos
add_filter( 'pikiforms_email_configs', array( 'PikiShare', 'email_content' ), 2, 3 );

# Dados do formulário
function pikiform_pikishare_settings(){
    return array(
        'allways_edit' => false,
        'preview' => false,
        'moderate' => false,
        'placeholders' => false,
        'pid' => 4,
        'key' => 'pikishare',
        'title' => 'Enviar por email',
        'description' => '',
        'edit_redirect' => '',
        'success_redirect' => '',
        'exclude_redirect' => '',
        'success_message' => 'Seu email foi enviado com sucesso.<br /><input type="button" class="form-save-button button-primary button-large reload-form" value="Compartilhar novamente" />',
        'edit_success_message' => '',
        'classname' => '',
        'attributes' => '',
        'submit_button_label' => 'Enviar email',
        'edit_button_label' => '',
        'email' => array(
            'send' => true,
            'subject' => 'Revista DeMillus Shopping',
            'sender' => get_option( 'admin_email' ),
            'to' => '[field_email_destino]',
            'replyto' => get_option( 'admin_email' ),
        ),
        'public' => true,
        'post_type' => NULL,
        'post_type_active' => true,
    );
}

function pikiform_pikishare_fields(){
    return array(
        'nome' => array(
            'label' => 'De',
            'required' => 'on',
            'ftype' => 'text',
            'machine_name' => 'nome',
            'attr' => array( 'placeholder' => 'Seu nome' )
        ),
        'email_destino' => array(
            'required' => 'on',
            'label' => 'Para',
            'ftype' => 'email',
            'machine_name' => 'email_destino',
             'attr' => array( 'placeholder' => 'Email de destino' )
       ),
        'assunto' => array(
            'required' => 'on',
            'label' => 'Assunto',
            'ftype' => 'text',
            'machine_name' => 'assunto',
        ),
        'mensagem' => array(
            'required' => 'on',
            'label' => 'Mensagem',
            'ftype' => 'textarea',
            'machine_name' => 'mensagem',
        ),
        'url' => array(
            'required' => 'on',
            'label' => 'URL',
            'ftype' => 'text',
            'machine_name' => 'url',
        ),
    );
}

# Apenas ADMIN
if( is_admin() ):
    # Página de administração
    require_once( plugin_dir_path( __FILE__ ) . '/admin.php' );
endif;
