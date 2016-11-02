<?php

namespace Drupal\commerce_price\Element;

use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a price form element.
 *
 * Usage example:
 * @code
 * $form['amount'] = [
 *   '#type' => 'commerce_price',
 *   '#title' => $this->t('Amount'),
 *   '#default_value' => ['number' => '99.99', 'currency_code' => 'USD'],
 *   '#size' => 60,
 *   '#maxlength' => 128,
 *   '#required' => TRUE,
 * ];
 * @endcode
 *
 * @FormElement("commerce_price")
 */
class Price extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#size' => 10,
      '#maxlength' => 128,
      '#default_value' => NULL,
      '#attached' => [
        'library' => ['commerce_price/admin'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#process' => [
        [$class, 'processElement'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#input' => TRUE,
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (is_array($input) && isset($input['number'])) {
      // Convert an empty string value to a numeric value.
      if ($input['number'] === '') {
        $input['number'] = '0';
      }
      return $input;
    }
    return NULL;
  }

  /**
   * Builds the commerce_price form element.
   *
   * @param array $element
   *   The initial commerce_price form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built commerce_price form element.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #default_value is not an instance of
   *   \Drupal\commerce_price\Price.
   */
  public static function processElement(array $element, FormStateInterface $form_state, &$complete_form) {
    $default_value = $element['#default_value'];
    if (isset($default_value) && !self::validateDefaultValue($default_value)) {
      throw new \InvalidArgumentException('The #default_value for a commerce_price element must be an array with "number" and "currency_code" keys.');
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    /** @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface $number_formatter */
    $number_formatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance(NumberFormatterInterface::DECIMAL);
    $number_formatter->setMaximumFractionDigits(6);
    $number_formatter->setGroupingUsed(FALSE);

    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
    $currencies = $currency_storage->loadMultiple();
    $currency_codes = array_keys($currencies);
    // Stop rendering if there are no currencies available.
    if (empty($currency_codes)) {
      return $element;
    }
    $fraction_digits = [];
    foreach ($currencies as $currency) {
      $fraction_digits[] = $currency->getFractionDigits();
    }
    $number_formatter->setMinimumFractionDigits(min($fraction_digits));

    $number = NULL;
    if (isset($default_value)) {
      // Convert the stored amount to the local format. For example, "9.99"
      // becomes "9,99" in many locales. This also strips any extra zeroes,
      // as configured via $this->numberFormatter->setMinimumFractionDigits().
      $number = $number_formatter->format($default_value['number']);
    }

    $element['#tree'] = TRUE;
    $element['#attributes']['class'][] = 'form-type-commerce-price';

    $element['number'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#default_value' => $number,
      '#required' => $element['#required'],
      '#size' => $element['#size'],
      '#maxlength' => $element['#maxlength'],
      // Provide an example to the end user so that they know which decimal
      // separator to use. This is the same pattern Drupal core uses.
      '#placeholder' => $number_formatter->format('9.99'),
    ];
    unset($element['#size']);
    unset($element['#maxlength']);

    if (count($currency_codes) == 1) {
      $last_visible_element = 'number';
      $currency_code = reset($currency_codes);
      $element['number']['#field_suffix'] = $currency_code;
      $element['currency_code'] = [
        '#type' => 'hidden',
        '#value' => $currency_code,
      ];
    }
    else {
      $last_visible_element = 'currency_code';
      $element['currency_code'] = [
        '#type' => 'select',
        '#title' => t('Currency'),
        '#default_value' => $default_value ? $default_value['currency_code'] : NULL,
        '#options' => array_combine($currency_codes, $currency_codes),
        '#title_display' => 'invisible',
        '#field_suffix' => '',
      ];
    }
    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element[$last_visible_element]['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }

    return $element;
  }

  /**
   * Validates the default value.
   *
   * @param mixed $default_value
   *   The default value.
   *
   * @return bool
   *   TRUE if the default value is valid, FALSE otherwise.
   */
  public static function validateDefaultValue($default_value) {
    if (!is_array($default_value)) {
      return FALSE;
    }
    if (!array_key_exists('number', $default_value) || !array_key_exists('currency_code', $default_value)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Validates the price element.
   *
   * Converts the number back to the standard format (e.g. "9,99" -> "9.99").
   *
   * @param array $element
   *   The commerce_price form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    /** @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface $number_formatter */
    $number_formatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance();

    $value = $form_state->getValue($element['#parents']);
    if (empty($value['number'])) {
      return;
    }

    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = $currency_storage->load($value['currency_code']);
    $value['number'] = $number_formatter->parseCurrency($value['number'], $currency);
    if ($value['number'] === FALSE) {
      $form_state->setError($element['number'], t('%title is not numeric.', [
        '%title' => $element['#title'],
      ]));
      return;
    }

    $form_state->setValueForElement($element, $value);
  }

}
