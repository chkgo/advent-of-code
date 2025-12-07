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

$beams = [];
$row = 0;
do
{
    $line = trim(fgets($src));
    if ($line)
    {
        for ($i = 0; $i < strlen($line); $i++)
        {
            // - the entrance of the first beam
            if ($line[$i] == 'S')
            {
                $beams[$row][$i] = 1;
            }
            else
            {
                // - take number of beams from the cell above, if any
                $beams[$row][$i] = ($beams[$row][$i] ?? 0) + ($beams[$row - 1][$i] ?? 0);

                // - if there's a splitter in the current cell
                if ($line[$i] == '^')
                {
                    // - splitter increases the cells to the left and to the right. if there's a beam coming from above
                    if ($beams[$row - 1][$i] > 0) {
                        $res1++; // - count any split of existing beam(s)
                        $beams[$row][$i - 1] = ($beams[$row][$i - 1] ?? 0) + $beams[$row - 1][$i];
                        $beams[$row][$i + 1] = ($beams[$row][$i + 1] ?? 0) + $beams[$row - 1][$i];
                    }
                    // - current cell becomes zero (splitter does not pass the beam down)
                    $beams[$row][$i] = 0;
                }
            }
        }

        $row++;
    }
}
while (!feof($src));

// - count the total beams number in the bottom
$res2 = array_sum($beams[count($beams) - 1]);

echo "RES: $res1, $res2\n";
