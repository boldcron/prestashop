<?php

if (!defined('_CAN_LOAD_FILES_'))
	exit;

class cobredireto extends PaymentModule
{
    private	$_html = '';
	private $_postErrors = array();
    private $all_formas = array (
            array(
                'title'                 => 'Cartões de crédito',
                'visa3dc'               => 'Visa VBV',
                'redecard_mastercard'   => 'Mastercard Komerci',
                'redecard_diners'       => 'Diners Komerci',
                'amex_webpos2p'         => 'Amex WebPOS 2P',
                'redecard_visa'         => 'Komerci Visa',
                'redecard_ws_visa'      => 'Komerci WS Visa',
                'cielo2p_master'        => 'Moset Master',
                'cielo2p_visa'          => 'Moset Visa',
                'cielo3p_mastercard'    => 'MASTERCARD VBV',
                'redecard_ws_mastercard'=> 'Komerci WS MASTERCARD',
                'redecard_ws_diners'    => 'Komerci WS Diners',
                'setef_hipercard'       => 'Hipercard Setef',
            ),
            array(
                'title'                 => 'Débito/transferência online',
                'bradesco'              => 'Bradesco',
                'itau'                  => 'Itaú',
                'bb'                    => 'Banco do Brasil',
                'unibanco'              => 'Unibanco',
                'real'                  => 'Real',
                'banrisul_pgta'         => 'Banrisul'
            ),
            array (
                'title'                 => 'Boleto bancário',
                'boleto_bradesco'       => 'Bradesco',
                'boleto_itau'           => 'Itaú',
                'boleto_bb'             => 'Banco do Brasil',
                'boleto_unibanco'       => 'Unibanco',
                'boleto_real'           => 'Real',
            )
        );

    public $formas;

	  public function __construct(){
		    $this->name = 'cobredireto';
		    $this->tab = 'Payment';
		    $this->version = '0.1';
		    
		    $this->currencies = true;
		    $this->currencies_mode = 'radio';

        parent::__construct();

		    $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('CobreDireto');
        $this->description = $this->l('Aceitar pagamentos com o CobreDireto');
		    $this->confirmUninstall = $this->l('Você tem certeza que deseja deletar os seus dados ?');
		    if (trim(Configuration::get('CD_COD_LOJA')) == '' || trim(Configuration::get('CD_COD_USER')) == '' || trim(Configuration::get('CD_PASSWORD')) == '')
		    	$this->warning = $this->l('Você precisa terminar de configurar os seus dados no CobreDireto');
        
        foreach($this->all_formas as $tipos)
            foreach($tipos as $k=>$v)
                $this->formas[] = $k;
	  }

	  public function install(){
		    if (!parent::install()
			    OR !Configuration::updateValue('CD_COD_LOJA', '')
			    OR !Configuration::updateValue('CD_COD_USER', '')
			    OR !Configuration::updateValue('CD_PASSWORD', '')
			    OR !Configuration::updateValue('CD_AMBIENTE', 1)
			    OR !Configuration::updateValue('CD_FORMAS', '[]')
			    OR !$this->registerHook('payment')
			    OR !$this->registerHook('paymentReturn'))
			      return false;
		    return true;
	  }

	  public function uninstall(){
		    if ( !Configuration::deleteByName('CD_COD_LOJA')
			    OR !Configuration::deleteByName('CD_COD_USER')
			    OR !Configuration::deleteByName('CD_PASSWORD')
			    OR !Configuration::deleteByName('CD_AMBIENTE')
			    OR !Configuration::deleteByName('CD_FORMAS')
			    OR !parent::uninstall())
			      return false;
		    return true;
	  }

