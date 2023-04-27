<?php
/**
 * @author: FrankHong
 * @datetime: 2016/12/2 20:36
 * @filename: OrderfController.class.php
 * @description: 运营中心订单模块
 * @note: 
 * 
 */

namespace Ucenter\Controller;


class FlowfController extends CommonController
{


    
    /**
     * @functionname: opt_user_status
     * @author: FrankHong
     * @date: 2016-12-02 20:28:24
     * @description: 操作用户的状态
     * @note:
     */
    public function money_flow() 
    {
       $MoneyFlow               = M('MoneyFlow');
       $userinfo                = M('userinfo');
       $userinfoRelationshipObj = M('UserinfoRelationship');
       $bankinfo                = M('bankinfo');

       $start_time = urldecode(I('get.start_time'));
       $end_time   = urldecode(I('get.end_time'));
       $type       = I('get.type');
       $operator   = trim(I('get.operator'));


        $userIdStr      = NOW_UID;

        //时间筛选
         if($start_time && $end_time) {
            $starttime = strtotime($start_time);
            $endtime   = strtotime($end_time);
            $map['dateline'] = array('between',array($starttime,$endtime));
            $time['start_time'] = $start_time;
            $time['end_time']   = $end_time;
            $this->assign('time',$time);
         }

         //资金变动筛选
         if($type) {
            $map['type'] = $type;
            $this->assign('type',$type);
         }

         //操作人筛选
         if($operator) {
            $map['op_id'] = $operator;
            $this->assign('operator',$operator);
         }
        
        //start
        $map['uid'] = array('in',$userIdStr);

        $count      = $MoneyFlow->where($map)->count();
        $pageObj    = new \Think\Pageace($count, PAGE_SIZE);
        $pageShow   = $pageObj->show();
       
        $Flow       = $MoneyFlow->where($map)->order('id  desc')->limit($pageObj->firstRow, $pageObj->listRows)->select();
        $Flow_money = $MoneyFlow->where($map)->order('id  desc')->select();
        
        $FlowArr  = array();
        $FlowArr1 = array();
        foreach ($Flow as $key => $value) {
            array_push($FlowArr, $value['uid']);
            array_push($FlowArr1, $value['op_id']);
        }
        $FlowId  = implode(',',array_unique($FlowArr));
        $FlowId1 = implode(',',array_unique($FlowArr1));

        //查询购买人信息
        $info = $userinfo->where('uid in('.$FlowId.')')->select();
        foreach ($info as $key => $value) {
            $info[$value['uid']] = $value;
        }

        //查询用户银行卡信息
        $bank = $bankinfo->where('uid in('.$FlowId.')')->select();
        foreach ($bank as $key => $value) {
            $bank[$value['uid']] = $value;
        }

        //查询操作人信息
        $info1 = $userinfo->where('uid in('.$FlowId1.')')->select();
        foreach ($info1 as $key => $value) {
            $info1[$value['uid']] = $value;
        }

        //查询操作人用户银行卡信息
        $bank1 = $bankinfo->where('uid in('.$FlowId1.')')->select();
        foreach ($bank1 as $key => $value) {
            $bank1[$value['uid']] = $value;
        }


        foreach ($Flow as $key => $value) {
            $Flow[$key]['busername']          = $bank[$value['uid']]['busername'];
            $Flow[$key]['username']           = $info[$value['uid']]['username'];
            $Flow[$key]['utel']               = $info[$value['uid']]['utel'];

            $Flow[$key]['account']            = substr($value['note'],strrpos($value['note'],'[')+1);
            $Flow[$key]['account']            = preg_replace("/]/", "",$Flow[$key]['account']);

            $Flow[$key]['operator']           = $info1[$value['op_id']]['username'];  //操作人
            $Flow[$key]['operator_busername'] = $bank1[$value['op_id']]['busername'];  //操作人

        }

        foreach ($Flow_money as $key => $value) {
                $Flow_money[$key]['account']  = substr($value['note'],strrpos($value['note'],'[')+1);
                $sum += preg_replace("/]元/", "",$Flow_money[$key]['account']);
        }
        
        $nowStart   = $pageObj->firstRow / PAGE_SIZE * PAGE_SIZE + 1;
        $nowEnd     = ($pageObj->firstRow / PAGE_SIZE + 1) * PAGE_SIZE;

        $this->assign('totalCount', $count);
        $this->assign('nowStart', $nowStart);
        $this->assign('nowEnd', $nowEnd);
        $this->assign('pageShow', $pageShow);
        $this->assign('flow',$Flow);
        $this->assign('agentinfoRs',$agentinfoRs);  //经纪人
        $this->assign('sum',$sum);
        $this->assign('info',M('userinfo')->where(array('otype' =>3))->select());
        $this->display();
    }

