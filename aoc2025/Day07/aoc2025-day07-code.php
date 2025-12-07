<?php
/*
 * https://adventofcode.com/2025/day/7
 *
 * call like this:
 * php aoc2025-day07-code.php < input.txt
 */

$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
$prev_beams = [];
do
{
    $line = trim(fgets($src));
    if ($line)
    {
        $beams = [];
        for ($i = 0; $i < strlen($line); $i++)
        {
            // - the entrance of the first beam
            if ($line[$i] == 'S')
            {
                $beams[$i] = 1;
            }
            else
            {
                // - increase the number of beams with the value coming from above, if any
                $beams[$i] = ($beams[$i] ?? 0) + ($prev_beams[$i] ?? 0);

                // - if there's a splitter in the current cell
                if ($line[$i] == '^')
                {
                    // - splitter increases the cells to the left and to the right. if there's a beam coming from above
                    if (($prev_beams[$i] ?? 0) > 0) {
                        $res1++; // - count any split of existing beam(s)
                        $beams[$i - 1] = ($beams[$i - 1] ?? 0) + $prev_beams[$i];
                        $beams[$i + 1] = ($beams[$i + 1] ?? 0) + $prev_beams[$i];
                    }
                    // - current cell becomes zero (splitter does not pass the beam down)
                    $beams[$i] = 0;
                }
            }
        }

        $prev_beams = $beams;
    }
}
while (!feof($src));

// - count the total beams number in the bottom
$res2 = array_sum($beams);

echo "RES: $res1, $res2\n";
