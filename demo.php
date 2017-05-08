<?php
	error_reporting(E_ALL);
	include "googlesheets_mysql_bridge/googlesheets_mysql_bridge.php";

	$bridge = new GoogleSheetsMySQLBridge();
	$bridge->setMYSQLAccess('localhost', 'root', '', 'gmbdemo');
	$bridge->setGoogleSheetsRules(
		array(
		
			array(
				'from_csv' => 'https://docs.google.com/spreadsheets/d/1JFhllpCTTBOz9e4Y_HeYkCiK-Z3w2cZM9gZ7eoGblVw/pub?gid=0&single=true&output=csv',
				'to_table' => 'apps',
				'sync_type'=> 'delete_and_insert'
			),
		
		/*
			array(
				'sync_type'=> 'update_if_exist_or_insert_new',
				'from_csv' => 'https://docs.google.com/spreadsheets/d/1JFhllpCTTBOz9e4Y_HeYkCiK-Z3w2cZM9gZ7eoGblVw/pub?gid=0&single=true&output=csv',
				'to_table' => 'apps',
				
				'id_field' => 'id', // private key in db table
				
				//db => csv
				//leave empty to auto mapping				
				'map_fields'=> array(
					'id' => 'id',
					'alias' => 'alias', 
					'product_name' => 'product_name',
					//'android' => 'android', //ignore update.
				)
			)
		*/
		
		)

	);
	$bridge->sync();