    /**
     * @functionname: opt_user_status
     * @author: FrankHong
     * @date: 2016-12-02 20:28:24
     * @description: 用户资金流水导出excel
     * @note:
     */
    public function flow_daochu()
    {
        
       $MoneyFlow               = M('MoneyFlow');
       $userinfo                = M('userinfo');
       $userinfoRelationshipObj = M('UserinfoRelationship');
       $bankinfo                = M('bankinfo');

       $start_time = urldecode(I('get.start_time'));
       $end_time   = urldecode(I('get.end_time'));
       $type       = I('get.type');
       $operator   = trim(I('get.operator'));

       $userIdStr      = NOW_UID;


        //时间筛选
         if($start_time && $end_time) {
            $starttime = strtotime($start_time);
            $endtime   = strtotime($end_time);
            $map['dateline'] = array('between',array($starttime,$endtime));
            $time['start_time'] = $start_time;
            $time['end_time']   = $end_time;
            $this->assign('time',$time);
         }

         //用户类型筛选
         if($type) {
            $map['type'] = $type;
         }

       //操作人筛选
         if($operator) {
            $map['op_id'] = $operator;
         }
        
        //start
        $map['uid'] = array('in',$userIdStr);
       
        $Flow       = $MoneyFlow->where($map)->order('dateline  desc')->select();
        
        $FlowArr  = array();
        $FlowArr1 = array();
        foreach ($Flow as $key => $value) {
            array_push($FlowArr, $value['uid']);
            array_push($FlowArr1, $value['op_id']);
        }
        $FlowId  = implode(',',array_unique($FlowArr));
        $FlowId1 = implode(',',array_unique($FlowArr1));

        //查询购买人信息
        $info = $userinfo->where('uid in('.$FlowId.')')->select();
        foreach ($info as $key => $value) {
            $info[$value['uid']] = $value;
        }

        //查询用户银行卡信息
        $bank = $bankinfo->where('uid in('.$FlowId.')')->select();
        foreach ($bank as $key => $value) {
            $bank[$value['uid']] = $value;
        }

        //查询操作人信息
        $info1 = $userinfo->where('uid in('.$FlowId1.')')->select();
        foreach ($info1 as $key => $value) {
            $info1[$value['uid']] = $value;
        }

        //查询操作人用户银行卡信息
        $bank1 = $bankinfo->where('uid in('.$FlowId1.')')->select();
        foreach ($bank1 as $key => $value) {
            $bank1[$value['uid']] = $value;
        }


        foreach ($Flow as $key => $value) {
            $Flow[$key]['busername']          = $bank[$value['uid']]['busername'];
            $Flow[$key]['username']           = $info[$value['uid']]['username'];
            $Flow[$key]['utel']               = $info[$value['uid']]['utel'];

            $Flow[$key]['account']            = substr($value['note'],strrpos($value['note'],'[')+1);
            $Flow[$key]['account']            = preg_replace("/]/", "",$Flow[$key]['account']);

            $Flow[$key]['operator']           = $info1[$value['op_id']]['username'];  //操作人
            $Flow[$key]['operator_busername'] = $bank1[$value['op_id']]['busername'];  //操作人
        }


        $data[0] = array('编号','用户姓名','手机号','资金变动描述','变动金额','操作人','操作时间');
        foreach ($Flow as $key => $value) {
            $name          = !empty($value['busername']) ? $value['busername'] : $value['username'];
            $operator_name = !empty($value['operator_busername']) ? $value['operator_busername'] : $value['operator'];

            $data[$key+1][] = $value['id'];
            $data[$key+1][] = $name;
            $data[$key+1][] = $value['utel'];
            $data[$key+1][] = $value['note'];
            $data[$key+1][] = $value['account'];
            $data[$key+1][] = $operator_name;
            $data[$key+1][] = date('Y-m-d H:i:s',$value['dateline']);  
        }
        $name='用户资金流水';
        $this->push($data,$name);
    }

