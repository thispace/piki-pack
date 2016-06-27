<?php
function pikiform18_register(){
   $post_type_settings=array(
'can_export'=>true,
'capability_type'=>'imovel',
'description'=>'',
'exclude_from_search'=>false,
'has_archive'=>true,
'hierarchical'=>false,
'labels'=>array (
  'name' => 'Imóveis',
  'singular_name' => 'Imóvel',
  'add_new' => 'Adicionar novo',
  'add_new_item' => 'Adicionar novo imóvel',
  'edit_item' => 'Editar imóvel',
  'new_item' => 'Novo imóvel',
  'view_item' => 'Ver imóvel',
  'search_items' => 'Buscar imóveis',
  'not_found' => 'Nenhum imóvel encontrado',
  'not_found_in_trash' => 'Nenhum imóvel na lixeira',
  'all' => 'todos',
),
'map_meta_cap'=>true,
'menu_icon'=>'dashicons-admin-home',
'menu_position'=>'5',
'name'=>'imovel',
'can_export'=>true,
'public'=>true,
'publicly_queryable'=>true,
'query_var'=>false,
'rewrite'=>array( 'slug' => 'imoveis' ),
'show_ui'=>true,
'show_in_admin_bar'=>true,
'show_in_nav_menus'=>true,
'show_in_menu'=>true,
'supports'=>array (
  0 => 'title',
),
'taxonomies'=>array(),
'slugtax'=>'Estados',
)
;
   register_post_type( 'imovel', $post_type_settings );
}
function pikiform_pikiform18_settings(){
    return array(
  'allways_edit' => false,
  'preview' => false,
  'moderate' => false,
  'placeholders' => false,
  'pid' => 18,
  'key' => 'pikiform18',
  'title' => '',
  'description' => '',
  'edit_redirect' => '',
  'success_redirect' => '',
  'exclude_redirect' => '',
  'success_message' => '',
  'edit_success_message' => '',
  'error_message' => '',
  'error_messages' => NULL,
  'classname' => '',
  'attributes' => '',
  'submit_button_label' => '',
  'edit_button_label' => '',
  'email' => 
  array(
    'send' => false,
    'subject' => '',
    'sender' => '',
    'to' => '',
    'replyto' => '',
  ),
  'public' => false,
  'post_type' => 'imovel',
  'post_type_active' => true,
);
}
function pikiform_pikiform18_fields(){
    return array(
  'title' => 
  array(
    'label' => 'Nome',
    'machine_name' => 'title',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'title',
    'placeholder' => '',
    'minlength' => '',
    'maxlength' => '',
  ),
  'estado_cidade' => 
  array(
    'label' => 'Estado/Cidade',
    'machine_name' => 'estado_cidade',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'ufcidade',
    'label_to_show' => 'sigla',
  ),
  'logradouro' => 
  array(
    'label' => 'Logradouro',
    'machine_name' => 'logradouro',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'text',
    'placeholder' => '',
    'minlength' => '',
    'maxlength' => '',
    'mask_type' => 'logradouro',
  ),
  'numero' => 
  array(
    'label' => 'Número',
    'machine_name' => 'numero',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'number',
    'maxlength' => '',
  ),
  'complemento' => 
  array(
    'label' => 'Complemento',
    'machine_name' => 'complemento',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'text',
    'placeholder' => '',
    'minlength' => '',
    'maxlength' => '',
    'mask_type' => 'complemento',
  ),
  'bairro' => 
  array(
    'label' => 'Bairro',
    'machine_name' => 'bairro',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'text',
    'placeholder' => '',
    'minlength' => '',
    'maxlength' => '',
    'mask_type' => 'bairro',
  ),
  'cep' => 
  array(
    'label' => 'CEP',
    'machine_name' => 'cep',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'cep',
  ),
  'estagio' => 
  array(
    'label' => 'Estágio da obra',
    'machine_name' => 'estagio',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'select',
    'options' => 
    array(
      'lancamento' => 'Lançamento',
      'construindo' => 'Em construção',
      'pronto' => 'Pronto',
    ),
    'placeholder' => '',
  ),
  'torres' => 
  array(
    'label' => 'Torres',
    'machine_name' => 'torres',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'text',
    'placeholder' => '',
    'minlength' => '',
    'maxlength' => '',
    'mask_type' => 'off',
  ),
  'unidades_description' => 
  array(
    'label' => 'Descrição das unidades',
    'machine_name' => 'unidades_description',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'text',
    'placeholder' => '',
    'minlength' => '',
    'maxlength' => '',
    'mask_type' => 'off',
  ),
  'incorporadora' => 
  array(
    'label' => 'Incorporadora',
    'machine_name' => 'incorporadora',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'text',
    'placeholder' => '',
    'minlength' => '',
    'maxlength' => '',
    'mask_type' => 'off',
  ),
  'cover' => 
  array(
    'label' => 'Capa',
    'machine_name' => 'cover',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'imagewp',
    'cover' => 'on',
    'crop' => 
    array(
      'status' => 'on',
      'ratio' => '1280x508',
    ),
    'styles' => 'teaser|width: 479,height:361,crop:cc
teaser-wide|width:983,height:361,crop:cc',
  ),
  'unidades' => 
  array(
    'label' => 'Unidades',
    'machine_name' => 'unidades',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'fieldset',
    'multiple' => 
    array(
      'status' => 'on',
      'minimo' => '1',
      'maximo' => '0',
      'abertos' => '1',
    ),
    'subfields' => 
    array(
      'quartos' => 
      array(
        'label' => 'Qtd. Quartos',
        'machine_name' => 'quartos',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'required' => 'on',
        'ftype' => 'number',
        'maxlength' => '',
      ),
      'rotulo_aba' => 
      array(
        'label' => 'Rótulo na aba',
        'machine_name' => 'rotulo_aba',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'ftype' => 'text',
        'placeholder' => '',
        'minlength' => '',
        'maxlength' => '',
        'mask_type' => 'off',
      ),
      'valor' => 
      array(
        'label' => 'Valor',
        'machine_name' => 'valor',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'required' => 'on',
        'ftype' => 'money',
      ),
      'valor_desconto' => 
      array(
        'label' => 'Valor com desconto',
        'machine_name' => 'valor_desconto',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'ftype' => 'money',
      ),
      'tipo' => 
      array(
        'label' => 'Tipo de imóvel',
        'machine_name' => 'tipo',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'required' => 'on',
        'ftype' => 'select',
        'options' => 
        array(
          'apartamento' => 'Apartamento',
          'casa' => 'Casa',
          'comercio' => 'Estabelecimento Comercial',
        ),
        'placeholder' => '',
      ),
      'unidades_disponiveis' => 
      array(
        'label' => 'Unidades disponíveis',
        'machine_name' => 'unidades_disponiveis',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'required' => 'on',
        'ftype' => 'number',
        'maxlength' => '',
      ),
      'metragem' => 
      array(
        'label' => 'Metragem',
        'machine_name' => 'metragem',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'required' => 'on',
        'ftype' => 'number',
        'maxlength' => '',
      ),
      'caracteristicas' => 
      array(
        'label' => 'Características',
        'machine_name' => 'caracteristicas',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'ftype' => 'textarea',
        'maxlength' => '',
        'html_editor' => 'on',
        'lines_number' => '',
      ),
      'vagas_garagem' => 
      array(
        'label' => 'Vagas na garagem',
        'machine_name' => 'vagas_garagem',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'ftype' => 'select',
        'options' => 
        array(
          1 => '1 vaga de garagem',
          2 => '2 vagas de garagem',
          3 => '3 vagas de garagem',
          4 => '4 vagas de garagem',
        ),
        'placeholder' => '',
      ),
    ),
  ),
  'fotos' => 
  array(
    'label' => 'Fotos',
    'machine_name' => 'fotos',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'fieldset',
    'multiple' => 
    array(
      'status' => 'on',
      'minimo' => '1',
      'maximo' => '10',
      'abertos' => '1',
    ),
    'subfields' => 
    array(
      'album_titulo' => 
      array(
        'label' => 'Título',
        'machine_name' => 'album_titulo',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'ftype' => 'text',
        'placeholder' => '',
        'minlength' => '',
        'maxlength' => '',
        'mask_type' => 'off',
      ),
      'album_imagens' => 
      array(
        'label' => 'imagens',
        'machine_name' => 'album_imagens',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'ftype' => 'imagewp',
        'gallery' => 'on',
        'crop' => 
        array(
          'ratio' => '',
        ),
        'styles' => 'thumb|width:242,height:136,crop:cc
show|width:823,height:463',
      ),
    ),
  ),
  'mapa' => 
  array(
    'label' => 'Localização',
    'machine_name' => 'mapa',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'gmap',
    'connetct_to' => '',
  ),
);
}