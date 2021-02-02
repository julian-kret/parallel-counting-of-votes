<?php
require_once "simple_html_dom.php";

define("LMR_SITE","http://www.site.ua");

define("LMR_MAX_CONTENT_TIMEOUT","5.0");
define("LMR_START_NAME_STR_MARK",'\cell\pard\intbl\li60');
define("LMR_START_SELECT_STR_MARK",'\cell\pard\intbl\qc\fs24');
define("LMR_END_SELECT_STR_MARK",'\cell\row\trowd');

define("DB_HOST","localhost");
define("DB_USER","root");
define("DB_PASSWORD","password");
define("DB_NAME","dbname");


class cDatabase extends mysqli
{
	public function __construct($host = DB_HOST, $user = DB_USER, $pass = DB_PASSWORD, $db = DB_NAME)
	{
		parent::init();
	
		if (!parent::options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
			die('MYSQLI_OPT_CONNECT_TIMEOUT не виконано');
		}
	
		if (!parent::real_connect($host, $user, $pass, $db)) {
			die('Помилка підключення (' . mysqli_connect_errno() . ') '
					. mysqli_connect_error());
		}
		
		if (!parent::set_charset("utf8")) {
			printf("Помилка при завантаженні символів utf8: %s\n", $mysqli->error);	
		}

		if (!parent::autocommit (FALSE)) {
			printf("Помилка AUTOCOMMIT = 0", $mysqli->error);	
		}
		
	}
	
	public function __destruct()
	{
		parent::close();
	}
	
	public function name2id($name)
	{
		$eName = $this->real_escape_string($name);
		$result = $this->query("SELECT id FROM `deputies` WHERE `name` LIKE '$eName'");
		$row = $result->fetch_row();
		if (!empty($row))
		{
			$deputy_id =  $row[0];
		}
		else 
		{
			$this->real_query("INSERT INTO deputies (name) VALUES ('$eName')");
			$deputy_id = $this->insert_id;
		}
		
		return $deputy_id;
	}
	
	// Вибір: 0 - ВІДСУТНІЙ, 1 - ЗА, 2 - ПРОТИ, 3 - УТРИМАВСЯ, 4 - НЕ ГОЛОСУВАВ, 5 - ХЗ
	public function election2id($electionStr)
	{
		if($electionStr === "відсутній")
			return 0;
		elseif ($electionStr === "ЗА")
			return 1;
		elseif ($electionStr === "ПРОТИ")
			return 2;
		elseif ($electionStr === "УТРИМАВСЯ")
			return 3;
		elseif ($electionStr === "НЕ ГОЛОСУВАВ")
			return 4;
		else 
			return 5;
	}
}

abstract class cLmrBase
{
	protected  $contents;
	
	private function read($url)
	{
		$this->contents = $this->getContent($url);
	}
	
	private function free_contents()
	{
		unset($this->contents);
	}
	
	protected function getContent($url)
	{
		$options = array(
				'http'=>array(
						'method'=>"GET",
						'timeout' => LMR_MAX_CONTENT_TIMEOUT
				)
		);
		$context = stream_context_create($options);
		$contents = file_get_contents($url, false, $context);
		if(!$contents)
			throw new Exception($url.': Не вдалося отримати контент');
	
		return $contents;
	}
	
	abstract protected function parse();
	
	public function process($url)
	{
		$this->read($url);
		$this->parse();
		$this->free_contents();
	}
	
	public function toJson()
	{
		return json_encode($this);	
	}
	
}

class cLmrElection extends cLmrBase
{
	public $election_result = array();
	
	private function rtfstr2uicode($str)
	{
		$str = str_replace('\\\'',"%",$str);
		$str = urldecode($str);
		$str = iconv("CP1251","UTF-8",$str);
		
		return $str;
	}
	
	protected function parse()
	{
		$start_name_str_mark = LMR_START_NAME_STR_MARK;
		$start_name_str_mark_length = strlen($start_name_str_mark);
		$start_select_str_mark = LMR_START_SELECT_STR_MARK;
		$start_select_str_mark_length = strlen($start_select_str_mark);
		$end_select_str_mark = LMR_END_SELECT_STR_MARK;
		$end_select_str_mark_length = strlen($end_select_str_mark);
		
		$row = 0;
		
		$offset = 0;
				
		while ($name_offset = strpos($this->contents,$start_name_str_mark,$offset))
		{
			$select_offset = strpos($this->contents,$start_select_str_mark,$name_offset);
			
			$name = substr($this->contents,$name_offset + $start_name_str_mark_length,$select_offset - ($name_offset + $start_name_str_mark_length));
			
			$offset = $select_offset + $start_select_str_mark_length;
			
			$end_select_offset = strpos($this->contents,$end_select_str_mark,$offset);
			
			$select = substr($this->contents,$offset,$end_select_offset - $offset); 
			
			$offset = $end_select_offset;
			
			$name = trim($this->rtfstr2uicode($name));
			$select = trim($this->rtfstr2uicode($select));
		
			$this->election_result[$name] = $select;
			
			$row++;
		}
	}
	
}

class cLmrSession extends cLmrBase
{
	public $elections = array();
	
	protected function parse()
	{
		$html = str_get_html($this->contents);

		$trs = $html->find('.itemFullText tr');
		if($trs)
			foreach($trs as $tr)
				{
					$issue = $election_url = "";
					
					$td = $tr->find('td',1);
					if(empty($td)) continue;
					$p = $td->find('p',0);
					if(empty($p)) continue;
					$issue = trim($p->innertext);
					
					$td = $tr->find('td',7);
					if(empty($td)) continue;
					$a = $tr->find('a',0);
					if(empty($a)) continue;
						
					$election_url = trim($a->href);
					
					if(!empty($election_url))
						{
							$this->elections[$issue] = new cLmrElection;
							$this->elections[$issue]->process(LMR_SITE.$election_url);
						}
				}
		
		$html->clear();
		unset($html);
	}
	
	public function save($date,$url)
	{
		$db = new cDatabase();

		$db->begin_transaction();
		
		try {
			$db->real_query("INSERT INTO sessions (date,url) VALUES ('$date','$url')");
			
			$sessionsId = $db->insert_id;
			
			foreach ($this->elections as $issue => $elections)
			{
				$eIssue = $db->real_escape_string($issue);
				
				$db->real_query("INSERT INTO issues (issue,session) VALUES ('$eIssue','$sessionsId')");
				
				$issueId = $db->insert_id;
				
				foreach ($elections->election_result as $name => $election)
				{
					$deputyId = $db->name2id($name);
					$electionId = $db->election2id($election);
					$db->real_query("INSERT INTO elections (deputy,issue,election) VALUES ('$deputyId','$issueId','$electionId')");
				}
			}

			$db->commit();
				
		} catch (Exception $e) {
			$db->rollback();
		}
				
	}
}


	$session = new cLmrSession;
	
	$session->process($_GET["s_url"]);
	
	$session->save($_GET["s_date"], $_GET["s_url"]);

	header('Content-Type: text/html; charset=utf-8');
	echo '<html>';
	echo '<body>';
	
// 	var_dump($session->elections);

	echo '</body>';
	echo '</html>';
?>
