<?php
/*
	Condorcet PHP Class, with Schulze Methods and others !

	Version : 0.13

	By Julien Boudry - MIT LICENSE (Please read LICENSE.txt)
	https://github.com/julien-boudry/Condorcet_Schulze-PHP_Class
*/

namespace Condorcet ;


// Include Algorithms
foreach (glob( __DIR__ . DIRECTORY_SEPARATOR."algorithms".DIRECTORY_SEPARATOR."*.algo.php" ) as $Condorcet_filename)
{
	include_once $Condorcet_filename ;
}

// Set the default Condorcet Class algorithm
namespace\Condorcet::setClassMethod('Schulze') ;


// Base Condorcet class
class Condorcet
{

/////////// CLASS ///////////


	const VERSION = '0.13' ;
	const ENV = 'STABLE' ;
	const MAX_LENGTH_CANDIDATE_ID = 30 ; // Max length for candidate identifiant string

	protected static $_classMethod	= null ;
	protected static $_authMethods	= '' ;
	protected static $_forceMethod	= false ;
	protected static $_max_parse_iteration = null ;

	// Return library version numer
	public static function getClassVersion ($dev_infos = true)
	{
		return ($dev_infos && self::ENV !== 'DEV') ? self::VERSION : self::ENV . ' - '. self::VERSION ;
	}


	// Change max parse iteration
	public static function setMaxParseIteration ($value)
	{
		if (is_int($value) || $value === null)
		{
			self::$_max_parse_iteration = $value ;
			return self::$_max_parse_iteration ;
		}
		else
			{ return false ; }
	}


	// Return an array with auth methods
	public static function getAuthMethods ()
	{
		$auth = explode(',', self::$_authMethods) ;

		return $auth ;
	}


	// Return the Class default method
	public static function getClassDefaultMethod ()
	{
		return self::$_classMethod ;
	}


	// Check if the method is supported
	public static function isAuthMethod ($input_methods)
	{
		$auth = self::getAuthMethods() ;

		if (is_string($input_methods))
		{
			$methods = array($input_methods);
		}

		if (is_array($methods))
		{
			foreach ($methods as $method)
			{
				if ( !in_array($method,$auth, true) )
					{ return false ; }
			}

			return true ;
		}

		return false ;
	}


	// Add algos
	public static function addAlgos ($algos)
	{
		$to_add = array() ;

		// Check algos
		if ( is_null($algos) )
			{ return false ; }

		elseif ( is_string($algos) && !self::isAuthMethod($algos) )
		{
			if ( !self::testAlgos($algos) )
			{
				return false ;
			}

			$to_add[] = $algos ; 
		}

		elseif ( is_array($algos) )
		{
			foreach ($algos as $value)
			{
				if ( !self::testAlgos($value) )
				{
					return false ;
				}

				if ( !self::isAuthMethod($value) )
				{
					$to_add[] = $value ; 
				}
			}
		}

		// Adding algo
		foreach ($to_add as $value)
		{
			if ( empty(self::$_authMethods) )
				{ self::$_authMethods .= $value ; }
			else
				{ self::$_authMethods .= ','.$value ; }
		}

		return true ;
	}


		// Check if the class Algo. exist and ready to be used
		protected static function testAlgos ($algos)
		{
			if ( !class_exists(__NAMESPACE__.'\\'.$algos, false) )
			{				
				throw new namespace\CondorcetException(9) ;
			}

			if ( !in_array(__NAMESPACE__.'\\'.'Condorcet_Algo', class_implements(__NAMESPACE__.'\\'.$algos), false) )
			{
				throw new namespace\CondorcetException(10) ;
			}

			return true ;
		}


	// Change default method for this class, if $force == true all current and further objects will be forced to use this method and will not be able to change it by themselves.
	public static function setClassMethod ($method, $force = null)
	{		
		if ( self::isAuthMethod($method) )
		{
			self::$_classMethod = $method ;

			if (is_bool($force))
			{
				self::forceMethod($force);
			}

			return self::getClassDefaultMethod() ;
		}
		else
			{ return false ; }
	}

			// If $force == true all current and further objects will be forced to use this method and will not be abble to change it by themselves.
			public static function forceMethod ($force = true)
			{
				if ($force)
				{
					self::$_forceMethod = true ;
					return true ;
				}
				else
				{
					self::$_forceMethod = false ;
					return false ;
				}
			}


