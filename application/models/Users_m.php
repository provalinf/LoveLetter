<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Users_m extends CI_Model {

	public function add_user($donnees) {
		$data = array(
			'Login'              => $donnees['login'], 'mot_de_passe' => $donnees['pass']
		);

		return $this->db->insert('Utilisateur', $data);
	}

	/*public function update_user($id, $donnees) {
		$this->db->where("id_user", $id);
		return $this->db->update("Utiisateur", $donnees);
	}*/

	public function verif_connexion($donnees) {
		$this->db->from("Utilisateur");
		$this->db->where('login', $donnees['login']);
		$query = $this->db->get();

		return (!empty($query)) ? $query->row_array() : false;
	}


}