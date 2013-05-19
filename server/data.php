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
	function __construct() {
		//echo "In BaseClass constructor\n";
	}

    function __get($member) {
        return $this->$member;
    }

	// RegEx
	// Using start and end string anchors in pattern.
	// string length 1-100 exclude <>
	public function validateName($EventName,&$Error){ if(!preg_match ('#^(?:(?!<>).){2,100}$#',$EventName)) {
																if(strlen($EventName) < 2) { $Error['EventName'] = 'Please enter at least 2 characters.'; } else if(strlen($EventName) > 100 ) { $Error['EventName'] = 'Please enter no more than 100 characters.'; } return 0; }
																else { return 1; }
													}

	// RegEx
	// Using start and end string anchors in pattern.
	// string length 1-255 exclude <>
	public function validateDesc($EventDesc,&$Error){ if(!preg_match ('#^(?:(?!<>).){2,255}$#',$EventDesc)) {
															if(strlen($EventDesc) < 2) { $Error['EventDesc'] = 'Please enter at least 2 characters.'; } else if(strlen($EventDesc) > 255 ) { $Error['EventDesc'] = 'Please enter no more than 255 characters.'; } return 0; }
															else { return 1; }
													}

	// RegEx
	// Using start and end string anchors in pattern.
	// yyyy-mm-dd
	public function validateDate($EventDate,&$Error){ if(!preg_match ('#^((?:19|20)\d\d)[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$#',$EventDate, $matches)) {
														$Error['EventDate'] = 'Please enter a valid date.';
														return 0; // Not a date
													} else if ((int)$matches[3] == 31 && ((int)$matches[2] == 4 || (int)$matches[2] == 6 || (int)$matches[2] == 9 || (int)$matches[2] == 11)) {
													 	$Error['EventDate'] = '31st of a month with 30 days.';
													 	return 0; // 31st of a month with 30 days
													} else if ((int)$matches[3] >= 30 && (int)$matches[2] == 2) {
													 	$Error['EventDate'] = 'February 30th or 31st.';
													 	return 0; // February 30th or 31st
													} else if ( (int)$matches[2] == 2 && (int)$matches[3] == 29 && ((int)$matches[1] % 4 != 0 && ((int)$matches[1] % 100 == 0 || (int)$matches[1] % 400 != 0))) {
													 	$Error['EventDate'] = 'February 29th outside a leap year.';
													 	return 0;
													} else {
													 	// At this point, $matches[1] holds the year, $matches[2] the month and $matches[3] the day of the date entered
													 	// Valid date
													 	// print_r((int)$matches[1]);
													 	// echo '<br/>';
													 	// print_r((int)$matches[2]);
													 	// echo '<br/>';
													 	// print_r((int)$matches[3]);
													 	// echo '<br/>';
													 	// print_r($matches);
													 	// echo '<br/>';
													 	// $Error['EventDate'] = 'Valid EventDate';
													 	return 1;
													}
  									 		 	}

	// RegEx
	// Using start and end string anchors in pattern.
	// Will only match if the subject contains exactly one character in the range [0-1].
	// Redundant
	public function validateFreq($IsAnnual, &$Error) { if(!preg_match ('#^[0-1]$#',$IsAnnual))  { $Error['IsAnnual'] = 'Please select for annual event.'; return 0;}  else return 1; }

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
			parent::validateName($EventName, $Error);
			parent::validateDesc($EventDesc, $Error);
			parent::validateDate($EventDate, $Error);
			parent::validateFreq($IsAnnual,  $Error);

			if (count($Error) > 0) {
				echo json_encode($Error);
				header("HTTP/1.1 400 Bad Request");
			} else {
				$query = $this->pdo->prepare("INSERT INTO event (EventName, EventDesc, EventDate, IsAnnual, DiaryId) VALUES ('".$EventName."', '".$EventDesc."', '".$EventDate."', ".$IsAnnual.", ".$DiaryId.");");
				echo $query->execute();
			}
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
			// should never happen from dev
			if ( strlen($DiaryId) <=  0)
				echo 'Read failed, no data to read';
			else {
				$query = $this->pdo->prepare("SELECT EventId, EventName, EventDesc, DATE_FORMAT(EventDate,'%e %M %Y') EventDate, IsAnnual FROM event WHERE DiaryId=".$DiaryId);
				$query->execute();
				$rows  = $query->fetchAll(PDO::FETCH_ASSOC);
				echo json_encode($rows);
			}
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
			parent::validateName($EventName, $Error);
			parent::validateDesc($EventDesc, $Error);
			parent::validateDate($EventDate, $Error);
			parent::validateFreq($IsAnnual,  $Error);

			if (count($Error) > 0) {
				echo json_encode($Error);
				header("HTTP/1.1 400 Bad Request");
			} else {
				$query = $this->pdo->prepare("UPDATE event SET EventName='".$EventName."', EventDesc='".$EventDesc."', EventDate='".$EventDate."', IsAnnual=".$IsAnnual." WHERE EventId=".$EventId);
				echo $query->execute();
			}
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
			else {
				$query = $this->pdo->prepare("DELETE FROM event WHERE EventId=".$EventId);
				echo $query->execute();
			}
		}
		catch (PDOException $e)
		{
			echo 'Delete failed: ' . $e->getMessage();
		}
	}

}

?>