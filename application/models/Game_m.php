<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Game_m extends CI_Model {

	public function fillGameList() {
		$this->db->select("nom_partie");
		$this->db->from("Partie");
		$query = $this->db->get();

		return $query->result_array();
	}

	public function getScore()
	{
		$this->db->select("*");
		$this->db->from("score_manche");
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
		return ($result) ? $this->db->insert_id() : false;
	}

	public function getMainJoueur($id_joueur, $manche) {
		$this->db->from("Main");
		$this->db->where("login", $id_joueur);
		$query = $this->db->get();
		return $query->result_array();
	}

	public function getJoueursPartie($id_partie) {
		$this->db->from("Joueur");
		$this->db->where("id_partie", $id_partie);
		$this->db->order_by("login", "ASC");
		$query = $this->db->get();
		return $query->result_array();
	}

	public function defineJoueurSuivant($id_joueur, $id_partie) {
		$result = $this->db->update("Joueur", ['joue' => false], ['ip_partie' => $id_partie]);
		return $result && $this->db->update("Joueur", ['joue' => true], [
				'ip_partie' => $id_partie, 'id_joueur' => $id_joueur
			]);
	}

	public function joueurIsInPartie($id_joueur, $id_partie) {
		$this->db->from("Joueur");
		$this->db->where('login', $id_joueur);
		$this->db->where('id_partie', $id_partie);
		$query = $this->db->get();
		return $query->num_rows() != 0;
	}

	public function refreshActualisationConnexionJoueur($id_joueur, $id_partie) {
		if (!$this->joueurIsInPartie($id_joueur, $id_partie)) return false;
		$this->db->set('temps_actualisation', "CURRENT_TIMESTAMP", false);
		return $this->db->update("Joueur", ['id_partie' => $id_partie, 'login' => $id_joueur]);
	}

	public function getNomPartie($id_partie) {
		$this->db->select("nom_partie");
		$this->db->from("Partie");
		$this->db->where("id_partie", $id_partie);
		$query = $this->db->get();
		return $query->row_array()['nom_partie'];
	}


	public function getWhatPlay($id_partie) {

	}

}