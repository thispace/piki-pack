<?php
# Chave do formulário de compartilhametno por email
define( 'RESUME_FORM_KEY', 'resume' );

class Resume {

    public static function init(){
        # Share buttons
        add_shortcode( 'resume', array( 'Resume', 'shortcode' ) );
        # Tipo de post
        self::register_post_type();
    }

    public static function register_post_type(){

        # Tipo de conteúdo
        $post_type_settings = array(
            'labels' => array (
                'name' => 'Curriculos',
                'singular_name' => 'Currículo',
                'add_new' => 'Adicionar novo',
                'add_new_item' => 'Adicionar novo currículo',
                'edit_item' => 'Editar currículo',
                'new_item' => 'Novo currículo',
                'view_item' => 'Ver currículo',
                'search_items' => 'Procurar currículos',
                'not_found' => 'Nenhum currículo encontrado',
                'not_found_in_trash' => 'Nada econtrado na lixeira',
                'all' => 'todos',
            ),
            'public'                => true,
            'publicly_queryable'    => false,
            'query_var'             => false,
            'rewrite'               => false,
            'hierarchical'          => false,
            'menu_position'         => 9,
            'exclude_from_search'   => true,
            'supports'              => array( 'title', 'thumbnail' ),
            'show_ui'               => true, 
            'show_in_menu'          => true,
            'menu_icon' => 'dashicons-nametag',
            'register_meta_box_cb'  => array( 'Resume', 'add_formfields' ),
        );
        register_post_type( RESUME_FORM_KEY, $post_type_settings );
    
    }

    # Adiciona os campos 
    public static function add_formfields( $post ){
        if( $post->post_type == RESUME_FORM_KEY ):
            add_meta_box( 
                'pikiform_fields', 
                'Campos', 
                array( 'PikiForms_ctype', 'render_formfields' ), 
                PikiForms_ptype, 
                'advanced',
                'high'
            );
        endif;
    }

    # Shortocode
    public static function shortcode( $atts ){
        $defaults = array( 'title' => false );
        extract( shortcode_atts( $defaults, $atts ));
        $html = '<div class="resume-form-wrapper clearfix"><div class="resume-form-content clearfix">' . do_shortcode( '[pikiforms fid="'. RESUME_FORM_KEY .'" class="resume"]' ) . '</div></div>';
        self::add_files();
        return $html;
    }

    # Adiciona os arquivos
    public static function add_files(){
        # Scripts e estilos
        $path = plugins_url( '/' , __FILE__ );
        wp_enqueue_script( 'resume-scripts', ( $path . 'resume.js' ), array( 'jquery' ) );
        wp_enqueue_style( 'resume-styles', ( $path . 'resume.css' ) );
    }

