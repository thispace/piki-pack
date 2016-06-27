<?php
function pikiform135_register(){
   $post_type_settings=array(
'can_export'=>true,
'capability_type'=>'falegerente',
'description'=>'',
'exclude_from_search'=>true,
'has_archive'=>false,
'hierarchical'=>false,
'labels'=>array (
  'name' => 'Fale com seu gerente',
  'singular_name' => 'Fale com seu gerente',
  'add_new' => 'Adicionar novo',
  'add_new_item' => 'Adicionar novo item',
  'edit_item' => 'Editar item',
  'new_item' => 'Novo item',
  'view_item' => 'Ver item',
  'search_items' => 'Buscar items',
  'not_found' => 'Nenhum item encontrado',
  'not_found_in_trash' => 'Nenhum item na lixeira',
  0 => 'Array',
),
'map_meta_cap'=>true,
'menu_icon'=>'dashicons-format-status',
'menu_position'=>'5',
'name'=>'falegerente',
'can_export'=>true,
'public'=>true,
'publicly_queryable'=>false,
'query_var'=>true,
'rewrite'=>array( 'slug' => 'falegerentes' ),
'show_ui'=>true,
'show_in_admin_bar'=>true,
'show_in_nav_menus'=>false,
'show_in_menu'=>true,
'supports'=>array (
  0 => 'title',
),
'taxonomies'=>array(),
'slugtax'=>false,
)
;
   register_post_type( 'falegerente', $post_type_settings );
}
function pikiform_pikiform135_settings(){
    return array(
  'allways_edit' => false,
  'preview' => false,
  'moderate' => false,
  'placeholders' => false,
  'pid' => 135,
  'key' => 'pikiform135',
  'title' => '',
  'description' => '',
  'edit_redirect' => '',
  'success_redirect' => '',
  'exclude_redirect' => '',
  'success_message' => '<div class="status success"><h2>Formulário enviado com sucesso. <br />Aguarde o contato do seu gerente de relacionamento.</h2></div>
<a href="[home]" title="Página inicial" class="arrow-right gray">Ir para a página inicial</a>
',
  'edit_success_message' => '',
  'error_message' => '',
  'error_messages' => NULL,
  'classname' => '',
  'attributes' => '',
  'submit_button_label' => 'Enviar',
  'edit_button_label' => '',
  'email' => 
  array(
    'send' => false,
    'subject' => '',
    'sender' => '',
    'to' => '',
    'replyto' => '',
  ),
  'public' => true,
  'post_type' => 'falegerente',
  'post_type_active' => true,
);
}
function pikiform_pikiform135_fields(){
    return array(
  'title' => 
  array(
    'label' => 'Nome completo',
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
  'telefone' => 
  array(
    'label' => 'Telefone fixo',
    'machine_name' => 'telefone',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'telephone',
    'placeholder' => '____-____',
    'prefixo' => 'on',
    'format' => 'fixo',
  ),
  'celular' => 
  array(
    'label' => 'Celular',
    'machine_name' => 'celular',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'telephone',
    'placeholder' => '____-____',
    'prefixo' => 'on',
    'format' => 'celular',
  ),
  'email' => 
  array(
    'label' => 'Email',
    'machine_name' => 'email',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'email',
    'placeholder' => '',
  ),
  'cpf' => 
  array(
    'label' => 'CPF',
    'machine_name' => 'cpf',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'cpf',
    'placeholder' => '___.___.___-__',
  ),
  'periodo' => 
  array(
    'label' => 'Melhor período para contato',
    'machine_name' => 'periodo',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'required' => 'on',
    'ftype' => 'select',
    'options' => 
    array(
      'manha' => 'Período da manhã',
      'tarde' => 'Período da tarde',
    ),
    'placeholder' => 'Selecione',
  ),
  'clientes' => 
  array(
    'label' => 'Apenas para clientes do <strong>Banco do Brasil</strong>, informar:',
    'machine_name' => 'clientes',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'fieldset',
    'multiple' => 
    array(
      'minimo' => '1',
      'maximo' => '10',
      'abertos' => '2',
    ),
    'subfields' => 
    array(
      'agencia' => 
      array(
        'label' => '&lt;strong&gt;Agência&lt;/strong&gt; (opcional)',
        'machine_name' => 'agencia',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'ftype' => 'text',
        'placeholder' => '',
        'minlength' => '6',
        'maxlength' => '6',
        'mask_type' => 'off',
      ),
      'conta' => 
      array(
        'label' => '&lt;strong&gt;Conta&lt;/strong&gt; (opcional)',
        'machine_name' => 'conta',
        'description' => '',
        'icone' => '',
        'tooltip' => '',
        'ftype' => 'text',
        'placeholder' => '',
        'minlength' => '',
        'maxlength' => '13',
        'mask_type' => 'off',
      ),
    ),
  ),
  'url_imovel' => 
  array(
    'label' => 'URL do imóvel',
    'machine_name' => 'url_imovel',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'hide_label' => 'on',
    'ftype' => 'text',
    'placeholder' => '',
    'minlength' => '',
    'maxlength' => '',
    'mask_type' => 'off',
  ),
  'status' => 
  array(
    'label' => 'Status',
    'machine_name' => 'status',
    'description' => '',
    'icone' => '',
    'tooltip' => '',
    'ftype' => 'select',
    'just_admin' => 'on',
    'options' => 
    array(
      'pendente' => 'Pendente',
      'visto' => 'Já visto',
      'contatado' => 'Contatado',
    ),
    'placeholder' => '',
  ),
);
}