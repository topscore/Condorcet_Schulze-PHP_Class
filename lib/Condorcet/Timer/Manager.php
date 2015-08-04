<?php
/*
    Condorcet PHP Class, with Schulze Methods and others !

    Version: 0.94

    By Julien Boudry - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
namespace Condorcet\Timer;

use Condorcet\CondorcetException;

class Manager
{
    protected $_globalTimer = 0.0;
    protected $_lastTimer;
    protected $_lastChronoTimestamp;

    public function addTime ( namespace\Chrono $chrono )
    {
        if ($chrono->getTimerManager() === $this) :
            $m = microtime(true);

            $c = ( $this->_lastChronoTimestamp > $chrono->getStart() ) ? $this->_lastChronoTimestamp : $chrono->getStart();

            $this->_lastTimer = $m - $c;
            $this->_globalTimer += $this->_lastTimer;

            $this->_lastChronoTimestamp = $m;
        else :
            throw new CondorcetException ('Only chrono linked to this Manager can be used');
        endif;
    }

    public function getGlobalTimer ($float = false)
    {
        return ($float) ? $this->_globalTimer : number_format($this->_globalTimer, 5);
    }

    public function getLastTimer ($float = false)
    {
        return ($float || $this->_lastTimer === null) ? $this->_lastTimer : number_format($this->_lastTimer, 5);
    }
}
