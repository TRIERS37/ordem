<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\Usuario;

class Usuarios extends BaseController {

    private $usuarioModel;
    private $grupoUsuarioModel;
    private $grupoModel;

    public function __construct() {
        $this->usuarioModel = new \App\Models\UsuarioModel();
        $this->grupoUsuarioModel = new \App\Models\GrupoUsuarioModel();
        $this->grupoModel = new \App\Models\GrupoModel();
    }

    public function index() {
        $data = [
            'titulo' => 'Listando os usuários do sistema',
        ];

        return view('Usuarios/index', $data);
    }

    public function recuperaUsuarios() {

        if (!$this->request->isAJAX()) {

            return redirect()->back();
        }

        $atributos = [
            'id',
            'nome',
            'email',
            'ativo',
            'imagem',
            'deletado_em',
        ];

        $usuarios = $this->usuarioModel->select($atributos)
                ->withDeleted(true)
                ->orderBy('id', 'DESC')
                ->findAll();

        $data = [
        ];
        foreach ($usuarios as $usuario) {


            if ($usuario->imagem != null) {
                $imagem = [
                    'src' => site_url("usuarios/imagem/$usuario->imagem"),
                    'class' => 'rounded-circle img-fluid',
                    'alt' => esc($usuario->nome),
                    'width' => '50',
                ];
            } else {
                $imagem = [
                    'src' => site_url("recursos/img/usuario_sem_imagem.png"),
                    'class' => 'rounded-circle img-fluid',
                    'alt' => 'Usuário sem imagem',
                    'width' => '50',
                ];
            }

            $data[] = [
                'imagem' => $usuario->imagem = img($imagem),
                'nome' => anchor("usuarios/exibir/$usuario->id", esc($usuario->nome), 'title="Exibir usuário ' . esc($usuario->nome) . '"'),
                'email' => esc($usuario->email),
                'ativo' => $usuario->exibeSituacao(),
            ];
        }

        $retorno = [
            'data' => $data,
        ];

        return $this->response->setJSON($retorno);
    }

    public function criar() {
        $usuario = new Usuario();

        $data = [
            'titulo' => "Criando novo usuário ",
            'usuario' => $usuario,
        ];

        return view('Usuarios/criar', $data);
    }

    public function cadastrar() {
        if (!$this->request->isAJAX()) {

            return redirect()->back();
        }

        $retorno['token'] = csrf_hash();

        $post = $this->request->getPost();

        //crio novo objeto da entidade usuário
        $usuario = new Usuario($post);

        if ($this->usuarioModel->protect(false)->save($usuario)) {

            $btnCriar = anchor("usuarios/criar/", 'Cadastrar novo usuário', ['class' => 'btn btn-danger mt-2']);

            session()->setFlashdata('sucesso', "Dados salvos com sucesso! <br> $btnCriar");
            $retorno['id'] = $this->usuarioModel->getInsertID();
            return $this->response->setJSON($retorno);
        }

        $retorno['erro'] = 'por favor verifique os erros abaixo e tente novamente';
        $retorno['erros_model'] = $this->usuarioModel->errors();

        return $this->response->setJSON($retorno);
    }

    public function exibir(int $id = null) {
        $usuario = $this->buscaUsuarioOu404($id);

        $data = [
            'titulo' => "Detalhando o usuário " . esc($usuario->nome),
            'usuario' => $usuario,
        ];

        return view('Usuarios/exibir', $data);
    }

    public function editar(int $id = null) {
        $usuario = $this->buscaUsuarioOu404($id);

        $data = [
            'titulo' => "Editando o usuário " . esc($usuario->nome),
            'usuario' => $usuario,
        ];

        return view('Usuarios/editar', $data);
    }

    public function atualizar() {
        if (!$this->request->isAJAX()) {

            return redirect()->back();
        }

        $retorno['token'] = csrf_hash();

        $post = $this->request->getPost();

        $usuario = $this->buscaUsuarioOu404($post['id']);

        if (empty($post['password'])) {
            unset($post['password']);
            unset($post['password_confirmation']);
        }

        $usuario->fill($post);

        if ($usuario->hasChanged() == false) {
            $retorno['info'] = 'Não há dados para serem atualizados';
            return $this->response->setJSON($retorno);
        }

        if ($this->usuarioModel->protect(false)->save($usuario)) {

            session()->setFlashdata('sucesso', 'Dados salvos com sucesso!');
            return $this->response->setJSON($retorno);
        }

        $retorno['erro'] = 'por favor verifique os erros abaixo e tente novamente';
        $retorno['erros_model'] = $this->usuarioModel->errors();

        return $this->response->setJSON($retorno);
    }

