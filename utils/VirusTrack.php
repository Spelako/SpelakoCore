<?php
// https://lab.ahusmart.com/
const MAIN_URL = 'https://lab.isaaclin.cn/';
const ALIASES = [
	'上海'=>'上海市',
	'云南'=>'云南省',
	'内蒙古'=>'内蒙古自治区',
	'北京'=>'北京市',
	'吉林'=>'吉林省',
	'四川'=>'四川省',
	'天津'=>'天津市',
	'宁夏'=>'宁夏回族自治区',
	'安徽'=>'安徽省',
	'山东'=>'山东省',
	'山西'=>'山西省',
	'广东'=>'广东省',
	'广西'=>'广西壮族自治区',
	'江苏'=>'江苏省',
	'江西'=>'江西省',
	'河北'=>'河北省',
	'河南'=>'河南省',
	'浙江'=>'浙江省',
	'海南'=>'海南省',
	'湖北'=>'湖北省',
	'湖南'=>'湖南省',
	'甘肃'=>'甘肃省',
	'福建'=>'福建省',
	'西藏'=>'西藏自治区',
	'贵州'=>'贵州省',
	'辽宁'=>'辽宁省',
	'重庆'=>'重庆市',
	'陕西'=>'陕西省',
	'青海'=>'青海省',
	'黑龙江'=>'黑龙江省'
];

function virus_overall() {
	$result = json_decode(file_get_contents(MAIN_URL.'nCoV/api/overall', false, stream_context_create($GLOBALS['stream_opts'])), true);
	if($result['success']){
		return $result['results'][0];
	}
	else {
		return false;
	}
}
function virus_getProvinceStats($province){
	if(isset(ALIASES[$province])) $province = ALIASES[$province];
	$result = json_decode(file_get_contents(MAIN_URL.'nCoV/api/area?province='.urlencode($province), false, stream_context_create($GLOBALS['stream_opts'])), true);
	if($result['success']){
		if($result['results']){
			return $result['results'][0];
		}
		else {
			return false;
		}
	}
	else {
		return false;
	}
}
?>