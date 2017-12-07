<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Game_c extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('twig');
        $this->load->helper(array('form', 'url'));
        $this->load->library(array('session', 'form_validation', 'encryption'));
        $this->load->model('Game_m');
    }

    public function index()
    {
        echo "Bienvenue sur game";
    }

    public function afficherCarte($donnees = array()) {
        $this->twig->display('game', array_merge($donnees, ['titre' => "Carte Prince"]));
    }

    public function form_valid_connexion() {
        $this->check_isConnected();

        $this->form_validation->set_rules('login', 'Login', 'trim|required');
        $this->form_validation->set_rules('pass', 'Mot de passe', 'trim|required');

        $this->form_validation->set_error_delimiters('<span class="error">', '</span>');

        $donnees = array(
            'login' => $this->input->post('login'), 'password' => $this->encrypt->encode($this->input->post('pass'))
        );
        if ($this->form_validation->run() == False) {
            $this->twig->display('form_connexion', $donnees);
        } else {
            if (($donnees_session = $this->Users_m->verif_connexion($donnees)) != False) {
                $this->session->set_userdata($donnees_session);
                redirect(base_url());
            } else {
                $donnees['erreur'] = "Pseudo ou mot de passe incorrect";
                $this->connexion($donnees);
            }
        }
    }

    public function deconnexion() {
        $this->session->sess_destroy();
        redirect(base_url());
    }

    public function inscription($donnees = array()) {
        $this->check_isConnected();
        $this->twig->display('Users/form_inscription', array_merge($donnees, ['titre' => "Page d'inscription"]));
    }

    public function validFormInscription() {
        $this->check_isConnected();
        $this->form_validation->set_rules('login', 'Pseudo', 'trim|required|is_unique[UTILISATEUR.PSEUDO]|min_length[4]|max_length[8]');
        $this->form_validation->set_rules('pass', 'Mot de passe', 'trim|required|min_length[6]|max_length[12]');
        $this->form_validation->set_rules('pass2', 'Confirmation de mot de passe', 'trim|required|matches[pass]');

        $this->form_validation->set_error_delimiters('<span class="error">', '</span>');

        $donnees = array(
            'login' => $this->input->post('login'), 'pass' => $this->input->post('pass')
        );

        if ($this->form_validation->run() == False) {
            $this->inscription($donnees);
        } else {
            $donnees['pass'] = $this->encrypt->encode($donnees['pass']);
            $this->Users_m->add_user($donnees);
            redirect(base_url());
        }
    }

}