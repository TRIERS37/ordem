<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Usuario extends Entity {

    protected $dates = [
        'criado_em',
        'atualizado_em',
        'deletado_em'
    ];

    public function exibeSituacao() {
        if($this->deletado_em != null) {
            //Usuario excluido
            
            $icone = '<span class="text-white">Excluído</span>&nbsp;<i class="fa fa-undo"></i>&nbsp;Desfazer';
            
            $situacao = anchor("usuarios/desfazerexclusao/$this->id", $icone, ['class' => 'btn btn-outline-succes btn-sm']);
            
            return $situacao;
            
        }
        
        if($this->ativo == true){
            //($usuario->ativo == true ? '<i class="fa fa-lock text-success"></i>&nbsp;Ativo' : '<i class="fa fa-lock text-warning"></i>&nbsp;Inativo'),
            
            return '<i class="fa fa-unlock text-success"></i>&nbsp;Ativo';
            
        }
        
        if($this->ativo == false){
            //($usuario->ativo == true ? '<i class="fa fa-lock text-success"></i>&nbsp;Ativo' : '<i class="fa fa-lock text-warning"></i>&nbsp;Inativo'),
            
            return '<i class="fa fa-lock text-warning"></i>&nbsp;Inativo';
            
        }
    }
    
    public function verificaPassword(string $password): bool {
        return password_verify($password, $this->password_hash);
    }
    
    /**
     * Método que valida se o usuario logado tem permissão para visualizar / acessar determinada rota
     * @param string $permissao
     * @return bool
     */
    public function temPermissaoPara(string $permissao): bool {
        if($this->is_admin == true){
            return true;
        }
        
        if(empty($this->permissoes)){
            return false;
        }
        
        if(in_array($permissao, $this->permissoes) == false){
            return false;
        }
        
        return true;
    }

}
