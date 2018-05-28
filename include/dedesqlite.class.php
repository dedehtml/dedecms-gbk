<?php   if(!defined('DEDEINC')) exit("Request Error!");
/**
 * ���ݿ���
 * ˵��:ϵͳ�ײ����ݿ������
 *      ���������ǰ,�����趨��Щ�ⲿ����
 *      $GLOBALS['cfg_dbhost'];
 *      $GLOBALS['cfg_dbuser'];
 *      $GLOBALS['cfg_dbpwd'];
 *      $GLOBALS['cfg_dbname'];
 *      $GLOBALS['cfg_dbprefix'];
 *
 * @version        $Id: dedesqli.class.php 1 15:00 2011-1-21 tianya $
 * @package        DedeCMS.Libraries
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
@set_time_limit(0);
// �ڹ��������ļ��о�����Ҫ������ʼ������࣬��ֱ���� $dsql �� $db ���в���
// Ϊ�˷�ֹ���󣬲�����󲻱عر����ݿ�
$dsql = $dsqlitete = $db = new DedeSqlite(FALSE);
/**
 * Dede MySQLi���ݿ���
 *
 * @package        DedeSqli
 * @subpackage     DedeCMS.Libraries
 * @link           http://www.dedecms.com
 */
if (!defined('MYSQL_BOTH')) {
     define('MYSQL_BOTH',MYSQLI_BOTH);
}
class DedeSqlite
{
    var $linkID;
    var $dbHost;
    var $dbUser;
    var $dbPwd;
    var $dbName;
    var $dbPrefix;
    var $result;
    var $queryString;
    var $parameters;
    var $isClose;
    var $safeCheck;
	var $showError=false;
    var $recordLog=false; // ��¼��־��data/mysqli_record_log.inc���ڽ��е���
	var $isInit=false;
	var $pconnect=false;
	var $_fixObject;

    //���ⲿ����ı�����ʼ�࣬���������ݿ�
    function __construct($pconnect=FALSE,$nconnect=FALSE)
    {
        $this->isClose = FALSE;
        $this->safeCheck = TRUE;
		$this->pconnect = $pconnect;
        if($nconnect)
        {
            $this->Init($pconnect);
        }
    }

    function DedeSql($pconnect=FALSE,$nconnect=TRUE)
    {
        $this->__construct($pconnect,$nconnect);
    }

    function Init($pconnect=FALSE)
    {
        $this->linkID = 0;
        //$this->queryString = '';
        //$this->parameters = Array();
        $this->dbHost   =  $GLOBALS['cfg_dbhost'];
        $this->dbUser   =  $GLOBALS['cfg_dbuser'];
        $this->dbPwd    =  $GLOBALS['cfg_dbpwd'];
        $this->dbName   =  $GLOBALS['cfg_dbname'];
        $this->dbPrefix =  $GLOBALS['cfg_dbprefix'];
        $this->result["me"] = 0;
        $this->Open($pconnect);
    }

    //��ָ��������ʼ���ݿ���Ϣ
    function SetSource($host,$username,$pwd,$dbname,$dbprefix="dede_")
    {
        $this->dbHost = $host;
        $this->dbUser = $username;
        $this->dbPwd = $pwd;
        $this->dbName = $dbname;
        $this->dbPrefix = $dbprefix;
        $this->result["me"] = 0;
    }

    //����SQL��Ĳ���
    function SetParameter($key,$value)
    {
        $this->parameters[$key]=$value;
    }

    //�������ݿ�
    function Open($pconnect=FALSE)
    {
        global $dsqlite;
        //�������ݿ�
        if($dsqlite && !$dsqlite->isClose && $dsqlite->isInit)
        {
            $this->linkID = $dsqlite->linkID;
        }
        else
        {

            $this->linkID = new SQLite3(DEDEDATA.'/'.$this->dbName.'.db');

            //����һ�����󸱱�
            CopySQLiPoint($this);
        }

        //������󣬳ɹ�������ѡ�����ݿ�
        if(!$this->linkID)
        {
            $this->DisplayError("DedeCms���󾯸棺<font color='red'>�������ݿ�ʧ�ܣ��������ݿ����벻�Ի����ݿ����������</font>");
            exit();
        }
		$this->isInit = TRUE;
        return TRUE;
    }

