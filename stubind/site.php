<?php
/**
 * 学生信息绑定模块
 *
 * @author yanson
 */
defined('IN_IA') or exit('Access Denied');

class StuBindModuleSite extends WeModuleSite {
	public $name = 'stubind';
	public $title = '学生信息绑定';
	public $ability = '';
	public $tablename = 'stubind_reply';
	public function getProfileTiles() {

	}
	
	public function getHomeTiles($keyword = '') {
		$urls = array();
		$list = pdo_fetchall("SELECT name, id FROM ".tablename('rule')." WHERE weid = '{$_W['weid']}' AND module = 'stubind'".(!empty($keyword) ? " AND name LIKE '%{$keyword}%'" : ''));
		if (!empty($list)) {
			foreach ($list as $row) {
				$urls[] = array('title'=>$row['name'], 'url'=> $this->createMobileUrl('stubind', array('id' => $row['id'])));
			}
		}
		return $urls;
	}
	
	public function doWebDisplay() {
		global $_GPC, $_W;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 50;
		
		$where = '';
		$starttime = empty($_GPC['start']) ? strtotime('-1 month') : strtotime($_GPC['start']);
		$endtime = empty($_GPC['end']) ? TIMESTAMP : strtotime($_GPC['end']) + 86399;
		$where .= " AND createtime >= '$starttime' AND createtime < '$endtime'";
		
		$fields = pdo_fetchall("SELECT field, title FROM ".tablename('stu_profile_fields'), array(), 'field');
		$select = array();
		if (!empty($_GPC['select'])) {
			foreach ($_GPC['select'] as $field) {
				if (isset($fields[$field])) {
					$select[] = $field;
				}
			}
		}
		
		$list = pdo_fetchall("SELECT from_user, createtime ".(!empty($select) ? ",`".implode('`,`', $select)."`" : '')." FROM ".tablename('stu_profile')." WHERE  from_user <> '' $where ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		$total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('stu_profile')." WHERE from_user <> '' $where ");
		$pager = pagination($total, $pindex, $psize);
		//print_r($list);
		include $this->template('display');
	}
	
	public function doWebProfile() {
		global $_W, $_GPC;
		$from_user = $_GPC['from_user'];
		if (checksubmit('submit')) {
			if (!empty($_GPC)) {
				foreach ($_GPC as $field => $value) {
					if (!isset($value) || in_array($field, array('do','weid','__weid','wechatloaded','__session','remember-username','from_user','act', 'name', 'token', 'submit', 'session'))) {
						unset($_GPC[$field]);
						continue;
					}
				}
					pdo_update('stu_profile',$_GPC, array('from_user' => $from_user));
			}
			message('更新资料成功！', referer(), 'success');
		}
		$profile =pdo_fetchall("SELECT * FROM ".tablename('stu_profile')." WHERE from_user = '".$from_user."'  limit 1");
		$fields = pdo_fetchall("SELECT field, title FROM ".tablename('stu_profile_fields'), array(), 'field');
		include $this->template('profile');
	}
	
	
	public function doMobileStuBind() {
		global $_GPC ,$_W;
		$fromuser = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		$we = pdo_fetch("select b.weid from ims_stubind_reply as a left join ims_rule  as b on a.rid = b.id where 1");
		$id = intval($_GPC['id']);
		$member = pdo_fetch("SELECT * FROM ".tablename('stu_profile')." WHERE from_user = '{$fromuser}'");
		$stubind = pdo_fetch("SELECT * FROM ".tablename($this->tablename)." WHERE rid = '".$id."' LIMIT 1");
		if (empty($stubind)) {
			exit('非法参数！0');
		}
		if (empty($member['realname'])) {
			$from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
			$title = '信息绑定';
			$loclurl=create_url('mobile/module/stubind', array( 'name' => 'stubind', 'id' => $id,'weid'=> $we['weid'], 'from_user' => $_GPC['from_user']));
			$checkinfo=create_url('mobile/module/check', array( 'name' => 'check', 'id' => $id,'weid'=> $we['weid'], 'from_user' => $_GPC['from_user']));
			include $this->template('bind');
		}else{
		global $_W, $_GPC;
		$from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		$title = '我的资料';
		
			if ($_GPC['type']=='update') {
			$insert = array(
								'from_user'=>$from_user,
								'xh' => $_GPC['xh'],
								'jwcpwd' => $_GPC['jwcpwd'],
								'libpwd' => $_GPC['libpwd'],
								'realname' => $_GPC['realname'],
								'nickname' => $_GPC['nickname'],
								'xb' => $_GPC['xb'],
								'nj' => $_GPC['nj'],
								'bjmc' => $_GPC['bjmc'],
								'avatar' => $_GPC['avatar'],
								'wechat' => $_GPC['wechat'],
								'mobile' => $_GPC['mobile'],
								'shortnum' => $_GPC['shortnum'],
								'room' => $_GPC['room']
						);
				
						
						
						
						
			if (!empty($insert)) {
				foreach ($insert as $field => $value) {
					if (!isset($value)) {
						unset($insert[$field]);
					continue;
					}
				}
			
					pdo_update('stu_profile',$insert, array('from_user' => $from_user));
			}
			message('更新资料成功！', referer(), 'success');
		}
		
		if ($_GPC['type']=='disband') {
			
			$a=pdo_delete('stu_profile', array('from_user' => $from_user),'and');
			if($a)
			{
				message('删除资料成功！', referer(), 'success');
			}
			else
			{
				message('删除资料失败！请将这信息告诉管理员', referer(), 'error');
			}
		}
		
		
		$profile=array();
		$sql = "SELECT * FROM " . tablename('stu_profile') . " WHERE `from_user`=:from_user LIMIT 1";
		$profile['0'] = pdo_fetch($sql, array(':from_user' => $from_user));
		$avatar=$_W['attachurl'].$profile['0']['avatar'];
			$update=create_url('mobile/module/profile', array( 'name' => 'stubind', 'id' => $id,'weid'=> $we['weid'], 'from_user' => $_GPC['from_user']));
			include $this->template('binded');
		}
	}
	
	
	public function doMobileProfile() {
		global $_W, $_GPC;
		$from_user =$_GPC['from_user'];
		$from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		if (empty($from_user)) {
			message('非法访问，请重新点击链接进入修改！');
		}
		$title = '我的资料';
	
		$profile=array();
		$sql = "SELECT * FROM " . tablename('stu_profile') . " WHERE `from_user`=:from_user LIMIT 1";
		$profile['0'] = pdo_fetch($sql, array(':from_user' => $from_user));
		include $this->template('binded');
	}
	
	  
	  
	  public function doMobileUpdate() {
	  
		global $_W, $_GPC;
		$from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		if (checksubmit('submit')) {
			$insert = array(
								'from_user'=>$from_user,
								'xh' => $_GPC['xh'],
								'jwcpwd' => $_GPC['jwcpwd'],
								'libpwd' => $_GPC['libpwd'],
								'realname' => $_GPC['realname'],
								'nickname' => $_GPC['nickname'],
								'xb' => $_GPC['xb'],
								'nj' => $_GPC['nj'],
								'bjmc' => $_GPC['bjmc'],
								'avatar' => $_GPC['avatar'],
								'wechat' => $_GPC['wechat'],
								'mobile' => $_GPC['mobile'],
								'shortnum' => $_GPC['shortnum'],
								'room' => $_GPC['room']
						);
			if (!empty($insert)) {
			
					pdo_update('stu_profile',$insert, array('from_user' => $from_user));
			}
			message('更新资料成功！', referer(), 'success');
		}
	$profile=array();
		$sql = "SELECT * FROM " . tablename('stu_profile') . " WHERE `from_user`=:from_user LIMIT 1";
		$profile['0'] = pdo_fetch($sql, array(':from_user' => $from_user));
	  //print_r($profile);
	  }
	  
	  
	  
	  
	  public function doMobileCheck() {
	  	global $_GPC;
	  	$_GPC['weid']=$_GET['weid'];
		$stuid=$_REQUEST['stuid'];
		$libpw=$_REQUEST['libpw'];
		$jwpw=$_REQUEST['jwpw'];
		//$from_user=$_REQUEST['from_user'];
		$nickname=$_REQUEST['nickname'];
		$mobile=$_REQUEST['mobile'];
		$shortnum=$_REQUEST['shortnum'];
		$room=$_REQUEST['room'];
		$wechat=$_REQUEST['wechat'];
		$from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		if($_GPC['type']=='setinfo'){
		$info=file_get_contents('http://ours.123nat.com:59832/api/chengji/jwc_personinfo.php?xh='.$stuid.'&pw='.$jwpw);
		$info=json_decode($info,true);

		//$checklib=$this->checklib($stuid,$libpw);

		//$lib_statue='-1';
		$jwc_statue='-1';

		//if($checklib == '1')
		//{
		//	$lib_statue='1';
		//}
		if(isset($info['xm']) && isset($info['sfzh']) && isset($info['bjmc']))
		{
			$jwc_statue='1';
		}

		$lib_statue='1';
		if(($lib_statue=='1') && ($jwc_statue=='1'))
		{
			
			$user = pdo_fetch("SELECT * FROM ims_stu_profile WHERE from_user = '".$from_user."'  limit 1");
			$insert = array(
								'from_user'=>$from_user,
								'xh' => $stuid,
								'jwcpwd' => $jwpw,
								'libpwd' => $libpw,
								'realname' => $info['xm'],
								'nickname' => $nickname,
								'xb' => $info['xb'],
								'csrq' => $info['csrq'],
								'sfzh' => $info['sfzh'],
								'xymc' => $info['xymc'],
								'zymc' => $info['zymc'],
								'zyfx' => $info['zyfx'],
								'bjmc' => $info['bjmc'],
								'nj' => $info['nj'],
								'syszd' => $info['syszd'],
								'wechat' => $wechat,
								'mobile' => $mobile,
								'shortnum' => $shortnum,
								'room' => $room,
								'createtime'=>time(),
						);
			if ($user==false) {
				$id=pdo_insert('stu_profile', $insert);
								
			} else {
				pdo_update('stu_profile', $insert, array('from_user' => $from_user));
			}			
			$statue='success';
			
			
			
		}
		if(($lib_statue=='-1') && ($jwc_statue=='1'))
		{
			$statue='lib_error';
		}
		if(($lib_statue=='1') && ($jwc_statue=='-1'))
		{
			$statue='jwc_error';
		}
		if(($lib_statue=='-1') && ($jwc_statue=='-1'))
		{
			$statue='error';
		}


		$info=array('msg'=>$statue,'stuid'=>$stuid,'libpw'=>$libpw,'jwpw'=>$jwpw,'nickname'=>$nickname,'mobile'=>$mobile,'shortnum'=>$shortnum,'room'=>$room,'wechat'=>$wechat,'data'=>$info);
		echo $a=json_encode($info);

		
	  }
	  else
	  {
	  echo '没有提交任何信息';
	  }
	  }
	  
	  
	  
	  
	 public function checklib($userid,$pw)
	 {
		$post_fields 	= 'userid='.$userid.'&password='.$pw.'&checkbox=on&button=%E7%99%BB%E5%BD%95';
		$submit_url 	= 'http://www.lib.gdpu.edu.cn/gdpusso/checklogin_cookie.jsp';//提价页面
		$success_url = "http://www.lib.gdpu.edu.cn/gdpusso/vrd_zxzxs.jsp\r";


		$ch = curl_init($submit_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HEADER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		$contents = curl_exec($ch);
		preg_match('/Location: (.*)/i', $contents, $ret);
		$location = $ret[1];
		if($location == $success_url)
		{
			return $liblogin="1";
		}
		else
		{
			return $liblogin="-1";
		}

		
		curl_close($ch);
	}
	  
	  
	  
	  
	  
	
	  
}
