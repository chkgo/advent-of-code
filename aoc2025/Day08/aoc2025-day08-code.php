<?php
/*
 * https://adventofcode.com/2025/day/8
 *
 * to proceed first task on 1000 connections, call like this:
 * php aoc2025-day08-code.php -n1000 < input.txt
 */

if (!preg_match('/-n(\d+)/', $argv[1] ?? '', $matches) || empty($matches[1]))
{
    echo "Provide a number of connections to proceed for the first part of the task.\n";
    exit;
}
$number_of_records_to_proceed = (int)$matches[1];

/**
 * Basically a structure, for keeping a junction box coordinates
 */
class JunctionBox {
    public int $x, $y, $z;
    public string $id;

    public function __construct(string $coordinates)
    {
        $this->id = $coordinates;
        [$this->x, $this->y, $this->z] = explode(',', $coordinates) ?: [0, 0, 0];
    }
}

/**
 * A helper class holding the pair of JunctionBox instances, and the distance between them.
 * Also a static holder for the entire connections list, and a method for fetching the shortest one.
 */
class Connection {
    public static array $list = [];

    public string $id;
    public JunctionBox $a, $b;
    
    public float $distance;

    public function __construct(JunctionBox $a, JunctionBox $b)
    {
        $this->distance = sqrt(($a->x - $b->x) * ($a->x - $b->x) +
            ($a->y - $b->y) * ($a->y - $b->y) +
            ($a->z - $b->z) * ($a->z - $b->z));
        $this->a = $a;
        $this->b = $b;

        // - make sure we use the same id for the pair regardless the order
        $ba_id = 'id' . $b->id . ';' . $a->id;
        $this->id =
            array_key_exists($ba_id, self::$list) ? $ba_id : ('id' . $a->id . ';' . $b->id);

        // - append newly created connection to the list
        self::$list[$this->id] = $this;
    }

    /**
     * Fetch a connection with the shortest distance from the list
     *
     * @return Connection|null
     */
    public static function fetchShortest(): ?Connection
    {
        $min_idx = null;
        $min_dist = null;
        foreach (self::$list as $idx => $conn)
        {
            if (!isset($min_idx) || ($min_dist > $conn->distance))
            {
                $min_idx  = $idx;
                $min_dist = $conn->distance;
            }
        }
        if (!isset($min_idx))
        {
            return null;
        }
        $res = self::$list[$min_idx];
        unset(self::$list[$min_idx]);
        return $res;
    }
}


// - a list of JunctionBox instances
$boxes = [];

// - a list of arrays (circuits), containing box ids
$circuits = [];

// - collect boxes from input
$src = STDIN;
stream_set_blocking(STDIN, false);
$res1 = 0;
$res2 = 0;
do
{
    $src_str = trim(fgets($src));
    if ($src_str)
    {
        $new_box = new JunctionBox($src_str);

        // - initially every box makes its own circuit
        $circuits[] = [$new_box->id];

        // - detect distances from the new box to all the existing
        foreach ($boxes as $box)
        {
            new Connection($box, $new_box);
        }
        $boxes[] = $new_box;
    }
}
while (!feof($src));

// - merge circuits starting from shortest connections
$n = $number_of_records_to_proceed;
do
{
    // - fetch the shortest unprocessed connection
    $connection = Connection::fetchShortest();
    $a = $connection->a->id;
    $b = $connection->b->id;

    // - find the existing circuits containing the boxes from the current pair
    $a_circuit = $b_circuit = null;
    foreach ($circuits as $cid => $circuit)
    {
        $a_circuit = !isset($a_circuit) && in_array($a, $circuit, true) ? $cid : $a_circuit;
        $b_circuit = !isset($b_circuit) && in_array($b, $circuit, true) ? $cid : $b_circuit;
        if (isset($a_circuit) && isset($b_circuit))
        {
            break;
        }
    }

    // - if both boxes already exist, but in the different circuits, then merge circuits
    if (isset($a_circuit) && isset($b_circuit) && $a_circuit != $b_circuit)
    {
        $circuits[] = array_values(array_unique(array_merge($circuits[$a_circuit],$circuits[$b_circuit])));
        unset($circuits[$a_circuit], $circuits[$b_circuit]);

        // - when the only circuit remains, calculate the second part result, and leave the loop
        if (count($circuits) == 1)
        {
            $res2 = $connection->a->x * $connection->b->x;
            break;
        }
    }

    // - calculate the first part result after N records processed
    if (--$n == 0)
    {
        // - sort circuits by the number of connections within. descending
        usort($circuits, function($a, $b){
            return count($b) - count($a);
        });

        $res1 =
            count($circuits[0] ?? []) *
            count($circuits[1] ?? []) *
            count($circuits[2] ?? []);
    }
}
while($connection);

echo "RES: $res1, $res2\n";