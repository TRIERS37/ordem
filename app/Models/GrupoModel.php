<?php

namespace App\Models;

use CodeIgniter\Model;

class GrupoModel extends Model {

    protected $table = 'grupos';
    protected $returnType = 'App\Entities\Grupo';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['nome', 'descricao', 'exibir',];
    // Dates
    protected $useTimestamps = true;
    protected $createdField = 'criado_em';
    protected $updatedField = 'atualizado_em';
    protected $deletedField = 'deletado_em';
    
    
    // Validation
    protected $validationRules = [
        
        'nome' => 'required|max_length[128]|is_unique[grupos.nome,id,{id}]',
        'descricao' => 'required|max_length[240]'
        
    ];
    protected $validationMessages = [
        'nome' => [
            'required' => 'O campo nome é obrigatório.',
            'is_unique' => 'Já existe um grupo com esse nome, por favor escolha outro nome.'
        ],
        
    ];

}
