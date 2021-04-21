<?php

namespace Drupal\facet_range_list_item\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypeRangeBase;

/**
 * Provides support for range list facets within the Search API scope.
 *
 * This is the default implementation that works with all backends.
 *
 * @FacetsQueryType(
 *   id = "search_api_range_list",
 *   label = @Translation("Range List Item"),
 * )
 */
class SearchApiRangeList extends QueryTypeRangeBase {

  /**
   * {@inheritdoc}
   */
  public function calculateRange($value) {
    $range_list = $this->getRangeList();
    return [
      'start' => $value,
      'stop' => $range_list[$value],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateResultFilter($value) {

    assert($this->getRangeList() > 0);
    $range_list = $this->getRangeList();

    foreach ($range_list as $range_start => $range_stop) {
      if ($value >= $range_start && $value <= $range_stop) {
        return [
          'display' => $range_start,
          'raw' => $range_start,
        ];
      }
    }

  }

  /**
   * Looks at the configuration for this facet
   *
   * @return array
   *   Returns range list items in format of start => stop for all ranges.
   */
  protected function getRangeList() {
    $list = $this->facet->getProcessors()['range_list_item']->getConfiguration()['range_list'];
    $list = explode("\n", $list);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $range_text = [];
        if (preg_match('/(.*)\-(.*)/', $key, $range_text)) {
          $range_list[trim($range_text[1])] = trim($range_text[2]);
        }
      }
    }
    return $range_list;
  }

}