    //Ϊ�˷�ֹ�ɼ�����Ҫ�ϳ�����ʱ��ĳ���ʱ���������������ʱ����ϵͳ�ȴ��ͽ���ʱ��
    function SetLongLink()
    {
        @mysqli_query("SET interactive_timeout=3600, wait_timeout=3600 ;", $this->linkID);
    }

    //��ô�������
    function GetError()
    {
        $str = mysqli_error($this->linkID);
        return $str;
    }

    //�ر����ݿ�
    //mysql���Զ�����ǳ־����ӵ����ӳ�
    //ʵ���Ϲرղ������岢�����׳�������ȡ���⺯��
    function Close($isok=FALSE)
    {
        $this->FreeResultAll();
        if($isok)
        {
            $this->linkID->close();
            $this->isClose = TRUE;
            $GLOBALS['dsql'] = NULL;
        }
    }

    //��������������
    function ClearErrLink()
    {
    }

    //�ر�ָ�������ݿ�����
    function CloseLink($dblink)
    {
    }

    function Esc( $_str )
    {
        return addslashes($_str);
    }

    //ִ��һ�������ؽ����SQL��䣬��update,delete,insert��
    function ExecuteNoneQuery($sql='')
    {
        global $dsqlite;
		if(!$dsqlite->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqlite->isClose)
        {
            $this->Open(FALSE);
            $dsqlite->isClose = FALSE;
        }
        if(!empty($sql))
        {
            $this->SetQuery($sql);
        }else{
            return FALSE;
        }
        if(is_array($this->parameters))
        {
            foreach($this->parameters as $key=>$value)
            {
                $this->queryString = str_replace("@".$key,"'$value'",$this->queryString);
            }
        }
        //SQL��䰲ȫ���
        if($this->safeCheck) CheckSql($this->queryString,'update');

        $t1 = ExecTime();
        
        $rs = $this->linkID->exec($this->queryString);


        //��ѯ���ܲ���
        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
            //echo $this->queryString."--{$queryTime}<hr />\r\n";
        }
        return $rs;
    }


    //ִ��һ������Ӱ���¼������SQL��䣬��update,delete,insert��
    function ExecuteNoneQuery2($sql='')
    {
        global $dsqlite;
		if(!$dsqlite->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqlite->isClose)
        {
            $this->Open(FALSE);
            $dsqlite->isClose = FALSE;
        }

        if(!empty($sql))
        {
            $this->SetQuery($sql);
        }
        if(is_array($this->parameters))
        {
            foreach($this->parameters as $key=>$value)
            {
                $this->queryString = str_replace("@".$key,"'$value'",$this->queryString);
            }
        }
        $t1 = ExecTime();
        $this->linkID->exec($this->queryString);

        //��ѯ���ܲ���
        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
            //echo $this->queryString."--{$queryTime}<hr />\r\n";
        }

        return $this->linkID->changes();
    }

    function ExecNoneQuery($sql='')
    {
        return $this->ExecuteNoneQuery($sql);
    }

    function GetFetchRow($id='me')
    {
        return $this->result[$id]->numColumns();
    }

    function GetAffectedRows()
    {
        return $this->linkID->changes();
    }

    //ִ��һ�������ؽ����SQL��䣬��SELECT��SHOW��
    function Execute($id="me", $sql='')
    {
        global $dsqlite;
		if(!$dsqlite->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqlite->isClose)
        {
            $this->Open(FALSE);
            $dsqlite->isClose = FALSE;
        }
        if(!empty($sql))
        {
            $this->SetQuery($sql);
        }
        //SQL��䰲ȫ���
        if($this->safeCheck)
        {
            CheckSql($this->queryString);
        }

        $t1 = ExecTime();
        //var_dump($this->queryString);
        
        $this->result[$id] = $this->linkID->query($this->queryString);
        
		//var_dump(mysql_error());

        //��ѯ���ܲ���
        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
            //echo $this->queryString."--{$queryTime}<hr />\r\n";
        }

        if($this->result[$id]===FALSE)
        {
            $this->DisplayError($this->linkID->lastErrorMsg()." <br />Error sql: <font color='red'>".$this->queryString."</font>");
        }
    }

    function Query($id="me",$sql='')
    {
        $this->Execute($id,$sql);
    }

    //ִ��һ��SQL���,����ǰһ����¼�������һ����¼
    function GetOne($sql='',$acctype=MYSQLI_ASSOC)
    {
        global $dsqlite;
		if(!$dsqlite->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqlite->isClose)
        {
            $this->Open(FALSE);
            $dsqlite->isClose = FALSE;
        }
        if(!empty($sql))
        {
            if(!preg_match("/LIMIT/i",$sql)) $this->SetQuery(preg_replace("/[,;]$/i", '', trim($sql))." LIMIT 0,1;");
            else $this->SetQuery($sql);
        }
        $this->Execute("one");
        $arr = $this->GetArray("one", $acctype);
        if(!is_array($arr))
        {
            return '';
        }
        else
        {
            $this->result["one"]->reset(); return($arr);
        }
    }

    //ִ��һ�������κα����йص�SQL���,Create��
    function ExecuteSafeQuery($sql,$id="me")
    {
        global $dsqlite;
		if(!$dsqlite->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqlite->isClose)
        {
            $this->Open(FALSE);
            $dsqlite->isClose = FALSE;
        }
        $this->result[$id] = $this->linkID->query($sql);
    }

    //���ص�ǰ��һ����¼�����α�������һ��¼
    // SQLITE3_ASSOC��SQLITE3_NUM��SQLITE3_BOTH
    function GetArray($id="me",$acctype=SQLITE3_ASSOC)
    {
        switch ( $acctype )
        {
            case MYSQL_ASSOC:
                $acctype = SQLITE3_ASSOC;
                break;    
            case MYSQL_NUM:
                $acctype = SQLITE3_NUM;
                break;  
            default:
                $acctype = SQLITE3_BOTH;
                break;
        }
        
        if($this->result[$id]===0)
        {
            return FALSE;
        }
        else
        {
            $rs = $this->result[$id]->fetchArray($acctype);
            if ( !$rs ) {
                $this->result[$id]=0;
                return false;
            }
            return $rs;
        }
    }

    function GetObject($id="me")
    {
        if ( !isset($this->_fixObject[$id]) )
        {
            $this->_fixObject[$id] = array();
            while ( $row = $this->result[$id]->fetchArray(SQLITE3_ASSOC) )
            {
                $this->_fixObject[$id][] = (object)$row;
            }
            $this->result[$id]->reset();
        }
        return array_shift($this->_fixObject[$id]);
    }

    // ����Ƿ����ĳ���ݱ�
    function IsTable($tbname)
    {
        global $dsqlite;
		if(!$dsqlite->isInit)
		{
			$this->Init($this->pconnect);
		}
        $prefix="#@__";
        $tbname = str_replace($prefix, $GLOBALS['cfg_dbprefix'], $tbname);

        $row = $this->linkID->querySingle( "PRAGMA table_info({$tbname});");
        
        if( $row !== null )
        {
            return TRUE;
        }
        return FALSE;
    }

    //���MySql�İ汾��
    function GetVersion($isformat=TRUE)
    {
        global $dsqlite;
		if(!$dsqlite->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqlite->isClose)
        {
            $this->Open(FALSE);
            $dsqlite->isClose = FALSE;
        }
        $rs = $this->linkID->querySingle("select sqlite_version();");
        $sqlite_version = $rs;
        if($isformat)
        {
            $sqlite_versions = explode(".",trim($sqlite_version));
            $sqlite_version = number_format($sqlite_versions[0].".".$sqlite_versions[1],2);
        }
        return $sqlite_version;
    }

    //��ȡ�ض������Ϣ
    function GetTableFields($tbname, $id="me")
    {
		global $dsqlite;
		if(!$dsqlite->isInit)
		{
			$this->Init($this->pconnect);
		}
        $prefix="#@__";
        $tbname = str_replace($prefix, $GLOBALS['cfg_dbprefix'], $tbname);
        $query = "SELECT * FROM {$tbname} LIMIT 0,1";
        $this->result[$id] = $this->linkID->query($query);
    }

    //��ȡ�ֶ���ϸ��Ϣ
    function GetFieldObject($id="me")
    {
        $cols = $this->result[$id]->numColumns(); 
        $fields = array();
        while ($row = $this->result[$id]->fetchArray()) { 
            for ($i = 1; $i < $cols; $i++) { 
                $fields[] =  $this->result[$id]->columnName($i); 
            } 
        } 
        
        return (object)$fields;
    }

    //��ò�ѯ���ܼ�¼��
    function GetTotalRow($id="me")
    {
        $queryString = preg_replace("/SELECT(.*)FROM/isU",'SELECT count(*) as dd FROM',$this->queryString);
        $rs = $this->linkID->query($queryString);
        $row = $rs->fetchArray();
        return $row['dd'];
    }

    //��ȡ��һ��INSERT����������ID
    function GetLastID()
    {
        //��� AUTO_INCREMENT ���е������� BIGINT���� mysqli_insert_id() ���ص�ֵ������ȷ��
        //������ SQL ��ѯ���� MySQL �ڲ��� SQL ���� LAST_INSERT_ID() �������
        //$rs = mysqli_query($this->linkID, "Select LAST_INSERT_ID() as lid");
        //$row = mysqli_fetch_array($rs);
        //return $row["lid"];
        return $this->linkID->lastInsertRowID();
    }

    //�ͷż�¼��ռ�õ���Դ
    function FreeResult($id="me")
    {
        if ( $this->result[$id] )
        {
            @$this->result[$id]->reset();
        }
        
    }
    function FreeResultAll()
    {
        if(!is_array($this->result))
        {
            return '';
        }
        foreach($this->result as $kk => $vv)
        {
            if($vv)
            {
                @$vv->reset();
            }
        }
    }

    //����SQL��䣬���Զ���SQL������#@__�滻Ϊ$this->dbPrefix(�������ļ���Ϊ$cfg_dbprefix)
    function SetQuery($sql)
    {
        $prefix="#@__";
        $sql = str_replace($prefix,$GLOBALS['cfg_dbprefix'],$sql);
        $this->queryString = $sql;
        //$this->queryString = preg_replace("/CONCAT\(',', arc.typeid2, ','\)/i","printf(',%s,', arc.typeid2)",$this->queryString);
        if ( preg_match("/CONCAT\(([^\)]*?)\)/i",$this->queryString,$matches) )
        {
            $this->queryString = preg_replace("/CONCAT\(([^\)]*?)\)/i",str_replace(",","||",$matches[1]), $this->queryString);
            $this->queryString = str_replace("'||'","','",$this->queryString);
        }
        
        $this->queryString = preg_replace("/FIND_IN_SET\('([\w]+)', arc.flag\)>0/i","(',' || arc.flag || ',') LIKE '%,\\1,%'",$this->queryString);
        $this->queryString = preg_replace("/FIND_IN_SET\('([\w]+)', arc.flag\)<1/i","(',' || arc.flag || ',') NOT LIKE '%,\\1,%'",$this->queryString);
        if ( preg_match("/CREATE TABLE/i",$this->queryString) )
        {
            $this->queryString = preg_replace("/[\r\n]/",'',$this->queryString);
            $this->queryString = preg_replace('/character set (.*?) /i','',$this->queryString);
            $this->queryString = preg_replace('/unsigned/i','',$this->queryString);
            $this->queryString = str_replace('TYPE=MyISAM','',$this->queryString);
            
            $this->queryString = preg_replace ('/TINYINT\(([\d]+)\)/i','INTEGER',$this->queryString);
            $this->queryString = preg_replace ('/mediumint\(([\d]+)\)/i','INTEGER',$this->queryString);
            $this->queryString = preg_replace ('/smallint\(([\d]+)\)/i','INTEGER',$this->queryString);
            $this->queryString = preg_replace('/int\(([\d]+)\)/i','INTEGER',$this->queryString);
            $this->queryString = preg_replace('/auto_increment/i','PRIMARY KEY AUTOINCREMENT',$this->queryString);
            $this->queryString = preg_replace('/, KEY(.*?)MyISAM;/i','',$this->queryString);
            $this->queryString = preg_replace('/, KEY(.*?);/i',');',$this->queryString);
            $this->queryString = preg_replace('/, UNIQUE KEY(.*?);/i',');',$this->queryString);
            $this->queryString = preg_replace('/set\(([^\)]*?)\)/','varchar',$this->queryString);
            $this->queryString = preg_replace('/enum\(([^\)]*?)\)/','varchar',$this->queryString);
            if ( preg_match("/PRIMARY KEY AUTOINCREMENT/",$this->queryString) )
            {
                $this->queryString = preg_replace('/,([\t\s ]+)PRIMARY KEY  \(`([0-9a-zA-Z]+)`\)/i','',$this->queryString);
                $this->queryString = str_replace(',	PRIMARY KEY (`id`)','',$this->queryString);
            }
        }
        $this->queryString = preg_replace("/SHOW fields FROM `([\w]+)`/i","PRAGMA table_info('\\1') ",$this->queryString);
        $this->queryString = preg_replace("/SHOW CREATE TABLE .([\w]+)/i","SELECT 0,sql FROM sqlite_master WHERE name='\\1'; ",$this->queryString);
        //var_dump($this->queryString);
        $this->queryString = preg_replace("/Show Tables/i","SELECT name FROM sqlite_master WHERE type = \"table\"",$this->queryString);
        $this->queryString = str_replace("\'","\"",$this->queryString);
        //var_dump($this->queryString);
    }

    function SetSql($sql)
    {
        $this->SetQuery($sql);
    }

	function RecordLog($runtime=0)
	{
		$RecordLogFile = dirname(__FILE__).'/../data/mysqli_record_log.inc';
		$url = $this->GetCurUrl();
		$savemsg = <<<EOT

------------------------------------------
SQL:{$this->queryString}
Page:$url
Runtime:$runtime
EOT;
        $fp = @fopen($RecordLogFile, 'a');
        @fwrite($fp, $savemsg);
        @fclose($fp);
	}

    //��ʾ�������Ӵ�����Ϣ
    function DisplayError($msg)
    {
        $errorTrackFile = dirname(__FILE__).'/../data/mysqli_error_trace.inc';
        if( file_exists(dirname(__FILE__).'/../data/mysqli_error_trace.php') )
        {
            @unlink(dirname(__FILE__).'/../data/mysqli_error_trace.php');
        }
		if($this->showError)
		{
			$emsg = '';
			$emsg .= "<div><h3>DedeCMS Error Warning!</h3>\r\n";
			$emsg .= "<div><a href='http://bbs.dedecms.com' target='_blank' style='color:red'>Technical Support: http://bbs.dedecms.com</a></div>";
			$emsg .= "<div style='line-helght:160%;font-size:14px;color:green'>\r\n";
			$emsg .= "<div style='color:blue'><br />Error page: <font color='red'>".$this->GetCurUrl()."</font></div>\r\n";
			$emsg .= "<div>Error infos: {$msg}</div>\r\n";
			$emsg .= "<br /></div></div>\r\n";

			echo $emsg;
		}

        $savemsg = 'Page: '.$this->GetCurUrl()."\r\nError: ".$msg."\r\nTime".date('Y-m-d H:i:s');
        //����MySql������־
        $fp = @fopen($errorTrackFile, 'a');
        @fwrite($fp, '<'.'?php  exit();'."\r\n/*\r\n{$savemsg}\r\n*/\r\n?".">\r\n");
        @fclose($fp);
    }

    //��õ�ǰ�Ľű���ַ
    function GetCurUrl()
    {
        if(!empty($_SERVER["REQUEST_URI"]))
        {
            $scriptName = $_SERVER["REQUEST_URI"];
            $nowurl = $scriptName;
        }
        else
        {
            $scriptName = $_SERVER["PHP_SELF"];
            if(empty($_SERVER["QUERY_STRING"])) {
                $nowurl = $scriptName;
            }
            else {
                $nowurl = $scriptName."?".$_SERVER["QUERY_STRING"];
            }
        }
        return $nowurl;
    }

}

