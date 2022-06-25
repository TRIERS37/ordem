<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model {

    protected $table = 'usuarios';
    protected $returnType = '\App\Entities\Usuario';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'nome',
        'email',
        'password',
        'reset_hash',
        'reset_expira_em',
        'imagem',
    ];
    // Dates
    protected $useTimestamps = true;
    protected $createdField = 'criado_em';
    protected $updatedField = 'atualizado_em';
    protected $deletedField = 'deletado_em';
    // Validation
    protected $validationRules = [
        'nome' => 'required|min_length[3]|max_length[125]',
        'email' => 'required|valid_email|max_length[230]|is_unique[usuarios.email,id,{id}]',
        'password' => 'required|min_length[6]',
        'password_confirmation' => 'required_with[password]|matches[password]',
    ];
    protected $validationMessages = [
        'nome' => [
            'required' => 'O campo nome é obrigatório.',
            'min_length' => 'O campo nome precisa ter pelo menos 3 caractéres.',
            'max_length' => 'O campo nome não pode ter mais que 125 caractéres.',
        ],
        'email' => [
            'required' => 'O campo E-mail é obrigatório.',            
            'max_length' => 'O campo E-mail não pode ter mais que 230 caractéres.',
            'is_unique' => 'Esse E-mail já existe!',
        ],
        'password_confirmation' => [
            'required_with' => 'Por favor confirme sua senha.',            
            'matches' => 'As senhas precisam combinar.',
        ],
    ];
    // Callbacks

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data) {
        if (isset($data['data']['password'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            unset($data['data']['password']);
            unset($data['data']['password_confirmation']);
        }



        return $data;
    }
    
    public function buscaUsuarioPorEmail(string $email) {
        
        return $this->where('email', $email)->where('deletado_em', null)->first();
        
    }
    
    public function recuperaPermissoesDoUsuarioLogado(int $usuario_id) {
        $atributos = [
            //'usuarios.id',
            //'usuarios.nome as usuario',
           // 'grupos_usuarios.*',
            'permissoes.nome as permissao',
        ];
        
        return $this->select($atributos)
                ->asArray()
                ->join('grupos_usuarios', 'grupos_usuarios.usuario_id = usuarios.id')
                ->join('grupos_permissoes', 'grupos_permissoes.grupo_id = grupos_usuarios.grupo_id')
                ->join('permissoes', 'permissoes.id = grupos_permissoes.permissao_id')
                ->where('usuarios.id', $usuario_id)
                ->groupBy('permissoes.nome')
                ->findAll();
    }

}
