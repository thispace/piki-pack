<?php
class image extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Imagem' );
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){

		$item_index = isset( $field[ 'item_index' ] ) && (int)$field[ 'item_index' ] > 0 ? $field[ 'item_index' ] : 0;

		# Pega o ítem de imagem
		if( is_object( $field[ 'value' ] ) ):
			$value = $field[ 'value' ];
		elseif( is_array( $field[ 'value' ] ) ):
			$value = $field[ 'value' ][ $item_index ];
		endif;

		# Normaliza o ítem
		if( ( $unserialized = unserialize( $value ) ) !== false ):
			$value = $unserialized;
		elseif( is_array( $value )  ):
			$value = get_post( $value[ 'file_id' ] );
		endif;

		# Imagem fullsize
		if( is_object( $value ) ):
			$fullsize = wp_get_attachment_image_src( $value->ID, 'full');
		endif;

		$field[ 'value' ] = '';

		$html = '<div class="ftype-image item-image">';
		
		$html .= '<div class="field"><input type="file" name="'. $field[ 'name_html' ] .'" id="'. $field[ 'id' ] .'" size="1"';
		parent::set_attributes( $html, $field );

		$html .= " /></div>";		

		# Classe de Campos hidden
		$h = new hidden();

		# File ID
		$file_id = $field;
		parent::set_as_child( $file_id, 'file_id' );
		parent::add_attributes( $file_id, array( 'class' => 'file-id' ) );
		if( is_object( $value ) ):
			$file_id[ 'value' ] = $value->ID;
		endif;
		$html .= $h->get_field( $file_id );

		# URI
		$uri = $field;
		parent::set_as_child( $uri, 'fullsize' );
		parent::add_attributes( $uri, array( 'class' => 'fullsize' ) );
		if( is_object( $value ) ):
			$uri[ 'value' ] = $fullsize[ 0 ];
		endif;
		$html .= $h->get_field( $uri );

		# URI do Thumbnail
		$thumb = $field;
		parent::set_as_child( $thumb, 'thumbnail' );
		parent::add_attributes( $thumb, array( 'class' => 'thumbnail' ) );
		if( is_object( $value ) ):
			$thumb[ 'value' ] = Piki::image_resize( 120, 90, $fullsize[ 0 ] );
		endif;
		$html .= $h->get_field( $thumb );

		# UNIQUE ID
		$unique_id = $field;
		$unique_id[ 'value' ] = md5(uniqid(rand(), true));
		parent::set_as_child( $unique_id, 'unique_id' );
		parent::add_attributes( $unique_id, array( 'class' => 'unique-id' ) );
		$html .= $h->get_field( $unique_id );
		
		$html .= '</div>';

		self::add_files();

		return $html;
	}

	public static function get_meta( $field, $post_id ){
		if( !$post_id ):
			return array( false );
		endif;
		return Piki::post_images( $post_id );
	}

	# Validação do campo
	public static function validate( $field, $action ){

		/*
		$value = !is_array( $values ) ? $values : $values[ $key ];
		$error = '';

		if( parent::is_required( $field ) && empty( $value ) ):
			$error = 'O campo "' . $field[ 'label' ] . '" é obrigatório.';
		elseif( parent::minlength( $field ) && strlen( $value ) < parent::minlength( $field ) ):
			$error = 'O campo "' . $field[ 'label' ] . '" deve ter no mínimo ' . parent::minlength( $field )  . ' caracteres.';
		elseif( parent::maxlength( $field ) && strlen( $value ) > parent::maxlength( $field ) ):
			$error = 'O campo "' . $field[ 'label' ] . '" deve ter no máximo ' . parent::maxlength( $field )  . ' caracteres.';
		endif;

		if( $error != '' ):
			return $error;
		endif;
		*/

		return true;

	}

	public static function is_emtpy( $field, $value ){

	}

	public static function renderize_values( $field, $values ){
		
		if( !$values || empty( $values ) ):
			return false;
		endif;

		if( is_object( $values ) ):
			$values = array( $values );
		endif;
		
		$return = array();
		foreach ( $values as $key => $value ) {
			if( ( $unserialized = unserialize( $value ) ) !== false ):
				$value = $unserialized;
			endif;
			$return[] = $value;
		}
		return $return;
	}

	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields( array( 'multiple' ) );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}

	public static function save_meta( $field, $ID, $value ){

		# Se não é um arquivo
		if( !is_array( $value ) ):
			return;
		endif;

		# Se só existe um arquivo
		if( isset( $value[ 'file_id' ] ) ):
			$value = array( $value );
		endif;

		# Insert images
		foreach( $value as $key => $item_value ):
			
			if( isset( $item_value[ 'file_id' ] ) && (int)$item_value[ 'file_id' ] > 0 ):
				
				$attatchment = array(
					'ID' => $item_value[ 'file_id' ],
					'post_parent' => $ID
				);
				wp_update_post( $attatchment );
				
				call_user_func( 'add_'. $field[ 'data_type' ] .'_meta', $ID, 'full', $item_value[ 'file_id' ], true );
				call_user_func( 'add_'. $field[ 'data_type' ] .'_meta', $ID, $field[ 'machine_name' ], serialize( $item_value ) );
			endif;
		
		endforeach;

	}

	public static function image_upload( $data, $file ){

		echo('<pre>');
		var_dump( $data );
		var_dump( $file );
		var_dump( $_POST );
		exit;

		# Diretórios para upload
		$upload_dir = wp_upload_dir();

		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');

        if ( !file_is_displayable_image( $file[ 'tmp_name' ] ) ){
			header('HTTP/1.1 200 OK');
			exit( "<script type=\"text/javascript\">window.parent.window.imageField_error('O arquivo enviado não é uma imagem');</script>" );
        }
		else{
            $overrides = array('test_form' => false);
            $uploaded = wp_handle_upload( $file, $overrides );
		}

		if ( array_key_exists( 'error', $uploaded ) ):
			Piki::error( $uploaded[ 'error' ] );
		endif;

		list( $image_width, $image_height ) = getimagesize ( $uploaded[ 'file' ] );

		/*
		if( $image_width < 393 || $image_height < 296 ):
			
			unlink( $uploaded['file'] );

			$return = json_encode(array(
				'status' => 'error',
				'error_message' => 'A imagem enviada é muito pequena. Ela dever ter pelo menos 393 x 296 pixels.',
				'unique_id' => $data[ 'unique_id' ]
			));

			header('HTTP/1.1 200 OK');
			exit( "<script type=\"text/javascript\">window.parent.window.imageField_receive_image( '". $return ."' );</script>" );
		endif;
		*/

		$wp_filetype = wp_check_filetype( basename( $uploaded[ 'file' ] ), null );
		$attachment = array(
			'post_mime_type' => $wp_filetype[ 'type' ],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename( $uploaded[ 'file' ] ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $uploaded[ 'file' ] );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded[ 'file' ] );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		
		$ful_size = str_replace( ABSPATH, get_bloginfo('url').'/', $uploaded[ 'file' ] );

		$enconded_content = json_encode(array(
			'status' => 'success',
			'token' => $data[ 'unique_id' ],
			'file_id' => $attach_id,
			'thumbnail' => Piki::image_resize( 120, 84, $ful_size ),
			'fullsize' => $ful_size,
		));

		header('HTTP/1.1 200 OK');
		exit( "<script type=\"text/javascript\">window.parent.window.imageField_receive_image('". $enconded_content ."');</script>" );
	}

