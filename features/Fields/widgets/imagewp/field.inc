<?php
class imagewp extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Imagem - Wordpress' );
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
			$value = !isset( $field[ 'value' ][ 'ids' ] ) ? array_shift( $field[ 'value' ] ) : $field[ 'value' ];
		endif;

		# Se é usado como capa, a mesma existe mas ainda não foi setada
		if( 
		empty( $value ) 
		&& 
		on( $field, 'cover' ) 
		&& 
		isset( $settings[ 'data' ]->ID ) 
		&& ( $thumbnail_id = get_post_thumbnail_id( $settings[ 'data' ]->ID ) ) !== false 
		):
			$value = array( 'ids' => $thumbnail_id );
		endif;

		# Objetos das imagens se existirem
		$images = self::get_images_objects( $value[ 'ids' ], $field );
		
		# Se o crop é utilizado
		$usecrop = self::coppable( $field );
		
		# Atributos adicionais
		$attrs_add = array();
		
		# Classe do campo hidden
		$fEx = new hidden();
		
		# Se é múltiplo
		if( on( $field, 'gallery' ) ):
			# Aributos
			$attrs_add[ '--multiple' ] = 'on';
			# Label do campo
			$select_label = 'Adicionar imagens';
			# Field remove
			$action_field = '';

		# Se não é múltiplo
		else:
			# Atributos
			$attrs_add[ '--multiple' ] = 'off';
			# Label do campo
			$select_label = 'Selecionar imagem';
			# Field remove
			$action_field = '<input class="imagewp-edit-button button ' . ( !$images ? 'hide' : 'show' ) . '" type="button" value="Substituir imagem" /><input class="imagewp-remove-button button ' . ( !$images ? 'hide' : 'show' ) . '" type="button" value="Remover imagem" />';
		endif;

		# Crop
		if( $usecrop ):
			$attrs_add[ '--crop' ] = 'on';
			$attrs_add[ '--crop-ratio' ] = $field[ 'crop' ][ 'ratio' ];
		endif;
		
		# Adiciona os atributos
		parent::add_attributes( $field, $attrs_add );

		# HTMl
		$html  = '<div'; parent::set_attributes( $html, $field ); $html .= '>';
		$html .= '	<div class="imagewp-media-'. ( !$usecrop ? 'thumbs' : 'croparea' ) .'  clearfix ' . ( $images == '' ? 'hide' : 'show' ) . '">';
		
		# Se já existem valores
		if ( $images ):
			
			foreach ( $images as $ikey => $image ):
				
				# Imagem com tamanho real
				$full_info = wp_get_attachment_image_src( $image->ID, 'full' );
				
				if( $usecrop ):

					$html .= '<img src="' . $full_info[ 0 ] . '" alt="' . $image->post_title . '" width="'. $full_info[ 1 ] .'" height="'. $full_info[ 2 ] .'" class="imagewp-croparea" />';
				
				else:
					

					$html .= '<div class="thumbnail" rel="'. $image->ID .'">';
					
					# Thumbnail
					$thumb_info = wp_get_attachment_image_src( $image->ID, 'thumbnail' );

					$html .= '	<img src="' . $thumb_info[ 0 ] . '" alt="' . $image->post_title . '" />';
					$html .= '	<a href="' . $full_info[ 0 ] . '" rel="' . $image->ID . '" target="_blank" class="action preview">Ampliar imagem</a>';
					$html .= '	<a rel="' . $image->ID . '" class="action remove" title="Remover">Remover imagem</a>';
					$html .= '</div>';

				endif;
				
			endforeach;
		endif;

		$html .= '	</div>';
		$html .= '	<div id="' . $field[ 'id' ] . '_status" class="imagewp-media-status"></div>';
		
		$ids = $backupfield;
		$ids[ 'label' ] = 'Ids';
		$ids[ 'value' ] = isset( $value[ 'ids' ] ) ? $value[ 'ids' ] : '';
		parent::set_as_child( $ids, 'ids' );
		parent::add_attributes( $ids, array( 'class' => 'imagews-ids' ) );
		$html .= $fEx->get_field( $ids );

		$html .= '	<input class="imagewp-select-button button '. ( ( !!$images && !on( $field, 'gallery' ) ) ? 'hide' : 'show' ) .'" type="button" value="'. $select_label .'" />';
		$html .= '	<input class="imagewp-upload-label" type="hidden" value="' . $field[ 'machine_name' ] . '" />';
		$html .= $action_field;

		if( $usecrop ):
			$extras = self::get_subfields_keys();
			foreach ( $extras as $key => $extra ):
				$extf = $backupfield;
				$extf[ 'label' ] = $extra;
				$extf[ 'value' ] = isset( $value[ $extra ] ) ? $value[ $extra ] : '';
				parent::set_as_child( $extf, $extra );
				parent::add_attributes( $extf, array( 'class' => 'coord-' . $extra ) );
				$html .= $fEx->get_field( $extf );
			endforeach;
		endif;
		
		$html .= '</div>';

		self::add_files( $field );

		return $html;
	}

	# Subcampos 
	public static function get_subfields_keys(){
		return array( 'x', 'y', 'width', 'height' );
	}

	public static function get_images_objects( $ids, $field ){

		if( is_string( $ids ) ):
			$ids = trim( $ids, ',' );
			$ids = explode( ',', $ids );
		endif;
		if( empty( $ids ) ):
			return false;
		endif;

		$images = get_posts(array(
			'posts_per_page' => -1,
			'post_type' => 'attachment',
			'include' => $ids
		));

		# Se não existem imagens
		if( empty( $images ) ):
			return false;
		endif;

		# Reordena os valores
		if( on( $field, 'gallery' ) ):
			$sorted = array_flip( $ids );	
			foreach( $images as $image ):
				$sorted[ $image->ID ] = $image;
			endforeach;			
			return $sorted;
		else:
			return $images;
		endif;

	}

	public static function validate( $field, $action ){

		$error = '';
		if( parent::is_required( $field ) && $field[ 'value' ][ 'ids' ] == '' ):
			$error = parent::get_default_message( $field, 'doselect', array( '{UMVALOR}', 'uma imagem' ) );
		endif;
		if( $error != '' ):
			return $error;
		endif;
		return true;
	}

	# Salva os valores
	public static function save_meta( $field, $ID, $value ){
		
		/*
		if( !Piki::user_can( $post->post_type, 'edit', $post_id ) ):
			return $post_id;
		endif;
		*/

		# Retira vírgula das extremidades
		$value[ 'ids' ] = trim( $value[ 'ids' ], ',' );

		# Valida se realmente existem imagens
		$images = self::get_images_objects( $value[ 'ids' ], $field );

		if( !empty( $images ) ):

			# Insere os objectos das imagens no array de valores
			$value[ 'images_objects' ] = $images;
			
			# Gerando as imagens necessárias
			$value[ 'childs' ] = self::generate_childs( $field, $ID, $value );

			# Remove o objeto da imagem do array de valor
			unset( $value[ 'images_objects' ] );
			
			# Se a imagem é usada como cover, ela é setada
			if( on( $field, 'cover' ) ):
				set_post_thumbnail( $ID, $images[ 0 ]->ID );
			endif;

			# Serializa os dados
			$serial = base64_encode( serialize( $value ) );

			# Atualiza os valores no banco
			call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ], $serial );

		# Se não existirem imagens, ou, se nenhuma for válida, limpamos o valor
		else:

			$value[ 'ids' ] = '';
			# Se a imagem é usada como cover, ela é removida
			if( isset( $field[ 'cover' ] ) && $field[ 'cover' ] == 'on' ):
				delete_post_thumbnail( $ID );
			endif;

		endif;

		return $value;

	}

	public static function generate_childs( $field, $ID, $value ){

		# Retorno
		$childs = array();

		# Se não existem imagens
		if( empty( $value[ 'images_objects' ] ) ):
			return $childs;
		endif;

		# Estilos de imagens
		$styles = self::extract_styles( $field );

		# Salva cada imagem
		foreach( $value[ 'images_objects' ] as $key => $image ):

			# Array de childs
			$childs[ $image->ID ] = array();

			# Informações da imagem original
			list( $original_url, $original_width, $original_height ) = wp_get_attachment_image_src( $image->ID, 'full' );
			
			# Path da imagem original
			$original_path = self::get_path( $original_url );

			# Se o crop é usado
			if( self::coppable( $field ) ):

				# Dimensões do crop
				list( $crop_width, $crop_height ) = explode( 'x', strtolower( $field[ 'crop' ][ 'ratio' ] ) );
					
				# Instancia a classe
				$sample = wp_get_image_editor( $original_path );
				if ( is_wp_error( $sample ) ):
					echo '<pre>';
					echo 'Problemas ao instanciar a imagem ' . $original_path;
					var_dump( $sample );
					exit;
				endif;

				# CROP

					# Caminho físico da imagem
					$cropped_path = self::add_sigla( $original_path, $field[ 'machine_name' ] . '_cropped', $ID );

					# Se a área de crop não foi informada, redimensionamos pelo apect ratio da imagem
					if( !isset( $value[ 'x' ] ) || $value[ 'x' ] === '' ):

						$value = self::crop_by_limits(
							array( 'width' => $original_width, 'height' => $original_height ),
							$crop_width,
							$crop_height	
						);
					  					
					endif;
					
					# Cropando a imagem
					$sample->crop( $value[ 'x' ], $value[ 'y' ], $value[ 'width' ], $value[ 'height' ] );
				    
				    # Salva
				    $sample->save( $cropped_path );
				    
				    # Adiciona ao array de valores
					$childs[ $image->ID ][ 'croped' ] = self::make_relative( $cropped_path );

				# RESIZE

					# URL com sigla
				    $resized_path = self::add_sigla( $original_path, $field[ 'machine_name' ] . '_resized', $ID );
					
					# Gera a imagem
				    $pkimage = new images(array( 
				    	'url' => self::make_url( $cropped_path ),
				    	'savepath' => $resized_path,
				    ));
				    $resized = $pkimage->create_fixed_size( $crop_width, $crop_height );

				    # Adiciona ao array de valores
					$childs[ $image->ID ][ 'resized' ] = $resized[ 'url' ][ 'relative' ];
				
				# STYLES

					if( $styles ):
						$styleds = self::generate_styles( $cropped_path, $styles, $field );
						$childs[ $image->ID ] = array_merge( $childs[ $image->ID ], $styleds );
					endif;
				
				# CND

					if( defined( 'W3TC' ) && !empty( $childs[ $tocrop->ID ] ) ):
						self::send_to_cdn( $childs[ $image->ID ] );
					endif;

			elseif( !empty( $styles ) ):

				# Estilos gerados
				$item_childs = self::generate_styles( $original_path, $styles, $field );
				if( !empty( $item_childs ) ):
					
					# Coloca os childs no array
					$childs[ $image->ID ] = $item_childs;
					
					# Se o CND estiver ativado
					if( defined( 'W3TC' ) && !empty( $childs ) ):
						self::send_to_cdn( $item_childs );
					endif;
				
				endif;
								
			endif;

			# Se não tem nenhuma derivação, removemos do array de retorno
			if( empty( $childs[ $image->ID ] ) ):
				unset( $childs[ $image->ID ] );
			endif;

		endforeach;

		# Retormna as imagens geradas
		return $childs;

	}

	# Gera os estilos da imagem
	public static function generate_styles( $path, $styles, $field=false ){

		# Retorno
		$return = array();
		
		# Percorre os estilos
		foreach ( $styles as $style_name => $style ):

			# Instancia a classe
			$styled = wp_get_image_editor( $path );
			
			# Qualidade
			$quality = isset( $style[ 'quality' ] ) ? $style[ 'quality' ] : 100;
			$styled->set_quality( $quality );

			# Path destino
			$styled_path = self::add_sigla( $path, $style_name );

			# Width
			if( !isset( $style[ 'width' ] ) ):
				$style[ 'width' ] = 0;
			endif;

			# Height
			if( !isset( $style[ 'height' ] ) ):
				$style[ 'height' ] = 0;
			endif;
			
			# Position
			if( isset( $style[ 'crop' ] ) ):

				if( $style[ 'width' ] > 0 && $style[ 'height' ] > 0 ):

					# Dimensões
					$info = getimagesize( $path );
					$info = array( 'width' => $info[ 0 ], 'height' => $info[ 1 ] );
					$info = self::crop_by_limits( $info, $style[ 'width' ], $style[ 'height' ] );

					# Cropando a imagem
					$styled->crop( $info[ 'x' ], $info[ 'y' ], $info[ 'width' ], $info[ 'height' ] );

					# Salva
					$styled->save( $styled_path );

					# Redimensiona para o tamanho exato
				    $pkimage = new images(array( 
				    	'url' => self::make_url( $styled_path ),
				    	'savepath' => $styled_path,
				    ));
				    $resized = $pkimage->create_fixed_size( $style[ 'width' ], $style[ 'height' ] );

				else:

					# Cropando a imagem sem limites
					$styled->resize( $style[ 'width' ], $style[ 'height' ], $style[ 'crop' ] );

					# Salva
					$styled->save( $styled_path );
				
				endif;
			
			else:

				# Redimensiona
				$styled->resize( $style[ 'width' ], $style[ 'height' ], false );

				# Salva
				$styled->save( $styled_path );

			endif;

			$return[ $style_name ] = self::make_relative( $styled_path  );
		
		endforeach;
		
		# Retorna o valor
		return $return;
	
	}
	
	public static function crop_by_limits( $info, $width, $height ){

		$ratio = $height/$width;

		# Ajustamos pelo tamanho
		$_width = $info[ 'width' ];
		$_height = round( $_width*$ratio );

		# Se a altura ultrapassou, redimensionamso pela largura
		if( $_height > $info[ 'height' ] ):
			
			$_height = $info[ 'height' ];
			$_width = round( $_height/$ratio );
			
			$y = 0;
			$x = round( ( $info[ 'width' ] - $_width ) / 2 );

		# Redimensionamos pela largura
		else:

			$x = 0;
			$y = round( ( $info[ 'height' ] - $_height ) / 2 );
		
		endif;

		return array(
			'width' 	=> $_width,
			'height' 	=> $_height,
			'x'			=> $x,
			'y' 		=> $y
		);

	}

	# Structure of style: thumb_name|width:100,height:100,crop:cc
	public static function extract_styles( $field ){
		
		# Se não tem opções de imagens
		if( !isset( $field[ 'styles' ] ) || empty( $field[ 'styles' ] ) || $field[ 'styles' ] == '0|' ):
			return false;
		endif;
		
		# Quebra por linhas e |
		$styles = textarea_options::explode_value( $field[ 'styles' ] );
		
		# Se não tem estilo, retornamos falso
		if( empty( $styles ) ) return false;
		
		foreach ( $styles as $style_name => $style ):
			$options = explode( ',', $style );
			$styles[ $style_name ] = array();
			foreach( $options as $key_option => $option ):
				list( $ko, $vo ) = explode( ':', $option );
				$styles[ $style_name ][ $ko ] = $vo;
			endforeach;
		endforeach;
		
		return $styles;
	
	}

	public static function send_to_cdn( $files ){
		# Configurações do plugin
		$w3tc_configs = w3_instance( 'W3_Config' );
		# Se o CND estiver ativado, enviamos os arquivos para a fila de uploads
        if( $w3tc_configs->get_boolean( 'cdn.enabled' ) === true ):
        	# Pré requisitos para envio dos arquivos para a fila
			w3_require_once( W3TC_LIB_W3_DIR . '/Request.php' );
			$w3_plugin_cdncommon = w3_instance('W3_Plugin_CdnCommon');
        	foreach ( $files as $key => $file ):
        		# PATH
        		$filepath = rtrim( ABSPATH, '/' ) . '/' . $file;
        		# Envia o arquivo para a fila de uploads
				$w3_plugin_cdncommon->queue_upload_url( $filepath );
        	endforeach;
        endif;
	}

	public static function unlink( $file ){
		if( file_exists( $file ) ):
			try {
				unlink( $file );
			} catch ( Exception $e ) {
			  echo "Erro ao remover imagem: ",  $e->getMessage();
			}				
		endif;
	}

	public static function coppable( $field ){
		return (
			isset( $field[ 'crop' ] )
			&& 
			on( $field[ 'crop' ], 'status' ) 
			&&
			isset( $field[ 'crop' ][ 'ratio' ] )
			&&
			!empty( $field[ 'crop' ][ 'ratio' ] )
		);
	}

	public static function change_meta_values( $field, $meta, $data_type = 'post', $data_table = false ){

		# Se não existem valores para o campo
		if( !isset( $meta[ $field[ 'machine_name' ] ] ) || !is_array( $value = $meta[ $field[ 'machine_name' ] ] ) ):
			return false;
		endif;

		# Se nenhum valor foi gravado
		foreach ( $value as $key => &$item ):
			$item = unserialize( base64_decode( $item ) );
		endforeach;
		
		# Retorna o array de resultados		
		return $value;
	
	}


	public static function renderize_values( $field, $options=array() ){

		if( empty( $field[ 'value' ] ) ):
			return false;
		endif;
		
		# ID's das imagens
		$images_ids = explode( ',', $field[ 'value' ][ 'ids' ] );

		# Retorno
		$return = array();

		# Derivações das imagens
		$childs = isset( $field[ 'value' ][ 'childs' ] ) ? $field[ 'value' ][ 'childs' ] : false;

		# URL base
		$baseurl = rtrim( get_site_url(), '/' ) . '/';

		# Se é uma galeria
		if( on( $field, 'gallery' ) ):

			# Biblioteca
			Piki::add_library( 'slick' );

			# Objetos das imagens
			$images = self::get_images_objects( $images_ids, $field );

			# Se não existem imagens
			if( !$images ): return false; endif;

			# ID único para cada galeria
			$token = md5(uniqid(rand(), true));
			$id = 'slideshow-' . $token;

			# Se a API deve ser inicializada
			$start_api = isset( $options[ 'start_api' ] ) && $options[ 'start_api' ] != 'true' ? 'false' : 'true';

			# Slick tema
			if( isset( $options[ 'theme' ] ) && $options[ 'theme' ] == 'slick' ):

				$return = '<div class="imagewp-slick-theme" id="'. $id .'" data-start-api="'. $start_api .'">';

				$_images = array();
				$_thumbs = array();
				$_total = count( $images );
				$cont = 1;
				foreach( $images as $image_id => $image_post ):

					# Imagens do form
					if( $childs && isset( $childs[ $image_id ] ) ):
						$urls = $childs[ $image_id ];
					# Imagens padrão
					else: 
						$thumb = wp_get_attachment_image_src( $image_id, 'thumbnail' );
						$show = wp_get_attachment_image_src( $image_id, 'full' );
						$urls = array(
							'thumbnail' => self::make_relative( $thumb[ 0 ] ),
							'full' => self::make_relative( $show[ 0 ] ),
						);
					endif;

					$_images[] = '
					<figure class="slide-item">
						<img src="'. $baseurl . $urls[ 'show' ] .'" alt="'. $image_post->post_title .'" />
						<figcaption class="clearfix">
							<strong>'. $image_post->post_title .'</strong>
							<span class="slide-indice">'. $cont .'&nbsp;/&nbsp;'. $_total .'</span>
						</figcaption>
					</figure>';

					$_thumbs[] = '
					<figure class="slide-item">
						<img src="'. $baseurl . $urls[ 'thumb' ] .'" alt="'. $image_post->post_title .'" />
					</figure>';

					$cont++;

				endforeach;
				
				$return .= '	<div class="main-slider-wrapper clear">
									<div class="main-slider">'. implode( '', $_images ) .'</div>
								</div>';
				$return .= '	<footer class="clear">
									<div class="menu-slider">'. implode( '', $_thumbs ) .'</div>
									<div class="see-all">
										<button title="Ver todas as fotos">
											<span class="icon"></span>
											Ver todas as fotos
										</button>
									</div>
								</footer>';
								
				$return .= '</div>';

			# Tema padrão
			else:

				Piki::add_library( 'cycle' );
				Piki::add_library( 'jcarousel' );

				$return = '
				<div class="imagewp-gallery" --gallery-id="'. $id .'">
					<div class="cycle-slideshow slideshow"
					    data-cycle-pager="#adv-custom-pager"
					    data-cycle-auto-height="container"
					    data-cycle-timeout="0"
					    >';
						foreach( $images as $key => $image ): 
							$full_info = wp_get_attachment_image_src( $image->ID, 'full' );
							$return .= '<img src="'. $full_info[ 0 ] .'" />';
						endforeach;
					$return .= '
					</div>
					<div class="jcarousel-wrapper">
		                <div class="jcarousel">
		                    <ul>';
								foreach( $images as $key => $image ):
									$thumb_info = wp_get_attachment_image_src( $image->ID );
									$return .= '<li rel="'. $key .'"><img src="'. $thumb_info[ 0 ] .'" /></li>';
								endforeach;
							$return .= '
							</ul>
		                </div>
		                <a href="#" class="jcarousel-control-prev">&lsaquo;</a>
		                <a href="#" class="jcarousel-control-next">&rsaquo;</a>
		            </div>
				</div>
				';

			endif;

		# Imagem única
		else:

			$torender = array();

			$childs = isset( $field[ 'value' ][ 'childs' ] ) && !empty( $field[ 'value' ][ 'childs' ] ) ? $field[ 'value' ][ 'childs' ] : false;

			# Se existem derivações das imagens
			if( !empty( $childs ) ):

				foreach( $childs as $key => $child ):

					if( is_array( $child ) && isset( $options[ 'style' ] ) && isset( $child[ $options[ 'style' ] ] ) ):
						$torender[] = $child[ $options[ 'style' ] ];
					else:
						$torender[] = isset( $child[ 'resized' ] ) ? $child[ 'resized' ] : array_shift( $child );
					endif;

				endforeach;

			# Se não há derivações das imagens
			else:

				$images = explode( ',', $field[ 'value' ][ 'ids' ] );
				
				foreach( $images as $key => $image_id ):
					$torender[] = str_replace( get_site_url(), '', wp_get_attachment_url( $image_id ) );
				endforeach;
			
			endif;

			# Escrevendo os HTMLs das imagens
			foreach ( $torender as $key => $item ):

				if( on( $options, 'urls' ) ):

					$return[] = get_site_url() . '/' . $item;
				
				else:

					$img = '<img src="' . get_site_url() . '/' . $item . '"';
					if( isset( $options[ 'alt' ] ) ):
						$img .= ' alt="'. $options[ 'alt' ] .'"';
					endif;
					if( isset( $options[ 'class' ] ) ):
						$img .= ' class="'. $options[ 'class' ] .'"';
					endif;
					$img .= ' />';
					$return[] = $img;

				endif;

			endforeach;

		endif;

	    wp_enqueue_script( 'imagewp-theme-scripts', plugins_url( '/theme.js' , __FILE__ ), array( 'jquery' ) );
	    wp_enqueue_style( 'imagewp-theme-styles', plugins_url( '/theme.css' , __FILE__ ) );

	    if( is_array( $return ) ):
			return implode( '', $return );
		endif;

		return $return;

	}

	public static function is_empty( $field ){
		if( isset( $field[ 'value' ][ 'ids' ] ) && !empty( $field[ 'value' ][ 'ids' ] ) ):
			return false;
		elseif( isset( $field[ 'value' ][ 0 ][ 'ids' ] ) && !empty( $field[ 'value' ][ 0 ][ 'ids' ] ) ):
			return false;
		endif;
		return true;
	}

	public static function make_url( $path ){
		$relative = self::make_relative( $path );
		return rtrim( get_site_url(), '/' ) . '/' . ltrim( $relative, '/' );
	}	

	public static function make_relative( $url ){
		$toremove = strpos( $url, 'http://' ) === 0 ? get_site_url() : ABSPATH;
		$url = str_replace( $toremove, '', $url );
		return ltrim( $url, '/' );
	}

	public static function get_path( $url ){
		$relative = self::make_relative( $url );	
		if( ( $wpcontent = strpos( $relative, 'wp-content' ) ) !== 0 ):
			$relative = substr( $relative, $wpcontent );
		endif;
		return rtrim( ABSPATH, '/' ) . '/' . $relative;
	}

	public static function add_sigla( $url, $sigla, $post_id=false ){
		# Sigla
		$_sigla = '';
		if( $post_id ):
			$_sigla .= 'p' . $post_id . '_';
		endif;
		$_sigla .= $sigla;
		# Path info
		$info = pathinfo( $url );
		# Monta o novo nome do arquivo
		$new_name = $info[ 'filename' ] . "_" . $_sigla . "." . $info[ 'extension' ];
		return $info[ 'dirname' ] . '/' . $new_name;
	}

	public static function add_files( $field ){

		# Fancybox
		Piki::add_library( 'fancybox' );

	    $filesdir = plugins_url( '/' , __FILE__ );

	    wp_enqueue_script( 'imagewp-scripts', $filesdir . 'imagewp.js', array( 'jquery' ), false, true );
	    wp_enqueue_style( 'imagewp-styles', $filesdir . 'imagewp.css' );
	   
	    # Arquivos para o crop
	    if( isset( $field[ 'crop' ] ) && isset( $field[ 'crop' ][ 'status' ] ) && $field[ 'crop' ][ 'status' ] == 'on' ):
			wp_enqueue_script( 'crop-script', $filesdir . 'Jcrop/js/jquery.Jcrop.min.js', array( 'jquery' ), '1.0.0', true );
			wp_enqueue_style( 'crop-style', $filesdir . 'Jcrop/css/jquery.Jcrop.css' );
	    endif;

	    # WP Media
		wp_enqueue_media();
	
	}

	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields();

		# Galeria de imagens?
		$fields[ 'gallery' ] = array(
			'label' => 'Seleção multipla?',
			'ftype' => 'boolean',
			'machine_name' => 'gallery'
		);
		# Utilizar como imagem destacada?
		$fields[ 'cover' ] = array(
			'label' => 'Usar como destaque?',
			'ftype' => 'boolean',
			'machine_name' => 'cover'
		);
		# Utilizar crop?
		$fields[ 'crop' ] = array(
			'label' => 'Cropar imagem?',
			'ftype' => 'crop',
			'machine_name' => 'crop'
		);
		# Estilos da imagem
		$fields[ 'styles' ] = array(
			'label' => 'Estilos de imagem',
			'description' => 'Formato: thumb_name|width:100,height:100,crop:cc',
			'ftype' => 'textarea_options',
			'machine_name' => 'styles'
		);
		
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}

}
