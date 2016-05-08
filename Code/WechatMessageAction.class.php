<?php
/**
 * 微信客服回复类
 */
class wechatMessageAction extends CommonAction{
    /*
     * 消息回复
     * param:message_id 消息ID
     * param:re_type 回复类型 1:文本 2:图片
     * param:re_content 文本回复内容
     * param:uploadImg 图片流
     */
    public function messageReply(){
        $message_id = $_REQUEST['message_id'];
        $re_type = $_REQUEST['re_type'];
        //获取该微信公众号的access_token
        $access_token = $this->getAccessToken('该公众号的AppID','该公众号的AppSecret');
        //调用微信客服回复接口
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
        if($re_type==1){
            $re_content = $_REQUEST['re_content'];
            $json = '{
                        "touser":"'.$find['openid'].'",
                        "msgtype":"text",
                        "text":
                        {
                            "content":"'.$re_content.'"
                        }
                    }';
        }elseif($re_type==2){
            //上传图片
            $file = $this->uploadImg();
            //新增微信图片素材
            $media_id = $this->add_material($file['data'],$access_token);
            if(!$media_id){
                $re['status'] = 0;
                $re['data'] = '新增微信图片素材失败';
                echo json_encode($re);exit;
            }
            $json = '{
                        "touser":"'.$find['openid'].'",
                        "msgtype":"image",
                        "image":
                        {
                          "media_id":"'.$media_id.'"
                        }
                    }';
        }
        //curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST,1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result,true);
        if($result['status']==0){
                $re['status'] = 1;
                echo json_encode($re);exit;
        }else{
            $re['status'] = 0;
            $re['data'] = '回复失败';
            echo json_encode($re);exit;
        }
    }

    /*
     * 新增图片素材
     */
    public function add_material($file,$access_token){
        $url="https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=".$access_token."&type=image";
        $real_path=new CURLFile($file);
        $data= array("media"=>$real_path);
        //curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST,1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result,true);
        if($result['media_id']){
            return $result['media_id'];
        }else{
            return false;
        }

    }

	/*
     * 获取普通access_token (自定义菜单等用到)
     */
    private function getAccessToken($AppID,$AppSecret){
        //先拿缓存
        $redisObj = $this->returnRedisObj();
        $cacheKey = 'WechatPlatform_accessToken_'.$AppID;
        $re = $redisObj ->get($cacheKey);
        if($re){
            return $re;
        }else{
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$AppID.'&secret='.$AppSecret;
            //curl
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL,$url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_GET, 1);
            $result = curl_exec($curl);
            curl_close($curl);
            //设置缓存
            $redisObj->set($cacheKey,$result,7200);
            $result = json_decode($result,true);
            return $result;
        }
    }

?>