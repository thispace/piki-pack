<?php
class posts extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Posts' );
	}

	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		if( isset( $field[ 'post_type' ] ) && !empty( $field[ 'post_type' ] ) ):
			$field[ 'post_type_object' ] = get_post_type_object( $field[ 'post_type' ] );
			$field[ 'label_prefix' ] = '
				<span class="controls clearfix">
					<input type="button" value="Adicionar" title="' . $field[ 'post_type_object' ]->labels->add_new_item . '" class="button button-primary add" />
					<input type="button" value="Selecionar" title="Selecionar ' . $field[ 'post_type_object' ]->labels->name . '" class="button button-primary select" />
				</span>
			';
		else:
			$field[ 'post_type_object' ] = false;
		endif;
		return $field;
	}

	# constroi o campo
	public static function get_field( $field ){

		global $post;

		if( !$field[ 'post_type_object' ] ):
			return 'O campo ' . $field[ 'label' ] . ' precisa ter um tipo de post especificado.';
		endif;

		$childs = !empty( $field[ 'value' ] ) ? $field[ 'value' ] : false;

		$form_key = PikiForms::get_post_type_form_key( $field[ 'post_type' ] );

		ob_start(); ?>
		
		<div class="ftype-posts">
			
			<ul class="posts-list">

				<li class="model clearfix" style="display:none;">
					<span class="title" class="title"></span>
					<div class="actions">
						<input type="button" value="Editar" class="edit button" />
						<input type="button" value="Remover" class="remove button" />
					</div>
					<input type="hidden" class="type-hidden item-id" value="" name="<?php echo $field[ 'name_html' ]; ?>[]" />
				</li>
				
				<?php if( $childs ): foreach( $childs as $child ): ?>
				<li class="post-item clearfix">
					<span class="title" class="title"><?php echo $child->post_title; ?></span>
					<div class="actions"><input type="button" value="Editar" class="edit button" /><input type="button" value="Remover" class="remove button" /></div>
					<input type="hidden" class="type-hidden item-id" value="<?php echo $child->ID; ?>" name="<?php echo $field[ 'name_html' ]; ?>[]" />
				</li>
				<?php endforeach; endif; ?>

			</ul>

			<div class="footer-field clearfix">
				<input type="button" value="<?php echo $field[ 'post_type_object' ]->labels->add_new_item; ?>" class="button button-primary add" />
				<input type="hidden" value="<?php echo $field[ 'machine_name' ]; ?>" class="field-name" />
				<input type="hidden" value="<?php echo $form_key; ?>" class="field-form-key" />
			</div>
		
		</div>
		<?php
		$html = ob_get_contents();
		ob_clean();

		self::add_files();

		self::other_files( $field );

		return $html;

	}

	public static function get_childs( $post_type, $ID, $exclude=false ){

		$args = array(
			'post_type' => $post_type,
			'post_parent' => $ID,
			'posts_per_page' => -1,
			'post_status' => 'any'
		);
		if( !!$exclude ):
			$args[ 'post__not_in' ] = !is_array( $exclude ) ? array( $exclude ) : $exclude;
		endif;

		$posts = get_posts( $args );
		if( !!$posts && !empty( $posts ) ):
			return $posts;
		else:
			return false;
		endif;
	}

	public static function add_files(){
	    wp_enqueue_script( 'field-posts-scripts', plugins_url( '/scripts.js' , __FILE__ ), array( 'jquery' ) );
	    wp_enqueue_style( 'field-posts-styles', plugins_url( '/styles.css' , __FILE__ ) );
	}

	public static function other_files( $field ){

	    PikiForms::add_files();

		# ID do post do formulário
        $form_key = PikiForms::get_post_type_form_key( $field[ 'post_type' ] );
		
		$fields = PikiForms::get_ptype_form_fields( $form_key );
		
		foreach ( $fields as $key => $field ):

			if( method_exists( $field[ 'ftype' ], 'add_files' ) ):
				
				call_user_func( array( $field[ 'ftype' ], 'add_files'  ), $field );
			
			endif;
		
		endforeach;
	
	}

	public static function save_meta( $field, $ID, $value ){

		# Retiramos o ítem vazio
		$first = array_shift( $value );
		if( $first != '' ): 
			array_unshift( $value, $first );
		endif;

		# Se não existe nenhum valor
		if( empty( $value ) ):
			return call_user_func( 'delete_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] );
		endif;

		# Atualiza o valor no banco
		call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ], $value );

		# Ordena os posts
		self::realign_childs_order( $field, $ID, $value );

	}

	public static function change_meta_values( $field, $meta, $data_type = 'post', $data_table = false ){
		if( !isset( $field[ 'item' ]->ID ) || empty( $field[ 'item' ]->ID ) ):
			return false;
		endif;
		$childs = self::get_childs( $field[ 'post_type' ], $field[ 'item' ]->ID );
		return $childs;
	}

	public static function realign_childs_order( $field, $ID, $value ){

		global $wpdb;

		# Data de cadastro da primeira página
		$first_date = $wpdb->get_var($wpdb->prepare("SELECT post_date FROM $wpdb->posts WHERE post_type = '%s' AND post_parent = %d ORDER BY post_date ASC", array( $field[ 'post_type' ], $ID )));
		
		# Time base para as datas
		$basetime = strtotime( $first_date );

		# Reverte o array para que a ordem fique correta
		$reversed = array_reverse( $value );

		# Faz a mudança na data de cada post no banco
		foreach( $reversed as $item_id ):

			# Acrescenta um minuto ao último tempo usado
			$basetime += 60;
			# Modela a data
			$new_date = date( 'Y-m-d H:i:s', $basetime );
			# Updata no banco
			$wpdb->update( 
				$wpdb->posts, 
				array( 'post_date' => $new_date ), 
				array( 'ID' => $item_id ), 
				array( '%s' ), 
				array( '%d' ) 
			);
		
		endforeach;

	}


	public static function get_settings_fields( $values ){

		# Post types
		$post_types = get_post_types( array( '_builtin' => false ) );
		unset( $post_types[ 'pikiform' ] );

		$fields = PikiFields::get_comum_fields( array( 'required', 'hide_label' ) );
		$fields[ 'post_type' ] = array(
			'label' => 'Tipo de post',
			'machine_name' => 'post_type',
			'ftype' => 'select',
			'options' => $post_types,
		);
		PikiFields::set_fields_values( $fields, $values );

		return $fields;

	}

	public static function get_post_form(){
		
		# ID do post pai
		$post_parent = $_POST[ 'post_parent' ];

		# Chave do formulário
		$form_key = $_POST[ 'form_key' ];
				
		# ID do ítem (edição)
		$item_id = (int)$_POST[ 'post_id' ] > 0 ? $_POST[ 'post_id' ] : false;

        Pikiforms::show_form( $form_key, $item_id, $post_parent );
	
	}

	public static function remove_item(){
		
		# ID do post
		$ID = isset( $_POST[ 'post_id' ] ) && (int)$_POST[ 'post_id' ] > 0 ? $_POST[ 'post_id' ] : false;
		
		# Se o id do post não foi informado
		if( !$ID ) Piki::error( 'O ID do post não foi informado' );
		
		# Post
		$post = get_post( $ID );
		
		# Se o post não existe
		if( !is_object( $post ) ) Piki::error( 'O ID informado é inválido' );
		
		# Permissão
        if( !current_user_can( 'delete_' . $post->post_type . 's', $post->ID ) ):
            Piki::error( 'Você não tem permissão para remover este conteúdo.', 'permission' );
        endif;
        
        # Remove o post
        wp_delete_post( $post->ID, true );
        
        # Sucesso
        Piki::success();
	}

	# Mudando o post de status
	public static function on_status_change( $new_status, $old_status, $post ){
		global $wpdb;
		# Previne loops infinitos
		if( defined( 'POST_FIELD_UPDATE_STATUS_' . $ID ) ):
			return;
		else:
			define( 'POST_FIELD_UPDATE_STATUS_' . $ID, true );
		endif;
		# Se o status do post não mudou
		if( $new_status == $old_status ) return;
		# Filhos
		$childs = self::get_post_childs( $post->ID );
		# Se não tiver filhos
		if( !$childs ) return;
		# Passa pelos filhos e muda seu status
		foreach( $childs as $key => $ID ):
			$child = array(
				'ID' => $ID,
				'post_status' => $new_status,
			);
			wp_update_post( $child );
		endforeach;
	}

	# Quando o pai é deletado, seus filhos vão juntos
	public static function delete_post( $post_ID ){
		$childs = self::get_post_childs( $post_ID );
		if( !$childs ) return;
		foreach( $childs as $key => $ID ):
			wp_delete_post( $ID, true );
		endforeach;
	}

	# Busca posts filhos
	public static function get_post_childs( $post_ID ){

		global $wpdb;

		# Tipo de post
		$post_type = get_post_type( $post_ID );
		
		# Configurações do form
		$form_settings = PikiForms::get_form_settings( $post_type );
		
		# Campos de posts
	    $posts_fields = PikiFields::extract_field( $form_settings[ 'fields' ], 'posts', 'ftype', false );

		# Post childs
		$post_childs = array();

		# Se não existe nenhum campo do tipo posts
		if( !$posts_fields ) return;

		# Percorre os campos e procura os posts filhos
		foreach( $posts_fields as $key => $field ):
			$childs = $wpdb->get_col($wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = '%s' AND post_parent='%d'", array( $field[ 'post_type' ], $post_ID )));

			if( $childs && !empty( $childs ) ):
				$post_childs = array_merge( $post_childs, $childs );
			endif;

		endforeach;

		return ( empty( $post_childs ) ? false : $post_childs ); 

	}

}

# Quando um post muda de status
add_action( 'transition_post_status',  array( 'posts', 'on_status_change' ), 10, 3 );
# Quando um post é deletado
add_action( 'before_delete_post', array( 'posts', 'delete_post' ) );
