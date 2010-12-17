<?php
	define('IMPORT_EXPORT_URL', BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=import_export');
	define('IMPORT_EXPORT_PATH', 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=import_export');

	class Import_export_mcp { 
		var $version = '1.0.0';
		var $module_name = "Import_Export";
	
		var $settings;

		function Import_export_mcp() 
		{ 
			$this->EE =& get_instance();
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('import_export_module_name'));

			$this->EE->cp->set_breadcrumb( IMPORT_EXPORT_URL, $this->EE->lang->line('import_export_module_name') );
		}
		
		function index()
		{
			$this->EE->load->helper('form');

			$vars = array(
				'channels' => array(),
				'form_action' => IMPORT_EXPORT_PATH . AMP . 'method=export'
			);

			$channels = $this->EE->channel_model->get_channels();
			
			foreach($channels->result() as $channel)
			{
				$vars['channels'][$channel->channel_id] = $channel->channel_title;
			}
			
			return $this->EE->load->view('index', $vars, TRUE);
		}
		

	
		function export()
		{
			$this->EE->load->model('channel_entries_model');

			// TODO
			header('Content-type: text/plain');
			$channel_id = 16;

			$channel = $this->EE->channel_model->get_channel_info($channel_id)->result();
			$channel = current($channel);

			if (!$channel)
			{
				return "<p>Channel $channel_id not found.</p>";
			}

			$fields = $this->_get_field_list($channel->field_group);
			$field_count = count($fields);

			$this->EE->db->select('t.*, d.*');
			$this->EE->db->from('channel_titles AS t, channel_data AS d');
			$this->EE->db->where('t.channel_id', $channel_id);
			$this->EE->db->where('t.entry_id = d.entry_id', NULL, FALSE);
			$entries_query = $this->EE->db->get();
			

			$entries = array();

			ob_clean();

			// requires PHP >= 5.1.0
			$out = fopen('php://output', 'w');
			fputcsv($out, array_values($fields));
			
			
			foreach($entries_query->result_array() as $entry)
			{
				// TODO: there must be a PHP built-in that does this
				$row = array();

				foreach($fields as $db => $label) 
				{
					$row[] = isset($entry[$db]) ? $entry[$db] : '';
				}

				// sanity check
				if (count($row) !== $field_count)
				{
					fwrite($out, "ERROR: incorrect number of fields in row " . count($entries));
					fclose($out);
					exit;
				}
				fputcsv($out, $row);
				$entries[] = $row;				
			}

			fclose($out);
			exit;
		}
	
		/**
		 * Returns an array of field_db_name => field_label from a specified field group.
		 */
		function _get_field_list($field_group)
		{
			$fields = array('entry_id' => 'entry_id', 'title' => 'title', 'url_title' => 'url_title', 'status' => 'status');

			$fields_query = $this->EE->channel_model->get_channel_fields($field_group);

			foreach($fields_query->result() as $field)
			{
				$fields['field_id_' . $field->field_id] = $field->field_name;
			}

			return $fields;
		}

	}