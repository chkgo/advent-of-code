<?php
/*
 * https://adventofcode.com/2025/day/2
 *
 * call like this:
 * php aoc2025-day03-code.php < input.txt
 */

// - read source data from STDIN
$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
$src_str = '';
do
{
    $src_str .= trim(fgets($src)) ?: '';
}
while (!feof($src));

// - fetch all the ranges
foreach (preg_split('/[,\s]+/', $src_str) as $chunk)
{
    // - proceed every valid range
    if (preg_match('/(\d+)-(\d+)/', $chunk, $matches))
    {
        $start = ltrim($matches[1],'0');
        $end = ltrim($matches[2], '0');
        // - run thru every id in the range
        for ($id = $start; $id <= $end; $id++)
        {
            $id_len = strlen((string)$id);
            $is_fake = false;
            $digits = 0;
            // - keep increasing the number of starting digits of the initial id to repeat
            // (only makes sense while no loner than a half of initial id)
            while (!$is_fake && (++$digits <= $id_len/2))
            {
                $id_part = substr((string)$id, 0, $digits);
                $check_id = $id_part;
                // - combine the starting digits until we reach the length of the id
                while (strlen($check_id) < $id_len)
                {
                    $check_id .= $id_part;
                }
                // - if the number built of starting digits equals the initial id, consider it fake
                if ($check_id === (string)$id)
                {
                    $is_fake = true;
                    $res1 += (int)(strlen($id_part) == $id_len / 2) * $id;
                }
            }
            $res2 += (int)$is_fake * $id;
        }
    }
}

echo "RES: $res1, $res2\n";
