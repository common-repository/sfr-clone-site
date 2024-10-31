<?php 
set_time_limit(120);

if (!function_exists('sfr_clone_site_mysql_drop')) {

	function sfr_clone_site_mysql_drop($database, $site_id = false, $table_prefix) {
		
		global $wpdb;
		
		$query = '';
      	$lnbr = " ";
		
		if (!$site_id) {
			exit;
		}
		
		$get_table_query = "SHOW TABLES FROM ". @mysql_real_escape_string($database);
      	$get_table_query .= $site_id ? " WHERE Tables_in_" . @mysql_real_escape_string($database) ." LIKE '%" . $table_prefix . $site_id . "%' " : ' ';

      	$tables = @mysql_query($get_table_query);
		
		while ($row = @mysql_fetch_row($tables)) { $table_list[] = $row[0]; }
     	 for ($i = 0; $i < @count($table_list); $i++) {
			$query = 'DROP TABLE IF EXISTS  ' . $database . '.' . $table_list[$i] . ';' . $lnbr;
			$wpdb->query($query);		
		}
		
	}
}



if (!function_exists('sfr_clone_site_mysql_clone')) {
				
	function sfr_clone_site_mysql_clone( $database, $new_site_id, $old_site_id,  $table_prefix ) {
		
		global $wpdb;
		
		$query = '';
      	$lnbr = " ";
		
		$get_table_query = "SHOW TABLES FROM ". @mysql_real_escape_string($database);
      	$get_table_query .= $old_site_id ? " WHERE Tables_in_" . @mysql_real_escape_string($database) ." LIKE '%" . $table_prefix . $old_site_id . "%' " : ' ';

      	$tables = @mysql_query($get_table_query);
		
		while ($row = @mysql_fetch_row($tables)) { $table_list[] = $row[0]; }
     	 for ($i = 0; $i < @count($table_list); $i++) {
     	 	
     	 	$table_parts = explode($old_site_id, $table_list[$i]);
     		
     		/* CREATE NEW TABLE */	
  			$query = 'CREATE TABLE ' . $database . '.' . $table_parts[0].$new_site_id.$table_parts[1] . ' ';
			$query .= ' LIKE ' . $database . '.' . $table_list[$i] . ' ';
			$wpdb->query($query);   


			/* DUPLICATED CONTENT */
			$query = 'INSERT INTO ' . $database . '.' . $table_parts[0].$new_site_id.$table_parts[1] . ' ';
			$query .= ' SELECT * FROM ' . $database . '.' . $table_list[$i] . ' ';
			$wpdb->query($query);
		
		}
		
		return true;	
	}
}





