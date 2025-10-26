<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright (C) 2023 - 2024 JoomShaper. <https: //www.joomshaper.com>
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

trait Availability
{
    /**
     * Check the variants availability
     *
     * @param array $variants
     * @param array $options
     * @return void
     */
    public function checkAvailability(array $variants, array $options)
    {
        $availability = [];

        // Store the availability status on the every variant combinations.
        // This will create an associative array that indicates if a combination
        // has available stock or not.
        $combinations = array_reduce($variants, function ($result, $current) {
            $isInStock                     = false;
            $isInStock                     = $current->is_tracking_inventory ? $current->inventory_amount > 0 : $current->inventory_status;
            $isInStock                     = $isInStock && $current->visibility;
            $result[$current->combination] = $isInStock;

            return $result;
        }, []);

        $domainSet = [];

        // Iterate over the variant options and get all the option values into the domain set.
        foreach ($options as $option) {
            $domainSet[] = array_map(function ($value) {
                return $value->name;
            }, $option->values);
        }

        $powerSet = [];
        $level    = 0;

        // Generate the power set from the domain set
        // This will create the all possible combinations for only the specific level
        // We are considering the first options into the domain set as level 0, next options level 1 and so on.
        // Example: if the domainSet is [[R, G, B], [L, S]]
        // Then the [R, G, B] array would be considered as level 0 and [L, S] considered as level 1
        // And the power set would be [[R, G, B], [RL, RS, GL, GS, BL, BS]] etc.
        while (count($domainSet) > 0) {
            $values = array_shift($domainSet);

            // For the first level we don't need to make any combination
            if ($level === 0) {
                $powerSet[] = $values;
            } else {
                // For the next levels, pick the powerSet values generated on the previous level and
                // and make the cartesian products or combinations.
                $previousValues = $powerSet[$level - 1];
                $newValues      = [];

                foreach ($previousValues as $previousValue) {
                    foreach ($values as $value) {
                        $newValues[] = implode(';', [$previousValue, $value]);
                    }
                }

                $powerSet[] = $newValues;
            }

            $level++;
        }

        $flattenArray = [];

        // Flatten the powerSet multi-dimensional array into a flatten array
        // The [[R, G, B], [RL, RS, GL, GS, BL, BS]] transformed into [R, G, B, RL, RS, GL, GS, BL, BS]
        // The item would be different but the ultimate linear structure is this.
        foreach ($powerSet as $level => $item) {
            foreach ($item as $value) {
                $flattenArray[] = (object)[
                    'level' => $level + 1,
                    'value' => $value,
                ];
            }
        }

        // Restructuring the flatten array and pick the combinations and split them by semicolon(;)
        // And then sort them alphabetically and rejoin them by semicolon(;) once again.
        // This is required for a common pattern that will be used overall the application.
        $flattenArray = array_map(function ($item) {
            $valueArray = explode(';', $item->value);
            $valueArray = array_filter($valueArray, function ($option) {
                return !empty($option);
            });

            $valueArray = array_values($valueArray);
            natcasesort($valueArray);

            $item->value = implode(';', $valueArray);

            return $item;
        }, $flattenArray);

        // Finally create an associative array for all possible combination store the availability status
        $availability = array_reduce($flattenArray, function ($result, $current) use ($combinations) {
            $result[strtolower($current->value)] = (object)[
                'availability' => $this->checkInsideCombination($combinations, $current),
                'level'        => $current->level,
            ] ;
            return $result;
        }, []);

        return (object) $availability;
    }

    /**
     * Check an item lays inside the combinations associative array.
     * If we found every encounter is true then we consider this is available.
     *
     * @param [type] $combinations
     * @param [type] $item
     * @return bool
     */
    protected function checkInsideCombination($combinations, $item)
    {
        $isAvailable = false;

        foreach ($combinations as $name => $availability) {
            if ($this->isAvailableCombination($name, $item->value)) {
                $isAvailable = $isAvailable || $availability;
            }
        }

        return $isAvailable;
    }

    /**
     * Convert a combination string into array by splitting semicolon
     *
     * @param string $combination
     * @return array
     */
    protected function combinationStringToArray($combination)
    {
        $combinationArray = explode(';', $combination);
        $combinationArray = array_filter($combinationArray, function ($item) {
            return !empty($item);
        });

        return array_values($combinationArray);
    }

    /**
     * Compare two combinations and check if the needle exists inside the haystack.
     * The haystack is the super string where we will search for the needle
     * We need to convert them into arrays so that we can compare the encounter of the needle
     * options.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    protected function isAvailableCombination($haystack, $needle)
    {
        $haystackArray = $this->combinationStringToArray($haystack);
        $needleArray   = $this->combinationStringToArray($needle);

        $intersect = array_intersect($haystackArray, $needleArray);

        return count($intersect) === count($needleArray);
    }
}
