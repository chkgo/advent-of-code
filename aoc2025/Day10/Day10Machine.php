<?php
/**
 * Advent Of Code 2025. Day 10 logic.
 *
 * PHP version 8.4
 * @author     Kirill Chernyshov <chk@chkgo.com>
 */

class Day10Machine
{
    /** @var int a bitmask of the desired state of all indicators (first part) */
    public int   $indicators_aim;

    /** @var array a list of state index bits for every button (button index is a raw position)*/
    public array $button_bits;
    /** @var array desired joltage counters values. keys are powers of 2 */
    public array $joltage_aim;

    /** @var array starting state for counters values. keys are powers of 2 */
    public array $zero;

    /** @var array maximum allowed number of button presses (to avoid overflow in any counter) */
    public array $max_button_press;

    /** @var int (a helper) a number of indicators|counters  */
    protected int $total_bits;

    public array $button_names;
    public array $buttons;
    public bool $verbose = false;



    /**
     * constructor
     *
     * @param string $setup a s
     * @throws Exception
     */
    public function __construct(string $setup)
    {
        // - parse the given machine config string and prep the values for further calculations
        if (preg_match('/^\[([.#]+)]\s+\(([^{]+)\)\s\{([\d,]+)}$/', $setup, $matches))
        {
            $this->total_bits = strlen($matches[1]);
            $this->indicators_aim = bindec(str_replace(['.', '#'], ['0', '1'], $matches[1]));
            $this->buttons = [];
            foreach (explode(') (', $matches[2]) ?: [] as $btn_str)
            {
                $btn_bits = 0;
                foreach (explode(',', $btn_str) ?: [] as $pow)
                {
                    $btn_bits += pow(2, $this->total_bits - $pow - 1);
                }
                $this->buttons[] = $btn_bits;
                $this->button_names[] = "($btn_str)";
            }
            $joltage = explode(',', $matches[3]);
            $this->zero = [];
            $this->joltage_aim = [];
            $this->max_button_press = [];
            foreach ($joltage as $j => $res_joltage)
            {
                $key = pow(2, $this->total_bits - $j - 1);
                $this->zero[$key] = 0;
                $this->joltage_aim[$key] = (int)$res_joltage;
                foreach ($this->buttons as $i => $btn)
                {
                    if ($btn & (int)$key)
                    {
                        $this->max_button_press[$i] =
                            isset($this->max_button_press[$i]) ?
                                min($this->max_button_press[$i], (int)$res_joltage) : (int)$res_joltage;
                    }
                }
            }
            $this->button_bits = [];
            foreach ($this->buttons as $idx => $btn)
            {
                $pos = $this->total_bits;
                while (--$pos >= 0)
                {
                    $pow = pow(2, $pos);
                    if ($btn & $pow)
                    {
                        $this->button_bits[$idx][] = $pow;
                    }
                }
            }
        }
        else
        {
            throw new Exception("invalid machine config");
        }
    }

    /**
     * Builds the set of possible combinations of the elements from the given array
     * where each element can appear 0 or 1 time.
     *
     * @param array $src source array
     * @return array
     */
    public function uniqueCombinations(array $src): array
    {
        $res = [];
        $prev_src = [];
        for ($len = 1; $len <= count($src); $len++)
        {
            foreach ($prev_src[$len - 1] ?? [null] as $item)
            {
                if (!isset($item))
                {
                    foreach ($src as $idx => $val)
                    {
                        $prev_src[$len][] = [$idx];
                        $res[] = [$src[$idx]];
                    }
                }
                else
                {
                    for ($append = $item[$len - 2] + 1; $append < count($src); $append++)
                    {
                        $element = array_merge($item, [$append]);
                        $prev_src[$len][] = $element;
                        foreach ($element as $key => $val)
                        {
                            $element[$key] = $src[$val];
                        }
                        $res[] = $element;
                    }
                }
            }
        }

        return $res;
    }

