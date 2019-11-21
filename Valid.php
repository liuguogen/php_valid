<?php
namespace Valid;
// +----------------------------------------------------------------------
// |数据验证类
// +----------------------------------------------------------------------
// | Author: liuguogen <liuguogen_vip@163.com>
// +----------------------------------------------------------------------

class Valid
{
    // 实例
    protected static $instance;

    // 自定义的验证类型
    protected static $type = [];

    // 验证类型别名
    protected $alias = [
        '>' => 'gt', '>=' => 'gte', '<' => 'lt', '<=' => 'lte', '=' => 'eq', 'same' => 'eq',
    ];

    // 当前验证的规则
    protected $rule = [];

    // 验证提示信息
    protected $message = [];
    // 验证字段描述
    protected $field = [];

    // 验证规则默认提示信息
    protected static $typeMsg = [];

    // 正则表达式 regex = ['zip'=>'\d{6}',...]
    protected $regex = [];

    // 验证失败错误信息
    protected $error = [];

    
    


    /**
     * 构造函数
     * @access public
     * @param array $rules 验证规则
     * @param array $message 验证提示信息
     * @param array $field 验证字段描述信息
     */
    

    public function __construct(array $rules = [], $message = [], $field = [])
    {

        $validation           = require  . 'config/valid.php';
        $this->rule    = array_merge($this->rule, $rules);

        $this->message = array_merge($this->message, $message);
        $this->field   = array_merge($this->field, $field);
        self::$typeMsg = $validation;
    }

    /**
     * 实例化验证
     * @access public
     * @param array     $rules 验证规则
     * @param array     $message 验证提示信息
     * @param array     $field 验证字段描述信息
     * @return Validate
     */
    public static  function make($rules = [], $data=[],&$message = [], $field = [])
    {

        $validate = new self($rules, $message, $field);
        
        if(!$validate->check($data,$validate->rule)) {
            $message = $validate->getError();
        }else {
            $message = [];
            foreach ($data as $key => $value) {
                if(!in_array($key, array_keys($rules))) {
                    unset($data[$key]);
                }
            }
            foreach ($rules as $key => $value) {
                if(isset($value['default']) && ($value['default']!='' || !empty($value['default']) || in_array($value['default'], [0,'0']) ) && !$data[$key] ) {
                    $data[$key] = $value['default'];
                }
            }
            return $data;
        }
        
        
    }
    
    /**
     * 添加字段验证规则
     * @access protected
     * @param string|array  $name  字段名称或者规则数组
     * @param mixed         $rule  验证规则
     * @return Validate
     */
    public function rule($name, $rule = '')
    {
        if (is_array($name)) {
            $this->rule = array_merge($this->rule, $name);
        } else {
            $this->rule[$name] = $rule;
        }
        return $this;
    }

    /**
     * 注册验证（类型）规则
     * @access public
     * @param string    $type  验证规则类型
     * @param mixed     $callback callback方法(或闭包)
     * @return void
     */
    public  function extend($type, $callback = null)
    {
        if (is_array($type)) {
            self::$type = array_merge(self::$type, $type);
        } else {
            self::$type[$type] = $callback;
        }
    }

    /**
     * 设置验证规则的默认提示信息
     * @access protected
     * @param string|array  $type  验证规则类型名称或者数组
     * @param string        $msg  验证提示信息
     * @return void
     */
    public function setTypeMsg($type, $msg = null)
    {
        if (is_array($type)) {
            self::$typeMsg = array_merge(self::$typeMsg, $type);
        } else {
            self::$typeMsg[$type] = $msg;
        }
    }

    /**
     * 设置提示信息
     * @access public
     * @param string|array  $name  字段名称
     * @param string        $message 提示信息
     * @return Validate
     */
    public function message($name, $message = '')
    {
        if (is_array($name)) {
            $this->message = array_merge($this->message, $name);
        } else {
            $this->message[$name] = $message;
        }
        return $this;
    }


