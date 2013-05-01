<?php

switch($_POST['Source'])
{
	case "DataBase":
	default:
			$edd = new EventDatabaseData();
			$edd->getData();
		break;
}

abstract class EventData
{
	function __construct()
	{
		//echo "In BaseClass constructor\n";
	}

    function __get($member) {
        return $this->$member;
    }

	abstract protected function getData();
//	{
//		$index = array();
//		$index[0]["EventDate"] = "1 April 2013";
//		$index[0]["EventDesc"] = "football match@The match will take place on the planet krypton";
//		$index[1]["EventDate"] = "12 April 2013";
//		$index[1]["EventDesc"] = "Some Event@Some Place";
//		echo json_encode($index);
//	}

}

class EventDatabaseData extends EventData
{
	protected $pdo;
	protected $config;
	function __construct()
	{
		parent::__construct();
		$this->config 	= include 'database.config.php';
		$this->pdo 		= new PDO($this->config['connection_properties']['connection']['dsn'], $this->config['connection_properties']['connection']['username'], $this->config['connection_properties']['connection']['password'], array(PDO::ATTR_PERSISTENT => true) );
	}

	function __destruct()
	{
       //$this->pdo = null;
    }

	function getData()
	{
		try
		{
			$query = $this->pdo->prepare("SELECT DATE_FORMAT(EventDate,'%e %M %Y') EventDate, EventDesc FROM Event WHERE DiaryId=".$_POST['id']);
			$query->execute();
			$rows  = $query->fetchAll(PDO::FETCH_ASSOC);
			echo json_encode($rows);
		}
		catch (PDOException $e)
		{
			echo 'Connection failed: ' . $e->getMessage();
		}
	}
}

?>