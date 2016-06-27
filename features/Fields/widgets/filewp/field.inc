<?php
class filewp extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Arquivo - Wordpress' );
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field, $settings ){
		
		# Guarda as configurações originais do campo
		$backupfield = $field;

		if( empty( $field[ 'value' ] ) ):
			$value = array( 'ids' => '' );
		else:
			$value = !isset( $field[ 'value' ][ 'ids' ] ) ? array_shift( $field[ 'value' ] ) : $field[ 'value' ][ 'ids' ];
		endif;
		
		# Objetos dos arquivos
		$files = self::get_objects( $value[ 'ids' ] );
				
		# Atributos adicionais
		$attrs_add = array();
		
		# Classe do campo hidden
		$fEx = new hidden();
		
		# Se é múltiplo
		if( on( $field, 'multiple' ) ):
			# Aributos
			$attrs_add[ '--multiple' ] = 'on';
			# Label do campo
			$select_label = 'Adicionar arquivos';
			# Field remove
			$action_field = '';

		# Se não é múltiplo
		else:
			# Atributos
			$attrs_add[ '--multiple' ] = 'off';
			# Label do campo
			$select_label = 'Selecionar arquivo';
			# Field remove
			$action_field = '<input class="filewp-edit-button button ' . ( !$files ? 'hide' : 'show' ) . '" type="button" value="Substituir arquivo" /><input class="filewp-remove-button button ' . ( !$files ? 'hide' : 'show' ) . '" type="button" value="Remover arquivo" />';
		endif;
		
		# Adiciona os atributos
		parent::add_attributes( $field, $attrs_add );

		# HTMl
		$html  = '<div'; parent::set_attributes( $html, $field ); $html .= '>';
		$html .= '	<div class="filewp-media-thumbs clearfix ' . ( $files == '' ? 'hide' : 'show' ) . '">';
		
		# Se já existem valores
		if ( $files ):
			
			foreach ( $files as $ikey => $file ):
				
				# Imagem com tamanho real
				$fileurl = wp_get_attachment_url( $file->ID, 'full' );
					
				$html .= '<div class="thumbnail" rel="'. $file->ID .'">';
				
				$html .= '	<img src="' . wp_mime_type_icon( $file->post_mime_type ) . '" alt="' . $file->post_title . '" />';
				$html .= '	<a href="' . $fileurl . '" rel="' . $file->ID . '" target="_blank" class="action preview" title="' . $file->post_title . '">Visualizar arquivo</a>';
				$html .= '	<a rel="' . $file->ID . '" class="action remove" title="Remover">Remover arquivo</a>';
				$html .= '</div>';

				
			endforeach;
		endif;

		$html .= '	</div>';
		$html .= '	<div id="' . $field[ 'id' ] . '_status" class="filewp-media-status"></div>';
		
		$ids = $backupfield;
		$ids[ 'label' ] = 'Ids';
		$ids[ 'value' ] = isset( $value[ 'ids' ] ) ? $value[ 'ids' ] : '';
		parent::set_as_child( $ids, 'ids' );
		parent::add_attributes( $ids, array( 'class' => 'filewp-ids' ) );
		$html .= $fEx->get_field( $ids );

		$html .= '	<input class="filewp-select-button button '. ( ( !!$files && !on( $field, 'multiple' ) ) ? 'hide' : 'show' ) .'" type="button" value="'. $select_label .'" />';
		$html .= '	<input class="filewp-upload-label" type="hidden" value="' . $field[ 'machine_name' ] . '" />';
		$html .= $action_field;
		
		$html .= '</div>';

		self::add_files( $field );

		return $html;
	
	}

	public static function get_object( $file ){
		if( empty( $file ) ):
			return false;
		endif;
		$return = get_posts(array(
			'posts_per_page' => -1,
			'post_type' => 'attachment',
			'include' => $file
		));
		if( empty( $return ) )
			return false;
		return $return;
	}

	public static function get_objects( $ids ){

		if( is_string( $ids ) ):
			$ids = trim( $ids, ',' );
			$ids = explode( ',', $ids );
		endif;
		if( empty( $ids ) ):
			return false;
		endif;

		$objects = get_posts(array(
			'posts_per_page' => -1,
			'post_type' => 'attachment',
			'include' => $ids
		));

		# Se não existem filens
		if( empty( $objects ) ):
			return false;
		endif;

		return $objects;

	}

	# Validação do campo
	public static function validate( $field, $action ){
		
		$error = '';

		if( parent::is_required( $field ) && ( !isset( $field[ 'value' ][ 'ids' ] ) || $field[ 'value' ][ 'ids' ] == '' ) ):
			$error = parent::get_default_message( $field, 'doselect', array( '{UMVALOR}' => 'um arquivo' ) );
		endif;
		if( $error != '' ):
			return $error;
		endif;
		return true;
	
	}

	public static function renderize_values( $field, $options=array() ){

		if( empty( $field[ 'value' ] ) || empty( $field[ 'value' ][ 'ids' ] ) ):
			return false;
		endif;
		
		# Objetos
		$files = self::get_objects($field[ 'value' ][ 'ids' ] );

		# Retorno
		$return = array();

		# Classes
		$classname = isset( $options[ 'classname' ] ) ? $options[ 'classname' ] : 'button';

		foreach( $files as $key => $file ):

			if( on( $options, 'just_url' ) ):

				$return[] = wp_get_attachment_url( $file->ID );

			else:

				switch ( $file->post_mime_type ):
					
					# Áudio
					case 'audio/mpeg':
						$return[] = do_shortcode( '[audio src="'. wp_get_attachment_url( $file->ID ) .'"] ' );
					break;
					# Default
					default:
						$return[] = '<a href="'. wp_get_attachment_url( $file->ID ) .'" class="'. $classname .'" rel="_blank"><span>'. $file->post_title . '</span></a>';
					break;
				
				endswitch;		

			endif;	
		
		endforeach;

		return implode( '', $return );
	
	}

	public static function renderize_audio( $post_id ){

		# Arquivos
	    wp_enqueue_script( 'filewp-audio-script', plugins_url( '/mediaelement/player.min.js' , __FILE__ ), array( 'jquery' ) );
	    wp_enqueue_style( 'filewp-audio-style', plugins_url( '/mediaelement/player.min.css' , __FILE__ ) );

	    # Código HTML
		$url = wp_get_attachment_url( $post_id );
		$id_player = 'player-' . $post_id;
		$return =  '<audio id="'. $id_player .'" src="'. $url .'" type="audio/mp3" controls="controls"></audio>';
		$return .= '<script>jQuery(function(){ jQuery("#'. $id_player .'").mediaelementplayer(); });</script>';

	    return $return;
	}

	public static function change_meta_values( $field, $meta, $data_type = 'post', $data_table = false ){
		$values = isset( $meta[ $field[ 'machine_name' ] ] ) ? $meta[ $field[ 'machine_name' ] ] : '';
		if( empty( $values ) ):
			return $values;
		endif;
		if( is_array( $values ) ):
			foreach( $values as $key => &$value ):
				$value = maybe_unserialize( $value );
			endforeach;
		else:
			$values = maybe_unserialize( $values );
		endif;
		return $values;
	}

	public static function add_files(){
		# Files
	    wp_enqueue_script( 'filewp.field.js', plugins_url( '/filewp.js' , __FILE__ ), array( 'jquery' ) );
	    wp_enqueue_style( 'filewp.field.css', plugins_url( '/filewp.css' , __FILE__ ) );
	    # WP Media
		wp_enqueue_media();
	}

	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields();
		# Galeria de filens?
		$fields[ 'multiple' ] = array(
			'label' => 'Seleção multipla?',
			'ftype' => 'boolean',
			'machine_name' => 'multiple'
		);
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}

}
