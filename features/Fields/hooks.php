<?php
# Imagewp - Deletando derivações das imagens 
function imagewp_delete_attachment( $post_id ){
	
	//echo '<pre>';
	//var_dump( $post_id );
	//exit;

	//echo '<pre>';
	//var_dump( $post_id );
	//exit;

}
add_action( 'delete_attachment', 'imagewp_delete_attachment' );
