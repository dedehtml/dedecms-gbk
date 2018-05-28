<?php
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC."/oxwindow.class.php");

helper('mda');

$install_sqls = array(
"CREATE TABLE IF NOT EXISTS `#@__plus_mda_setting` (
  `skey` varchar(255) NOT NULL DEFAULT '',
  `svalue` text NOT NULL,
  `stime` int(10) NOT NULL,
  PRIMARY KEY (`skey`)
) TYPE=MyISAM;",
"INSERT INTO `#@__plus_mda_setting` (`skey`, `svalue`, `stime`) VALUES
('version', '0.0.1', 0),
('channel_uuid', '0', 0),
('channel_secret', '0', 0),
('email', '0', 0);",
);

$update_sqls = array(
    '0.0.2'=>array(
        "UPDATE `#@__plus_mda_setting` SET `svalue`='0.0.2' WHERE `skey`='version';",
    )
);


/*--------------------------------
function __install(){  }
-------------------------------*/

if (! $dsql->IsTable('#@__plus_mda_setting') )
{
    $mysql_version = $dsql->GetVersion(TRUE);
    
    foreach( $install_sqls as $install_sql )
    {
        $sql = preg_replace("#ENGINE=MyISAM#i", 'TYPE=MyISAM', $install_sql);
        $sql41tmp = 'ENGINE=MyISAM DEFAULT CHARSET='.$cfg_db_language;
        
        if($mysql_version >= 4.1)
        {
            $sql = preg_replace("#TYPE=MyISAM#i", $sql41tmp, $sql);
        }
        $dsql->ExecuteNoneQuery($sql);
    }

}

/*--------------------------------
function __update(){  }
-------------------------------*/

$version=mda_get_setting('version');
if (empty($version)) $version = '0.0.1';
if (version_compare($version, MDA_VER, '<')) {
    $mysql_version = $dsql->GetVersion(TRUE);

    foreach ($update_sqls as $ver => $sqls) {
        if (version_compare($ver, $version,'<')) {
            continue;
        }
        foreach ($sqls as $sql) {
            $sql = preg_replace("#ENGINE=MyISAM#i", 'TYPE=MyISAM', $sql);
            $sql41tmp = 'ENGINE=MyISAM DEFAULT CHARSET='.$cfg_db_language;
            
            if($mysql_version >= 4.1)
            {
                $sql = preg_replace("#TYPE=MyISAM#i", $sql41tmp, $sql);
            }
            $dsql->ExecuteNoneQuery($sql);
        }
        mda_set_setting('version', $ver);
        $version=mda_get_setting('version');
    }
}

if(empty($dopost)) $dopost = '';

/*--------------------------------
function __link(){  }
-------------------------------*/
if($dopost == 'place' OR $dopost == 'report' OR $dopost == 'account' OR $dopost == 'setting')
{
    if ( !mda_islogin() )
    {
        ShowMsg("����δ��¼�µù�棬���ȵ�¼�����ʹ�á�����",'?dopost=login');
        exit();
    }
    mda_check_islogin();

    if($dopost=='place') 
    {
        $channel_uuid = mda_get_setting('channel_uuid');
        $manage_url = MDA_APIHOST."/place?from=dedecms&uuid={$channel_uuid}";
        $ptitle = '������';
    } elseif ($dopost=='report')
    {
        $manage_url = MDA_APIHOST."/report";
        $ptitle = '�������';
    } elseif ($dopost=='account')
    {
        $manage_url = MDA_APIHOST."/account";
        $ptitle = '��������';
    } elseif ($dopost=='setting')
    {
        $manage_url = MDA_APIHOST."/setting";
        $ptitle = '�µ�����';
    }

    echo <<<EOT
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$cfg_soft_lang}">
<title>{$ptitle}</title>
<link rel="stylesheet" type="text/css" href="css/base.css">
</head>
<body background='images/allbg.gif' leftmargin="8" topmargin='8'>
<table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#DFF9AA" height="100%">
  <tr>
    <td height="28" style="border:1px solid #DADADA" background='images/wbg.gif'>
    
    <div style="float:left">&nbsp;<b>��<a href="?">�µù��</a> ��{$ptitle}</b></div>
    <div style="float:right;margin-right:20px;">���ã�{$_SESSION['mda_email']} {$account_str}</div>
    </td>
  </tr>
  <tr>
    <td width="100%" height="100%" valign="top" bgcolor='#ffffff' style="padding-top:5px"><table width='100%'  border='0' cellpadding='3' cellspacing='1' bgcolor='#DADADA' height="100%">
        <tr bgcolor='#DADADA'>
          <td colspan='2' background='images/wbg.gif' height='26'><font color='#666600'><b>{$ptitle}</b></font></td>
        </tr>
        {$addstr}
        <tr bgcolor='#FFFFFF'>
          <td colspan='2' height='100%'><iframe src="{$manage_url}" {$addstyle} width="100%" height="100%" style="border:none" onload="if(this.postMessage){this.postMessage('ok')}"></iframe></td>
        </tr>
        <tr>
          <td bgcolor='#F5F5F5'>&nbsp;</td>
        </tr>
      </table></td>
  </tr>
