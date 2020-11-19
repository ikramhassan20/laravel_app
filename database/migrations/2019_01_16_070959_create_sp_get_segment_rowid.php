<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class Create_sp_get_segment_rowid
 * @author  Ikram Hassan
 */
class CreateSpGetSegmentRowid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $config_database_definer = config('database.connections.' . config('database.default') . '.username');
        $procedureName = \App\Components\SQL_SP_VW_Identifier::SP_GET_SEGMENT_ROW_ID;

        Db::connection()->getPdo()->exec("CREATE DEFINER=`$config_database_definer` @`%` PROCEDURE `$procedureName` (IN segment_id INT, IN `OFF` INT, IN `LIM` INT) 

BEGIN 
DECLARE column_list VARCHAR (255); 
DECLARE action_list VARCHAR (255);
DECLARE conversion_list VARCHAR (255);
DECLARE delim CHAR (1); 
DECLARE column_name VARCHAR (50); 
DECLARE join_name VARCHAR (50); 
DECLARE alias_name VARCHAR (3); 
DECLARE join_sql VARCHAR (500); 
DECLARE join_value VARCHAR (500);
DECLARE group_sql VARCHAR (500); 
DECLARE main_sql TEXT; 
DECLARE where_sql VARCHAR (200); 
DECLARE company_sql VARCHAR (80); 
DECLARE company_alias_sql VARCHAR (80); 
DECLARE valid_column_list VARCHAR (100); 
DECLARE data_cast VARCHAR (50); 
DECLARE casted_data VARCHAR (50); 
DECLARE company_idx INT (10); 
DECLARE IN_SCHEMA_COLUMN INT DEFAULT 0;

## ON Error Exit
##Init looping indexer
DECLARE i INT DEFAULT 0; 
DECLARE j INT DEFAULT 0; 
DECLARE k INT DEFAULT 0;
 
#DECLARE EXIT HANDLER FOR SQLEXCEPTION
## Pre set the template
SELECT IF (TRIM(attribute_fields)='',NULL,attribute_fields),action_fields,conversion_fields,criteria,AP0.company_id INTO column_list,action_list,conversion_list,where_sql,company_idx FROM segment  S0 
INNER JOIN app_group AP0 ON AP0.id = S0.app_group_id  WHERE S0.id=segment_id; 

SET delim=','; 
SET company_sql=CONCAT(\" [ALIAS_CLAUSE]data_type='user'\"); 
SET main_sql=\"SELECT DISTINCT row_id FROM (SELECT r0.row_id,r0.company_id,sg.id  [COLUMN_VALUE]\"; 
SET main_sql=CONCAT(main_sql,\" FROM app_user r0  INNER JOIN segment sg ON sg.`app_group_id` = r0.app_group_id AND r0.is_deleted=0 AND sg.id = \",segment_id ); 
SET main_sql=CONCAT(main_sql,\" INNER JOIN app_user_token aut ON r0.`row_id` = aut.row_id AND aut.status=1 AND aut.is_revoked=0 \");
SET main_sql=CONCAT(main_sql,\" [LEFT_JOIN_SQL]\"); 
SET main_sql=CONCAT(main_sql,\" GROUP BY r0.row_id [GROUP_BY_SQL]\"); 
SET main_sql=CONCAT(main_sql,\" HAVING company_id = \",company_idx,\" AND (\",where_sql,\") ) B\"); 
SET join_sql=\" \"; 
SET join_value=\"\"; 
SET group_sql=\" \";

IF column_list!='' THEN
	## Loop through the colum_names within the segment
	WHILE i<=splitter_count (column_list,delim) 
	DO 
	SET column_name=SUBSTRING_INDEX(SUBSTRING_INDEX(column_list,delim,i+1),delim,-1); 
	SELECT COUNT(NAME) INTO IN_SCHEMA_COLUMN FROM attribute WHERE NAME=column_name AND level_type='platform' LIMIT 1; 
	IF IN_SCHEMA_COLUMN=0 THEN
	 SET data_cast=IFNULL(data_cast,'CHAR'); 
	 SET alias_name=CONCAT('r',i+1); 
	 SET join_name=CONCAT(alias_name,'.row_id'); 
	 SET company_alias_sql=
	 REPLACE (company_sql,\"[ALIAS_CLAUSE]\",CONCAT(alias_name,\".\")); 
	 SELECT data_type INTO data_cast FROM attribute WHERE NAME=column_name AND (data_type IS NOT NULL OR data_type<> '') LIMIT 1; 
	 
	 ## Check Data Type
	 IF data_cast=\"VARCHAR\" THEN 
	  SET casted_data=\"CHAR\"; 
	 ELSEIF data_cast=\"INT\" THEN 
	  SET casted_data=\"SIGNED\";   
	 ELSE 
		SET casted_data=data_cast; 
	 END IF; 
	 
	 SET join_value=CONCAT(join_value,\",CAST(\",alias_name,\".value AS \",casted_data,\") as \",column_name); 
	 SET join_sql=CONCAT(join_sql,\" LEFT JOIN attribute_data \",alias_name,\" ON \",join_name,\" = r0.row_id AND \",alias_name,\".code = '\",column_name,\"' AND \",company_alias_sql); 
	 
     IF i=0 THEN
		SET group_sql=CONCAT(group_sql,\",\",column_name);
     END IF;
     
	 ELSE	 
	  
		IF column_name!=\"row_id\" AND column_name!=\"company_id\" THEN 
			IF column_name=\"user_id\" || column_name=\"app_id\"  THEN
				SET join_value=CONCAT(join_value,\",r0.`\",column_name,\"`\");
            ELSE 
				SET join_value=CONCAT(join_value,\",`\",column_name,\"`\");
            END IF;
		END IF;
	 
	 END IF; 
	 
	 SET i=i+1; 
	 END WHILE; 
END IF;

IF action_list!='' THEN
	## Loop through the colum_names within the segment
	WHILE j<=splitter_count (action_list,delim) 
	DO 
	 SET column_name=SUBSTRING_INDEX(SUBSTRING_INDEX(action_list,delim,j+1),delim,-1); 	
	 SET alias_name=CONCAT('r',i+1); 
	 SET join_name=CONCAT(alias_name,'.row_id'); 
	 SET company_alias_sql=
	 REPLACE (company_sql,\"[ALIAS_CLAUSE]\",CONCAT(alias_name,\".\"));
     
	 SET join_value=CONCAT(join_value,\",\",alias_name,\".event_value  as \",column_name); 
	 SET join_sql=CONCAT(join_sql,\" LEFT JOIN app_user_activity \",alias_name,\" ON \",join_name,\" = r0.row_id AND \",alias_name,\".event_id = '\",column_name,\"'\"); 		 
	 
     SET i=i+1; 
     SET j=j+1; 	
    END WHILE;    
END IF;

IF conversion_list!='' THEN    
## Loop through the colum_names within the segment
	WHILE k<=splitter_count (conversion_list,delim) 
	DO 
	 SET column_name=SUBSTRING_INDEX(SUBSTRING_INDEX(conversion_list,delim,k+1),delim,-1); 	
	 SET alias_name=CONCAT('r',i+1); 
	 SET join_name=CONCAT(alias_name,'.row_id'); 
	 SET company_alias_sql=
	 REPLACE (company_sql,\"[ALIAS_CLAUSE]\",CONCAT(alias_name,\".\"));  
	 
     SET join_value=CONCAT(join_value,\",\",alias_name,\".event_value  as \",column_name); 
	 SET join_sql=CONCAT(join_sql,\" LEFT JOIN app_user_activity \",alias_name,\" ON \",join_name,\" = r0.row_id AND \",alias_name,\".event_id = '\",column_name,\"'\"); 		 
	 
     SET i=i+1;
     SET k=k+1;	
    END WHILE;    
END IF;

SET main_sql=REPLACE (main_sql,\"[COLUMN_VALUE]\",join_value); 
SET main_sql=REPLACE (main_sql,\"[LEFT_JOIN_SQL]\",join_sql); 
SET main_sql=REPLACE (main_sql,\"[GROUP_BY_SQL]\",group_sql);  

#SET @SQL=main_sql;
SET @sql = CONCAT(main_sql,\" limit \", OFF, \", \",LIM);
## START DEBUG
#SELECT @SQL;
## END DEBUG

PREPARE stmt FROM @SQL; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;  

END

");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /**
         * @author  Ikram Hassan
         */
        ///////// drop sp no need to implement
    }
}
