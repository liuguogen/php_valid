<?php

return  array(

			'must'        => ['msg'=>':attribute不能为空','code'=>10001],
	        'num'         => ['msg'=>':attribute必须是数字','code'=>10002],
	        'int'         => ['msg'=>':attribute必须是整数','code'=>10003],
	        'short'       => ['msg'=>':attribute必须是短整数','code'=>10004],
	        'float'       => ['msg'=>':attribute必须是浮点数','code'=>10005],
	        'str'         => ['msg'=>':attribute必须是字符串','code'=>10006],
	        'gte'         => ['msg'=>':attribute必须大于等于 :rule','code'=>10007],
	        'gt'          => ['msg'=>':attribute必须大于 :rule','code'=>10008],
	        'lte'         => ['msg'=>':attribute必须小于等于 :rule','code'=>10009],
	        'lt'          => ['msg'=>':attribute必须小于 :rule','code'=>10010],
	        'eq'          => ['msg'=>':attribute必须等于 :rule','code'=>10011],
	        'neq'         => ['msg'=>':attribute必须不等于 :rule','code'=>10012],
	        'gt0'         => ['msg'=>':attribute必须大于0','code'=>100013],
	        'bool'        => ['msg'=>':attribute必须是布尔值','code'=>10014],
	        'regex'       => ['msg'=>':attribute不符合指定规则','code'=>10015],
 	        'list'        => ['msg'=>':attribute必须是数组','code'=>10016],
 	        'mustKey'     => ['msg'=>':attribute必须存在 :rule','code'=>10017],
	        'date'        => ['msg'=>':attribute格式不符合','code'=>10018],
	        'dateFormat'  => ['msg'=>':attribute必须使用日期格式 :rule','code'=>10019],
	        'after'       => ['msg'=>':attribute日期不能小于 :rule','code'=>10020],
	        'before'      => ['msg'=>':attribute日期不能超过 :rule','code'=>10021],
	        'expire'      => ['msg'=>'不在有效期内 :rule','code'=>10022],
	        'file'        => ['msg'=>':attribute不是有效的上传文件','code'=>10023],
	        'fileSize'    => ['msg'=>'上传文件大小不符','code'=>10024],
	        'fileExt'     => ['msg'=>'上传文件后缀不符','code'=>10025],
	        'fileMime'    => ['msg'=>'上传文件类型不符','code'=>10026],
	        'image'       => ['msg'=>':attribute不是有效的图像文件','code'=>10027],
	        'strIn'       => ['msg'=>':attribute格式不符','code'=>10028],
	        'strIs'       => ['msg'=>':attribute格式不符','code'=>10029],
	        'len_gt'      => ['msg'=>':attribute长度必须大于 :rule','code'=>10030],
	        'len_gte'     => ['msg'=>':attribute长度必须大于等于 :rule','code'=>10031],
	        'len_lt'      => ['msg'=>':attribute长度必须小于 :rule','code'=>10032],
	        'len_lte'     => ['msg'=>':attribute长度必须小于等于 :rule','code'=>10033],
	        'suffix'      => ['msg'=>':attribute没有后缀 :rule','code'=>10034],
	        'prefix'      => ['msg'=>':attribute没有并锥 :rule','code'=>10035],
	        'different'   => ['msg'=>':attribute和比较字段:2不能相同','code'=>10036],
	        'in'          => ['msg'=>':attribute必须在 :rule 范围内','code'=>10037],
	        'not_in'      => ['msg'=>':attribute不能在 :rule 范围内','code'=>10038],
	        'count_gt'    => ['msg'=>':attribute数组必须大于 :rule','code'=>10039],
	        'count_gte'   => ['msg'=>':attribute数组必须大于等于 :rule','code'=>10040],
	        'count_lt'    => ['msg'=>':attribute数组必须小于 :rule','code'=>10041],
	        'count_lte'   => ['msg'=>':attribute数组必须小于等于 :rule','code'=>10042],
	        'unique'      => ['msg'=>':attribute有重复值存在 :rule','code'=>10043],
	        'range'       => ['msg'=>':attribute只能在 :1 - :2 之间','code'=>10044],
	        'not_range'   => ['msg'=>':attribute不能在 :1 - :2 之间','code'=>10045],
	        'length'      => ['msg'=>':attribute长度不符合要求 :rule','code'=>10046],
	        'max'         => ['msg'=>':attribute长度不能超过 :rule','code'=>10047],
	        'min'         => ['msg'=>':attribute长度不能小于 :rule','code'=>10048],
	        'allowIp'     => ['msg'=>'不允许的IP访问','code'=>10049],
	        'denyIp'      => ['msg'=>'禁止的IP访问','code'=>10050],
	        'confirm'     => ['msg'=>':attribute和确认字段:2不一致','code'=>10051],
	        'mustIf'   => ['msg'=>':attribute不能为空','code'=>10052],// 验证某个字段的值等于某个值的时候必须 requireIf:field,value  'password'=>'requireIf:account,1' 当account的值等于1的时候 password必须
	        'mustWith' => ['msg'=>':attribute不能为空','code'=>10053],// 验证某个字段有值的时候必须 requireWith:field 'password'=>'requireWith:account' // 当account有值的时候password字段必须
	        'mustCallback' => ['msg'=>':attribute不能为空','code'=>10054],// 验证当某个callable为真的时候字段必须  requireCallback:callable 使用check_require方法检查是否需要验证age字段必须'age'=>'requireCallback:check_require|number'
	        
			
    );

?>