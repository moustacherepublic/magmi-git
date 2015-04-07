<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 01/04/15
 * Time: 22:43
 */
require_once('utils.php');
function getWebServerHelper()
{
    $wst=getWebServerType();
    $classname=ucfirst($wst["Server"]."ServerHelper");
    if(class_exists($classname))
    {
        $helperinst=new $classname($wst["Version"]);
        return $helperinst;
    }
    else
    {
        return null;
    }
}

abstract class WebServerHelper
{
    protected $_version;
    protected $_user="magmi";
    protected $_pass="magmi";
    protected $_templatesdir;
    protected $_signature="#MAGMI SECURITY FILE";
    public function __construct($version)
    {
        $this->_version=$version;
        $this->_templatesdir=dirname(__FILE__)."/securitytpl";
    }

    public function setCredentials($user,$pass)
    {
        $this->_user=$user;
        $this->_pass=$pass;
    }

    public function getWebUI()
    {
        return "WEBUI from ".get_class($this)."!!!!";
    }

    public abstract function secureServer();

}


class ApacheServerHelper extends WebServerHelper
{
    protected $_passfile;
    protected $_mode;
    public function __construct($version)
    {
        parent::__construct($version);
        $this->_passfile=dirname(dirname(dirname(__DIR__)))."/.htmagmipass";
        if(version_compare($this->_version,"2.4",">="))
        {
            $this->_mode="24";
        }
        else
        {
            $this->_mode="22";
        }
    }

    public function secureServer()
    {
        $methname="generateFiles_".$this->_mode;
        return $this->$methname();
    }

    public function generateHtPass($usr,$pass,$dest)
    {
        $f=fopen($dest,"w");
        $htpass= '{SHA}' . base64_encode(sha1($pass, TRUE));
        fwrite($f,$usr.":".$htpass);
        fclose($f);
    }
    public function copyOrInsertTemplate($tplname,$dest)
    {
            //Template content
          $tplcontent=file_get_contents($this->_templatesdir."/apache".$this->_mode."/$tplname");
          //destination file
          $exist=file_exists($dest)?file_get_contents($dest):'';
         //if template content not present, append it to dest file
         if(strpos($exist,$this->_signature)===FALSE) {
              $cf = fopen($dest, "a");
              fwrite($cf, $tplcontent);

              fclose($cf);
          }
    }


    public function generateFiles_24()
    {
        $sfname=".htaccess";
        //check if we have already a .htaccess
        //generating "main dir" .htaccess
        $log=array("ERROR"=>array(),"OK"=>array());
        try {
            $this->generateHtPass($this->_user,$this->_pass,$this->_passfile);
            $log["OK"][]="Generated PassFile";
            $this->copyOrInsertTemplate("main.htaccess", dirname(dirname(__DIR__)) . "/.htaccess");
            $log["OK"][]="Protected main magmi directory";
            $this->copyOrInsertTemplate("main.htaccess", dirname(__FILE__) . "/.htaccess");
            $log["OK"][]="Protected main magmi UI directory";
            $this->copyOrInsertTemplate("images.htaccess", dirname(dirname(__FILE__)) . "/images/.htaccess");
            $log["OK"][]="Authorized image access";

        }
        catch(Exception $e)
        {
            $log["ERROR"][]=$e->getMessage();
        }
        return $log;
    }

    public function getWebUI()
    {
        include_once($this->_templatesdir."/apache".$this->_mode."/webui.php");
    }
}

class NginxServerHelper
{
    public function __construct($version)
    {

    }
}