	// Check JSON format
	public static function isJson ($string)
	{
		// try to decode string
		json_decode($string);

		// check if error occured
		$isValid = json_last_error() === JSON_ERROR_NONE;

		return $isValid;
	}



/////////// CONSTRUCTOR ///////////


	// Data and global options
	protected $_Method ; // Default method for this object
	protected $_Candidates ; // Candidate list
	protected $_Votes ; // Votes list

	// Mechanics 
	protected $_i_CandidateId	= 'A' ;
	protected $_State	= 1 ; // 1 = Add Candidates / 2 = Voting / 3 = Some result have been computing
	protected $_CandidatesCount = 0 ;
	protected $_nextVoteTag = 0 ;
	protected $_objectVersion ;
	protected $_globalTimer = 0.0 ;
	protected $_lastTimer = 0.0 ;

	// Result
	protected $_Pairwise ;
	protected $_Calculator ;

		//////

	public function __construct ($method = null)
	{
		$this->_Method = self::$_classMethod ;

		$this->_Candidates	= array() ;
		$this->_Votes 	= array() ;

		$this->setMethod($method) ;

		// Store constructor version (security for caching)
		$this->_objectVersion = self::VERSION ;
	}

		public function getObjectVersion ()
		{
			return $this->_objectVersion ;
		}

	public function __sleep ()
	{
		// Don't include computing data, only candidates & votes
		return array	(
			'_Method',
			'_Candidates',
			'_Votes',
			'_i_CandidateId',
			'_State',
			'_CandidatesCount',
			'_nextVoteTag',
			'_objectVersion'
						);
	}

	public function __wakeup ()
	{
		if ( version_compare($this->getObjectVersion(),self::getClassVersion(false),'<') )
		{
			throw new namespace\CondorcetException(11, 'Your object version is '.$this->getObjectVersion().' but the class engine version is '.self::getClassVersion());
		}

		if ($this->_State > 2) 
			{$this->_State = 2 ;}
	}

		//////

	// Change the object method, except if self::$_for_Method == true
	public function setMethod ($method = null)
	{
		if (self::$_forceMethod)
		{
			$this->_Method = self::$_classMethod ;
		}
		elseif ( $method != null && self::isAuthMethod($method) )
		{
			$this->_Method = $method ;
		}

		return $this->_Method ;
	}


	// Return object state with somes infos
	public function getConfig ()
	{
		$this->setMethod() ;

		return array 	(
							'CondorcetObject_Version' => $this->getObjectVersion(),

							'object_Method'		=> $this->getMethod(),
							'class_default_Method'	=> self::$_classMethod,
							'is_ClassForceMethod'=> self::$_forceMethod,

							'class_authMethods'=> self::getAuthMethods(),
							'class_MaxParseIterations'=> self::$_max_parse_iteration,

							'state'		=> $this->_State
						);
	}


	public function getMethod ()
	{
		return $this->setMethod() ;
	}


	// Reset all, be ready for a new vote - PREFER A CLEAN DESTRUCT of this object
	public function resetAll ()
	{
		$this->cleanupResult() ;

		$this->_Candidates = array() ;
		$this->_CandidatesCount = 0 ;
		$this->_nextVoteTag = 0 ;
		$this->_Votes = array() ;
		$this->_i_CandidateId = 'A' ;
		$this->_State	= 1 ;

		$this->setMethod() ;

		return true ;
	}


	// Generic action before parsing data from string input
	protected function prepareParse ($input, $allowFile)
	{
		// Input must be a string
		if (!is_string($input))
			{ throw new namespace\CondorcetException(14); }

		// Is string or is file ?
		if ($allowFile === true && is_file($input))
		{
			$input = file_get_contents($input);
		}

		// Line
		$input = preg_replace("(\r\n|\n|\r)",';',$input);
		$input = explode(';', $input);

		// Delete comments
		foreach ($input as &$line)
		{
			// Trim
			$line = trim($line);

			// Delete comments
			$is_comment = strpos($line, '#') ;
			if ($is_comment !== false)
			{
				$line = substr($line, 0, $is_comment) ;
			}
		}

		return $input ;
	}


	protected function prepareJson ($input)
	{
		if (!self::isJson($input))
			{ throw new namespace\CondorcetException(15); }

		return json_decode($input, true);
	}


