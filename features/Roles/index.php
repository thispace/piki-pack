<?php
class PKRoles{

    private $addfiles;

    # Inicia o plugin
   function __construct() {
        # Multilínguas
        load_plugin_textdomain( 'pkroles', false, plugin_dir_path( __FILE__ ) . 'languages/' );
        # Arquivos onde widgets serão ineridos
        $this->addfiles = array( 'user-edit.php', 'user-new.php' );
    }

    # Scripts para as páginas de edição
    public function admin_enqueue_styles( $handle ) {
        if ( in_array( $handle, $this->addfiles ) ):
            wp_add_inline_style( 'pkroles-styles', 'label[for="role"],select#role{display:none;}' );
        endif;
    }

    # Todas as roles
    private function all_roles(){
        static $allroles;
        if( empty( $this->allroles ) ):
            $this->allroles = get_editable_roles();
        endif;
        return $this->allroles;
    }

    public function multiple_roles_widget( $user ) {

        global $pagenow;

        # Roles do usuário
        $user_roles = array();

        # Edição ou criação de usuáro
        $exists = is_object( $user ) && isset( $user->ID );

        # Permissões
        if( ( $exists && !current_user_can( 'edit_user', $user->ID ) ) || ( !$exists && !current_user_can( 'create_users' ) ) ):
            return;
        endif;

        # Todas as roles
        $allroles = $this->all_roles();
        
        # Edição
        if( $exists ):
            $user_roles = array_intersect( array_values( $user->roles ), array_keys( $allroles ) );
        # Criação
        else:
            $user_roles = in_array( $_POST[ 'pkroles_user_roles' ], array_keys( $allroles ) );
        endif;
        
        ?>
        <div id="pkroles-widget-wrapper">
            <h3><?php _e( 'User Roles', 'pkroles' ); ?></h3>
            <table class="form-table">
                <tr id="pkroles-widget">
                    <th>
                        <label for="user-roles"><?php _e( 'Roles', 'pkroles' ); ?></label>
                    </th>
                    <td>
                        <?php 
                        foreach ( $allroles as $id => $role ): 
                            $checked = ( $pagenow == 'user-edit.php' && in_array( $id, $user_roles ) ) ? ' checked="checked"' : '';?>
                            <p><label for="pkrole-user-role-<?php echo esc_attr( $id ); ?>"><input type="checkbox" name="pkrole-user-roles[]" id="pkrole-user-role-<?php echo esc_attr( $role_id ); ?>" value="<?php echo esc_attr( $id ); ?>" <?php echo $checked; ?> /><?php echo $role[ 'name' ]; ?></label></p>
                        <?php 
                        endforeach; 
                        wp_nonce_field( 'pkroles_update_roles', 'pkroles_nonce' ); ?>
                    </td>
                </tr>
            </table>
        </div>
        <script type="text/javascript">
            (function($){
                $(function(){
                    var $pkrole_widget = $( 'tr#pkroles-widget' ).first();
                    $( 'select#role' ).parents( 'tr.user-role-wrap' ).replaceWith( $pkrole_widget );
                    $( '#pkroles-widget-wrapper' ).remove();
                });
            })(jQuery);
        </script>
    <?php 
    }

    public function save_user_roles( $user_id ) {

        # Nonce
        if( !wp_verify_nonce( @$_POST[ 'pkroles_nonce' ], 'pkroles_update_roles' ) ):
            return;
        endif;

        # Permissões
        if( !current_user_can( 'edit_user', $user_id ) ):
            return;
        endif;

        # Usuário postado
        $user = get_user_by( 'id', $user_id );
        
        # Roles postadas
        $posteds_roles = is_array( @$_POST[ 'pkrole-user-roles' ] ) ? $_POST[ 'pkrole-user-roles' ] : array();

        # Todas as roles
        $allroles = $this->all_roles();

        # Verifica todas as roles existentes
        foreach( $allroles as $role_key => $role ):

            # Se devemos atribuir a role ao usuário
            if( in_array( $role_key, $posteds_roles ) && ( !isset( $user->caps[ $role_key ] ) || $user->caps[ $role_key ] !== true ) ):
                
                $user->add_role( $role_key );
           
            # Se devemos remover a role do usuário 
            elseif( !in_array( $role_key, $posteds_roles ) && ( isset( $user->caps[ $role_key ] ) && $user->caps[ $role_key ] === true ) ):
                
                $user->remove_role( $role_key );
           
            endif;
       
       endforeach;

    }

    # Adiciona a coluna de multiplas roles na listagem administrativa
    public function roles_colum( $cols ){
        
        # Guarda a coluna de posts
        $posts_colum = isset( $cols[ 'posts' ] ) ? $cols[ 'posts' ] : false;
        
        # Remove a coluna 'role' e a de 'posts'
        unset( $cols[ 'role' ], $cols[ 'posts' ] );
        
        # Adicionamos nossa coluna
        $cols[ 'pkroles' ] = __( 'Roles', 'pkroles' );
        
        # Se tinhamos uma coluna de posts, reinserimos
        if ( $posts_colum ):
            $cols[ 'posts' ] = $posts_colum;
        endif;
        
        return $cols;
    
    }

    # Adiciona a coluna de multiplas roles na listagem administrativa
    public function display_roles_colum( $value, $colum, $user_id ){

        # Apenas a nossa coluna
        if( $colum !== 'pkroles' ):
            return $value;
        endif;

        # Usuário postado
        $user = get_user_by( 'id', $user_id );

        # Todas as roles
        $allroles = $this->all_roles();

        # Labels
        $to_show = array();
        foreach( $user->roles as $user_role ):
            $to_show[] = $allroles[ $user_role ][ 'name' ];
        endforeach;

        # Retorna os labels das roles, implodidos.
        return implode( ', ', $to_show );
    
    }

}

# Inicia
$PKRoles = new PKRoles();

# Salva as roles do usuário
add_action( 'edit_user_profile_update', array( $PKRoles, 'save_user_roles' ) );
add_action( 'user_register', array( $PKRoles, 'save_user_roles' ) );

# Apenas Admin
if( is_admin() ):

    # Scripts para as páginas de edição
    add_action( 'admin_enqueue_scripts',  array( $PKRoles, 'admin_enqueue_styles' ), 10 );

    # Widget para seleção das roles
    add_action( 'edit_user_profile', array( $PKRoles, 'multiple_roles_widget' ), 0 );
    add_action( 'user_new_form', array( $PKRoles, 'multiple_roles_widget' ), 0 );
    
    # Listagem de usuário no admin
    add_filter( 'manage_users_columns', array( $PKRoles, 'roles_colum' ) );
    add_filter( 'manage_users_custom_column', array( $PKRoles, 'display_roles_colum' ), 10, 3 );

endif;


