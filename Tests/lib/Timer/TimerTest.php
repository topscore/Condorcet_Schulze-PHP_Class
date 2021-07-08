<?php
declare(strict_types=1);

namespace CondorcetPHP\Condorcet\Tests\Timer;

use CondorcetPHP\Condorcet\Timer\{Manager, Chrono};
use PHPUnit\Framework\TestCase;

class TimerTest extends TestCase
{
    public function testInvalidChrono () : void
    {
        $this->expectException(\CondorcetPHP\Condorcet\Throwable\CondorcetException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Only chrono linked to this Manager can be used');

        $manager1 = new Manager;
        $manager2 = new Manager;

        $chrono1 = new Chrono ($manager1);
        $chrono2 = new Chrono ($manager2);

        $manager1->addTime($chrono1);
        $manager1->addTime($chrono2);
    }
}