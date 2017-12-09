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
		$this->twig->addGlobal('globlogin', $this->session->userdata('login'));
		$this->twig->addGlobal('globpartie', $this->session->userdata('id_partie'));
		$this->check_isConnected();
	}

	private function check_isConnected() {
		if (empty($this->session->userdata('login'))) redirect('Users_c/connexion');
	}

	private function check_isPartieSelected() {
		if (!isset($this->session->userdata()['id_partie']) && !$this->session->userdata('id_partie')) redirect(base_url());
	}

	public function creerPartie($donnees = array()) {
		$this->twig->display('Partie/creer', array_merge($donnees, ['titre' => "Créer une partie"]));
	}

	public function rejoindrePartie() {
		$this->twig->display('Partie/rejoindre', ['titre' => "Rejoindre une partie"]);
	}


	public function validFormCreerPartie() {
		$this->form_validation->set_rules('name', 'Nom', 'trim|required|min_length[2]|max_length[20]');
		$this->form_validation->set_rules('nb_joueurs', 'Nombre de joueurs', 'trim|required|min[2]|max[4]');

		$this->form_validation->set_error_delimiters('<small class="form-text text-muted">', '</small>');

		$donnees = array(
			'name' => $this->input->post('name'), 'nb_joueurs' => $this->input->post('nb_joueurs')
		);

		if ($this->form_validation->run() == False) {
			$this->creerPartie($donnees);
		} else {
			if (($id_partie = $this->Game_m->createPartie($donnees)) != false) {
				$this->session->set_userdata(['id_partie' => $id_partie]);
				redirect('Game_c/partie');
			}
		}
	}

	public function partie() {
		$this->check_isPartieSelected();
		$nom_partie = $this->Game_m->getNomPartie($this->session->userdata('id_partie'));
		$this->twig->display('Partie/partie', ['titre' => "Partie \"$nom_partie\" en cours ", 'name' => $nom_partie]);
	}

	public function refreshTimeConnect() {
		echo json_encode($this->Game_m->refreshActualisationConnexionJoueur($this->session->userdata()['login'], $this->session->userdata()['id_partie']));
	}
}