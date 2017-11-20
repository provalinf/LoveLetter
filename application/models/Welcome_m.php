<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class welcome_m extends CI_Model {

	public function test() {
		$this->db->from('Utilisateur');
		$query = $this->db->get();
		return $query->result();
	}
}