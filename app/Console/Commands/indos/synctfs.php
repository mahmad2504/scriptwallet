<?php
namespace App\Console\Commands\indos;

use mahmad\Jira\Fields;
use mahmad\Jira\Jira;
use Illuminate\Console\Command;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Carbon\Carbon;

use App;
class SyncTFS extends Command
{
	private $ping_url = null;
	private $TFS_SERVER = "https://venus.tfs.siemens.net/tfs/Paumann/VS%20HMI-P";
	private $PROXY_SERVER = "cyp-fsrprx.net.plm.eds.com";
	private $PROXY_PORT = '2020';
	
	//"https://script.google.com/macros/s/AKfycbwCNrLh0BxlYtR3I9iW2Z-4RQK88Hryd4DEC03lIYLoLCce80A/exec?func=alive&device=iesd_support"; 
    private $ping = 10; // minutes
	private $self_update = 60; // minutes
	 /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'indos:synctfs  {--beat=0}';
	/**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'INDOS TFS Update';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
	function getContentBycURL($strURL)
	{
		$strURL= $this->TFS_SERVER.$strURL;
		dump($strURL);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return data inplace of echoing on screen
		curl_setopt($ch, CURLOPT_URL, $strURL);
		curl_setopt($ch, CURLOPT_VERBOSE, '0');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, '2');
		curl_setopt($ch, CURLOPT_USERPWD,env("TFS_USER").':'.env("TFS_TOKEN"));  
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, '1');
		//curl_setopt($ch, CURLOPT_SSLCERT, getcwd() . "/Z003UJ3F_cert.pem");
		//curl_setopt($ch, CURLOPT_SSLKEY, getcwd() . "/Z003UJ3F_key.pem");
		//New commands
		//curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt($ch, CURLOPT_PROXY, $this->PROXY_SERVER );
		curl_setopt($ch, CURLOPT_PROXYPORT, $this->PROXY_PORT);
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, env("PROXY_USER").':'.env("PROXY_PASS"));
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
		//curl_setopt($ch, CURLOPT_KEEP_SENDING_ON_ERROR, TRUE);
		
		//curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/siemens_root_ca_v3.0_2016.pem");
		//curl_setopt($ch, CURLOPT_CAPATH, getcwd() . "/siemens_root_ca_v3.0_2016.pem");
		//curl_setopt($ch, CURLOPT_CAINFO, "/etc/ssl/certs/ca-certificates.crt");
		$rsData = curl_exec($ch);
		$error = curl_error($ch);
		if($error != null)
		{
			echo $error;
			return [];
		}
		$data = json_decode($rsData);
		
		if(isset($data->errors))
		{
			json_encode($data->errors);
			return [];
		}
		curl_close($ch);
		return $data;
	}
   
	public function handle()
	{
		date_default_timezone_set("Asia/Karachi");
		
		$classname = __CLASS__;
		$namespace = __NAMESPACE__;
		$beat = $this->option('beat');
		if($beat == 0)
		{
			$this->ProcessCommand();
			return;
		}
		
		if(($beat % $this->ping) ==0)
		{
			if($this->ping_url != null)
				file_get_contents($this->ping_url);
		}
		if(($beat % $this->self_update) ==0)
		{
			$this->ProcessCommand();
		}
	}
	
	public function ProcessCommand()
    {
		$classname = __CLASS__;
		$namespace = __NAMESPACE__;
		$key = (substr($namespace, strrpos($namespace, '\\') + 1))."/".(substr($classname, strrpos($classname, '\\') + 1));
		if (App::runningInConsole())
		{
			$filename =  "data/".$key.".json";
		}
		else
		{
			$filename =  "../data/".$key.".json";
		}
		$tickets = $this->getContentBycURL("/_apis/wit/wiql/44f79dc1-bf54-4089-9020-4c40d4fe0057");
		$del = '';
		$str = '';
		foreach($tickets->workItems as $ticket)
		{
			$str = $str.$del.$ticket->id;
			$del = ",";
		}
		$this->getContentBycURL("/_apis/wit/WorkItems?ids=".$str);
			//."&fields=System.State,System.WorkItemType,Microsoft.VSTS.Common.Priority"));
	}
}