</table>
<p align="center"> <br>
  <br>
</p>
</body>
</html>

EOT;
}
/*--------------------------------
function __clearcache(){  }
-------------------------------*/
else if($dopost == 'clearcache'){
    if (!is_dir(DEDEDATA . "/cache/mda/") OR  RmRecurse(DEDEDATA . "/cache/mda/") )
    {
        ShowMsg("�ɹ����������Ϣ",-1);
        exit();
    } else {
        ShowMsg("�������ʧ�ܣ��볢���ֹ�ɾ��".DEDEDATA."/cache/mda/", 'javascript:;');
        exit();
    }
}
/*--------------------------------
function __bind_user(){  }
-------------------------------*/
else if($dopost == 'bind_user')
{
    $email = isset($email)? $email : '';
    $pwd = isset($pwd)? $pwd : '';
    $domain = isset($domain)? $domain : '';
    $channel_name = isset($channel_name)? $channel_name : '';
    if ( !$email OR !$pwd OR !$domain OR !$channel_name)
    {
        ShowMsg("��д��ȷ���˺���Ϣ��",-1);
        exit();
    }
    if($cfg_soft_lang=='gb2312') $channel_name = gb2gbk($channel_name);
    $paramsArr=array(
        'email'=>$email, 
        'password'=>$pwd,
        'domain'=>$domain,
        'channel_name'=>$channel_name,
    );
    $rs = json_decode(mda_http_send(MDA_API_BIND_USER,0,$paramsArr),TRUE);
    if ( !$rs )
    {
        ShowMsg("����API���������ԣ�",-1);
        exit();
    }
    if ( $rs['code'] != 0 )
    {
        ShowMsg("����ʧ�ܣ��������[code:{$rs['code']}]����Ϣ[{$rs['msg']}]",-1);
        exit();
    }
    $channel_uuid = $rs['data']['channel_uuid'];
    $channel_secret = $rs['data']['channel_secret'];

    mda_set_setting('email', $email);
    mda_set_setting('channel_uuid', $channel_uuid);
    mda_set_setting('channel_secret', $channel_secret);
    $login_url = "?dopost=login";
    echo <<<EOT
<iframe src="{$login_url}" scrolling="no" width="0" height="0" style="border:none"></iframe>
EOT;
    ShowMsg("�󶨳ɹ��������Զ���¼�µù��ƽ̨", "?dopost=login");
    exit();
}
/*--------------------------------
function __login(){  }
-------------------------------*/
else if($dopost == 'login')
{
    $email = mda_get_setting('email');
    $channel_uuid = mda_get_setting('channel_uuid');
    $channel_secret = mda_get_setting('channel_secret');
    $ts = time();
    $paramsArr=array(
        'channel_uuid'=>$channel_uuid, 
        'channel_secret'=>$channel_secret,
        'email'=>$email,
        'ts'=>$ts,
        'crc'=>md5($channel_uuid.$channel_secret.$ts),
    );
    $jquery_file = MDA_JQUERY;
    $api_login = MDA_API_LOGIN;
    $params = json_encode($paramsArr);
    $rs = json_decode(mda_http_send(MDA_API_LOGIN,0,$paramsArr),TRUE);
    if ( isset($rs['code']) AND $rs['code'] == 0 ) {
      $_SESSION['mda_email']=$email;
    } else {
      unset($_SESSION['mda_email']);
      header('Location:?logout=1');
      exit();
    }
    
    echo <<<EOT
<script type="text/javascript" src="{$jquery_file}"></script>
<script type="text/javascript">
	(function($){
        $.ajax({
            url: "{$api_login}",
            dataType : 'jsonp',
            jsonpCallback:"callfunc",
            data: $params,
            success: function( response ) {
                if(response.code == 0){
                    window.location.href='?dopost=main&nomsg=yes&forward={$dopost}';
                    console.log(response);
                }
                
            }
        });
	})(jQuery);
</script>
EOT;
    exit;
} 
/*--------------------------------
function __main(){  }
-------------------------------*/
else if($dopost == 'main'){
    $mda_version = MDA_VER;
    $channel_uuid = mda_get_setting('channel_uuid');
    $channel_secret = mda_get_setting('channel_secret');
    $msg = <<<EOT
<form name='myform' method='POST' action='?'>
<input type='hidden' name='dopost' value='set_secret'>
<table width="98%" border="0" cellspacing="1" cellpadding="1">
  <tbody>
    <tr>
      <td width="16%" height="30">��¼�û���</td>
      <td width="84%" style="text-align:left;">{$_SESSION['email']} {$account_str} <!--<a href='?dopost=logout' style='color:blue'>[�˳�]</a>--></td>
    </tr>
    <tr>
      <td width="16%" height="30">�µ�ģ��汾��</td>
      <td width="84%" style="text-align:left;">v{$mda_version} </td>
    </tr>
    <tr>
      <td width="16%" height="30">Channel UUID��</td>
      <td width="84%" style="text-align:left;"><input class="input-xlarge" type="text" value="{$channel_uuid}" disabled="disabled/" style="width:260px"> </td>
    </tr>
    <tr>
      <td width="16%" height="30">Channel Secret��</td>
      <td width="84%" style="text-align:left;">
        <input name="channel_secret" class="input-xlarge" type="text" value="{$channel_secret}" style="width:260px">
        <input type="submit" value="�޸�">
      </td>
    </tr>
    <tr>
      <td height="30" colspan="2">���ѳɹ���¼�µù�棡�����Խ������²�����</td>
    </tr>
    <tr>
      <td height="30" colspan="2">
        <a href='?dopost=place' style='color:blue'>[������]</a> 
        <a href='?dopost=report' style='color:blue'>[�鿴����]</a> 
        <a href='?dopost=account' style='color:blue'>[��������]</a> 
        <a href='?dopost=setting' style='color:blue'>[�µ�����]</a> 
        <a href='?dopost=clearcache' style='color:blue'>[��ջ���]</a> 
      </td>
    </tr>
    <tr>
      <td height="30" colspan="2">
        <hr>
        ʹ��˵����<br>
        �ڹ������д�����Ӧ���λ����ȡ���λ��ǩ������ģ����Ӧλ�ü��ɡ�
        <hr>
        ����˵����<br>
        <b>[������]</b>����վ����Ӧ�Ĺ��λ��<br>
        <b>[�鿴����]</b>��ȡ���λ��Ӧ��ͳ�������<br>
        <b>[��������]</b>�鿴����ͳ�ƣ�<br>
        <b>[�µ�����]</b>�µù��ƽ̨�˺���Ϣ���ã�<br>
        <b>[��ջ���]</b>��չ���ǩ���棬������ĵ�¼�˺Ž�����ջ��������ɣ�<br>
        <hr>
      </td>
    </tr>
    <tr>
      <td height="30" colspan="2" style="color:#999"><strong>�µù��</strong>��һ�����ѺõĹ��ƽ̨������ں�֯��ϵͳ����������ȡ�������档</td>
    </tr>
  </tbody>
</table>
</form>
{$login_str}
{$change_isv_id}
EOT;
        $wintitle = '�µù�����';
        $wecome_info = '�µù��ģ�� ��';
        $win = new OxWindow();
        $win->AddTitle($wintitle);
        $win->AddMsgItem($msg);
        $winform = $win->GetWindow('hand', '&nbsp;', false);
        $win->Display();
        exit;
} else if($dopost == 'set_secret') {
    $email = mda_get_setting('email');
    $channel_uuid = mda_get_setting('channel_uuid');
    $ts = time();
    $paramsArr=array(
        'channel_uuid'=>$channel_uuid, 
        'channel_secret'=>$channel_secret,
        'email'=>$email,
        'ts'=>$ts,
        'crc'=>md5($channel_uuid.$channel_secret.$ts),
    );
    $rs = json_decode(mda_http_send(MDA_API_LOGIN,0,$paramsArr),TRUE);
    if ( !$rs )
    {
        ShowMsg("����API���������ԣ�",-1);
        exit();
    }
    if ( $rs['code'] != 0 )
    {
        ShowMsg("����ʧ�ܣ��������[code:{$rs['code']}]����Ϣ[{$rs['msg']}]",'?dopost=main');
        exit();
    }
    if ($rs['code'] == 0){
        ShowMsg("Channel Secret �޸ĳɹ�������",'?dopost=main');
        mda_set_setting('channel_secret', $channel_secret);
    }
}
// ------------------------------------------------------------------------
/*--------------------------------
function __index(){  }
-------------------------------*/
else {
    if ( mda_get_setting('email') AND mda_get_setting('channel_uuid') AND mda_get_setting('channel_secret') AND empty($logout))
    {
        header('Location:?dopost=login');
        exit;
    }
    
    $mda_reg_url = MDA_REG_URL;
    $mda_forget_pwd_url = MDA_FORGOT_PASSWORD_URL;
    $domain = !empty($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    $mda_update_url = MDA_APIHOST."/help/dedecms_module_download";
    
echo <<<EOT
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$cfg_soft_lang}">
<title>�µù��</title>
<link rel="stylesheet" type="text/css" href="{$cfg_plus_dir}/img/base.css">
</head>
<body background='{$cfg_plus_dir}/img/allbg.gif' leftmargin="8" topmargin='8'>
<table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#DFF9AA">
  <tr>
    <td height="28" style="border:1px solid #DADADA" background='{$cfg_plus_dir}/img/wbg.gif'>&nbsp;<b><a href="?">�µù�� </a> &gt;&gt; ���õµù��</b></td>
  </tr>
  <tr>
  <td width="100%" height="80" style="padding-top:5px" bgcolor='#ffffff'>
  <form name='myform' method='POST' action='?'>
  <input type='hidden' name='dopost' value='bind_user'>
  <table width='100%'  border='0' cellpadding='3' cellspacing='1' bgcolor='#DADADA'>
    <tr bgcolor='#DADADA'>
      <td colspan='2' background='{$cfg_plus_dir}/img/wbg.gif' height='26'><font color='#666600'><b>�µù��</b></font></td>
    </tr>
    <tr bgcolor='#FFFFFF'>
      <td colspan='2'  height='100'>
      <table width="98%" border="0" cellspacing="1" cellpadding="1">
        <tbody>
            <tr>
                <td colspan="2" id="isvsContent">
                <table width="98%" border="0" cellspacing="1" cellpadding="1">
          <tbody>
            <tr>
              <td width="16%" height="30">���䣺</td>
              <td width="84%" style="text-align:left;"><input name="email" type="email" id="email" size="16" style="width:200px" value="" />
                <a target="_blank" href="$mda_reg_url" style="color:blue">���ע��</a> ����ȡרҵ������</td>
            </tr>
            <tr>
              <td height="30">���룺</td>
              <td style="text-align:left;"><input name="pwd" type="password" id="pwd" size="16" style="width:200px">
               <a target="_blank" href="$mda_forget_pwd_url" style="color:blue">��������</a> &nbsp; </td>
            </tr>
            <tr>
              <td width="16%" height="30">վ��������</td>
              <td width="84%" style="text-align:left;"><input name="domain" type="text" id="domain" size="16" style="width:200px" value="{$domain}" />
                </td>
            </tr>
            <tr>
              <td width="16%" height="30">���ƣ�</td>
              <td width="84%" style="text-align:left;"><input name="channel_name" type="text" id="channel_name" size="16" style="width:200px" value="{$cfg_webname}" />
                </td>
            </tr>
            <tr>
              <td height="30">ģ��汾��</td>
              <td style="text-align:left;">{$version}
               <a target="_blank" href="$mda_update_url" style="color:blue">�����°�ģ��</a> &nbsp; </td>
            </tr>
          </tbody>
        </table>
                </td>
            </tr>
        </tbody>
      </table>
      </td>
    </tr>
    <tr>
      <td colspan='2' bgcolor='#F9FCEF'><table width='270' border='0' cellpadding='0' cellspacing='0'>
          <tr align='center' height='28'>
            <td width='90'><input name='imageField1' type='image' class='np' src='{$cfg_plus_dir}/img/button_ok.gif' width='60' height='22' border='0' /></td>
            <td width='90'></td>
            <td></td>
          </tr>
        </table></td>
    </tr>
  </table>
  </form>
  </td>
  </tr>
</table>
<p align="center"> <br>
  <br>
</p>
</body>
</html>
EOT;
    
}