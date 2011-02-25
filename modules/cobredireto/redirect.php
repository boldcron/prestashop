<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/pagamento.php');
include(dirname(__FILE__).'/cobredireto.php');

$cobredireto = new CobreDireto();
$conf = Configuration::getMultiple(array('CD_COD_LOJA', 'CD_COD_USER', 'CD_PASSWORD', 'CD_AMBIENTE'));
$cart = new Cart(intval($cookie->id_cart));

$address = new Address(intval($cart->id_address_invoice));
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

$cobredireto->validateOrder($cart->id, _PS_OS_PAYMENT_, floatval($cart->getOrderTotal(true, 3)), $cobredireto->displayName);

$url = Tools::getHttpHost(true, true).__PS_BASE_URI__;
define('CD_CODLOJA',  $conf['CD_COD_LOJA']);
define('CD_USUARIO',  $conf['CD_COD_USER']);
define('CD_SENHA',    $conf['CD_PASSWORD']);
define('CD_AMBIENTE', $conf['CD_AMBIENTE']);
define('CD_URL_RECIBO', "{$url}order-confirmation.php?key={$customer->secure_key}&id_cart={$cart->id}&id_module={$cobredireto->id}");

$pg = new Pg(intval($cookie->id_cart));

$pg->frete(number_format(floatval($cart->getOrderShippingCost()),2,'',''));

$data = array (
    'primeiro_nome' => $address->firstname,
    'meio_nome'     => '',
    'ultimo_nome'   => $address->lastname,
    'email'         => $address->email,
    'documento'     => '',
    'tel_casa'      => array (
      'area'    => '99',
      'numero'  => '99999999',
    ),
    'cep'           => $address->postcode,
);
$pg->endereco($data,'ENTREGA');

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
);
$pg->endereco($data,'COBRANCA');
$pg->endereco($data,'CONSUMIDOR');

$products = $cart->getProducts();
$prods = array();
foreach ($products as $p){
    $prods[] = array(
        "descricao"=>$p['name'],
        "valor"=>$p['price'],
        "quantidade"=>$p['quantity'],
        "id"=>$p['id_product']
    );
}
$pg->adicionar($prods);

if (isset($_POST) & isset($_POST['pgto']) & (in_array($_POST['pgto'],$cobredireto->formas))){
    $pg->pagamento($_POST['pgto']);
}

$pg->pagar();