    public function editarImagem(int $id = null) {
        $usuario = $this->buscaUsuarioOu404($id);

        $data = [
            'titulo' => "Alterando a imagem do usuário " . esc($usuario->nome),
            'usuario' => $usuario,
        ];

        return view('Usuarios/editar_imagem', $data);
    }

    public function upload() {
        if (!$this->request->isAJAX()) {

            return redirect()->back();
        }

        $retorno['token'] = csrf_hash();

        $validacao = service('validation');

        $regras = [
            'imagem' => 'uploaded[imagem]|max_size[imagem,1024]|ext_in[imagem,png,jpg,jpeg,webp]',
        ];
        $mensagens = [// Errors
            'imagem' => [
                'uploaded' => 'Por favor escolha uma imagem',
                'ext_in' => 'Por favor escolha uma imagem png,jpg,jpeg,webp',
            ],
        ];

        $validacao->setRules($regras, $mensagens);

        if ($validacao->withRequest($this->request)->run() == false) {
            $retorno['erro'] = 'por favor verifique os erros abaixo e tente novamente';
            $retorno['erros_model'] = $validacao->getErros();

            return $this->response->setJSON($retorno);
        }

        $post = $this->request->getPost();

        $usuario = $this->buscaUsuarioOu404($post['id']);

        $imagem = $this->request->getFile('imagem');

        list($largura, $altura) = getimagesize($imagem->getPathName());

        if ($largura < "300" || $altura < "300") {
            $retorno['erro'] = 'por favor verifique os erros abaixo e tente novamente';
            $retorno['erros_model'] = ['dimensao' => 'A imagem não pode ser menor do que 300 x 300 pixels'];

            return $this->response->setJSON($retorno);
        }

        $caminhoImagem = $imagem->store('usuarios');
        $caminhoImagem = WRITEPATH . "uploads/$caminhoImagem";

        $this->manipulaImagem($caminhoImagem, $usuario->id);

        $imagemAntiga = $usuario->imagem;

        $usuario->imagem = $imagem->getName();
        $this->usuarioModel->save($usuario);

        if ($imagemAntiga != null) {
            $this->removeImagemDoFileSystem($imagemAntiga);
        }

        session()->setFlashdata('sucesso', 'Imagem atualizada com sucesso!');

        return $this->response->setJSON($retorno);
    }

    public function imagem(string $imagem = null) {
        if ($imagem != null) {
            $this->exibeArquivo('usuarios', $imagem);
        }
    }

    public function excluir(int $id = null) {
        $usuario = $this->buscaUsuarioOu404($id);

        if ($usuario->deletado_em != null) {
            return redirect()->back()->with('info', "Esse usuário já encontra - se excluído");
        }

        if ($this->request->getMethod() === 'post') {

            $this->usuarioModel->delete($usuario->id);

            if ($usuario->imagem != null) {
                $this->removeImagemDoFileSystem($usuario->imagem);
            }

            $usuario->imagem = null;
            $usuario->ativo = false;

            $this->usuarioModel->protect(false)->save($usuario);

            return redirect()->to(site_url("usuarios"))->with('sucesso', "Usuário $usuario->nome excluído com sucesso!");
        }

        $data = [
            'titulo' => "Excluindo o usuário " . esc($usuario->nome),
            'usuario' => $usuario,
        ];

        return view('Usuarios/excluir', $data);
    }

    public function desfazerExclusao(int $id = null) {
        $usuario = $this->buscaUsuarioOu404($id);

        if ($usuario->deletado_em == null) {
            return redirect()->back()->with('info', "Apenas usuários excluídos podem ser recuperados");
        }


        $usuario->deletado_em = null;
        $this->usuarioModel->protect(false)->save($usuario);

        return redirect()->back()->with('sucesso', "Usuário $usuario->nome recuperado com sucesso");
    }

    public function grupos(int $id = null) {
        $usuario = $this->buscaUsuarioOu404($id);

        $usuario->grupos = $this->grupoUsuarioModel->recuperaGruposDoUsuario($usuario->id, 5);
        $usuario->pager = $this->grupoUsuarioModel->pager;

        $data = [
            'titulo' => "Gerenciando os grupos de acesso do usuário " . esc($usuario->nome),
            'usuario' => $usuario,
        ];

        //dd((in_array(2, array_column($usuario->grupos, 'grupo_id'))));

        if (in_array(2, array_column($usuario->grupos, 'grupo_id'))) {
            return redirect()->to(site_url("usuarios/exibir/$usuario->id"))
                            ->with('info', "Esse usuário é um clientes, portanto, não é necessário atribuí-lo ou removê-lo de outro grupo de acesso");
        }
        
        if (in_array(1, array_column($usuario->grupos, 'grupo_id'))) {
            $usuario->full_control = true;
            
            return view('Usuarios/grupos', $data);
        }
        
        $usuario->full_control = false;

        if (!empty($usuario->grupos)) {
            $gruposExistentes = array_column($usuario->grupos, 'grupo_id');

            $data['gruposDisponiveis'] = $this->grupoModel->where('id !=', 2)->whereNotIn('id', $gruposExistentes)->findAll();
        } else {
            $data['gruposDisponiveis'] = $this->grupoModel->where('id !=', 2)->findAll();
        }

        //dd($data['gurposDisponiveis']);

        return view('Usuarios/grupos', $data);
    }

