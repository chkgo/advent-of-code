<?php
/*
 * https://adventofcode.com/2025/day/10
 *
 * call like this:
 * php aoc2025-day10-code.php -verbose < input.txt
 *
 * -verbose arg is quite excessive. better use it for individual machines (not the entire input)
 */

require_once('Day10Machine.php');

$verbose = ($argv[1] ?? '') == '-verbose';

$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
$total_seconds = 0;

// - read machines config from input
$machines = 0;
do
{
    echo "======= MACHINE: ", ++$machines, " =======\n";
    $cfg = trim(fgets($src));
    if ($cfg)
    {
        echo $cfg;
        try
        {
            $machine = new Day10Machine($cfg);
        }
        catch (Exception $e)
        {
            echo "\t> ", $e->getMessage(), PHP_EOL;
        }
        echo "\t> \n";

        $machine->verbose = $verbose;

        // - first part
        $sub_res1 = $machine->setIndicators();
        $res1+=$sub_res1;
        echo "(1) $sub_res1\n";

        // - second part
        $start = microtime(true);

        // - build source matrix basing
        $matrix = $machine->buildMatrix();
        $machine->prnMatrix($matrix,'SrcMatrix', force:true);

        // - do all the necessary matrix transformations and calculations
        $press_log = $machine->eliminateMatrix($matrix);
        $seconds = microtime(true) - $start;
        $total_seconds += $seconds;
        $res = array_sum($press_log);
        $res2 += $res;
        echo "\n(2) $res! ($seconds sec.):\n";

        echo "\tMax: ", json_encode($machine->max_button_press), PHP_EOL;
        echo "\tRes: ", json_encode($press_log), PHP_EOL;
        echo "\tExp: ", json_encode($machine->joltage_aim), PHP_EOL;

        $check = $machine->pressAllButtons($press_log);
        echo "\tAct: ", json_encode($check), PHP_EOL;
        if ($machine->joltage_aim != $check)
        {
            echo "\n UNEXPECTED RESULT\n";
            break;
        }
    }
}
while (!feof($src) && !empty($cfg));

echo "\nRES: $res1, $res2 ($total_seconds sec.)\n";