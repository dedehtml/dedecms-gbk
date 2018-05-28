<?php   if(!defined('DEDEINC')) exit('Request Error!');

helper('mda');
helper('cache');

function lib_mda(&$ctag,&$refObj)
{
    global $dsql, $envs, $cfg_soft_lang;
    //���Դ���
    $type = empty($type)? 'code' : $type;
    $class = empty($class)? '_DEDECY' : $class;
    $version = MDA_VER;
    $attlist="uuid|,name|";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);

    if ( empty($uuid) AND empty($name) ) return '��д��ȷ��uuid �� name';
    
    $reval="";
    
    //if( !$dsql->IsTable("#@__plus_mda_setting") ) return 'û��װ<a href="'.MDA_APIHOST.'" target="_blank">�µù��ģ��</a>';
    
    $email = mda_get_setting('email');
    $channel_uuid = mda_get_setting('channel_uuid');
    
    $channel_secret = mda_get_setting('channel_secret');
        
    //if(empty($channel_uuid)) return '��δ�󶨵µù���˺ţ���<a href="'.MDA_APIHOST.'/home/register" target="_blank">ע��</a>����ϵͳ��̨��';
    
    $prefix = 'mda';
    $key = 'code'.md5($uuid.$name);
    $row = GetCache($prefix, $key);

    if(!is_array($row))
    {
        $ts = time();
        $paramsArr=array(
            'channel_uuid'=>$channel_uuid, 
            'channel_secret'=>$channel_secret,
            'ts'=>$ts,
            'crc'=>md5($channel_uuid.$channel_secret.$ts),
        );
        if ( !empty($uuid) )
        {
            $paramsArr['place_uuid'] = $uuid;
        } else {
            $paramsArr['tag_name'] = urlencode($name);
        }

        $place = json_decode(mda_http_send(MDA_API_GET_PLACE,0,$paramsArr),TRUE);
        
        if (!isset($place['data']['place_code']) )
        {
            return '���λAPI�ӿ�ͨ�Ŵ��󣬲鿴<a href="'.MDA_APIHOST.'/help/apicode/'.$place['code'].'" target="_blank">�µù��</a>��ȡ����';
        }
    
        $row['reval'] = htmlspecialchars($place['data']['place_code']);
        SetCache($prefix, $key, $row, 60*60*12);
    }

    if($cfg_soft_lang != 'utf-8') $row = AutoCharset($row, 'utf-8', 'gb2312');
    
    $reval .= htmlspecialchars_decode($row['reval']);
        
    return $reval;
}

