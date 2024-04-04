<?php

require 'Config/Configuration.php';
require 'Database.php';

class Backup extends Database 
{
    public $db;
    public $queryFinal;

    public function __construct($db) 
    {
        parent::__construct(CONF_DB_HOST, CONF_DB_PORT, CONF_DB_USER, CONF_DB_PASS, $db);
    }

    public function backup() 
    {
        $this->queryFinal = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n\n";

        $tables = $this->db->prepare('SHOW TABLES');
        $tables->execute();
        $tables = $tables->fetchAll();

        foreach ($tables as $table):
            $table = $table[0];

            $tablesRun = $this->db->query("SELECT * FROM $table");
            $tablesCol = $tablesRun->columnCount();
            $tablesRun->execute();
            $tablesResult = $tablesRun->fetchAll();

            $this->queryFinal .= "DROP TABLE IF EXISTS `$table`;";

            $result = $this->db->query("SHOW CREATE TABLE $table");
            $result->execute();
            $tablesShows = $result->fetchAll(); 

            $tableDescribe = $this->db->query("DESCRIBE $table");
            $tableDescribe->execute();
            $colsDescribes = $tableDescribe->fetchAll();

            foreach ($tablesShows as $show):
                if (strstr($show[1], 'AUTO_INCREMENT=')):
                    $hasAutoIncrement = true;
                endif;

                $this->queryFinal .= "\n\n" . $show[1] . ";\n\n";

                if ($hasAutoIncrement)
                    $this->queryFinal .= "BEGIN;\n";

                    foreach ($tablesResult as $tableRes):
                        $this->queryFinal .= "INSERT INTO $table VALUES(";
                        
                        for ($j = 0; $j < $tablesCol; $j++):
                            $tableRes[$j] = addslashes($tableRes[$j]);
                            $tableRes[$j] = str_replace("\n", "\\n", $tableRes[$j]);

                            if (isset($tableRes[$j]) && !empty($tableRes[$j])):
                                switch ($colsDescribes[$j]['Type']):
                                    case 'int(11)':
                                        $this->queryFinal .= $tableRes[$j];
                                    break;

                                    case 'longtext':
                                    case 'varchar(255)':
                                        $this->queryFinal .= "'" . $tableRes[$j] . "'";
                                    break;

                                endswitch;
                            else:
                                if ($tableRes[$j] == '0'):
                                    $this->queryFinal .= '0';

                                else:
                                    switch ($colsDescribes[$j]['Null']):
                                        case 'YES':
                                            $this->queryFinal .= 'NULL';
                                            break;
                                        case 'NO':
                                            $this->queryFinal .= "''";
                                        break;

                                        case 'default':
                                            $this->queryFinal .= 'NULL';
                                        break;
                                    endswitch;
                                endif;
                            endif;

                            if ($j < ($tablesCol - 1)) 
                                $this->queryFinal .= ', ';
                        endfor;

                        $this->queryFinal .= ");\n";

                    endforeach;

                if ($hasAutoIncrement)
                    $this->queryFinal .= "COMMIT;";

                $this->queryFinal .= "\n\n";

            endforeach;
        endforeach;

        if (!is_dir(CONF_PATH_FOLDER))
            mkdir(CONF_PATH_FOLDER, 0777, true);

        chmod(CONF_PATH_FOLDER, 0777);

        $date       = date('m-d-Y-H-i-s', time()); 
        $filename   = CONF_PATH_FOLDER . "db_backup_$date"; 
        $handle     = fopen($filename.'.sql','w+');

        fwrite($handle, $this->queryFinal);
        fclose($handle);

        echo '<a href="' . $filename . '.sql">Clique ici</a>';
    }
}
