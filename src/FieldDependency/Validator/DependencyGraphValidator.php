<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\FieldDependency\Validator;

/**
 * Validates dependency graphs for circular dependencies.
 * 
 * Uses depth-first search (DFS) to detect cycles in the dependency graph.
 * Time complexity: O(V + E) where V is vertices (fields) and E is edges (dependencies).
 */
final class DependencyGraphValidator
{
    /** @var array<string, array<string, true>> Adjacency list using associative arrays as sets */
    private array $graph = [];

    /**
     * Add an edge to the dependency graph.
     * 
     * @param string $from The dependent field name
     * @param array<string> $to Array of dependency field names
     */
    public function addEdge(string $from, array $to): void
    {
        // Initialize as empty set if it doesn't exist
        $this->graph[$from] ??= [];
        
        foreach ($to as $dependency) {
            // Use associative array as set to prevent duplicates
            $this->graph[$from][$dependency] = true;
            
            // Ensure dependency node exists
            $this->graph[$dependency] ??= [];
        }
    }

    /**
     * Detect if there's a cycle in the dependency graph.
     * 
     * @return array<string>|null Returns the cycle path if found, null otherwise
     */
    public function detectCycle(): ?array
    {
        $visited = [];
        $recursionStack = [];
        
        foreach (array_keys($this->graph) as $node) {
            if (!isset($visited[$node])) {
                $path = [];
                if ($this->dfsHasCycle($node, $visited, $recursionStack, $path)) {
                    return $this->extractCycle($path);
                }
            }
        }
        
        return null;
    }

    /**
     * Depth-first search to detect cycles.
     * 
     * @param string $node Current node being visited
     * @param array<string, true> $visited All visited nodes
     * @param array<string, true> $recursionStack Nodes in current DFS path
     * @param array<string> $path Current traversal path
     * 
     * @return bool True if cycle detected
     */
    private function dfsHasCycle(string $node, array &$visited, array &$recursionStack, array &$path): bool
    {
        $visited[$node] = true;
        $recursionStack[$node] = true;
        $path[] = $node;

        // Check all adjacent nodes
        foreach (array_keys($this->graph[$node]) as $adjacent) {
            if (!isset($visited[$adjacent])) {
                if ($this->dfsHasCycle($adjacent, $visited, $recursionStack, $path)) {
                    return true;
                }
            } elseif ($recursionStack[$adjacent] ?? false) {
                // Cycle found - add the node that completes the cycle
                $path[] = $adjacent;
                return true;
            }
        }

        // Backtrack
        unset($recursionStack[$node]);
        array_pop($path);
        
        return false;
    }

    /**
     * Extract the cycle from the path.
     * 
     * @param array<string> $path Path containing the cycle
     * 
     * @return array<string> The extracted cycle
     */
    private function extractCycle(array $path): array
    {
        if (empty($path)) {
            return [];
        }
        
        $cycleNode = end($path);
        $firstIndex = array_search($cycleNode, $path, true);

        if ($firstIndex === false) {
            // Defensive fallback; should not happen
            return $path;
        }

        return array_slice($path, $firstIndex);
    }

    /**
     * Clear the dependency graph.
     */
    public function clear(): void
    {
        $this->graph = [];
    }

    /**
     * Get the current graph structure.
     * 
     * @return array<string, array<string>> The adjacency list
     */
    public function getGraph(): array
    {
        // Convert sets back to regular arrays for compatibility
        return array_map(function ($adjacentSet) {
            return array_keys($adjacentSet);
        }, $this->graph);
    }
}