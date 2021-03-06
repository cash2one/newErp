﻿<?php
//	by winday 2013-5-17
/*--------------------------------------------------------
 *	taobao.logistics.companies.get
 *	查询淘宝支持的快递公司信息
 */
include_once WEB_PATH."lib/api/taobao/taobaoSession.php";
class TaobaoLogisticsCompaniesGet extends TaobaoSession{
    public function __construct(){
		parent::__construct();
	}
	
    /*--------------------------------------------------------
     *	taobao.logistics.companies.get
     *	查询淘宝支持的快递公司信息
     */    
    function taobaoLogisticsCompaniesGet(){    
    	$paramArr	=	array(
    				'method' => 'taobao.logistics.companies.get',  
    			   'session' => $this->session,			
    			 'timestamp' => date('Y-m-d H:i:s'),			
    				'format' => 'json',				
    			   'app_key' => $this->appKey,					
    					 'v' => '2.0',					   
    			'sign_method'=> 'md5',						
    				'fields' =>  'id,code,name,reg_mail_no',  
    	);   
    	$sign		=	$this->tmall_createSign($paramArr,$this->appSecret);
    	$strParam	=	$this->tmall_createStrParam($paramArr);
    	$strParam	.=	'sign='.$sign;
    	$urls		=	$this->url.$strParam;
    
    	$cnt	=	0;	
    	while($cnt < 3 && ($result=@$this->tmall_vita_get_url_content($urls))===FALSE) $cnt++;
    	$json_data	=	json_decode($result,true);
    	return $json_data;    
    }
}
?>