<?php
/*
 * https://adventofcode.com/2025/day/6
 *
 * call like this:
 * php aoc2025-day06-code.php < input.txt
 */

$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;

// - read all input lines
$math_lines = [];
$max_len = 0;
do
{
    $str = fgets($src);
    if (!empty(trim($str)))
    {
        $math_lines[] = $str;
    }
    $max_len = max(strlen($str), $max_len);
}
while (!feof($src));

// - the last line contains operations to apply
$operations_line = array_pop($math_lines);

/**
 * Removes empty array items and trims others
 *
 * @param array $operands strings of digits. may be empty or contain a leading or trailing spaces
 * @return void
 */
function cleanUpOperands(array &$operands): void
{
    foreach ($operands as $idx => $operand)
    {
        $num = trim($operand);
        if ($num == '')
        {
            unset($operands[$idx]);
        }
        else
        {
            $operands[$idx] = (int)$num;
        }
    }
}

/**
 * Reads numbers either vertically or horizontally from the given rows, and applies the given operation
 *
 * @param array  $rows          rows of digits to read from
 * @param string $operation     operation to apply (*|+)
 * @param bool   $calc_vertical whether to read digits vertically or not
 * @return int either the sum or the product
 */
function calc(array $rows, string $operation, bool $calc_vertical): int
{
    if (empty(trim($operation)) || empty($rows))
    {
        return 0;
    }

    // - no transformations needed for the horizontal calculation (the first part)
    if (!$calc_vertical)
    {
        cleanUpOperands($rows);
        return trim($operation) == '*' ? array_product($rows) : array_sum($rows);
    }

    // - build vertical numbers
    $op_num = strlen($rows[0]) - 1;
    $numbers = [];
    for ($i = 0; $i <= $op_num; $i++)
    {
        foreach ($rows as $row)
        {
            $numbers[$i] = ($numbers[$i] ?? '') . (is_numeric($row[$i] ?? '') ? $row[$i] : '');
        }
    }

    cleanUpOperands($numbers);
    return trim($operation) == '*' ? array_product($numbers) : array_sum($numbers);
}

// - read whole data from left to right till the longest line is processed in full
$operation = '';
$operands = [];
for ($i = 0; $i < $max_len; $i++)
{
    // - we assume that operation char starts a new section
    if (isset($operations_line[$i]) && in_array($operations_line[$i], ['*', '+']))
    {
        // - the previous section (if any) can be calculated
        if ($operation)
        {
            $res1 += calc($operands, $operation, false);
            $res2 += calc($operands, $operation, true);
        }

        // - start the next section
        $operands = [];
        $operation = $operations_line[$i];
    }

    // - append digits from the current data column to the current section
    foreach ($math_lines as $idx => $line)
    {
        $operands[$idx] = ($operands[$idx] ?? '') . ($line[$i] ?? '');
    }
}

// - calculate the last collected section
$res1 += calc($operands, $operation, false);
$res2 += calc($operands, $operation, true);

echo "RES: $res1, $res2\n";