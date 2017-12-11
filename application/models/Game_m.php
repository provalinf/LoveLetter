<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Game_m extends CI_Model {

	public function fillGameList() {
		$this->db->select("nom_partie");
		$this->db->from("Partie");
		$query = $this->db->get();

		return $query->result_array();
	}

	public function getCurrentManche($id_partie) {
		$this->db->select("num_manche");
		$this->db->from("Manche");
		$this->db->where("id_partie", $id_partie);
		$this->db->order_by("num_manche", "DESC");
		$this->db->limit(1);
		$query = $this->db->get();
		return $query->row()->num_manche;
	}

	public function getScore() {
		$this->db->from("Score_manche");
		$query = $this->db->get();
		return $query->result_array();
	}

	public function createPartie($donnees) {
		$donnees = array_merge($donnees, ['nom_partie' => $donnees['name']]);
		unset($donnees['name']);
		$result = $this->db->insert("Partie", $donnees);
		return ($result) ? $this->db->insert_id() : false;
	}

	public function initManche($donnees) {
		$result = $this->db->insert("Manche", $donnees);
		return ($result) ? $this->db->insert_id() /*$donnees['num_manche']*/ : false;
	}

	public function getMainJoueur($id_joueur, $num_manche) {
		$this->db->from("Main as m");
		$this->db->join("Carte as c", "c.id_carte=m.id_carte");
		$this->db->where("login", $id_joueur);
		$this->db->where("num_manche", $num_manche);
		$query = $this->db->get();
		return $query->result_array();
	}

	public function getJoueursPartie($id_partie) {
		$this->db->select("login, id_partie, temps_actualisation, dernier_coup, joue");
		$this->db->from("Joueur");
		$this->db->where("id_partie", $id_partie);
		$this->db->order_by("login", "ASC");
		$query = $this->db->get();
		return $query->result_array();
	}

	public function defineJoueurSuivant($id_joueur, $id_partie) {
		$result = $this->db->update("Joueur", ['joue' => false], ['id_partie' => $id_partie]);
		return $result && $this->db->update("Joueur", ['joue' => true], [
				'id_partie' => $id_partie, 'login' => $id_joueur
			]);
	}

	public function joueurIsInPartie($id_joueur, $id_partie) {
		$this->db->from("Joueur");
		$this->db->where('login', $id_joueur);
		$this->db->where('id_partie', $id_partie);
		$query = $this->db->get();
		return $query->num_rows() != 0;
	}

	public function addPlayerToPartie($id_joueur, $id_partie) {
		return $this->db->insert("Joueur", ["login" => $id_joueur, "id_partie" => $id_partie]);
	}

	public function refreshActualisationConnexionJoueur($id_joueur, $id_partie) {
		if (!$this->joueurIsInPartie($id_joueur, $id_partie)) {
			if (!$this->AllPlayersInscrits($id_partie)) {
				$this->addPlayerToPartie($id_joueur, $id_partie);
			} else {
				return false;
			}
		}
		$this->db->where(['id_partie' => $id_partie, 'login' => $id_joueur]);
		$this->db->set('temps_actualisation', "CURRENT_TIMESTAMP", false);
		return $this->db->update("Joueur");
	}

	public function getNomPartie($id_partie) {
		$this->db->select("nom_partie");
		$this->db->from("Partie");
		$this->db->where("id_partie", $id_partie);
		$query = $this->db->get();
		return $query->row_array()['nom_partie'];
	}

	public function AllMainIsEmpty($id_partie, $id_manche) {
		$this->db->from("Main");
		$this->db->where("id_partie", $id_partie);
		$this->db->where("num_manche", $id_manche);
		$query = $this->db->get();
		return $query->num_rows() == 0;
	}


	public function getWhatPlay($id_partie, $id_joueur) {
		$this->db->from("Joueur");
		$this->db->where("id_partie", $id_partie);
		$this->db->where("login", $id_joueur);
		$this->db->where("joue", true);
		$query = $this->db->get();
		return $query->num_rows() != 0;
	}

	public function PartieIsFinished($id_partie) {
		$this->db->from("Partie");
		$this->db->where('id_partie', $id_partie);
		$this->db->where('finie', true);
		$query = $this->db->get();
		return $query->num_rows() != 0;
	}

	public function getPartiesDispo() {
		$this->db->select("id_partie, nom_partie, date_creation, nb_joueurs");
		$this->db->from("Partie");
		$this->db->where("finie", false);
		$this->db->order_by("date_creation", "DESC");
		$query   = $this->db->get();
		$parties = array();
		foreach ($query->result_array() as $partie) {
			if (!$this->AllPlayersInscrits($partie['id_partie'])) {
				$parties[] = $partie;
			}
		}
		return $parties;
	}

	public function AllPlayersInscrits($id_partie) {
		$joueurs = $this->getJoueursPartie($id_partie);
		$this->db->select("nb_joueurs");
		$this->db->from("Partie");
		$query = $this->db->get();
		if (count($joueurs) != $query->row()->nb_joueurs) return false;

		$this->db->from("Joueur");
		$this->db->where("id_partie", $id_partie);
		$query2 = $this->db->get();
		return $query2->num_rows() == $query->row()->nb_joueurs;
	}

	public function AllPlayersConnected($id_partie) {
		$joueurs = $this->getJoueursPartie($id_partie);
		$this->db->select("nb_joueurs");
		$this->db->from("Partie");
		$query = $this->db->get();
		if (count($joueurs) != $query->row()->nb_joueurs) return false;

		$this->db->from("Joueur");
		$this->db->where("temps_actualisation <= DATE_ADD(NOW(), INTERVAL 15 SECOND)");
		$this->db->where("id_partie", $id_partie);
		$query2 = $this->db->get();
		return $query2->num_rows() == $query->row()->nb_joueurs;
	}

	public function getNbManches($id_partie) {
		$this->db->from("Manche");
		$this->db->where("id_partie", $id_partie);
		$query = $this->db->get();
		return $query->num_rows();
	}

	public function defausse($id_carte, $id_joueur, $id_partie) {
		$this->db->where("id_carte", $id_carte);
		$this->db->where("login", $id_joueur);
		$this->db->where("num_manche", $this->getCurrentManche($id_partie));
		$this->db->delete("main");

		$donnees = [
			'login'    => $id_joueur, 'num_manche' => $this->getCurrentManche($id_partie), 'id_partie' => $id_partie,
			'id_carte' => $id_carte
		];
		return $this->db->insert("defausse", $donnees);

	}

	public function pioche($id_joueur, $num_manche) {
		$this->db->select("id_carte");
		$this->db->from("Est_disponible");
		$this->db->where("num_manche", $num_manche);
		$this->db->order_by("rand()");
		$this->db->limit(1);
		$query    = $this->db->get();
		$id_carte = $query->row()->id_carte;
		echo $id_carte;
		/*print_r($query);
		print_r($query->row_array());*/
		$this->db->insert("Main", [
			'login' => $id_joueur, 'id_carte' => $id_carte, 'num_manche' => $num_manche
		]);

		$this->db->where("num_manche", $num_manche);
		$this->db->where("id_carte", $id_carte);
		$this->db->limit(1);
		$this->db->delete("est_disponible");
	}

	public function getJoueurPlusPetiteCarte($id_adversaire, $id_joueur, $id_partie)
	{
		$this->db->select("login");
		$this->db->from("main");
		$this->db->where("num_manche", $this->getCurrentManche($id_partie));
		$this->db->where("login", $id_adversaire);
		$this->db->or_where("login", $id_joueur);
		$this->db->order_by("id_carte", "ASC");
		$this->db->limit(1);
		$query = $this->db->get();
		return $query->result_array();
	}

	public function echangeMain_m($id_adversaire, $id_joueur, $id_partie)
	{
		$this->db->select("id_carte");
		$this->db->from("main");
		$this->db->where("num_manche", $this->getCurrentManche($id_partie));
		$this->db->where("login", $id_adversaire);
		$carteAdverse = $this->db->get();

		$this->db->set('login', $id_joueur, FALSE);
		$this->db->where("num_manche", $this->getCurrentManche($id_partie));
		$this->db->where("login", $id_adversaire);
		$this->db->update('main');

		$this->db->set('login', $id_joueur, FALSE);
		$this->db->where("num_manche", $this->getCurrentManche($id_partie));
		$this->db->where("login", $id_joueur);
		$this->db->where("id_carte", $carteAdverse);
		$this->db->update('main');
	}

	public function getDefausseJoueur($id_joueur, $num_manche) {
		$this->db->select("id_carte");
		$this->db->from("Defausse");
		$this->db->where("login", $id_joueur);
		$this->db->where("num_manche", $num_manche);
		$query    = $this->db->get();
		return $query->result_array();
	}

	public function devineCarte($id_adversaire, $id_carte_devinee, $num_manche) {
		$this->db->from('Main');
		$this->db->where("login", $id_adversaire);
		$this->db->where("num_manche", $num_manche);
		$this->db->where("id_carte", $id_carte_devinee);

		$query = $this->db->get();
		return $query->num_rows() != 0;
	}

	public function deleteMain($id_adversaire, $num_manche) {
		$this->db->where("login", $id_adversaire);
		$this->db->where("num_manche", $num_manche);
		return $this->db->delete("Main");
	}

	private function piocheIsEmpty($num_manche) {
		$this->db->from("Est_disponible");
		$this->db->where('num_manche', $num_manche);
		$query = $this->db->get();
		return $query->num_rows() == 0;
	}

	public function check_isFirstMasterPlayer($id_partie, $id_joueur) {
		$this->db->select("login");
		$this->db->from("Joueur");
		$this->db->where("id_partie", $id_partie);
		$this->db->order_by("login", "ASC");
		$this->db->limit(1);
		$query = $this->db->get();
		return $query->row()->login == $id_joueur;
	}

	public function MancheIsFinished($id_partie, $id_manche) {
		$this->db->from("Est_disponible");
		$this->db->where("num_manche", $id_manche);
		$query = $this->db->get();

		$joueurs = $this->getJoueursPartie($id_partie);
		$this->db->from("Main as m");
		$this->db->join("Joueur as j", "j.login=m.login");
		$this->db->where("j.id_partie", $id_partie);
		$this->db->where("m.num_manche", $id_manche);
		$query2 = $this->db->get();

		$this->db->from("Joueur");
		$this->db->where("id_partie", $id_partie);
		$this->db->where("joue", true);
		$query3 = $this->db->get();

		return $query3->num_rows() == 0 && ($query->num_rows() == 0 || $query2->num_rows() == 1);
	}

	public function defineScoresJoueurs($joueurs, $num_current_manche) {
		foreach ($joueurs as $joueur) {
			$this->db->select("c.valeur");
			$this->db->from("Main as m");
			$this->db->join("Carte as c", "c.id_carte=m.id_carte");
			$this->db->where("m.num_manche", $num_current_manche);
			$this->db->where("m.login", $joueur['login']);
			$query = $this->db->get();

			if ($query->num_rows() != 0) $score = $query->row_array()["c.valeur"]; else $score = 0;

			$this->db->insert("Score_manche", [
				'login' => $joueur['login'], 'num_manche' => $num_current_manche, 'score' => $score
			]);
		}
	}

	public function definePartieFinished($id_partie) {
		$this->db->where("id_partie", $id_partie);
		return $this->db->update("Partie", ['finie' => true]);
	}

	public function getIdWinnerPrevManche($id_partie) {
		$this->db->from("Score_manche as sm");
		$this->db->join("manche as m", "m.num_manche=sm.num_manche");
		$this->db->where("m.id_partie", $id_partie);
		$this->db->group_by("sm.num_manche");
		$this->db->order_by("sm.score DESC, sm.num_manche DESC");
		$this->db->limit(1,1);
		$query = $this->db->get();
		return $query->row()->login;
	}

	public function getIdWinnerManche($num_manche) {
		$this->db->from("Score_manche");
		$this->db->where("num_manche", $num_manche);
		$this->db->order_by("score", 'DESC');
		$this->db->limit(1);
		$query = $this->db->get();
		return $query->row()->login;
	}

	public function getWinner($id_partie) {
		$this->db->select("num_manche");
		$this->db->from("Manche");
		$this->db->where("id_partie");
		$query = $this->db->get();
		if ($query->num_rows() == 0) return false;
		$joueurs = array();
		foreach ($query->result_array() as $num_manche) ++$joueurs[$this->getIdWinnerManche($num_manche)];
		arsort($joueurs);
		return array_keys($joueurs)[0];
	}

	public function InitPioche($id_partie, $num_init_manche) {
		$this->db->select("id_carte, nb_cartes");
		$this->db->from("Carte");
		$query  = $this->db->get();
		$jeu_cartes = array();
		foreach ($query->result_array() as $carte) for ($i = 0; $i < (int)$carte['nb_cartes']; $i++)
			$jeu_cartes[] = array(
			'num_manche' => $num_init_manche, 'id_carte' => $carte['id_carte']
		);
		return $this->db->insert_batch("Est_disponible", $jeu_cartes);
	}

	public function InitMainJoueurs($id_partie, $num_init_manche) {
		$joueurs = $this->getJoueursPartie($id_partie);
		foreach ($joueurs as $joueur) $this->pioche($joueur['login'], $num_init_manche);
	}

	public function getAdvers($id_partie, $num_manche, $id_joueur) {
		$this->db->from("Main as m");
		$this->db->join("Joueur as j", "j.login=m.login");
		$this->db->where("j.id_partie", $id_partie);
		$this->db->where("m.num_manche", $num_manche);
		$this->db->where_not_in("j.login", $id_joueur);
		$query = $this->db->get();
		return $query->result_array();
	}

	/*public function getAdvers($id_partie, $id_joueur) {

		$this->db->select("login");
		$this->db->from("joueur");
		$this->db->where("id_partie", $id_partie);
		$this->db->where_not_in("login", $id_joueur);
		$query = $this->db->get();
		return $query->result_array();
	}*/

	public function getCartesSansG() {
		$this->db->select("id_carte, image");
		$this->db->from("Carte");
		$this->db->where_not_in("id_carte", 1);
		$query = $this->db->get();
		return $query->result_array();
	}

	public function voirMain_m($id_joueur) {
		$this->db->select("image");
		$this->db->from("main");
		$this->db->where("login", $id_joueur);
		$query = $this->db->get();
		return $query->result_array();
	}

}