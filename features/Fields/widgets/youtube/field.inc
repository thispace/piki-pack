<?php
class youtube extends PikiField {
	
	function __construct(){

		# Label do campo
		self::set_label( 'Youtube (vídeo)' );

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){
		$html = '<input type="text" name="'.$field["name_html"].'" id="'.$field["id"].'"';
		parent::set_value( $html, $field );
		parent::set_attributes( $html, $field );
		$html .= " />";
		return $html;
	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';

		if( parent::is_required( $field ) && empty( $field[ 'value' ] ) ):

			$error = parent::get_default_message( $field, 'required' );
		
		elseif( !empty( $field[ 'value' ] ) && !self::validate_url( $field[ 'value' ] ) ):
			
			$error = parent::get_default_message( $field, 'invalid', array( '{OVALOR}' => 'A URL' ) );
		
		endif;
		
		if( $error != '' ):
			return $error;
		endif;
		
		return true;
	
	}

	public static function validate_url( $url ){
		$rx = '~
		    ^(?:https?://)?              			# Optional protocol
		     (?:www\.)?                  			# Optional subdomain
		     (?:youtube\.com|youtu\.be|youtu\.br)  	# Mandatory domain name
		     /watch\?v=([^&]+)           			# URI with video id as capture group 1
		     ~x';
		return preg_match($rx, $url, $matches);	
	}

	public static function extract_id( $url, $debug=false ){
		$length = strlen( $url );
		if( $length >= 10 && $length <= 12 ):
			return $url;
		endif;
		$pattern = 
	        '%^# Match any youtube URL
	        (?:https?://)?  # Optional scheme. Either http or https
	        (?:www\.)?      # Optional www subdomain
	        (?:             # Group host alternatives
	          youtu\.be/    # Either youtu.be,
	        | youtube\.com  # or youtube.com
	          (?:           # Group path alternatives
	            /embed/     # Either /embed/
	          | /v/         # or /v/
	          | /watch\?v=  # or /watch\?v=
	          )             # End path alternatives.
	        )               # End host alternatives.
	        ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
	        $%x'
	    ;
		$result = preg_match( $pattern, $url, $matches );
	    if ( $result && isset( $matches[ 1 ] ) ):
	        return $matches[ 1 ];
	    endif;
	    return false;
	}


	public static function renderize_values( $field, $options=array() ){

		# Código do vídeo
		$videocode = self::extract_id( $field[ 'value' ] );
		if( empty( $videocode ) ):
			return '';
		endif;

		# Formato
		$format = isset( $options[ 'format' ] ) && !empty( $options[ 'format' ] ) ? $options[ 'format' ] : 'player';

		# Título
		$title = isset( $options[ 'title' ] ) && !empty( $options[ 'title' ] ) ? $options[ 'title' ] : 'Assita o vídeo';

		# Renderizando
		switch ( $format ):

			# Link para o vídeo
			case 'link':

				$better_token = md5(uniqid(rand(), true));

				$html = '
					<a href="https://www.youtube.com/watch?gl=BR&v='. $videocode .'&hl=pt" title="'. $title .'" rel="_blank" class="youtube-inline-widget" video-code="'. $videocode .'" video-token="video-'. $better_token .'">'. $title .'</a>
				';
				
			    wp_enqueue_script( 'youtube-api-scripts', 'https://www.youtube.com/iframe_api', array( 'jquery' ) );
			    wp_enqueue_script( 'youtube-field-scripts', plugins_url( '/scripts.js' , __FILE__ ), array( 'jquery' ) );
			    wp_enqueue_style( 'youtube-field-styles', plugins_url( '/styles.css' , __FILE__ ) );
			
			break;

			# Player do vídeo
			default:
				$html = '
				<div class="video-container">
					<iframe width="420" height="315" src="https://www.youtube.com/embed/'. $videocode .'/?enablejsapi=1&rel=0" frameborder="0" allowfullscreen></iframe>
				</div>'
				;
			break;

		endswitch;

		return $html;

	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields();
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}