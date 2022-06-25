<?php

namespace App\Libraries;

class Autenticacao{
    private $usuario;
    private $usuarioModel;
    private $grupoUsuarioModel;
    
    public function __construct() {
        $this->usuarioModel = new \App\Models\UsuarioModel();
        $this->grupoUsuarioModel = new \App\Models\GrupoUsuarioModel();
    }
    
    /**
     * Método que realiza o login na aplicação
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function login(string $email, string $password): bool {
        $usuario = $this->usuarioModel->buscaUsuarioPorEmail($email);
        
        if($usuario === null){
            return false;
        }
        
        if($usuario->verificaPassword($password) == false){
            return false;
        }
        
        if($usuario->ativo == false){
            return false;
        }
        
        $this->logaUsuario($usuario);
        
        return true;
    }
    
    public function logout(): void {
        session()->destroy();
    }
    
    public function pegaUsuarioLogado() {
        if($this->usuario === null){
            $this->usuario = $this->pegaUsuarioDaSessao();
        }
        
        return $this->usuario;
    }
    
    public function estaLogado(): bool {
        return $this->pegaUsuarioLogado() !== null;
    }
    
    private function logaUsuario(object $usuario): void {
        $session = session();
        
        $session->regenerate();
        
        $session->set('usuario_id', $usuario->id);
    }
    
    private function pegaUsuarioDaSessao() {
        if(session()->has('usuario_id') == false){
            return null;
        }
        
        $usuario = $this->usuarioModel->find(session()->get('usuario_id'));
        
        if($usuario == null || $usuario->ativo == false){
            return null;
        }
        
        $usuario = $this->definePermissoesDoUsuarioLogado($usuario);
        
        return $usuario;
    }
    
    private function isAdmin(): bool {
        $grupoAdmin = 1;
        
        $administrador = $this->grupoUsuarioModel->usuarioEstaNoGrupo($grupoAdmin, session()->get('usuario_id'));
        
        if($administrador == null){
            
            return false;
        }
        
        return true;
    }
    
    private function isCliente(): bool {
        $grupoCliente = 2;
        
        $cliente = $this->grupoUsuarioModel->usuarioEstaNoGrupo($grupoCliente, session()->get('usuario_id'));
        
        if($cliente == null){
            
            return false;
        }
        
        return true;
    }
    
    private function definePermissoesDoUsuarioLogado(object $usuario): object{
        $usuario->is_admin = $this->isAdmin();
        
        if($usuario->is_admin == true){
            $usuario->is_cliente = false;
        }else{
            $usuario->is_cliente = $this->isCliente();
        }
        
        if($usuario->is_admin == false && $usuario->is_cliente == false){
            $usuario->permissoes = $this->recuperaPermissoesDoUsuarioLogado();
        }
        
        return $usuario;
    }
    
    private function recuperaPermissoesDoUsuarioLogado(): array {
        $permissoesDoUsuario = $this->usuarioModel->recuperaPermissoesDoUsuarioLogado(session()->get('usuario_id'));
        
        return array_column($permissoesDoUsuario, 'permissao');
    }
}
