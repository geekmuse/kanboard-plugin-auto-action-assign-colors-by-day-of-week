<?php

declare(strict_types=1);

namespace Kanboard\Plugin\AssignColorsByDayOfWeek\Tests\Action;

use Kanboard\Model\ColorModel;
use Kanboard\Model\TaskModificationModel;
use Kanboard\Plugin\AssignColorsByDayOfWeek\Action\AssignColorsByDayOfWeek;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test-only subclass that replaces the Pimple\Container DI mechanism with a
 * plain PHP array so tests have no external dependencies.
 *
 * The parent constructor (which requires a Pimple\Container type-hint) is
 * intentionally NOT called.  PHP initialises class property defaults before
 * the constructor runs, so the private $params array in Kanboard\Action\Base
 * is always ready; setParam() / getParam() work correctly.
 *
 * __get() is overridden so that $this->colorModel and
 * $this->taskModificationModel resolve to our PHPUnit mocks instead of
 * pulling from a live DI container.
 */
class TestableAssignColorsByDayOfWeek extends AssignColorsByDayOfWeek
{
    /** @var array<string, object> */
    private array $services;

    /**
     * @param array<string, object> $services Map of service name => instance
     */
    public function __construct(array $services)
    {
        // Do NOT call parent::__construct() — it requires Pimple\Container.
        $this->services = $services;
    }

    /**
     * Resolve DI services from the plain-array service map.
     *
     * @param  string $name Service name (e.g. 'colorModel')
     * @return object|null
     */
    public function __get(string $name): ?object
    {
        return $this->services[$name] ?? null;
    }
}

/**
 * Unit tests for AssignColorsByDayOfWeek.
 *
 * All six test cases required by US-010:
 *   1. hasRequiredCondition() → true  for weekday with configured color
 *   2. hasRequiredCondition() → false for weekday with no configured color
 *   3. hasRequiredCondition() → false for weekend day (no color param set)
 *   4. hasRequiredCondition() → false for zero due date
 *   5. hasRequiredCondition() → false when task already has a non-default color
 *   6. doAction() calls taskModificationModel->update() with the correct color_id
 *
 * Timezone is fixed to UTC in all tests via setParam('Timezone', 'UTC') so
 * day-of-week resolution is deterministic regardless of the host machine's TZ.
 *
 * Timestamps used (noon UTC, verified with DateTime::format('l')):
 *   1704110400 → 2024-01-01 Monday
 *   1704196800 → 2024-01-02 Tuesday
 *   1704542400 → 2024-01-06 Saturday
 */
class AssignColorsByDayOfWeekTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Constants
    // -----------------------------------------------------------------------

    /** Unix timestamp for 2024-01-01 12:00:00 UTC — a Monday. */
    private const MONDAY_TS = 1704110400;

    /** Unix timestamp for 2024-01-02 12:00:00 UTC — a Tuesday (no color set). */
    private const TUESDAY_TS = 1704196800;

    /** Unix timestamp for 2024-01-06 12:00:00 UTC — a Saturday. */
    private const SATURDAY_TS = 1704542400;

    /** Default color returned by ColorModel::getDefaultColor(). */
    private const DEFAULT_COLOR = 'yellow';

    // -----------------------------------------------------------------------
    // Fixtures
    // -----------------------------------------------------------------------

    private TestableAssignColorsByDayOfWeek $action;

    /** @var ColorModel&MockObject */
    private ColorModel $colorModel;

    /** @var TaskModificationModel&MockObject */
    private TaskModificationModel $taskModificationModel;

    protected function setUp(): void
    {
        /** @var ColorModel&MockObject $colorModel */
        $colorModel = $this->createMock(ColorModel::class);
        $colorModel->method('getDefaultColor')->willReturn(self::DEFAULT_COLOR);
        $colorModel->method('getList')->willReturn([
            'yellow' => 'Yellow',
            'blue'   => 'Blue',
            'green'  => 'Green',
            'red'    => 'Red',
        ]);
        $this->colorModel = $colorModel;

        /** @var TaskModificationModel&MockObject $taskModificationModel */
        $taskModificationModel = $this->createMock(TaskModificationModel::class);
        $this->taskModificationModel = $taskModificationModel;

        $this->action = new TestableAssignColorsByDayOfWeek([
            'colorModel'            => $this->colorModel,
            'taskModificationModel' => $this->taskModificationModel,
        ]);

        // Pin timezone to UTC so day-of-week resolution is always predictable.
        $this->action->setParam('Timezone', 'UTC');
    }

    // -----------------------------------------------------------------------
    // Helper — build a minimal task event payload
    // -----------------------------------------------------------------------

    /**
     * Build a task event payload array.
     *
     * @param  int    $taskId
     * @param  string $colorId   color_id value for the task
     * @param  int    $dateDue   Unix timestamp (0 = no due date)
     * @return array<string, mixed>
     */
    private function makeTaskData(int $taskId, string $colorId, int $dateDue): array
    {
        return [
            'task_id' => $taskId,
            'task'    => [
                'project_id' => 1,
                'color_id'   => $colorId,
                'date_due'   => $dateDue,
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Test 1 — hasRequiredCondition(): true for weekday with configured color
    // -----------------------------------------------------------------------

    public function testHasRequiredConditionReturnsTrueForWeekdayWithConfiguredColor(): void
    {
        // Configure Monday → blue
        $this->action->setParam('Monday', 'blue');

        $data = $this->makeTaskData(1, self::DEFAULT_COLOR, self::MONDAY_TS);

        $this->assertTrue(
            $this->action->hasRequiredCondition($data),
            'Expected true: task has default color, weekday due date, and a color is configured for that day.'
        );
    }

    // -----------------------------------------------------------------------
    // Test 2 — hasRequiredCondition(): false for weekday with no configured color
    // -----------------------------------------------------------------------

    public function testHasRequiredConditionReturnsFalseForWeekdayWithNoConfiguredColor(): void
    {
        // Monday is configured, but the task is due on Tuesday (no param set).
        $this->action->setParam('Monday', 'blue');
        // 'Tuesday' param intentionally omitted → getParam('Tuesday') returns null.

        $data = $this->makeTaskData(1, self::DEFAULT_COLOR, self::TUESDAY_TS);

        $this->assertFalse(
            $this->action->hasRequiredCondition($data),
            'Expected false: no color is configured for Tuesday.'
        );
    }

    // -----------------------------------------------------------------------
    // Test 3 — hasRequiredCondition(): false for weekend day (no param set)
    // -----------------------------------------------------------------------

    public function testHasRequiredConditionReturnsFalseForWeekendDayWithNoColorParam(): void
    {
        // No Saturday param set → getParam('Saturday') returns null.
        // This verifies the GAP-02/GAP-05 fix: weekends with no configured
        // color return false instead of writing null to the database.

        $data = $this->makeTaskData(1, self::DEFAULT_COLOR, self::SATURDAY_TS);

        $this->assertFalse(
            $this->action->hasRequiredCondition($data),
            'Expected false: no color configured for Saturday — doAction() must not run.'
        );
    }

    // -----------------------------------------------------------------------
    // Test 4 — hasRequiredCondition(): false for zero due date
    // -----------------------------------------------------------------------

    public function testHasRequiredConditionReturnsFalseForZeroDueDate(): void
    {
        $this->action->setParam('Monday', 'blue');

        $data = $this->makeTaskData(1, self::DEFAULT_COLOR, 0);

        $this->assertFalse(
            $this->action->hasRequiredCondition($data),
            'Expected false: date_due is 0 (no due date set).'
        );
    }

    // -----------------------------------------------------------------------
    // Test 5 — hasRequiredCondition(): false when task has non-default color
    // -----------------------------------------------------------------------

    public function testHasRequiredConditionReturnsFalseWhenTaskHasNonDefaultColor(): void
    {
        $this->action->setParam('Monday', 'blue');

        // Task color is already 'red' — not the default 'yellow'.
        $data = $this->makeTaskData(1, 'red', self::MONDAY_TS);

        $this->assertFalse(
            $this->action->hasRequiredCondition($data),
            'Expected false: task already has a non-default color; action should not overwrite it.'
        );
    }

    // -----------------------------------------------------------------------
    // Test 6 — doAction(): calls update() with the correct color_id
    // -----------------------------------------------------------------------

    public function testDoActionCallsUpdateWithCorrectColorId(): void
    {
        $this->action->setParam('Monday', 'blue');

        $data = $this->makeTaskData(42, self::DEFAULT_COLOR, self::MONDAY_TS);

        $this->taskModificationModel
            ->expects($this->once())
            ->method('update')
            ->with([
                'id'       => 42,
                'color_id' => 'blue',
            ])
            ->willReturn(true);

        $result = $this->action->doAction($data);

        $this->assertTrue($result, 'doAction() should return true when taskModificationModel->update() succeeds.');
    }
}