	protected function setTimer ($timer)
	{
		$this->_lastTimer = microtime(true) - $timer ;
		$this->_globalTimer += $this->_lastTimer ;
	}

	public function getGlobalTimer ($float = false)
		{ return ($float) ? $this->_globalTimer : number_format($this->_globalTimer, 5) ; }

	public function getLastTimer ($float = false)
		{ return ($float) ? $this->_lastTimer : number_format($this->_lastTimer, 5) ; }



/////////// CANDIDATES ///////////


	// Add a vote candidate before voting
	public function addCandidate ($candidate_id = null)
	{
		// only if the vote has not started
		if ( $this->_State > 1 ) { throw new namespace\CondorcetException(2) ; }
		
		// Filter
		if ( is_bool($candidate_id) || is_array($candidate_id) || is_object($candidate_id) )
			{ throw new namespace\CondorcetException(1, $candidate_id) ; }

		
		// Process
		if ( empty($candidate_id) ) // $candidate_id is empty ...
		{
			while ( !$this->try_addCandidate($this->_i_CandidateId) )
			{
				$this->_i_CandidateId++ ;
			}

			$this->_Candidates[] = $this->_i_CandidateId ;
			$this->_CandidatesCount++ ;

			return $this->_i_CandidateId ;
		}
		else // Try to add the candidate_id
		{
			$candidate_id = trim($candidate_id);

			if ( mb_strlen($candidate_id) > self::MAX_LENGTH_CANDIDATE_ID || is_bool($candidate_id) )
				{ throw new namespace\CondorcetException(1, $candidate_id) ; }

				///

			if ( $this->try_addCandidate($candidate_id) )
			{
				$this->_Candidates[] = $candidate_id ;
				$this->_CandidatesCount++ ;

				return $candidate_id ;
			}
			else
			{
				throw new namespace\CondorcetException(3,$candidate_id) ;
			}
		}
	}

		protected function try_addCandidate ($candidate_id)
		{
			return !$this->existCandidateId($candidate_id) ;
		}


	// Destroy a register vote candidate before voting
	public function removeCandidate ($list)
	{
		// only if the vote has not started
		if ( $this->_State > 1 ) { throw new namespace\CondorcetException(2) ; }

		
		if ( !is_array($list) )
		{
			$list	= array($list) ;
		}

		foreach ($list as &$candidate_id)
		{
			$candidate_id = trim($candidate_id) ;

			$candidate_key = $this->getCandidateKey($candidate_id) ;

			if ( $candidate_key === false )
				{ throw new namespace\CondorcetException(4,$candidate_id) ; }

			$candidate_id = $candidate_key ;
		}

		foreach ($list as $candidate_id)
		{
			unset($this->_Candidates[$candidate_id]) ;
			$this->_CandidatesCount-- ;
		}

		return true ;
	}


	public function jsonCandidates ($input)
	{
		$input = $this->prepareJson($input);
		if ($input === false) { return $input ; }

			//////

		$count = 0 ;
		foreach ($input as $candidate)
		{
			if ($this->addCandidate($candidate))
				{ $count++; }
		}

		return $count ;
	}


	public function parseCandidates ($input, $allowFile = true)
	{
		$input = $this->prepareParse($input, $allowFile) ;
		if ($input === false) { return $input ; }

		$ite = 0 ;
		foreach ($input as $line)
		{
			// Empty Line
			if (empty($line)) { continue ; }

			// addCandidate
			if ($this->addCandidate($line))
				{ $ite++ ; }
		}

		return $ite ;
	}


		//:: CANDIDATES TOOLS :://

		// Count registered candidates
		public function countCandidates ()
		{
			return $this->_CandidatesCount ;
		}

		// Get the list of registered CANDIDATES
		public function getCandidatesList ()
		{
			return $this->_Candidates ;
		}

		protected function getCandidateKey ($candidate_id)
		{
			return array_search($candidate_id, $this->_Candidates, true) ;
		}

		protected function getCandidateId ($candidate_key)
		{
			return self::getStatic_CandidateId($candidate_key, $this->_Candidates) ;
		}

			public static function getStatic_CandidateId ($candidate_key, &$candidates)
			{
				if (!array_key_exists($candidate_key, $candidates))
					{ return false ; }

				return $candidates[$candidate_key] ;
			}