//����һ�����󸱱�
function CopySQLiPoint(&$ndsql)
{
    $GLOBALS['dsqlite'] = $ndsql;
}

//SQL�����˳�����80sec�ṩ�����������ʵ����޸�
if (!function_exists('CheckSql'))
{
    function CheckSql($db_string,$querytype='select')
    {
        global $cfg_cookie_encode;
        $clean = '';
        $error='';
        $old_pos = 0;
        $pos = -1;
        $log_file = DEDEINC.'/../data/'.md5($cfg_cookie_encode).'_safe.txt';
        $userIP = GetIP();
        $getUrl = GetCurUrl();

        //�������ͨ��ѯ��䣬ֱ�ӹ���һЩ�����﷨
        if($querytype=='select')
        {
            $notallow1 = "[^0-9a-z@\._-]{1,}(union|sleep|benchmark|load_file|outfile)[^0-9a-z@\.-]{1,}";

            //$notallow2 = "--|/\*";
            if(preg_match("/".$notallow1."/i", $db_string))
            {
                fputs(fopen($log_file,'a+'),"$userIP||$getUrl||$db_string||SelectBreak\r\n");
                exit("<font size='5' color='red'>Safe Alert: Request Error step 1 !</font>");
            }
        }

        //������SQL���
        while (TRUE)
        {
            $pos = strpos($db_string, '\'', $pos + 1);
            if ($pos === FALSE)
            {
                break;
            }
            $clean .= substr($db_string, $old_pos, $pos - $old_pos);
            while (TRUE)
            {
                $pos1 = strpos($db_string, '\'', $pos + 1);
                $pos2 = strpos($db_string, '\\', $pos + 1);
                if ($pos1 === FALSE)
                {
                    break;
                }
                elseif ($pos2 == FALSE || $pos2 > $pos1)
                {
                    $pos = $pos1;
                    break;
                }
                $pos = $pos2 + 1;
            }
            $clean .= '$s$';
            $old_pos = $pos + 1;
        }
        $clean .= substr($db_string, $old_pos);
        $clean = trim(strtolower(preg_replace(array('~\s+~s' ), array(' '), $clean)));

        if (strpos($clean, '@') !== FALSE  OR strpos($clean,'char(')!== FALSE OR strpos($clean,'"')!== FALSE
        OR strpos($clean,'$s$$s$')!== FALSE)
        {
            $fail = TRUE;
            if(preg_match("#^create table#i",$clean)) $fail = FALSE;
            $error="unusual character";
        }

        //�ϰ汾��Mysql����֧��union�����õĳ�����Ҳ��ʹ��union������һЩ�ڿ�ʹ���������Լ����
        if (strpos($clean, 'union') !== FALSE && preg_match('~(^|[^a-z])union($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="union detect";
        }

        //�����汾�ĳ�����ܱȽ��ٰ���--,#������ע�ͣ����Ǻڿ;���ʹ������
        elseif (strpos($clean, '/*') > 2 || strpos($clean, '--') !== FALSE || strpos($clean, '#') !== FALSE)
        {
            $fail = TRUE;
            $error="comment detect";
        }

        //��Щ�������ᱻʹ�ã����Ǻڿͻ������������ļ���down�����ݿ�
        elseif (strpos($clean, 'sleep') !== FALSE && preg_match('~(^|[^a-z])sleep($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="slown down detect";
        }
        elseif (strpos($clean, 'benchmark') !== FALSE && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="slown down detect";
        }
        elseif (strpos($clean, 'load_file') !== FALSE && preg_match('~(^|[^a-z])load_file($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="file fun detect";
        }
        elseif (strpos($clean, 'into outfile') !== FALSE && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="file fun detect";
        }

        //�ϰ汾��MYSQL��֧���Ӳ�ѯ�����ǵĳ��������Ҳ�õ��٣����ǺڿͿ���ʹ��������ѯ���ݿ�������Ϣ
        elseif (preg_match('~\([^)]*?select~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="sub select detect";
        }
        if (!empty($fail))
        {
            fputs(fopen($log_file,'a+'),"$userIP||$getUrl||$db_string||$error\r\n");
            exit("<font size='5' color='red'>Safe Alert: Request Error step 2!</font>");
        }
        else
        {
            return $db_string;
        }
    }
}