    /**
     * 数据自动验证
     * @access public
     * @param array     $data  数据
     * @param mixed     $rules  验证规则
     * @param string    $scene 验证场景
     * @return bool
     */
    public function check($data, $rules = [], $scene = '')
    {



        foreach($data as $k => &$v) {
           if(json_decode($v,1)) {
                $v = json_decode($v,1);
           }
        }
        
        $this->error = [];

        if (empty($rules)) {
            // 读取验证规则
            $rules = $this->rule;
        }
    
       
        foreach ($rules as $key => $item) {
            
            $rule = $item['vstr'];
            $type = $item['type'];
            if(!$rule || !$type) continue;
            if (isset($item['msg'])) {
                $msg = is_string($item['msg']) ? explode('|', $item['msg']) : $item['msg'];
            } else {
                $msg = [];
            }
            $value = $this->getDataValue($data, $key);
            if('mix' != $type ) {
                $result = $this->checkItem($key, $value, $type, $data, $title, []); //$this->is($value,$type,$data);
                if (true !== $result) {
                    // 没有返回true 则表示验证失败
                    $this->error = $result;
                    return false;
                }
            }

            if (strpos($key, '|')) {
                // 字段|描述 用于指定属性名称
                list($key, $title) = explode('|', $key);
            } else {
                $title = isset($this->field[$key]) ? $this->field[$key] : $key;
            }

            
            if(!strpos($rule, ':') && $rule == 'in') {
                if(isset($item['in_data']) && is_array($item['in_data'])) {
                    $rule = 'in'.':'.implode(',', $item['in_data']);
                }
                if(isset($item['in_kdata']) && is_array($item['in_kdata'])) {
                    $rule = 'in'.':'.implode(',', array_keys($item['in_kdata']));
                }
            }
            // 获取数据 支持二维数组
            $value = $this->getDataValue($data, $key);

            if(strpos($rule, ':')) {
                $m_rule = explode(':', $rule);
                if($m_rule[0] == 'mustKey') {
                   $result =   $this->checkItem($key, $value, $rule, $data, $title, $msg);
                   if (true !== $result) {
                        // 没有返回true 则表示验证失败
                        $this->error = $result;
                        return false;
                    }
                } 
               
            }
            if(isset($item['child']) && $item['child'] && is_array($item['child'])) {
               
                 $result = $this->check($data[$key],$item['child']);
                 
                return $result;
            }
            

            
            
            // 字段验证
            if ($rule instanceof \Closure) {
                // 匿名函数验证 支持传入当前字段和所有字段两个数据
                $result = call_user_func_array($rule, [$value, $data]);
            } else {
                
                $result = $this->checkItem($key, $value, $rule, $data, $title, $msg);
            }
            
            
            if (true !== $result) {
                // 没有返回true 则表示验证失败
                $this->error = $result;
                return false;
            }
        }


        return !empty($this->error) ? false : true;
    }

    /**
     * 验证必须存在的key
     * @access protected
     * @param  mixed     $value 字段值
     * @param  mixed     $rules 验证规则
     * @return bool
     */
    protected function mustKey($value,$rule) {

        
        $mustKey = explode(',', $rule);
        $result = true;
        foreach ($mustKey as $key => $field) {
            if(!in_array($field, array_keys($value))) {
                $result = false;
                continue;
            }
        }

        return $result;
         
    }

    /**
     * 根据验证规则验证数据
     * @access protected
     * @param  mixed     $value 字段值
     * @param  mixed     $rules 验证规则
     * @return bool
     */
    protected function checkRule($value, $rules)
    {
        if ($rules instanceof \Closure) {
            return call_user_func_array($rules, [$value]);
        } elseif (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $key => $rule) {
            if ($rule instanceof \Closure) {
                $result = call_user_func_array($rule, [$value]);
            } else {
                // 判断验证类型
                list($type, $rule) = $this->getValidateType($key, $rule);

                $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this, $type];

                $result = call_user_func_array($callback, [$value, $rule]);
            }

            if (true !== $result) {
                return $result;
            }
        }

