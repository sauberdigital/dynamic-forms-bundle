<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Tests\Unit\FieldDependency\Validator;

use PHPUnit\Framework\TestCase;
use Sd\DynamicFormsBundle\FieldDependency\Validator\DependencyGraphValidator;

class DependencyGraphValidatorTest extends TestCase
{
    private DependencyGraphValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DependencyGraphValidator();
    }

    public function testNoCycleInEmptyGraph(): void
    {
        $this->assertNull($this->validator->detectCycle());
    }

    public function testNoCycleInLinearDependency(): void
    {
        // A → B → C
        $this->validator->addEdge('A', ['B']);
        $this->validator->addEdge('B', ['C']);

        $this->assertNull($this->validator->detectCycle());
    }

    public function testDetectDirectCycle(): void
    {
        // A → B, B → A
        $this->validator->addEdge('A', ['B']);
        $this->validator->addEdge('B', ['A']);

        $cycle = $this->validator->detectCycle();
        
        $this->assertNotNull($cycle);
        $this->assertContains('A', $cycle);
        $this->assertContains('B', $cycle);
        
        // The cycle should show the complete loop
        $cycleString = implode(' → ', $cycle);
        $this->assertMatchesRegularExpression('/A → B → A|B → A → B/', $cycleString);
    }

    public function testDetectSelfCycle(): void
    {
        // A → A
        $this->validator->addEdge('A', ['A']);

        $cycle = $this->validator->detectCycle();
        
        $this->assertNotNull($cycle);
        $this->assertEquals(['A', 'A'], $cycle);
    }

    public function testDetectIndirectCycle(): void
    {
        // A → B → C → A
        $this->validator->addEdge('A', ['B']);
        $this->validator->addEdge('B', ['C']);
        $this->validator->addEdge('C', ['A']);

        $cycle = $this->validator->detectCycle();
        
        $this->assertNotNull($cycle);
        $this->assertContains('A', $cycle);
        $this->assertContains('B', $cycle);
        $this->assertContains('C', $cycle);
    }

    public function testDetectCycleInComplexGraph(): void
    {
        // A → B → C → D → B (cycle: B → C → D → B)
        $this->validator->addEdge('A', ['B']);
        $this->validator->addEdge('B', ['C']);
        $this->validator->addEdge('C', ['D']);
        $this->validator->addEdge('D', ['B']);

        $cycle = $this->validator->detectCycle();
        
        $this->assertNotNull($cycle);
        $this->assertContains('B', $cycle);
        $this->assertContains('C', $cycle);
        $this->assertContains('D', $cycle);
    }

    public function testNoCycleInDiamondDependency(): void
    {
        // Diamond pattern (valid DAG):
        // A → B → D
        // A → C → D
        $this->validator->addEdge('A', ['B', 'C']);
        $this->validator->addEdge('B', ['D']);
        $this->validator->addEdge('C', ['D']);

        $this->assertNull($this->validator->detectCycle());
    }

    public function testMultipleDependenciesWithoutCycle(): void
    {
        // Complex valid dependency graph
        $this->validator->addEdge('field1', ['field2', 'field3']);
        $this->validator->addEdge('field2', ['field4']);
        $this->validator->addEdge('field3', ['field4', 'field5']);
        $this->validator->addEdge('field4', ['field6']);
        $this->validator->addEdge('field5', ['field6']);

        $this->assertNull($this->validator->detectCycle());
    }

    public function testMultipleDependenciesWithCycle(): void
    {
        // Complex graph with a cycle
        $this->validator->addEdge('field1', ['field2', 'field3']);
        $this->validator->addEdge('field2', ['field4']);
        $this->validator->addEdge('field3', ['field4']);
        $this->validator->addEdge('field4', ['field1']); // Creates cycle

        $cycle = $this->validator->detectCycle();
        
        $this->assertNotNull($cycle);
        $this->assertContains('field1', $cycle);
        $this->assertContains('field4', $cycle);
    }

    public function testDisconnectedGraphsWithCycle(): void
    {
        // Two disconnected graphs, one with a cycle
        // Graph 1: A → B (no cycle)
        $this->validator->addEdge('A', ['B']);
        
        // Graph 2: X → Y → X (cycle)
        $this->validator->addEdge('X', ['Y']);
        $this->validator->addEdge('Y', ['X']);

        $cycle = $this->validator->detectCycle();
        
        $this->assertNotNull($cycle);
        $this->assertContains('X', $cycle);
        $this->assertContains('Y', $cycle);
    }

    public function testClearGraph(): void
    {
        // Add some edges
        $this->validator->addEdge('A', ['B']);
        $this->validator->addEdge('B', ['A']);
        
        // Should detect cycle
        $this->assertNotNull($this->validator->detectCycle());
        
        // Clear the graph
        $this->validator->clear();
        
        // Should not detect cycle after clearing
        $this->assertNull($this->validator->detectCycle());
        $this->assertEmpty($this->validator->getGraph());
    }

    public function testGetGraph(): void
    {
        $this->validator->addEdge('A', ['B', 'C']);
        $this->validator->addEdge('B', ['D']);

        $graph = $this->validator->getGraph();
        
        $this->assertArrayHasKey('A', $graph);
        $this->assertArrayHasKey('B', $graph);
        $this->assertArrayHasKey('C', $graph);
        $this->assertArrayHasKey('D', $graph);
        
        $this->assertContains('B', $graph['A']);
        $this->assertContains('C', $graph['A']);
        $this->assertContains('D', $graph['B']);
        $this->assertEmpty($graph['C']);
        $this->assertEmpty($graph['D']);
    }

    public function testLongCycleDetection(): void
    {
        // Create a long chain with a cycle at the end
        // A → B → C → D → E → F → G → H → C (cycle)
        $chain = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        
        for ($i = 0; $i < count($chain) - 1; $i++) {
            $this->validator->addEdge($chain[$i], [$chain[$i + 1]]);
        }
        
        // Add cycle from H back to C
        $this->validator->addEdge('H', ['C']);

        $cycle = $this->validator->detectCycle();
        
        $this->assertNotNull($cycle);
        $this->assertContains('C', $cycle);
        $this->assertContains('H', $cycle);
        
        // Should not include nodes before the cycle
        $this->assertNotContains('A', $cycle);
        $this->assertNotContains('B', $cycle);
    }

    public function testParallelPathsWithCycle(): void
    {
        // Two parallel paths, one creates a cycle
        // A → B → D
        // A → C → D → A (cycle)
        $this->validator->addEdge('A', ['B', 'C']);
        $this->validator->addEdge('B', ['D']);
        $this->validator->addEdge('C', ['D']);
        $this->validator->addEdge('D', ['A']);

        $cycle = $this->validator->detectCycle();
        
        $this->assertNotNull($cycle);
        $this->assertContains('A', $cycle);
        $this->assertContains('D', $cycle);
    }

    public function testNoDuplicateEdges(): void
    {
        // Add the same edge multiple times
        $this->validator->addEdge('A', ['B']);
        $this->validator->addEdge('A', ['B']);
        $this->validator->addEdge('A', ['B', 'B', 'B']);
        
        $graph = $this->validator->getGraph();
        
        // Should only have one edge from A to B
        $this->assertArrayHasKey('A', $graph);
        $this->assertCount(1, $graph['A']);
        $this->assertEquals(['B'], $graph['A']);
        
        // Adding different edges should work normally
        $this->validator->addEdge('A', ['C', 'D']);
        $graph = $this->validator->getGraph();
        
        $this->assertCount(3, $graph['A']);
        $this->assertContains('B', $graph['A']);
        $this->assertContains('C', $graph['A']);
        $this->assertContains('D', $graph['A']);
    }
}