    /**
     * DBG: Gen a human-readable state of indicators of desired length basing on bitmask in $val
     *
     * @param int $val bitmask
     * @param int $len length
     * @return string
     */
    public function getIndicatorString(int $val, int $len): string
    {
        $res = str_pad('', $len, '.');
        for ($i = 0; $i < $len; $i++)
        {
            if (pow(2, $i) & $val)
            {
                $res[$len - $i - 1] = '#';
            }
        }
        return $res;
    }

    /**
     * Press buttons to set indicator lights (initially all are off) to the aim state
     * (The first part solution)
     *
     * @return int|null Either a minimum number of button presses or null if unable to reach the desired result
     */
    public function setIndicators(): ?int
    {
        if (empty($this->buttons))
        {
            return null;
        }
        foreach ($this->uniqueCombinations($this->buttons) as $sequence)
        {
            $state = 0;
            foreach ($sequence as $btn)
            {
                $state ^= $btn;
            }
            if ($state == $this->indicators_aim)
            {
                return count($sequence);
            }
        }

        return null;
    }

    /**
     * a combination in human-readable format
     *
     * @param array $combination combination
     * @return string
     */
    public function getCombinationTitle(array $combination): string
    {
        $res = [];
        foreach ($combination as $btn_idx => $cnt)
        {
            $res[] = $this->button_names[$btn_idx] . "*" . $cnt;
        }
        return implode(',', $res);
    }

    /**
     * Updates the joltage in the given state by pressing a button number of times
     *
     * @param array $state a state to alter
     * @param int $btn     a button index
     * @param int $count   a number of times to press
     * @return void
     */
    public function pressButton(array &$state, int $btn, float $count): void
    {
        foreach ($this->button_bits[$btn] as $pow)
        {
            $state[$pow]+=$count;
        }
    }

    /**
     * Builds a matrix (system of equations) basing on the machine config.
     * Every line is presented with three elements:
     * 0) the identifying bit of the counter a power of 2 of the counter position. We ned to preserve it while sorting lines.
     * 1) the list of button press numbers affecting a counter
     * 2) a desired counter result
     *
     * @return array
     */
    public function buildMatrix(): array
    {
        $res = [];
        foreach ($this->joltage_aim as $j_bit => $sum)
        {
            $line = array_map(function ($affected_bits) use ($j_bit) {
                return (int)(in_array($j_bit, $affected_bits));
            }, $this->button_bits);

            $res[] = [$j_bit, $line, $sum];
        }

        return $res;
    }

    /**
     * Calculates the state of all counters (initially zero) after pressing given buttons given number of times
     *
     * @param array $btn_press [btn_idx => number_of_presses]
     * @return array the resulting state (index is a power of 2)
     */
    public function pressAllButtons(array $btn_press): array
    {
        $state = $this->zero;
        foreach($btn_press as $btn_idx => $press)
        {
            $this->pressButton($state, (int)$btn_idx, (float)$press);
        }
        return $state;
    }

    /**
     * Calculates the entire counters state basing on matrix solution with respect to previously swapped button columns
     *
     * @param array $btn_press a list of button presses to apply
     * @param array $swp       a list of column index couples, swapped during transformation
     * @return bool Whether the result matches a desired one
     */
    public function checkResult(array $btn_press, array $swp): bool
    {
        $this->prnStr('CHK:::');
        $this->prnArr($swp, "\tSwp");
        $this->prnArr($btn_press, "\tChk BtnPress");

        while($swp)
        {
            [$col1, $col2] = array_pop($swp);
            $tmp = $btn_press[$col1] ?? 0;
            $btn_press[$col1] = $btn_press[$col2] ?? 0;
            $btn_press[$col2] = $tmp;
        }

        $this->prnArr($btn_press, "\tSwp BtnPress");

        $check = $this->pressAllButtons($btn_press);
        $this->prnArr($check, "\tState after Press");

        foreach ($check as $k => $v)
        {
            if ($this->joltage_aim[$k] != $v)
            {
                return false;
            }
        }

        return count($check) == count($this->joltage_aim);
    }