     /**
      * 充值记录
      */
     public function recharge()
     {
       $userinfo                = M('userinfo');
       $balance                 = M('balance');
       $bankinfo                = M('bankinfo');

       $start_time = urldecode(trim(I('get.start_time')));
       $end_time   = urldecode(trim(I('get.end_time')));
       $status     = trim(I('get.status'));


        //时间筛选
         if($start_time && $end_time) {
            $starttime = strtotime($start_time);
            $endtime   = strtotime($end_time);
            $map['bptime'] = array('between',array($starttime,$endtime));
            $time['start_time'] = $start_time;
            $time['end_time']   = $end_time;
            $this->assign('time',$time);
         }

         //充值状态
         if($status)
         {
            switch ($status) {
                case 1:
                    $map['status'] = 1;
                    $map['isverified'] = 1;
                    break;
                case 2:
                    $map['status'] = 1;
                    $map['isverified'] = 0;
                    break;
                default:
                    $map['status'] = 0;
                    $map['isverified'] = 0;
                    break;
            }
            $this->assign('status',$status);
         }

        
        $map['uid'] = NOW_UID;
        $map['b_type'] = 1;

        $count      = $balance->where($map)->count();
        $pageObj    = new \Think\Pageace($count, PAGE_SIZE);
        $pageShow   = $pageObj->show();
       
        $balance_data = $balance->where($map)->order('bpid  desc')->limit($pageObj->firstRow, $pageObj->listRows)->select();

        $balanceArr = array();
        $pay_type   = array();
        foreach ($balance_data as $key => $value) {
            array_push($balanceArr,$value['uid']);
            array_push($pay_type,$value['pay_type']);
        }
        $balanceId = implode(',',array_unique($balanceArr));
        $payTypeId = implode(',',array_unique($pay_type));

        //查询用户银行卡信息
        $bank = $bankinfo->where('uid in('.$balanceId.')')->select();
        foreach ($bank as $key => $value) {
            $bank[$value['uid']] = $value;
        }

        //查询用户信息
        $info = $userinfo->where('uid in('.$balanceId.')')->select();
        foreach ($info as $key => $value) {
            $info[$value['uid']] = $value;
        }

        //查询用户充值渠道
        $pay = M()->table('dict_pay_type')->where('id in('.$payTypeId.')')->select();
        foreach ($pay as $key => $value) {
            $pay[$value['id']] = $value;
        }

        foreach ($balance_data as $key => $value) {
            $balance_data[$key]['busername']          = !empty($bank[$value['uid']]['busername']) ? $bank[$value['uid']]['busername'] : $info[$value['uid']]['username'];
            $balance_data[$key]['pay_type']           = $pay[$value['pay_type']]['pay_name'];
        }

        //查询用户总充值金额
        $map['isverified'] = 1;
        $map['status'] = 1;
        $bpprice = $balance->where($map)->sum('bpprice');
        
        $nowStart   = $pageObj->firstRow / PAGE_SIZE * PAGE_SIZE + 1;
        $nowEnd     = ($pageObj->firstRow / PAGE_SIZE + 1) * PAGE_SIZE;

        $this->assign('totalCount', $count);
        $this->assign('nowStart', $nowStart);
        $this->assign('nowEnd', $nowEnd);
        $this->assign('pageShow', $pageShow);
        $this->assign('balance',$balance_data);
        $this->assign('bpprice',$bpprice);
        $this->display();
     }


     /**
      * 充值记录
      */
     public function recharge_daochu()
     {
       $userinfo                = M('userinfo');
       $balance                 = M('balance');
       $bankinfo                = M('bankinfo');

       $start_time = urldecode(trim(I('get.start_time')));
       $end_time   = urldecode(trim(I('get.end_time')));
       $status     = trim(I('get.status'));


        //时间筛选
         if($start_time && $end_time) {
            $starttime = strtotime($start_time);
            $endtime   = strtotime($end_time);
            $map['bptime'] = array('between',array($starttime,$endtime));
            $time['start_time'] = $start_time;
            $time['end_time']   = $end_time;
            $this->assign('time',$time);
         }

        //充值状态
         if($status)
         {
            switch ($status) {
                case 1:
                    $map['status'] = 1;
                    $map['isverified'] = 1;
                    break;
                case 2:
                    $map['status'] = 1;
                    $map['isverified'] = 0;
                    break;
                default:
                    $map['status'] = 0;
                    $map['isverified'] = 0;
                    break;
            }
            $this->assign('status',$status);
         }

        
        $map['uid'] = NOW_UID;
        $map['b_type'] = 1;
       
        $balance_data = $balance->where($map)->order('bpid  desc')->select();

        $balanceArr = array();
        $pay_type   = array();
        foreach ($balance_data as $key => $value) {
            array_push($balanceArr,$value['uid']);
            array_push($pay_type,$value['pay_type']);
        }
        $balanceId = implode(',',array_unique($balanceArr));
        $payTypeId = implode(',',array_unique($pay_type));

        //查询用户银行卡信息
        $bank = $bankinfo->where('uid in('.$balanceId.')')->select();
        foreach ($bank as $key => $value) {
            $bank[$value['uid']] = $value;
        }

        //查询用户信息
        $info = $userinfo->where('uid in('.$balanceId.')')->select();
        foreach ($info as $key => $value) {
            $info[$value['uid']] = $value;
        }

        //查询用户充值渠道
        $pay = M()->table('dict_pay_type')->where('id in('.$payTypeId.')')->select();
        foreach ($pay as $key => $value) {
            $pay[$value['id']] = $value;
        }

        foreach ($balance_data as $key => $value) {
            $balance_data[$key]['busername']          = !empty($bank[$value['uid']]['busername']) ? $bank[$value['uid']]['busername'] : $info[$value['uid']]['username'];
            $balance_data[$key]['pay_type']           = $pay[$value['pay_type']]['pay_name'];
        }
        
        $data[0] = array('编号','用户昵称','充值时间','充值金额','账户余额','状态','充值渠道');
        foreach ($balance_data as $key => $value) {

            $status = $value['status'] == 1 ? ($value['isverified'] == 1 ? '充值完成' : '充值失败') : ($value['isverified'] == 0 ? '未处理' : '不存在');
            $data[$key+1][] = $value['bpid'];
            $data[$key+1][] = $value['busername'];
            $data[$key+1][] = date('Y-m-d H:i:s',$value['bptime']);
            $data[$key+1][] = $value['bpprice'];
            $data[$key+1][] = $value['shibpprice'];
            $data[$key+1][] = $status;
            $data[$key+1][] = $value['pay_type'];
        }
        $name='用户充值';
        $this->push($data,$name);
     }