    # Recupera os cursos
    public static function get_cursos(){
        return array(
            'Agronomia',
            'Aqüicultura',
            'Análise de Sistemas',
            'Astronomia',
            'Automação Industrial',
            'Bioengenharia (ou Engenharia Biológica)',
            'Biotecnologia (ou Engenharia Biotecnológica)',
            'Biocombustíveis',
            'Ciências exatas (Licenciatura)',
            'Ciências Moleculares (ou Biomoleculares)',
            'Ciências Naturais',
            'Computação',
            'Computação Científica',
            'Construção Civil (ou Engenharia Civil)',
            'Ciência e Tecnologia',
            'Eletrônica',
            'Engenharia Aeroespacial',
            'Engenharia Aeronáutica',
            'Engenharia Agrícola',
            'Engenharia de agrimensura',
            'Engenharia de alimentos',
            'Engenharia de Áudio',
            'Engenharia Urbana',
            'Engenharia Biofísica',
            'Engenharia Biomédica',
            'Engenharia Cartográfica',
            'Engenharia de computação',
            'Engenharia de Controle e Automação',
            'Engenharia Econômica',
            'Engenharia Elétrica (ou Engenharia Energética)',
            'Engenharia Eletrônica',
            'Engenharia Estrutural',
            'Engenharia Física',
            'Engenharia Florestal',
            'Prospecção Geofísica',
            'Engenharia Geográfica',
            'Engenharia Geológica',
            'Engenharia Hidráulica',
            'Engenharia Humana',
            'Engenharia Industrial',
            'Engenharia Industrial Madeireira',
            'Engenharia de informação',
            'Engenharia de instrumentação',
            'Engenharia de instrumentação (ou Engenharia de Controle e Automação ou Robótica)',
            'Engenharia de manufatura',
            'Engenharia de manutenção',
            'Engenharia de materiais',
            'Engenharia mecânica',
            'Engenharia mecatrônica',
            'Engenharia metalúrgica',
            'Engenharia militar',
            'Engenharia multimídia',
            'Engenharia de minas',
            'Engenharia naval e oceânica',
            'Engenharia nuclear',
            'Engenharia óptica',
            'Engenharia de pesca',
            'Engenharia de petróleo',
            'Engenharia de produção',
            'Engenharia da qualidade',
            'Engenharia química',
            'Engenharia sanitária',
            'Engenharia de serviços',
            'Engenharia de sistemas',
            'Engenharia de sistemas eletrônicos',
            'Engenharia de software',
            'Engenharia de tecidos',
            'Engenharia de telecomunicações',
            'Engenharia têxtil',
            'Engenharia de transportes',
            'Eletrônica embarcada (ou Autotrônica)',
            'Farmacêutica (ou Farmácia ou ainda Bioquímica)',
            'Física',
            'Física biológica',
            'Física computacional',
            'Física médica',
            'Geociências (ou Ciências da Terra)',
            'Geofísica',
            'Geologia',
            'Informática (ou Ciência da Computação)',
            'Informática biomédica',
            'Química',
            'Transporte Hidroviário (ou Transporte Marítimo)',
            'Manutenção Industrial',
            'Matemática',
            'Matemática Aplicada',
            'Matemática Computacional',
            'Ciência dos materiais',
            'Mecânica - processos de produção',
            'Mecânica de precisão (ou Mecatrônica)',
            'Metalurgia',
            'Meteorologia',
            'Oceanografia (ou oceanologia ou ainda Ciência dos Mares)',
            'Processos Gerenciais',
            'Processamento de dados',
            'Produção',
            'Produção de materiais plásticos',
            'Produção Têxtil',
            'Química Ambiental',
            'Química Industrial',
            'Saneamento',
            'Segurança da informação',
            'Sistemas de informação',
            'Tecnologia da informação',
            'Telecomunicações',
            'Telemática',
            'Ciências biológicas',
            'Alimentos',
            'Bioenergia sucro-alcooleira',
            'Biologia',
            'Biologia marinha',
            'Biomedicina',
            'Biotecnologia',
            'Botânica',
            'Ciência dos alimentos',
            'Ciências ambientais',
            'Ciências da atividade física',
            'Ciências biológicas (ou Biologia)',
            'Ciências do esporte',
            'Cosmetologia e Estética ou Estética e Cosmética',
            'Ecologia',
            'Engenharia Ambiental',
            'Educação física',
            'Enfermagem',
            'Esportes',
            'Fisioterapia',
            'Fonoaudiologia',
            'Gerontologia',
            'Medicina',
            'Medicina veterinária',
            'Meio ambiente e recursos hídricos',
            'Microbiologia (ou Imunologia)',
            'Nutrição',
            'Obstetrícia',
            'Odontologia',
            'Psicologia',
            'Radiologia',
            'Saúde',
            'Silvicultura',
            'Tecnologias em saúde - oftálmica e radiológica',
            'Terapia ocupacional',
            'Zootecnia',
            'Administração',
            'Administração pública',
            'Agronegócio',
            'Antropologia',
            'Arqueologia',
            'Arquitetura',
            'Arquivologia',
            'Artes cênicas',
            'Artes plásticas',
            'Artes visuais',
            'Automação de escritório (ou Secretariado)',
            'Biblioteconomia',
            'Ciências atuariais',
            'Comércio exterior',
            'Ciências contábeis (ou Contabilidade)',
            'Ciências econômicas (ou Economia)',
            'Ciência da informação',
            'Ciências políticas (ou Ciências do Estado)',
            'Ciências sociais (ou Sociologia)',
            'Cinema',
            'Comunicação social',
            'Cooperativismo (ou associativismo em Redes de empresas e no agronegócio)',
            'Dança',
            'Defesa e Gestão Estratégica Internacional',
            'Design',
            'Design de interiores',
            'Design de moda',
            'Desenho industrial (ou Design gráfico)',
            'Direito',
            'Editoração (ou produção editorial)',
            'Educação artística',
            'Educação musical',
            'Escultura',
            'Estatística',
            'Estudos literários',
            'Eventos',
            'Fotografia (ou Gravura)',
            'Filosofia',
            'Gastronomia',
            'Geografia',
            'Gestão da Qualidade',
            'Gestão Ambiental',
            'Gestão de Comércio Internacional',
            'Gestão de empresas',
            'Gestão empresarial',
            'Gestão de Políticas Públicas',
            'Gestão da Produção de Calçados',
            'História',
            'História da arte',
            'Hotelaria',
            'Jornalismo',
            'Lazer',
            'Letras',
            'Linguística',
            'Logística',
            'Logística Aeroportuária',
            'Logística e Transportes',
            'Marketing (ou Propaganda)',
            'Museologia',
            'Música',
            'Paisagismo',
            'Pedagogia',
            'Pintura',
            'Psicopedagogia',
            'Publicidade',
            'Produção Fonográfica',
            'Radialismo (ou Rádio & TV, ou ainda Audiovisual)',
            'Recursos humanos',
            'Relações internacionais (ou Diplomacia)',
            'Relações públicas',
            'Secretariado',
            'Serviço social',
            'Teatro',
            'Teologia',
            'Design de jogos digitais',
            'Turismologia',
            'Urbanismo',
        );  
    }

}
add_action( 'init', array( 'Resume', 'init' ) );
# Conteúdo dos emails automáticos
//add_filter( 'pikiforms_email_configs', array( 'Resume', 'email_content' ), 2, 3 );