    /**
     * Removes duplicate lines from matrix
     *
     * @param array $matrix matrix
     * @return void
     */
    public function cleanDuplicates(array &$matrix): void
    {
        $line_idx = 0;
        while (array_key_exists($line_idx, $matrix))
        {
            $below_idx = $line_idx + 1;
            while (array_key_exists($below_idx, $matrix))
            {
                if ($matrix[$below_idx][1] == $matrix[$line_idx][1])
                {
                    unset($matrix[$below_idx]);
                }
                else
                {
                    $below_idx++;
                }
            }

            $line_idx++;
        }

        $matrix = array_values($matrix);
        $this->prnMatrix($matrix, 'After Dups Cleaning:');
    }

    /**
     * Removes empty matrix lines below the given position
     *
     * @param array $matrix    matrix
     * @param int   $not_above ignore lines above (there should not be any... just to speed up)
     * @return void
     */
    public function cleanEmptyLines(array &$matrix, int $not_above = 0): void
    {
        $remove_these = [];
        $tmp_idx = count($matrix) - 1;
        while ($tmp_idx>=$not_above)
        {
            if (array_all($matrix[$tmp_idx][1], function($val){
                return $val == 0;
            }))
            {
                $remove_these[] = $tmp_idx;
            }

            $tmp_idx--;
        }

        arsort($remove_these);

        foreach ($remove_these as $tmp_idx)
        {
            unset($matrix[$tmp_idx]);
        }

        $matrix = array_values($matrix);
        $this->prnMatrix($matrix, 'After Empty Cleaning:');
    }

    /**
     * Lists the index of empty columns among free vars (cols to the right from diagonal)
     *
     * @param array $matrix matrix
     * @return array
     */
    public function getColumnsToIgnore(array $matrix): array
    {
        // - find out the number of basic variables (how much more the buttons than counters left)
        $try_buttons =  count($matrix[0][1]) - count($matrix);
        if ($try_buttons <= 0)
        {
            return [];
        }

        $free_vars = [];
        for ($i = count($matrix[0][1]) - 1, $j=$try_buttons; $j; $j--, $i--)
        {
            $free_vars[$i] = 0;
        }

        $ignore_buttons = array_fill(0, count($matrix[0][1]), 0);
        foreach ($matrix as $tmp_line)
        {
            foreach ($tmp_line[1] as $tmp_idx => $tmp_cell)
            {
                if ($tmp_cell != 0)
                {
                    $ignore_buttons[$tmp_idx] = 1;
                }
            }
        }

        $avoid_these_buttons = [];
        foreach ($ignore_buttons as $tmp_idx => $val )
        {
            if (!$val && isset($free_vars[$tmp_idx]))
            {
                $avoid_these_buttons[] = $tmp_idx;
            }
        }

        foreach ($free_vars as $k => $v)
        {
            if (in_array($k, $avoid_these_buttons))
            {
                unset($free_vars[$k]);
            }
        }

        return $free_vars;
    }