	  public function getContent(){
		    $this->_html = '<h2>CobreDireto</h2>';
		    if (isset($_POST['submitCobreDireto'])){
			      if (empty($_POST['cod_loja']))
                $this->_postErrors[] = $this->l('Código da loja obrigatório');
			      if (empty($_POST['cod_user']))
                $this->_postErrors[] = $this->l('Usuário obrigatório');
			      if (empty($_POST['password']))
                $this->_postErrors[] = $this->l('Senha obrigatório');
			      if (!sizeof($this->_postErrors)) {
                Configuration::updateValue('CD_COD_LOJA', strval($_POST['cod_loja']));
                Configuration::updateValue('CD_COD_USER', strval($_POST['cod_user']));
                Configuration::updateValue('CD_PASSWORD', strval($_POST['password']));
                Configuration::updateValue('CD_AMBIENTE', strval($_POST['ambiente']));
                $this->displayConf();
			      }
			      else
                $this->displayErrors();
        }
        elseif (isset($_POST['submitCobreDiretoFormas'])){
            Configuration::updateValue('CD_FORMAS', json_encode($_POST['formas_pgto']));
        }
		    $this->displayFormSettings();
		    return $this->_html;
	  }

	  public function displayConf(){
        $this->_html .= '
            <div class="conf confirm">
            <img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
            '.$this->l('Configurações atualizadas com sucesso').'
            </div>';
	  }

	  public function displayErrors(){
        $nbErrors = sizeof($this->_postErrors);
        $this->_html .= '
            <div class="alert error">
            <h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
            <ol>';
        foreach ($this->_postErrors AS $error)
            $this->_html .= '<li>'.$error.'</li>';
        $this->_html .= '
            </ol>
            </div>';
	  }
	
	
	  public function displayFormSettings()
	  {
        $conf = Configuration::getMultiple(array('CD_COD_LOJA', 'CD_COD_USER', 'CD_PASSWORD', 'CD_AMBIENTE'));
		    $cod_loja = array_key_exists('cod_loja', $_POST) ? $_POST['cod_loja'] : (array_key_exists('CD_COD_LOJA', $conf) ? $conf['CD_COD_LOJA'] : '');
		    $cod_user = array_key_exists('cod_user', $_POST) ? $_POST['cod_user'] : (array_key_exists('CD_COD_USER', $conf) ? $conf['CD_COD_USER'] : '');
		    $password = array_key_exists('password', $_POST) ? $_POST['password'] : (array_key_exists('CD_PASSWORD', $conf) ? $conf['CD_PASSWORD'] : '');
		    $ambiente = array_key_exists('ambiente', $_POST) ? $_POST['ambiente'] : (array_key_exists('CD_AMBIENTE', $conf) ? $conf['CD_AMBIENTE'] : '');

        $this->_html .= '
            <fieldset>
                <legend>'.$this->l('Pre-requisitos').'</legend>
                '.$this->checkSoap().'
                '.$this->checkOpenSSL().'
                '.$this->checkURL().'
            </fieldset>
            <form action="'.$_SERVER['REQUEST_URI'].'" method="post" style="margin-top:20px; float:left;">
                <fieldset style="width:410px;">
                    <legend><img src="../img/admin/contact.gif" />'.$this->l('Settings').'</legend>
                    <label>'.$this->l('Código da loja').'</label>
                    <div class="margin-form"><input type="text" size="33" name="cod_loja" value="'.htmlentities($cod_loja, ENT_COMPAT, 'UTF-8').'" /></div>
                    <label>'.$this->l('Usuário').'</label>
                    <div class="margin-form"><input type="text" size="33" name="cod_user" value="'.htmlentities($cod_user, ENT_COMPAT, 'UTF-8').'" /></div>
                    <label>'.$this->l('Senha').'</label>
                    <div class="margin-form"><input type="password" size="33" name="password" value="'.htmlentities($password, ENT_COMPAT, 'UTF-8').'" /></div>
                    <label>'.$this->l('Ambiente').'</label>
                    <div class="margin-form">
                        <input type="radio" name="ambiente" value="1" '.($ambiente ? 'checked="checked"' : '').' /> <label class="t">'.$this->l('Produção').'</label>
                        <input type="radio" name="ambiente" value="0" '.(!$ambiente ? 'checked="checked"' : '').' /> <label class="t">'.$this->l('Homologação').'</label>
                    </div>
                    <br />
                    <center>
                        <input type="submit" name="submitCobreDireto" value="'.$this->l('Update settings').'" class="button" />
                    </center>
                </fieldset>
            </form>
		        <form action="'.strval($_SERVER['REQUEST_URI']).'" method="post" style="margin:20px 0px 0px 20px; float:left;">
			          <fieldset style="width:428px;">
                    <legend><img src="../img/admin/payment.gif" />'.$this->l('Formas de Pagamento Habilitadas').'</legend>
                    '.$this->_montaFormas().'
                    <br />
                    <br />
                    <center>
                        <input type="submit" name="submitCobreDiretoFormas" value="'.$this->l('Salvar Configurações').'" class="button" />
                    </center>
                </fieldset>
            </form>
		        <div style="clear:both;">
            <br />
        ';
	  }

