<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/pagamento.php');
include(dirname(__FILE__).'/cobredireto.php');

$cobredireto = new CobreDireto();
$conf = Configuration::getMultiple(array('CD_COD_LOJA', 'CD_COD_USER', 'CD_PASSWORD', 'CD_AMBIENTE'));

$cart = new Cart(intval($cookie->id_cart));

if (isset($cart->id_address_delivery) AND $cart->id_address_delivery)
	$address = new Address((int)($cart->id_address_delivery));

$country = new Country(intval($address->id_country));

$state = NULL;
if ($address->id_state)
	$state = new State(intval($address->id_state));

$customer = new Customer(intval($cart->id_customer));
$currency_order = new Currency(intval($cart->id_currency));
$currency_module = $cobredireto->getCurrency();

// check currency of payment
if ($currency_order->id != $currency_module->id)
{
	$cookie->id_currency = $currency_module->id;
	$cart->id_currency = $currency_module->id;
	$cart->update();
}

//$cobredireto->validateOrder($cart->id, _PS_OS_PAYMENT_, floatval($cart->getOrderTotal(true, 3)), $cobredireto->displayName);

$url = Tools::getHttpHost(true, true).__PS_BASE_URI__;
define('CD_CODLOJA'     , $conf['CD_COD_LOJA']);
define('CD_USUARIO'     , $conf['CD_COD_USER']);
define('CD_SENHA'       , $conf['CD_PASSWORD']);
define('CD_AMBIENTE'    , $conf['CD_AMBIENTE']);
define('CD_URL_RECIBO'  , "{$url}order-confirmation.php?key={$customer->secure_key}&id_cart={$cart->id}&id_module={$cobredireto->id}");

$pg = new Pg(intval($cookie->id_cart));
$pg->frete(number_format(floatval($cart->getOrderShippingCost()),2,'',''));

$street=explode(',', $address->address1);            
$street = array_slice(array_merge($street, array("","","")),0,3); 
list($rua, $numero, $complemento) = $street;      

$data = array (
    'primeiro_nome' => $address->firstname,
    'meio_nome'     => '',
    'ultimo_nome'   => $address->lastname,
    'email'         => $customer->email,
    'documento'     => '',
    'tel_casa'      => array (
      'area'    => '99',
      'numero'  => '99999999',
    ),
    'cep'           => $address->postcode,
    'rua'           => $rua,
    'numero'        => $numero,
    'complemento'   => $complemento,
    'bairro'        => $address->address2,
    'estado'        => $state->name,
    'cidade'        => $address->city,
    'pais'          => $country->iso_code
);
$pg->endereco($data,'ENTREGA');

if (isset($cart->id_address_invoice) AND $cart->id_address_invoice)
	$address = new Address(intval($cart->id_address_invoice));
$country = new Country(intval($address->id_country));
$state = NULL;
if ($address->id_state)
	$state = new State(intval($address->id_state));

$data = array (
    'primeiro_nome' => $customer->firstname,
    'meio_nome'     => '',
    'ultimo_nome'   => $customer->lastname,
    'email'         => $customer->email,
    'documento'     => '',
    'tel_casa'      => array (
      'area'    => '99',
      'numero'  => '99999999',
    ),
    'cep'           => '',
    'rua'           => $rua,
    'numero'        => $numero,
    'complemento'   => $complemento,
    'bairro'        => $address->address2,
    'estado'        => $state->name,
    'cidade'        => $address->city,
    'pais'          => $country->iso_code
);
$pg->endereco($data,'COBRANCA');
$pg->endereco($data,'CONSUMIDOR');

$products = $cart->getProducts();
$prods = array();
foreach ($products as $p){
    $prods[] = array(
        "id"=>$p['id_product'],
        "descricao"=>$p['name'],
        "quantidade"=>$p['quantity'],
        "valor"=>$p['price'],        
    );
}
$pg->adicionar($prods);

if (isset($_POST) & isset($_POST['pgto']) & (in_array($_POST['pgto']))){
    $pg->pagamento($_POST['pgto']);
}

$pg->pagar();