    /**
     * The main solver for the second part.
     * Prepares (Gauss-Jordan elimination) the given matrix for the button press count detection.     *
     *
     * @param array $matrix matrix
     * @return array
     */
    public function eliminateMatrix(array $matrix): array
    {
        if (empty($matrix))
        {
            return [];
        }

        $col_swaps = [];
        $max_button_press = $this->max_button_press;
        $this->prnMatrix($matrix, 'Before Dups Cleaning:');
        $this->cleanDuplicates($matrix);
        // $this->mulMatrix($matrix, 1000);
        $this->prnMatrix($matrix, 'After multiplication:');

        // - prep the gaussian stairs
        for ($idx = 0; $idx<count($matrix); $idx++)
        {
            if (!array_key_exists($idx, $matrix[$idx][1]))
            {
                continue;
            }
            // - if we have zero on diagonal, switch with any line below, having non zero val.
            // if none found, continue to the next line
            if ($matrix[$idx][1][$idx] == 0)
            {
                $swapped = false;
                $nl = $idx + 1;
                while (array_key_exists($nl, $matrix) && !isset($non_zero_val_idx))
                {
                    if (!empty($matrix[$nl][1][$idx]))
                    {
                        // - swap lines
                        $tmp = $matrix[$idx];
                        $matrix[$idx] = $matrix[$nl];
                        $matrix[$nl] = $tmp;

                        $swapped = true;
                    }

                    $nl++;
                }

                // - if failed to find non-zero below, search to the right, and swap columns instead
                if (!$swapped)
                {
                    $col = $idx + 1;
                    while ($col <= count($matrix[0][1]) - 1  && $matrix[$idx][1][$col] == 0) $col++;

                    if ($matrix[$idx][1][$col] ?? false)
                    {
                        $this->swapMatrixColumns($matrix, $idx, $col);

                        // - swap max button press values too
                        $tmp = $max_button_press[$idx];
                        $max_button_press[$idx] = $max_button_press[$col];
                        $max_button_press[$col] = $tmp;

                        // - note the swapped lines to get it back when processing the result
                        $col_swaps[] = [$idx, $col];

                        $swapped = true;
                    }
                }

                if (!$swapped)
                {
                    continue;
                }
            }

            // - transform all lines below making sure that there's zero on the current position
            foreach ($matrix as $idx_below => &$line_below)
            {
                if ($idx_below <= $idx)
                {
                    continue;
                }

                if (($line_below[1][$idx] ?? 0) != 0)
                {
                    $this->addMatrixLine($line_below,
                        $this->mulMatrixLine($matrix[$idx], -($line_below[1][$idx]/$matrix[$idx][1][$idx])));
                }
            }

            // - get rid of empty lines below
            $this->prnMatrix($matrix, 'Before Empty Cleaning:');
            $this->cleanEmptyLines($matrix, $idx);
        }

        // (i) at this point we have stairs done. i.e. there are all zeroes below the diagonal.

        // - build diagonal (process angle matrix upwards) so that there are zeroes above the diagonal
        for ($idx = count($matrix) - 1; $idx >= 0; $idx--)
        {
            // - a zero on diagonal
            if (!$matrix[$idx][1][$idx])
            {
                $this->prnMatrix($matrix, "FAIL at $idx");
                die;
            }

            // - transform all lines above making sure that there's zero on the current position
            for ($idx_above = $idx - 1; $idx_above >= 0; $idx_above--)
            {
                if (($matrix[$idx_above][1][$idx] ?? 0) != 0)
                {
                    $this->addMatrixLine($matrix[$idx_above],
                        $this->mulMatrixLine($matrix[$idx], -($matrix[$idx_above][1][$idx]/$matrix[$idx][1][$idx])));
                }
            }
        }

        // - matrix transformation is done at this point. print it out
        $this->prnMatrix($matrix, 'Diagonal', force:true);

        // - set free vars (columns to the right from diagonal) to zero,
        // and increment until we either get matrix solved or reach the maximum allowed presses
        $free_vars = $this->getColumnsToIgnore($matrix);

        $this->prnArr($col_swaps, 'Col Swp');
        $this->prnArr($max_button_press, 'Max');
        $this->prnArr($free_vars, 'Free Var');

        // - minimum presses required to get the desired state
        $min_res = null;
        $min_press_log = [];

        $total_checked = 0;
        $this->calcAndCheck($matrix, $free_vars, $col_swaps, $total_checked, $min_res, $min_press_log);

        // - check every possible combination of free vars (if any) values starting from zero upto maximum for each button
        $base = [$free_vars];
        do
        {
            $sub_res = [];
            // - run thru base (all the combinations from previous iteration)
            foreach ($base as $btn_presses)
            {
                // - increment buttons one by one
                foreach ($free_vars as $btn_idx => $val)
                {
                    $item = $btn_presses;

                    // - if there's no overflow with this combination, try calculating the matrix
                    if (++$item[$btn_idx] <= $max_button_press[$btn_idx] && !in_array($item, $sub_res))
                    {
                        $this->calcAndCheck($matrix, $item, $col_swaps, $total_checked, $min_res, $min_press_log);
                        $sub_res[] = $item;
                    }
                }
            }

            $base = $sub_res;
        }
        while($base);

        $this->prnStr("[checked: $total_checked]");

        // - was unable to find out a suitable button press combination
        if (!isset($min_res))
        {
            return [];
        }

        // - revert the previously made column swaps and apply to the result
        while($col_swaps)
        {
            [$col1, $col2] = array_pop($col_swaps);
            $tmp = $min_press_log[$col1] ?? 0;
            $min_press_log[$col1] = $min_press_log[$col2] ?? 0;
            $min_press_log[$col2] = $tmp;
        }

        return $min_press_log;
    }


