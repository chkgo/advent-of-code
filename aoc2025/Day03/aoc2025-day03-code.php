<?php
/*
 * https://adventofcode.com/2025/day/3
 *
 * call like this:
 * php aoc2025-day03-code.php < input.txt
 */

/**
 * Fetch a maximum joltage battery pack from given bank
 *
 * @param string $bank_str      battery bank str
 * @param int    $batteries_num a number of batteries to use
 * @return string
 */
function getMaxJoltage(string $bank_str, int $batteries_num): string
{
    if (!empty($bank_str) && $batteries_num)
    {
        for ($digit = 9; $digit > 0; $digit--)
        {
            $pos = strpos($bank_str, (string)$digit);
            // - if digit not found, or the remaining bank (to the right ) is not long enough, go check a smaller digit
            if ($pos !== false && strlen($bank_str) - $pos >= $batteries_num)
            {
                // - use the found digit, appending it with the best batteries combination from the remaining bank (to the right)
                return $digit . getMaxJoltage(substr($bank_str, $pos + 1), $batteries_num - 1);
            }
        }
    }

    return '';
}

// - read source data from STDIN
$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
do
{
    $str = trim(fgets($src)) ?: '';
    $res1 += (int)getMaxJoltage($str, 2);
    $res2 += (int)getMaxJoltage($str, 12);
}
while (!feof($src));

echo "RES: $res1, $res2\n";
