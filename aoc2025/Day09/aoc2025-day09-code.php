<?php
/*
 * https://adventofcode.com/2025/day/9
 *
 * call like this:
 * php aoc2025-day09-code.php < input.txt
 */

$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
$max_w = 0;
$max_h = 0;
$tiles = []; // - coordinates of all  tiles
$couples = [[],[]]; // - horizontal and vertical links
$starting_key = null; // - a key to start walking around horizontally
$green_to_the_right = null; // - whether a green side is to the right, as we move around

$moving_ver = null;
$prev_tile = null;
$idx = 0;
do
{
    if (preg_match('/^(\d+),(\d+)/', trim(fgets($src)), $matches))
    {
        $min_y = isset($min_y) ? min($min_y, $matches[2]) : $matches[2];
        $max_w = max($max_w, $matches[1]);
        $max_h = max($max_h, $matches[2]);

        $tile = [(int)$matches[1], (int)$matches[2]];
        $tiles[$idx] = $tile;

        // - unless it's the first tile (for the first one we cannot know the direction), set up tile links (one vertical and one horizontal)
        if ($idx)
        {
            $prev_tile = $tiles[$idx - 1];
            $moving_ver = isset($moving_ver) ? !$moving_ver : $prev_tile[0] == $tile[0];
            $couples[(int)$moving_ver][$idx] = $idx - 1;
            $couples[(int)(!$moving_ver)][$idx] = $idx + 1;
        }
        $idx++;
    }
}
while (!feof($src));
$idx--;

// - connect first and last couples
$couples[(int)!$moving_ver][$idx] = 0;
$couples[(int)!$moving_ver][0] = $idx;
$couples[(int)$moving_ver][0] = 1;

// - find starting (topmost) horizontal pair, and detect the green (inner) side of the path
foreach ($couples[0] as $idx => $to_idx)
{
    $tile = $tiles[$idx];
    if ($tile[1] == $min_y)
    {
        $to_tile = $tiles[$to_idx];
        $starting_key = $idx;
        $green_to_the_right = $tile[0] < $to_tile[0];
    }
}


$svg_w = $max_w + 3;
$svg_h = $max_h + 3;
$svg = <<<EOL
<?xml version="1.0" standalone="no"?>
<svg width="30cm" height="30cm" viewBox="0 0 $svg_w $svg_h" xmlns="http://www.w3.org/2000/svg">

EOL;

foreach ($tiles as $key => $t1)
{
    $svg_x = $t1[0];
    $svg_y = $t1[1];
    $svg_r = $svg_w / 200;
    $svg .= <<<EOL
<circle cx="$svg_x" cy="$svg_y" r="$svg_r" fill="red"/>

EOL;
}

/**
 * Check if the box is being crossed with the line (either vertical or horizontal) defined as another box
 * Returns a bitmask for spoiling the first task result (&1) and the second one (&2)
 *
 * @param array $box1 the box [$left, $right, $top, $bottom]
 * @param array $box2 either vertical or horizontal 1 tile wide defined as the box [$left, $right, $top, $bottom]
 * @return int check the spoil as &1 for first task, &2 for second task
 */
function checkIfIntersect(array $box1, array $box2): int
{
    [$left, $right, $top, $bottom] = $box1;
    [$_left, $_right, $_top, $_bottom] = $box2;

    // - if any corner of box2 falls into box1, it means both tasks fail
    if (
        ($_top > $top && $_top < $bottom || $_bottom > $top && $_bottom < $bottom) &&
        ($_left > $left && $_left < $right || $_right > $left && $_right < $right)
    )
    {
        return 3;
    }

    // - if any edge of box2 crosses box1, the second task fail
    if (
        ($_left == $_right) && ($_left>$left && $_left<$right) && ($_top<=$top && $_bottom>=$bottom) || // v
        ($_top == $_bottom) && ($_top>$top && $_top<$bottom) && ($_left<=$left && $_right>=$right) // h
    )
    {
        return 2;
    }

    return 0;
}

/**
 * Check if the box is being touched by the line defined as starting and ending points
 * (direction is important as we need to know which side of the line is green... is the box on a green side or not)
 *
 * (i) Expanded all conditions to make the logic more transparent.
 *
 * @param array $box1 the box [$left, $right, $top, $bottom]
 * @param array $from starting point [$x, $y]
 * @param array $to   ending point [$x, $y]
 * @return bool|null whether the box is on a green side (null if the line cannot help detecting the box position)
 */
