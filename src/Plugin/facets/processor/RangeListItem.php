<?php

namespace Drupal\facet_range_list\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor that creates User specifc range list.
 *
 * @FacetsProcessor(
 *   id = "range_list_item",
 *   label = @Translation("Range List Item Processor"),
 *   description = @Translation("Specify any range of option for numberic or decimal fields."),
 *   stages = {
 *     "build" = 35
 *   }
 * )
 */
class RangeListItem extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\facets\Result\Result $result */
    $list = $this->getConfiguration()['range_list'];
    $list = explode("\n", $list);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        if (preg_match('/(.*)\-(.*)/', $key, $range_text)) {
          $range_display[$range_text[1]] = trim($matches[2]);
        }
      }
    }

    foreach ($results as $result) {
      $value = $result->getRawValue();
      if (is_numeric($value)) {
        $result->setDisplayValue($range_display[$value]);
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'range_list' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $configuration = $this->getConfiguration();
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key must be numeric range. The label will be used in displaye values of facet. ex.');
    $description .= '<br/>' . t('0-20|0-20 Minutes.');
    $description .= '<br/>' . t('20-40|20-40 Minutes.');
    $description .= '<br/>' . t('40-999|40+ Minutes.');
    $description .= '</p>';

    $build['range_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter Range'),
      '#default_value' => $configuration['range_list'],
      '#description' => $description,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $values = $form_state->getValues();
    $error = FALSE;
    $list = explode("\n", $values['range_list']);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    if (!is_array($list)) {
      $error = t('Allowed values list: invalid input.');
    }
    else {
      foreach ($list as $position => $text) {
        $matches = [];
        if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
          // Trim key and value to avoid unwanted spaces issues.
          $key = trim($matches[1]);
          $range_text = [];
          if (preg_match('/(.*)\-(.*)/', $key, $range_text)) {
            $range_start = trim($range_text[1]);
            $range_stop = trim($range_text[2]);
            if (!is_numeric($range_start) || !is_numeric($range_stop)) {
              $error = t('Allowed values list: each key must be a valid integer or decimal.');
              break;
            }
          }
        }
      }
    }

    if ($error) {
      $form_state->setErrorByName('range_list', $error);
    }
    return parent::validateConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'numeric_range';
  }

}
