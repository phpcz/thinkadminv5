<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/4
 * Time: 16:23
 */
namespace app\common\service;
use PHPMailer\PHPMailer\PHPMailer;
use Qiniu\Auth;
use think\Db;

/**
 * 工具类
 * Class ToolService
 * @package app\api\service
 */
class ToolService
{
    const OPENSS_KEY = 'aUzNsB91wQt3qmSk';  //openssl密钥
    const OPENSS_IV = 'HTa8QNWzPFuhKJyt';  //openssl偏移量

    const BD_APP_ID = '';   //百度内容审核App ID
    const BD_API_KEY  = '';   //百度内容审核Api Key
    const BD_SECRET_KEY  = '';   //百度内容审核Secret Key


    /**
     * 百度内容审核
     * @param $content  审核内容 文本/图片地址
     * @param $type     审核类型 1文本 2图片（支持以竖线分割的多图）
     * @return bool
     */
    public static function bdCheckContent($content,$type)
    {
        require_once __DIR__.'/../../../vendor/baidu/AipContentCensor.php';
        $client = new \AipContentCensor(self::BD_APP_ID,self::BD_API_KEY,self::BD_SECRET_KEY);

        if ($type == 1) //文本内容
        {
            $result = $client->textCensorUserDefined($content);

            if (!empty($result['error_code']))
            {
                return false;
            }

        }else{         //图片

            $content = explode('|',$content);

            foreach ($content as $v)
            {
                $result = $client->imageCensorUserDefined($v);
                if (!empty($result['error_code']))
                {
                    return false;
                }
            }

        }
        return true;
    }

