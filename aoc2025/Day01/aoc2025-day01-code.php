<?php
/*
 * https://adventofcode.com/2025/day/1
 *
 * call like this:
 * php aoc2025-day01-code.php < input.txt
 */

$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
$pos = 50;
do
{
    if (preg_match('/^([LR])(\d+)/', trim(fgets($src)), $matches))
    {
        $prev_pos = $pos;
        $pos += ($matches[1] == 'L' ? -1 : 1) * $matches[2]; // - apply the rotation
        $res2 += (int)($pos <= 0 && $prev_pos > 0) + abs(intdiv($pos, 100)); // - run back thru/to 0 + whole rotations
        $pos %= 100; // - get rid of overflow
        $pos += 100 * (int)($pos < 0); // - handle negative resulting pos
        $res1 += (int)($pos == 0); // - zero final pos
    }
}
while (!feof($src));
echo "RES: $res1, $res2\n";