		public function existCandidateId ($candidate_id)
		{
			return in_array($candidate_id, $this->_Candidates) ;
		}



/////////// VOTING ///////////


	// Close the candidate config, be ready for voting (optional)
	public function closeCandidatesConfig ()
	{
		if ( $this->_State === 1 )
			{ 
				$this->_State = 2 ;
			}

		// If voting continues after a first set of results
		elseif ( $this->_State > 2 )
			{ 
				$this->cleanupResult();
			}

		return true ;
	}


	// Add a single vote. Array key is the rank, each candidate in a rank are separate by ',' It is not necessary to register the last rank.
	public function addVote ($vote, $tag = null)
	{
		$this->closeCandidatesConfig() ;

			////////
		$original_input = $vote ;

		// Translate the string if needed
		if ( is_string($vote) )
		{
			$vote = $this->convertVoteInput($vote) ;
		}

		// Check array format
		if ( !is_array($vote) || !$this->checkVoteInput($vote) )
			{ throw new namespace\CondorcetException( 5, (!is_array($original_input) ? $original_input : null) ) ; }

		// Check tag format
		if ( is_bool($tag) )
			{ throw new namespace\CondorcetException(5) ; }

		// Sort
		ksort($vote);

		// Register vote
		return $this->registerVote($vote, $tag) ; // Return the array vote tag(s)
	}

		// From a string like 'A>B=C=H>G=T>Q'
		protected function convertVoteInput ($formula)
		{
			$vote = explode('>', $formula);

			foreach ($vote as $rank => $rank_vote)
			{
				$vote[$rank] = explode('=', $rank_vote);

				// Del space at start and end
				foreach ($vote[$rank] as $key => $value)
				{
					$vote[$rank][$key] = trim($value);
				}
			}

			return $vote ;
		}

		protected function checkVoteInput ($vote)
		{
			$list_candidate = array() ;

			if ( count($vote) > $this->_CandidatesCount || count($vote) < 1 )
				{ return false ; }

			foreach ($vote as $rank => $choice)
			{
				// Check key & candidate
				if ( !is_numeric($rank) || $rank > $this->_CandidatesCount || empty($choice) )
					{ return false ; }

					//////

				if (!is_array($choice))
				{
					$candidates = explode(',', $choice) ;
				}
				else
				{
					$candidates = $choice ;
				}

				foreach ($candidates as $candidate)
				{
					if ( !$this->existCandidateId($candidate) )
					{
						return false ;
					}

					// Do not do 2x the same candidate
					if ( !in_array($candidate, $list_candidate)  )
						{ $list_candidate[] = $candidate ; }
					else 
						{ return false ; }
				}
			}

			return true ;
		}

		// Write a new vote
		protected function registerVote ($vote, $tag = null)
		{
			$last_line_check = array() ;
			$vote_r = array() ;

			$i = 1 ;
			foreach ($vote as $value)
			{
				if ( !is_array($value) )
				{
					$vote_r[$i] = explode(',', $value) ;
				}
				else
				{
					$vote_r[$i] = $value ;
				}

				// $last_line_check
				foreach ($vote_r[$i] as $candidate)
				{
					$last_line_check[] = $this->getCandidateKey($candidate) ;
				}

				$i++ ;
			}

			if ( count($last_line_check) < count($this->_Candidates) )
			{
				foreach ($this->_Candidates as $key => $value)
				{
					if ( !in_array($key,$last_line_check) )
					{
						$vote_r[$i][] = $value ;
					}
				}
			}

			// Vote identifiant
			if ($tag !== null)
			{
				$vote_r['tag'] = $this->tagsConvert($tag) ;
			}
			
			$vote_r['tag'][] = $this->_nextVoteTag++ ;
			
			
			// Register
			$this->_Votes[] = $vote_r ;

			return $vote_r['tag'] ;
		}


	public function removeVote ($tag, $with = true)
	{
		$this->closeCandidatesConfig() ;

			//////

		// Prepare Tags
		$tag = $this->tagsConvert($tag) ;

		// Deleting

		$effective = 0 ;

		foreach ($this->getVotesList($tag, $with) as $key => $value)
		{
			unset($this->_Votes[$key]) ;
			$effective++ ;
		}

		return $effective ;
	}