    public function salvarGrupos() {
//        if (!$this->request->isAJAX()) {
//
//            return redirect()->back();
//        }

        $retorno['token'] = csrf_hash();

        $post = $this->request->getPost();

        $usuario = $this->buscaUsuarioOu404($post['id']);

        if (empty($post['grupo_id'])) {
            $retorno['erro'] = 'por favor verifique os erros abaixo e tente novamente';
            $retorno['erros_model'] = ['grupo_id' => 'Escolha uma ou mais grupos para salvar'];

            return $this->response->setJSON($retorno);
        }

        if (in_array(2, $post['grupo_id'])) {
            $retorno['erro'] = 'por favor verifique os erros abaixo e tente novamente';
            $retorno['erros_model'] = ['grupo_id' => 'O grupo de cliente não pode ser atribuído de forma manual'];

            return $this->response->setJSON($retorno);
        }
        
        
        if (in_array(1, $post['grupo_id'])){
            
            $grupoAdmin = [
                'grupo_id' => 1,
                'usuario_id' => $usuario->id
            ];
            
            $this->grupoUsuarioModel->insert($grupoAdmin);
            $this->grupoUsuarioModel->where('grupo_id !=', 1)
                    ->where('usuario_id', $usuario->id)
                    ->delete();
            
            session()->setFlashdata('sucesso', 'Dados salvos com sucesso!');
            session()->setFlashdata('info', 'Se o grupo administrador for escolhido, não há necessidade de informar outros grupos, pois apenas o Administrador será associado ao usuário !');
            return $this->response->setJSON($retorno);
            
        }
        
        
        $grupoPush = [];
        
        foreach ($post['grupo_id'] as $grupo){
            array_push($grupoPush, [
                'grupo_id' => $grupo,
                'usuario_id' => $usuario->id
            ]);
        }
        
       
        $this->grupoUsuarioModel->insertBatch($grupoPush);
        
        session()->setFlashdata('sucesso', 'Dados salvos com sucesso!');
            return $this->response->setJSON($retorno);
    }
    
    
    public function removeGrupo(int $principal_id = null){
        if($this->request->getMethod() === 'post'){
            $grupoUsuario = $this->buscaGrupoUsuarioOu404($principal_id);
            
            
            
            if($grupoUsuario->grupo_id == 2){
                return redirect()->to(site_url("usuarios/exibir/$grupoUsuario->usuario_id"))->with("info", "Não é permitido a exclusão do grupo de clientes");
            }
            
            $this->grupoUsuarioModel->delete($principal_id);
            return redirect()->back()->with("sucesso", "Usuário removido do grupo de acesso com sucesso!");
        }
        
        //não e post
        return redirect()->back();
    }

    /**
     * Método que recupera o usuário
     * @param int $id
     * @return Exceptions|object 
     * @throws type
     */
    private function buscaUsuarioOu404(int $id = null) {
        if (!$id || !$usuario = $this->usuarioModel->withDeleted(true)->find($id)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Não encontramos o usuário $id");
        }

        return $usuario;
    }
    
    private function buscaGrupoUsuarioOu404(int $principal_id = null) {
        if (!$principal_id || !$grupoUsuario = $this->grupoUsuarioModel->find($principal_id)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Não encontramos o registro de associação ao grupo de acesso $$principal_id");
        }

        return $grupoUsuario;
    }

    private function removeImagemDoFileSystem(string $imagem) {
        $caminhoImagem = WRITEPATH . "uploads/usuarios/$imagem";

        if (is_file($caminhoImagem)) {
            unlink($caminhoImagem);
        }
    }

    private function manipulaImagem(string $caminhoImagem, int $usuario_id) {
        service('image')
                ->withFile($caminhoImagem)
                ->fit(300, 300, 'center')
                ->save($caminhoImagem);

        $anoAtual = date('Y');

        //marca d'agua
        \Config\Services::image('imagick')
                ->withFile($caminhoImagem)
                ->text("Ordem $anoAtual - User - ID $usuario_id", [
                    'color' => '#fff',
                    'opacity' => 0.5,
                    'withShadow' => false,
                    'hAlign' => 'center',
                    'vAlign' => 'bottom',
                    'fontSize' => 10,
                ])
                ->save($caminhoImagem);
    }

}