if (!function_exists('sfr_clone_site_mysql_update')) {
				
	function sfr_clone_site_mysql_update( $database, $new_site_id, $old_site_id,  $table_prefix, $os_url, $ns_url, $os_theme, $ns_theme ) {
		
		global $wpdb;
		
		$query = '';
      	$lnbr = " ";

		$get_table_query = "SHOW TABLES FROM ". @mysql_real_escape_string($database);
      	$get_table_query .= $old_site_id ? " WHERE Tables_in_" . @mysql_real_escape_string($database) ." LIKE '%" . $table_prefix . $new_site_id . "%' " : ' ';

      	$tables = @mysql_query($get_table_query);
		$tables_array = array();
		
		while ($row = @mysql_fetch_row($tables)) { $table_list[] = $row[0]; }
     	 for ($i = 0; $i < @count($table_list); $i++) {
     	 	$table_parts = explode($old_site_id, $table_list[$i]);
			$tables_array[] = $table_list[$i];	
		}
	
		/*	THIS WILL UPDATE ALL TABLE NAMES */		
		$finder = new dbsearch(DB_NAME);
        $finder->exclude(false);
        $finder->useonly(implode(',' , $tables_array));
        $finder->find($table_prefix.$old_site_id,$table_prefix.$new_site_id,false);
        
        
		
		/*	THIS WILL UPDATE ALL URL IN THE DB */		
		$finder = new dbsearch(DB_NAME);
        $finder->exclude(false);
        $finder->useonly(implode(',' , $tables_array));
        $finder->find($os_url,$ns_url,false);
		

		/*	UPDATING FILE DIRECTORY	- THEME FOLDER	*/
		
		$query = array(
			" UPDATE  ". $table_prefix . $new_site_id ."_options SET option_value = 'wp-content/blogs.dir/". $new_site_id ."/files' WHERE option_name = 'upload_path' LIMIT 1; "
			, " UPDATE  ". $table_prefix . $new_site_id ."_options SET option_name = '". $table_prefix . $new_site_id ."_user_roles' WHERE option_name = '". $table_prefix . $old_site_id ."_user_roles' LIMIT 1; "
			, " UPDATE  ". $table_prefix . $new_site_id ."_options SET option_value = '". $ns_theme[0]->option_value ."' WHERE option_name = 'current_theme' ; "
			, " UPDATE  ". $table_prefix . $new_site_id ."_options SET option_value = '". $ns_theme[1]->option_value ."' WHERE option_name = 'stylesheet' ; "		
			, " UPDATE  ". $table_prefix . $new_site_id ."_options SET option_value = '". $ns_theme[2]->option_value ."' WHERE option_name = 'template' ; "
			, " UPDATE  ". $table_prefix . $new_site_id ."_options SET option_name = 'mods_". $os_theme[0]->option_value ."/". $ns_theme[1]->option_value ."' WHERE option_name = 'mods_". $os_theme[0]->option_value ."' LIMIT 1; "
		
		);
		

		foreach ( $query as $q ) {
			$wpdb->query($q);
			//echo $q . '<br />';	
		}
		
		return true;
		
	}
}






$dbase = DB_NAME;