	public function jsonVotes ($input)
	{
		$input = $this->prepareJson($input);
		if ($input === false) { return $input ; }

			//////

		$count = 0 ;

		foreach ($input as $record)
		{
			if (empty($record['vote']))
				{ continue ; }

			$tags = (!isset($record['tag'])) ? null : $record['tag'] ;
			$multi = (!isset($record['multi'])) ? 1 : $record['multi'] ;

			for ($i = 0 ; $i < $multi ; $i++)
			{
				if (self::$_max_parse_iteration !== null && $count >= self::$_max_parse_iteration)
				{
					throw new namespace\CondorcetException(12, self::$_max_parse_iteration);
				}

				if ( $this->addVote($record['vote'], $tags) )
					{ $count++; }
			}
		}

		return ($count === 0) ? false : $count ;
	}

	public function parseVotes ($input, $allowFile = true)
	{
		$input = $this->prepareParse($input, $allowFile) ;
		if ($input === false) { return $input ; }

		// Check each lines
		$ite = 0 ;
		foreach ($input as $line)
		{
			// Empty Line
			if (empty($line)) { continue ; }

			// Multiples
			$is_multiple = strpos($line, '*') ;
			if ($is_multiple !== false)
			{
				$multiple = trim( substr($line, $is_multiple + 1) ) ;

				// Errors
				if ( !is_numeric($multiple) )
				{ 
					throw new namespace\CondorcetException(13, null);
				}

				$multiple = intval($multiple) ;
				$multiple = floor($multiple) ;


				// Reformat line
				$line = substr($line, 0, $is_multiple) ;
			}
			else
				{ $multiple = 1 ; }

			// Tags + vote
			if (strpos($line, '||') !== false)
			{
				$data = explode('||', $line);

				$vote = $data[1] ;
				$tags = $data[0] ;
			}
			// Vote without tags
			else
			{
				$vote = $line ;
				$tags = null ;
			}

			// addVote
			for ($i = 0 ; $i < $multiple ; $i++)
			{
				if (self::$_max_parse_iteration !== null && $ite >= self::$_max_parse_iteration)
				{
					throw new namespace\CondorcetException(12, self::$_max_parse_iteration);
				}

				if ($this->addVote($vote, $tags))
					{ $ite++ ; }
			}
		}

		return $ite ;
	}


	//:: VOTING TOOLS :://

	// How many votes are registered ?
	public function countVotes ($tag = null, $with = true)
	{
		if (!empty($tag))
		{
			return count( $this->getVotesList($tag, $with) ) ;
		}
		else
		{
			return count($this->_Votes) ;
		}
	}

	// Get the votes registered list
	public function getVotesList ($tag = null, $with = true)
	{
		if (empty($tag))
		{
			return $this->_Votes ;
		}
		else
		{
			$tag = $this->tagsConvert($tag) ;

			$search = array() ;

			foreach ($this->_Votes as $key => $value)
			{
				$noOne = true ;
				foreach ($tag as $oneTag)
				{
					if ( in_array($oneTag, $value['tag'],true) )
					{
						if ($with)
						{
							$search[$key] = $value ;
							break ;
						}
						else
						{
							$noOne = false ;
						}
					}
				}

				if (!$with && $noOne)
					{ $search[$key] = $value ;}
			}

			return $search ;
		}
	}

	protected function tagsConvert ($tags)
	{		
		if (empty($tags))
			{ return null ; }

		// Make Array
		if (!is_array($tags))
		{
			$tags = explode(',', $tags);
		}

		// Trim tags
		foreach ($tags as &$oneTag)
		{
			$oneTag = trim($oneTag);
		}

		return $tags ;
	}



/////////// RETURN RESULT ///////////


	//:: PUBLIC FUNCTIONS :://

	// Generic function for default result with ability to change default object method
	public function getResult ($method = true, array $options = null, $tag = null, $with = true)
	{
		// Filter if tag is provided & return
		if ($tag !== null)
		{ 
			$timer_start = microtime(true);

			$filter = new self ($this->_Method) ;

			foreach ($this->getCandidatesList() as $candidate)
			{
				$filter->addCandidate($candidate);
			}
			foreach ($this->getVotesList($tag, $with) as $vote)
			{
				$voteTags = $vote['tag'] ;
				unset($vote['tag']) ;

				$filter->addVote($vote, $voteTags) ;
			}

			$this->setTimer($timer_start) ;

			return $filter->getResult($method, $options) ;
		}

			////// Start //////

		// Method
		$this->setMethod() ;
		// Prepare
		$this->prepareResult() ;

			//////

		$timer_start = microtime(true);

		if ($method === true)
		{
			$this->initResult($this->_Method) ;

			$result = $this->_Calculator[$this->_Method]->getResult($options) ;
		}
		elseif (self::isAuthMethod($method))
		{
			$this->initResult($method) ;

			$result = $this->_Calculator[$method]->getResult($options) ;
		}
		else
		{
			throw new namespace\CondorcetException(8,$method) ;
		}

		$this->setTimer($timer_start) ;

		return $this->humanResult($result) ;
	}

