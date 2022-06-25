<?php

namespace App\Controllers;
use App\Libraries\Autenticacao;

class Home extends BaseController
{
    public function index()
    {
        $data = [
            'titulo' => 'Home',
        ];
        return view('Home/index', $data);
    }
    
   // public function login() {
       // $autenticacao = service('autenticacao');
        //$autenticacao->login('vcremin@hotmail.com', '123456');
       // $autenticacao->login('paulotriers.29@gmail.com', '123456');
       // $usuario = $autenticacao->pegaUsuarioLogado();
        //dd($usuario);
        //$autenticacao->logout();
       // return redirect()->to(site_url('/'));
   // }
}
