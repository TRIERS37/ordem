<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Grupo extends Entity {

    protected $dates = [
        'criado_em',
        'atualizado_em',
        'deletado_em'
    ];

    public function exibeSituacao() {
        if ($this->deletado_em != null) {
            //Usuario excluido

            $icone = '<span class="text-white">Excluído</span>&nbsp;<i class="fa fa-undo"></i>&nbsp;Desfazer';

            $situacao = anchor("grupos/desfazerexclusao/$this->id", $icone, ['class' => 'btn btn-outline-succes btn-sm']);

            return $situacao;
        }

        if ($this->exibir == true) {


            return '<i class="fa fa-eye text-secundary"></i>&nbsp;Exibir grupo';
        }

        if ($this->exibir == false) {


            return '<i class="fa fa-eye-slash text-danger"></i>&nbsp;Não exibir grupo';
        }
    }

}