		protected function humanResult ($robot)
		{
			if (!is_array($robot))
				{return $robot ;}

			$human = array() ;

			foreach ( $robot as $key => $value )
			{
				if (is_array($value))
				{
					foreach ($value as $candidate_key)
					{
						$human[$key][] = $this->getCandidateId($candidate_key) ;
					}
				}
				elseif (is_null($value))
					{ $human[$key] = null ;	}
				else
					{ $human[$key][] = $this->getCandidateId($value) ; }
			}

			foreach ( $human as $key => $value )
			{
				if (is_null($value))
					{ $human[$key] = null; }
				else
					{ $human[$key] = implode(',',$value); }
			}

			return $human ;
		}


	public function getWinner ($substitution = false)
	{
		if ( $substitution )
		{
			if ($substitution === true)
				{$substitution = $this->_Method ;}

			if ( self::isAuthMethod($substitution) )
				{$algo = $substitution ;}
			else
				{throw new namespace\CondorcetException(9,$substitution);}
		}
		else
			{$algo = 'Condorcet_Basic';}

			//////

		return $this->getResult($algo)[1] ;
	}


	public function getLoser ($substitution = false)
	{
		if ( $substitution )
		{			
			if ($substitution === true)
				{$substitution = $this->_Method ;}
			
			if ( self::isAuthMethod($substitution) )
				{$algo = $substitution ;}
			else
				{throw new namespace\CondorcetException(9,$substitution);}
		}
		else
			{$algo = 'Condorcet_Basic';}

			//////

		$result = $this->getResult($algo) ;

		return $result[count($result)] ;
	}


	public function getResultStats ($method = null)
	{
		// Method
		$this->setMethod() ;
		// Prepare
		$this->prepareResult() ;

			//////

		if ($method === null)
		{
			$this->initResult($this->_Method) ;

			$stats = $this->_Calculator[$this->_Method]->getStats() ;
		}
		elseif (self::isAuthMethod($method))
		{
			$this->initResult($method) ;

			$stats = $this->_Calculator[$method]->getStats() ;
		}
		else
		{
			throw new namespace\CondorcetException(8) ;
		}

		if (!is_null($stats))
			{ return $stats ; }
		else
			{ return $this->getPairwise(); }
	}



	//:: TOOLS FOR RESULT PROCESS :://


	// Prepare to compute results & caching system
	protected function prepareResult ()
	{
		if ($this->_State > 2)
		{
			return false ;
		}
		elseif ($this->_State === 2)
		{
			$this->cleanupResult();

			// Do Pairewise
			$this->doPairwise() ;

			// Change state to result
			$this->_State = 3 ;

			// Return
			return true ;
		}
		else
		{
			throw new namespace\CondorcetException(6) ;
		}
	}


	protected function initResult ($method)
	{
		if ( !isset($this->_Calculator[$method]) )
		{
			$param['_Pairwise'] = $this->_Pairwise ;
			$param['_CandidatesCount'] = $this->_CandidatesCount ;
			$param['_Candidates'] = $this->_Candidates ;
			$param['_Votes'] = $this->_Votes ;

			$class = __NAMESPACE__.'\\'.$method ;
			$this->_Calculator[$method] = new $class($param) ;
		}
	}


	// Cleanup results to compute again with new votes
	protected function cleanupResult ()
	{
		// Reset state
		if ($this->_State > 2)
		{
			$this->_State = 2 ;
		}

			//////

		// Clean pairwise
		$this->_Pairwise = null ;

		// Algos
		$this->_Calculator = null ;
	}


	//:: GET RAW DATA :://