    /**
     * 检查图片格式是否正确
     * @param $images
     * @return bool
     */
    public static function checkImage($images)
    {
        if (!is_array($images))
        {
            $images = explode('|',$images);
        }

        foreach ($images as $v)
        {
            if (!preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $v))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * 快递100查询
     * @param $num
     * @param string $comCode
     * @return array
     */
    public static function kd100ExpressQuery($num,$comCode = '')
    {
        $key = 'XEqaSrBw8185';  //授权KEY
        $post_data["customer"] = '0EDDD6CC702B0639AE4929A6502EF5EF'; //公司编码customer

        if (!$comCode) {
            //查询快递公司编码
            $url = "http://www.kuaidi100.com/autonumber/auto?num={$num}&key={$key}";
            // 初始url会话
            $ch = curl_init();
            //  设置url传输选项
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 执行url会话
            $data = curl_exec($ch);
            $data = json_decode($data, true);

            if (empty($data) || empty($data[0]['comCode'])) {
                return [];
            } else {
                $comCode = $data[0]['comCode'];
            }
        }

        //查询物流轨迹
        $post_data["param"] = json_encode([
            'com' => $comCode,
            'num' => $num,
        ]);

        $url='http://poll.kuaidi100.com/poll/query.do';
        $post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
        $post_data["sign"] = strtoupper($post_data["sign"]);

        $o="";
        foreach ($post_data as $k=>$v)
        {
            $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
        }

        $post_data=substr($o,0,-1);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $data = str_replace("\"",'"',$result );
        $data = json_decode($data,true);

        if (empty($data['data']))
        {
            return [];
        }

        return $data['data'];
    }

    /**
     * 快递轨迹查询（阿里云市场查询）
     * @param $num
     * @param $comCode
     * @return mixed
     */
    public static function aliExpressQuery($num,$comCode = ''){

        $host = "https://wuliu.market.alicloudapi.com";//api访问链接
        $path = "/kdi";//API访问后缀
        $method = "GET";
        $appcode = "4e60d0c8fb2047a6a9f84c753379ede8";//替换成自己的阿里云appcode
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "no={$num}&type={$comCode}";  //参数写在这里

        $url = $host . $path . "?" . $querys;//url拼接

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $data = curl_exec($curl);
        $data = json_decode($data,true);

        if ($data['status'] != 0)
        {
            return ['code' => 10017,'data' => [] , 'info' => $data['msg']];
        }

        if (empty($data['result']['list']))
        {
            return ['code' => 10017,'data' => [] , 'info' => '获取物流失败！'];
        }

        return ['code' => 10000,'data' => ['list' => $data['result']['list'] , 'expName' => $data['result']['expName']] , '获取物流成功！'];
    }

    /**
     * 邮件发送
     * @param $title    标题
     * @param $content  内容
     * @param string $toEmail   收件人
     * @param array $appendix   附件
     * @return array
     */
    public static function sendEmail($title,$content,$toEmail = '',$appendix = array())
    {
        $config = Db::name('silo_email_config')->column('name,value');
        $mail = new PHPMailer();
        try{
            //服务器配置
            $mail->CharSet ="UTF-8";                     //设定邮件编码
            $mail->SMTPDebug = 0;                        // 调试模式输出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = 'smtp.qiye.aliyun.com';        // SMTP服务器
            $mail->SMTPAuth = true;                      // 允许 SMTP 认证
            $mail->Username = $config['username'];       // SMTP 用户名  即邮箱的用户名
            $mail->Password = $config['password'];       // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->SMTPSecure = 'ssl';                   // 允许 TLS 或者ssl协议
            $mail->Port = 465;                           // 服务器端口 25 或者465 具体要看邮箱服务器支持

            $mail->setFrom($config['formEmail'], $config['nickname']);  //发件人
            $mail->addAddress($toEmail);  // 收件人
            //$mail->addAddress('ellen@example.com');  // 可添加多个收件人
            $mail->addReplyTo($config['formEmail'], $config['nickname']); //回复的时候回复给哪个邮箱 建议和发件人一致
            //$mail->addCC('cc@example.com');                    //抄送
            //$mail->addBCC('bcc@example.com');                    //密送

            //发送附件
            // $mail->addAttachment('../xy.zip');         // 添加附件
            // $mail->addAttachment('../thumb-1.jpg', 'new.jpg');    // 发送附件并且重命名

            if ($appendix)
            {
                foreach ($appendix as $v)
                {
                    $mail->addAttachment($v);
                }
            }

            //Content
            $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject = $title;
            $mail->Body    = $content;
            $mail->AltBody = $content;

            if ($mail->send())
            {
                return ['code' => 10000 , 'info' => '邮件发送成功'];
            }
            return ['code' => 10004 , 'info' => '邮件发送失败'];

        }catch (\Exception $exception)
        {
            return ['code' => 10017 , 'info' => $mail->ErrorInfo];
        }
    }

    /**
     * openssl 加密
     * @param $content 需要加密的内容
     * @return string
     */
    public static function opensslEncryption($content)
    {
        // aes-128=16 aes-192=24 aes-256=32 密钥
        $key = self::OPENSS_KEY;

        // openssl AES 向量长度固定 16 位 这里为兼容建议固定长度为 16 位
        $iv = self::OPENSS_IV;

        $data = openssl_encrypt($content, "AES-128-CBC", $key, 0, $iv);

        return $data;
    }

    /**
     * openssl 解密
     * @param $data 需要解密的内容
     * @return string
     */
    public static function opensslDecrypt($data)
    {
        // aes-128=16 aes-192=24 aes-256=32 密钥
        $key = self::OPENSS_KEY;

        // openssl AES 向量长度固定 16 位 这里为兼容建议固定长度为 16 位
        $iv = self::OPENSS_IV;

        $data = openssl_decrypt($data, "AES-128-CBC", $key, 0, $iv);

        return $data;
    }

    /**
     * 打印输出数据到文件
     * @param mixed $data 输出的数据
     * @param boolean $force 强制替换
     * @param string|null $file 文件名称
     * @param string|null $dir  目录
     */
    public static function printing($data, $force = false, $file = null , $dir = null)
    {
        if (is_null($file)) {
            $file = env('runtime_path') . date('Ymd') . '.txt';
        }else{
            if ($dir && !is_dir($dir)) { //判断目录是否存在 不存在就创建
                mkdir($dir, 0755, true);
            }

            $file = $dir.$file;
        }

        $str = (is_string($data) ? $data : ((is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true))) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }

    /**
     * 检查cookie登录
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function checkCookieLogin()
    {
        $key = 'silo_supplier';

        //判断是否登录信息在cookie中
        $login = cookie('supplierLogin');

        if (!$login)
        {
            return ['code' => 10006 , 'info' => '登录信息不存在！'];
        }

        // 去掉魔术引号
        if (get_magic_quotes_gpc()) {
            $login = stripslashes($login);
        }


        $str = substr($login,0,32); //找出md5加密部分

        $value = substr($login,32); //找出登录信息部分

        //校验
        if (md5($value . $key) == $str) {

            $login  = self::opensslDecrypt($value); //找出登录信息部分

            $login = unserialize($login);

            //判断是否存在
            $info = Db::name('silo_supplier')->where(['supp_username' => $login['username'] , 'supp_secret_pass' => $login['pass']])->find();

            if (!$info)
            {
                return ['code' => 10006 , 'info' => '用户信息不存在！'];
            }

            if ($info['supp_status'] != 1)
            {
                return ['code' => 10005 , 'info' => '您的账号已被禁用！'];
            }

            if ($info['is_deleted'] == 1)
            {
                return ['code' => 10005 , 'info' => '您的账号已被删除！'];
            }

            session('supplier',$info);
            return ['code' => 10000 , 'info' => '账户信息正确，允许登录！'];
        }

        return ['code' => 10017 , 'info' => '密钥验证失败！'];
    }

    /**
     * 生成二维码文件
     * @param $value    二维码内容
     * @param string $filename  二维码文件名称
     * @param bool $domain   是否需要域名
     * @param string $logo  logo地址
     * @param int $matrixPointSize  生成图片大小
     * @param string $errorCorrectionLevel 容错级别
     * @return bool|string
     */
    public static function qrcode($value,$filename = '' ,$domain = true ,$logo = '',$matrixPointSize = 4 , $errorCorrectionLevel = 'H')
    {
        include_once __DIR__ . '/../../../vendor/phpqrcode/phpqrcode.php';

        $codePath = './upload/image/qrcode/';     //二维码存储地址

        if (!is_dir($codePath)) { //判断目录是否存在 不存在就创建
            mkdir($codePath, 0777, true);
        }

        if ($filename)
        {
            $filename = $codePath.$filename.'.png';
        }else{
            $filename = $codePath.microtime().'.png';
        }

        //判断二维码是否存在
        if(file_exists($filename))
        {
            return  $domain ? url('/','',true,true).trim($filename,'./') : $filename;
        }

        //生成二维码图片
        \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);

        $QR = $filename;          //已经生成的原始二维码图

        if (file_exists($logo)) {
            $QR = imagecreatefromstring(file_get_contents($QR));//目标图象连接资源。
            $logo = imagecreatefromstring(file_get_contents($logo));//源图象连接资源。
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 4; //组合之后logo的宽度(占二维码的1/5)
            $scale = $logo_width/$logo_qr_width; //logo的宽度缩放比(本身宽度/组合后的宽度)
            $logo_qr_height = $logo_height/$scale; //组合之后logo的高度
            $from_width = ($QR_width - $logo_qr_width) / 2; //组合之后logo左上角所在坐标点
            //重新组合图片并调整大小
            /*
            * imagecopyresampled() 将一幅图像(源图象)中的一块正方形区域拷贝到另一个图像中
            */
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
            imagedestroy($QR);
            imagedestroy($logo);
        }else{
            $QR = imagecreatefromstring(file_get_contents($QR));
            imagedestroy($QR);
        }
        //imagepng($QR, 'qrcode.png');


        if (file_exists($filename))
        {
            return  $domain ? url('/','',true,true).trim($filename,'./') : $filename;
        }else{
            return false;
        }
    }

