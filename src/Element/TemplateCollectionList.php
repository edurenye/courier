<?php

/**
 * @file
 * Contains \Drupal\courier\Element\TemplateCollectionList.
 */

namespace Drupal\courier\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;

/**
 * Provides a template collection list element.
 *
 * Can be used outside of a form.
 *
 * @FormElement("courier_template_collection_list")
 */
class TemplateCollectionList extends FormElement {

  /**
   * @inheritDoc
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => array(
        [$class, 'processTemplateCollectionList'],
      ),
      // Items can be any non-zero key. Forms will return this key for keys of
      // checkboxes in $form_element['checkboxes'].
      '#items' => [],
      '#checkboxes' => FALSE,
      '#attributes' => [],
      '#value' => NULL,
    ];
  }

  public static function processTemplateCollectionList(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;
    $element['#attached']['library'][] = 'courier/courier.template_collection_list';

    $element['template_collection_list'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['template_collection_list'],
      ],
    ];

    if ($element['#checkboxes']) {
      $element['template_collection_list']['#attributes']['class'][] = 'checkboxes';
    }

    $entity_manager = \Drupal::entityManager();
    $destination = \Drupal::destination()->getAsArray();
    /** @var \Drupal\courier\Service\IdentityChannelManagerInterface $icm */
    $icm = \Drupal::service('plugin.manager.identity_channel');
    $channels_all = array_keys($icm->getChannels());

    foreach ($element['#items'] as $id => $setting) {
      /** @var \Drupal\courier\TemplateCollectionInterface $template_collection */
      $template_collection = $setting['#template_collection'];

      $t_args = [
        '@id' => $template_collection->id(),
        '@label' => $setting['#title'],
      ];

      $element['template_collection_list'][$id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'template_collection',
          'template_collection' => $template_collection->id(),
        ],
      ];
      $row = &$element['template_collection_list'][$id];

      if ($element['#checkboxes']) {
        $parents = array_merge($element['#parents'], ['checkboxes', $id]);
        $row['checkbox'] = [
          '#type' => 'checkbox',
          '#id' => Html::getUniqueId('edit-' . implode('-', $parents)),
          '#title' => t('Select Template Collection @id', $t_args),
          '#title_display' => 'hidden',
          '#parents' => $parents,
        ];
      }

      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $setting['#operations'],
      ];

      $row['title']['#markup'] = '<h1>' . t('@label', $t_args) . '</h1>';

      if (isset($setting['#description'])) {
        $row['description']['#markup'] = '<p>' . $setting['#description'] . '</p>';
      }

      $row['templates'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['ui', 'top', 'attached', 'menu', 'small', 'compact', 'pointing'],
        ],
      ];

      // Template links.
      $row['templates']['links'] = [
        '#prefix' => '<div class="item header">' . t('Messages') . ':</div>',
        '#theme' => 'item_list',
        '#items' => [],
        '#attributes' => [
          'class' => [
            'templates',
          ],
        ],
      ];

      foreach ($channels_all as $channel) {
        $url = Url::fromRoute('entity.courier_template_collection.channel')
          ->setRouteParameter('courier_template_collection', $template_collection->id())
          ->setRouteParameter('courier_channel', $channel)
          ->setOption('attributes', [
            'entity_type' => $channel,
            'class' => ['item']
          ])
          ->setOption('query', $destination);

        $row['templates']['links']['#items'][] = new Link(
          $entity_manager->getDefinition($channel)->getLabel(),
          $url
        );
      }
    }

    return $element;
  }

}
