<?php

namespace App\Http\Library\YunChuang;
class ZmHttpUtil {

    public static function curlPost( $url, $data ) {
        $ch = curl_init();

        // 设置不验证ssl证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = FALSE; //是否返回响应头信息
        $params[CURLOPT_SSL_VERIFYPEER] = false;
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_POSTFIELDS] = $data;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }


    public static function getJson($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output);
    }

    public static function https_request($url, $data = null,$time_out=60,$out_level="s",$headers=array())    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if($out_level=="s")
        {
            //超时以秒设置
            curl_setopt($curl, CURLOPT_TIMEOUT,$time_out);//设置超时时间
        }elseif ($out_level=="ms")
        {
            curl_setopt($curl, CURLOPT_TIMEOUT_MS,$time_out);  //超时毫秒，curl 7.16.2中被加入。从PHP 5.2.3起可使用
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if($headers)
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);//如果有header头 就发送header头信息
        }
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

	public static function https_request_async($url, $data = null,$time_out=60,$out_level="s",$headers=array())    {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_NOSIGNAL, true); // 开启异步选项
		curl_setopt($curl, CURLOPT_TIMEOUT_MS, 200); //超时时间200毫秒
		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		if($out_level=="s")
		{
			//超时以秒设置
			curl_setopt($curl, CURLOPT_TIMEOUT,$time_out);//设置超时时间
		}elseif ($out_level=="ms")
		{
			curl_setopt($curl, CURLOPT_TIMEOUT_MS,$time_out);  //超时毫秒，curl 7.16.2中被加入。从PHP 5.2.3起可使用
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if($headers)
		{
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);//如果有header头 就发送header头信息
		}
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}


    public static function get( $url ) {
        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Content-type:application/x-www-form-urlencoded;charset=UTF-8",
                'timeout' => 300 // 超时时间（单位:s）
            ),
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			)
        );
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
        return $result;
    }

	public static function get2( $url, $header ) {
		$options = array(
			'http' => array(
				'method' => 'GET',
				'header' => $header,
				'timeout' => 300 // 超时时间（单位:s）
			)
		);
		$context = stream_context_create( $options );
		$result = file_get_contents( $url, false, $context );
		return $result;
	}


    public static function send_post( $url, $post_data ) {
//        $postdata = http_build_query($post_data);
//        echo $postdata;
//        echo json_encode( $post_data );
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type:application/x-www-form-urlencoded;charset=UTF-8",
                'content' => http_build_query($post_data),
                'timeout' => 300 // 超时时间（单位:s）
            ),
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            )
        );
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
        return $result;
    }

	public static function sendPost2( $url, $header, $data ) {
//        $postdata = http_build_query($post_data);
//        echo $postdata;
//        echo json_encode( $post_data );
		$options = array(
			'http' => array(
				'method' => 'POST',
				'header' => $header,
				'content' => http_build_query( $data ),
				'timeout' => 300 // 超时时间（单位:s）
			),
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			)
		);
		$context = stream_context_create( $options );
		$result = file_get_contents( $url, false, $context );
		return $result;
	}

	public static function sendPost( $url, $header, $post_data ) {
//        $postdata = http_build_query($post_data);
//        echo $postdata;
//        echo json_encode( $post_data );
		$options = array(
			'http' => array(
				'method' => 'POST',
				'header' => "Content-type:application/json;charset=UTF-8\r\n" . $header,
				'content' => json_encode( $post_data ),
				'timeout' => 300 // 超时时间（单位:s）
			),
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			)
		);
		$context = stream_context_create( $options );
		$result = file_get_contents( $url, false, $context );
		return $result;
	}

    public static function send_delete( $url ) {
//        $postdata = http_build_query($post_data);
//        echo $postdata;
//        echo json_encode( $post_data );
        $options = array(
            'http' => array(
                'method' => 'DELETE',
                'header' => "api-key:" . ConstUtil::IotPlatformApiKeyChinaMobileXiaoWeiAnQuan,
//                'header' => "api-key:" . ConstUtil::IotPlatformApiKeyChinaMobilePingAnSuiYue,
//                'header' => "api-key:" . ConstUtil::IotPlatformApiKeyChinaMobile,
//                'content' => http_build_query($post_data),
                'timeout' => 300 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
        return $result;
    }

	public static function send_delete2( $url, $api_key ) {
//        $postdata = http_build_query($post_data);
//        echo $postdata;
//        echo json_encode( $post_data );
		$options = array(
			'http' => array(
				'method' => 'DELETE',
				'header' => "api-key:" . $api_key,
//                'content' => http_build_query($post_data),
				'timeout' => 300 // 超时时间（单位:s）
			)
		);
		$context = stream_context_create( $options );
		$result = file_get_contents( $url, false, $context );
		return $result;
	}

    public static function send_post_json( $url, $post_data, $token = "" ) {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type:application/json;charset=UTF-8",
                'content' => json_encode( $post_data ),
                'timeout' => 300 // 超时时间（单位:s）
            ),
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			)
        );
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
        return $result;
    }

    public static function send_post_json2( $url, $post_data, $token = "" ) {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type:application/json;charset=UTF-8",
                'content' => json_encode( $post_data ),
                'timeout' => 300 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
        return $result;
    }

	public static function curlGetAsync( $url, $data=null ) {
		$curl = curl_init();
//		if($isProxy){   //是否设置代理
//			$proxy = "127.0.0.1";   //代理IP
//			$proxyport = "8001";   //代理端口
//			curl_setopt($curl, CURLOPT_PROXY, $proxy.":".$proxyport);
//		}

		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, FALSE );

		curl_setopt( $curl, CURLOPT_NOSIGNAL, true );    //注意，毫秒超时一定要设置这个
		curl_setopt( $curl, CURLOPT_TIMEOUT_MS, 200 ); //超时时间200毫秒
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 ); //设置有返回值，0，直接显示
		curl_setopt( $curl, CURLOPT_POST, 1 );//post方法请求
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $data);//post请求发送的数据包

//		if(!empty($data)){
//			curl_setopt($curl, CURLOPT_POST, 1);
//			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
//					"cache-control: no-cache",
//					"content-type: application/json",)
//			);
//		}

		$output = curl_exec( $curl );
		$error = curl_errno( $curl );
		curl_close($curl);
	}

}
