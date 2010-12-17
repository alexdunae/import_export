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

			header('Content-type: text/csv');
			$channel_id = $this->EE->input->post('channel_id', TRUE);

			// channel info
			$channel = $this->EE->channel_model->get_channel_info($channel_id)->result();
			$channel = current($channel);

			if (!$channel)
			{
				return "<p>Channel $channel_id not found.</p>";
			}

			$fields = $this->_get_field_list($channel->field_group);
					
			// categories
			$cat_groups = array();
			$post_categories = array();

			if (empty($channel->cat_group) === FALSE) 
			{
				$cat_groups = $this->_get_category_groups($channel->cat_group);
				$post_categories = $this->_get_post_categories($channel->cat_group);
			}

			// entries
			$entries = array();

			$this->EE->db->select('t.*, d.*');
			$this->EE->db->from('channel_titles AS t, channel_data AS d');
			$this->EE->db->where('t.channel_id', $channel_id);
			$this->EE->db->where('t.entry_id = d.entry_id', NULL, FALSE);
			$entries_query = $this->EE->db->get();




			ob_clean();

			// build the CSV file; fputcsv requires PHP >= 5.1.0
			$out = fopen('php://output', 'w');

			// build the header row, showing categories first
			$headers = array_merge(array_values($cat_groups), $fields);	
			fputcsv($out, $headers);
			$field_count = count($headers);	
			
			foreach($entries_query->result_array() as $entry)
			{
				$row = array();

				foreach($cat_groups as $group_id => $group_slug)
				{
					$row[] = isset($post_categories[$entry['entry_id']]) ? @$post_categories[$entry['entry_id']][$group_id] : '';
				}

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

		/**
		 * Returns an array of group_id => group_name for one or more category groups (e.g. 1|2|3).
		 */
		function _get_category_groups($group_ids)
		{
			$this->EE->load->model('category_model');

			$group_ids = explode('|', $group_ids);

			$this->EE->db->select("group_id, group_name");
			$this->EE->db->from("category_groups");
			$this->EE->db->where_in("group_id", $group_ids);

			$groups_query = $this->EE->db->get();
			
			$groups = array();

			foreach($groups_query->result() as $group)
			{
				$groups[$group->group_id] = $this->_cat_group_slug($group->group_name);
			}

			return $groups;
		}

		function _get_post_categories($group_ids)
		{
			if ($group_ids === '')
			{				
				return array();
			}

			$group_ids = explode('|', $group_ids);
			
			$sql = sprintf(
				"SELECT cp.entry_id, c.group_id, GROUP_CONCAT(c.cat_url_title) AS cat_url_titles 
				 FROM exp_categories AS c LEFT JOIN exp_category_posts AS cp ON cp.cat_id = c.cat_id
				 WHERE c.group_id IN (%s) GROUP BY cp.entry_id, c.group_id;", 
				implode(',', $group_ids));
			
			$q = $this->EE->db->query($sql);
			
			$post_categories = array();
			foreach($q->result_array() as $row)
			{

				if (isset($post_categories[$row['entry_id']]) === FALSE)
				{
					$post_categories[$row['entry_id']] = array();
				}
				$post_categories[$row['entry_id']][$row['group_id']] = $row['cat_url_titles'];
			}
			
			return $post_categories;
		}
		
		function _cat_group_slug($raw)
		{
			return 'cat_' . preg_replace('/\W/', '_', strtolower($raw));
		}


	}