<?php
class post_connect extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Post Connect' );
	}

	# Configurações específicas do campo
	public static function set_confs( $field ){
		
		$field[ 'sqlrpc' ] = '%s';
		
		# Lista de Posts
		$posts_list = self::get_posts_list( $field[ 'post_type' ] );
		
		# Se não há posts
		if( empty( $posts_list ) ):
			return $field;
		endif;

		# Preenche o array de opções
		$field[ 'options' ] = array();
		foreach( $posts_list as $key => $post ):
			$field[ 'options' ][ $post->ID ] = $post->post_title;
		endforeach;
		
		return $field;
	
	}

	# Constroi o campo
	public static function get_field( $field ){

		# Se não existem posts disponíveis
		if( empty( $field[ 'options' ] ) ):
			return 'Nenhum post disponível para conexão';
		endif;

		# Select
		$field[ 'ftype' ] = 'select';
		
		$s = new select();
		return $s->get_field( $field );

	}

	public static function get_posts_list( $post_type ){
		global $wpdb;
		$posts = $wpdb->get_results($wpdb->prepare(
			"SELECT ID, post_title FROM $wpdb->posts WHERE post_type = '%s' AND post_status IN ( 'publish', 'future', 'private', 'pending', 'draft' ) ORDER BY post_date ASC",
			$post_type
		));
		if( empty( $posts ) ):
			return false;
		endif;
		return $posts;
	}

	public static function change_meta_values( $field, $meta, $data_type = 'post', $data_table = false ){

		if( on( $field, 'use_as_parent' ) ):
			if( isset( $meta[ 'post_item' ]->post_parent ) && !empty( $meta[ 'post_item' ]->post_parent ) ):
				return $meta[ 'post_item' ]->post_parent;
			else:
				return '';
			endif;
		else:
			return isset( $meta[ $field[ 'machine_name' ] ] ) && !empty( $meta[ $field[ 'machine_name' ] ] ) ? $meta[ $field[ 'machine_name' ] ] : '';
		endif;
	
	}

	# Validação do campo
	public static function validate( $field, $action ){
		$error = '';
		if( parent::is_required( $field ) && empty( $field[ 'value' ] ) ):
			$error = parent::get_default_message( $field, 'doselect' );
		endif;
		if( $error != '' ):
			return $error;
		endif;
		return true;
	}

	# Atualiza os valores nos campos
	public static function save_meta( $field, $ID, $value ){

		global $wpdb;
		
		if( on( $field, 'use_as_parent' ) ):
			$wpdb->query($wpdb->prepare(
				"UPDATE $wpdb->posts SET post_parent = %d WHERE ID = %d",
				array( (int)$value, $ID )
			));
		endif;

		call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ], (int)$value );

	}

	# Renderizando os valores
	public static function renderize_values( $field, $options=array() ){

		if( empty( $field[ 'value' ] ) ):
			return '';
		endif;
		$connected = get_post( $field[ 'value' ] );
		if( isset( $options[ 'field' ] ) ):
			if( isset( $connected->$options[ 'field' ] ) ):
				return $connected->$options[ 'field' ];
			elseif( ( $meta = get_post_meta( $connected->ID, $options[ 'field' ], true ) ) !== '' ):
				return $meta;
			endif;
		endif;
		return $connected->post_title;
	}

	public static function change_for_filter( $field ){

		global $wpdb;

		$posts_ids = array();
		$rpcts = array();

		# IDs de posts
		foreach( $field[ 'options' ] as $key => $option ):
			if( (int)$key > 0 ):
				$posts_ids[] = $key;
				$rpcts[] = '%d';
			endif;
		endforeach;

		# Nomes dos posts
		$posts_names = $wpdb->get_results($wpdb->prepare(
			"SELECT ID, post_name FROM $wpdb->posts WHERE ID in ( ". implode( ',', $rpcts ) ." )",
			$posts_ids
		));

		# Coloca os ids dos posts como chaves
		$keyed_names = array();
		foreach ( $posts_names as $name_key => $name):
			$keyed_names[ $name->ID ] = $name->post_name;
		endforeach;

		# Novo array de opções
		$new_options = array();		
		foreach( $field[ 'options' ] as $key => $option ):
			if( isset( $keyed_names[ $key ] ) ):
				$new_options[ $keyed_names[ $key ] ] = $field[ 'options' ][ $key ];
			else:
				$new_options[ $key ] = $option;
			endif;
		endforeach;

		# Atribui as novas opções ao campo
		$field[ 'options' ] = $new_options;
		unset( $new_options, $keyed_names, $posts_names );

		return $field;

	}

	public static function change_filter_query( $query, $field ){
		
		global $wpdb;
		
		# Total values
		$total_pars = count( $query[ 'value' ] );
		
		# Query replacements
		$rpcts = array();
		for ( $p=0; $p < $total_pars; $p++ ):
			$rpcts[] = '%s';
		endfor;
		
		# Query posts by post_name
		$posts = $wpdb->get_results($wpdb->prepare(
			"SELECT ID, post_name FROM $wpdb->posts WHERE post_name IN ( ". implode( ',', $rpcts ) ." )",
			$query[ 'value' ]
		) );
		
		# Values for compare
		$fliped = array_flip( $query[ 'value' ] );
		
		# Replace post names for post ID
		$keyeds = array();
		foreach ( $posts as $key => $post ):
			if( isset( $fliped[ $post->post_name ] ) ):
				$query[ 'value' ][ $fliped[ $post->post_name ] ] = $post->ID;
			endif;
		endforeach;
		
		# REQUIRED
		return $query;
	
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
		$fields[ 'use_as_parent' ] = array(
			'label' => 'Usar como post pai',
			'machine_name' => 'use_as_parent',
			'ftype' => 'boolean',
		);
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}
}