	public function getPairwise ()
	{
		$this->prepareResult() ;

		$explicit_pairwise = array() ;

		foreach ($this->_Pairwise as $candidate_key => $candidate_value)
		{
			$candidate_key = $this->getCandidateId($candidate_key) ;
			
			foreach ($candidate_value as $mode => $mode_value)
			{
				foreach ($mode_value as $candidate_list_key => $candidate_list_value)
				{
					$explicit_pairwise[$candidate_key][$mode][$this->getCandidateId($candidate_list_key)] = $candidate_list_value ;
				}
			}
		}

		return $explicit_pairwise ;
	}



/////////// PROCESS RESULT ///////////


	//:: COMPUTE PAIRWISE :://

	protected function doPairwise ()
	{		
		$timer_start = microtime(true);

		$this->_Pairwise = array() ;

		foreach ( $this->_Candidates as $candidate_key => $candidate_id )
		{
			$this->_Pairwise[$candidate_key] = array( 'win' => array(), 'null' => array(), 'lose' => array() ) ;

			foreach ( $this->_Candidates as $candidate_key_r => $candidate_id_r )
			{
				if ($candidate_key_r != $candidate_key)
				{
					$this->_Pairwise[$candidate_key]['win'][$candidate_key_r]	= 0 ;
					$this->_Pairwise[$candidate_key]['null'][$candidate_key_r]	= 0 ;
					$this->_Pairwise[$candidate_key]['lose'][$candidate_key_r]	= 0 ;
				}
			}
		}

		// Win && Null
		foreach ( $this->_Votes as $vote_id => $vote_ranking )
		{
			// Del vote identifiant
			unset($vote_ranking['tag']) ;

			$done_Candidates = array() ;

			foreach ($vote_ranking as $candidates_in_rank)
			{
				$candidates_in_rank_keys = array() ;

				foreach ($candidates_in_rank as $candidate)
				{
					$candidates_in_rank_keys[] = $this->getCandidateKey($candidate) ;
				}

				foreach ($candidates_in_rank as $candidate)
				{
					$candidate_key = $this->getCandidateKey($candidate);

					// Process
					foreach ( $this->_Candidates as $g_candidate_key => $g_CandidateId )
					{
						// Win
						if (	$candidate_key !== $g_candidate_key && 
								!in_array($g_candidate_key, $done_Candidates, true) && 
								!in_array($g_candidate_key, $candidates_in_rank_keys, true)
							)
						{
							$this->_Pairwise[$candidate_key]['win'][$g_candidate_key]++ ;

							$done_Candidates[] = $candidate_key ;
						}

						// Null
						if (	$candidate_key !== $g_candidate_key &&
								count($candidates_in_rank) > 1 &&
								in_array($g_candidate_key, $candidates_in_rank_keys)
							)
						{
							$this->_Pairwise[$candidate_key]['null'][$g_candidate_key]++ ;
						}
					}
				}
			}
		}

		// Loose
		foreach ( $this->_Pairwise as $option_key => $option_results )
		{
			foreach ($option_results['win'] as $option_compare_key => $option_compare_value)
			{
				$this->_Pairwise[$option_key]['lose'][$option_compare_key] = $this->countVotes() - (
							$this->_Pairwise[$option_key]['win'][$option_compare_key] + 
							$this->_Pairwise[$option_key]['null'][$option_compare_key]
						) ;
			}
		}

		$this->setTimer($timer_start);
	}



/////////// TOOLS FOR MODULAR ALGORITHMS ///////////


	public static function makeStatic_PairwiseComparison (&$pairwise)
	{
		$comparison = array();

		foreach ($pairwise as $candidate_key => $candidate_data)
		{
			$comparison[$candidate_key]['win'] = 0 ;
			$comparison[$candidate_key]['null'] = 0 ;
			$comparison[$candidate_key]['lose'] = 0 ;
			$comparison[$candidate_key]['balance'] = 0 ;
			$comparison[$candidate_key]['worst_defeat'] = 0 ;

			foreach ($candidate_data['win'] as $opponenent['key'] => $opponenent['lose']) 
			{
				if ( $opponenent['lose'] > $candidate_data['lose'][$opponenent['key']] )
				{
					$comparison[$candidate_key]['win']++ ;
					$comparison[$candidate_key]['balance']++ ;
				}
				elseif ( $opponenent['lose'] === $candidate_data['lose'][$opponenent['key']] )
				{
					$comparison[$candidate_key]['null']++ ;
				}
				else
				{
					$comparison[$candidate_key]['lose']++ ;
					$comparison[$candidate_key]['balance']-- ;

					// Worst defeat
					if ($comparison[$candidate_key]['worst_defeat'] < $candidate_data['lose'][$opponenent['key']])
					{
						$comparison[$candidate_key]['worst_defeat'] = $candidate_data['lose'][$opponenent['key']] ;
					}
				}
			}
		}

		return $comparison ;
	}

