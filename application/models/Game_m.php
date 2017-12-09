<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Game_m extends CI_Model {

	public function fillGameList() {
		$this->db->select("NOM_PARTIE");
		$this->db->from("PARTIE");
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

}