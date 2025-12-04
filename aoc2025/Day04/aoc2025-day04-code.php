<?php
/*
 * https://adventofcode.com/2025/day/4
 *
 * call like this:
 * php aoc2025-day04-code.php < input.txt
 */

// - read elevator lines input to array
$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
$elevator_lines = [];
while(!feof($src))
{
    $src_str = trim(fgets($src)) ?: '';
    if ($src_str)
    {
        $elevator_lines[] = $src_str;
    }
}

/**
 * Counts the untouched rolls (@) and the ones marked for further removal (x) in the given string.
 * @param string $str string to search rolls within
 * @return int
 */
function rollsWithin(string $str): int
{
    if (!preg_match_all("/([@x])/i", $str, $matches))
    {
        return 0;
    }
    return count($matches[0]);
}

$pass = 0;

// - proceed with removal
do
{
    $removed = 0;
    $prev_str = '';
    foreach ($elevator_lines as $row => $cur_str)
    {
        $next_str = $elevator_lines[$row + 1] ?? '';
        for ($col = 0; $col < strlen($cur_str); $col++)
        {
            // - if there's a roll standing at a current position
            if ($cur_str[$col] == '@')
            {
                // - mind leftmost position corrections
                $pos = $col ? $col-1 : 0;
                $len = $col ? 3 : 2;
                // - count rolls around the current position
                $rolls_around =
                    ($prev_str ? rollsWithin(substr($prev_str, $pos, $len)) : 0) +
                    rollsWithin(substr($cur_str, $pos, $len)) +
                    ($next_str ? rollsWithin(substr($next_str, $pos, $len)) : 0) - 1;
                if ($rolls_around < 4)
                {
                    // - mark current roll for removal
                    $elevator_lines[$row][$col] = 'x';
                    $removed++;
                    $res1 += (int)!$pass;
                }
            }
        }

        // - remove marked rolls from the previous line, if any (we no longer consider them in further counts)
        if ($prev_str)
        {
            $elevator_lines[$row - 1] = preg_replace('/x/', '.', $elevator_lines[$row - 1]);
        }
        $prev_str = $cur_str;
    }
    // - remove marked rolls from the last line
    $elevator_lines[count($elevator_lines) - 1] =
        preg_replace('/x/', '.', $elevator_lines[count($elevator_lines) - 1]);

    $res2 += $removed;
    $pass += (int)($removed > 0);
}
while ($removed);

echo "RES: $res1, $res2 (in $pass iterations)\n";