    /**
     * 提现记录
     */
    public function withdrawal()
    {   
       $userinfo                = M('userinfo');
       $balance                 = M('balance');
       $bankinfo                = M('bankinfo');

       $start_time = urldecode(trim(I('get.start_time')));
       $end_time   = urldecode(trim(I('get.end_time')));
       $status     = trim(I('get.status'));


        //时间筛选
         if($start_time && $end_time) {
            $starttime = strtotime($start_time);
            $endtime   = strtotime($end_time);
            $map['bptime'] = array('between',array($starttime,$endtime));
            $time['start_time'] = $start_time;
            $time['end_time']   = $end_time;
            $this->assign('time',$time);
         }

         //提现状态
         if($status)
         {
            switch ($status) {
                case 1:
                    $map['status'] = 1;
                    $map['isverified'] = 1;
                    break;
                case 2:
                    $map['status'] = 0;
                    $map['isverified'] = 0;
                    $map['cltime']  = array('exp','is not null');
                    break;
                default:
                    $map['cltime']  = array('exp','is null');
                    break;
            }
            $this->assign('status',$status);
         }

        
        $map['uid'] = NOW_UID;
        $map['b_type'] = 2;

        $count      = $balance->where($map)->count();
        $pageObj    = new \Think\Pageace($count, PAGE_SIZE);
        $pageShow   = $pageObj->show();
       
        $balance_data = $balance->where($map)->order('bpid  desc')->limit($pageObj->firstRow, $pageObj->listRows)->select();
        $balanceArr = array();
        foreach ($balance_data as $key => $value) {
            array_push($balanceArr,$value['uid']);
        }
        $balanceId = implode(',',array_unique($balanceArr));

        //查询用户银行卡信息
        $bank = $bankinfo->where('uid in('.$balanceId.')')->select();
        foreach ($bank as $key => $value) {
            $bank[$value['uid']] = $value;
        }

        //查询用户信息
        $info = $userinfo->where('uid in('.$balanceId.')')->select();
        foreach ($info as $key => $value) {
            $info[$value['uid']] = $value;
        }


        foreach ($balance_data as $key => $value) {
            $balance_data[$key]['busername']          = !empty($bank[$value['uid']]['busername']) ? $bank[$value['uid']]['busername'] : $info[$value['uid']]['username'];
        }

        //查询用户总提现金额
        $map['isverified'] = 1;
        $map['status'] = 1;
        $bpprice = $balance->where($map)->sum('bpprice');
        
        $nowStart   = $pageObj->firstRow / PAGE_SIZE * PAGE_SIZE + 1;
        $nowEnd     = ($pageObj->firstRow / PAGE_SIZE + 1) * PAGE_SIZE;

        $this->assign('totalCount', $count);
        $this->assign('nowStart', $nowStart);
        $this->assign('nowEnd', $nowEnd);
        $this->assign('pageShow', $pageShow);
        $this->assign('balance',$balance_data);
        $this->assign('bpprice',$bpprice);
        $this->display();
    }


