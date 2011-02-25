<?php

  class moduleCheck {
    
    public $Modules;
      
    //function parseModules() {
    function __construct() {
      ob_start(); // Stop output of the code and hold in buffer
      phpinfo(INFO_MODULES); // get loaded modules and their respective settings.
      $data = ob_get_contents(); // Get the buffer contents and store in $data variable
      ob_end_clean(); // Clear buffer
      
      $data = strip_tags($data,'<h2><th><td>'); // Keep only the items in the <h2>,<th> and <td> tags
       
      // Use regular expressions to filter out needed data
      // Replace everything in the <th> tags and put in <info> tags
      $data = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$data);
       
      // Replace everything in <td> tags and put in <info> tags
      $data = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$data);
       
      // Split the data into an array
      $vTmp = preg_split('/(<h2>[^<]+<\/h2>)/',$data,-1,PREG_SPLIT_DELIM_CAPTURE);
      $vModules = array();
      $count = count($vTmp);
      for ($i=1;$i<$count; $i+=2) { // Loop through array and add 2 instead of 1
        
        if (preg_match('/<h2>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) { // Check to make sure value is a module
          $moduleName = trim($vMat[1]); // Get the module name 
          $vTmp2 = explode("\n",$vTmp[$i+1]);
          foreach ($vTmp2 AS $vOne) {
            $vPat = '<info>([^<]+)<\/info>'; // Specify the pattern we created above
            $vPat3 = "/$vPat\s*$vPat\s*$vPat/"; // Pattern for 2 settings (Local and Master values)
            $vPat2 = "/$vPat\s*$vPat/"; // Pattern for 1 settings
            if (preg_match($vPat3,$vOne,$vMat)) { // This setting has a Local and Master value
              $vModules[$moduleName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
            } elseif (preg_match($vPat2,$vOne,$vMat)) { // This setting only has a value
              $vModules[$moduleName][trim($vMat[1])] = trim($vMat[2]);
            }
          }
         
        }
      }
      $this->Modules = $vModules; // Store modules in Modules variable
    }
      
    // Quick check if module is loaded
    // Returns true if loaded, false if not
    public function isLoaded($moduleName) {
      if($this->Modules[$moduleName]) { 
        return true;
      }
      return false;
    } // End function isLoaded
      
    // List all php modules installed with no settings
    public function listModules() {
      foreach($this->Modules as $moduleName=>$values) { // Loop through modules
        // $moduleName is the key of $this->Modules, which is also module name
        $onlyModules[] = $moduleName;
      }
      return $onlyModules; // Return array of all module names
    } // End function listModules();
  }

  /**
    * Classe para integração com o CobreDireto
    *
    * Classe com a finalidade de facilitar a integração com CobreDireto
    * utilizando de métodos simples e objetivos
    * e já realizando as consultas e consumos dos webservices do CobreDireto
    *
    * @package CobreDireto
    * @author RZamana <zamana@visie.com.br>
    * @date 05/14/2009
    * @version 0.1.1
    * @abstract
    **/
    abstract class CobreDiretoABS {
    
    /**
      * Código da Loja dentro do CobreDireto
      * @var int
      * @access private
      **/
    private $codloja;

    /**
      * Usuario para se conectar ao webservice do CobreDireto
      * @var string
      * @access private
      **/
    private $usuario;

    /**
      * Senha para se conectar ao webservice do CobreDireto
      * @var string
      * @access private
      **/
    private $senha;

    /**
      * Ambiente do CobreDireto (producao,teste)
      * @var string
      * @access private
      **/
    private $ambiente;

    /**
      * DomDocument com o request para o CobreDireto
      * @var object
      * @access protected
      **/
    protected $request;

    /**
      * Url do WebService a ser utilizada
      * @var string
      * @access protected
      **/
    protected $__url;
    
    /**
      * XML de retorno do cobreDireto
      * @var object
      * @access protected
      **/
    protected $xml;
    
    /**
      * Funcionando em modo de debug
      * @var boolean
      * @access protected
      */
    protected $_debug;
    
    /**
      * Configuração inicial do CobreDireto
      *
      * Método para configurar todas as informações necessárias
      * para o CobreDireto
      * 
      * @access private
      **/
    protected function configuraCobreDireto(){
      if (!defined('CD_CODLOJA'))
        die('<h1>C&oacute;digo da loja n&atilde;o definido</h1>');
      $this->codloja = CD_CODLOJA;

      if (!defined('CD_USUARIO'))
        die('<h1>Usu&aacute;rio n&atilde;o definido</h1>');
      $this->usuario = CD_USUARIO;

      if (!defined('CD_SENHA'))
        die('<h1>Senha n&atilde;o definida</h1>');
      $this->senha = CD_SENHA;

      $this->ambiente = defined('CD_AMBIENTE')? CD_AMBIENTE : 'producao';
      $this->__url = ($this->ambiente == 'producao') ?
              'https://psp.cobredireto.com.br/bpag2/services/BPagWS?wsdl'
            : 'https://psp.cobredireto.com.br/bpag2Sandbox/services/BPagWS?wsdl';

      $this->request = new DomDocument('1.0','utf8');
    }
   
    /**
      * Função para auxiliar as alterações durante o processo
      *
      * Utilizada para poder 'setar' durante o processo algumas variaveis vinda do BD
      * @param string $method O método a ser 'criado'
      * @param array $argument os Argumentos a serem enviados para o novo método
      * @access public
      **/
    public function __call($method, $argument){
      $liberados = array('codpedido','url_recibo','url_retorno','url_erro','usuario','senha','codloja','ambiente','frete');
      if (preg_match('@^set_@i',$method)){
        $var = substr($method,4);
        if (in_array($var,$liberados)){
          $this->$var = $argument[0];
        }else
          return false;
      }else
        return false;
    }
    /**
      * Inicializa a conexão com o webservice do CobreDireto
      *
      * Faz as chamadas iniciais do webservice.
      * @access private
      **/
    protected function initCobreDireto($action){
      $__CD = new SoapClient($this->__url);
      $retorno = $__CD->doService(
        array (
          'version'   => '1.1.0',
          'action'    => $action,
          'merchant'  => $this->codloja,
          'user'      => $this->usuario,
          'password'  => $this->senha,
          'data'      => $this->request->saveXML(),
        )
      );
      if ($retorno->doServiceReturn)
        $retorno = $retorno->doServiceReturn;
      $this->xml = simplexml_load_string($retorno);
    }
  }
  
  /**
    * Classe para pagamento com o CobreDireto
    *
    * Classe com a finalidade de executar os procedimentos e adequação
    * para pagamento no CobreDireto
    *
    * @package CobreDireto
    * @subpackage pagamento
    * @version 0.1
    * @author RZamana <zamana@visie.com.br>
    * @date 05/14/2009
    **/
  Class Pg extends CobreDiretoABS {
  
    /**
      * Código do Pedido dentro da Loja
      * @var int
      * @access private
      **/
    private $codpedido;
    
    /**
      * Frete do pedido
      * @var float
      * @access private
      **/
    private $frete;

    /**
      * root node for DomDocument
      * @var object
      * @access private
      **/
    private $payOrder;

    /**
      * URL onde encontra-se o recibo
      
      * @var string
      * @access private
      **/
    private $url_recibo;

    /**
      * URL em caso de erro
      * @var string
      * @access private
      **/
    private $url_erro;

    /**
      * URL para uso do Bell
      * @var string
      * @access private
      **/
    private $url_retorno;

    /**
      * Objeto com as configurações do consumidor
      * @var object
      * @access private
      **/
    private $customer_info;

    /**
      * Objeto com as configurações de cobrança
      * @var object
      * @access private
      **/
    private $billing_info;

    /**
      * Objeto com as configurações de entrega
      * @var object
      * @access private
      **/
    private $shipment_info;
    
    /**
      * Objeto com as configurações de pagamento
      * @var object
      * @access private
      **/
    private $payment_data;
    
    /**
      * Recebe a configuração inicial do CobreDireto
      *
      * Configura toda a instancia para poder se comunicar com o CobreDireto
      * @param string $fileconfig Arquivo com os dados de configuração
      **/
    public function __construct($codpedido, $debug=false){
      $this->_debug = $debug;
      parent::configuraCobreDireto();
      
      $this->payOrder =  $this->request->createElement('payOrder');
      
      $this->codpedido = $codpedido;
      
      preg_match('@^([[:alnum:]]+)/@i',$_SERVER['SERVER_PROTOCOL'],$matche);
      $urlHost = strtolower($matche[1]).'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
      $partes = explode('/',$urlHost);$tot = count($partes) - 1;
      $urlHost = str_replace($partes[$tot],'',$urlHost);
      $this->url_recibo   = ((defined('CD_URL_RECIBO')) ? CD_URL_RECIBO  : $urlHost.'recibo.php').'&id='.$this->codpedido;
      $this->url_erro     = (defined('CD_URL_ERRO'))   ? CD_URL_ERRO    : $urlHost.'erro.php';
      $this->url_retorno  = (defined('CD_URL_RETORNO'))? CD_URL_RETORNO : $urlHost.'retorno.php';
      $this->frete        = (defined('CD_FRETE'))      ? CD_FRETE       : 0;
      if ($this->_debug){
          print "Inicializando a classe Pg<br />\n";
          print "Verificando pend&ecirc;ncias<br />\n";
          self::checkSystem();
          print "Definindo URL de Recibo para {$this->url_recibo}<br />\n";
          print "Definindo URL de Erro para {$this->url_erro}<br />\n";
          print "Definindo URL de Retorno para {$this->url_retorno}<br />\n";
          print "Definindo Frete para {$this->frete}<br />\n";
      }
    }

    public function checkSystem() { 
      $modules = new moduleCheck;
      if (class_exists('SoapClient'))
        print "Classe SoapClient encontrada com sucesso.<br />\n";
      else
        print "Classe SoapClient não foi encontrada, favor verificar.<br />\n";

      if ($modules->isLoaded('openssl'))
        print "OpenSSL encontrado.<br />\n";
      else
        print "OpenSSL não foi encontrado.<br />\n";

      if (intval(ini_get('allow_url_fopen')))
        print "Acesso a URL externa liberado.<br />\n";
      else
        print "Acesso a URL externa bloqueado.<br />\n";
    }
	function frete($valor) { $this->frete = $valor; }
	function url_recibo($valor) { $this->url_recibo = $valor; }
	function url_erro($valor) { $this->url_erro = $valor; }
	function url_retorno($valor) { $this->url_retorno = $valor; }

	/**
      * Adiciona as configurações do consumidor
      *
      * Adiciona, caso tenha, as configurações do consumidor ao XML
      * @access private
      **/
    private function configuraConsumidor(){
      if (!is_object($this->customer_info)){
        if (is_object($this->billing_info))
          $this->customer_info = $this->billing_info;
        else if (is_object($this->shipment_info))
          $this->customer_info = $this->shipment_info;
      }
      if (is_object($this->customer_info)) {
        $enderecos = $this->request->createElement('customer_data');
        $enderecos->appendChild($this->customer_info);
        if (is_object($this->billing_info))
          $enderecos->appendChild($this->billing_info);
        if (is_object($this->shipment_info))
          $enderecos->appendChild($this->shipment_info);
        $this->payOrder->appendChild($enderecos);
      }
    }

    /**
      * Recebe os produtos a serem enviados para o CobreDireto
      *
      * Recebe um array de produtos a serem adicionados no carrinho do CobreDireto, para cobrança
      * $produtos = array(
      *   array(
      *     "descricao"=>"Descrição do Produto",
      *     "valor"=>12.90,
      *     "quantidade"=>1,
      *     "id"=>33
      *   ),
      * );
      * @param array $dados Array com os produtos
      **/
    public function adicionar($produtos){

      $order_data = $this->request->createElement('order_data');

      $merch_ref  =  $this->request->createElement('merch_ref',$this->codpedido);
      $order_data->appendChild($merch_ref);

      $tax_freight  =  $this->request->createElement('tax_freight',$this->frete);
      $order_data->appendChild($tax_freight);

      $total = 0;
      foreach($produtos as $k=>$v)
        $total += (floatval($v['valor']) * $v['quantidade']);
      
      $order_subtotal  =  $this->request->createElement('order_subtotal',number_format($total,2,'',''));
      $order_data->appendChild($order_subtotal);

      $order_total  =  $this->request->createElement('order_total',number_format(($total + $this->frete/100),2,'',''));
      $order_data->appendChild($order_total);

      $prods =  $this->request->createElement('order_items');
      foreach($produtos as $k=>$v){
        $item =  $this->request->createElement('order_item');
        $codigo     = $this->request->createElement('code',$v['id']);
        $descricao  = $this->request->createElement('description',$v['descricao']);
        $quantidade = $this->request->createElement('units',$v['quantidade']);
        $valor      = $this->request->createElement('unit_value',number_format($v['valor'],2,'',''));

        $item->appendChild($codigo);
        $item->appendChild($descricao);
        $item->appendChild($quantidade);
        $item->appendChild($valor);
        if ($this->_debug){
          print "Inserindo produto:";
          print '<pre>'.print_r($v,true)."</pre><br />\n";
        }

        $prods->appendChild($item);
      }
      $order_data->appendChild($prods);
      $this->payOrder->appendChild($order_data);
    }

    /**
      * Insere em $request as url pré-configuradas para o CobreDireto
      *
      **/
    function configuraBehavior() {
      $behavior_data = $this->request->createElement('behavior_data');

      $url_post_bell = $this->request->createElement('url_post_bell',$this->url_retorno);
      $behavior_data->appendChild($url_post_bell);

      $url_redirect_success = $this->request->createElement('url_redirect_success');
      $cdata = $this->request->createCDATASection($this->url_recibo);
      $url_redirect_success->appendChild($cdata);
      $behavior_data->appendChild($url_redirect_success);

      $url_redirect_error = $this->request->createElement('url_redirect_error', $this->url_erro);
      $behavior_data->appendChild($url_redirect_error);

      $this->payOrder->appendChild($behavior_data);
    }

    /**
      * Configura o pagamento pela Loja
      * 
      * Configura o pagamento estabelecido pela Loja, deixando ao CobreDireto apenas o pagamento em si
      * 
      * @param string $tipo Qual a forma de pagamento (ver Apendice A do manual)
      * @param int $parcelas Quantidade de parcelas
      *
      **/
    function pagamento($tipo,$parcelas = ''){
      $payment_data = $this->request->createElement('payment');
      $method = $this->request->createElement('payment_method',$tipo);
      $payment_data->appendChild($method);
      if ($parcelas != ''){
        $installments = $this->request->createElement('installments',$parcelas);
        $payment_data->appendChild($installments);
      }
      if (!is_object($this->payment_data))
        $this->payment_data = $this->request->createElement('payment_data');
      $this->payment_data->appendChild($payment_data);
    }
    /**
      * Insere as informação do consumidor
      *
      * Insere no XML as informações do consumidor para ser enviada ao CobreDireto
      *
      * $data = array (
      *     'primeiro_nome' => '',
      *     'meio_nome'     => '',
      *     'ultimo_nome'   => '',
      *     'email'         => '',
      *     'documento'     => '',
      *     'tel_casa'      => array (
      *       'area'    => '',
      *       'numero'  => '',
      *     ),
      *     'cep'           => '',
      * )
      * @param array $data array contendo todas as informações do consumidor
      * @param string $tipo Qual o endereço a ser inserido, ex.: TODOS, CONSUMIDOR, COBRANCA, ENTREGA
      **/
    public function endereco($dados, $tipo = 'TODOS'){
      $CD_tipos = array('TODOS','CONSUMIDOR','COBRANCA','ENTREGA');
      if (!in_array($tipo,$CD_tipos))
        return false;
      switch($tipo){
        case 'TODOS':
          $insere = array('customer_info','billing_info','shipment_info');
          break;
        case 'CONSUMIDOR':
          $insere = array('customer_info');
          break;
        case 'COBRANCA':
          $insere = array('billing_info');
          break;
        case 'ENTREGA':
          $insere = array('shipment_info');
          break;
      }
      foreach($insere as $v){
        $this->$v = $this->request->createElement($v);
        $first_name   = $this->request->createElement('first_name',   $dados['primeiro_nome']); 
        $middle_name  = $this->request->createElement('middle_name',  $dados['meio_nome']);
        $last_name    = $this->request->createElement('last_name',    $dados['ultimo_nome']);
        $email        = $this->request->createElement('email',        $dados['email']);
        $document     = $this->request->createElement('document',     $dados['documento']);
        $phone_home   = $this->request->createElement('phone_home');
        $area_cod     = $this->request->createElement('area_code',    $dados['tel_casa']['area']);
        $phone_number = $this->request->createElement('phone_number', $dados['tel_casa']['numero']);
        $phone_home->appendChild($area_cod);
        $phone_home->appendChild($phone_number);
        $address_zip  = $this->request->createElement('address_zip',  $dados['cep']);

        $this->$v->appendChild($first_name );
        $this->$v->appendChild($middle_name);
        $this->$v->appendChild($last_name  );
        $this->$v->appendChild($email      );
        $this->$v->appendChild($document   );
        $this->$v->appendChild($phone_home );
        $this->$v->appendChild($address_zip);
      }
    }
    
    /**
      * Método para enviar para o CobreDireto
      *
      * Valida todas as informações e envia para o CobreDireto, já redirecionando para a URL do CobreDireto
      *
      **/
    public function pagar(){
      self::configuraBehavior();
      if (is_object($this->payment_data))
        $this->payOrder->appendChild($this->payment_data);
      self::configuraConsumidor();
      $this->request->appendChild($this->payOrder);
      parent::initCobreDireto('payOrder');
      if ($this->xml->status != 0){
        if ($this->_debug){
          print '<pre>'.print_r($this->xml,true).'</pre>';
          print '<hr />';
        }
        die('<strong>Erro:</strong> '.$this->xml->msg);
      }
      else{
        if ($this->_debug)
          print "Redirecionar Usuario para: <a href=\"{$this->xml->bpag_data->url}\">{$this->xml->bpag_data->url}</a>";
        else
          header('Location: '.$this->xml->bpag_data->url);
      }
      
    }

  }
  
  /**
    * Classe para o retorno do CobreDireto
    *
    * Classe com a finalidade de executar os procedimentos adequados ao retorno do CobreDireto
    * (Bell e Probe)
    *
    * @package CobreDireto
    * @subpackage Retorno
    * @version 0.1
    * @author RZamana <zamana@visie.com.br>
    * @date 05/14/2009
    **/  
  class Retorno extends CobreDiretoABS {
  
    /**
      * Código do pedido na loja
      * @var int
      * @access private
      **/
    private $merch_ref;
    
    /**
      * Código do pedido no CobreDireto
      * @var int
      * @access private
      **/
    private $id;

    /**
      * Método Construtor com as configurações e já recebendo os POST's
      *
      * Recebe o POST do CobreDireto
      *
      **/
    function __construct(){
      parent::configuraCobreDireto();
      
      $this->merch_ref  = $_POST['merch_ref'];
      $this->id         = $_POST['id'];
      
    }
    
    /**
      * Método para montar o Bell do CobreDireto
      *
      * Monta a estrtura XML para o BELL
      *
      **/
    public function campainha(){
      @header('Content-type: text/xml');
      $bell = new DomDocument('1.0','utf8');
      $payOrder = $bell->createElement('payOrder');
      $status   = $bell->createElement('status',(function_exists(checagem))? checagem($this->merch_ref): '1');
      $msg      = $bell->createElement('msg',$msg);
      $payOrder->appendChild($status);
      $payOrder->appendChild($msg);
      $bell->appendChild($payOrder);
      echo $bell->saveXML();
    }
   
    /**
      * Método para montar o Probe para o CobreDireto
      *
      * Monta a estrtura XML para o Probe e solicita o WebService
      * caso tenha a função capturar, já solicita a mesma
      *
      **/
    public function probe(){
      $soapProbe =  $this->request->createElement('probe');
      
      $merch  = $this->request->createElement('merch_ref',$this->merch_ref);
      $id     = $this->request->createElement('id', $this->id);
      
      $soapProbe->appendChild($merch);
      $soapProbe->appendChild($id);
      
      $this->request->appendChild($soapProbe);
      parent::initCobreDireto('probe');
      if (function_exists(capturar)){
         capturar($this->merch_ref,(string) $this->xml->order_data->order->bpag_data->status, array(
          'url' => (string) $this->xml->order_data->order->bpag_data->url, 
          'msg' => $this->msg, 
          'cobredireto_id' => (string) $this->xml->order_data->order->bpag_data->id,
         ));
      }
    }
  }