function checkIfLineOverlapsTheEdge(array $box1, array $from, array $to): ?bool
{
    global $green_to_the_right;
    [$left, $right, $top, $bottom] = $box1;

    // - vertical path
    if ($from[0] == $to[0])
    {
        // - left border match
        if ($from[0] == $left)
        {
            // - downwards
            if ($from[1] == $top && $to[1] > $from[1])
            {
                return !$green_to_the_right;
            }
            // - upwards
            elseif ($from[1] == $bottom && $to[1] < $from[1])
            {
                return $green_to_the_right;
            }
        }
        // - right border match
        elseif ($from[0] == $right)
        {
            // - downwards
            if ($from[1] == $top && $to[1] > $from[1])
            {
                return $green_to_the_right;
            }
            // - upwards
            elseif ($from[1] == $bottom && $to[1] < $from[1])
            {
                return !$green_to_the_right;
            }
        }
    }
    // - horizontal path
    elseif ($from[1] == $to[1])
    {
        // - bottom border
        if ($from[1] == $bottom)
        {
            // - to the right
            if ($from[0] == $left && $from[0] < $to[0])
            {
                return !$green_to_the_right;
            }
            // - to the left
            elseif ($from[0] == $right && $from[0] > $to[0])
            {
                return $green_to_the_right;
            }
        }
        // - top border
        elseif ($from[1] == $top)
        {
            // - to the right
            if ($from[0] == $left && $from[0] < $to[0])
            {
                return $green_to_the_right;
            }
            // - to the left
            elseif ($from[0] == $right && $from[0] > $to[0])
            {
                return !$green_to_the_right;
            }
        }
    }

    return null;
}


// - build svg path
$coords = [];
$idx = $starting_key;
$go_vert = false;
do
{
    $tile = $tiles[$idx];
    $next_idx = $couples[(int)$go_vert][$idx];
    $next_tile = $tiles[$next_idx];
    $coords[] = "{$tile[0]} {$tile[1]}";
    $idx = $next_idx;
    $go_vert = !$go_vert;
}
while ($idx != $starting_key);
$coords[] = "{$next_tile[0]} {$next_tile[1]}";
$svg_stroke = round($svg_w / 300, 2);
$svg .= '<path d="M ' . implode(' L ', $coords) . ' z" fill="none" stroke="blue" stroke-width="' . $svg_stroke . '" />' . PHP_EOL;


// - check every couple of tiles, and find the largest rectangle in accordance with task rules
$max_s = 0;
$pair = null;
$max_sg = 0;
$pair_g = null;
foreach ($tiles as $i => $t1)
{
    foreach ($tiles as $j => $t2)
    {
        if ($i == $j)
        {
            continue;
        }
        $left = min($t1[0], $t2[0]);
        $right = max($t1[0], $t2[0]);
        $top = min($t1[1], $t2[1]);
        $bottom = max($t1[1], $t2[1]);

        // - initially the box satisfies both tasks comparison, until we reject it
        $good1 = true;
        $good2 = true;

        // - run along the surrounding path, making sure we don't cross the rect
        $idx = $starting_key;
        $go_vert = false;
        do
        {
            $tile = $tiles[$idx];
            $next_idx = $couples[(int)$go_vert][$idx];
            $next_tile = $tiles[$next_idx];
            if (!isset($tile) || !isset($next_tile))
            {
                echo "tile: $idx, next_tile: $next_idx\n";
                die;
            }

            $_left = min($tile[0], $next_tile[0]);
            $_right = max($tile[0], $next_tile[0]);
            $_top = min($tile[1], $next_tile[1]);
            $_bottom = max($tile[1], $next_tile[1]);

            $intersection = checkIfIntersect(
                [$left, $right, $top, $bottom],
                [$_left, $_right, $_top, $_bottom]
            );

            if ($good1 && ($intersection & 1))
            {
                $good1 = false;
            }

            if ($good2 && ($intersection & 2))
            {
                $good2 = false;
            }
            else
            {
                // - make sure that rect is located on a green side of the path (surrounding path goes around our rect).
                // if tile matches either t1 or t2 and the next tile is inlined with any edge of the rect, check if rect is on the green side
                $overlap = checkIfLineOverlapsTheEdge([$left, $right, $top, $bottom], $tile, $next_tile);

                // - wrong side
                if ($overlap === false)
                {
                    $good2 = false;
                }
            }
            
            $idx = $next_idx;
            $go_vert = !$go_vert;
        }
        while( $idx != $starting_key && ($good1 || $good2));

        // - if the box was not rejected, check if it's bigger than we found before
        if ($good1 || $good2)
        {
            $w = $right - $left + 1;
            $h = $bottom - $top + 1;
            $s = $w * $h;

            if ($good1 && $s > $max_s)
            {
                $max_s = $s;
                $pair = [$t1, $t2];
                $svg_first = [
                    "$left $top", "$right $top", "$right $bottom", "$left $bottom", "$left $top"
                ];
            }

            if ($good2 && $s > $max_sg)
            {
                $max_sg = $s;
                $pair_g = [$t1, $t2];
                $svg_final = [
                    "$left $top", "$right $top", "$right $bottom", "$left $bottom", "$left $top"
                ];
            }
        }
    }
}

$svg .= '<path d="M ' . implode(' L ', $svg_first ?? ['0 0']) .
    ' z" fill="none" stroke="yellow" stroke-dasharray="0 ' . $svg_stroke . ' 0" stroke-width="' . ($svg_stroke * .5) . '" />' . PHP_EOL;
$svg .= '<path d="M ' . implode(' L ', $svg_final ?? ['0 0']) .
    ' z" fill="none" stroke="green" stroke-dasharray="0 ' . $svg_stroke . ' 0" stroke-width="' . ($svg_stroke * .5) . '" />' . PHP_EOL .
    '</svg>';
file_put_contents('map.svg', $svg);

$res1 = $max_s;
$res2 = $max_sg;
echo "RES: $res1, $res2\n";
