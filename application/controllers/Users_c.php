<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Users_c extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->library('twig');
		$this->load->helper(array('form', 'url', 'cookie'));
		$this->load->library(array('session', 'form_validation', 'encryption'));
		$this->load->model('Users_m');
		$this->twig->addGlobal('globlogin', $this->session->userdata('login'));
		$this->loadCookies();
	}

	private function loadCookies() {
		if (!empty($this->input->cookie('login')) && empty($this->session->userdata('login'))) {
			$this->session->set_userdata('login', $this->input->cookie('login'));
		}
		if (!empty($this->input->cookie('id_partie')) && empty($this->session->userdata('id_partie'))) {
			$this->session->set_userdata('id_partie', $this->input->cookie('id_partie'));
		}

		if ($this->input->cookie('login') != $this->session->userdata('login')) {
			$this->input->set_cookie("login", $this->session->userdata('login'));
		}
		if ($this->input->cookie('id_partie') != $this->session->userdata('id_partie')) {
			$this->input->set_cookie("id_partie", $this->session->userdata('id_partie'));
		}
	}

	private function check_isConnected() {
		if (!empty($this->session->userdata('login'))) redirect(base_url());
	}

	public function connexion($donnees = array()) {
		$this->check_isConnected();
		$this->twig->display('Users/form_connexion', array_merge($donnees, ['titre' => "Page de connexion"]));
	}

	public function form_valid_connexion() {
		$this->check_isConnected();

		$this->form_validation->set_rules('login', 'Login', 'trim|required');
		$this->form_validation->set_rules('password', 'Mot de passe', 'trim|required');

		$this->form_validation->set_error_delimiters('<small class="form-text text-muted">', '</small>');

		$donnees = array(
			'login' => $this->input->post('login'), 'password' => $this->input->post('password')
		);
		if ($this->form_validation->run() == False) {
			$this->connexion($donnees);
		} else {
			if (($donnees_session = $this->Users_m->verif_connexion($donnees)) != False && $this->encryption->decrypt($donnees_session['MOT_DE_PASSE']) == $donnees['password']) {
				$this->session->set_userdata(['login' => $donnees_session['LOGIN']]);
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
		$this->form_validation->set_rules('login', 'Pseudo', 'trim|required|is_unique[UTILISATEUR.LOGIN]|min_length[4]|max_length[18]');
		$this->form_validation->set_rules('pass', 'Mot de passe', 'trim|required|min_length[6]|max_length[12]');
		$this->form_validation->set_rules('pass2', 'Confirmation de mot de passe', 'trim|required|matches[pass]');

		$this->form_validation->set_error_delimiters('<small class="form-text text-muted">', '</small>');

		$donnees = array(
			'login' => $this->input->post('login'), 'pass' => $this->input->post('pass')
		);

		if ($this->form_validation->run() == False) {
			$this->inscription($donnees);
		} else {
			$donnees['pass'] = $this->encryption->encrypt($donnees['pass']);
			$this->Users_m->add_user($donnees);
			redirect(base_url());
		}
	}



}