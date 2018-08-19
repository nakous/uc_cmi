<?php

namespace Drupal\uc_cmi\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;

/**
 * cmi Ubercart gateway payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "cmi_gateway",
 *   name = @Translation("Paiement CMI"),
 *   label = @Translation("Le centre monétique interbancaire"),
 * )
 */
class CmiPaymentForm extends PaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface {



  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $build['label'] = [
      '#prefix' => ' ',
      '#plain_text' => $label,
      '#suffix' => ' ',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
  
	  return parent::defaultConfiguration() + [ 
		  '3d_secure'    => '',// url paiement

		  'api' => [
				'merchant_id'     => '', // Identifiant marchand (ClientId) 
				'user_api_key'    => '', // Clé de hachage
		  ],
		   'callbacks' => [
				'continue_url'    => '', 
				'callback_url'      => '',  
				'cancel_url'      => '',  
			  ],
	  ];

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
   
   
	$form['3d_secure'] = [
      '#type' => 'textfield',
      '#title' => $this->t('3D Secure Url'),
      '#description' => $this->t('Checked 3D Secure Creditcard Url'),
      '#default_value' => $this->configuration['3d_secure'],
    ];
	 $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('API credentials'),  
      '#open' => TRUE,
    ];
    $form['api']['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['api']['merchant_id'],
      '#description' => $this->t('This is your Merchant Account id.'),
      '#required' => TRUE,
    ];
    $form['api']['user_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API user key'),
      '#default_value' => $this->configuration['api']['user_api_key'],
      '#description' => $this->t('This is an API user key.'),
      '#required' => TRUE,
    ];
   
    $form['callbacks'] = [
      '#type' => 'details',
      '#title' => $this->t('CALLBACKS'),
      '#description' => $this->t('cmi callback urls.'),
      '#open' => TRUE,
    ];
    $form['callbacks']['continue_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Continue URL'),
      '#default_value' => $this->configuration['callbacks']['continue_url'],
      '#description' => $this->t('The customer will be redirected to this URL upon a successful payment. No data will be send to this URL.'),
    ];
	$form['callbacks']['callback_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('callback URL'),
      '#default_value' => $this->configuration['callbacks']['callback_url'],
      '#description' => $this->t('Callback URL.'),
    ];
    $form['callbacks']['cancel_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel URL'),
      '#default_value' => $this->configuration['callbacks']['cancel_url'],
      '#description' => $this->t('The customer will be redirected to this URL if the customer cancels the payment. No data will be send to this URL.'),
    ];
   
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $elements = [
      'merchant_id',
      'user_api_key',
    ];
    foreach ($elements as $element_name) {
      $raw_key = $form_state->getValue(['settings', 'api', $element_name]);
      $sanitized_key = $this->trimKey($raw_key);
      $form_state->setValue(['settings', $element_name], $sanitized_key);

    }
    parent::validateConfigurationForm($form, $form_state);
  }


  /**
   * Checking vaildation keys of payment gateway.
   */
  protected function trimKey($key) {
    $key = trim($key);
    $key = Html::escape($key);
    return $key;
  }

  /**
   * Validate cmi key.
   *
   * @var $key
   *   Key which passing on admin side.
   *
   * @return bool
   *   Return that is key is vaild or not.
   */
  public function validateKey($key) {
    $valid = preg_match('/^[a-zA-Z0-9_]+$/', $key);
    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $elements = [
      'merchant_id',
      'user_api_key',
    ];
    foreach ($elements as $item) {
      $this->configuration['api'][$item] = $form_state->getValue([
        'settings',
        'api',
        $item,
      ]);
    }
    $this->configuration['3d_secure'] = $form_state->getValue('3d_secure');
    $this->configuration['callbacks']['continue_url'] = $form_state->getValue([
      'settings',
      'callbacks',
      'continue_url',
    ]);
    $this->configuration['callbacks']['callback_url'] = $form_state->getValue([
      'settings',
      'callbacks',
      'callback_url',
    ]);
	$this->configuration['callbacks']['cancel_url'] = $form_state->getValue([
      'settings',
      'callbacks',
      'cancel_url',
    ]);
    return parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $payment_id = db_query("SELECT payment_id FROM {uc_payment_cmi_callback} WHERE order_id = :id ORDER BY created_at ASC", [':id' => $order->id()])->fetchField();
    if (empty($payment_id)) {
      $payment_id = $this->t('Unknown');
    }
    $build['#markup'] = $this->t('Payment ID: @payment_id', ['@payment_id' => $payment_id]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    // Get billing address object.
    $bill_address = $order->getAddress('billing');
    $country = $country = \Drupal::service('country_manager')->getCountry($bill_address->country)->getAlpha3();
    // Formate current with multiply 100.
    $amount_currency = uc_currency_format($order->getTotal(), FALSE, FALSE, FALSE);
    $data = [];
	
	
				$data['clientid'] =$this->configuration['api']['merchant_id']; 
				$data['amount'] =$amount_currency;
				$data['okUrl'] =Url::fromRoute('uc_cmi.qpf_ok', [], ['absolute' => TRUE])->toString();
				$data['failUrl'] =Url::fromRoute('uc_cmi.qpf_fail', [], ['absolute' => TRUE])->toString();
				$data['TranType'] ="PreAuth";
				$data['callbackUrl'] =Url::fromRoute('uc_cmi.qpf_callback', [], ['absolute' => TRUE])->toString();
				// $data['shopurl'] =Url::fromRoute('<front>', [], ['absolute' => TRUE]);
				$data['currency'] ="504";
				$data['rnd'] =microtime();
				$data['storetype'] ="3D_PAY_HOSTING";
				$data['hashAlgorithm'] ="ver3";
				$data['lang'] ="fr";
				$data['refreshtime'] ="5";
				$data['BillToName'] =$bill_address->first_name . " " . $bill_address->last_name;
				$data['BillToCompany'] ="billToCompany";
				$data['BillToStreet1'] =$bill_address->street1;
				$data['BillToCity'] =$bill_address->city;
				$data['BillToStateProv'] =$bill_address->zone;
				$data['BillToPostalCode'] =$bill_address->postal_code;
				$data['BillToCountry'] ="504";
				$data['email'] =$order->getEmail();
				$data['tel'] ="0021201020304";
				$data['encoding'] ="UTF-8";
				$data['oid'] =$order->id();
	// Add hidden field with new form.
    foreach ($data as $name => $value) { 
      if (!empty($value)) {
        $form[$name] = ['#type' => 'hidden', '#value' => $value];
      }
    }
    $form['#action'] = Url::fromRoute('uc_cmi.qpf_senddata', [], ['absolute' => TRUE])->toString() ; 
    // $form['#action'] = $this->configuration['3d_secure'];
    $form['actions'] = ['#type' => 'actions'];
    // Text alter.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('cmi Payment'),
      '#id' => 'cmi-submit',
    ];
    return $form;
  }

  /**
   * Utility function: Load cmi API.
   *
   * @return bool
   *   Checking prepareApi is set or not.
   */
  public function prepareApi() {
    // Checking API keys configuration.
    if (!_uc_cmi_check_api_keys($this->getConfiguration())) {
      \Drupal::logger('uc_cmi')->error('cmi API keys are not configured. Payments cannot be made without them.', []);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Calculate the hash for the request.
   *
   * @var array $var
   *   The data to POST to cmi.
   *
   * @return string
   *   The checksum.
   */
  protected function checksumCal($params, $api_key) {
    $flattened_params = $this->flattenParams($params);
    ksort($flattened_params);
    $base = implode(" ", $flattened_params);
    return hash_hmac("sha256", $base, $api_key);
  }

  /**
   * Flatten request parameter array.
   */
  protected function flattenParams($obj, $result = [], $path = []) {
    if (is_array($obj)) {
      foreach ($obj as $k => $v) {
        $result = array_merge($result, $this->flattenParams($v, $result, array_merge($path, [$k])));
      }
    }
    else {
      $result[implode("", array_map(function ($p) {
        return "[{$p}]";
      }, $path))] = $obj;
    }
    return $result;
  }

}
