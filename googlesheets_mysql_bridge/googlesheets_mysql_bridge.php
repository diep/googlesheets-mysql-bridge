<?php
include "dbutil.php";

class GoogleSheetsMySQLBridge{
	
	private $db_host = '';
	private $db_username = '';
	private $db_password = '';
	private $db_name = '';
	
	private $RULES = array(
		/*
			array(
				'from_csv' => 'https://docs.google.com/spreadsheets/d/1BirUILeSVAFJs7E6IYr84BZiUij7BQt9FJohhDUdgVY/pub?gid=868951319&single=true&output=csv',
				'to_table' => 'tt_category',
				'sync_type'=> 'delete_and_insert'
			),
		*/
		);
		
	public function setMYSQLAccess($db_host, $db_username, $db_password, $db_name){
		$this->db_host = $db_host;
		$this->db_username = $db_username;
		$this->db_password = $db_password;
		$this->db_name = $db_name;
	}
	
	public function setGoogleSheetsRules($rules){
		$this->RULES = $rules;
	}
	
	
	public function sync()
	{
		
		$SYNCS = $this->RULES;		
		$db = new DBUtil($this->db_host, $this->db_username, $this->db_password, $this->db_name);
		
		
		for ($i = 0; $i < count($SYNCS); $i++){
			$to_table = $SYNCS[$i]['to_table'];
			$sync_type = $SYNCS[$i]['sync_type'];
			
			
			echo "<h1>$to_table</h1>";
			$fields = $db->fetchAll("show columns from $to_table");
			$db_fields = array();
			echo "<h3>Table $to_table has ". count($fields) . " fields</h3>";
			for($j = 0; $j < count($fields); $j++){
				$db_fields[] = $fields[$j]['Field'];
				echo "<div>{$fields[$j]['Field']}</div>";
			}
			
			if($sync_type == 'delete_and_insert'){
				$db->query("TRUNCATE $to_table");
			}
			
			
			$url = $SYNCS[$i]['from_csv'] . "&t=". time();
			echo "<h5>$url</h5>";

			ini_set('auto_detect_line_endings', TRUE);
			if (($handle = fopen($url, 'r')) === FALSE) 
			{
				echo "Loi khi moi file csv ";
				exit;
			}
						
			$header = fgetcsv($handle, 2048, ',', '"');
			
			$auto_map_fields = array();
			echo "<h3>CSV has ". count($header) . " columns</h3>";				
			for($j = 0; $j < count($header); $j++){					
				$ok = in_array($header[$j], $db_fields);		
				$color = $ok !== false ? 'back' : 'red';
				echo "<div style='color:$color'>{$header[$j]}</div>";
				if ($ok !== false){
					$auto_map_fields[$header[$j]] = $header[$j];
				}
			}
			//echo json_encode($auto_map_fields);
			
			//maping.
			if(isset($SYNCS[$i]['map_fields'])){
				$map_fields = $SYNCS[$i]['map_fields'];
			} else {
				//calc map fields.
				$map_fields = $auto_map_fields;
			}			
			
			echo "<h3>=> MAP FIELD</h3>";
			var_dump ($map_fields);
			echo "<hr>";
			
			while (($data = fgetcsv($handle, 2048, ',', '"')) !== FALSE) 
			{
				if(isset($SYNCS[$i]['fake_data'])){
					for ($j = 0; $j < count($SYNCS[$i]['fake_data']); $j++)
					{
						$field = $SYNCS[$i]['fake_data'][$j];
						if(!in_array($field, $header))
						{
							$header[] = $field;
						}
						$data[] = rand(50000, 10000000);
						
					}
				}
				
				if($sync_type == 'delete_and_insert'){
					
					$query = $this->build_insert_query($db, $to_table, $map_fields, $header,$data);					
					
				} else if ($sync_type = 'update_if_exist_or_insert_new'){
					//check exist
					$id_field = $SYNCS[$i]['id_field'];
					$exists = $this->check_exists($db, $to_table, $id_field, $map_fields, $header, $data);
					
					//update 
					if(!$exists)
					{
						$query = $this->build_insert_query($db, $to_table, $map_fields, $header,$data);	
					} else {
						
						$query = $this->build_update_query($db, $to_table, $id_field, $map_fields, $header,$data);	
					}
				}
				//echo $query;				
				$rs = $db->query($query);
				if($rs === false){					
					echo "<div style='color:red'>Bug When insert <br/> $query</div>";
					exit;
				}
				
				
			}
			fclose($handle);
			
		}
		
		echo "<h3>Done</h3>";
	}	
	
	function build_insert_query($db, $table, $map_fields, $header,$data){
		
		$keys = array();
		$values = array();
		
		foreach ($map_fields as $db_field => $csv_field )
		{
			$keys[] = $db_field;
			
			$csv_field_index = array_search($csv_field, $header);			
			if ($csv_field_index === false){
				echo " csv_field $csv_field not found in header csv ";
				exit;				
			}			
			$values[] = $db->escape_string($data[$csv_field_index]);
			
		}		
		
		for ($i = 0; $i < count($keys); $i++)
		{
			$keys[$i] = "`".$keys[$i]."`";
		}
		$header_seg = implode(",", $keys);
		
		for ($i = 0; $i < count($values); $i++)
		{
			$values[$i] = "'".$db->escape_string($values[$i]). "'";
		}
		$data_seg = implode(",", $values);
		return "INSERT INTO `$table` 
			($header_seg) 
			VALUES ($data_seg);";
	}
	
	function get_id_value($id_field, $map_fields, $header, $data){
		
		$csv_id_field = $map_fields[$id_field];
		
		$index = array_search($csv_id_field, $header);
		if($index === false){
			echo "ERORRRRRRRRR $csv_id_field not found in header csv. <br/>";
			exit;
		}
		return $data[$index];
	}
	
	function check_exists($db, $table, $id_field, $map_fields,  $header, $data){
		
		$id_value = $this->get_id_value($id_field, $map_fields, $header, $data);		
		$rs = $db->fetchAll("select * from $table where $id_field = '$id_value'");
		
		return count($rs) > 0;
	}
	
	function build_update_query($db, $table, $id_field, $map_fields, $header,$data){		
		$id_value = $this->get_id_value($id_field, $map_fields, $header, $data);		
		$seg = array(); 
		
		foreach ($map_fields as $db_field => $csv_field )
		{
			$csv_field_index = array_search($csv_field, $header);
			
			if ($csv_field_index === false){
				echo " csv_field $csv_field not found in header csv ";
				exit;				
			}
			//$field_key = $header[$i];
			$field_val = $db->escape_string($data[$csv_field_index]);
			$seg[] = "`$db_field` = '$field_val'";
		}
		$seg_sql = implode(",", $seg);
		return "UPDATE `$table` SET $seg_sql where $id_field = '$id_value'";
	}
}