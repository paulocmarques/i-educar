<?php

return new class extends clsCadastro {
    /**
     * Referencia pega da session para o idpes do usuario atual
     *
     * @var int
     */
    public $pessoa_logada;

    public $cod_biblioteca;
    public $ref_cod_instituicao;
    public $ref_cod_escola;
    public $nm_biblioteca;
    public $valor_multa;
    public $max_emprestimo;
    public $valor_maximo_multa;
    public $data_cadastro;
    public $data_exclusao;
    public $requisita_senha;
    public $bloqueia_emprestimo_em_atraso;
    public $ativo;
    public $dias_espera;

    public $dias_da_semana = [ '' => 'Selecione', 1 => 'Domingo', 2 => 'Segunda', 3 => 'Terça', 4 => 'Quarta', 5 => 'Quinta', 6 => 'Sexta', 7 => 'Sábado' ];
    public $dia;
    public $biblioteca_dia_semana;
    public $incluir_dia_semana;
    public $excluir_dia_semana;

    public $nm_feriado;
    public $data_feriado;
    public $biblioteca_feriado;
    public $incluir_feriado;
    public $excluir_feriado;

    public $tombo_automatico;

    public function Inicializar()
    {
//      $retorno = "Novo";

        $this->cod_biblioteca=$_GET['cod_biblioteca'];

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra(629, $this->pessoa_logada, 11, 'educar_biblioteca_dados_lst.php');

        $nivel_usuario = $obj_permissoes->nivel_acesso($this->pessoa_logada);
        if ($nivel_usuario <= 3) {
            $permitido = true;
        } else {
            $obj_usuario_bib = new clsPmieducarBibliotecaUsuario();
            $lista_bib = $obj_usuario_bib->lista(null, $this->pessoa_logada);
            $permitido = false;
            if ($lista_bib) {
                foreach ($lista_bib as $biblioteca) {
                    if ($this->cod_biblioteca == $biblioteca['ref_cod_biblioteca']) {
                        $permitido = true;
                    }
                }
            }
        }

        if (!$permitido) {
            $this->simpleRedirect('educar_biblioteca_dados_lst.php');
        }
        if (is_numeric($this->cod_biblioteca)) {
            $obj = new clsPmieducarBiblioteca($this->cod_biblioteca);
            $registro  = $obj->detalhe();
            if ($registro) {
                foreach ($registro as $campo => $val) {  // passa todos os valores obtidos no registro para atributos do objeto
                    $this->$campo = $val;
                }

                if ($obj_permissoes->permissao_excluir(629, $this->pessoa_logada, 11)) {
                    $this->fexcluir = true;
                }
                $retorno = 'Editar';
            }
        }
        $this->url_cancelar = ($retorno == 'Editar') ? "educar_biblioteca_dados_det.php?cod_biblioteca={$registro['cod_biblioteca']}" : 'educar_biblioteca_dados_lst.php';
        $this->nome_url_cancelar = 'Cancelar';

        $nomeMenu = $retorno == 'Editar' ? $retorno : 'Cadastrar';

        $this->breadcrumb($nomeMenu . ' dados da biblioteca', [
            url('intranet/educar_biblioteca_index.php') => 'Biblioteca',
        ]);

        return $retorno;
    }

    public function Gerar()
    {
        // primary keys
        $this->campoOculto('cod_biblioteca', $this->cod_biblioteca);

        if ($_POST) {
            foreach ($_POST as $campo => $val) {
                $this->$campo = ($this->$campo) ? $this->$campo : $val;
            }
        }

        // foreign keys

        // text
        $this->campoTexto('nm_biblioteca', 'Biblioteca', $this->nm_biblioteca, 30, 255, true, false, false, '', '', '', '', true);
        $this->campoMonetario('valor_multa', 'Valor Multa', $this->valor_multa, 8, 8, true);
        $this->campoNumero('max_emprestimo', 'Máximo Empréstimo', $this->max_emprestimo, 8, 8, true);
        $this->campoMonetario('valor_maximo_multa', 'Valor Máximo Multa', $this->valor_maximo_multa, 8, 8, true);

//      $opcoes = array( "" => "Selecione", 1 => "não", 2 => "sim" );
//      $this->campoLista( "requisita_senha", "Requisita Senha", $opcoes, $this->requisita_senha );
        $this->campoCheck('requisita_senha', 'Requisita Senha', $this->requisita_senha);

        $options = ['label' => 'Bloquear novos empréstimos em caso de atrasos de devolução', 'value' => dbBool($this->bloqueia_emprestimo_em_atraso)];
        $this->inputsHelper()->checkbox('bloqueia_emprestimo_em_atraso', $options);

        //$this->campoCheck( "bloqueia_emprestimo_em_atraso", "Bloquear novos empréstimos em caso de atrasos de entrega", dbBool($this->bloqueia_emprestimo_em_atraso) );
        $this->campoNumero('dias_espera', 'Dias Espera', $this->dias_espera, 2, 2, true);

        /*if ($this->tombo_automatico)
            $this->campoBoolLista("tombo_automatico", "Biblioteca possui tombo automático", $this->tombo_automatico);
        else
            $this->campoBoolLista("tombo_automatico", "Biblioteca possui tombo automático", "t");*/

        //-----------------------INCLUI DIA SEMANA------------------------//
        $this->campoQuebra();

        if ($_POST['biblioteca_dia_semana']) {
            $this->biblioteca_dia_semana = unserialize(urldecode($_POST['biblioteca_dia_semana']));
        }

        $qtde_registro = 0;
        if (is_numeric($this->cod_biblioteca) && !$_POST) {
            $obj = new clsPmieducarBibliotecaDia();
            $registros = $obj->lista($this->cod_biblioteca);
            if ($registros) {
                foreach ($registros as $campo) {
                    $this->biblioteca_dias_semana_tabela[$qtde_registro] =  $campo['dia'];
                    $qtde_registro++;
                }
            }
        }

        $this->campoTabelaInicio('dia_semana_biblioteca', 'Dias da semana', ['Dias da semana'], $this->biblioteca_dias_semana_tabela, 200);

        $this->campoLista('dia_semana', 'Dia', $this->dias_da_semana);
        $this->campoTabelaFim();

        $this->campoOculto('biblioteca_dia_semana', serialize($this->biblioteca_dia_semana));

        $this->campoQuebra();
        //-----------------------FIM INCLUI DIA SEMANA------------------------//

        //-----------------------INCLUI FERIADO------------------------//
        $this->campoQuebra();
        if ($_POST['biblioteca_feriado']) {
            $this->biblioteca_feriado = unserialize(urldecode($_POST['biblioteca_feriado']));
        }
        if (is_numeric($this->cod_biblioteca) && !$_POST) {
            $obj = new clsPmieducarBibliotecaFeriados();
            $registros = $obj->lista(null, $this->cod_biblioteca);
            if ($registros) {
                foreach ($registros as $campo) {
                    $aux['nm_feriado_']= $campo['nm_feriado'];
                    $aux['data_feriado_']= dataFromPgToBr($campo['data_feriado']);
                    $this->biblioteca_feriado[] = $aux;
                }
            }
        }

        unset($aux);

        if ($_POST['nm_feriado'] && $_POST['data_feriado']) {
            $aux['nm_feriado_'] = $_POST['nm_feriado'];
            $aux['data_feriado_'] = $_POST['data_feriado'];
            $this->biblioteca_feriado[] = $aux;
            unset($this->nm_feriado);
            unset($this->data_feriado);
        }

        $this->campoOculto('excluir_feriado', '');
        unset($aux);

        if ($this->biblioteca_feriado) {
            foreach ($this->biblioteca_feriado as $key => $feriado) {
                if ($this->excluir_feriado == $feriado['nm_feriado_']) {
                    unset($this->biblioteca_feriado[$key]);
                    unset($this->excluir_feriado);
                } else {
                    $this->campoTextoInv("nm_feriado_{$feriado['nm_feriado_']}", '', $feriado['nm_feriado_'], 30, 255, false, false, true);
                    $this->campoTextoInv("data_feriado_{$feriado['nm_feriado_']}", '', $feriado['data_feriado_'], 10, 10, false, false, false, '', "<a href='#' onclick=\"getElementById('excluir_feriado').value = '{$feriado['nm_feriado_']}'; getElementById('tipoacao').value = ''; {$this->__nome}.submit();\"><img src='imagens/nvp_bola_xis.gif' title='Excluir' border=0></a>");
                    $aux['nm_feriado_'] = $feriado['nm_feriado_'];
                    $aux['data_feriado_'] = $feriado['data_feriado_'];
                }
            }
        }
        $this->campoOculto('biblioteca_feriado', serialize($this->biblioteca_feriado));

        $this->campoTexto('nm_feriado', 'Feriado', $this->nm_feriado, 30, 255);
        $this->campoData('data_feriado', ' Data Feriado', $this->data_feriado);

        $this->campoOculto('incluir_feriado', '');
        $this->campoRotulo('bt_incluir_feriado', 'Feriado', '<a href=\'#\' id="event_incluir_feriado"><img src=\'imagens/nvp_bot_adiciona.gif\' alt=\'adicionar\' title=\'Incluir\' border=0></a>');

        $this->campoQuebra();

        //-----------------------FIM INCLUI FERIADO------------------------//
    }

    public function Editar()
    {
        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra(629, $this->pessoa_logada, 11, 'educar_biblioteca_dados_lst.php');

        $this->valor_multa = str_replace('.', '', $this->valor_multa);
        $this->valor_multa = str_replace(',', '.', $this->valor_multa);
        $this->valor_maximo_multa = str_replace('.', '', $this->valor_maximo_multa);
        $this->valor_maximo_multa = str_replace(',', '.', $this->valor_maximo_multa);

        $this->requisita_senha = is_null($this->requisita_senha) ? 0 : 1;

        $obj = new clsPmieducarBiblioteca(
            $this->cod_biblioteca,
            null,
            null,
            null,
            $this->valor_multa,
            $this->max_emprestimo,
            $this->valor_maximo_multa,
            null,
            null,
            $this->requisita_senha,
            1,
            $this->dias_espera,
            $this->tombo_automatico,
            $this->bloqueia_emprestimo_em_atraso == 'on'
        );
        $editou = $obj->edita();
        if ($editou) {
            //-----------------------EDITA DIA SEMANA------------------------//

            if ($this->dia_semana) {
                $obj  = new clsPmieducarBibliotecaDia($this->cod_biblioteca);
                $excluiu = $obj->excluirTodos();
                if ($excluiu) {
                    $naoRetornaDuplicados = array_unique($this->dia_semana);
                    asort($naoRetornaDuplicados);
                    foreach ($naoRetornaDuplicados as $key => $campo) {
                        $obj = new clsPmieducarBibliotecaDia(
                            $this->cod_biblioteca,
                            $this->dia_semana[$key]
                        );
                        $cadastrou1 = $obj->cadastra();

                        if (!$cadastrou1) {
                            $this->mensagem = 'Edição não realizada.<br>';

                            return false;
                        }
                    }

                    $this->mensagem .= 'Edição efetuada com sucesso.<br />';
                    $this->simpleRedirect('educar_biblioteca_dados_det.php');
                }
            }
            //-----------------------FIM DIA DA SEMANA------------------------//

            //-----------------------EDITA FERIADO------------------------//
            $obj  = new clsPmieducarBibliotecaFeriados();
            $excluiu = $obj->excluirTodos($this->cod_biblioteca);
            if ($excluiu) {
                $this->biblioteca_feriado = unserialize(urldecode($this->biblioteca_feriado));
                if ($this->biblioteca_feriado) {
                    foreach ($this->biblioteca_feriado as $feriado) {
                        $feriado['data_feriado_'] = dataToBanco($feriado['data_feriado_']);
                        $obj = new clsPmieducarBibliotecaFeriados(null, $this->cod_biblioteca, $feriado['nm_feriado_'], null, $feriado['data_feriado_'], null, null, 1);
                        $cadastrou2  = $obj->cadastra();
                        if (!$cadastrou2) {
                            $this->mensagem = 'Cadastro não realizado.<br>';

                            return false;
                        }
                    }
                }
            }
            //-----------------------FIM EDITA FERIADO------------------------//
            $this->mensagem .= 'Edição efetuada com sucesso.<br>';
            $this->simpleRedirect('educar_biblioteca_dados_lst.php');
        }

        $this->mensagem = 'Edição não realizada.<br>';

        return false;
    }

    public function Excluir()
    {
        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_excluir(629, $this->pessoa_logada, 11, 'educar_biblioteca_dados_lst.php');

        $obj = new clsPmieducarBiblioteca($this->cod_biblioteca, null, null, null, 'NULL', 'NULL', 'NULL', null, null, 0, 1, 'NULL');
        $editou = $obj->edita();
        if ($editou) {
            $obj  = new clsPmieducarBibliotecaDia($this->cod_biblioteca);
            $excluiu1 = $obj->excluirTodos();
            if ($excluiu1) {
                $obj  = new clsPmieducarBibliotecaFeriados();
                $excluiu2 = $obj->excluirTodos($this->cod_biblioteca);
                if ($excluiu2) {
                    $this->mensagem .= 'Exclusão efetuada com sucesso.<br>';
                    $this->simpleRedirect('educar_biblioteca_dados_lst.php');
                }
            }
        }

        $this->mensagem = 'Exclusão não realizada.<br>';

        return false;
    }

    public function makeExtra()
    {
        return file_get_contents(__DIR__ . '/scripts/extra/educar-biblioteca-dados-cad.js');
    }

    public function Formular()
    {
        $this->title = 'Dados Biblioteca';
        $this->processoAp = '629';
    }
};
