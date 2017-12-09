<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class General_c extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->helper('url');
		$this->load->library(array('session', 'Twig'));
		$this->load->model('General_m');
		$this->twig->addGlobal('globlogin', $this->session->userdata('login'));

	}


    public function index(){
        $this->twig->display('welcome', ['titre' => "Bienvenue"]);
    }

    public function aide(){
        $this->twig->display('help', ['titre' => "Aide"]);
    }
}
