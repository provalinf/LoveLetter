<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property  Game_m
 */
class Game_c extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('twig');
        $this->load->helper(array('form', 'url'));
        $this->load->library(array('session', 'form_validation', 'encryption'));
        $this->load->model('Game_m');
    }

    private function check_isConnected() {
        if (!empty($this->session->userdata('login'))) redirect(base_url());
    }

    public function commencer() {
        $this->check_isConnected();
        $this->Game_m->fillGameList();
        $this->twig->display('game', ['titre' => "Commencement"]);
    }

    public function rejoindrePartie(){
        $this->check_isConnected();

    }

    public function validFormCreerPartie() {
        $this->check_isConnected();
        $this->form_validation->set_rules('Nom', 'Nom', 'trim|required|min_length[2]|max_length[20]');
        $this->form_validation->set_rules('NombreJoueurs', 'NombreJoueurs', 'trim|required|min_length[2]|max_length[4]');

        $this->form_validation->set_error_delimiters('<span class="error">', '</span>');

        $donnees = array(
            'NombreJoueurs' => $this->input->post('NombreJoueurs'), 'Nom' => $this->input->post('Nom')
        );

        if ($this->form_validation->run() == False) {
            $this->commencer();
        } else {
            //$donnees['pass'] = $this->encrypt->encode($donnees['pass']);
            //$this->Users_m->add_user($donnees);
            redirect(base_url());
        }
    }

    public function deconnexion() {
        $this->session->sess_destroy();
        redirect(base_url());
    }

}