	public static function makeStatic_PairwiseSort (&$pairwise)
	{
		$comparison = self::makeStatic_PairwiseComparison($pairwise);

		$score = array() ;	

		foreach ($pairwise as $candidate_key => $candidate_value)
		{
			foreach ($candidate_value['win'] as $challenger_key => $challenger_value)
			{
				if ($challenger_value > $candidate_value['lose'][$challenger_key])
				{
					$score[$candidate_key.'>'.$challenger_key]['score'] = $challenger_value ;
					$score[$candidate_key.'>'.$challenger_key]['minority'] = $candidate_value['lose'][$challenger_key] ;
					$score[$candidate_key.'>'.$challenger_key]['margin'] = $candidate_value['win'][$challenger_key] - $candidate_value['lose'][$challenger_key] ;
				}
				elseif ( $challenger_value === $candidate_value['lose'][$challenger_key] && !isset($score[$challenger_key.'>'.$candidate_key]) )
				{
					if ($comparison[$candidate_key]['worst_defeat'] <= $comparison[$challenger_key]['worst_defeat'])
					{
						$score[$candidate_key.'>'.$challenger_key]['score'] = 0.1 ;
						$score[$candidate_key.'>'.$challenger_key]['minority'] = $candidate_value['lose'][$challenger_key] ;
						$score[$candidate_key.'>'.$challenger_key]['margin'] = $candidate_value['win'][$challenger_key] - $candidate_value['lose'][$challenger_key] ;
					}
				}
			}
		}

		uasort($score, function ($a, $b){
			if ($a['score'] < $b['score']) {return 1;} elseif ($a['score'] > $b['score']) {return -1 ;}
			elseif ($a['score'] === $b['score'])
			{
				if ($a['minority'] > $b['minority'])
					{ return 1 ; }
				elseif ($a['minority'] < $b['minority'])
					{ return -1 ; }
				elseif ($a['minority'] === $b['minority'])
					{ 
						if ($a['margin'] < $b['margin'])
							{ return 1 ; }
						elseif ($a['margin'] > $b['margin'])
							{ return -1 ; }
						else
							{ return 0 ; }
					}
			}
		});

		return $score ;
	}

}

// Interface with the aim of verifying the good modular implementation of algorithms.
interface Condorcet_Algo
{
	public function getResult($options);
	public function getStats();
}

// Custom Exeption
class CondorcetException extends \Exception
{
	protected $_infos ;

	public function __construct ($code = 0, $infos = '')
	{
		$this->_infos = $infos ;

		parent::__construct($this->correspondence($code), $code);
	}

	public function __toString ()
	{
		   return __CLASS__ . ": [{$this->code}]: {$this->message} (line: {$this->file}:{$this->line})\n";
	}

	protected function correspondence ($code)
	{
		$error[1] = 'Bad candidate format';
		$error[2] = 'The voting process has already started';
		$error[3] = 'This candidate ID is already registered';
		$error[4] = 'This candidate ID do not exist';
		$error[5] = 'Bad vote format';
		$error[6] = 'You need to specify votes before results';
		$error[7] = 'Your Candidate ID is too long > ' . namespace\Condorcet::MAX_LENGTH_CANDIDATE_ID;
		$error[8] = 'This method do not exist';
		$error[9] = 'The algo class you want has not been defined';
		$error[10] = 'The algo class you want is not correct';
		$error[11] = 'You try to unserialize an object version older than your actual Class version. This is a problematic thing';
		$error[12] = 'You have exceeded the number of votes allowed for this method.';
		$error[13] = 'Formatting error: You do not multiply by a number!';
		$error[14] = 'parseVote() must take a string (raw or path) as argument';
		$error[15] = 'Input must be valid Json format';

		if ( array_key_exists($code, $error) )
		{
			return $error[$code];
		}
		else
		{
			return (!is_null($this->_infos)) ? $this->_infos : 'Mysterious Error' ;
		}
	}
}