# Wordpress methods

    public static function create_rewrite_rules() {
        global $wp_rewrite; 
        $new_rules[ 'image-field/([^/]+)' ] = 'index.php?image-field=true&unique-id=$matches[1]';
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }

    # Adiciona variaveis de busca
    public static function add_query_vars( $qvars ) {
        $qvars[] = 'image-field';
        $qvars[] = 'unique-id';
        return $qvars;
    }

	public static function add_files(){
	    $filesdir = plugins_url( '/' , __FILE__ );
	    # Scripts
	    wp_register_script( 'image.field.js', $filesdir . 'image.field.js' );
	    wp_enqueue_script( 'image.field.js' );
	    # Styles
	    wp_register_style( 'image.field.css', $filesdir . 'image.field.css' ); 
	    wp_enqueue_style( 'image.field.css' );
	}
  
    # Redirecionar as páginas solicitadas para os devidos arquivos 
    public static function template_redirect_intercept(){
        global $wp_query;
        if( $wp_query->get( "image-field" ) == 'true' ):

	        # Verifica se é um POST
	        Piki::check_http_method( 'POST', true );

            $action = isset( $_POST[ 'image-field-action' ] ) && !empty( $_POST[ 'image-field-action' ] ) ? $_POST[ 'image-field-action' ] : false;

        	$file = self::extract_file( $_FILES );

        	if( $action && $action == 'delete' ):
        		$data = $_POST;
        		self::image_delete( $data );
        	elseif( $file && !empty( $file ) ):
        		$data = self::array_flatten( $_POST );
        		self::image_upload( $data, $file );
        	endif;

       		die();

        endif;
    }

	public static function image_delete( $data ){
		if( !isset( $data[ 'file_id' ] ) || (int)$data[ 'file_id' ] <= 0 ):
			Piki::error( 'Erro ao remover: Arquivo inexistente' );
		endif;
		if ( false === wp_delete_attachment( $data[ 'file_id' ], true ) ):
			Piki::error( 'Erro ao remover arquivo: Erro desconhecido' );
		endif;
		Piki::success( 'A imagem foi deletada com sucesso.' );
	}

    public static function extract_file( $array ){

        if( !is_array( $array ) || empty( $array ) ):
        	return false;
       	endif;

       	if( !array_key_exists( 'tmp_name', $array ) ):
       		return self::extract_file( array_shift( $array ) );
       	else:

       		if( !is_array( $array[ 'name' ] ) ):
       			return $array;
       		endif;

       		$return = array();
       		foreach ( $array as $key => $value) {
       			$return[ $key ] = self::array_flatten( $value );
       			if( is_array( $return[ $key ] ) ):
       				$return[ $key ] = array_shift( $return[ $key ] );
       			endif;
       		}
       		return $return;
       	endif;
    }

	public static function array_flatten($array) { 
		if (!is_array($array)) { 
			return FALSE; 
		} 
		$result = array(); 
		foreach ( $array as $key => $value ) { 
			if ( is_array( $value ) ) { 
				$result = array_merge( $result, self::array_flatten( $value ) ); 
			} 
			else { 
				$result[ $key ] = $value; 
			} 
		} 
		return $result; 
	} 


}	

add_filter( 'query_vars', array( 'image', 'add_query_vars' ) );
add_action( 'generate_rewrite_rules', array( 'image', 'create_rewrite_rules' ) );
add_action( 'template_redirect', array( 'image', 'template_redirect_intercept' ) );
