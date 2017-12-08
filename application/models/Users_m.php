<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Users_m extends CI_Model {

	public function add_user($donnees) {
		$data = array(
			'PSEUDO'              => $donnees['PSEUDO'], 'mot_de_passe' => $donnees['pass'],
			'derniere_connexion' => $donnees['last_connect']
		);

		$this->db->insert('utilisateur', $data);
	}

	public function update_user($id, $donnees) {
		$this->db->where("id_user", $id);
		$this->db->update("user", $donnees);
	}

	public function verif_connexion($donnees) {
		$this->db->select("login");
		$this->db->from("utilisateur");
		$this->db->where('login', $donnees['login']);
		$this->db->where('mot_de_passe', $donnees['password']);
		$query = $this->db->get();

		return (!empty($query)) ? $query->row_array() : false;
	}


    public function getScore()
    {
        $this->db->select("*");
        $this->db->from("score_manche");
        $query = $this->db->get();

        return $query->result_array();
    }


}