    private function checkSoap() { 
        if (class_exists('SoapClient'))
            return '<p class="ok" style="padding-left: 20px;">Classe SOAP built-in</p>';
        else
            return '<p class="fail" style="padding-left: 20px;">Classe SOAP built-in</p>';
    }

    private function checkOpenSSL() { 
        require_once(dirname(__FILE__).'/pagamento.php');
        $modules = new moduleCheck;
        if ($modules->isLoaded('openssl'))
            return '<p class="ok" style="padding-left: 20px;">Módulo OpenSSL</p>';
        else
            return '<p class="fail" style="padding-left: 20px;">Módulo OpenSSL</p>';
    }

    private function checkURL() { 
        if (intval(ini_get('allow_url_fopen')))
            return '<p class="ok" style="padding-left: 20px;">Acesso a URL Externa</p>';
        else
            return '<p class="fail" style="padding-left: 20px;">Acesso a URL Externa</p>';
    }

    public function _montaFormas() { 
        $salvas = json_decode(Configuration::get('CD_FORMAS'));
        if (!$salvas)
            $salvas = array();
        $h = '<select name="formas_pgto[]" class="cobredireto_formas" multiple="multiple" style="height: 380px; margin-left: 120px;">';
        foreach($this->all_formas as $key=>$tipos){
            $h .= '<optgroup label="'.$tipos['title'].'">';
            unset($tipos['title']);
            foreach($tipos as $k=>$v){
                $s = '';
                if (in_array($k, $salvas))
                    $s = ' selected="true"';
                $h .= '<option value="'.$k.'"'.$s.'>'.$v.'</option>';
            }
            $h .= '</optgroup>';
        }
        $h .= '</select>';
        return $h;
    }

	  public function hookPayment($params)
	  {
        global $smarty;

        if (!$this->active || Configuration::get('CD_COD_LOJA') == '')
            return ;

        $escolhidas = json_decode(Configuration::get('CD_FORMAS'));
        if (!$escolhidas)
            $escolhidas = array();
        $tipos = $this->all_formas;
        foreach($tipos as $t=>$sec){
            foreach($sec as $k=>$v){
                if (!in_array($k, $escolhidas) && ($k != 'title'))
                    unset($tipos[$t][$k]);
            }
            if (count($tipos[$t]) == 1)
                unset($tipos[$t]);
        }

		    $smarty->assign('tipos', $tipos);
        return $this->display(__FILE__, 'cobredireto.tpl');
	  }

	  public function hookPaymentReturn($params)
	  {
        if (!$this->active)
            return ;

        return $this->display(__FILE__, 'confirmation.tpl');
	  }

	  function validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod = 'Unknown', $message = NULL, $extraVars = array(), $currency_special = NULL, $dont_touch_amount = false)
	  {
        if (!$this->active)
            return ;

        parent::validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod, $message, $extraVars);
	  }
}