    /**
     * 提现记录
     */
    public function withdrawal_daochu()
    {   
       $userinfo                = M('userinfo');
       $balance                 = M('balance');
       $bankinfo                = M('bankinfo');

       $start_time = urldecode(trim(I('get.start_time')));
       $end_time   = urldecode(trim(I('get.end_time')));
       $status     = trim(I('get.status'));


        //时间筛选
         if($start_time && $end_time) {
            $starttime = strtotime($start_time);
            $endtime   = strtotime($end_time);
            $map['bptime'] = array('between',array($starttime,$endtime));
            $time['start_time'] = $start_time;
            $time['end_time']   = $end_time;
            $this->assign('time',$time);
         }

         //提现状态
         if($status)
         {
            switch ($status) {
                case 1:
                    $map['status'] = 1;
                    $map['isverified'] = 1;
                    break;
                case 2:
                    $map['status'] = 0;
                    $map['isverified'] = 0;
                    $map['cltime']  = array('exp','is not null');
                    break;
                default:
                    $map['cltime']  = array('exp','is null');
                    break;
            }
            $this->assign('status',$status);
         }

        
        $map['uid'] = NOW_UID;
        $map['b_type'] = 2;
       
        $balance_data = $balance->where($map)->order('bpid  desc')->select();
        $balanceArr = array();
        foreach ($balance_data as $key => $value) {
            array_push($balanceArr,$value['uid']);
        }
        $balanceId = implode(',',array_unique($balanceArr));

        //查询用户银行卡信息
        $bank = $bankinfo->where('uid in('.$balanceId.')')->select();
        foreach ($bank as $key => $value) {
            $bank[$value['uid']] = $value;
        }

        //查询用户信息
        $info = $userinfo->where('uid in('.$balanceId.')')->select();
        foreach ($info as $key => $value) {
            $info[$value['uid']] = $value;
        }


        foreach ($balance_data as $key => $value) {
            $balance_data[$key]['busername']          = !empty($bank[$value['uid']]['busername']) ? $bank[$value['uid']]['busername'] : $info[$value['uid']]['username'];
        }
        

        $data[0] = array('编号','用户昵称','提现时间','处理时间','提现金额','账户余额','状态','原因');
        foreach ($balance_data as $key => $value) {
            $status = !empty($value['cltime']) ? ($value['isverified'] == 1 && $value['status'] == 1 ? '已通过' : '拒绝申请') : ('待处理');
            $data[$key+1][] = $value['bpid'];
            $data[$key+1][] = $value['busername'];
            $data[$key+1][] = date('Y-m-d H:i:s',$value['bptime']);
            $data[$key+1][] = date('Y-m-d H:i:s',$value['cltime']);
            $data[$key+1][] = $value['bpprice'];
            $data[$key+1][] = $value['shibpprice'];
            $data[$key+1][] = $status;
            $data[$key+1][] = $value['remarks'];
        }
        $name='用户提现记录';
        $this->push($data,$name);
    }

    private function push($data,$name){
        import("Excel.class.php");
        $excel = new Excel();
        $excel->download($data,$name);
    }



    private function get_username($uid = 0) {
         
         $info = M("userinfo a")->field('a.uid,a.username,b.busername')->join('left join wp_bankinfo as b on a.uid = b.uid')->where(array('a.uid'=> $uid))->find();

         $info['username'] = !empty($info['busername']) ? $info['busername'] : $info['username'];

         return $info ? $info : null;
    }

    public function ajax_get_brokers(){
        if(IS_AJAX){
            $userobj         = M('userinfo a');
            $relationshipobj = M('userinfo_relationship');
            
            $parent_id = I('get.parent_id',0,'intval');
            
            if($parent_id < 1) $this->AjaxReturn(array('msg'=>'父级id不存在','status'=>0));
            $ids_arr = $relationshipobj->field('user_id')->where(array('parent_user_id'=>$parent_id))->select();
            $ids='';
            
            if($ids_arr){
                foreach($ids_arr as $v){
                    if(!empty($ids)){
                        $ids .=','.$v['user_id'];
                    }else{
                        $ids = $v['user_id'];
                    }
                }
            }
            $where['a.uid']=array('IN',$ids);
            $res = $userobj->field('a.uid,a.username,b.busername')->join('left join wp_bankinfo as b on a.uid = b.uid')->where($where)->order('uid DESC')->select();
            foreach ($res as $key => $value) {
                $res[$key]['username'] = !empty($value['busername']) ? $value['busername'] : $value['username'];
            }

            $data=array('msg'=>'成功','status'=>1,'data'=>$res);
            $this->AjaxReturn($data);
        }
            $this->error('您访问的页面不存在','index/index');
    }

}