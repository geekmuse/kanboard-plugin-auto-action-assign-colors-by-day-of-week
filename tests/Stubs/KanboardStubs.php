<?php

declare(strict_types=1);

/**
 * Minimal Kanboard class stubs for local development / CI environments that do
 * not have the kanboard/kanboard Docker image available.
 *
 * These stubs replicate only the surface area of the Kanboard classes that
 * AssignColorsByDayOfWeek depends on.  They intentionally omit constructor
 * type-hints (Pimple\Container) so that test helpers can pass plain arrays.
 *
 * When running inside the kanboard/kanboard Docker image, tests/bootstrap.php
 * loads the real Kanboard autoloader instead and this file is never included.
 */

// ---------------------------------------------------------------------------
// Kanboard\Core\Base
// ---------------------------------------------------------------------------

namespace Kanboard\Core;

/**
 * Minimal stub for Kanboard\Core\Base.
 *
 * The real class takes a Pimple\Container; here the constructor is untyped so
 * that test helpers can pass a plain array or ArrayObject.
 *
 * @property \Kanboard\Core\Action\ActionManager $actionManager
 * @property \Kanboard\Model\ColorModel          $colorModel
 * @property \Kanboard\Model\TaskModificationModel $taskModificationModel
 */
abstract class Base
{
    /** @var mixed */
    protected $container;

    /**
     * @param mixed $container
     */
    public function __construct($container = null)
    {
        $this->container = $container;
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if (is_array($this->container) && array_key_exists($name, $this->container)) {
            return $this->container[$name];
        }
        if ($this->container instanceof \ArrayAccess && isset($this->container[$name])) {
            return $this->container[$name];
        }
        return null;
    }
}

// ---------------------------------------------------------------------------
// Kanboard\Action\Base
// ---------------------------------------------------------------------------

namespace Kanboard\Action;

/**
 * Minimal stub for Kanboard\Action\Base.
 *
 * @property \Kanboard\Model\ColorModel            $colorModel
 * @property \Kanboard\Model\TaskModificationModel $taskModificationModel
 */
abstract class Base extends \Kanboard\Core\Base
{
    /** @var array<string, mixed> */
    private array $params = [];

    /** @var int */
    protected int $projectId = 0;

    /**
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam(string $name, $default = null)
    {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    /**
     * @param  string $name
     * @param  mixed  $value
     * @return static
     */
    public function setParam(string $name, $value): static
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }

    /**
     * @param  int $projectId
     * @return void
     */
    public function setProjectId(int $projectId): void
    {
        $this->projectId = $projectId;
    }

    /** @return string */
    abstract public function getDescription();

    /** @return string[] */
    abstract public function getCompatibleEvents();

    /** @return array<string, mixed> */
    abstract public function getActionRequiredParameters();

    /** @return array<string|int, mixed> */
    abstract public function getEventRequiredParameters();

    /**
     * @param  array<string, mixed> $data
     * @return bool
     */
    abstract public function doAction(array $data);

    /**
     * @param  array<string, mixed> $data
     * @return bool
     */
    abstract public function hasRequiredCondition(array $data);
}

// ---------------------------------------------------------------------------
// Kanboard\Model\TaskModel
// ---------------------------------------------------------------------------

namespace Kanboard\Model;

/**
 * Minimal stub for Kanboard\Model\TaskModel.
 */
class TaskModel extends \Kanboard\Core\Base
{
    public const EVENT_CREATE = 'task.create';
}

// ---------------------------------------------------------------------------
// Kanboard\Model\ColorModel
// ---------------------------------------------------------------------------

/**
 * Minimal stub for Kanboard\Model\ColorModel.
 */
class ColorModel extends \Kanboard\Core\Base
{
    /**
     * @return string
     */
    public function getDefaultColor(): string
    {
        return 'yellow';
    }

    /**
     * @return array<string, string>
     */
    public function getList(): array
    {
        return [
            'yellow' => 'Yellow',
            'blue'   => 'Blue',
            'green'  => 'Green',
            'red'    => 'Red',
        ];
    }
}

// ---------------------------------------------------------------------------
// Kanboard\Model\TaskModificationModel
// ---------------------------------------------------------------------------

/**
 * Minimal stub for Kanboard\Model\TaskModificationModel.
 */
class TaskModificationModel extends \Kanboard\Core\Base
{
    /**
     * @param  array<string, mixed> $data
     * @return bool
     */
    public function update(array $data): bool
    {
        return true;
    }
}

// ---------------------------------------------------------------------------
// Kanboard\Core\Action\ActionManager
// ---------------------------------------------------------------------------

namespace Kanboard\Core\Action;

/**
 * Minimal stub for Kanboard\Core\Action\ActionManager.
 */
class ActionManager extends \Kanboard\Core\Base
{
    /**
     * @param  \Kanboard\Action\Base $action
     * @return void
     */
    public function register(\Kanboard\Action\Base $action): void
    {
    }
}

// ---------------------------------------------------------------------------
// Kanboard\Core\Plugin\Base
// ---------------------------------------------------------------------------

namespace Kanboard\Core\Plugin;

/**
 * Minimal stub for Kanboard\Core\Plugin\Base.
 *
 * @property \Kanboard\Core\Action\ActionManager $actionManager
 */
abstract class Base extends \Kanboard\Core\Base
{
    /** @return void */
    abstract public function initialize();

    /** @return string */
    public function getPluginName()
    {
        return '';
    }

    /** @return string */
    public function getPluginDescription()
    {
        return '';
    }

    /** @return string */
    public function getPluginAuthor()
    {
        return '';
    }

    /** @return string */
    public function getPluginVersion()
    {
        return '';
    }

    /** @return string */
    public function getPluginHomepage()
    {
        return '';
    }

    /** @return string */
    public function getCompatibleVersion()
    {
        return '';
    }
}