function safeget($key,$default=false)
{
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

class db {
    private $link;
    public function getdata() {
        return array('user'=>ini_get('mysql.default_user'), 'pwd' =>ini_get('mysql.default_password'));

    }
    public function connect($dbase)
    {
        $user = ini_get('mysql.default_user');
        $pwd  = ini_get('mysql.default_password');
        if ($user & $pwd)
        {
           $this->link = @mysql_connect();
        }
        else
        {
           // echo  '<br>Warning: ini_get user and pwd are not set';
            // 2) Enter here your user pwd and server if required
            $this->link = @mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
        }
        if ($this->link === FALSE)
    	{
            //die ('Not able to connect to the database');
        }
    else
    {
            $db_selected = mysql_select_db ($dbase, $this->link);
            if (!$db_selected) {
                die ('Can\'t use database : ' . mysql_error());
            }
    }

    }
    function query($query)
    {

        if (false === ($this->res = mysql_query($query, $this->link))) {
            echo sprintf('MySQL error #%d: %s.', mysql_errno(), mysql_error());
        } else {
            $this->error = null;
            $result = array();
            while ($row = mysql_fetch_assoc($this->res)) {
                $result[] = $row;
            }
            return $result;
        }
    }
}



class dbsearch
{
	
    private $db;
    private $targettypes = array('varchar','char','text','tinytext','mediumtext','longtext');
    private $dbname;
    private $include=array();
    private $exclude=array();
    public function __construct($dbase)
    {
        $this->db = new db();
        $this->db->connect($dbase);
        $this->dbname = $dbase;
    }
    public function exclude($tables)
    {
        if (!$tables) return;
        $this->exclude = explode(',',$tables);
    }
    public function useonly($tables)
    {
        if (!$tables) return;
        $this->include = explode(',',$tables);
    }
    public function find($string, $ns_url, $detailed=false)
    {
        if (!$string || strlen($string)<3)
        {
            //die ("<br>Please enter a search term (3 chars min)");
        }
        $tables = $this->db->query('SHOW TABLES');
		global $wpdb;
        $tblfn = 'Tables_in_'.$this->dbname;
        foreach ($tables as $tablerow)
        {
            $table = $tablerow[$tblfn];
            $use = ($this->include ? in_array($table, $this->include) : true);
            $notuse = ($this->exclude ? in_array($table, $this->exclude) : false);
            if ($use && !$notuse)
            {
            $qryfields = array();
            
            $key='';

            //echo ".";
            $msql = 'SHOW FIELDS IN '. $table;
            //echo "<br>$msql<br>";
            
            $fields = $this->db->query($msql);
            // use char,varchar and text tinytext
            
            foreach($fields as $field)
            {
                $mysqltype = preg_replace('/\s*\(\d+\)\s*/','', $field['Type']);
        		$mysqltype = preg_replace('/\s*\(\d+,\d+\)\s*/','', $mysqltype);
                //echo $mysqltype."<br>";
                if ($field['Key'] == 'PRI' && !$key)
                {
                    $key = $field['Field'];
                }
                if (in_array($mysqltype, $this->targettypes ))
                {
                    $qryfields[] = ' `'.$field['Field']."`  like '%$string%'";
                    $updetafields[] = array(
                    	'field' => $field['Field'],
                    	'sql' => ' '.$field['Field'].' = replace('.$field['Field'].", '".$string."', '".$ns_url."') "
                    );
                    
                }

            }

            if ($qryfields)
            {
                $mSql = "SELECT * FROM  `$table` WHERE " . implode(' OR ', $qryfields);
                //echo $mSql."<br>";
                $results = $this->db->query($mSql);
                
                if ($results)
                {	
                	
                	foreach($results as $id => $result) {
                		
                		$first_stop = true;
              
                		foreach($result as $name => $field) {
                			//echo $field . '<br><br>';
                			if ($first_stop) {
                				$ref_where = array(
		                			'field' => $name,
		                			'id' => $field
		                		);
                				$first_stop = false;
                			}
                			
                			
                			$pos = strpos($field, $string);
                			
                			if ($pos === false) {

                			} else {
                				
                				if ($raw_data = $this->is_serial( $field )) {

                					array_walk_recursive( $raw_data, array($this, 'update_in_array'), array('old' => $string, 'new' => $ns_url) );            

                					$mSql = " UPDATE  `$table` SET " . $name . " = '". serialize($raw_data) ."' WHERE " . $ref_where['field'] . " = '". $ref_where['id'] ."' LIMIT 1; ";
                					
                				} else {
                 					$new_val = str_replace( $string, $ns_url, $field );
                					$mSql = " UPDATE  `$table` SET " . $name . " = '". $new_val ."' WHERE " . $ref_where['field'] . " = '". $ref_where['id'] ."' LIMIT 1; ";
                					
                				}

                				//echo $mSql.'<br />';
                				$wpdb->query($mSql);
                				//print_r($aa);
                				
                			}
                			
                			
                			
                			
                		
                		}
                		
                		
                		
                		
                	}
                	
                	
                }
                
                
                

            }
        }//if
        }
        //echo "Done.";
    }
    
    
    public function is_serial( $data ) { 
	    $data = @unserialize($data); 
	    if( $data === false ) { 
	        return false; 
	    } else { 
	        return $data; 
	    } 
	} 
	

	public function update_in_array(&$item, $key, $str) {
	   $item = str_replace($str['old'], $str['new'], $item);
	}
	
    
}





function deleter($dirname) {
   if (is_dir($dirname))
      $dir_handle = opendir($dirname);
   if (!$dir_handle)
      return false;
   while($file = readdir($dir_handle)) {
      if ($file != "." && $file != ".." ) {
         if (!is_dir($dirname."/".$file))
            unlink($dirname."/".$file);
         else
            deleter($dirname.'/'.$file);    
      }
   }
   closedir($dir_handle);
   
   
   rmdir($dirname);
}





function copyr( $source, $dest ) {

	if ( is_file( $source ) ) {
		$c = copy($source, $dest);
		chmod($dest, 0777);
		return $c;
	}

	if (!is_dir($dest)) {
		$oldumask = umask(0);
		mkdir($dest, 0777);
		umask($oldumask);
	}

	$dir = dir($source);
	while (false !== $entry = $dir->read()) {

		if ($entry == "." || $entry == ".." ) {
			continue;
		}
		
		if ($dest !== "$source/$entry") {
			copyr( $source."/".$entry, $dest."/".$entry ) ;
		}
	}

	$dir->close();
	return true;

}

			
















?>