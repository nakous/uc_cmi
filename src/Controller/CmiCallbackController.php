<?php

namespace Drupal\uc_cmi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\uc_order\Entity\Order;
use Drupal\user\Entity\User
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * Returns response for cmi Form Payment Method.
 */
class CmiCallbackController extends ControllerBase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The error and warnings logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $log;

  /**
   * Constructs a cmiFormController.
   *
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger.
   */
  public function __construct(PaymentMethodManager $payment_method_manager, SessionInterface $session, LoggerChannelFactory $logger) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->session = $session;
    $this->log = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_payment.method'),
      $container->get('session'),
      $container->get('logger.factory')
    );
  }

  
  /**
   * cmi callback request.
   *
   * @todo Handle Callback from cmi payment gateway.
   */
  public function CmiMobileOK() { 
			$para=$_POST;
			$msg= "";
			// $msgNo= 0;
			$postParams = array();
			foreach ($_POST as $key => $value){
				array_push($postParams, $key);				
				// echo "<tr><td>" . $key ."</td><td>" . $value . "</td></tr>";
			}
			
			
			natcasesort($postParams);		
			
			$hashval = "";					
			foreach ($postParams as $param){				
				$paramValue = trim(html_entity_decode($_POST[$param], ENT_QUOTES, 'UTF-8')); 
				$escapedParamValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $paramValue));	
					
				$lowerParam = strtolower($param);
				if($lowerParam != "hash" && $lowerParam != "encoding" )	{
					$hashval = $hashval . $escapedParamValue . "|";
				}
			}
			if(!isset($_POST['oid']))
					return;
				
			$order = Order::load($_POST['oid']);
			$plugin = $this->paymentMethodManager->createFromOrder($order);
			$adminconfiguration = $plugin->getConfiguration();			
			$storeKey = $adminconfiguration['api']['user_api_key'];
			
			// $storeKey = "TEST1234";
			$escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));	
			$hashval = $hashval . $escapedStoreKey;
			
			$calculatedHashValue = hash('sha512', $hashval);  
			$actualHash = base64_encode (pack('H*',$calculatedHashValue));
			
			$retrievedHash = $_POST["HASH"];
			if($retrievedHash == $actualHash )	{
				$para['msg']= "Your order was successfully with Payment ID: ".$_POST["acqStan"];	
				$para['msgNo']= 1; 
			}else {
				$para['msg']=  "Security Alert. The digital signature is not valid."  ;
				$para['msgNo']= 0;
			}		
		return new JsonResponse($para);
  }
  public function CmiOK() {
			$msg= "";
			$postParams = array();
			foreach ($_POST as $key => $value){
				array_push($postParams, $key);				
				// echo "<tr><td>" . $key ."</td><td>" . $value . "</td></tr>";
			}
			
			
			natcasesort($postParams);		
			
			$hashval = "";					
			foreach ($postParams as $param){				
				$paramValue = trim(html_entity_decode($_POST[$param], ENT_QUOTES, 'UTF-8')); 
				$escapedParamValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $paramValue));	
					
				$lowerParam = strtolower($param);
				if($lowerParam != "hash" && $lowerParam != "encoding" )	{
					$hashval = $hashval . $escapedParamValue . "|";
				}
			}
			if(!isset($_POST['oid']))
					return;
				
			$order = Order::load($_POST['oid']);
			$plugin = $this->paymentMethodManager->createFromOrder($order);
			$adminconfiguration = $plugin->getConfiguration();			
			$storeKey = $adminconfiguration['api']['user_api_key'];
			
			// $storeKey = "TEST1234";
			$escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));	
			$hashval = $hashval . $escapedStoreKey;
			
			$calculatedHashValue = hash('sha512', $hashval);  
			$actualHash = base64_encode (pack('H*',$calculatedHashValue));
			
			$retrievedHash = $_POST["HASH"];
			if($retrievedHash == $actualHash )	{
				$msg= "<h4>Your order was successfully with Payment ID: ".$_POST["acqStan"]."</h4>"  . " <br />\r\n";	
				$this->session->set('uc_checkout_complete_' . $_POST['oid'], TRUE);
			}else {
				$msg=  "<h4>Security Alert. The digital signature is not valid.</h4>"  . " <br />\r\n";
			}		
		return  array(
		  '#type' => 'markup',
		  '#markup' => $msg,
		) ;
  } 
  
  public function CmiSendData(Request $reques) {
  
	
	
	$output='
	
	<html>
<head>
<title>Generic Hash Request Handler</title>
<meta http-equiv="Content-Language" content="tr">
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-9">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="now">
</head>

<body onload="javascript:moveWindow()">
	
	
	<form name="pay_form" id="form-pyement-cmi-12045" method="post" action="https://testpayment.cmi.co.ma/fim/est3Dgate">';
		
			
			
			$postParams = array();
			foreach ($_POST as $key => $value){
				array_push($postParams, $key);
				$output.= "<input type=\"hidden\" name=\"" .$key ."\" value=\"" .trim($value)."\" />";
			}
			// print_r($postParams);
			// $order = Order::load($_POST['oid']);
			// if(!$order)
				// return new Response("order not found!".$_POST['oid']);;
			
			natcasesort($postParams);		
			
			// $plugin = $this->paymentMethodManager->createFromOrder($order);
			// $adminconfiguration = $plugin->getConfiguration();			
			// $storeKey = $adminconfiguration['api']['user_api_key'];
			$storeKey = "201704271502";
			$hashval = "";					
			foreach ($postParams as $param){				
				$paramValue = trim($_POST[$param]);
				$escapedParamValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $paramValue));	
					
				$lowerParam = strtolower($param);
				if($lowerParam != "hash" && $lowerParam != "encoding" )	{
					$hashval = $hashval . $escapedParamValue . "|";
				}
			}
			
			
			$escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));	
			$hashval = $hashval . $escapedStoreKey;
			
			$calculatedHashValue = hash('sha512', $hashval);  
			$hash = base64_encode (pack('H*',$calculatedHashValue));
			
			$output.= "<input type=\"hidden\" name=\"HASH\" value=\"" .$hash."\" />";			
		
	
	// $output.='<input  value="Appliquer"   type="submit">';
	$output.='</form>
		
	  <script type="text/javascript" language="javascript">
        function moveWindow() {
           document.pay_form.submit();
        }
    </script>

