<?php

class Graph {
    public array $back_pool = [];

    protected array $per_node_paths;

    protected array $req_nodes_passed;

    protected array $paths_thru_required;

    protected array $must_pass;

    /**
     * constructor
     * @param array $pool A pool of directions [$from => [$to1, $to2]]
     */
    public function __construct(array $pool)
    {
        // - build the back directions pool (which nodes point to current one)
        foreach ($pool as $k => $dirs)
        {
            foreach ($dirs as $dir)
            {
                if (!in_array($k, $this->back_pool[$dir] ?? []))
                {
                    $this->back_pool[$dir][] = $k;
                }
            }
        }
    }

    /**
     * Count paths from the given node to starting one
     *
     * @param string $node a node name/key
     * @return array [ total paths from start to this node, a list of required nodes passed so far, a numbers of paths per passed req nodes]
     */
    protected function cntBack(string $node): array
    {
        // - if we already passed this node, just return what we have for it
        if (array_key_exists($node, $this->per_node_paths))
        {
            return [
                $this->per_node_paths[$node],
                $this->req_nodes_passed[$node] ?? [],
                $this->paths_thru_required[$node] ?? []
            ];
        }

        // - read counts from neighbour nodes leading to the current one
        $sum = [0,[],[]];
        foreach ($this->back_pool[$node] ?? [] as $from_node)
        {
            // - read counts recursively
            [$sub_all, $sub_req_passed, $sub_req_counts] = $this->cntBack($from_node);

            // - just add total paths
            $sum[0] += $sub_all;

            // - if the current node is the one of required nodes, append it to the list of previously passed req nodes
            $passed_nodes = in_array($node, $this->must_pass) ?
                array_unique(array_merge($sub_req_passed, [$node])) :
                $sub_req_passed;
            $sum[1] = array_unique(array_merge($sum[1], $passed_nodes));

            // - if source node has paths passed thru any of must_pass nodes, the current node passes them too (inc counters)
            $i = 0;
            while ($i < count($sub_req_passed))
            {
                $sum[2][$i] = ($sum[2][$i] ?? 0) + $sub_req_counts[$i];
                $i++;
            }

            // - if current node is the one of the required, we should add one more counter basing of the last collected counter
            if (in_array($node, $this->must_pass))
            {
                $sum[2][count($passed_nodes) - 1] = ($sum[2][count($passed_nodes) - 1] ?? 0) +
                    (empty($sub_req_counts) ? $sub_all : ($sub_req_counts[count($sub_req_counts) - 1] ?? 0));
            }
        }

        // - store counted value
        [$this->per_node_paths[$node], $this->req_nodes_passed[$node], $this->paths_thru_required[$node]] = $sum;

        return $sum;
    }

    /**
     * Detects the number of paths with an optional set of must-pass nodes
     *
     * @param string $from      starting node index
     * @param string $to        destination node index
     * @param array  $must_pass a list of nodes mandatory to pass
     * @return int
     */
    public function countPaths(string $from, string $to, array $must_pass = []): int
    {
        // - only set the known number of paths for the starting node
        $this->per_node_paths = [$from => 1];

        $this->paths_thru_required = [];
        $this->req_nodes_passed = [];
        $this->must_pass = $must_pass;
        $res = $this->cntBack($to);

        return empty($must_pass) ? $res[0] : ($res[2][count($must_pass) - 1] ?? 0);
    }
}

$src = STDIN;
stream_set_blocking(STDIN, false);
$pool = [];
do
{
    $cfg = trim(fgets($src));
    if (preg_match('/^(\w+):\s+([\s\w]+)$/', $cfg, $matches))
    {
        $pool[$matches[1]] = explode(' ', $matches[2]);
    }
}
while (!feof($src) && !empty($cfg));

$g = new Graph($pool);
$start = microtime(true);
$res1 = $g->countPaths('you', 'out');
$res2 = $g->countPaths('svr', 'out', ['dac', 'fft']);
$seconds = microtime(true) - $start;
echo "\nRES: $res1, $res2 ($seconds sec.)\n";
