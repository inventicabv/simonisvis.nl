<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Supports;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use ArrayAccess;

/**
 * The array helper class.
 *
 * @since 1.3.4
 */
class Arr implements ArrayAccess
{
    /**
     * The array items.
     *
     * @var array
     *
     * @since 1.3.4
     */
    protected $items = [];

    /**
     * The private constructor method that helps the class to instantiate from outside,
     * Instead it enforce to use the Arr::make() method to instantiate the array instance.
     *
     * @param array $items The initial array items
     *
     * @since 1.3.4
     */
    private function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Make an instance of the Arr class.
     *
     * @param array $items The initial array items
     *
     * @return self
     *
     * @since 1.3.4
     */
    public static function make(array $items = [])
    {
        return (new self($items));
    }

    /**
     * Check if the array has the provided key
     *
     * @param string $key The key to check
     *
     * @return boolean True if the key exists, false otherwise
     *
     * @since 1.3.4
     */
    public function has($key): bool
    {
        return isset($this->items[ $key ]);
    }

    /**
     * Get the value of the array by the key.
     *
     * @param string $key The key to retrieve
     * @param mixed $default The default value if the key doesn't exist
     *
     * @return mixed The value associated with the key or the default value
     *
     * @since 1.3.4
     */
    public function get($key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->items[$key];
    }

    /**
     * Set the value into the array. If the key exists then it will update the value. Add a new one otherwise.
     *
     * @param string $key The key to set
     * @param mixed $value The value to set
     *
     * @return void
     *
     * @since 1.3.4
     */
    public function set($key, $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * Count the total number of items into the array.
     *
     * @return integer The number of items in the array
     *
     * @since 1.3.4
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Add a new item at the end of the array
     *
     * @param mixed $value The value to add
     *
     * @return integer The updated array length
     *
     * @since 1.3.4
     */
    public function push($value): int
    {
        $this->items[] = $value;

        return $this->count();
    }

    /**
     * Add a new item to the beginning of the array.
     *
     * @param mixed $value The value to add
     *
     * @return integer The updated array length
     *
     * @since 1.3.4
     */
    public function prepend($value): int
    {
        array_unshift($this->items, $value);

        return $this->count();
    }

    /**
     * Remove an item from the end of the array.
     *
     * @return mixed The removed item
     *
     * @since 1.3.4
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Remove and get an item from the beginning of the array.
     *
     * @return mixed The removed item
     *
     * @since 1.3.4
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Pick the last element of the array.
     * If the array is empty then it will return null.
     * This will only pick the item, but does not remove it from the array.
     *
     * @return mixed The last element or null if the array is empty
     *
     * @since 1.3.4
     */
    public function top()
    {
        if ($this->count() === 0) {
            return null;
        }

        $length = $this->count();

        return $this->items[$length - 1];
    }

    /**
     * Pick the first element of the array.
     * If the array is empty then it will return null.
     * This will only pick the item, but does not remove it from the array.
     *
     * @return mixed The first element or null if the array is empty
     *
     * @since 1.3.4
     */
    public function front()
    {
        if ($this->count() === 0) {
            return null;
        }

        return $this->items[0];
    }

    /**
     * Run a map operation using a callable to the array.
     *
     * @param callable $callable The callable function
     *
     * @return array The new array after applying the callable
     *
     * @since 1.3.4
     */
    public function map(callable $callable): array
    {
        $newArray = [];

        foreach ($this->items as $index => $value) {
            $newArray[] = $callable($value, $index);
        }

        return $newArray;
    }

    /**
     * Filter the array by a callable function.
     * The function will return a true/false value and the return value is true then the value will be kept,
     * otherwise removed.
     *
     * @param callable $callable The callable function for filtering
     *
     * @return array The filtered array
     *
     * @since 1.3.4
     */
    public function filter(callable $callable): array
    {
        $filteredArray = [];

        foreach ($this->items as $index => $value) {
            $result = $callable($value, $index);

            if ($result) {
                $filteredArray[] = $value;
            }
        }

        return $filteredArray;
    }

    /**
     * Find an item into the array by a callable function condition.
     *
     * @param callable $callable The callable function for finding
     *
     * @return mixed|null The found item or null if not found
     *
     * @since 1.3.4
     */
    public function find(callable $callable)
    {
        foreach ($this->items as $index => $value) {
            if ($callable($value, $index)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Find the index of an item into the array.
     * If not found then it will return -1.
     *
     * @param callable $callable The callable function for finding
     *
     * @return integer The index of the found item or -1 if not found
     *
     * @since 1.3.4
     */
    public function findIndex(callable $callable): int
    {
        foreach ($this->items as $index => $value) {
            if ($callable($value, $index)) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * A boolean method that checks if the array satisfies a specific condition.
     * If it satisfies for only one item then it returns true.
     *
     * @param callable $callable The callable function for checking condition
     *
     * @return boolean True if any item satisfies the condition, false otherwise
     *
     * @since 1.3.4
     */
    public function some(callable $callable): bool
    {
        foreach ($this->items as $index => $value) {
            if ($callable($value, $index)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A boolean method that checks if the array satisfies a specific condition.
     * If every items satisfies the condition then it will return true.
     *
     * @param callable $callable The callable function for checking condition
     *
     * @return boolean True if all items satisfy the condition, false otherwise
     *
     * @since 1.3.4
     */
    public function every(callable $callable): bool
    {
        foreach ($this->items as $index => $value) {
            if (!$callable($value, $index)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Replicate the array_reduce function for usage consistency
     *
     * @param callable $callable The callable function for reducing
     * @param mixed $initial_value The initial value for reduction
     *
     * @return mixed The result of the reduction
     *
     * @since 1.3.4
     */
    public function reduce(callable $callable, $initial_value)
    {
        return array_reduce($this->items, $callable, $initial_value);
    }

    /**
     * Join the array using a glue
     *
     * @param string $glue The symbol by which it will be joined
     *
     * @return string The joined string
     *
     * @since 1.3.4
     */
    public function join($glue = ',')
    {
        return implode($glue, $this->items);
    }

    /**
     * Checks whether an offset exists
     *
     * @param mixed $key An offset to check for
     *
     * @return bool True if the offset exists, false otherwise
     *
     * @since 1.3.4
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $key The offset to retrieve
     *
     * @return mixed The value at the specified offset
     *
     * @since 1.3.4
     */
    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    /**
     * Offset to set
     *
     * @param mixed $key The offset to assign the value to
     * @param mixed $value The value to set
     *
     * @return void
     *
     * @since 1.3.4
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Offset to unset
     *
     * @param mixed $key The offset to unset
     *
     * @return void
     *
     * @since 1.3.4
     */
    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }
}