</body>

</html>
	
	';
	return new Response($output);
	// return  array(
		  // '#type' => 'inline_template', 
		  // '#children' => $output,
		// ) ;
  }
  
  /**
   * cmi callback request.
   *
   * @todo Handle Callback from cmi payment gateway.
   */
  public function CmiFail() {
			$postParams = array();
			foreach ($_POST as $key => $value){
				array_push($postParams, $key);				
				// echo "<tr><td>" . $key ."</td><td>" . $value . "</td></tr>";
			}
			
			natcasesort($postParams);		
			
			$hashval = "";					
			foreach ($postParams as $param){				
				$paramValue = trim(html_entity_decode($_POST[$param], ENT_QUOTES, 'UTF-8')); 
				$escapedParamValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $paramValue));	
					
				$lowerParam = strtolower($param);
				if($lowerParam != "hash" && $lowerParam != "encoding" )	{
					$hashval = $hashval . $escapedParamValue . "|";
				}
			}
			
			$order = Order::load($_POST['oid']);
			$plugin = $this->paymentMethodManager->createFromOrder($order);
			$adminconfiguration = $plugin->getConfiguration();			
			$storeKey = $adminconfiguration['api']['user_api_key'];
			
			$escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));	
			$hashval = $hashval . $escapedStoreKey;
			
			$calculatedHashValue = hash('sha512', $hashval);  
			$actualHash = base64_encode (pack('H*',$calculatedHashValue));
			
			$retrievedHash = $_POST["HASH"];
			if($retrievedHash == $actualHash )	{
				$msg ="<h4>HASH is successfull</h4>"  . " <br />\r\n";	
			}else {
				$msg ="<h4>Security Alert. The digital signature is not valid.</h4>"  . " <br />\r\n";
			}		
  
  
	return ;
  }
  /**
   * cmi callback request.
   *
   * @todo Handle Callback from cmi payment gateway.
   */
  public function CmiCallback() {
  
		
	
	$text="";
	$postParams = array();
	\Drupal::logger('uc_cmi')->notice(" - ".print_r($_POST,true));
	foreach ($_POST as $key => $value){
		array_push($postParams, $key);				
	}
	if(isset($_POST['oid'])){
		
		// $order_id=str_replace("ref-", "", $_POST['oid']);
		$order_id= $_POST['oid'];
	$order = Order::load($order_id);
			// $plugin = $this->paymentMethodManager->createFromOrder($order);
			// $adminconfiguration = $plugin->getConfiguration();			
			// $storeKey = $adminconfiguration['api']['user_api_key'];
			$storeKey = "201704271502";
	natcasesort($postParams);		
	$hach = "";
	$hashval = "";					
	foreach ($postParams as $param){				
	    $paramValue = html_entity_decode(preg_replace("/\n$/","",$_POST[$param]), ENT_QUOTES, 'UTF-8'); 

		$hach = $hach . "(!".$param."!:!".$_POST[$param]."!)";
		$escapedParamValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $paramValue));	
			
		$lowerParam = strtolower($param);
		if($lowerParam != "hash" && $lowerParam != "encoding" )	{
			$hashval = $hashval . $escapedParamValue . "|";
		}
	}
	
	
	$escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));	
	$hashval = $hashval . $escapedStoreKey;
	
	$calculatedHashValue = hash('sha512', $hashval);  
	$actualHash = base64_encode (pack('H*',$calculatedHashValue));
	}
	$retrievedHash = $_POST["HASH"];
	if($retrievedHash == $actualHash && $_POST["ProcReturnCode"] == "00" )	{
		//	"Il faut absolument verifier toutes les informations envoyées par MTC (requete server-to-server) avec les données du site avant de procéder à la confirmation de la transaction!"
		//	"Par exemple le montant envoyé dans la requête de MTC doit correspondre exactement au montant de la commande enregistré dans la BDD du site marchand.
		//  "Mettre à jour la base de données du site marchand en vérifiant si la commande existe et correspond au retour MTC!"
		//  "Dans cette MAJ, il faut enregistrer le n° du Bon de commande de paiement envoyé dans le paramètre ""orderNumber"" "
		   	
		   $comment = $this->t('Cmi transaction ID: @txn_id', ['@txn_id' => $_POST["TransId"]]);
			uc_payment_enter($order_id, 'cmi_form_gateway', $_POST["amount"], $order->getOwnerId(), NULL, $comment);
		   $order->setStatusId('payment_received')->save();
		   //chnage le role 
				$user = User::load($order->getOwnerId());
				if($user){
					$user->addRole('abonne');
					$user->save();
				}
				
				 
				// $user->removeRole('administrator');
				// $user->save();
		   // ajouter les categorie (produit) 
		   // date d'abonnoment
		
		   uc_order_comment_save($order_id, $order->getOwnerId(), $this->t('Your order was successfully with Payment ID: @payment_id.',
              [
                '@payment_id' => $_POST["acqStan"],
              ]
            ), 'admin');
			$text = "ACTION=POSTAUTH";
			
	}else {
		   $text= "APPROVED";
	}
  
	return new Response($text);

  }

 

}