    /**
     * Calculate the matrix with a given set of free vars
     *
     * @param array  $matrix        matrix
     * @param array  $free_vars     free vars
     * @param array  $col_swaps     column swaps
     * @param int    $total_checked external counter
     * @param ?float $min_res       external previous minimum
     * @param array  $min_press_log external previous press log combination
     * @return void
     */
    public function calcAndCheck(array $matrix, array $free_vars, array $col_swaps, int &$total_checked, ?float &$min_res, array &$min_press_log): void
    {
        $res = $this->calcJordanMatrix($matrix, $free_vars, true);
        $total_checked++;
        $this->prnArr($free_vars, "\tFreeVars");
        $check = $this->checkResult($res, $col_swaps);
        $this->prnArr($this->joltage_aim, "\tExp");
        $this->prnArr($res, "\tRes");

        // - if result doesn't contain negative values, we found a valid answer
        if ($res && !array_any($res, function ($v, $k) {
                return $v < 0;
            }) && $check
        )
        {
            $sum = array_sum($res);
            if (!isset($min_res) || $min_res > $sum)
            {
                $min_res = $sum;
                $min_press_log = $res;
            }
        }
    }

    /**
     * Solves the system of equations (columns are "buttons", rows are "counters") presented as a matrix.
     * The matrix base is supposed to be diagonal (prep using Jordan-Gauss approach).
     * Yet we don't expect neither empty lines nor empty columns in the base.
     * The columns to the right from diagonal (if any) require $free_vars items (column index => factor).
     *
     * @param array $matrix           [(int)bit][(array)buttons][(float)res]
     * @param array $free_vars        optional list of free button indexes for extended matrix (where we'll test different values)
     * @param bool $break_on_negative we don't expect a negative button presses. so if we face any, reject the whole answer.
     * @return array the list of buttons $idx=>$number_of_presses
     */
    public function calcJordanMatrix(array $matrix, array $free_vars = [], bool $break_on_negative = false): array
    {
        if (empty($matrix))
        {
            return [];
        }
        $res = $free_vars;

        // - start from below upwards
        $line_idx = count($matrix) - 1;
        while ($line_idx >= 0)
        {
            $line = $matrix[$line_idx--];

            // - find the first non-zero value in the line
            $i = 0;
            while (array_key_exists($i, $line[1]) && $line[1][$i] == 0)
            {
                $i++;
            }
            if (!array_key_exists($i, $line[1]))
            {
                // - redundant line (all zeroes)
                continue;
            }

            // - sum up the cells to the right from the current one (they're either given in $free_vars or calculated before)
            $k = 0;
            for ($j = $i + 1; array_key_exists($j, $line[1]); $j++)
            {
                $k += $line[1][$j] * ($res[$j] ?? 0);
            }
            // - get the number of presses for this button (top projection of the current cell/line)
            $res_tmp = ($line[2] - $k) / $line[1][$i];

            if ($break_on_negative && $res_tmp < 0)
            {
                return [];
            }
            $res[$i] = $res_tmp;
        }

        // - gotta round the result (float operations lead to error)
        $this->prnArr($res, "-MatrixRes: ");
        foreach($res as &$val)
        {
            $val = round($val);
        }
        $this->prnArr($res, "+MatrixRes: ");

        return $res;
    }

