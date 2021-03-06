# googlesheets-mysql-bridge
A simple php script help you automatically update tables (MYSQL) from sheets (Goolge Sheets)

# sample problems
You have a sheet on google sheets

![sheet](README_images/sheet.JPG)

You have a associated table in SQL database

![database table](README_images/table.JPG)


When you edit sheet, you want changes will be updated to table on database Mysql


# Usage

For a table, you must: 

Step 1 : Ensure name of column headers on sheet are field names on table. If it's wrong, you must rename it.

Step 2 : Publish sheet as csv and get access link.
On google sheets, Access menu File >> Publish to the webs
1. select sheet 
2. select publish format is .csv
3. press publish
4. copy link.

![publish sheet as csv](README_images/publish.jpg)

Step 3 : just write code
Create new a file named demo.php
```php
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
			)
		)
	);
	$bridge->sync();
```
Step 4 : run code to sync.

Example: You release the web on domain http://localhost/gmbdemo. Then you just open link http://localhost/gmbdemo/demo.php on browser, you get result:

![result table](README_images/table_result.JPG)

You can check output

![result output](README_images/result_output.JPG)
