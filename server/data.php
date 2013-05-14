<?php


switch($_POST['Source'])
{
	case 'DataBase':
		$edd = new EventDatabaseData('database.config.php');
		switch($_POST['CRUD'])
		{
			case 'create':
				parse_str(trim($_POST['FormData']['Data'],"[]"));
				$edd->createData(htmlentities($DiaryId,ENT_NOQUOTES,"UTF-8"), htmlentities($EventName,ENT_NOQUOTES,"UTF-8"), htmlentities($EventDesc,ENT_NOQUOTES,"UTF-8"), htmlentities($EventDate,ENT_NOQUOTES,"UTF-8"), htmlentities($IsAnnual,ENT_NOQUOTES,"UTF-8"));
			break;
			case 'read':
				$edd->readData($_POST['id']);
			break;
			case 'update':
				parse_str(trim($_POST['FormData']['Data'],"[]"));
				$edd->updateData(htmlentities($EventId,ENT_NOQUOTES,"UTF-8"), htmlentities($EventName,ENT_NOQUOTES,"UTF-8"), htmlentities($EventDesc,ENT_NOQUOTES,"UTF-8"), htmlentities($EventDate,ENT_NOQUOTES,"UTF-8"), htmlentities($IsAnnual,ENT_NOQUOTES,"UTF-8"));
			break;
			case 'delete':
				parse_str(trim($_POST['FormData']['Data'],"[]"));
				$edd->deleteData(htmlentities($EventId,ENT_NOQUOTES,"UTF-8"));
			break;
			default:
				$edd->readData($_POST['id']);
			break;
		}
		break;
	default:
			$edd->readData($_POST['id']);
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

	abstract protected function createData($DiaryId, $EventName, $EventDesc, $EventDate, $IsAnnual);
	abstract protected function readData($DiaryId);
	abstract protected function updateData($EventId, $EventName, $EventDesc, $EventDate, $IsAnnual);
	abstract protected function deleteData($EventId);
}

class EventDatabaseData extends EventData
{
	protected $pdo;
	protected $config;
	function __construct($file)
	{
		parent::__construct();
		$this->config 	= include $file;
		$this->pdo 		= new PDO($this->config['connection_properties']['connection']['dsn'], $this->config['connection_properties']['connection']['username'], $this->config['connection_properties']['connection']['password'], array(PDO::ATTR_PERSISTENT => true) );
	}

	function __destruct()
	{
       //$this->pdo = null;
    }

	function createData($DiaryId, $EventName, $EventDesc, $EventDate, $IsAnnual)
	{
		try
		{
			$query = $this->pdo->prepare("INSERT INTO event (EventName, EventDesc, EventDate, IsAnnual, DiaryId) VALUES ('".$EventName."', '".$EventDesc."', '".$EventDate."', ".$IsAnnual.", ".$DiaryId.");");
			echo $query->execute();
		}
		catch (PDOException $e)
		{
			echo 'Create failed: ' . $e->getMessage();
		}
	}

	function readData($DiaryId)
	{
		try
		{
			$query = $this->pdo->prepare("SELECT EventId, EventName, EventDesc, DATE_FORMAT(EventDate,'%e %M %Y') EventDate, IsAnnual FROM event WHERE DiaryId=".$DiaryId);
			$query->execute();
			$rows  = $query->fetchAll(PDO::FETCH_ASSOC);
			echo json_encode($rows);
		}
		catch (PDOException $e)
		{
			echo 'Select failed: ' . $e->getMessage();
		}
	}

	function updateData($EventId, $EventName, $EventDesc, $EventDate, $IsAnnual)
	{
		try
		{
			$query = $this->pdo->prepare("UPDATE event SET EventName='".$EventName."', EventDesc='".$EventDesc."', EventDate='".$EventDate."', IsAnnual=".$IsAnnual." WHERE EventId=".$EventId);
			echo $query->execute();
		}
		catch (PDOException $e)
		{
			echo 'Update failed: ' . $e->getMessage();
		}
	}

	function deleteData($EventId)
	{
		try
		{
			// should never happen from UI
			if ( strlen($EventId) <=  0)
				echo 'Delete failed, no event to delete';

			$query = $this->pdo->prepare("DELETE FROM event WHERE EventId=".$EventId);
			echo $query->execute();
		}
		catch (PDOException $e)
		{
			echo 'Delete failed: ' . $e->getMessage();
		}
	}

}

?>