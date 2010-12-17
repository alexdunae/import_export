<?php
	if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Import_export_upd { 

		var $version = '1.0'; 

		function Import_export_upd() 
		{ 

			$this->EE =& get_instance();
		}
	
		// required stubs
		function install()
		{
			$data = array(
				'module_name' => 'Import_export' ,
				'module_version' => $this->version,
				'has_cp_backend' => 'y',
				'has_publish_fields' => 'n'
			);

			$this->EE->db->insert('modules', $data);

			$data = array(
				'class' => 'Import_export' ,
				'method' => 'import'
			);

			$this->EE->db->insert('actions', $data);

			$data = array(
				'class' => 'Import_export' ,
				'method' => 'export'
			);

			$this->EE->db->insert('actions', $data);

			return TRUE;
		}
	
		function uninstall() 
		{
			$this->EE->db->where('module_name', 'Import_export');
			$this->EE->db->delete('modules');

			$this->EE->db->where('class', 'Import_export');
			$this->EE->db->delete('actions');
			
			return TRUE;
		}

	
		function update($current = '')
		{
			return TRUE;
		}
	}