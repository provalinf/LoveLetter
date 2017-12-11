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

	public function joueurCompare($id_adversaire) {
		/* ... */
		$cartes_adversaire = array();
		echo json_encode($cartes_adversaire);
	}

	public function joueurCompare2() {
		/* ... */
		$id_adversaire     = json_decode(stripslashes(file_get_contents("php://input")));
		$cartes_adversaire = array();
		echo json_encode($cartes_adversaire);
	}

	public function play() {
		$this->check_isPartieSelected();
		$id_joueur = $this->session->userdata('login');
		$id_partie = $this->session->userdata('id_partie');

		$nb_manche_par_joueurs = array(2 => 7, 3 => 5, 4 => 4);

		if ($this->Game_m->PartieIsFinished($id_partie)) {
			echo json_encode("Partie terminée. Le gagnant est {$this->Game_m->getWinner($id_partie)}");
			return;
		}

		if (!$this->Game_m->AllPlayersConnected($id_partie)) {
			echo json_encode("En attente de joueur");
			return;
		}

		$nb_manche = $this->Game_m->getNbManches($id_partie);
		if ($nb_manche != 0) {
			$num_current_manche = $this->Game_m->getCurrentManche($id_partie);
		}

		if ($this->Game_m->check_isFirstMasterPlayer($id_partie, $id_joueur)) {
			if ($nb_manche == 0 || $this->Game_m->MancheIsFinished($id_partie, $num_current_manche)) {
				$joueurs = $this->Game_m->getJoueursPartie($id_partie);

				if ($this->Game_m->MancheIsFinished($id_partie, $num_current_manche)) {
					$this->Game_m->defineScoresJoueurs($joueurs, $num_current_manche);
				}

				if ($nb_manche < $nb_manche_par_joueurs[$nb_manche]) {
					$num_init_manche = $this->Game_m->initManche([
						'num_manche' => $nb_manche + 1, 'id_partie' => $id_partie
					]);
					$this->Game_m->InitPioche($id_partie, $num_init_manche);
					$this->Game_m->InitMainJoueurs($num_init_manche);

					if ($num_init_manche == 1) {
						$this->Game_m->defineJoueurSuivant($joueurs[0]['login'], $id_partie);
					} else {
						$joueur_suivant = $this->Game_m->getIdWinnerManche($nb_manche);
						$this->Game_m->defineJoueurSuivant($joueur_suivant, $id_partie);
					}
					$num_current_manche = $num_init_manche;    // Facultatif
				} else {
					$this->Game_m->definePartieFinished($id_partie);
				}
			}
		}

		if (empty($num_current_manche)) {
			echo json_encode("En attente d'une nouvelle manche");
			return;
		}

		if ($this->Game_m->MancheIsFinished($id_partie, $num_current_manche)) {
			echo json_encode("Manche terminée");
			return;
		}

	}

	public function refreshTimeConnect() {
		echo json_encode($this->Game_m->refreshActualisationConnexionJoueur($this->session->userdata('login'), $this->session->userdata('id_partie')));
	}

	public function score() {
		$this->twig->display('score', ['titre' => "Scores", 'liste_score' => $this->Game_m->getScore()]);
	}

	public function defausser($id_carte, $id_joueur) {
		$this->check_isConnected();
		echo json_encode($this->Game_m->defausse($id_carte, $id_joueur, $this->session->userdata('id_partie')));
	}

	public function piocher($id_joueur, $id_pioche) {
		$this->check_isConnected();
		echo json_encode($this->Game_m->pioche($id_joueur, $id_pioche, $this->session->userdata('id_partie')));
	}

	public function getAdversaires(){
        $this->check_isConnected();
        echo json_encode($this->Game_m->getAdvers($this->session->userdata('id_partie'), $this->session->userdata('login')));
    }

    public function getCartesSansGarde(){
        $this->check_isConnected();
        echo json_encode($this->Game_m->getCartesSansG());
    }

    public function voirMain($id_joueur){
        $this->check_isConnected();
        echo json_encode($this->Game_m->voirMain_m($id_joueur));
    }
}