# Dados do formulário de cadastro
function pikiform_resume_settings(){
    return array(
        'allways_edit' => false,
        'preview' => false,
        'moderate' => false,
        'placeholders' => false,
        'pid' => false,
        'key' => 'resume',
        'title' => '',
        'description' => '',
        'edit_redirect' => '',
        'success_redirect' => '',
        'exclude_redirect' => '',
        'success_message' => '<h2>Seu currículo foi enviado com sucesso</h2><p>A CNB abradece o seu interesse.</p>',
        'error_messages' => array(
            'tooltip' => 'tooltip',
        ),
        'edit_success_message' => '',
        'classname' => '',
        'attributes' => '',
        'submit_button_label' => 'Enviar currículo',
        'edit_button_label' => 'Salvar currículo',
        'email' => array(
            'send' => false,
            'subject' => 'Cadastro de currículo',
            'sender' => get_option( 'admin_email' ),
            'to' => '[field_email]',
            'replyto' => '[field_email]',
        ),
        'public' => true,
        'post_type' => 'resume',
        'post_type_active' => true,
    );
}

# Campos do formulário de cadastro
function pikiform_resume_fields(){
    return array(
        # Dados pessoais
        'dados_pessoais' => array(
            'label' => 'Dados pessoais',
            'description' => '',
            'tooltip' => '',
            'ftype' => 'fieldset',
            'machine_name' => 'dados_pessoais',
            'multiple' => array(
                'minimo' => '1',
                'maximo' => '10',
                'abertos' => '2',
            ),
            'subfields' => array(
                'nome' => array(
                    'label' => 'Nome',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'title',
                    'machine_name' => 'nome',
                    'maxlength' => '',
                ),
                'cpf' => array(
                    'label' => 'CPF',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'cpf',
                    'machine_name' => 'cpf',
                ),
                'nascimento' => array(
                    'label' => 'Data de nascimento',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'date',
                    'machine_name' => 'nascimento',
                    'masktype' => 'datepicker',
                ),
                'sexo' => array(
                    'label' => 'Sexo',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'select',
                    'machine_name' => 'sexo',
                    'options' => array(
                        'm' => 'Masculino',
                        'f' => 'Feminino',
                    ),
                ),
                'natural_de' => array(
                    'label' => 'Natural de',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'ufcidade',
                    'machine_name' => 'natural_de',
                    'label_to_show' => 'name',
                ),
            ),
        ),
        # Contatos
        'contatos' => array(
            'label' => 'Contato',
            'description' => '',
            'tooltip' => '',
            'ftype' => 'fieldset',
            'machine_name' => 'contatos',
            'multiple' => array(
                'minimo' => '1',
                'maximo' => '10',
                'abertos' => '2',
            ),
            'subfields' => array(
                'email' => array(
                    'label' => 'Email',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'email',
                    'machine_name' => 'email',
                ),
                'telefone_fixo' => array(
                    'label' => 'Telefone fixo',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'telephone',
                    'machine_name' => 'telefone_fixo',
                ),
                'telefone_celular' => array(
                    'label' => 'Telefone celular',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'telephone',
                    'machine_name' => 'telefone_celular',
                ),
            ),
        ),
        # Endereço
        'endereco' => array(
            'label' => 'Endereço',
            'description' => '',
            'tooltip' => '',
            'ftype' => 'fieldset',
            'machine_name' => 'endereco',
            'multiple' => array(
                'minimo' => '1',
                'maximo' => '10',
                'abertos' => '2',
            ),
            'subfields' => array(
                'logradouro' => array(
                    'label' => 'Logradouro',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'text',
                    'machine_name' => 'logradouro',
                    'maxlength' => '',
                    'mask_type' => 'logradouro',
                ),
                'numero' => array(
                    'label' => 'Número',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'number',
                    'machine_name' => 'numero',
                    'maxlength' => '',
                ),
                'bairro' => array(
                    'label' => 'Bairro',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'text',
                    'machine_name' => 'bairro',
                    'maxlength' => '',
                    'mask_type' => 'bairro',
                ),
                'cep' => array(
                    'label' => 'CEP',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'cep',
                    'machine_name' => 'cep',
                ),
                'estado_cidade' => array(
                    'label' => 'Estado/Cidade',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'ufcidade',
                    'machine_name' => 'estado_cidade',
                    'label_to_show' => 'sigla',
                ),
            ),
        ),
        # Formação
        'formacao' => array(
            'label' => 'Formação acadêmica',
            'description' => '',
            'tooltip' => '',
            'ftype' => 'fieldset',
            'machine_name' => 'formacao',
            'multiple' => array(
                'minimo' => '1',
                'maximo' => '10',
                'abertos' => '2',
            ),
            'subfields' => array(
                'nivel_de_ensino' => array(
                    'label' => 'Nível de ensino',
                    'description' => '',
                    'tooltip' => '',
                    'required' => 'on',
                    'ftype' => 'select',
                    'machine_name' => 'nivel_de_ensino',
                    'options' => array(
                        'fundamental_incompleto' => 'Fundamental incompleto',
                        'fundamental_completo' => 'Fundamental completo',
                        'medio_incompleto' => 'Médio incompleto',
                        'medio_completo' => 'Médio completo',
                    ),
                ),
                'nivel_superior' => array(
                    'label' => 'Nível superior',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'select',
                    'machine_name' => 'nivel_superior',
                    'options' => Resume::get_cursos(),
                ),
                'nivel_superior_status' => array(
                    'label' => 'Nivel superior status',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'select',
                    'machine_name' => 'nivel_superior_status',
                    'options' => array(
                        'completo' => 'Completo',
                        'incompleto' => 'Incompleto',
                    ),
                ),
                'mestrado' => array(
                    'label' => 'Mestrado',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'boolean',
                    'machine_name' => 'mestrado',
                    'label_option' => 'Mestrado',
                    'default_value' => 'off',
                ),
                'doutorado' => array(
                    'label' => 'Doutorado',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'boolean',
                    'machine_name' => 'doutorado',
                    'label_option' => 'Doutorado',
                    'default_value' => 'off',
                ),
                'mba' => array(
                    'label' => 'MBA',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'boolean',
                    'machine_name' => 'mba',
                    'label_option' => 'MBA',
                    'default_value' => 'off',
                ),
            ),
        ),
        # Cursos complementares
        'cursos_complementares' => array(
            'label' => 'Cursos complementares',
            'description' => '',
            'tooltip' => '',
            'ftype' => 'fieldset',
            'machine_name' => 'cursos_complementares',
            'multiple' =>  array(
                'status' => 'on',
                'minimo' => '1',
                'maximo' => '10',
                'abertos' => '1',
            ),
            'subfields' => array(
                'nome_do_curso' => array(
                    'label' => 'Nome do curso',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'text',
                    'machine_name' => 'nome_do_curso',
                    'maxlength' => '',
                    'mask_type' => 'off',
                ),
                'tempo_de_duracao' => array(
                    'label' => 'Tempo de duração',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'text',
                    'machine_name' => 'tempo_de_duracao',
                    'maxlength' => '',
                    'mask_type' => 'off',
                ),
                'data_de_conclusao' => array(
                    'label' => 'Data de conclusão',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'mesano',
                    'machine_name' => 'data_de_conclusao',
                ),
                'descricao_do_curso' => array(
                    'label' => 'Descrição do curso',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'textarea',
                    'machine_name' => 'descricao_do_curso',
                    'maxlength' => '',
                    'lines_number' => '',
                ),
            ),
        ),
        # Idiomas
        'idiomas' => array(
            'label' => 'Idiomas',
            'description' => '',
            'tooltip' => '',
            'ftype' => 'fieldset',
            'machine_name' => 'idiomas',
            'multiple' => array(
                'status' => 'on',
                'minimo' => '1',
                'maximo' => '10',
                'abertos' => '1',
            ),
            'subfields' => array(
                'idioma' => array(
                    'label' => 'Idioma',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'select',
                    'machine_name' => 'idioma',
                    'options' => array(
                        'alemao' => 'Alemão',
                        'arabe' => 'Árabe',
                        'espanhol' => 'Espanhol',
                        'frances' => 'Francês',
                        'hindu' => 'Hindu',
                        'ingles' => 'Inglês',
                        'italiano' => 'Italiano',
                        'japones' => 'Japonês',
                        'mandarim' => 'Mandarim',
                        'russo' => 'Russo',
                    ),
                ),
                'nivel_de_conhecimento' => array(
                    'label' => 'Nível de conhecimento',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'nivel_conhecimento',
                    'machine_name' => 'nivel_de_conhecimento',
                ),
            ),
        ),
        # Experiências profissionais
        'experiencias_profissionais' =>  array(
            'label' => 'Experiências profissionais',
            'description' => '',
            'tooltip' => '',
            'ftype' => 'fieldset',
            'machine_name' => 'experiencias_profissionais',
            'multiple' => array(
                'status' => 'on',
                'minimo' => '1',
                'maximo' => '10',
                'abertos' => '1',
            ),
            'subfields' =>  array(
                'nome_da_empresa' => array(
                    'label' => 'Nome da empresa',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'text',
                    'machine_name' => 'nome_da_empresa',
                    'maxlength' => '',
                    'mask_type' => 'off',
                ),
                'admissao' => array(
                    'label' => 'Data de admissão',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'mesano',
                    'machine_name' => 'admissao',
                ),
                'demissao' => array(
                    'label' => 'Data da demissão',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'mesano',
                    'machine_name' => 'demissao',
                ),
                'cargo' => array(
                    'label' => 'Cargo',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'text',
                    'machine_name' => 'cargo',
                    'maxlength' => '',
                    'mask_type' => 'off',
                ),
                'atividades' => array(
                    'label' => 'Descrição das atividades exercidas',
                    'description' => '',
                    'tooltip' => '',
                    'ftype' => 'textarea',
                    'machine_name' => 'atividades',
                    'maxlength' => '',
                    'lines_number' => '',
                ),
            ),
        ),
    );
}