    /**
     * Swap columns on given positions ($idx & $col) in the given matrix
     *
     * @param array $matrix matrix to proceed. each item is [bit][matrix line values array][line result]
     * @param int   $idx    col1 index
     * @param int   $col    col2 index
     * @return void
     */
    public function swapMatrixColumns(array &$matrix, int $idx, int $col): void
    {
        foreach ($matrix as &$line)
        {
            $tmp = $line[1][$idx];
            $line[1][$idx] = $line[1][$col];
            $line[1][$col] = $tmp;
        }
    }

    /**
     * Multiplies every matrix cell and result by the given factor.
     * We need it to minimize floating point error during calculations.
     *
     * @param array $matrix matrix
     * @param float $factor factor
     * @return void
     */
    public function mulMatrix(array &$matrix, float $factor): void
    {
        $idx = 0;
        while (array_key_exists($idx, $matrix))
        {
            $matrix[$idx] = $this->mulMatrixLine($matrix[$idx], $factor);
            $idx++;
        }
    }

    /**
     * Returns a matrix line multiplied by given factor.
     *
     * @param array $line   matrix line
     * @param float $factor factor
     * @return array
     */
    public function mulMatrixLine(array $line, float $factor): array
    {
        foreach ($line[1] as &$cell)
        {
            $cell *= $factor;
        }

        $line[2] *= $factor;

        return $line;
    }


    /**
     * Adds $term_line to the $subj_line
     * @param array $subj_line matrix line to modify
     * @param array $term_line matrix line to add
     * @return void
     */
    public function addMatrixLine(array &$subj_line, array $term_line):void
    {
        foreach ($subj_line[1] as $idx => &$cell)
        {
            $cell += $term_line[1][$idx];
        }
        $subj_line[2] += $term_line[2];
    }

    /**
     * debug helper
     *
     * @param string $str string to print
     * @return void
     */
    public function prnStr(string $str): void
    {
        if (!$this->verbose)
        {
            return;
        }

        echo "$str\n";
    }

    /**
     * debug helper
     *
     * @param array  $arr   array
     * @param string $title title
     * @return void
     */
    public function prnArr(array $arr, string $title = ''): void
    {
        if (!$this->verbose)
        {
            return;
        }

        echo $title ? ("$title: ") : '', json_encode($arr), PHP_EOL;
    }

    /**
     * Prints the matrix
     *
     * @param array  $matrix a matrix to print
     * @param string $title  optional title above the matrix
     * @param bool   $excel  if it should be printed for copy-pasting to excel
     * @param bool   $force  whether to ignore verbose flag
     * @return void
     */
    public function prnMatrix(array $matrix, string $title = '', bool $excel = false, bool $force = false): void
    {
        if (!$this->verbose && !$force)
        {
            return;
        }
        echo $title ? ($title . PHP_EOL) : '';

        foreach ($matrix as [$j_bit, $line, $sum])
        {
            echo str_pad($j_bit, 6, ' ', STR_PAD_LEFT), $excel ? "\t" : ':';
            foreach ($line as $idx => $cell)
            {
                echo str_pad($cell, 6, ' ', STR_PAD_LEFT), $excel ? "\t" : '';
            }
            echo $excel ? '' : ' | ', str_pad($sum, 6, ' ', STR_PAD_LEFT);

            echo PHP_EOL;
        }
    }
}