        return true;
    }

    /**
     * 验证单个字段规则
     * @access protected
     * @param string    $field  字段名
     * @param mixed     $value  字段值
     * @param mixed     $rules  验证规则
     * @param array     $data  数据
     * @param string    $title  字段描述
     * @param array     $msg  提示信息
     * @return mixed
     */
    protected function checkItem($field, $value, $rules, $data, $title = '', $msg = [])
    {


        // 支持多规则验证 require|in:a,b,c|... 或者 ['require','in'=>'a,b,c',...]
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        $i = 0;
        
        foreach ($rules as $key => $rule) {

            if ($rule instanceof \Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
                $info   = is_numeric($key) ? '' : $key;
            } else {
                // 判断验证类型
                list($type, $rule, $info) = $this->getValidateType($key, $rule);

                // 如果不是must 有数据才会行验证
                if (0 === strpos($info, 'must') ||  (!is_null($value) && '' !== $value)) {

                    // 验证类型
                    $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this, $type];

                    // 验证数据
                    $result = call_user_func_array($callback, [$value, $rule, $data, $field, $title]);

                } else {
                    $result = true;
                }
            }

            if (false === $result) {
                // 验证失败 返回错误信息
                if (isset($msg[$i])) {
                    $message = $msg[$i];
                
                    if (is_string($message) && strpos($message, '{%') === 0) {
                        $message = substr($message, 2, -1);
                    }
                } else {
                    
                    $message = $this->getRuleMsg($field, $title, $info, $rule);
                }
               
                return ['msg'=>$message,'code'=>isset(self::$typeMsg[$info]['code']) ?  self::$typeMsg[$info]['code'] : 400 ];
            } elseif (true !== $result) {
                // 返回自定义错误信息
                if (is_string($result) && false !== strpos($result, ':')) {
                    $result = str_replace([':attribute', ':rule'], [$title, (string) $rule], $result);
                }
                return $result;
            }
            $i++;
        }
        return $result;
    }

    /**
     * 获取当前验证类型及规则
     * @access public
     * @param  mixed     $key
     * @param  mixed     $rule
     * @return array
     */
    protected function getValidateType($key, $rule)
    {

        // 判断验证类型
        if (!is_numeric($key)) {
            return [$key, $rule, $key];
        }

        if (strpos($rule, ':')) {
            list($type, $rule) = explode(':', $rule, 2);

            if (isset($this->alias[$type])) {
                // 判断别名
                $type = $this->alias[$type];
            }
            $info = $type;
        } elseif (method_exists($this, $rule)) {
            $type = $rule;
            $info = $rule;
            $rule = '';
        } else {
            $type = 'is';
            $info = $rule;
        }

        return [$type, $rule, $info];
    }



    /**
     * 验证是否和某个字段的值一致
     * @access protected
     * @param mixed     $value 字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @param string    $field 字段名
     * @return bool
     */
    protected function confirm($value, $rule, $data, $field = '')
    {
        if ('' == $rule) {
            if (strpos($field, '_confirm')) {
                $rule = strstr($field, '_confirm', true);
            } else {
                $rule = $field . '_confirm';
            }
        }
        return $this->getDataValue($data, $rule) === $value;
    }

    /**
     * 验证是否和某个字段的值是否不同
     * @access protected
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool
     */
    protected function different($value, $rule, $data)
    {
        return $this->getDataValue($data, $rule) != $value;
    }

    /**
     * 验证是否大于等于某个值
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function gte($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && $value >= $val;
    }

    /**
     * 验证是否大于某个值
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function gt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && $value > $val;
    }

    /**
     * 验证是否小于等于某个值
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function lte($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && $value <= $val;
    }

    /**
     * 验证是否小于某个值
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function lt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && $value < $val;
    }
    /**
     * 验证是否大于0
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function gt0($value,$rule,$data) {
        return $value > 0;  
    }

    /**
     * 验证是否等于某个值
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function eq($value, $rule)
    {
        return $value == $rule;
    }
    /**
     * 验证是否不等于某个值
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function neq($value, $rule)
    {
        return $value != $rule;
    }
    /**
    * 验证数组是否大于某个值
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @param array     $data  数据
    * @return bool
    */
    protected function count_gt($value,$rule,$data) {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && is_array($value) && count($value) > $val;
    }
    /**
    * 验证数组是否大于等于某个值
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @param array     $data  数据
    * @return bool
    */
    protected function count_gte($value,$rule,$data) {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && is_array($value) && count($value) >= $val;
    }
    /**
    * 验证数组是否小于某个值
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @param array     $data  数据
    * @return bool
    */
    protected function count_lt($value,$rule,$data) {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && is_array($value) && count($value) < $val;
    }
    /**
    * 验证数组是否小于等于某个值
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @param array     $data  数据
    * @return bool
    */
    protected function count_lte($value,$rule,$data) {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && is_array($value) && count($value) <= $val;
    }
    /**
    * 验证数组是否唯一
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @param array     $data  数据
    * @return bool
    */
    protected function unique($value,$rule,$data) {

        return is_array($value)  && count($value) == count(array_unique($value));
    }
    /**
     * 验证字段值是否为有效格式
     * @access protected
     * @param mixed     $value  字段值
     * @param string    $rule  验证规则
     * @param array     $data  验证数据
     * @return bool
     */
    protected function is($value, $rule, $data = [])
    {
        switch ($rule) {
            case 'must':
                // 必须
                $result = !empty($value) || '0' == $value;
                break;
            
            case 'date':
                // 是否是一个有效日期
                $result = false !== strtotime($value);
                break;
             case 'str':
                // 是否是字符串
                $result = is_string($value) && ''!=(string) $value && !empty($value);
                break;
            case 'float':
                // 是否为float
                $result = $this->filter($value, FILTER_VALIDATE_FLOAT) && is_float($value);
                break;
            case 'num':
                $result = ctype_digit((string) $value);
                break;
            case 'int':
                // 是否为整型
                $result = $this->filter($value, FILTER_VALIDATE_INT);
                break;
            case 'short':
                // 是否为短整型
                $result = is_numeric($value) && is_int($value) && intval($value) >=0 && intval($value) <=65535;
                break;
            
            case 'boolean':
            case 'bool':
                // 是否为布尔值
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'list':
                // 是否为数组
                $result = is_array($value);
                break;
            case 'file':
                $result = $value instanceof File;
                break;
            case 'image':
                $result = $value instanceof File && in_array($this->getImageType($value->getRealPath()), [1, 2, 3, 6]);
                break;
            
            default:
                if (isset(self::$type[$rule])) {
                    // 注册的验证规则
                    $result = call_user_func_array(self::$type[$rule], [$value]);
                } else {
                    // 正则验证
                    $result = $this->regex($value, $rule);
                }
        }
        return $result;
    }

    // 判断图像类型
    protected function getImageType($image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        } else {
            try {
                $info = getimagesize($image);
                return $info ? $info[2] : false;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    

    

    /**
     * 验证上传文件后缀
     * @access protected
     * @param mixed     $file  上传文件
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function fileExt($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkExt($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkExt($rule);
        } else {
            return false;
        }
    }

    /**
     * 验证上传文件类型
     * @access protected
     * @param mixed     $file  上传文件
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function fileMime($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkMime($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkMime($rule);
        } else {
            return false;
        }
    }

    /**
     * 验证上传文件大小
     * @access protected
     * @param mixed     $file  上传文件
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function fileSize($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkSize($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkSize($rule);
        } else {
            return false;
        }
    }

    /**
     * 验证图片的宽高及类型
     * @access protected
     * @param mixed     $file  上传文件
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function image($file, $rule)
    {
        if (!($file instanceof File)) {
            return false;
        }
        if ($rule) {
            $rule                        = explode(',', $rule);
            list($width, $height, $type) = getimagesize($file->getRealPath());
            if (isset($rule[2])) {
                $imageType = strtolower($rule[2]);
                if ('jpeg' == $imageType) {
                    $imageType = 'jpg';
                }
                if (image_type_to_extension($type, false) != $imageType) {
                    return false;
                }
            }

            list($w, $h) = $rule;
            return $w == $width && $h == $height;
        } else {
            return in_array($this->getImageType($file->getRealPath()), [1, 2, 3, 6]);
        }
    }

    /**
     * 验证请求类型
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function method($value, $rule)
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        return strtoupper($rule) == $method;
    }

    /**
     * 验证时间和日期是否符合指定格式
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function dateFormat($value, $rule)
    {
        $info = date_parse_from_format($rule, $value);
        return 0 == $info['warning_count'] && 0 == $info['error_count'];
    }

    

   

    /**
     * 使用filter_var方式验证
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function filter($value, $rule)
    {
        if (is_string($rule) && strpos($rule, ',')) {
            list($rule, $param) = explode(',', $rule);
        } elseif (is_array($rule)) {
            $param = isset($rule[1]) ? $rule[1] : null;
            $rule  = $rule[0];
        } else {
            $param = null;
        }
        return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule), $param);
    }

    /**
     * 验证某个字段等于某个值的时候必须
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function mustIf($value, $rule, $data)
    {
        list($field, $val) = explode(',', $rule);
        if ($this->getDataValue($data, $field) == $val) {
            return !empty($value) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * 通过回调方法验证某个字段是否必须
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function mustCallback($value, $rule, $data)
    {
        $result = call_user_func_array($rule, [$value, $data]);
        if ($result) {
            return !empty($value) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * 验证某个字段有值的情况下必须
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function mustWith($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        if (!empty($val)) {
            return !empty($value) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * 验证是否在范围内
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function in($value, $rule)
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 验证是否不在某个范围
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function not_in($value, $rule)
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
    * range验证数据
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function range($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;
        return $value >= $min && $value <= $max;
    }
    /**
    * 使用not_range验证数据 不在某个范围
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function not_range($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;
        return $value < $min || $value > $max;
    }

    /**
    * strIs验证数据
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function strIs($value,$rule) {
        
        if (is_string($rule)) {

            switch ($rule) {
                case 'email'://验证邮箱
                    $result = $this->filter($value, FILTER_VALIDATE_EMAIL);
                    break;
                case 'mobile'://验证手机
                    $result = $this->regex($value, '/^1[3-9][0-9]\d{8}$/');
                    break;
                case 'url'://验证url
                    $result = $this->filter($value, FILTER_VALIDATE_URL);
                    break;
                case 'domain'://验证domain
                    if (!in_array($rule, ['A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY'])) {
                        $rule = 'MX';
                    }
                    $result = checkdnsrr($value, $rule);
                    break;
                case 'ip': //验证ip
                    $result = $this->filter($value, [FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6]);
                    break;
                case 'ipv4'://验证ipv4
                    $result = $this->filter($value, [FILTER_VALIDATE_IP, FILTER_FLAG_IPV4]);
                    break;
                case 'ipv6'://验证ipv6
                    $result = $this->filter($value, [FILTER_VALIDATE_IP, FILTER_FLAG_IPV6]);
                    break;
                case 'date': //验证 日期 格式 2019-11-18
                    $result = $this->regex($value,'/^[12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1])$/');
                    break;
                case 'time'://验证时间  格式 20:12:14
                    $result = $this->regex($value,'/^([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)$/');
                    break;
                case 'datetime': //验证日期时间 格式 2019-11-18 14:10:51
                    $result = $this->regex($value,'/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d)(:[0-5]\d)?$/');
                    break;
                case 'json'://验证json
                    $is_json = !is_string($value) && in_array($value, ['true','false',true, false, 0, 1, '0', '1'], true) ? false :true;
                    if($is_json) {
                        json_decode($value);
                        $result = json_last_error() === JSON_ERROR_NONE;
                    }else {
                        $result = false;
                    }
                    break;
                case 'ip_range':
                    $result = true;
                    break;
                case 'qq'://验证QQ
                    $result = $this->regex($value, '/\d{5,11}/');
                    break;
                case 'port': //验证端口
                    $result = $this->regex($value,'/^(\d)+$/') && intval($value) <=65535 && intval($value) > 0;
                    break;
                case 'lanIp': //验证是否为局域网ip
                    $result = $this->regex($value,'/^(127\.0\.0\.1)|(localhost)|(10\.\d{1,3}\.\d{1,3}\.\d{1,3})|(172\.((1[6-9])|(2\d)|(3[01]))\.\d{1,3}\.\d{1,3})|(192\.168\.\d{1,3}\.\d{1,3})$/');
                    break;
                case 'scheme':
                    $result = true;
                    break;
                case 'ip_cidr'://验证无类别域间路由
                    $result = $this->regex($value,'/^(?:(?:[0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}(?:[0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\/([1-9]|[1-2]\d|3[0-2])$/');
                    break;
                case 'zip'://验证邮政编码
                    $result = $this->regex($value, '/\d{6}/');
                    break;
                case 'idCard'://验证身份证号
                    $result = $this->regex($value, '/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/');
                    break;
                default:
                    
                    break;
            }
        }
        return $result ? : false;
    }
    /**
    * strIn验证数据
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function strIn($value,$rule) {
        if(!is_string($rule)) return false;
        if(strpos($rule, ',')) {
            $reg_rule = '';
            $_rule = explode(',', $rule) ;
            foreach ($_rule as $key => $val) {
                switch ($val) {
                    case 'word':
                        $reg_rule.='A-Za-z';
                        break;
                    case 'num':
                        $reg_rule.='0-9';
                        break;
                    case 'hanzi':
                        $reg_rule.='\x{4e00}-\x{9fa5}';
                        break;
                    case '-':
                        $reg_rule.='\-';
                        break;
                    default:
                        $reg_rule .= '\_';
                        break;
                }
            }

            return $this->regex($value, "/^[{$reg_rule}]+$/u");
            
            
        }else {
            switch ($rule) {
                case 'word':
                    return $this->regex($value, '/^[A-Za-z]+$/');
                    break;
                case 'num':
                    return $this->regex($value, '/^[0-9]+$/');
                    break;
                case 'hanzi':
                    return $this->regex($value, '/^[\x{4e00}-\x{9fa5}]+$/u');
                    break;
                case '-':
                    return $this->regex($value, '/^[\-]+$/u');
                    break;
                case '_':
                    return $this->regex($value, '/^[\_]+$/u');
                    break;
                default:
                    return $this->regex($value, '/^[A-Za-z]+$/');
                    break;
            }
        }
       
        
        
    }
    /**
    * 字符串长度大于
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function len_gt($value,$rule)
    {
        if(!is_string($value)) return false;
        return mb_strlen((string)$value) > $rule;
    }
    /**
    * 字符串长度大于等于
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function len_gte($value,$rule)
    {
        if(!is_string($value)) return false;
        return mb_strlen((string)$value) >= $rule;
    }
    /**
    * 字符串长度小于
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function len_lt($value,$rule)
    {
        if(!is_string($value)) return false;
        return mb_strlen((string)$value) < $rule;
    }
    /**
    * 判断字符串是否有后缀
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function suffix($value,$rule)
    {
        if(!is_string($value)) return false;
        return strripos($value, $rule) ? true :false;
    }
    /**
    * 判断字符串是否有前缀
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function prefix($value,$rule)
    {
        if(!is_string($value)) return false;
        return stripos($value, $rule) ? true :false;
    }
    /**
    * 字符串长度小于等于
    * @access protected
    * @param mixed     $value  字段值
    * @param mixed     $rule  验证规则
    * @return bool
    */
    protected function len_lte($value,$rule)
    {
        if(!is_string($value)) return false;
        return mb_strlen((string)$value) <= $rule;
    }
    

    /**
     * 验证数据长度
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function length($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }

        if (strpos($rule, ',')) {
            // 长度区间
            list($min, $max) = explode(',', $rule);
            return $length >= $min && $length <= $max;
        } else {
            // 指定长度
            return $length == $rule;
        }
    }

    /**
     * 验证数据最大长度
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function max($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }
        return $length <= $rule;
    }

    /**
     * 验证数据最小长度
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function min($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }
        return $length >= $rule;
    }

    /**
     * 验证日期
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function after($value, $rule, $data)
    {
        return strtotime($value) >= strtotime($rule);
    }

    /**
     * 验证日期
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function before($value, $rule, $data)
    {
        return strtotime($value) <= strtotime($rule);
    }

    /**
     * 验证日期字段
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function afterWith($value, $rule, $data)
    {
        $rule = $this->getDataValue($data, $rule);
        return !is_null($rule) && strtotime($value) >= strtotime($rule);
    }

    /**
     * 验证日期字段
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function beforeWith($value, $rule, $data)
    {
        $rule = $this->getDataValue($data, $rule);
        return !is_null($rule) && strtotime($value) <= strtotime($rule);
    }

    /**
     * 验证有效期
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function expire($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($start, $end) = $rule;
        if (!is_numeric($start)) {
            $start = strtotime($start);
        }

        if (!is_numeric($end)) {
            $end = strtotime($end);
        }
        return $_SERVER['REQUEST_TIME'] >= $start && $_SERVER['REQUEST_TIME'] <= $end;
    }

    /**
     * 验证IP许可
     * @access protected
     * @param string    $value  字段值
     * @param mixed     $rule  验证规则
     * @return mixed
     */
    protected function allowIp($value, $rule)
    {
        return in_array($_SERVER['REMOTE_ADDR'], is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 验证IP禁用
     * @access protected
     * @param string    $value  字段值
     * @param mixed     $rule  验证规则
     * @return mixed
     */
    protected function denyIp($value, $rule)
    {
        return !in_array($_SERVER['REMOTE_ADDR'], is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 使用正则验证数据
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则 正则规则或者预定义正则名
     * @return mixed
     */
    protected function regex($value, $rule)
    {
        if (isset($this->regex[$rule])) {
            $rule = $this->regex[$rule];
        }
        if (0 !== strpos($rule, '/') && !preg_match('/\/[imsU]{0,4}$/', $rule)) {
            // 不是正则表达式则两端补上/
            $rule = '/^' . $rule . '$/';
        }
        return is_scalar($value) && 1 === preg_match($rule, (string) $value);
    }

  

    // 获取错误信息
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取数据值
     * @access protected
     * @param array $data 数据
     * @param string $key 数据标识 支持二维
     * @return mixed
     */
    protected function getDataValue($data, $key)
    {
        if (is_numeric($key)) {
            $value = $key;
        } elseif (strpos($key, '.')) {
            // 支持二维数组验证
            list($name1, $name2) = explode('.', $key);
            $value               = isset($data[$name1][$name2]) ? $data[$name1][$name2] : null;
        } else {
            $value = isset($data[$key]) ? $data[$key] : null;
        }
        return $value;
    }

    /**
     * 获取验证规则的错误提示信息
     * @access protected
     * @param string    $attribute  字段英文名
     * @param string    $title  字段描述名
     * @param string    $type  验证规则名称
     * @param mixed     $rule  验证规则数据
     * @return string
     */
    protected function getRuleMsg($attribute, $title, $type, $rule)
    {

        if (isset($this->message[$attribute . '.' . $type['msg']])) {
            $msg = $this->message[$attribute . '.' . $type['msg']];
        } elseif (isset($this->message[$attribute][$type]['msg'])) {
            $msg = $this->message[$attribute][$type]['msg'];
        } elseif (isset($this->message[$attribute])) {
            $msg = $this->message[$attribute];
        } elseif (isset(self::$typeMsg[$type]['msg'])) {
            $msg = self::$typeMsg[$type]['msg'];
        } elseif (0 === strpos($type, 'must')) {
            $msg = self::$typeMsg['must']['msg'];
        } else {
            $msg = $attribute . ' not conform to the rules';
        }

        if (is_string($msg) && 0 === strpos($msg, '{%')) {
            $msg = substr($msg, 2, -1);
        } 

        if (is_string($msg) && is_scalar($rule) && false !== strpos($msg, ':')) {
            // 变量替换
            if (is_string($rule) && strpos($rule, ',')) {
                $array = array_pad(explode(',', $rule), 3, '');
            } else {
                $array = array_pad([], 3, '');
            }
            $msg = str_replace(
                [':attribute', ':rule', ':1', ':2', ':3'],
                [$attribute, (string) $rule, $array[0], $array[1], $array[2]],
                $msg);
        }
        return $msg;
    }

}