    /**
     * 获取图片的Base64编码(不支持url)
     * @param $img_file 传入本地图片地址
     * @return string
     */
    public static function imgToBase64($img_file) {
        $img_base64 = '';
        if (file_exists($img_file)) {
            $app_img_file = $img_file; // 图片路径
            $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等
            $fp = fopen($app_img_file, "r"); // 图片是否可读权限
            if ($fp) {
                $filesize = filesize($app_img_file);
                $content = fread($fp, $filesize);
                $file_content = chunk_split(base64_encode($content)); // base64编码
                switch ($img_info[2]) {           //判读图片类型
                    case 1: $img_type = "gif";
                        break;
                    case 2: $img_type = "jpg";
                        break;
                    case 3: $img_type = "png";
                        break;
                }

                $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//合成图片的base64编码

            }
            fclose($fp);
        }
        return $img_base64; //返回图片的base64
    }

    /**
     * 将html保存为pdf文件
     * @param $html html代码
     * @param $file_name    文件名
     * @param string $path  存储路径
     * @return array
     */
    public static function saveHtmlPDF($html,$file_name,$path = './upload/pdf/in_storage/')
    {
        try {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . trim($path, '.') . $file_name;

            if (is_file($filePath))
            {
                return ['code' => 10000 , 'inof' => '获取pdf成功' , 'data' => $filePath];
            }

            if (!is_dir($path)) { //判断目录是否存在 不存在就创建
                mkdir($path, 0777, true);
            }

            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            // 设置文档信息
            $pdf->SetCreator('乐湛科技');
            $pdf->SetAuthor('简仓');
            $pdf->SetTitle('交货单');
            $pdf->SetSubject('TCPDF Tutorial');

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

//        $pdf->SetKeywords('TCPDF, PDF, PHP');
            // 设置页眉和页脚信息
//        $pdf->SetHeaderData('logo.png', 30, 'lezhan100.com', '乐湛科技',
//            array(0,64,255), array(0,64,128));
//        $pdf->setFooterData(array(0,64,0), array(0,64,128));
            // 设置页眉和页脚字体
//        $pdf->setHeaderFont(Array('stsongstdlight', '', '10'));
//        $pdf->setFooterFont(Array('helvetica', '', '8'));

            // 设置默认等宽字体
            $pdf->SetDefaultMonospacedFont('courier');
            // 设置间距
            $pdf->SetMargins(15, 27, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            // 设置分页
            $pdf->SetAutoPageBreak(TRUE, 25);
            // set image scale factor
            $pdf->setImageScale(1.25);
            // set default font subsetting mode
            $pdf->setFontSubsetting(true);
            //设置字体
            $pdf->SetFont('stsongstdlight', '', 14);
            $pdf->AddPage();


//        $pdf->Write(0,$str1,'', 0, 'L', true, 0, false, false, 0);
            $pdf->writeHTML($html, true, false, true, false, '');
            //保存PDF
            $pdf->Output($filePath,'F');

            return ['code' => 10000 , 'inof' => '生成pdf成功' , 'data' => $filePath];
        }catch (\Exception $exception)
        {
            return ['code' => 10017 , 'inof' => '生成pdf失败'];
        }
    }

    /**
     * 通过excel表导入数据至数据库
     * @param $db   表名
     * @param $file 文件地址
     * @param array $format 导入格式 ['excle位置'=>'字段名']
     * @param int $currentRow 起始行 默认为第二行
     * @return bool
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public static function importDb($db, $file, $format = ['A' => 'filed'], $currentRow = 1)
    {
        $PHPReader = new \PHPExcel_Reader_Excel2007();

        //载入文件
        $PHPExcel = $PHPReader->load($file);

        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet = $PHPExcel->getSheet(0);
        //获取总列数
        $allColumn = $currentSheet->getHighestColumn();

        //获取总行数
        $allRow = $currentSheet->getHighestRow();

        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        $data = array();

        for ($currentRow; $currentRow <= $allRow; $currentRow++) {
            $filed = array();
            foreach ($format as $k => $v) {
                $filed[$v] = $PHPExcel->getActiveSheet()->getCell($k . $currentRow)->getValue();
            }

            $data[] = $filed;
        }

        // 写入数据库操作
        $res = Db::name($db)->insertAll($data);

        if ($res !== false) {
            return true;
        }

        return false;
    }

    /**
     * 导出数据至excel
     * @param $dbData   导出数据
     * @param array $format 导出数据格式 ['excle位置'=>['数据展示名称' , '数据字段名' , '列宽度']]
     * @param array $setinfo    设置信息
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function importExcel($dbData, $format = ['A' => ['name', 'filed', 'width']], $setinfo = ['title' => '', 'subject' => '', 'description' => ''])
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()
            ->setTitle($setinfo['title'])
            ->setSubject($setinfo['subject'])
            ->setDescription($setinfo['description']);

        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        foreach ($format as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue($k . '1', $v[0]);
            $objPHPExcel->getActiveSheet()->getColumnDimension($k)->setWidth($v[2]);//设置列宽度
        }

        foreach ($dbData as $k => $vo) {
            $num = $k + 2;
            foreach ($format as $kk => $vv) {
                $objPHPExcel->getActiveSheet()->setCellValue($kk . $num, $vo[$vv[1]]);
                //$objPHPExcel->getActiveSheet()->getStyle($kk . $num)->getAlignment()->setWrapText(true);
            }
        }
        ob_end_clean();
        $fileName = $setinfo['title'] . date("Y-m-d H-i-s", time()) . ".xlsx";
        $fileName = iconv("utf-8", "gb2312", $fileName);
        //将输出重定向到一个客户端web浏览器(Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * 文件下载
     * @param $filename 文件路径
     * @param string $name 文件名称
     * @param int $size 文件大小
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function downloadFile($filename,$name = '',$size = 0) {

        //获取文件的扩展名
        $allowDownExt = explode(',',sysconf('storage_local_exts'));

        //获取文件信息
        $fileExt = pathinfo($filename);

        //检测文件类型是否允许下载
        if(!in_array($fileExt['extension'], $allowDownExt)) {
            return false;
        }

        //设置脚本的最大执行时间，设置为0则无时间限制
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        //通过header()发送头信息
        //因为不知道文件是什么类型的，告诉浏览器输出的是字节流
        header('content-type:application/octet-stream');

        //告诉浏览器返回的文件大小类型是字节
        header('Accept-Ranges:bytes');

        //获得文件大小
        $filesize = $size;

        //告诉浏览器返回的文件大小
        header('Accept-Length:'.$filesize);
        //告诉浏览器文件作为附件处理并且设定最终下载完成的文件名称
        header('content-disposition:attachment;filename='.$name);

        //针对大文件，规定每次读取文件的字节数为8192字节，直接输出数据
        $read_buffer = 8192;
        $handle = fopen($filename, 'rb');
        //总的缓冲的字节数
        $sum_buffer = 0;
        //只要没到文件尾，就一直读取
        while(!feof($handle) && $sum_buffer<$filesize) {
            echo fread($handle,$read_buffer);
            $sum_buffer += $read_buffer;
        }

        //关闭句柄
        fclose($handle);
        exit;
    }
    /**
     * 单图上传
     * @param $file 文件
     * @param $path 存在路径
     * @param string $filename  文件名称
     * @return array
     */
    public static function uploadOne($file,$path,$filename = '')
    {
        try{
            if (!is_dir($path)) { //判断目录是否存在 不存在就创建
                mkdir($path, 0777, true);
            }

            $filename = $filename ? $filename : true;

            $info = $file->validate(['size'=>5000000,'ext'=>'jpg,png,jpeg'])->move($path,$filename);

            if ($info) {
                $url = url('/','',true,true).ltrim($path,'./') .$info->getSaveName();
            }else{
                return ['code' => 0 , 'info' => '文件上传失败' , 'data' => []];
            }

            return ['code' => 1 , 'info' => '文件上传成功' , 'data' => ['url' => trim($url,'.')]];
        }catch (\Exception $exception)
        {
            return ['code' => 0 , 'info' => '文件上传失败，请稍后再试！' , 'data' => []];
        }
    }

    /**
     * 多图上传
     * @param $files    文件
     * @param $path 存在路径
     * @param string $filename  文件名称
     * @return array
     */
    public static function uploadMultiple($files,$path,$filename = '')
    {
        try{
            if (!is_dir($path)) { //判断目录是否存在 不存在就创建
                mkdir($path, 0777, true);
            }

            $url = '';

            foreach($files as $file)
            {
                $filename = $filename ? $filename : true;
                $info = $file->validate(['size'=>5000000,'ext'=>'jpg,png,jpeg'])->move($path,$filename);

                if ($info) {
                    $url .= trim(url('/','',true,true).ltrim($path,'./') .$info->getSaveName().'|','.');
                }else{
                    return ['code' => 0 , 'info' => '文件上传失败' , 'data' => []];
                }

            }

            return ['code' => 1 , 'info' => '文件上传成功' , 'data' => ['url' => trim($url,'|')]];
        }catch (\Exception $exception)
        {
            return ['code' => 0 , 'info' => '文件上传失败，请稍后再试！' , 'data' => []];
        }
    }

    /**
     * 七牛云解密url
     * @param $url  原始url地址
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function decryptUrl($url)
    {
        $auth = new Auth(sysconf('storage_qiniu_access_key'),sysconf('storage_qiniu_secret_key'));
        // 对链接进行签名
        $signedUrl = $auth->privateDownloadUrl($url);
        return $signedUrl;
    }

    /**
     * PC端微信登录url拼接
     * combineURL
     * 拼接url
     * @param string $baseURL   基于的url
     * @param array  $keysArr   参数列表数组
     * @return string           返回拼接的url
     */
    public static function combineURL($baseURL,$keysArr){
        $combined = $baseURL."?";
        $valueArr = array();

        foreach($keysArr as $key => $val){
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&",$valueArr);
        $combined .= ($keyStr);

        return $combined;
    }

    /**
     * 服务器通过get请求获得内容
     * @param $url 请求的url,拼接后的
     * @return bool|mixed|string  请求返回的内容
     * @throws \Exception
     */
    public static function get_contents($url){

        if (ini_get("allow_url_fopen") == "1") {
            $response = file_get_contents($url);
        }else{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response =  curl_exec($ch);
            curl_close($ch);
        }

        //-------请求为空
        if(empty($response)){
            throw new \Exception('<h2>可能是服务器无法请求https协议</h2>可能未开启curl支持,请尝试开启curl支持，重启web服务器，如果问题仍未解决，请联系我们');
        }

        return $response;
    }

    /**
     * get方式请求资源
     * @param $url   基于的baseUrl
     * @param $keysArr  参数列表数组
     * @return string 返回的资源内容
     * @throws \Exception
     */
    public static function get($url, $keysArr){
        $combined = self::combineURL($url, $keysArr);
        return self::get_contents($combined);
    }

    /**
     * post方式请求资源
     * @param string $url       基于的baseUrl
     * @param array $keysArr    请求的参数列表
     * @param int $flag         标志位
     * @return string           返回的资源内容
     */
    public static function post($url, $keysArr, $flag = 0){

        $ch = curl_init();
        if(! $flag) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr);
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);

        curl_close($ch);
        return $ret;
    }
}