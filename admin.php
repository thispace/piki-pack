<?php
class PikiSettingsPage {

    private $features;
    private $features_list;
    private $features_dir;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ), 1, 1 );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        # Diretórios das features
        $this->features_dir = plugin_dir_path( __FILE__ ) . 'features/';
    }

    # Lista de features
    public function get_features_list(){
        return get_option( 'piki_features_options', array() );
    }

    # Seta a lista de features
    public function set_features_list( $features ){
        update_option( 'piki_features_options', $features );
    }

    # Recupera a lista de features na pasta
    public function extract_features(){
        $actual_list = $this->get_features_list();
        # Diretórios
        $dirs = array_diff( scandir( $this->features_dir ), array('..', '.') );
        # Features encontradas
        $features = array();
        foreach( $dirs as $key => $feature ):
            # Apenas diretórios
            if( is_dir( $this->features_dir . $feature ) ):
                $features[ $feature ] = isset( $actual_list[ $feature ] ) && $actual_list[ $feature ] === 'on' ? 'on' : 'off';
            endif;
        endforeach;
        # Atualiza a lista de features no banco
        $this->set_features_list( $features );
        # Verifica features que foram desativadas
        $this->check_changeds_satatus( $actual_list, $features );
        # Retorna
        return $features;
    }

    # Páginas de opções do plugin
    public function add_plugin_page() {
        add_menu_page(
            'Piki', 
            'Piki',
            'manage_options', 
            'piki-dashboard', 
            array( $this, 'create_admin_page' ), 
            plugins_url( 'images/piki-icon.png', __FILE__ )
        );
        add_submenu_page(
            'piki-dashboard', 
            'Features', 
            'Features', 
            'manage_options', 
            'piki-dashboard'
        );
        add_submenu_page(
            'piki-dashboard', 
            'SMTP', 
            'SMTP', 
            'manage_options', 
            'piki-smtp',
            array( $this, 'smtp_admin_page' )
        );
   }

    # Página de opções de features
    public function create_admin_page() {
        # Features marcadas
        $this->features = get_option( 'piki_features_options' );
        Piki::add_library( 'custom-fields' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Piki Pack</h2>           
            <h3>Features:</h3>           
            <form method="post" action="options.php" id="piki-features">
                <fieldset id="features-fields">
                    <?php
                    # This prints out all hidden setting fields
                    settings_fields( 'piki_features_group' );
                    # Busca as features na pasta
                    $features = self::extract_features(); 
                    # Lista todas as features disponíveis
                    foreach ( $features as $feature => $status ):
                        if( $feature == 'Meta' ):
                            $value = 'checked="checked" disabled="disabled"';
                        else:
                            $value = $status === 'on' ? 'checked="checked"' : '';
                        endif;
                        echo 
                        '<div class="item-check">
                            <label for="piki_features_'. $feature .'">
                                <input type="checkbox" class="piki-feature-'. $feature .'" id="piki_features_'. $feature .'" name="piki_features_options['. $feature .']" value="on"', $value, ' piki-check-background="'. plugins_url( 'features/'. $feature .'/images/feature-icon.png', __FILE__ ) .'" />
                                <span class="text">'. $feature .'</span>
                            </label>
                        </div>';
                    endforeach;
                    ?>
                </fieldset>
                <div class="obs">(*) This Feature is required, and can't be disabled.</div>
                <?php submit_button(); ?>
            </form>
        </div>
        <script type="text/javascript">jQuery(function(){jQuery('#features-fields input').pikiCheckbox()});</script>
        <?php
    }

    # SMTP options page
    public function smtp_admin_page() {
        $this->smtp_options = get_option( 'piki_smtp_options' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Piki Pack</h2>           
            <form method="post" action="options.php">
            <?php
                settings_fields( 'piki_smtp_group' );   
                do_settings_sections( 'piki-smtp' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    # Register and add settings
    public function page_init() {

        # Configuraçõe gerais
        register_setting(
            'piki_features_group', // Option group
            'piki_features_options', // Option name
            array( $this, 'sanitize_features' ) // Sanitize
        );

            # Section
            add_settings_section(
                'piki_features_section', // ID
                'Features', // Title
                NULL, // Callback
                'piki-features' // Page
            ); 
            
            # Fields
            add_settings_field(
                'features', // ID
                'Features disponíveis', // Title 
                array( $this, 'field_features' ), // Callback
                'piki-features', // Page
                'piki_features_section' // Section           
            );


        # Configuraçõe de SMTP
        register_setting(
            'piki_smtp_group', // Option group
            'piki_smtp_options', // Option name
            array( $this, 'sanitize_smtp' ) // Sanitize
        );

            # Section
            add_settings_section(
                'piki_setting_smtp', // ID
                'Configurações de SMTP', // Title
                FALSE, // Callback
                'piki-smtp' // Page
            ); 

            # Fields
            add_settings_field(
                'status', // ID
                'Enviar emails via SMTP?', // Title 
                array( $this, 'field_smtp_status' ), // Callback
                'piki-smtp', // Page
                'piki_setting_smtp' // Section           
            );      
            add_settings_field(
                'host', // ID
                'Host:', // Title 
                array( $this, 'field_smtp_host' ), // Callback
                'piki-smtp', // Page
                'piki_setting_smtp' // Section           
            );      
            add_settings_field(
                'host', // ID
                'Host:', // Title 
                array( $this, 'field_smtp_host' ), // Callback
                'piki-smtp', // Page
                'piki_setting_smtp' // Section           
            );      
            add_settings_field(
                'port', // ID
                'Porta:', // Title 
                array( $this, 'field_smtp_port' ), // Callback
                'piki-smtp', // Page
                'piki_setting_smtp' // Section           
            );      
            add_settings_field(
                'username', // ID
                'Username:', // Title 
                array( $this, 'field_smtp_username' ), // Callback
                'piki-smtp', // Page
                'piki_setting_smtp' // Section           
            );      
            add_settings_field(
                'password', // ID
                'Password:', // Title 
                array( $this, 'field_smtp_password' ), // Callback
                'piki-smtp', // Page
                'piki_setting_smtp' // Section           
            );

    }

    # Status
    public function field_smtp_status() {
        echo '<input type="checkbox" id="piki_smtp_status" name="piki_smtp_options[status]" ', ( isset( $this->smtp_options[ 'status' ] ) && $this->smtp_options[ 'status' ] == 'on' ? 'checked="checked"' : '' ), ' /> Sim';
    }
    # Host
    public function field_smtp_host() {
        echo '<input type="text" id="piki_smtp_host" name="piki_smtp_options[host]" value="', ( isset( $this->smtp_options[ 'host' ] ) ? $this->smtp_options[ 'host' ] : '' ), '" />';
    }
    # Port
    public function field_smtp_port() {
        echo '<input type="text" id="piki_smtp_port" name="piki_smtp_options[port]" value="', ( isset( $this->smtp_options[ 'port' ] ) ? $this->smtp_options[ 'port' ] : '' ), '" />';
    }
    # Username
    public function field_smtp_username() {
        echo '<input type="text" id="piki_smtp_username" name="piki_smtp_options[username]" value="', ( isset( $this->smtp_options[ 'username' ] ) ? $this->smtp_options[ 'username' ] : '' ), '" />';
    }
    # Username
    public function field_smtp_password() {
        echo '<input type="text" id="piki_smtp_password" name="piki_smtp_options[password]" value="', ( isset( $this->smtp_options[ 'password' ] ) ? $this->smtp_options[ 'password' ] : '' ), '" />';
    }

    # Valida os valores
    public function sanitize_features( $input ) {

        # Apenas quando postado
        if( !isset( $_POST[ 'piki_features_options' ] ) || empty( $_POST[ 'piki_features_options' ] ) ):
            return $input;
        endif;

        # Lista de features atualizadas no banco
        $features_list = $this->get_features_list();
        
        # Lista o estado de cada feature
        $new_input = array();
        foreach( $features_list as $feature => $status ):
            # Insere a feature na lista
            $new_input[ $feature ] = isset( $input[ $feature ] ) && $input[ $feature ] === 'on' ? 'on' : 'off';
        endforeach;

        # Obrigatórios
        $new_input[ 'Meta' ] = 'on';
        
        # Chama os métods das features que mudaram de status
        $this->check_changeds_satatus( $features_list, $new_input );

        # Escreve o arquivo de features
        $this->write_features_file( $new_input );
        
        # Retorna
        return $new_input;
    }

    # Atualiza os status das features
    public function check_changeds_satatus( $before, $after ){
        # Passa pelo array de features
        foreach( $after as $feature => $status ):
            # A feature foi ativada
            if( $status == 'on' && $before[ $feature ] == 'off' ):
                $this->activate_feature( $feature );
            # A feature foi desativada
            elseif( !isset( $before[ $feature ] ) || ( $status == 'off' && $before[ $feature ] == 'on' ) ):
                $this->deactivate_feature( $feature );
            endif;
        endforeach;
    }

    # Ativa uma feature
    private function activate_feature( $feature ){
        $activate_file = $this->features_dir . $feature . '/activate.php';
        if( is_file( $activate_file ) ):
            require_once( $activate_file );
        endif;
    }

    # Desativa uma feature
    private function deactivate_feature( $feature ){
        $deactivate_file = $this->features_dir . $feature . '/deactivate.php';
        if( is_file( $deactivate_file ) ):
            require_once( $deactivate_file );
        endif;
    }

    # Escreve o arquivo e plugins
    private function write_features_file( $features ){

        # Quebra de linha
        $br = "\n";
        # PHP
        $towrite  = '<?php' . $br;

        # Plugins obrigatórios
        $base = array( 'meta' => 'on' );
        $plugins = array_merge( $base, $features );

        # Escreve os includes
        foreach( $features as $feature => $status ):
            if( $status == 'on' ):
                $towrite  .= '# ' . $feature . $br;
                $towrite  .= 'require_once( plugin_dir_path( __FILE__ ) . \'features/'. $feature .'/index.php\' );' . $br;
            endif;
        endforeach;

        # Escreve o arquivo
        if( !file_put_contents( PIKI_FEATURES_FILE, $towrite, FILE_TEXT ) ):
            Piki::error( 'O arquivo ' . PIKI_FEATURES_FILE . ' não pôde ser criado.' );
        endif;

        # Carrega o novo arquivo
        require( PIKI_FEATURES_FILE );
        
        # Flush rules
        global $wp_rewrite;
        $wp_rewrite->flush_rules();

    }

    # Valida os valores
    public function sanitize_smtp( $input ) {

        $new_input = array();

        # Status
        if( isset( $input[ 'status' ] ) && $input[ 'status' ] == 'on' ):
            $new_input[ 'status' ] = 'on';
        endif;
        # Host
        if( isset( $input[ 'host' ] ) ):
            $new_input[ 'host' ] = $input[ 'host' ];
        endif;
        # Host
        if( isset( $input[ 'port' ] ) ):
            $new_input[ 'port' ] = $input[ 'port' ];
        endif;
        # Username
        if( isset( $input[ 'username' ] ) ):
            $new_input[ 'username' ] = $input[ 'username' ];
        endif;
        # Password
        if( isset( $input[ 'password' ] ) && $input[ 'password' ] != '' ):
            $new_input[ 'password' ] = $input[ 'password' ];
        endif;

        return $new_input;
    }

}
if( is_admin() ):
    $piki_settings_page = new PikiSettingsPage();
    add_action( 'wp_ajax_piki_load_options', array( 'PikiSettingsPage', 'load_options' ) );
    add_action( 'wp_ajax_piki_change_status', array( 'PikiSettingsPage', 'change_status' ) );
endif;