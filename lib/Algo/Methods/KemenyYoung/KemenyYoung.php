<?php
/*
    Part of KEMENY–YOUNG method Module - From the original Condorcet PHP

    Condorcet PHP - Election manager and results calculator.
    Designed for the Condorcet method. Integrating a large number of algorithms extending Condorcet. Expandable for all types of voting systems.

    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

namespace CondorcetPHP\Condorcet\Algo\Methods\KemenyYoung;

use CondorcetPHP\Condorcet\Dev\CondorcetDocumentationGenerator\CondorcetDocAttributes\{Description, Example, FunctionReturn, PublicAPI, Related};
use CondorcetPHP\Condorcet\Result;
use CondorcetPHP\Condorcet\Algo\{Method, MethodInterface};
use CondorcetPHP\Condorcet\Algo\Tools\Permutation;

// Kemeny-Young is a Condorcet Algorithm | http://en.wikipedia.org/wiki/Kemeny%E2%80%93Young_method
class KemenyYoung extends Method implements MethodInterface
{
    // Method Name
    public const METHOD_NAME = ['Kemeny–Young','Kemeny-Young','Kemeny Young','KemenyYoung','Kemeny rule','VoteFair popularity ranking','Maximum Likelihood Method','Median Relation'];

    // Method Name
    public const CONFLICT_WARNING_CODE = 42;

    // Limits
        /* If you need to put it on 9, You must use \ini_set('memory_limit','1024M'); before. The first use will be slower because Kemeny-Young will work without pre-calculated data of Permutations.
        Do not try to go to 10, it is not viable! */
        public static ?int $MaxCandidates = 8;

    // Cache
    public static bool $devWriteCache = false;

    // Kemeny Young
    protected array $_PossibleRanking = [];
    protected array $_RankingScore = [];


/////////// PUBLIC ///////////


    // Get the Kemeny ranking
    public function getResult () : Result
    {
        // Cache
        if ( $this->_Result === null ) :
            $this->calcPossibleRanking();
            $this->calcRankingScore();
            $this->makeRanking();
            $this->conflictInfos();
        endif;

        // Return
        return $this->_Result;
    }


    protected function getStats () : array
    {
        $explicit = [];

        foreach ($this->_PossibleRanking as $key => $value) :
            $explicit[$key] = $value;

            // Human readable
            foreach ($explicit[$key] as &$candidate_key) :
                $candidate_key = $this->_selfElection->getCandidateObjectFromKey($candidate_key)->getName();
            endforeach;

            $explicit[$key]['score'] = $this->_RankingScore[$key];
        endforeach;

        $stats = [];
        $stats['bestScore'] = \max($this->_RankingScore);
        $stats['rankingScore'] = $explicit;

        return $stats;
    }

        protected function conflictInfos () : void
        {
            $max = \max($this->_RankingScore);

            $conflict = -1;
            foreach ($this->_RankingScore as $value) :
                if ($value === $max) :
                    $conflict++;
                endif;
            endforeach;

            if ($conflict > 0)  :
                $this->_Result->addWarning(
                    type: self::CONFLICT_WARNING_CODE,
                    msg: ($conflict + 1).';'.\max($this->_RankingScore)
                );
            endif;
        }


/////////// COMPUTE ///////////


    //:: Kemeny-Young ALGORITHM. :://

    protected function calcPossibleRanking () : void
    {
        $i = 0;
        $search = [];
        $replace = [];

        foreach ($this->_selfElection->getCandidatesList() as $candidate_id => $candidate_name) :
            $search[] = $i++;
            $replace[] = $candidate_id;
        endforeach;

        $path = __DIR__ . '/KemenyYoung-Data/'.$this->_selfElection->countCandidates().'.data';

        // But ... where are the data ?! Okay, old way now...
        if (self::$devWriteCache || !\file_exists($path)) :
            (new Permutation ($this->_selfElection->countCandidates()))->writeResults($path);
        endif;

        // Read Cache & Compute
        $f = fopen($path, 'r');

        while ( ($oneResult = fgets($f)) !== false ) :
            $oneResult = explode(',', \str_replace($search, $replace, $oneResult));

            $resultToRegister = [];
            $rank = 1;
            foreach($oneResult as $oneCandidate) :
                $resultToRegister[$rank++] = (int) $oneCandidate;
            endforeach;

            $this->_PossibleRanking[] = $resultToRegister;
        endwhile;

        fclose($f);
    }

    protected function calcRankingScore () : void
    {
        $pairwise = $this->_selfElection->getPairwise();

        foreach ($this->_PossibleRanking as $keyScore => $ranking) :
            $this->_RankingScore[$keyScore] = 0;

            $do = [];

            foreach ($ranking as $candidateId) :
                $do[] = $candidateId;

                foreach ($ranking as $rankCandidate) :
                    if (!\in_array(needle: $rankCandidate, haystack: $do, strict: true)) :
                        $this->_RankingScore[$keyScore] += $pairwise[$candidateId]['win'][$rankCandidate];
                    endif;
                endforeach;
            endforeach;
        endforeach;
    }


    /*
    I do not know how in the very unlikely event that several possible classifications have the same highest score.
    In the current state, one of them is chosen arbitrarily.

    See issue on Github : https://github.com/julien-boudry/Condorcet/issues/6
    */
    protected function makeRanking () : void
    {
        $this->_Result = $this->createResult($this->_PossibleRanking[ \array_search(needle: \max($this->_RankingScore), haystack: $this->_RankingScore, strict: true) ]);
    }
}
