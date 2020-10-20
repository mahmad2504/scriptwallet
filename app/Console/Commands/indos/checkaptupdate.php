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
class CheckAptUpdate extends Command
{
	private $ping_url = null;
	//"https://script.google.com/macros/s/AKfycbwCNrLh0BxlYtR3I9iW2Z-4RQK88Hryd4DEC03lIYLoLCce80A/exec?func=alive&device=iesd_support"; 
    private $ping = 10; // minutes
	private $self_update = 60; // minutes
	 /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'indos:checkaptupdate  {--beat=0}';
	/**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'INDOS Apt update check';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
	public function scanDirectories($rootDir, $allData=array()) 
	{
		// set filenames invisible if you want
		$invisibleFileNames = array(".", "..", ".htaccess", ".htpasswd");
		// run through content of root directory
		$dirContent = scandir($rootDir);
		foreach($dirContent as $key => $content) {
			// filter all files not accessible
			$path = $rootDir.'/'.$content;
			if(!in_array($content, $invisibleFileNames)) {
				// if content is file & readable, add to array
				if(is_file($path) && is_readable($path)) {
					// save file name with path
					$allData[] = $path;
					dump($path);
				// if content is a directory and readable, add path and name
				}elseif(is_dir($path) && is_readable($path)) {
					// recursive callback to open new directory
					$allData = $this->scanDirectories($path, $allData);
				}
			}
		}
		return $allData;
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
		$data = [];
		if(file_exists($filename))
		{
			$data = file_get_contents($filename);
			$data = json_decode($data);
		}
		$username = env("INDOS_SFTP_USER");
		$password = env("INDOS_SFTP_PASS");
		$url      = env("INDOS_SFTP_URL");
		// Make our connection
		$connection = ssh2_connect($url);

		// Authenticate
		if (!ssh2_auth_password($connection, $username, $password)) throw new Exception('Unable to connect.');

		// Create our SFTP resource
		if (!$sftp = ssh2_sftp($connection)) throw new Exception('Unable to create SFTP connection.');

		$remoteDir = '/filesend/volt/common/2.0';
		dd($this->scanDirectories('ssh2.sftp://' . $sftp. $remoteDir));
		// download all the files
		//$files    = scandir('ssh2.sftp://' . $sftp . $remoteDir);
		$files    = scandir('app');

		if (!empty($files)) {
		  foreach ($files as $file) {
			if ($file != '.' && $file != '..') {
				//  ssh2_scp_recv($connection, "$remoteDir/$file", "$localDir/$file");
				//echo $file."\n";
				$obj =  new \StdClass();
				foreach($data as $d)
				{
					if($d->name == $file)
					{
						$obj = $d;
						break;
					}
				}
				if(!isset($obj->name))
				{
					$obj->name =  $file;
					//$statinfo = ssh2_sftp_stat($sftp, '/filesend/volt/common/'.$file);
					//$mtime = $statinfo['mtime'];
					$mtime = filemtime('app/'.$file);
					
					$obj->mtime =  $mtime ;
					//$message = '/filesend/volt/common/'.$file." Created";
					//$this->SendEmail($message);
					$data[] = $obj;
				}
				else
				{
					//$statinfo = ssh2_sftp_stat($sftp, '/filesend/volt/common/'.$file);
					//$mtime = $statinfo['mtime'];
					$mtime = filemtime('app/'.$file);
					
					if($obj->mtime != $mtime)
					//if($obj->stats->mtime != $statinfo['mtime'])
					{
						$message = '/filesend/volt/common/'.$file." Updated";
						//$this->SendEmail($message);
					}
				}
				//echo gmdate("Y-m-d\TH:i:s\Z", $statinfo['mtime'])."\n";
			}
		  }
		}
		$data = json_encode($data);
		file_put_contents($filename,$data);
		//ssh2_disconnect($sftp);
		//$this->SendEmail();

    }
	public function SendEmail($msg)
	{
		$this->mail = new PHPMailer(true);	
		$this->mail->isSMTP();     
		$this->mail->Host = 'localhost';
		$this->mail->SMTPAuth = false;
		$this->mail->SMTPAutoTLS = false; 
		$this->mail->Port = 25; 
		$this->mail->Username   = 'support-bot@mentorg.com'; 
		$this->mail->setFrom('support-bot@mentorg.com', 'Support Bot');
		$this->mail->addAddress('mumtaz_ahmad@mentor.com');     // Add a recipient
		//$this->mail->addBCC("mumtazahmad2504@gmail.com", "Mumtaz Ahmad");
		$this->mail->addReplyTo('mumtaz_ahmad@mentor.com', 'Support Bot');
		$this->mail->Subject = 'Notofication - INDOS - APT Update  ';
		
		$this->mail->isHTML(true); 
		 $this->mail->Body= $msg;
		try 
		{
			$this->mail->send();
		} 
		catch (phpmailerException $e) 
		{
			echo $e->errorMessage(); //Pretty error messages from PHPMailer
		} 
		catch (Exception $e) 
		{
			echo $e->getMessage(); //Boring error messages from anything else!
		}	
	}
}
