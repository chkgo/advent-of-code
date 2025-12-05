<?php
/*
 * https://adventofcode.com/2025/day/5
 *
 * call like this:
 * php aoc2025-day05-code.php < input.txt
 */

$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
$filling_ranges = true;
$fresh_ids = [];

/**
 * Compare two large numbers presented in string format
 *
 * @param string $a a potentially large number
 * @param string $b another large number
 * @return int comparison result
 */
function matchLargeNumbers(string $a, string $b): int
{
    if (strlen($a) == strlen($b))
    {
        return ($a == $b) ? 0 : strcmp($a, $b);
    }

    return strlen($a) - strlen($b);
}

do
{
    $src_str = trim(fgets($src));

    // - stop filling ranges (will collect ids after that)
    if (empty($src_str) && $filling_ranges)
    {
        $filling_ranges = false;
        // - sort ranges by starting id
        usort($fresh_ids, function (array $a, array $b)
        {
            return matchLargeNumbers($a[0], $b[0]);
        });
        continue;
    }

    // - fill id-ranges from the task input
    if ($filling_ranges && preg_match('/^(\d+)-(\d+)/', $src_str, $matches))
    {
        $fresh_ids[] = [$matches[1], $matches[2]];
    }
    // - known fresh ids follow the empty line
    elseif (!$filling_ranges && is_numeric($src_str))
    {
        // - check if fresh id falls into any fresh ids range
        foreach ($fresh_ids as [$start, $end])
        {
            if ($src_str >= $start && $src_str <= $end)
            {
                $res1++;
                break;
            }
        }
    }
}
while (!feof($src));

// - combine fresh ids ranges so they don't intersect
$prev_range = [];
foreach ($fresh_ids as $range)
{
    // - take first range on start
    if (empty($prev_range))
    {
        $prev_range = $range;
        continue;
    }
    // - a current range entirely fits into the previous range
    if (matchLargeNumbers($range[0], $prev_range[0]) >= 0 && matchLargeNumbers($range[1], $prev_range[1]) <= 0)
    {
        continue;
    }
    // - there's a gap between the previous range and the current one
    if (matchLargeNumbers($range[0], $prev_range[1]) > 0)
    {
        // - count all ids from the previous range (inclusive)
        $res2 += $prev_range[1] - $prev_range[0] + 1; // (i) PHP proves to successfully add/subtract this large numbers
        // - stop extending the previous range
        $prev_range = $range;
        continue;
    }
    // - ranges intersect. extend the previous range so that the current one would fit into it
    $prev_range[1] = $range[1];
}

// - count the last range ids
$res2 += $prev_range[1] - $prev_range[0] + 1;

echo "RES: $res1, $res2\n";