<?php
error_reporting(0);
session_start();

class SiteController extends Controller
{

/**
 * Declares class-based actions.
 */
public function actions()
{
  return array(
    // captcha action renders the CAPTCHA image displayed on the contact page
    'captcha'=>array(
      'class'=>'CCaptchaAction',
      'backColor'=>0xFFFFFF,
    ),
    // page action renders "static" pages stored under 'protected/views/site/pages'
    // They can be accessed via: index.php?r=site/page&view=FileName
    'page'=>array(
      'class'=>'CViewAction',
    ),
  );
}

/*Email verification start */
  public function actionVerifyEmail($member_id)
  {
    $member_id = base64_decode($member_id);
    $member_data = Member::model()->findByPk($member_id);

    if(!empty($member_data))
    {
      Member::model()->updateByPk($id,array('is_email_verify'=>'Y'));
      echo "200";
    }
    else
    {
      echo "304";
    }
  }

/*Email verification end */

/**
 * This is the default 'index' action that is invoked
 * when an action is not explicitly requested by users.
 */

public function actionIndex()
{
  $this->layout = 'commingSoon';
  $this->render('commingSoon');
}

public function actionIndexOld()
{
  $member_id = ApplicationSessions::run()->read('member_id');

  $recent_member 	= array();
  $cnt_location 	= 0;
  $loc_data 		= '';
  $loc_suggst_data= '';
  $gallery_data 	= array();
  $memer_data 	= Member::model()->findByPk($member_id);

  if(!empty($member_id))
  {
    $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 0;
    $limit  = (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 50;

    if(!empty($member_id))
    {
      $loc_data .=  	Controller::UserJoinedLocation($member_id);
      $loc_suggst_data = 	$this->LocationSuggestion($member_id);
      // $cnt_location++;
    }
          /*************************/
          $condition = 'active_status="S" and status="1" and (type="PC" or type="S") ';
        //get Frnds & follower
          $frnd_nd_follower = $this->getFriendFollowerIds($member_id);

          if(!empty($frnd_nd_follower))
          {
            $member_ids = Member::model()->find(array('select'=>'group_concat(member_id) as member_id','condition'=>'active_status="S" and status="1" and acc_suspend="N" and member_id IN ('.$frnd_nd_follower.')'));

             $condition .= ' and (member_id IN ('.$frnd_nd_follower.') or to_id='.$member_id.')';
          }
        //get followedLocation user ids
          $location_followers = $this->followedSameLocationUserIds($member_id);

        //blocked user ids
          $bloced_usr_id = $this->BlockedUserList($member_id);

            if(!empty($bloced_usr_id))
            {
               $condition .= ' and member_id NOT IN ('.$bloced_usr_id.')';
            }

        //blocked user ids
          $blokcedby_othr_usr_id = $this->BlockedByOtherUserList($member_id);

            if(!empty($blokcedby_othr_usr_id))
            {
               $condition .= ' and member_id NOT IN ('.$blokcedby_othr_usr_id.')';
            }

        //reported post ids
          $reported_post = $this->ReportedPost($member_id);

            if(!empty($reported_post))
            {
              $condition .= ' and post_id NOT IN ('.$reported_post.')';
            }

        //getfrom post ids from user activity tbl
          // $post_ids = UserActivity::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>$condition));
          $post_ids 	= $this->SequenceOfactivity($condition);

          if(!empty($post_ids))
          {
            $locn_mstr_id 	= $this->followedLocationMstrId($member_id);
            $post_id 		= $post_ids;

            //Post shared by user on timeline
              $shared_post_to_frnd = PostShare::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and type="T" and to_id='.$member_id));

              if(!empty($shared_post_to_frnd->post_id))
              {
                if(!empty($post_id))
                {
                  $post_id .= ','.$shared_post_to_frnd->post_id;
                }
                else
                {
                  $post_id .= $shared_post_to_frnd->post_id;
                }
              }

              $psot_condition = 'active_status="S" and status="1" and post_id IN ('.$post_id.')';

              //blocked user ids
                $bloced_usr_id = $this->BlockedUserList($member_id);

                  if(!empty($bloced_usr_id))
                  {
                     $psot_condition .= ' and member_id NOT IN ('.$bloced_usr_id.')';
                  }

              //blocked user ids
                $blokcedby_othr_usr_id = $this->BlockedByOtherUserList($member_id);

                  if(!empty($blokcedby_othr_usr_id))
                  {
                     $psot_condition .= ' and member_id NOT IN ('.$blokcedby_othr_usr_id.')';
                  }

              //reported post ids
                $reported_post = $this->ReportedPost($member_id);

                  if(!empty($reported_post))
                  {
                    $psot_condition .= ' and post_id NOT IN ('.$reported_post.')';
                  }

              //post from location
                $post 	= Post::model()->findAll(array('condition'=>$psot_condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'FIELD(post_id,'.$post_id.')'));


              /*************************/
              //post from location

              $tot_post_count 		= Post::model()->count(array('condition'=>$psot_condition));
              $max_id 				= Post::model()->find(array('select'=>'max(post_id) as post_id','condition'=>$psot_condition));
              $data['tot_post_count'] = $tot_post_count;
              $data['max_post_id'] 	= (!empty($max_id->post_id)) ? $max_id->post_id : 0;

              if((!empty($cursor == 1)) && $cursor == 1)
              {
                $followed_location 	= $this->userFollowedLocation($member_id);
                $admin_msg['url'] 	= 'http://www.lovethispic.com/uploaded_images/235930-Happy-Valentine-s-Day-Gif.gif';
              }
          }

  }
  else
  {
    $post = Post::model()->findAll(array('condition'=>'active_status="S"','order'=>'post_id desc','limit'=>10));
  }

  if(!empty($member_id))
  {
    // $member_post   = Post::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'post_id desc'));
    $buddies_data  = Friends::model()->findAll(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));
    $sent_data	   = Friends::model()->findAll(array('condition'=>'from_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
    $receive_data  = Friends::model()->findAll(array('condition'=>'to_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
    $buddies_count = Friends::model()->count(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

    $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'user_activity_id desc'));

    $recent_member 	= $this->stories();
  }
  else
  {
    $member_post 	= '';
    $buddies_data 	= '';
    $sent_data 		= '';
    $receive_data 	= '';
    $analytics 		= '';
    $loc_data 		= '';
    $loc_suggst_data	 = '';
    $location_attachment = '';
  }


  $this->render('index',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'location_attachment'=>$location_attachment,'recent_member'=>$recent_member,'loc_data'=>$loc_data,'gallery_data'=>$gallery_data,'limit'=>$limit,'memer_data'=>$memer_data,'loc_suggst_data'=>$loc_suggst_data));
}


public function actionProfileView()
{
    $member_id = ApplicationSessions::run()->read('member_id');

    $recent_member = array();
    $cnt_location = 0;
    $loc_data = '';
    $gallery_data = array();
    $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 0;
    $limit  = (!empty($_REQUEST['limit']))  ? $_REQUEST['limit'] : 50;
    $newCursor = $limit + $cursor;
    if(!empty($member_id))
    {


      if(!empty($member_id))
      {
        $loc_data =  Controller::UserJoinedLocation($member_id);
        // $cnt_location++;
      }
      /*************************/

        $condition = 'active_status="S" and status="1" ';
          //own post from activity
            $own_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$member_id));

            if(!empty($own_post->post_id))
            {
              $post_id = $own_post->post_id;
            }
          ////reported post ids
              $reported_post = $this->ReportedPost($member_id);

          //Post shared
            $shared_post = PostShare::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and type="T" and from_id='.$member_id));

            if(!empty($shared_post->post_id))
            {
              if(!empty($post_id))
              {
                $post_id .= ','.$shared_post->post_id;
              }
              else
              {
                $post_id .= $shared_post->post_id;
              }
            }
            if(!empty($post_id))
            {
              $condition .= ' and post_id IN ('.$post_id.')';
            }
            else
            {
              $condition .= ' and member_id='.$member_id;
            }
            if(!empty($reported_post))
            {
              $condition .= ' and post_id NOT IN ('.$reported_post.')';
            }

            $post 			= Post::model()->findAll(array('condition'=>$condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'post_id desc'));
            $tot_post_count = Post::model()->count(array('condition'=>$condition));

            /*own post id for gallery start*/
              $own_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$member_id));

              if(!empty($own_post->post_id))
              {
                $gallery_data = PostAttachment::model()->findAll(array('condition'=>'active_status="S" and  status="1" and post_id IN ('.$own_post->post_id.')','order'=>'post_attachment_id desc'));
              }
            /*own post id for gallery end*/

            $max_id 				= Post::model()->find(array('select'=>'max(post_id) as post_id','condition'=>$psot_condition));
            $data['tot_post_count'] = $tot_post_count;
            $data['max_post_id'] 	= (!empty($max_id->post_id)) ? $max_id->post_id : 0;

            if((!empty($cursor == 1)) && $cursor == 1)
            {
              $followed_location = $this->userFollowedLocation($member_id);
              $admin_msg['url'] = 'http://www.lovethispic.com/uploaded_images/235930-Happy-Valentine-s-Day-Gif.gif';
            }

      /*************************/


    }
    else
    {
      $post = Post::model()->findAll(array('condition'=>'active_status="S"','order'=>'post_id desc','limit'=>10));
    }

    if(!empty($member_id))
    {
      // $member_post   = Post::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'post_id desc'));
      $buddies_data  = Friends::model()->findAll(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));
      $sent_data	   = Friends::model()->findAll(array('condition'=>'from_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
      $receive_data  = Friends::model()->findAll(array('condition'=>'to_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
      $buddies_count = Friends::model()->count(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

      $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'user_activity_id desc'));

      $recent_member = $this->stories();
    }
    else
    {
      $member_post = '';
      $buddies_data = '';
      $sent_data = '';
      $receive_data = '';
      $analytics = '';
      $location_attachment = '';
      $loc_data = '';
    }


    $this->render('profileView',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'location_attachment'=>$location_attachment,'recent_member'=>$recent_member,'loc_data'=>$loc_data,'gallery_data'=>$gallery_data,'newCursor'=>$newCursor));
}

public function actionGoogleLogin()
  {
  $client = Yii::app()->google->init();
  $redirect_uri = Yii::app()->createAbsoluteUrl('site/googleLogin');
  $client->setRedirectUri($redirect_uri);

  if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();

    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
  }

  if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
  } else {
    $authUrl = $client->createAuthUrl();
  }

  if ($client->getAccessToken() && isset($_GET['url'])) {
    $_SESSION['access_token'] = $client->getAccessToken();
  }

  if (isset($authUrl))
  {
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));

  }
  else
  {
    $oauth2 = new Google_Service_Oauth2($client);
    $user_profile = $oauth2->userinfo->get();
    $name 	= $user_profile['name'];
    $email 	= $user_profile['email'];
    $google_id = $user_profile['id'];
    $gender    = $user_profile['gender'];
    $img_url   = $user_profile['picture'];
    $refresh_token = $client->getRefreshToken();
    $access_token = $client->getAccessToken();
    $sessionToken = json_decode($access_token);
    $token = $sessionToken->access_token;

    $model = Member::model()->find('email_id=:email', array(':email'=>$email));

    if($model===null)
    {
      $name_arr = explode(" ",$name);
      $model = new Member;
      $model->first_name = $name_arr[0];
      $model->last_name = (count($name_arr)>1)?$name_arr[count($name_arr)-1]:'';
      $model->email_id = $email;
      $model->username = $name_arr[0];
      $model->profile_pic = $img_url;
      $model->status = 1;
      $model->login_type = 'G';
      $model->added_on = time();
      $model->updated_on = time();
      $model->save(false);
      }
    $notification_count = UserActivity::model()->count(array('condition'=>'active_status="S" and status="1" and to_id='.$model->member_id));

    ApplicationSessions::run()->write('member_id', $model->member_id);
    ApplicationSessions::run()->write('member_email', $model->email_id);
    ApplicationSessions::run()->write('member_name', $model->first_name." ".$model->last_name);
    ApplicationSessions::run()->write('first_name', $user_data->first_name);
    ApplicationSessions::run()->write('last_name', $user_data->last_name);
    ApplicationSessions::run()->write('member_username', $model->username);
    ApplicationSessions::run()->write('member_pic', $model->profile_pic);
    ApplicationSessions::run()->write('cover_photo', $model->cover_photo);
    ApplicationSessions::run()->write('about_me', $model->about_me);
    ApplicationSessions::run()->write('notification_count', $notification_count);

    echo "<script>
        window.close();
        window.opener.location.reload();
      </script>";
    //$this->redirect(array('index'));

  }
  }

public function  actionFblogin()
  {
      if(!empty($_POST))
  {

          $token 		= $_POST['access_token'];  // to store access_token in database
    $name 		= $_POST['name'];
    $email 		= $_POST['user_email'];
    $fb_id 		= $_POST['user_id'];
    $gender 	= $_POST['gender'];
    $img_url 	= $_POST['profile_image'];
    $unixtimestamp = time();



          $model = Member::model()->find('email_id=:email', array(':email'=>$email));
          $res= '200';
    if($model===null)
    {
      $name_arr 	= explode(" ",$name);
      $model 		= new Member;
      $model->fb_id 		= $token;
      $model->first_name 	= base64_encode($token);
      $model->last_name 	= (count($name_arr)>1)?base64_encode($name_arr[count($name_arr)-1]):'';
      $model->email_id 	= $email;
      $model->username 	= $name[0];
      $model->fb_image_url= $img_url;
      $model->status 		= 1;
      $model->login_type 	= 'F';
      $model->added_on 	= time();
      $model->updated_on 	= time();
      $model->save(false);

      $res= '300';
    }

    ApplicationSessions::run()->write('member_id', $model->member_id);
    ApplicationSessions::run()->write('member_email', $model->email_id);
    ApplicationSessions::run()->write('member_name', $model->first_name." ".$model->last_name);
    ApplicationSessions::run()->write('first_name', $user_data->first_name);
    ApplicationSessions::run()->write('last_name', $user_data->last_name);
    ApplicationSessions::run()->write('member_username', $model->username);
    ApplicationSessions::run()->write('member_pic', $model->profile_pic);
    ApplicationSessions::run()->write('cover_photo', $model->cover_photo);
    ApplicationSessions::run()->write('about_me', $model->about_me);
    $notification_count = UserActivity::model()->count(array('condition'=>'active_status="S" and status="1" and to_id='.$model->member_id));
    ApplicationSessions::run()->write('notification_count', $notification_count);

    print $res;

  } else {
              $loginUrl = $helper->getLoginUrl(array('scope' => 'email,read_stream,user_friends'));
              echo("<script> top.location.href='".$loginUrl."'</script>");
  }
}

//register start

public function actionRegisterStep1()
{
  $otp = rand(1000,9999);
  echo $otp;
}

public function actionRegister()
{
  // if($_REQUEST['password'] == $_REQUEST['confirm_password'])
  // {
    $user_data = Member::model()->find(array('condition'=>'email_id!="" && (username="'.$_REQUEST['email'].'" || email_id="'.$_REQUEST['email'].'")'));


    if(empty($user_data))
    {
      $model = new Member;
    }
    else
    {
      $model = $user_data;
    }

    if(!empty($user_data))
    {
      echo "304";
    }
    else
    {
      $name_arr = explode(' ',$_REQUEST['name']);
      $password 			= $_REQUEST['password'];
      $model->first_name 	= $name_arr[0];
      $model->last_name 	= $name_arr[1];
      $model->email_id 	= $_REQUEST['email'];
      $model->mobile_no 	= $_REQUEST['mobile_no'];
      $model->password 	= md5($password);
      $model->username 	= $_REQUEST['email'];

      //save profile pic if exist
      if(!empty($_FILES['vpb-data-file']['name']))
      {
        if(!is_dir("upload/member/profile_pic/"))
        {
          mkdir("upload/member/profile_pic/" , 0777,true);
        }

        foreach($_FILES['vpb-data-file']['name'] as $key=>$val)
        {
          $tmpFilePath 	= $_FILES['vpb-data-file']['tmp_name'][$key];
          $caption_val  	= (!empty($_REQUEST['caption_img'][$key]))?$_REQUEST['caption_img'][$key]:'';

          if ($tmpFilePath != "")
          {
            $image_path = Yii::app()->basePath . '/../upload/member/profile_pic/';
            $ext 		= explode(".",$_FILES['vpb-data-file']['name'][$key]);
            $image_name = time().".".$ext[1];

            $newFilePath = $image_path . $image_name;

            if(move_uploaded_file($tmpFilePath, $newFilePath))
            {
              $model->profile_pic= $image_name;
            }
          }
        }
      }

      $model->login_type = "S";
      $model->added_on   = time();
      $model->updated_on = time();

      if($model->save(false))
      {


        if(!empty($_REQUEST['location_follow']))
        {
          $location_user =  $_REQUEST['location_follow'];

          for($i=0; $i<count($_REQUEST['location_follow']); $i++)
          {

            $location_model = new Location;



            $address= $this->getLocation($_REQUEST['location_follow'][$i]);
            $address_array = explode(',',$address);
            $location_master = LocationMaster::model()->find(array('condition'=>'latitude='.round($address_array[0],4).' and longitude='.round($address_array[1],4)));

              if(empty($location_master))
              {
                $location_master = new LocationMaster;
                $location_master->location_name = $_REQUEST['location_follow'][$i];
                $location_master->latitude 		= $address_array[0];
                $location_master->longitude 	= $address_array[1];
                $location_master->added_on		= time();
                $location_master->updated_on	= time();

                $location_master->save();
              }
            $location_model->member_id 	  	= $model->member_id;
            $location_model->location_master_id = $location_master->location_master_id;
            $location_model->location_name 	= $_REQUEST['location_follow'][$i];
            $location_model->latitude 		= round($address_array[0],4);
            $location_model->longitude 		= round($address_array[1],4);
            $location_model->added_on		= time();
            $location_model->updated_on		= time();
            $location_model->save();

          }
        }
        ApplicationSessions::run()->write('member_id', $model->member_id);
        ApplicationSessions::run()->write('member_email', $model->email_id);
        ApplicationSessions::run()->write('member_name', $model->first_name." ".$model->last_name);
        ApplicationSessions::run()->write('first_name', $user_data->first_name);
        ApplicationSessions::run()->write('last_name', $user_data->last_name);
        ApplicationSessions::run()->write('member_username', $model->username);
        ApplicationSessions::run()->write('member_pic', $model->profile_pic);
        ApplicationSessions::run()->write('cover_photo', $model->cover_photo);
        ApplicationSessions::run()->write('about_me', $model->about_me);
        $notification_count = UserActivity::model()->count(array('condition'=>'active_status="S" and status="1" and to_id='.$model->member_id));
        ApplicationSessions::run()->write('notification_count', $notification_count);

        echo "200";
      }
      else
      {
        echo "503";
      }
    }
  // }
  // else
  // {
  // 	echo "404";
  // }


}
//register end

//login start

public function actionLogin()
{
  $user_data = Member::model()->find(array('condition'=>'(email_id="'.$_REQUEST['email'].'" or username="'.$_REQUEST['email'].'" or mobile_no="'.$_REQUEST['email'].'") and password="'.md5($_REQUEST['password']).'" and active_status="S" and status="1"'));

  if(!empty($user_data))
  {
    if($user_data->acc_temp_suspend =="N" && $user_data->acc_suspend =="N")
    {
      ApplicationSessions::run()->write('member_id', $user_data->member_id);
      ApplicationSessions::run()->write('member_email', $user_data->email_id);
      ApplicationSessions::run()->write('member_name', $user_data->first_name." ". $user_data->last_name);
      ApplicationSessions::run()->write('first_name',  $user_data->first_name);
      ApplicationSessions::run()->write('last_name',  $user_data->last_name);
      ApplicationSessions::run()->write('member_username', $user_data->username);
      ApplicationSessions::run()->write('member_pic', $user_data->profile_pic);
      ApplicationSessions::run()->write('cover_photo', $user_data->cover_photo);
      ApplicationSessions::run()->write('about_me', $user_data->about_me);
      $notification_count = UserActivity::model()->count(array('condition'=>'active_status="S"  and status="1" and type NOT IN("PL,SP,CP,CR,L")  and member_id !='.$user_data->member_id.' and to_id ='.$user_data->member_id,'order'=>'user_activity_id DESC'));
      ApplicationSessions::run()->write('notification_count', $notification_count);

      echo "200";
    }
    else if($user_data->acc_temp_suspend =="Y" && $user_data->acc_suspend !="Y")
    {
      echo "W2";
    }
    else if($user_data->acc_suspend =="Y")
    {
      echo "W3";
    }
  }
  else
  {
    echo "404";
  }

}
//login end

//forgtPassword start
public function actionForgtPassword()
{
  $user_data = Member::model()->find(array('condition'=>' email_id="'.$_REQUEST['email'].'" and active_status="S" and status="1"'));

  if(!empty($user_data))
  {
    $password = rand(1000,9999);

    $model = $user_data;
    $model->password = md5($password);
    if($model->save())
    {
      /*

      $body = 'Dear '.$model->first_name." ".$model->last_name.",<br/><br/>You have successfully reset your password at HashBuddies, use below credentials to login :-<br/><br/>Username :- ".$model->email_id."<br/>Password :- ".$password."<br/><br/>--Regards<br/>HashBuddies Team";
      $subject = 'New Password From HashBuddies';
      $to_email = $model->email_id;
      $to_name = $model->first_name." ".$model->last_name;
      $this->sendMail($subject,$body,$to_email,$to_name);
      */
      echo "200";
    }
    else
    {
      echo "301";
    }


  }
  else
  {
    echo "404";
  }
}
//forgtPassword end

//GetFriend Start
public function actionGetFriend()
{
  $term = $_REQUEST['term'];
  $html = '';

  if(empty($term))
  {
    $friend_data = array();
  }
  else
  {
    $friend_data = Member::model()->findAll(array('condition'=>'first_name like "%'.$term.'%" or last_name like "%'.$term.'%" or username like "%'.$term.'%"'));
  }

  if(!empty($friend_data))
  {
    foreach($friend_data as $val)
    {
      $html .= '<div class="clearfix pop-pad d-ft-bor" member-id="'.$val->member_id.'" onclick="tagFriend(this)">
             <div class="pbl-ewih mar-none">
            <div class="left-tex">
              <div class="flot-left dp-icon mt10">';

                if(empty($val->profile_pic))
                {
                  $html .= '<img class="img-responsive" src="'.Yii::app()->theme->baseUrl.'/images/profile-act.png">';
                }
                else if(strpos($val->profile_pic,"http")!==false)
                {
                  $html .= '<img class="img-responsive" src="'.$val->profile_pic.'">';
                }
                else
                {
                  $html .= '<img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/member/profile_pic/'.$val->profile_pic.'">';
                }

          $html .= '</div>
                <div class="flot-left mt10"><strong>'.$val->first_name." ".$val->last_name.'</strong> ('.$val->username.')
                <br>'.$this->getFriendsCount($val->member_id,'A').' mutual connections
              </div>
            </div>
            </div>
          </div>';
    }
  }
  else
  {
    $html .= '<div class="clearfix pop-pad">
          <div class="pbl-ewih mar-none">
            <div class="left-tex">
              <div class="flot-left dp-icon mt10">
                &nbsp;
              </div>
              <div class="flot-left mt10"><strong>No Friend Found </strong>
              </div>
            </div>
          </div>
        </div>';
  }

  echo $html;
}


public function actionUpdateProfile()
{
  $member_id = ApplicationSessions::run()->read('member_id');

  echo "<pre>";
  print_r($_REQUEST);
  exit;

  $model =  Member::model()->findByPk($member_id);
  $model->attributes		=	$_POST['Member'];
  $model->first_name 		= (!empty($_POST['Member']['first_name'])) 	? base64_encode($_POST['Member']['first_name']) : " ";
  $model->last_name 		= (!empty($_POST['Member']['last_name'])) 	? base64_encode($_POST['Member']['last_name']) : " ";
  $model->about_me 		= (!empty($_POST['Member']['about_me'])) 	? base64_encode($_POST['Member']['']) : " ";
  $model->job_title 		= (!empty($_POST['Member']['job_title'])) 	? base64_encode($_POST['Member']['about_me']) : " ";
  $model->full_name 		= $_POST['Member']['first_name'].' '. $_POST['Member']['last_name'];

  $model->updated_on = time();

  ///profile pic File obj start

  if(!is_dir("upload/member/profile_pic/"))
    mkdir("upload/member/profile_pic/" , 0777,true);

    if(!empty($_FILES['profile_pic']['name']) )
      {
        $ext = explode(".",$_FILES['profile_pic']['name']);
        $image_name = time().".".$ext[1];
        $image_path = Yii::app()->basePath . '/../upload/member/profile_pic/'.$image_name;

        if(move_uploaded_file($_FILES['profile_pic']['tmp_name'],$image_path))
        {
          $model->profile_pic = $image_name;
        }
      }
  ///profile pic File obj end

  ///cover pic File obj start
  if(!is_dir("upload/member/cover_photo/"))
    mkdir("upload/member/cover_photo/" , 0777,true);

      if(!empty($_FILES['cover_photo']['name']) )
      {
        $ext = explode(".",$_FILES['cover_photo']['name']);
        $image_name = time().".".$ext[1];
        $image_path = Yii::app()->basePath . '/../upload/member/cover_photo/'.$image_name;

        if(move_uploaded_file($_FILES['cover_photo']['tmp_name'],$image_path))
        {
          $model->cover_photo = $image_name;
        }
      }
  ///cover pic File obj end

  if($model->save())
  {
    if(!empty($model->device_token))
    {
      $this->sendMessage('Profile Updated SUCCESSFULLY',$model->device_token);
    }
    //storeUserActivity
      $this->storeUserActivity($member_id,"Edited profile","EP","Member",$member_id);
      echo "200";

      ApplicationSessions::run()->write('member_id', $model->member_id);
      ApplicationSessions::run()->write('member_email', $model->email_id);
      ApplicationSessions::run()->write('member_name', $model->first_name." ".$model->last_name);
      ApplicationSessions::run()->write('first_name',$model->first_name);
      ApplicationSessions::run()->write('last_name', $model->last_name);
      ApplicationSessions::run()->write('member_username', $model->username);
      ApplicationSessions::run()->write('member_pic', $model->profile_pic);
      ApplicationSessions::run()->write('cover_photo', $model->cover_photo);
      ApplicationSessions::run()->write('about_me', $model->about_me);
  }
  else
  {
    echo "400";
  }
}

//Tag Friend Start
public function actionTagFriend()
{
  $member_id = $_REQUEST['member_id'];
  $friend_id = explode(",",$_REQUEST['friend_id']);

  if(empty($friend_id))
  {
    $friend_id[] = $member_id;
    $resp = 'Y::'.trim(implode(",",$friend_id),",");
  }
  else
  {
    if(in_array($member_id,$friend_id))
    {
      $key = array_search($member_id,$friend_id);
      unset($friend_id[$key]);
      $resp = 'N::'.trim(implode(",",$friend_id),",");
    }
    else
    {
      $friend_id[] = $member_id;
      $resp = 'Y::'.trim(implode(",",$friend_id),",");
    }
  }

  echo $resp;
}

// getTaggedFriend Start
public function actionGetTaggedFriend()
{
  $friend_id = $_REQUEST['friend_id'];
  $html = '';

  if(!empty($friend_id))
  {
    $member_data = Member::model()->findAll(array('condition'=>'member_id IN('.$friend_id.')'));

    if(!empty($member_data))
    {
      foreach($member_data as $val)
      {
        $connections = Friends::model()->count(array('condition'=>'(from_id='.$val->member_id.' or to_id='.$val->member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

        if(empty($val->profile_pic))
        {
          $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
        }
        else if(!empty($val->profile_pic) && strpos($val->profile_pic,"http")===false)
        {
          $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$val->profile_pic;
        }
        else
        {
          $profile_pic = $val->profile_pic;
        }

        $html .= '<div class="flt-ion-tb" id="tag_friend_'.$val->member_id.'">
              <div class="row">
                <div class="col-md-2"><a href="javascript:void(0);" onclick="removeTagFriend(this);" data-id="'.$val->member_id.'">X</a></div>
                <div class="col-md-10">
                  <div class="left-tex">
                    <div class="flot-left dp-icon-tb">
                      <img class="img-responsive" src="'.$profile_pic.'">
                    </div>
                    <div class="flot-left">
                      <strong>'.base64_decode($val->first_name).' '.base64_decode($val->last_name).'</strong>
                    <!--- 	<br>
                      '.$connections.' connections --->
                    </div>
                  </div>
                </div>
              </div>
          </div>';
      }

      echo "200::".$html;
    }
    else
    {
      echo "400";
    }
  }
  else
  {
    echo "400";
  }
}

//removeTaggedFriend function start
public function actionRemoveTaggedFriend()
{
  $member_id = $_REQUEST['member_id'];
  $friend_id_arr = explode(",",$_REQUEST['friend_id']);
  if(($key = array_search($member_id, $friend_id_arr)) !== false)
  {
    unset($friend_id_arr[$key]);
  }

  echo "200::".implode(",",$friend_id_arr);
}

// getLocation function start
public function actionGetLocation()
{
  $location = $_REQUEST['location'];
  $html = '';

  if(!empty($location))
  {
    $location_arr = explode("::",$location);
    $i = 0;

    foreach ($location_arr as $val)
    {
      if($i==0)
      {
        $class = 'd-ft-bor';
        $i++;
      }
      else
      {
        $class = '';
      }

      $html .= '<div class="clearfix pop-pad '.$class.'">
            <div class="pbl-ewih mar-none">
              <div class="left-tex">
                <div class="flot-left mt10">
                  <strong><span onclick="tagLocation(this);">'.$val.'</span></strong>
                </div>
              </div>
            </div>
          </div>';
    }

    echo "200::".$html;
  }
  else
  {
    echo "400::".$html;
  }
}

// tagLocation function start
public function actionTagLocation()
{
  $location = $_REQUEST['location'];
  $geometry = $this->getLocationGeometry($location);
  $html = '';

  if(!empty($location))
  {
    $html .= '<div class="flt-ion-tb">
          <div class="row">
            <div class="col-md-2"><a href="javascript:void(0);" onclick="removeTagLocation();">X</a></div>
            <div class="col-md-10">
              <div class="left-tex">
                <div class="flot-left">
                  <strong>'.$location.'</strong>
                </div>
              </div>
            </div>
          </div>
        </div>';

    echo "200::".$html."::".$location."::".$geometry;
  }
  else
  {
    echo "400::".$html;
  }
}

public function getLocationGeometry($address)
{
  $lat = "";
  $lng = "";

  $address = str_replace (" ", "+", $address);

  $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";

  // $header_resp = $this->get_http_response_code($url);

  // if($header_resp != "404")
  // {
    $ch = curl_init($url);
             curl_setopt_array($ch, array(
             CURLOPT_URL            => $url,
             CURLOPT_RETURNTRANSFER => TRUE,
             CURLOPT_TIMEOUT        => 30,
             CURLOPT_USERAGENT      => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
             CURLOPT_SSL_VERIFYPEER =>0,
             CURLOPT_SSL_VERIFYHOST => 0
         ));

    $output = curl_exec($ch);

    $geoloc = json_decode($output, true);

    curl_close($ch);
    // echo "<pre>";
    // print_r($output);
    // exit;
    if(!empty($output) && is_array($geoloc) && !empty($geoloc) && $geoloc['status']=='OK')
    {
      if( is_array($geoloc['results']) && !empty($geoloc['results']) && is_array($geoloc['results'][0]) && !empty($geoloc['results'][0]))
      {
        if( is_array($geoloc['results'][0]['geometry']) && !empty($geoloc['results'][0]['geometry']) && is_array($geoloc['results'][0]['geometry']['location']) && !empty($geoloc['results'][0]['geometry']['location']))
        {
          if( array_key_exists('lat',$geoloc['results'][0]['geometry']['location'] ) && array_key_exists('lng',$geoloc['results'][0]['geometry']['location'] ))
          {
            $lat = $geoloc['results'][0]['geometry']['location']['lat'];
            $lng = $geoloc['results'][0]['geometry']['location']['lng'];
          }
        }
      }
    }
  // }

  return $lat.'::'.$lng;
}

// Create Post Start
public function actionCreatePost()
{
  // echo "<pre>";
  // print_r($_REQUEST);
  // print_r(base64_decode($_REQUEST['post']));
  // exit;
    $user_post = $_REQUEST['post'];
    $member_id = ApplicationSessions::run()->read('member_id');
    $post_contains = preg_replace('/(?<! )[#,@,\n]/', ' $0', $user_post);
    $post = explode(" ", $post_contains);

    $tag_count=0;
    $tag_found_in_post ='';
    $in_post_tag_vnt= array();
    for($i=0; $i< count($post); $i++)
    {
      if(strspn($post[$i],"#"))
      {
        $in_post_tag_vnt[$tag_count] = $post[$i];
        $tag_count++;
      }
    }

    if(count(array_unique($in_post_tag_vnt))<count($in_post_tag_vnt))
    {
      $resp_code = Yii::app()->params['BAD_REQUEST'];
      $resp = array('code'=>$resp_code,'msg'=>Yii::app()->params[$resp_code],'data'=>'Unique Tags require in post');
    }
    else
    {

      if($tag_count=0 || $tag_count<=5 && $tag_count>=0)
      {
        if(!empty($_REQUEST['post_id']))
        {
          $model = Post::model()->findByPk($_REQUEST['post_id']);
          $model->updated_on = time();
        }
        else
        {
          $model = new Post;
          $model->added_on = time();
          $model->updated_on = time();
        }

        $model->member_id = (!empty($_REQUEST['member_id'])) ? $_REQUEST['member_id'] : $member_id;
        $model->post 	  = base64_encode($_REQUEST['post']);

        if(!empty($_REQUEST['tagged_location_name']))
        {
          $model->location  = $_REQUEST['tagged_location_name'];

          /****/
            $location_name = $_REQUEST['tagged_location_name'];
              $address_array = array();
              $loc_condition = 'active_status="S" and status="1" and location_name = "'.$_REQUEST['tagged_location_name'].'"';

              if(empty($_REQUEST['latitude']) && empty($_REQUEST['longitude']) )
              {
                $address = $this->getLocation($location_name);
                $address_array = explode(',',$address);
              }

              if(!empty($address_array))
              {
                $model->latitude =(!empty($address_array[0])) ? round($address_array[0],3) : "";
                $model->longitude =(!empty($address_array[1])) ? round($address_array[1],3) : "";

                $latitude =(!empty($address_array[0])) ? round($address_array[0],3) : "";
                $longitude =(!empty($address_array[1])) ? round($address_array[1],3) : "";

                if(!empty($latitude) && !empty($longitude))
                {
                  $loc_condition .=' or (latitude='.$latitude.' and longitude='.$longitude.')';
                }
              }
              $location_master = LocationMaster::model()->find(array('condition'=>$loc_condition));

              if(empty($location_master))
              {
                $location_master = new LocationMaster;
                $location_master->location_name = $location_name;
                $location_master->latitude 		= (!empty($latitude))  ? $latitude  : $location_master->latitude;
                $location_master->longitude 	= (!empty($longitude)) ? $longitude : $location_master->longitude;
                $location_master->added_on		= time();
                $location_master->updated_on	= time();

                $location_master->save();
              }
              $model->location_mstr_id = $location_master->location_master_id;
          /****/
        }

        if(!empty($_REQUEST['title']))
        {
          $model->title = base64_encode($_REQUEST['title']);
        }

        if(!empty($_REQUEST['friend_id']))
        {
          $friends_array = explode(",",$_REQUEST['friend_id']);
        }

      if($model->save(false))
      {

        for($i=0; $i< count($post); $i++)
        {
          if(strspn($post[$i],"#"))
          {
            $tags = Tags::model()->find(array('condition'=>'tags="'.$post[$i].'"'));

            if(empty($tags))
            {
              $tags =  new Tags;
              $tags->tags=  preg_replace('~[^a-z0-9#]+~i', '', $post[$i]);
              $tags->added_on = time();
              $tags->updated_on = time();
              $tags->save(false);
            }
            else
            {
              $tags->retag_count= $tags->retag_count+1;
              $tags->updated_on = time();
              $tags->save(false);
            }
          }
        }

          /// if new #tag is in post follow tahat tag
        /*	//commented on 06-sep-17 by sunny sir intruction
          for($i=0; $i< count($post); $i++)
          {
            if(strspn($post[$i],"#"))
            {
              $tags = Tags::model()->find(array('condition'=>'tags="'.$post[$i].'"'));

              $tagfollow= TagFollow::model()->find(array('condition'=>'tag_id="'.$tags->tags_id.'" and member_id="'.$_REQUEST['member_id'].'"'));

              if(empty($tagfollow))
              {
                $tagfollow =  new TagFollow;
                $tagfollow->tag_id =$tags->tags_id;
                $tagfollow->tag =$tags->tags;
                $tagfollow->member_id =$_REQUEST['member_id'];
                $tagfollow->added_on = time();
                $tagfollow->updated_on = time();
                $tagfollow->save(false);

                //storeUserActivity
                    $this->storeUserActivity($_REQUEST['member_id'],"Followed ".$tags->tags ,"TF","TagFollow",$tagfollow->tag_follow_id);
              }

            }
          } */

        //end

        //save tage friends

        if(!empty($friends_array))
        {
          if(!empty($_REQUEST['post_id']))
          {
            PostFriends::model()->deleteAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
          }
          foreach($friends_array as $val)
          {
            $postFriends = new PostFriends;

            $postFriends->friend_id = $val;
            $postFriends->post_id = $model->post_id;
            $postFriends->added_on = time();
            $postFriends->updated_on = time();
            $postFriends->save();
          }
        }

        //insert rec in PostTags

        for($i=0; $i< count($post); $i++)
        {
          if(strspn($post[$i],"#"))
          {
            $postTags =  PostTags::model()->find(array('condition'=>'tags="'.$post[$i].'" and post_id="'.$model->post_id.'" and tags_id="'.$tags->tags_id.'"'));

            $tags = Tags::model()->find(array('condition'=>'tags="'.$post[$i].'"'));

            if(!empty($tags))
            {
              if(empty($postTags))
              {
                $postTags =  new PostTags;

                $postTags->tags=$post[$i];
                $postTags->post_id=$model->post_id;
                $postTags->tags_id=$tags->tags_id;
                $postTags->added_on = time();
                $postTags->updated_on = time();
                $postTags->save();
              }
              else
              {
                $postTags->retag_count= $postTags->retag_count+1;
                $postTags->updated_on = time();
                $postTags->save();
              }
            }

          }
        }

        $images_name = '';
        // Update already exist images in post
        if(!empty($_REQUEST['exist_images']))
        {
          foreach($_REQUEST['exist_images'] as $exist_img)
          {
            $image_path = 'http://iresolveservices.com/tagswag/upload/post_attachment/';
            $url = $exist_img['url'];

            $url = str_replace($image_path,"",$url);
            //update caption
            if(!empty($exist_img['url']))
            {
              $data_update = PostAttachment::model()->find(array('condition'=>'active_status="S"  and type="P" and attachment ="'.$url.'" and post_id ='.$_REQUEST['post_id']));

              if(!empty($data_update))
              {
                 PostAttachment::model()->UpdateByPK($data_update->post_attachment_id,array('caption'=>$exist_img['caption']));
              }
            }
            if(empty($images_name))
            {
              $images_name .= '"'.$url.'"';
            }
            else
            {
              $images_name .= ',"'.$url.'"';
            }

          }
        }
          ///delete image if not present in $images_name

            if(!empty($_REQUEST['post_id']) && !empty($images_name) && !empty($images_name))
            {
              PostAttachment::model()->deleteAll(array('condition'=>'active_status="S" and status="1" and type="P" and attachment  NOT IN('.$images_name.') and post_id='.$_REQUEST['post_id']));
            }

            /*Update already exist videos*/

              $vides_name = '';
              // Update already exist images in post
              if(!empty($_REQUEST['exist_videos']))
              {
                foreach($_REQUEST['exist_videos'] as $exist_video)
                {
                  $image_path = 'http://iresolveservices.com/tagswag/upload/post_attachment/';
                  $url = $exist_video['url'];

                  $url = str_replace($image_path,"",$url);
                  if(!empty($exist_video['url']))
                    {
                      $data_update = PostAttachment::model()->find(array('condition'=>'active_status="S"  and type="V" and attachment ="'.$url.'" and post_id ='.$_REQUEST['post_id']));

                      if(!empty($data_update))
                      {
                         PostAttachment::model()->UpdateByPK($data_update->post_attachment_id,array('caption'=>$exist_video['caption']));
                      }
                    }
                  if(empty($vides_name))
                  {
                    $vides_name .= '"'.$url.'"';
                  }
                  else
                  {
                    $vides_name .= ',"'.$url.'"';
                  }

                }
              }
                ///delete image if not present in $images_name

                  if(!empty($_REQUEST['post_id']) && !empty($vides_name))
                  {
                    PostAttachment::model()->deleteAll(array('condition'=>'active_status="S" and status="1" and type="V" and attachment  NOT IN('.$vides_name.') and post_id='.$_REQUEST['post_id']));
                  }
            /*Update already exist videos end*/
        // if(!empty($_FILES['post_images']['name']))
        // {
          // foreach($_FILES['post_images']['name'] as $key=>$val)
          // {
            // $tmpFilePath = $_FILES['post_images']['tmp_name'][$key];
            // $caption_val  = $_REQUEST['caption_img'][$key];
            // if ($tmpFilePath != "")
            // {
              // $image_path = Yii::app()->basePath . '/../upload/post_attachment/';
              // $ext = explode(".",$_FILES['post_images']['name'][$key]);
              // $image_name = rand().time().".".$ext[1];

              // $newFilePath = $image_path . $image_name;

              // if(move_uploaded_file($tmpFilePath, $newFilePath))
              // {
                  // $post_Attach_img_model = new PostAttachment;
                  // $post_Attach_img_model->attachment = $image_name;
                  // $post_Attach_img_model->post_id=$model->post_id;
                  // $post_Attach_img_model->type="P";
                  // if(!empty($_REQUEST['caption_img'][$key]))
                  // {
                    // $post_Attach_img_model->caption= $caption_val;
                  // }


                  // $post_Attach_img_model->save();
              // }
            // }
          // }

        // }

        //save images if exist in post
        if(!empty($_FILES['vpb-data-file']['name']))
        {
          if(!is_dir("upload/post_attachment/"))
          {
            mkdir("upload/post_attachment/" , 0777,true);
          }

          foreach($_FILES['vpb-data-file']['name'] as $key=>$val)
          {
            $tmpFilePath = $_FILES['vpb-data-file']['tmp_name'][$key];
            $caption_val  = (!empty($_REQUEST['caption_img'][$key]))?$_REQUEST['caption_img'][$key]:'';
            if ($tmpFilePath != "")
            {
              $image_path = Yii::app()->basePath . '/../upload/post_attachment/';
              $ext = explode(".",$_FILES['vpb-data-file']['name'][$key]);
              $image_name = time().".".$ext[1];

              $newFilePath = $image_path . $image_name;

              if(move_uploaded_file($tmpFilePath, $newFilePath))
              {
                  $post_Attach_img_model = new PostAttachment;
                  $post_Attach_img_model->attachment = $image_name;
                  $post_Attach_img_model->post_id=$model->post_id;
                  $post_Attach_img_model->type="P";
                  if(!empty($_REQUEST['caption_img'][$key]))
                  {
                    $post_Attach_img_model->caption= $caption_val;
                  }


                  $post_Attach_img_model->save();
              }
            }
          }
        }

        // //save video if exist in post
        if(!empty($_FILES['post_video']['name']))
        {
          if(!is_dir("upload/post_attachment/"))
          {
            mkdir("upload/post_attachment/" , 0777,true);
          }

          foreach($_FILES['post_video']['name'] as $key=>$val)
          {

            $tmpFilePath = $_FILES['post_video']['tmp_name'][$key];

            if ($tmpFilePath != "")
            {
              $image_path = Yii::app()->basePath . '/../upload/post_attachment/';
              $ext = explode(".",$_FILES['post_video']['name'][$key]);
              $image_name = rand().time().".".$ext[1];

              $newFilePath = $image_path . $image_name;
              $caption_val  = $_REQUEST['caption_video'][$key];
              if(move_uploaded_file($tmpFilePath, $newFilePath))
              {
                  $post_Attach_img_model = new PostAttachment;
                  $post_Attach_img_model->attachment = $image_name;
                  $post_Attach_img_model->post_id=$model->post_id;
                  $post_Attach_img_model->type="V";
                  if(!empty($_REQUEST['caption_video'][$key]))
                  {
                    $post_Attach_img_model->caption= $caption_val;
                  }
                  $post_Attach_img_model->save(false);

              }
            }
          }
        }

        //save Data in ClubPost start
        if(!empty($_REQUEST['club_id']))
        {
          if(!empty($_REQUEST['post_id']))
          {
            $model_club_post = ClubPost::model()->find(array('condition'=>'club_id="'.$_REQUEST['club_id'].'" and memer_id="'.$model->member_id.'" and post_id="'.$model->post_id.'"'));
          }
          else
          {
            $model_club_post = new ClubPost;
          }

          $model_club_post->club_id=  $_REQUEST['club_id'];
          $model_club_post->post_id=  $model->post_id;
          $model_club_post->memer_id=  $model->member_id;
          $model_club_post->added_on=  time();
          $model_club_post->save();
        }

        //save Data in ClubPost End

        //save data in a post_user_tagin start
        for($i=0; $i< count($post); $i++)
        {
          if(strspn($post[$i],"@"))
          {
            $userName 	 = str_replace("@","",$post[$i]);


            $member_data = Member::model()->find(array('condition'=>'username="'.$userName.'"'));

            $is_allready_taged = PostUserTagin::model()->find(array('condition'=>'active_status="S" and member_id='.$member_data->member_id.' and post_id ='.$model->post_id));

            if(!empty($member_data) && empty($is_allready_taged))
            {
              $model_taged_user = new PostUserTagin;
              $model_taged_user->post_id		=  $model->post_id;
              $model_taged_user->member_id	=  $member_data->member_id;
              $model_taged_user->user_name	=  $member_data->username;
              $model_taged_user->added_on		=  time();
              $model_taged_user->updated_on	=  time();
              $model_taged_user->save();

              /**/


                if(!empty($member_data->device_token))
                {
                  $profile_pic= $this->getProfilePic($member_data->member_id);

                  $message = $model->member->username.' tagged you in a post';
                  $data =	array (
                      "message"=>$message,
                       "post_id" =>$model->post_id,
                       "method" => "getPostData",
                       "user_name" => $model->member->username,
                       "post_description" =>$model->post,
                       "profile_pic" =>$profile_pic,
                       "member_id" =>$model->member_id,
                        );
                  $notification_status = 	$this->sendMessage($message,$data,$member_data->device_token);
                }

              //storeUserActivity
                $this->storeUserActivity($_REQUEST['member_id'],"tagged you in post","TP","Post",$model->post_id,$member_data->member_id,$model->post_id,'P');
            }
          }
        }

        //save data in a post_user_tagin end

        $resp_code = Yii::app()->params['SUCCESS'];
        $resp = array('code'=>$resp_code,'msg'=>'Post Created successfully','data'=>null);

        $postTag = PostTags::model()->find(array('select'=>'group_concat(tags) as tags ','condition'=>'post_id='.$model->post_id));

        //storeUserActivity
          $this->storeUserActivity($_REQUEST['member_id'],"Posted ".$postTag->tags,"PC","Post",$model->post_id,'',$model->post_id,'P');

        echo '200::Post Created successfully';

      }
      else if($tag_count==0)
      {
        $resp_code = Yii::app()->params['BAD_REQUEST'];
        $resp = array('code'=>$resp_code,'msg'=>Yii::app()->params[$resp_code],'data'=>'Atleast one tage is require in post');
      }
      else if($tag_count>5)
      {
        $resp_code = Yii::app()->params['BAD_REQUEST'];
        $resp = array('code'=>$resp_code,'msg'=>Yii::app()->params[$resp_code],'data'=>'More than 5 tag not permited in a post');
      }
      else
      {
        $resp_code = Yii::app()->params['BAD_REQUEST'];
          $resp = array('code'=>$resp_code,'msg'=>Yii::app()->params[$resp_code],'data'=>null);
      }
    }
  }

}
/*public function actionCreatePost()
{
  $post_contains = preg_replace('/(?<! )[#,@,\n]/', ' $0', $_REQUEST['post']);
  $post = explode(" ", $post_contains);

  $tag_count=0;
  $tag_found_in_post ='';
  $in_post_tag_vnt= array();
  for($i=0; $i< count($post); $i++)
  {
    if(strspn($post[$i],"#"))
    {
        $in_post_tag_vnt[$tag_count] = $post[$i];
        $tag_count++;
    }
  }

  // echo "<pre>";
  // print_r($in_post_tag_vnt);

  if(count(array_unique($in_post_tag_vnt))<count($in_post_tag_vnt))
  {
    echo "400::Unique Tags require in post";
  }
  else
  {
    if($tag_count<=5 && $tag_count!=0)
    {
      if(!empty($_REQUEST['post_id']))
      {
        $model = Post::model()->findByPk($_REQUEST['post_id']);
      }
      else
      {
        $model = new Post;
      }

      $model->member_id = $_REQUEST['member_id'];
      $model->post 	  = $_REQUEST['post'];

      if(!empty($_REQUEST['location']))
      {
        $model->location = $_REQUEST['location'];
        $model->latitude = $_REQUEST['latitude'];
        $model->longitude = $_REQUEST['longitude'];
      }

      if(!empty($_REQUEST['post_title']))
      {
        $model->title = $_REQUEST['post_title'];
      }

      $model->added_on = time();
      $model->updated_on = time();

      if(!empty($_REQUEST['friend_id']))
      {
        $friends_array = explode(",",$_REQUEST['friend_id']);
      }

      if($model->save())
      {
        for($i=0; $i< count($post); $i++)
        {
          if(strspn($post[$i],"#"))
          {
            $tags = Tags::model()->find(array('condition'=>'tags="'.$post[$i].'"'));

            if(empty($tags))
            {
              $tags =  new Tags;
              $tags->tags=  preg_replace('~[^a-z0-9#]+~i', '', $post[$i]);
              $tags->added_on = time();
              $tags->updated_on = time();
              $tags->save(false);
            }
            else
            {
              $tags->retag_count= $tags->retag_count+1;
              $tags->updated_on = time();
              $tags->save(false);
            }
          }
        }

        /// if new #tag is in post follow tahat tag

        for($i=0; $i< count($post); $i++)
        {
          if(strspn($post[$i],"#"))
          {
            $tags = Tags::model()->find(array('condition'=>'tags="'.$post[$i].'"'));

            $tagfollow= TagFollow::model()->find(array('condition'=>'tag_id="'.$tags->tags_id.'" and member_id="'.$_REQUEST['member_id'].'"'));

            if(empty($tagfollow))
            {
              $tagfollow =  new TagFollow;
              $tagfollow->tag_id =$tags->tags_id;
              $tagfollow->tag =$tags->tags;
              $tagfollow->member_id =$_REQUEST['member_id'];
              $tagfollow->added_on = time();
              $tagfollow->updated_on = time();
              $tagfollow->save(false);

              //storeUserActivity
                  $this->storeUserActivity($_REQUEST['member_id'],"Start Follow ".$tags->tags." Tag","TF","TagFollow",$tagfollow->tag_follow_id);
            }

          }
        }

        //end

        //save tage friends

        if(!empty($friends_array))
        {
          if(!empty($_REQUEST['post_id']))
          {
            PostFriends::model()->deleteAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
          }
          foreach($friends_array as $val)
          {
            $postFriends = new PostFriends;

            $postFriends->friend_id = $val;
            $postFriends->post_id = $model->post_id;
            $postFriends->added_on = time();
            $postFriends->updated_on = time();
            $postFriends->save();
          }
        }

        //insert rec in PostTags

        for($i=0; $i< count($post); $i++)
        {
          if(strspn($post[$i],"#"))
          {
            $postTags =  PostTags::model()->find(array('condition'=>'tags="'.$post[$i].'" and post_id="'.$model->post_id.'" and tags_id="'.$tags->tags_id.'"'));

            $tags = Tags::model()->find(array('condition'=>'tags="'.$post[$i].'"'));

            if(!empty($tags))
            {
              if(empty($postTags))
              {
                $postTags =  new PostTags;

                $postTags->tags=$post[$i];
                $postTags->post_id=$model->post_id;
                $postTags->tags_id=$tags->tags_id;
                $postTags->added_on = time();
                $postTags->updated_on = time();
                $postTags->save();
              }
              else
              {
                $postTags->retag_count= $postTags->retag_count+1;
                $postTags->updated_on = time();
                $postTags->save();
              }
            }

          }
        }
        //save images if exist in post
        if(!empty($_FILES['vpb-data-file']['name']))
        {
          if(!is_dir("upload/post_attachment/"))
          {
            mkdir("upload/post_attachment/" , 0777,true);
          }

          foreach($_FILES['vpb-data-file']['name'] as $key=>$val)
          {
            $tmpFilePath = $_FILES['vpb-data-file']['tmp_name'][$key];
            $caption_val  = (!empty($_REQUEST['caption_img'][$key]))?$_REQUEST['caption_img'][$key]:'';
            if ($tmpFilePath != "")
            {
              $image_path = Yii::app()->basePath . '/../upload/post_attachment/';
              $ext = explode(".",$_FILES['vpb-data-file']['name'][$key]);
              $image_name = time().".".$ext[1];

              $newFilePath = $image_path . $image_name;

              if(move_uploaded_file($tmpFilePath, $newFilePath))
              {
                  $post_Attach_img_model = new PostAttachment;
                  $post_Attach_img_model->attachment = $image_name;
                  $post_Attach_img_model->post_id=$model->post_id;
                  $post_Attach_img_model->type="P";
                  if(!empty($_REQUEST['caption_img'][$key]))
                  {
                    $post_Attach_img_model->caption= $caption_val;
                  }


                  $post_Attach_img_model->save();
              }
            }
          }
        }


        //save video if exist in post
        if(!empty($_FILES['post_video']['name']))
        {
          if(!is_dir("upload/post_attachment/"))
          {
            mkdir("upload/post_attachment/" , 0777,true);
          }

          foreach($_FILES['post_video']['name'] as $key=>$val)
          {

            $tmpFilePath = $_FILES['post_video']['tmp_name'][$key];

            if ($tmpFilePath != "")
            {
              $image_path = Yii::app()->basePath . '/../upload/post_attachment/';

              $ext = explode(".",$_FILES['post_video']['name'][$key]);
              $image_name = time().".".$ext[1];

              $newFilePath = $image_path . $image_name;
              $caption_val  = $_REQUEST['caption_video'][$key];
              if(move_uploaded_file($tmpFilePath, $newFilePath))
              {
                  $post_Attach_img_model = new PostAttachment;
                  $post_Attach_img_model->attachment = $image_name;
                  $post_Attach_img_model->post_id=$model->post_id;
                  $post_Attach_img_model->type="V";
                  if(!empty($_REQUEST['caption_video'][$key]))
                  {
                    $post_Attach_img_model->caption= $caption_val;
                  }
                  $post_Attach_img_model->save(false);

              }
            }
          }
        }

        //save Data in ClubPost start
        if(!empty($_REQUEST['club_id']))
        {
          if(!empty($_REQUEST['post_id']))
          {
            $model_club_post = ClubPost::model()->find(array('condition'=>'club_id="'.$_REQUEST['club_id'].'" and memer_id="'.$model->member_id.'" and post_id="'.$model->post_id.'"'));
          }
          else
          {
            $model_club_post = new ClubPost;
          }

          $model_club_post->club_id=  $_REQUEST['club_id'];
          $model_club_post->post_id=  $model->post_id;
          $model_club_post->memer_id=  $model->member_id;
          $model_club_post->added_on=  time();
          $model_club_post->save();
        }

        //save Data in ClubPost End
        //save data in a post_user_tagin start
        for($i=0; $i< count($post); $i++)
        {
          if(strspn($post[$i],"@"))
          {
            $userName 	 = str_replace("@","",$post[$i]);
            $member_data = Member::model()->find(array('condition'=>'username="'.$userName.'"'));
            if(!empty($member_data))
            {
              $model_taged_user = new PostUserTagin;
              $model_taged_user->post_id		=  $model->post_id;
              $model_taged_user->member_id	=  $member_data->member_id;
              $model_taged_user->user_name	=  $member_data->username;
              $model_taged_user->added_on		=  time();
              $model_taged_user->updated_on	=  time();
              $model_taged_user->save();
            }
          }
        }
        //save data in a post_user_tagin end

        $postTag = PostTags::model()->find(array('select'=>'group_concat(tags) as tags ','condition'=>'post_id='.$model->post_id));
        //storeUserActivity
          $this->storeUserActivity($model->member_id,"Posted ".$postTag->tags,"PC","Post",$model->post_id);

        echo '200::Post Created successfully';
      }

    }
    else if($tag_count==0)
    {
      echo '400::Atleast one tage is require in post';
    }
    else if($tag_count>5)
    {
      echo '400::More than 5 tag not permited in a post';
    }
    else
    {
      echo '400::Something Went Wrong';
    }
  }
} */

// Share Post

public function actionSharePost()
{
  $member_id = $_REQUEST['member_id'];
  $post_id = $_REQUEST['post_id'];
  $type = $_REQUEST['type'];


  $post = Post::model()->findByPk($_REQUEST['post_id']);

  if(!empty($post))
  {

  $model = new PostShare;
  $model->post_id = $post_id;
  $model->from_id = $member_id;
  $model->type = $type;
  $model->save(false);

    //PushNotification start
      $post_auther_data = $this->memberInfoByMemberId($post->member_id);

        if($_REQUEST['member_id'] != $post->member_id)
        {
          if(!empty($post_auther_data->device_token))
          {
                $profile_pic= $this->getProfilePic($_REQUEST['member_id']);

                $message = 'shared your post';
                $data =	array (
                       "message"=>$message,
                       "post_id" =>$post->post_id,
                       "method" => "getPostData",
                       "user_name" => $model->member->username,
                       "member_first_name"=>(!empty($model->member->first_name)) ?  base64_decode($model->member->first_name) : "",
                       "member_last_name"=>(!empty($model->member->last_name)) ?  base64_decode($model->member->last_name) : "",
                       "post_description" =>$post->post,
                       "profile_pic" =>$profile_pic,
                       "member_id" =>$_REQUEST['member_id'],
                      );

                $notification_status = 	$this->sendMessage($message,$data,$post_auther_data->device_token);
          }
        }
    //PushNotification start

    //storeUserActivity
        $this->storeUserActivity($_REQUEST['member_id'],"Shared ".$post_auther_data->username."’s post ","S","PostShare",$model->post_share_id,$post->member_id,$post->post_id,'P');
  }


  echo '200';
}

// Comment Post

public function actionPostComment()
{
  $member_id 	= $_REQUEST['member_id'];
  $post_id 	= $_REQUEST['post_id'];
  $comment 	= base64_encode($_REQUEST['comment']);
  $post 		= Post::model()->findByPk($_REQUEST['post_id']);

  $model 				= new PostComment;
  $model->post_id 	= $post_id;
  $model->member_id 	= $member_id;
  $model->type 		= "C";
  $model->comment 	= $comment;
  $model->added_on 	= time();
  $model->updated_on 	= time();
  $model->save(false);

  /* if any one tag user in comment */
          $comment = explode(" ",$_REQUEST['comment']);
          for($i=0; $i< count($comment); $i++)
            {
              if(strspn($comment[$i],"@"))
              {
                $userName 	 = str_replace("@","",$comment[$i]);

                $member_data =$this->memberInfoByuserName($userName);

                if(!empty($member_data))
                {
                    if(!empty($member_data->device_token))
                    {
                      $profile_pic= $this->getProfilePic($member_data->member_id);

                      $type_comment = ($model->type == "C") ? "comment" : "reply";
                      $message = 'tagged you in a '.$type_comment;
                      $data =	array (
                               "message"			=>$message,
                               "post_id" 			=>$model->post_id,
                               "method" 			=>"getPostData",
                               "user_name" 		=>$model->member->username,
                               "member_first_name"=>(!empty($model->member->first_name)) ?  base64_decode($model->member->first_name) : "",
                               "member_last_name"	=>(!empty($model->member->last_name))  ?  base64_decode($model->member->last_name)  : "",  "post_description" =>$model->post,
                               "profile_pic" 		=>$profile_pic,
                               "member_id" 		=>$model->member_id,
                              );
                      $notification_status = 	$this->sendMessage($message,$data,$member_data->device_token);
                    }

                  ///store in user activity (Notification)
                    $this->storeUserActivity($_REQUEST['member_id'],"tagged you in a ".$type_comment .$model->member->username."’s post","TC","PostComment",$model->post_comment_id,$member_data->member_id);
                }
              }
            }
          /* if any one tag user in comment */

  //storeUserActivity
    $this->storeUserActivity($_REQUEST['member_id'],"Commented on ".$post->member->username."’s post","C","PostComment",$model->post_comment_id,$post->member_id);

    $comment_count = PostComment::model()->count(array('condition'=>'post_id='.$post_id));

    echo '200::Comment Posted Successfully::'.$comment_count.'::'.$post_id;
}

// Like Post

public function actionPostLike()
{
  $member_id = $_REQUEST['member_id'];
  $post_id = $_REQUEST['post_id'];
  $like_data = PostLike::model()->find(array('condition'=>'post_id='.$post_id.' and member_id='.$member_id));

  $post = Post::model()->findByPk($_REQUEST['post_id']);

  if(!empty($like_data))
  {
    PostLike::model()->deleteByPk($like_data->post_like_id);
  }
  else
  {
    $model = new PostLike;
    $model->post_id = $post_id;
    $model->member_id = $member_id;
    $model->type = "L";
    $model->added_on = time();
    $model->updated_on = time();
    $model->save(false);

    //storeUserActivity
      $this->storeUserActivity($_REQUEST['member_id']," Liked ".$post->member->username."'s post","L","PostLike",$model->post_like_id,$post->member_id);
  }

  $like_count = PostLike::model()->count(array('condition'=>'post_id='.$post_id));
  echo $like_count;
}

// getComment Start
public function actionGetComment()
{
  $post_id 	= $_REQUEST['post_id'];
  $html 		= '';
  $member_id 	= ApplicationSessions::run()->read('member_id');
  if(!empty($post_id))
  {
    $comment_data = PostComment::model()->findAll(array('condition'=>'type="C" and post_id='.$post_id));

    if(!empty($comment_data))
    {
      foreach($comment_data as $val)
      {
        $member = Member::model()->find(array('condition'=>'member_id='.$val->member_id));

        $profile_pic = $this->getProfilePic($val->member_id);

        $child_comment = PostComment::model()->findAll(array('condition'=>'type="R" and parent_id='.$val->post_comment_id));

        if(!empty($child_comment))
        {
          foreach($child_comment as $val_child_comment)
          {
              $child_comment_member = Member::model()->find(array('condition'=>'member_id='.$val_child_comment->member_id));

            $child_comment_member_profile_pic = $this->getProfilePic($val_child_comment->member_id);

            $child_comment_comment = "'".$val_child_comment->comment."'";
            $child_comment_data .= '
              <div class="flt-ion-tb" style="width:100%!important;margin:0px!important;">
              <div class="left-tex" style="margin-left:50px;">
                <div class="flot-left dp-icon-tb">
                  <img class="img-responsive" src="'.$child_comment_member_profile_pic.'">
                </div>
                    <div class="flot-left">
                      <a href='. Yii::app()->createUrl("site/friendTimeLine?friend_id=".$val_child_comment->member_id).' target="_blank">
                        <strong>'.base64_decode($child_comment_member->first_name).' '.base64_decode($child_comment_member->last_name).'</strong>
                      </a>
                      <br>
                      '.base64_decode($val_child_comment->comment).'
                      <br>
                    <span onclick="CommentLike('.$val_child_comment->post_comment_id.')">	Like ('.$val_child_comment->comment_like_count.') </span> ';
                    if($member_id == $val_child_comment->member_id)
                    {
                      $child_comment_data .= '<span onclick="deleteComment('.$val_child_comment->post_comment_id.')"> Delete</span>
                    <span onclick="editComment('.$val_child_comment->post_comment_id.','.$child_comment_comment.')"> Edit </span>';
                    }

                $child_comment_data .= '	</div>

                </div>
                <div class="flot-right" style="margin-right:2%">'.Controller::timeago($val_child_comment->added_on).'</div>

              </div>';
          }
        }
        else
        {
          $child_comment_data = '';
        }
        $comment = "'".base64_decode($val->comment)."'";

        $html .= '<div class="flt-ion-tb" style="width:98%!important;margin:0px!important;">

            <div class="left-tex">
              <div class="row">
              <div class="col-md-2">
                <div class="flot-left dp-icon-tb">
                  <img class="img-responsive" src="'.$profile_pic.'">
                </div>
              </div>

              <div class="col-md-10">
                <div class="flot-left">
                  <a href='. Yii::app()->createUrl("site/friendTimeLine?friend_id=".$val->member_id).' target="_blank">
                    <strong>'.base64_decode($member->first_name).' '.base64_decode($member->last_name).'</strong>
                  </a>
                  <br>
                  '.base64_decode($val->comment).'
                  <br>
                <span onclick="CommentLike('.$val->post_comment_id.')">	Like ('.$val->comment_like_count.') </span>
                <span onclick="commentReply('.$post_id.','.$val->post_comment_id.')">Reply </span>';
                if($member_id == $val->member_id)
                      {
                      $html .= 	'<span onclick="deleteComment('.$val->post_comment_id.')"> Delete</span>
                <span onclick="editComment('.$val->post_comment_id.','.$comment.')"> Edit </span>';
                      }

              $html .= 	'</div>
                 </div>
               </div>
            </div>
            <div class="flot-right" style="margin-right:2%">'.Controller::timeago($val->added_on).'</div>

            '.$child_comment_data.'
          </div>';

        $child_comment_data = '';
      }
    }
    else
    {
      $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Comment Found</strong>
              </div>
            </div>
          </div>';
    }
  }
  echo $html;
}

// getLike Start
public function actionGetLike()
{
  $post_id = $_REQUEST['post_id'];
  $html = '';

  if(!empty($post_id))
  {
    $like_data = PostLike::model()->findAll(array('condition'=>'post_id='.$post_id));

    if(!empty($like_data))
    {
      foreach($like_data as $val)
      {
        $member = Member::model()->find(array('condition'=>'member_id='.$val->member_id));
        $profile_pic =	 $this->getProfilePic($val->member_id);


        $html .= '<div class="flt-ion-tb" style="width:98%!important;margin-bottom:2px!important;">

            <div class="left-tex">
              <div class="row"><div class="col-md-2">
                <div class="flot-left dp-icon-tb">
                  <img class="img-responsive" src="'.$profile_pic.'">
                </div>
              </div>
              <div class="col-md-10">
                <a href='. Yii::app()->createUrl("site/friendTimeLine?friend_id=".$val->member_id).' target="_blank">
                  <div class="flot-left">
                    <strong> '.$member->username.'</strong><br/>
                    '.base64_decode($member->first_name).' '.base64_decode($member->last_name).'
                  </div>
                </a>
              </div>
            </div>
            </div>
            <div class="flot-right" style="margin-right: 2%;">
                '.Controller::timeago($val->added_on).'
              </div>
          </div>';
      }


    }
    else
    {
      $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Likes Found</strong>
              </div>
            </div>
          </div>';
    }
  }

  echo $html;
}

// getSharePost Start
public function actionGetSharePost()
{
  $post_id = $_REQUEST['post_id'];
  $html = '';

  if(!empty($post_id))
  {
    $share_data = PostShare::model()->findAll(array('condition'=>'post_id='.$post_id.' and type="S"'));

    if(!empty($share_data))
    {
      foreach($share_data as $val)
      {
        $member = Member::model()->find(array('condition'=>'member_id='.$val->from_id));

        if(empty($member->profile_pic))
        {
          $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
        }
        else if(!empty($member->profile_pic) && strpos($member->profile_pic,"http")===false)
        {
          $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$member->profile_pic;
        }
        else
        {
          $profile_pic = $member->profile_pic;
        }

        $html .= '<div class="flt-ion-tb" style="width:98%!important;margin-bottom:2px!important;">

            <div class="left-tex">
              <div class="flot-left dp-icon-tb">
                <img class="img-responsive" src="'.$profile_pic.'">
              </div>
              <div class="flot-left">
                <strong>'.$member->first_name.' '.$member->last_name.'</strong>
              </div>
            </div>
          </div>';
      }


    }
    else
    {
      $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No One Share Post yet</strong>
              </div>
            </div>
          </div>';
    }
  }

  echo $html;
}

// getPostRetag Start
public function actionGetPostRetag()
{
  $post_id = $_REQUEST['post_id'];
  $html = '';

  if(!empty($post_id))
  {
    $retag_data = PostRetag::model()->findAll(array('condition'=>'post_id='.$post_id));

    if(!empty($retag_data))
    {
      foreach($retag_data as $val)
      {
        $member = Member::model()->find(array('condition'=>'member_id='.$val->member_id));

        if(empty($member->profile_pic))
        {
          $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
        }
        else if(!empty($member->profile_pic) && strpos($member->profile_pic,"http")===false)
        {
          $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$member->profile_pic;
        }
        else
        {
          $profile_pic = $member->profile_pic;
        }

        $html .= '<div class="flt-ion-tb" style="width:98%!important;margin:0px!important;">

            <div class="left-tex">
              <div class="flot-left dp-icon-tb">
                <img class="img-responsive" src="'.$profile_pic.'">
              </div>
              <div class="flot-left">
                <strong>'.base64_decode($member->first_name).' '.base64_decode($member->last_name).'</strong>
                <br>
                '.$val->tag.'
              </div>
            </div>
          </div>';
      }


    }
    else
    {
      $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Retag Found</strong>
              </div>
            </div>
          </div>';
    }
  }

  echo $html;
}

// Retag Post

public function actionPostRetag()
{
  $member_id = $_REQUEST['member_id'];
  $post_id = $_REQUEST['post_id'];

  $post = Post::model()->findByPk($_REQUEST['post_id']);

  $post_retag = $_REQUEST['post_retag'];

  $post_contains = preg_replace('/(?<! )[#,@]/', ' $0', $_REQUEST['post_retag']);
  $retag_tags = explode(" ", $post_contains);
  $tag_count=0;

  //find post tag and store in one array
  $posted_tags 	= PostTags::model()->findAll(array('condition'=>'post_id="'.$_REQUEST['post_id'].'"'));
  $posted_retag   = PostRetag::model()->findAll(array('condition'=>'post_id="'.$_REQUEST['post_id'].'"'));
  $in_post_tag_vnt= array();
  $iter=0;

  foreach($posted_tags as $val)
  {
    if(strspn($val->tags,"#"))
    {
        $in_post_tag_vnt[$iter] = $val->tags;
        $iter++;
    }
  }

  foreach($posted_retag as $val1)
  {
    if(strspn($val1->tag,"#"))
    {
        $in_post_tag_vnt[$iter] = $val1->tag;
        $iter++;
    }
  }

  for($i=0; $i< count($retag_tags); $i++)
  {
    if(strspn($retag_tags[$i],"#"))
    {
        $in_post_tag_vnt[$iter] = $retag_tags[$i];
        $tag_count++;
        $iter++;
    }
  }


  if(count(array_unique($in_post_tag_vnt))<count($in_post_tag_vnt))
  {
    echo "402::Please Use Unique Tags";
  }
  else
  {
    if($tag_count<=2 && $tag_count!=0)
    {
      $newly_added_tags='';

      for($i=0; $i< count($retag_tags); $i++)
      {
        if(strspn($retag_tags[$i],"#"))
        {
          $newly_added_tags .= $retag_tags[$i]." "; // to store in user activity
          $tags = Tags::model()->find(array('condition'=>'tags="'.$retag_tags[$i].'"'));
          if(empty($tags))
          {
            $tags =  new Tags;
            $tags->tags		  = $retag_tags[$i];
            $tags->added_on   = time();
            $tags->updated_on = time();
            $tags->save(false);
          }
          else
          {
            $tags->retag_count= $tags->retag_count+1;
            $tags->updated_on = time();
            $tags->save(false);
          }

        /*
          $tagfollow	= TagFollow::model()->find(array('condition'=>'tag_id="'.$tags->tags_id.'" and member_id="'.$_REQUEST['member_id'].'"'));

          if(empty($tagfollow))
          {
            $tagfollow 	=  new TagFollow;
            $tagfollow->tag_id 		=$tags->tags_id;
            $tagfollow->tag 		=$tags->tags;
            $tagfollow->member_id 	=$_REQUEST['member_id'];
            $tagfollow->added_on 	= time();
            $tagfollow->updated_on 	= time();
            $tagfollow->save(false);

            //storeUserActivity
            $this->storeUserActivity($_REQUEST['member_id'],"Start Follow ".$tags->tags." Tag","TF","TagFollow",$tagfollow->tag_follow_id);
          }
        */

          $postRetag = new PostRetag;

          $postRetag->post_id 	= $_REQUEST['post_id'];
          $postRetag->member_id 	= $_REQUEST['member_id'];
          $postRetag->tag_id 		= $tags->tags_id;
          $postRetag->tag 		= $retag_tags[$i];
          $postRetag->added_on	= time();
          $postRetag->updated_on	= time();
          $postRetag->save(false);
        }
      }

      //storeUserActivity
          $this->storeUserActivity($_REQUEST['member_id'],"Retagged ".$post->member->username."’s post with ".$newly_added_tags,"RT","PostRetag",$postRetag->post_retag_id,$post->member_id,$post->post_id,'P');

      $retag_count = PostRetag::model()->count(array('condition'=>'post_id='.$_REQUEST['post_id']));
      echo "200::Reatag Post Successfully::".$retag_count.'::'.$post_id;

    }
    else
    {
      echo '400::Atleast one Or Max two tags is allow ';
    }

  }
}

// getProfileLike Start
public function actionGetProfileLike()
{
  $member_id = $_REQUEST['member_id'];
  $html = '';

  $userLike_profile = ProfileLike::model()->find(array('select'=>'group_concat(member_id) as member_id','condition'=>'active_status="S" and status="1" and friend_id='.$_REQUEST['member_id']));
  $html ='<div class="pulb-tex">ProfileLike Liked By user</div>';
  if(!empty($userLike_profile->member_id))
  {
    $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$userLike_profile->member_id.')'));

    if(!empty($member_data))
    {
      foreach($member_data as $valUsr)
      {
        $html .= $this->MemberShortInfo($valUsr);
      }
    }
  }
  else
  {
    $html .= '<div class="flt-ion-tb" style="width:98%!important;">
          <div class="left-tex">
            <div class="flot-left">
              <strong>No Likes Found</strong>
            </div>
          </div>
        </div>';
  }


  echo $html;
}

// Post Profile Like

public function actionPostProfileLike()
{
  $member_id = $_REQUEST['member_id'];
  $friend_id = $_REQUEST['friend_id'];
  $like_data = ProfileLike::model()->find(array('condition'=>'member_id='.$member_id.' and friend_id='.$friend_id));
  $is_i_like = "no";
  if(!empty($like_data))
  {
    ProfileLike::model()->deleteByPk($like_data->profile_like);
    $is_i_like = "No";
  }
  else
  {
    $model = new ProfileLike;
    $model->friend_id = $friend_id;
    $model->member_id = $member_id;
    $model->type = "L";
    $model->added_on = time();
    $model->updated_on = time();
    $model->save(false);

        $friend_data = Member::model()->findByPk($_REQUEST['friend_id']);

      if($member_id == $friend_id)
      {
        $username = 'own';
      }
      else
      {
        $username = $friend_data->username;
      }

      //storeUserActivity
        $this->storeUserActivity($_REQUEST['member_id'],"Liked ".$username."’s profile ","PL","ProfileLike",$model->profile_like,$_REQUEST['friend_id']);
    $is_i_like = "Yes";
  }

  $like_count = ProfileLike::model()->count(array('condition'=>'friend_id='.$friend_id));
  echo $like_count.'::'.$is_i_like;
}

/**
 * This is the action to handle external exceptions.
 */
public function actionError()
{
    if($error=Yii::app()->errorHandler->error)
    {
      if(Yii::app()->request->isAjaxRequest)
        echo $error['message'];
      else
          $this->render('error', $error);
    }
}

/**
 * Displays the contact page
 */
public function actionContact()
{
  $model=new ContactForm;
  if(isset($_POST['ContactForm']))
  {
    $model->attributes=$_POST['ContactForm'];
    if($model->validate())
    {
      $headers="From: {$model->email}\r\nReply-To: {$model->email}";
      mail(Yii::app()->params['adminEmail'],$model->subject,$model->body,$headers);
      Yii::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
      $this->refresh();
    }
  }
  $this->render('contact',array('model'=>$model));
}

/**
 * Logs out the current user and redirect to homepage.
 */
public function actionLogout()
{
  Yii::app()->user->logout();
  ApplicationSessions::run()->deleteAll();
  $this->redirect(Yii::app()->homeUrl);
}


//Block User

public function actionBlockFriend()
{
  $model = Friends::model()->findByPk($_REQUEST['friends_id']);

  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($model))
  {
    $model->active_status="H";
    $model->is_block="Y";
    $model->status=0;
    if($model->save())
    {
      $friend_id = ($member_id == $model->from_id) ? $model->to_id : $model->from_id;
      $blockModel = new BlockUser;

      $blockModel->from_id 	= $member_id;
      $blockModel->to_id 		= $friend_id;
      $blockModel->added_on 	= time();
      $blockModel->updated_on = time();
      $blockModel->save();

      //storeUserActivity
        $this->storeUserActivity($member_id,"Blocked user ".$blockModel->to_member->username  ,"BU","BlockUser",$blockModel->block_user_id);

    }
    echo "200";
  }
  else
  {
    $blockModel = new BlockUser;

    $blockModel->from_id 	= $member_id;
    $blockModel->to_id 		= $_REQUEST['friends_id'];
    $blockModel->added_on 	= time();
    $blockModel->updated_on = time();
    $blockModel->save();

    //storeUserActivity
      $this->storeUserActivity($member_id,"Blocked user ".$blockModel->to->username,"BU","BlockUser",$blockModel->block_user_id);
    echo "200";
  }

}


/*unblock friend */
public function actionUnBlockFriend()
{
  $model = Friends::model()->findByPk($_REQUEST['friends_id']);

  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($model))
  {
    $model->active_status = "S";
    $model->is_block	  = "N";
    $model->status		  = 1;
    if($model->save())
    {
      $friend_id = ($member_id == $model->from_id) ? $model->to_id : $model->from_id;
      $blockModel = BlockUser::model()->find(array('condition'=>'from_id="'.$member_id .'" and to_id="'.$friend_id.'"'));

    }
    echo "200";
  }
  else
  {
    echo "400";
  }
}
/*unblock friend */
//Un-BlockUser

public function actionUnBlockUser()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $blockModel = BlockUser::model()->findByPk($_REQUEST['block_id']);

  if(!empty($blockModel))
  {
    $friends =Friends::model()->find(array('condition'=>'active_status="S" and status="1" and is_accepted="Y" and is_block="Y" and active_status="H" and (from_id="'.$member_id.'" or to_id="'.$member_id.'") and (from_id="'.$_REQUEST['friend_id'].'" or to_id="'.$_REQUEST['friend_id'].'")'));

    if($friends)
    {
      $model = Friends::model()->findByPk($friends->friends_id);
      $model->active_status="S";
      $model->is_block="N";
      $model->status=1;
      if($model->save())
      {
        BlockUser::model()->deleteByPk($_REQUEST['block_id']);
        //delte from user activity
          UserActivity::model()->delete(array('condition'=>'active_status="S" and status="1" and type="BU" and parent_id='.$_REQUEST['block_id']));
        $resp = "200";
      }
      else
      {
        BlockUser::model()->deleteByPk($_REQUEST['block_id']);

        //delete from user activity
          UserActivity::model()->delete(array('condition'=>'active_status="S" and status="1" and type="BU" and parent_id='.$_REQUEST['block_id']));
        $resp = "200";
      }

    }
    else
    {
      BlockUser::model()->deleteByPk($_REQUEST['block_id']);
      $resp = "200";
    }
  }
  else
  {
    $resp = "304";
  }

  echo $resp;

}


public function actionUnfriend()
{
  $model = Friends::model()->findByPk($_REQUEST['friends_id']);
  $member_id = ApplicationSessions::run()->read('member_id');

  if(!empty($model))
  {
    if($model->is_accepted == "Y")
    {
      if($model->from_id == $member_id)
      {
        $name = $model->to->username;
        $parent_id = $model->to_id;
      }
      else
      {
        $name = $model->from->username;
        $parent_id = $model->from_id;
      }
      //storeUserActivity
        $this->storeUserActivity($member_id,"Removed ".$name." from connections","DF","Member",$parent_id);
    }
    Friends::model()->deleteByPk($_REQUEST['friends_id']);
    echo "200";
  }
  else
  {
    echo "400";
  }
}

public function actionAcceptfriendRequest()
{
  $model = Friends::model()->findByPk($_REQUEST['friends_id']);
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($model))
  {
    $model->active_status = "S";
    $model->is_accepted	  = "Y";
    $model->is_deleted	  = "N";
    $model->is_block	  = "N";
    $model->status		  = 1;
    if($model->save())
    {
      //storeUserActivity
        $this->storeUserActivity($member_id,"Accepted connection request from ".$model->from->username,"AF","Friends",$model->friends_id,$model->from_id);

      echo "200";
    }
  }
  else
  {
    echo "400";
  }
}




public function getTaggedFriend($friend_id)
{
  // $friend_id = $_REQUEST['friend_id'];
  $html = '';

  if(!empty($friend_id))
  {
    $member_data = Member::model()->findAll(array('condition'=>'member_id IN('.$friend_id.')'));

    if(!empty($member_data))
    {
      foreach($member_data as $val)
      {
        $connections = Friends::model()->count(array('condition'=>'(from_id='.$val->member_id.' or to_id='.$val->member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

        if(empty($val->profile_pic))
        {
          $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
        }
        else if(!empty($val->profile_pic) && strpos($val->profile_pic,"http")===false)
        {
          $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$val->profile_pic;
        }
        else
        {
          $profile_pic = $val->profile_pic;
        }

        $html .= '<div class="flt-ion-tb" id="tag_friend_'.$val->member_id.'">
            <div class="left-tex">
              <div class="flot-left dp-icon-tb">
                <img class="img-responsive" src="'.$profile_pic.'">
              </div>
              <div class="flot-left">
                <strong>'.base64_decode($val->first_name).' '.base64_decode($val->last_name).'</strong>
               <!---	<br>
                '.$connections.' connections --->
              </div>
            </div>
          </div>';
      }

      return $html;
    }
    else
    {
      return '';
    }
  }
  else
  {
    return '';
  }
}



public function getPostData($post_id)
{
  $baseUrl = Yii::app()->theme->baseUrl;
  $share_image = '';
  $post_data = Post::model()->findByPk($post_id);
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($post_data))
  {
    $like_count = PostLike::model()->count(array('condition'=>'post_id='.$post_id));
    $comment_count = PostComment::model()->count(array('condition'=>'post_id='.$post_id));
    $share_count = PostShare::model()->count(array('condition'=>'post_id='.$post_id.' and type="S"'));
    $retag_count = PostRetag::model()->count(array('condition'=>'post_id='.$post_id));
    $attachment = PostAttachment::model()->findAll(array('condition'=>'post_id='.$post_id.' and active_status="S"'));
    $post_friends 	= PostFriends::model()->find(array('select'=>'group_concat(friend_id) as friend_id','condition'=>'post_id='.$post_id.' and active_status="S"'));

    if(!empty($post_data->member->profile_pic) && strpos($post_data->member->profile_pic,"http")===false)
    {
      if(file_exists("upload/member/profile_pic/".$post_data->member->profile_pic))
      {
        $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$post_data->member->profile_pic;
      }
      else
      {
        $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
      }

    }
    else
    {
      $profile_pic = (!empty($post_data->member->profile_pic))?$post_data->member->profile_pic:Yii::app()->theme->baseUrl."/images/profile-act.png";
    }
    $val = $post_data;
    $post_text = preg_replace('/#(\w+)/', ' <span class="braun-color text-bold ">#$1</span>', $val->post);

    $name = (!empty($val->member->first_name))?$val->member->first_name." ".$val->member->last_name:'';
    $user_name = (!empty($val->member->username))?"(".$val->member->username.")":'';
    $added_on= Controller::get_timeago($val->added_on);
    $post ='<div class="menu-m-are clearfix mt10 mb10">
                              <div class="img-tex-box clearfix ">
                                <div class="col-sm-12">
                                  <div class="left-tex">
                                    <div class="flot-left dp-icon mt10">
                                      <img class="img-responsive" src="'.  $profile_pic .'">
                                    </div>
                                    <div class="flot-left mt10"><strong>'.  $name .'</strong> '.$user_name  .'
                                      <br> '. $added_on .'
                                    </div>
                                  </div>
                                <!--	<div class="right-tex text-center mt10">
                                    <i class="fa fa-share-square-o shre-icn" aria-hidden="true "></i>
                                    <div>Instant Share</div>
                                  </div> -->
                                </div>
                              </div>
                              <div class="col-sm-12 mb10 ">
                                <div class="post-tex ">'.
                                     $post_text.'
                                  </div>';
                                  if(!empty($val->location))
                                  {
                                    $location = $val->location;
                                  $post .='
                                        <div class="col-md-12">
                                          <div class="col-sm-2"><img class="img-responsive" src="'.  $baseUrl .'/images/location_ping.png"></div>
                                            <div class="col-sm-10">'.  $location .'</div>
                                        </div>';
                                  }

                                $post .='	<div class="post-tex">';
                                    if(!empty($post_friends->friend_id))
                                    {
                                      $post .= $this->getTaggedFriend($post_friends->friend_id);
                                    }
                                $post .=	'
                                  </div>

                              </div>
                              ';
                                if(!empty($attachment))
                                {
                              $post .= '<div class="col-sm-12 ">';

                                  if(count($attachment)==1)
                                  {
                                    if($attachment[0]->type=="V")
                                    {

                                      $post .='<div class="col-sm-grid-2">
                                        <video class="img-responsive" controls>
                                          <source src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[0]->attachment .'" type="video/mp4">
                                        </video>
                                      </div>';
                                    }
                                    else
                                    {
                                      $share_image =   Yii::app()->baseUrl .'/upload/post_attachment/"'.$attachment[0]->attachment;
                              $post .= '<div class="col-sm-grid-2">
                                        <img class="img-responsive" src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[0]->attachment .'">
                                      </div>';

                                    }
                                  }
                                  else if(count($attachment)==2)
                                  {
                                    if($attachment[0]->type=="P")
                                    {
                              $post .= '<div class="col-sm-grid-2">
                                        <img class="img-responsive" src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[0]->attachment .'">
                                      </div>';

                                    }
                                    else
                                    {
                              $post .= '<div class="col-sm-grid-2">
                                        <video class="img-responsive" controls>
                                          <source src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[0]->attachment .'" type="video/mp4">
                                        </video>
                                      </div>';
                                    }

                                    if($attachment[1]->type=="P")
                                    {
                                      $post .= '<div class="col-sm-grid-2">
                                        <img class="img-responsive" src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[1]->attachment .'">
                                      </div>';
                                    }
                                    else
                                    {
                                      $post .='<div class="col-sm-grid-2">
                                        <video class="img-responsive" controls>
                                          <source src="'.  Yii::app()->baseUrl.'/upload/post_attachment/'.  $attachment[1]->attachment .'" type="video/mp4">
                                        </video>
                                      </div>';
                                    }

                                  }
                                  else if(count($attachment)==3)
                                  {
                              $post .='<div class="col-sm-grid ">
                                      <img class="img-responsive " src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[0]->attachment .'">
                                    </div>
                                    <div class="col-sm-grid">
                                      <img class="img-responsive" src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[1]->attachment .'">
                                    </div>
                                    <div class="col-sm-grid-2">
                                      <img class="img-responsive" src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[2]->attachment .'">
                                    </div>';
                                  }
                                  else if(count($attachment)==4)
                                  {
                              $post .='<div class="col-sm-grid ">
                                      <img class="img-responsive " src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[0]->attachment .'">
                                    </div>
                                    <div class="col-sm-grid">
                                    <img class="img-responsive" src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[1]->attachment .'">
                                    </div>
                                    <div class="col-sm-grid ">
                                      <img class="img-responsive " src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[2]->attachment .'">
                                    </div>
                                    <div class="col-sm-grid">
                                      <img class="img-responsive" src="'.  Yii::app()->baseUrl .'/upload/post_attachment/'.  $attachment[3]->attachment .'">
                                    </div>';
                                  }
                              $post .='</div>';
                                }
                              $post .='<div class="col-sm-12 mt10">

                              <div class="left-tex">
                                <a data-toggle="modal" data-id="'.  $val->post_id .'" ';
                                if(!empty($member_id)){ $post .=' href="#" onclick="postLike(this);" '; }else{ $post .=' href="#onload" '; } $post .='>
                                <div class="flot-left icon-with">

                                  <img class="img-responsive" src="'.  $baseUrl.'/images/link-btn-clr.png">

                                </div>

                                <div class="flot-left mr10">
                                  <a data-toggle="modal" data-id="'. $val->post_id.'"'; if(!empty($member_id)){$post .=' href="#" onclick="getLike(this);"';}else{ $post .=' href="#onload"';} $post .='><span class="post_like_count_'. $val->post_id.'">'.  $like_count.'</span> Likes</a>

                                </div>
                                </a>
                              </div>



                              <div class="left-tex">
                                <a data-toggle="modal" data-id="'. $val->post_id .'" '; if(!empty($member_id)){$post .=' href="#" onclick="postComment(this);"';}else{ $post .=' href="#onload" '; }$post .='>
                                <div class="flot-left icon-with">

                                  <img class="img-responsive" src="'. $baseUrl.'/images/comm-cle.png">

                                </div>

                                <div class="flot-left mr10">
                                  <span class="post_comment_count_'. $val->post_id.'">'. $comment_count.'</span> Comments
                                </div>
                                </a>
                              </div>



                              <div class="left-tex po-reti">

                                <div class="couponcode">
                                  <a data-id="'.$val->post_id .'" ';
                                  if(!empty($member_id))
                                  {
                                $post .=' href="#" onclick="getSharePost(this)" '; }else{ $post .=' href="#onload"'; } $post .='><span id="post_share_count_'. $val->post_id.'">
                                  <div class="flot-left icon-with">

                                    <img class="img-responsive" src="'. $baseUrl.'/images/share-clr.png">

                                  </div>

                                  <div class="flot-left mr10">
                                    '. $share_count.'</span> Shares

                                  </div>
                                  </a>


                                </div>

                              </div>


                               <div class="left-tex po-reti">

                              <div class="couponcode">

                                    <img class="img-responsive" src="'. $baseUrl.'/images/dot-icon.png">
                                <span class="coupontooltip ">

                                 <div class="coupontooltip-arrow"></div> ';
                                 if($member_id == $val->member_id)
                                {
                                  $post .='	<a href="#" class="tooltiptext" onclick="deletePost('.$val->post_id.');">Delte post</a>';

                                }
                                $post .='<a href="#" class="tooltiptext" onclick="savePost('.$val->post_id.',"S")"> Save Post</a>
                                     <a href="#" class="tooltiptext" onclick="savePost('. $val->post_id.',"H")"> Hide Post</a>
                                     <a href="#" class="tooltiptext" onclick="ReportPost('.$val->post_id.',"R")"> Report Post</a>
                                </span>
                              </div>
                            </div>
                          </div>'	;
                  $post .='</div>';

   return $post;

  }// if close
  else
  {
     return '';
  }


}

//List of followed tag by user
public function actionFollowingTagList()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $tagFollow 	= 	TagFollow::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id='.$member_id));
  $html ='';

  if(!empty($tagFollow))
  {
    $html .= '<table>';
      foreach($tagFollow as $valpostedTag)
      {
        $html .= '<tr><div class="flt-ion-tb" style="width:98%!important;margin:0px!important;" id="'.$valpostedTag->tag_follow_id.'">
            <div class="left-tex">
            <td>
              <div class="flot-left" >

                <strong>'.$valpostedTag->tag.'</strong>
              </div>
            </td>
            <td>
              <div class="flot-left" style="margin-left:50px;">
                <button type="button" onclick="unFollowtag('.$valpostedTag->tag_follow_id.')"> Un Followtag</button>
              </div>
            </td>
            </div>
          </div>
          </tr>';
      }
  $html .= '</table>';
  }
  else
  {
    $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Follow any tag Found</strong>
              </div>
            </div>
          </div>';
  }
    echo $html;

}
/* Tag Unfollow*/
public function actionUnFollowTag()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $tagFollow 	= 	TagFollow::model()->findAll(array('condition'=>'active_status="S" and status="1" and tag_id="'.$_REQUEST['tag_id'].'" and member_id='.$member_id));

  if(!empty($tagFollow))
  {
    TagFollow::model()->deleteAll(array('condition'=>'active_status="S" and status="1" and tag_id="'.$_REQUEST['tag_id'].'" and member_id='.$member_id));
    echo "F";
  }
  else
  {
    $tag_data = Tags::model()->find(array('condition'=>'tags_id='.$_REQUEST['tag_id']));
    $model = new TagFollow;
    $model->member_id = $member_id;
    $model->tag_id = $_REQUEST['tag_id'];
    $model->added_on = time();
    $model->updated_on = time();
    $model->tag = (!empty($tag_data->tags)) ? $tag_data->tags : "";
    $model->save();
    echo "U";
  }
}

/*All Tag List*/

public function actionTagList()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $tags 	= 	Tags::model()->findAll(array('condition'=>'active_status="S" and status="1"'));
  if(!empty($tags))
  {
    foreach($tags as $tag_list)
    {
      $is_tagFollow 	= 	TagFollow::model()->find(array('condition'=>'active_status="S" and status="1" and tag_id="'.$tag_list->tags_id.'" and member_id='.$member_id));

      if(!empty($is_tagFollow))
      {
        $button = '<button type="button" onclick="unFollowtag('.$is_tagFollow->tag_follow_id.')"> Un Followtag</button>' ;
      }
      else
      {
        $button = '<button type="button" onclick="Followtag('.$tag_list->tags_id.')"> Followtag</button>' ;
      }

      $html .= '<div class="flt-ion-tb" style="width:98%!important;margin:0px!important;" id="'.$tag_list->tags_id.'">

            <div class="left-tex">
              <div class="flot-left" >

                <strong>'.$tag_list->tags.'</strong>
              </div>
              <div class="flot-left" style="margin-left:50px;">
                '.$button.'
              </div>
            </div>
          </div>';

    }
  }
  else
  {
    $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Follow any tag Found</strong>
              </div>
            </div>
          </div>';
  }

  echo $html;
}

/*Start Follow new Tag*/

public function actionFollowTag()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $tagFollow 	= 	TagFollow::model()->findAll(array('condition'=>'active_status="S" and status="1" and tag_id="'.$_REQUEST['tag_id'].'" and member_id='.$member_id));
  $tag_data  = Tags::model()->findByPk($_REQUEST['tag_id']);

  if(empty($tagFollow))
  {
    $model = new TagFollow;
    $model->tag_id = $_REQUEST['tag_id'];
    $model->member_id = $member_id;
    $model->tag = $tag_data->tags;
    $model->added_on = time();
    $model->updated_on = time();
    $model->save();

    //storeUserActivity
        $this->storeUserActivity($member_id,"Followed ".$tag_data->tags ,"TF","TagFollow",$model->tag_follow_id);
    echo "200";
  }
  else
  {
    echo "304";
  }
}

/*Delte Post */

public function actionDeltePost()
{
  $post = Post::model()->findByPk($_REQUEST['post_id']);

  if(!empty($post))
  {
    $postTag = PostTags::model()->find(array('select'=>'group_concat(tags) as tags ','condition'=>'post_id='.$_REQUEST['post_id']));

    //storeUserActivity
      $this->storeUserActivity($post->member_id,"Deleted your post ".$postTag->tags,"DP","Post",$_REQUEST['post_id']);

    //Post::model()->deleteByPk($post->post_id);
    Post::model()->updateByPk($_REQUEST['post_id'],array('active_status'=>'H','status'=>'0'));



  $postAttachment = PostAttachment::model()->findAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
    if(!empty($postAttachment))
    {
      PostAttachment::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }
  $postComment = PostComment::model()->findAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
    if(!empty($postComment))
    {
      PostComment::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }

  $postFriends = PostFriends::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    if(!empty($postFriends))
    {
      PostFriends::model()->updateAll(array('active_status'=>'G','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }

  $postLike = PostLike::model()->findAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
    if(!empty($postLike))
    {
      PostFriends::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }

  $postRetag = PostRetag::model()->findAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
    if(!empty($postRetag))
    {
      PostRetag::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }
  $postSetting = PostSetting::model()->findAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
    if(!empty($postRetag))
    {
      PostSetting::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }

  $postShare = PostShare::model()->findAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
    if(!empty($postShare))
    {
      PostShare::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }
  $postTags = PostTags::model()->findAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
    if(!empty($postTags))
    {
      PostTags::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }
  $postUserTagin = PostUserTagin::model()->findAll(array('condition'=>'post_id='.$_REQUEST['post_id']));
    if(!empty($postUserTagin))
    {
      PostUserTagin::model()->updateAll(array('active_status'=>'H','status'=>'0'),'post_id='.$_REQUEST['post_id']);
    }
  }
}

/*isTagFollow*/


public function actionIsTagFollow()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $tags 	= 	Tags::model()->findAll(array('condition'=>'active_status="S" and status="1" and tags = "'.$_REQUEST['tag'].'"'));
  if(!empty($tags))
  {
    foreach($tags as $tag_list)
    {
      $is_tagFollow 	= 	TagFollow::model()->find(array('condition'=>'active_status="S" and status="1" and tag_id="'.$tag_list->tags_id.'" and member_id='.$member_id));

      if(!empty($is_tagFollow))
      {
        $button = '<button type="button" onclick="unFollowtag('.$is_tagFollow->tag_follow_id.')"> Un Followtag</button>' ;
      }
      else
      {
        $button = '<button type="button" onclick="Followtag('.$tag_list->tags_id.')"> Followtag</button>' ;
      }

      $html .= '<div class="flt-ion-tb" style="width:100%!important;margin:0px!important;" id="'.$tag_list->tags_id.'">

            <div class="left-tex">
              <div class="flot-left" >

                <strong>'.$tag_list->tags.'</strong>
              </div>
              <div class="flot-left" style="margin-left:50px;">
                '.$button.'
              </div>
            </div>
          </div>';

      ///find all post of that tag which is not hide by user and nor by blocked user

        $post_tags = PostTags::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and tags_id='.$tag_list->tags_id));
        if(!empty($post_tags->post_id))
        {
          $condition = 'active_status="S" and status="1"';

            $abuse_post = PostSetting::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'member_id='.$member_id));

            $block_frnd = BlockUser::model()->find(array('select'=>'group_concat(to_id) as to_id','condition'=>'active_status="S" and status="1" and from_id='.$member_id));

              if(!empty($block_frnd->to_id))
              {
                $condition .= 'and member_id NOT IN('.$block_frnd->to_id.')';
              }

              if(!empty($abuse_post->post_id))
              {
                $condition .= ' and post_id NOT IN('.$abuse_post->post_id.')';
              }

              $post = Post::model()->findAll(array('condition'=>$condition.' and (post_id IN('.$post_tags->post_id.'))','order'=>'post_id desc'));
              if(!empty($post))
              {
                foreach($post as $val_post)
                {
                  $html .= $this->ShowPost($val_post);

                }
              }

        }
    }
  }
  else
  {
    $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Follow any tag Found</strong>
              </div>
            </div>
          </div>';
  }

  echo $html;
}

/*Show user  BasicDetails*/

public function actionBasicDetails()
{
  $user_name = str_replace("@","",$_REQUEST['user_name']);

  $member = Member::model()->find(array('condition'=>'active_status="S" and status="1" and username ="'.$user_name.'"'));

  if(!empty($member))
  {
    if(empty($member->profile_pic))
    {
      $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
    }
    else if(!empty($member->profile_pic) && strpos($member->profile_pic,"http")===false)
    {
      $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$member->profile_pic;
    }
    else
    {
      $profile_pic = $member->profile_pic;
    }

      $html .= '<div class="flt-ion-tb" style="width:98%!important;margin:0px!important;">

          <div class="left-tex">
            <div class="flot-left dp-icon-tb">
              <img class="img-responsive" src="'.$profile_pic.'">
            </div>
            <div class="flot-left">
              <strong>'.$member->first_name.' '.$member->last_name.'</strong>
              <br>
              '.$val->comment.'
            </div>
          </div>
        </div>';
  }
  else
  {
    $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Comment Found</strong>
              </div>
            </div>
          </div>';

  }

echo $html;
}
//All User List
public function actionAllUserList()
{
  $member = Member::model()->findAll(array('condition'=>'active_status="S" and status="1"'));
  if(!empty($member))
    {
      foreach($member as $val)
      {


        if(empty($val->profile_pic))
        {
          $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
        }
        else if(!empty($val->profile_pic) && strpos($val->profile_pic,"http")===false)
        {
          $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$val->profile_pic;
        }
        else
        {
          $profile_pic = $val->profile_pic;
        }

        $html .= '<div class="flt-ion-tb" style="width:98%!important;margin:0px!important;">

            <div class="left-tex">
              <div class="flot-left dp-icon-tb">
                <img class="img-responsive" src="'.$profile_pic.'">
              </div>
              <div class="flot-left">
                <strong>'.$val->first_name.' '.$val->last_name.'</strong>
                ('.$val->username.')
              </div>
            </div>
          </div>';
      }


    }
    else
    {
      $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No User Found</strong>
              </div>
            </div>
          </div>';
    }


  echo $html;
}

public function actionSavePost()
{
  $post_data = Post::model()->findByPk($_REQUEST['post_id']);
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($post_data))
  {
    $res 	= ($_REQUEST['type']=='S')?"Save":"Hide";
    $model 	= new PostSetting;
    $model->post_id 	= $_REQUEST['post_id'];
    $model->member_id 	= $member_id;
    $model->type 		= $_REQUEST['type'];
    $model->added_on 	=  time();
    $model->updated_on  =  time();

    if($model->save())
    {
        if($_REQUEST['type']=='S')
        {
          $postTag = PostTags::model()->find(array('select'=>'group_concat(tags) as tags ','condition'=>'post_id='.$_REQUEST['post_id']));
        }
        $msg 	= ($_REQUEST['type']=='S')?"Saved post ".$postTag->tags : " Hide ".$post_data->member->username."'s post ";
        $type 	= ($_REQUEST['type']=='S')?"SP":" HP";

      //storeUserActivity
        $this->storeUserActivity($member_id,$msg,$type,"PostSetting",$model->post_setting_id,'',$_REQUEST['post_id'],'P');
      echo "200";
    }
    else
    {
      echo "400";
    }
  }
  else
  {
    echo "400";
  }
}

public function actionReportPost()
{
  $post_data = Post::model()->findByPk($_REQUEST['post_id']);
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($post_data))
  {
    $res = ($_REQUEST['type']=='S')?"Save":"Hide";

    $model = new PostSetting;
    $model->post_id 	= $_REQUEST['post_id'];
    $model->member_id 	= $member_id;
    $model->type 		= $_REQUEST['type'];
    if($_REQUEST['type']=='R')
    {
      $model->report_type = (!empty($_REQUEST['report_type'])) ? $_REQUEST['report_type'] : '';
    }
    $model->added_on 	=  time();
    $model->updated_on  =  time();

    if($model->save())
    {

        $report_type_msg = $this->getReporttype($_REQUEST['report_type']);
        $msg = " Reported ".$report_type_msg." for ".$post->member->username."'s post ";
        $type = "RSP";

      //storeUserActivity
        $this->storeUserActivity($member_id,$msg,$type,"PostSetting",$model->post_setting_id,'',$_REQUEST['post_id'],'P');
      echo "200";
    }
    else
    {
      echo "400";
    }
  }
  else
    {
      echo "400";
    }
}

///save/hide post of user
public function actionShowSavedPost()
{
  $results = '';
  $member_id = ApplicationSessions::run()->read('member_id');
  $condition = 'active_status="S" and status="1" and  type = "'.$_REQUEST['type'].'" and member_id='.$member_id;
  $post_ids = PostSetting::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>$condition));
  if(!empty($post_ids->post_id))
  {
    $post_id_arr = explode(',',$post_ids->post_id);
    foreach($post_id_arr as $val)
    {
      $post_data = Post::model()->findByPk($val);
      if(!empty($post_data))
      {
        $results .= $this->ShowPost($post_data);
      }
    }
  }
  else
  {
    $results ='No Post Found';
  }
  echo $results;
}


///Add Location
public function actionAddLocation()
{
  $member_id = ApplicationSessions::run()->read('member_id');


    $location_name = str_replace(':',',',$_REQUEST['location_name']);
    $address_array = array();
    $loc_condition = 'active_status="S" and status="1" and location_name = "'.$location_name.'"';

    if(empty($_REQUEST['latitude']) && empty($_REQUEST['longitude']) )
    {
      $address = $this->getLocation($location_name);
      $address_array = explode(',',$address);
    }
    if(!empty($address))
    {
      $latitude =(!empty($address_array[0])) ? round($address_array[0],3) : "";
      $longitude =(!empty($address_array[1])) ? round($address_array[1],3) : "";

      $loc_condition .=' or (latitude='.$latitude.' and longitude='.$longitude.')';
    }
    if(!empty($_REQUEST['latitude']) && !empty($_REQUEST['longitude']) )
    {
      $latitude = $_REQUEST['latitude'];
      $longitude = $_REQUEST['longitude'];

      $loc_condition .=' or (latitude='.$latitude.' and longitude='.$longitude.')';
    }
    $location_master = LocationMaster::model()->find(array('condition'=>$loc_condition));

    $isExist = Location::model()->find(array('condition'=>'active_status="S" and status="1" and member_id='.$member_id.' and location_name = "'.$location_name.'"'));


    if(empty($isExist))
    {
        if(empty($location_master))
        {

          $location_master = new LocationMaster;
          $location_master->location_name = $location_name;
          $location_master->latitude 		= (!empty($latitude)) ? $latitude : $location_master->latitude;
          $location_master->longitude 	= (!empty($longitude)) ? $longitude : $location_master->longitude;
          $location_master->added_on		= time();
          $location_master->updated_on	= time();

          $location_master->save();
        }

        if(empty($_REQUEST['location_id']))
      {
        $model = new Location;
      }
      else
      {
        $location_data = Location::model()->findByPk($_REQUEST['location_id']);
        $model = $location_data;
      }

        $model->member_id 			= $member_id;
        $model->location_master_id 	= $location_master->location_master_id;
        $model->latitude 			= $location_master->latitude;
        $model->longitude 			= $location_master->longitude;
        $model->location_name 		= $location_name;
        $model->added_on			= time();
        $model->updated_on			= time();

        if($model->save())
        {
          $res = "200";
        }
        else
        {
          $res = "304";
        }

    }
    else
    {
      $res = "304";
    }


  echo $res;

}

//Show Location list

public function actionShowFollowingLocation()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  if($member_id)
  {
    $location_data = Location::model()->findAll(array('condition'=>'member_id='.$member_id));
    if(!empty($location_data))
    {
      $html .= '<table> ';
      foreach($location_data as $valLocation)
      {
        // $html .= '<div class="flt-ion-tb" style="width:98%!important;margin:0px!important;" id="followLocation_'.$valLocation->location_master_id.'">

            // <div class="left-tex">
              // <div class="flot-left" >

                // <strong>'.$valLocation->location_name.'</strong>
              // </div>
              // <div class="flot-left" style="margin-left:50px;">
                // <button type="button" onclick="unFollowlocation('.$valLocation->location_id.')"> Un Follow Location</button>
              // </div>
            // </div>
          // </div>';

           $html .= ' <tr id="followLocation_'.$valLocation->location_master_id.'">
                <td><strong>'.$valLocation->location_name.'</strong></td>
                <td><button type="button" onclick="unFollowlocation('.$valLocation->location_id.')"> Un Follow Location</button></td>
                <td> <a href="'.Yii::app()->createUrl('site/locationMedia?location_id='.$valLocation->location_id).'" target="_blank"> <button type="button" > View LOcation Gallery </button> </a></td>
                <td><button type="button" onclick="LocationFollower('.$valLocation->location_id.')">  Location Follower</button></td></tr>';

      }
      $html .= '</table> ';
    }
  }
  echo $html;

}

public function actionUnFollowLocation()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  if($member_id)
  {
    $locationFollow 	= 	Location::model()->findAll(array('condition'=>'active_status="S" and status="1" and location_id="'.$_REQUEST['location_id'].'" and member_id='.$member_id));

    if($locationFollow)
    {
      Location::model()->deleteByPk($_REQUEST['location_id']);
    }

    $location_data = Location::model()->findAll(array('condition'=>'member_id='.$member_id));
    if(!empty($location_data))
    {
      $html .= '<table> ';
      foreach($location_data as $valLocation)
      {


           $html .= ' <tr id="followLocation_'.$valLocation->location_master_id.'">
                <td><strong>'.$valLocation->location_name.'</strong></td>
                <td><button type="button" onclick="unFollowlocation('.$valLocation->location_id.')"> Un Follow Location</button></td>
                <td><button type="button" onclick="viewLocationGallery('.$valLocation->location_id.')"> View Location Gallery</button></td>
                <td><button type="button" onclick="LocationFollower('.$valLocation->location_id.')"> Location Follower</button></td></tr>';

      }
    }
      $html .= '</table> ';
  }
  echo $html;
}
// getLocationAdd function start
public function actionGetLocationAdd()
{
  $location = $_REQUEST['location'];
  $html = '';

  if(!empty($location))
  {
    $location_arr = explode("::",$location);
    $i = 0;

    foreach ($location_arr as $val)
    {
      if($i==0)
      {
        $class = 'd-ft-bor';
        $i++;
      }
      else
      {
        $class = '';
      }

      $html .= '<div class="clearfix pop-pad '.$class.'">
            <div class="pbl-ewih mar-none">
              <div class="left-tex">
                <div class="flot-left mt10">
                  <strong><span onclick="AddLocation(this);">'.$val.'</span></strong>
                </div>
              </div>
            </div>
          </div>';
    }

    echo "200::".$html;
  }
  else
  {
    echo "400::".$html;
  }
}

///Like Comment or Like Reply
public function actionLikeComment()
{
  $member_id 	 = ApplicationSessions::run()->read('member_id');
  $postComment = PostComment::model()->findByPk($_REQUEST['post_comment_id']);


  if(!empty($postComment))
  {
    $is_like = PostLike::model()->count(array('condition'=>'member_id='.$member_id.' and comment_id='.$_REQUEST['post_comment_id']));

    if(empty($is_like))
    {
      $model = new PostLike;
      $model->comment_id 	= $_REQUEST['post_comment_id'];
      $model->member_id 	= $member_id;
      $model->added_on	= time();
      $model->save();

      $postComment->comment_like_count = $postComment->comment_like_count+1;
      $postComment->updated_on=time();
      $postComment->save();

      if($postComment->type=='C' )
      {
        $type = "CL";
        $msg = "Liked ".$postComment->member->username." comment";
      }
      else
      {
        $type = "RL";
        $msg = "Liked ".$postComment->member->username." reply";
      }
      //storeUserActivity
        $this->storeUserActivity($member_id,$msg,$type,"PostLike",$model->post_like_id);
    }
    else
    {
      PostLike::model()->deleteAll(array('condition'=>'member_id='.$member_id.' and comment_id='.$_REQUEST['post_comment_id']));

      UserActivity::model()->deleteAll(array('condition'=>'member_id='.$member_id.' and module="PostLike" and parent_id='.$_REQUEST['post_comment_id']));

      $postComment->comment_like_count=$postComment->comment_like_count-1;
      $postComment->updated_on=time();
      $postComment->save();
    }

    echo "200";
  }
  else
  {
    echo "400";
  }

}

///Comment Reply

public function actionCommentReply()
{
  $member_id 	 = ApplicationSessions::run()->read('member_id');
  $post = Post::model()->findByPk($_REQUEST['post_id']);
  if(!empty($post))
  {
    if(empty($_REQUEST['post_comment_id']))
    {
      $post->comment_count = $post->comment_count+1;
      $post->updated_on = time();
      $post->save();
      $model = new PostComment;
    }
    else
    {
      $model = PostComment::model()->findByPk($_REQUEST['post_comment_id']);
    }



    $model->post_id 	= $_REQUEST['post_id'];
    $model->member_id 	= $member_id;
    $model->comment 	= base64_encode($_REQUEST['comment']);
    $model->type 		= 'R';
    $model->parent_id 	= $_REQUEST['parent_id'];
    $model->added_on	= time();
    if($model->save())
    {
      $parent_comment_data = PostComment::model()->findByPk($_REQUEST['parent_id']);

      if(!empty($parent_comment_data))
      {
        //PushNotification for comment auther start
          $comment_auther_data = Member::model()->findByPk($parent_comment_data->member_id);
          $member_data  	  = Member::model()->findByPk($_REQUEST['member_id']);

            if(!empty($comment_auther_data->device_token))
            {
              $notification_status = 	$this->sendMessage($member_data->first_name.' '.$member_data->last_name.' Comment Your Comment',$comment_auther_data->device_token);
            }
        //PushNotification start

      //storeUserActivity
        $this->storeUserActivity($_REQUEST['member_id']," Replied to ".$comment_auther_data->username."’s comment","CR","PostComment",$model->post_comment_id,$comment_auther_data->member_id);
      }
      echo "200";
    }
    else
    {
      echo "400";
    }

  }
  else
  {
    echo "400";
  }

}

public function actionDeleteComment()
{
  $postComment = PostComment::model()->findByPk($_REQUEST['post_comment_id']);

  $post = Post::model()->findByPk($postComment->post_id);
  if(!empty($postComment))
  {
    $commet_ids 		= PostComment::model()->find(array('select'=>'group_concat(post_comment_id) as post_comment_id','condition'=>'parent_id= "'.$_REQUEST['post_comment_id'].'"','order'=>'post_comment_id DESC'));

    $commet_count = PostComment::model()->count(array('condition'=>'parent_id='.$_REQUEST['post_comment_id']));

    $desc_cnt = ($commet_count > 0 ) ? 1+$commet_count:1;

    PostComment::model()->deleteByPk($_REQUEST['post_comment_id']);

    PostComment::model()->deleteAll(array('condition'=>'parent_id='.$_REQUEST['post_comment_id']));

    //delte from like Comment`
        if(!empty($commet_ids->post_comment_id))
        {
          PostLike::model()->deleteAll(array('condition'=>'comment_id IN ('.$_REQUEST['post_comment_id'].' or '.$commet_ids->post_comment_id.')'));
        }
        else
        {
          PostLike::model()->deleteAll(array('condition'=>'comment_id IN ('.$_REQUEST['post_comment_id'].')'));
        }
   // desc comment_count from post as per count
    $res = $post->comment_count - $desc_cnt;


    $post->comment_count	= $res;
    $post->updated_on		=	time();
    $post->save();

    echo "200";
  }
  else
  {
    echo "400";
  }
}


//editReply

public function actionEditedComment()
{
  $post_comment = PostComment::model()->findByPk($_REQUEST['post_comment_id']);
  if(!empty($post_comment))
  {
    $post_comment->comment = base64_encode($_REQUEST['post_comment']);
    $post_comment->updated_on = time();
    if($post_comment->save())
    {
      echo "200";
    }
    else
    {
      echo "400";
    }
  }
  else
  {
    echo "400";
  }
}

//Post share with friends

public function actionPostShare()
{
  $member_id 	= ApplicationSessions::run()->read('member_id');
  $post 		= Post::model()->findByPk($_REQUEST['post_id']);

  if(!empty($post))
  {
    $post->share_count	=	$post->share_count+1;
    $post->updated_on	=	time();
    $post->save();

    $model = new PostShare;

    $model->post_id = $_REQUEST['post_id'];
    $model->from_id = $member_id;
    $model->type 	= $_REQUEST['type'];

    if(!empty($_REQUEST['to_id']))
    {
      $model->to_id = $_REQUEST['to_id'];
    }
    $model->added_on	= time();
    $model->updated_on 	= time();
    $model->save();


    //PushNotification start
      $post_auther_data 	= Member::model()->findByPk($post->member_id);
      $member_data  		= 	Member::model()->findByPk($member_id);

      if(!empty($post_auther_data->device_token))
      {
        $notification_status = 	$this->sendMessage($member_data->first_name.' '.$member_data->last_name.' Share Your Post',$post_auther_data->device_token);
      }
    //PushNotification start

    //storeUserActivity
      $this->storeUserActivity($member_id,"Shared ".$post_auther_data->username."’s post ","S","PostShare",$model->post_share_id,$post->member_id,$post->post_id,'P');

      echo "200";
  }
  else
  {
      echo "400";
  }
}

public function userActivity($type,$module,$parent_id)
{
  $data ='';
  if($type == "L")
  {
    $postLike_Data = PostLike::model()->find(array('condition'=>'active_status="S" and status="1" and post_like_id='.$parent_id));

    if(!empty($postLike_Data))
    {
      $post_data = Post::model()->find(array('condition'=>'active_status="S" and status="1" and post_id ='.$postLike_Data->post_id));
      if(!empty($post_data))
      {
        $data = $this->ShowPost($postLike_Data->post_id);
      }
    }

  }
  else if($type == "C")
  {
    $postLike_Data = PostComment::model()->findByPk($parent_id);
    if(!empty($postLike_Data))
    {
      $data = $this->ShowPost($postLike_Data->post_id);

    }

  }
  if($type == "S")
  {
    $postLike_Data = PostShare::model()->findByPk($parent_id);
    if(!empty($postLike_Data))
    {
      $data = $this->ShowPost($postLike_Data->post_id);
    }

  }
  else if($type == "TF")
  {
    $tag_follow = TagFollow::model()->findByPk($parent_id);
    if(!empty($tag_follow))
    {
    //	$tag_follow->tags_id
      $data = 'You start following <a href="" target="_blank">'.$tag_follow->tag .'</a>';
    }
  }
  else if($type == "TU")
  {
     $master = Tags::model()->findByPk($parent_id);
    if(!empty($master))
    {
      //	$master->tags_id
      $data = 'You Stop following <span class="braun-color text-bold " onclick="isTagFollow(this);">'.$master->tags .'</span>';
    }
  }

  else if($type == "PL")
  {
    $master = ProfileLike::model()->findByPk($parent_id);
    if(!empty($master))
    {
      //$master->friend_id
      $data = 'You Like Profile of  <a href='. Yii::app()->createUrl("site/friendTimeLine?friend_id=".$master->friend_id).' target="_blank">'.base64_decode($master->friend->first_name)." ".base64_decode($master->friend->last_name).'</a>';
    }
  }

  else if($type == "SF" )
  {
    $master = Friends::model()->findByPk($parent_id);
    if(!empty($master))
    {
      //$data->to_id; $data->to->username
      $data = 'Sent connection request to <a href='. Yii::app()->createUrl("site/friendTimeLine?friend_id=".$master->to_id).' target="_blank">'.$master->to->username.'</a>';
    }
  }
  else if($type == "CP")
  {
    $data = Friends::model()->findByPk($parent_id);
    if(!empty($data))
    {
      $data = 'You Changed Password ';
    }
  }
  else if($type == "PC" || $type == "DP")
  {
    $data = Post::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['member_id']  = $data->member_id;
      $member_data['name']	   = $data->member->first_name ." ".$data->post->member->last_name;
      $member_data['username']   = $data->member->username;

      if(!empty($data->member->profile_pic))
      {
        $member_data['profile_pic']	= Yii::app()->params['SITE_URL']."upload/member/profile_pic/".$data->member->profile_pic;
      }
      else
      {
        $member_data['profile_pic']= '';
      }

      $postTag =	$this->getPostTags($parent_id,$member_id);

      $list_tags='';
      foreach($postTag as $val_tag)
      {
        $list_tags .= $val_tag['tag'].' ';
      }

      $params_data['member_id'] = $data->member_id;
      $params_data['post_id']   = $data->post_id;
      $params_data['auther']    = $member_data;
      $params_data['postTag']   = $postTag;
      if($type == "PC")
      {
        $data = "Posted <br/>". $this->getPostData($postLike_Data->post_id);
      }
      else
      {
        $data  	 = 'Deleted your Post '.$list_tags;
      }



    }
  }
  else if($type == "DF")
  {
    $data = Member::model()->findByPk($parent_id);
    if(!empty($data))
    {
      // $params_data['friend_id'] 		= $parent_id;
      // $data   	 = 'Removed '.$data->username." from connections";
      $data = 'Removed <a href='. Yii::app()->createUrl("site/friendTimeLine?friend_id=".$data->member_id).' target="_blank">'.$data->username.'</a>  from connections';
    }
  }
  else if($type == "SM")
  {
    $data = ChatMessage::model()->model()->findByPk($parent_id);

    if(!empty($data))
    {
      $data  = 'Messaged '.$data->to->username;
    }
  }
  else if($type == "RSP")
  {
    $data = PostSetting::model()->findByPk($parent_id);
    if(!empty($data))
    {
      $data  = ' Reported '.$report_type.' for '.$data->post->member->username."'s post ";
    }
  }

  else if($type == "RT")
  {
    $data = PostRetag::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $reTag = $this->reTagedPost($data->post_id,$member_id);

      $rt_tag_list ='';
      foreach($reTag as $rt_val)
      {
        $rt_tag_list .= $rt_val[tag].',';
      }

      $data  = ' Retagged '.$data->post->member->username."’s post with ".$rt_tag_list;
    }
  }
  else if($type == "BU")
  {
    $data = BlockUser::model()->findByPk($parent_id);
    if(!empty($data))
    {
      $data   	 = 'Blocked user <a href='. Yii::app()->createUrl("site/friendTimeLine?friend_id=".$data->to_id).' target="_blank">'.$data->to->username.'</a>';
    }
  }
  else if($type == "CL")
  {
    $data = PostLike::model()->findByPk($parent_id); // like

    $data1 = PostComment::model()->findByPk($data->comment_id); //comment auther
    if(!empty($data) && !empty($data1))
    {
      $data  = 'Liked '.$data1->member->username. "'s comment";
    }
  }
  else if($type == "CR")
  {
    $data = PostComment::model()->findByPk($parent_id);
    $data1 = PostComment::model()->findByPk($data->parent_id);

    if(!empty($data))
    {
      $data   	= ' Replied to '.$data1->member->username."'s comment";
    }
  }
  else if($type == "RL")
  {
    $data = PostLike::model()->findByPk($parent_id); // like

    $data1 = PostComment::model()->findByPk($data->comment_id); //comment auther
    if(!empty($data) && !empty($data1))
    {
      $data  = 'Liked '.$data1->member->username. "'s reply";
    }
  }
  return $data;
}

/*User Changes profile*/


public function actionUpdateProfilePic()
{

  $member_id = ApplicationSessions::run()->read('member_id');

  if(!empty($member_id))
  {

    if(!is_dir("upload/member/profile_pic/"))
      mkdir("upload/member/profile_pic/" , 0777,true);

    $model = Member::model()->findByPk($member_id);
    if(!empty($model))
    {
      $model->updated_on = time();

      if(!empty($_FILES['image']['name']) )
      {
        $ext = explode(".",$_FILES['image']['name']);
        $image_name = time().".".$ext[1];
        $image_path = Yii::app()->basePath . '/../upload/member/profile_pic/'.$image_name;

        if(move_uploaded_file($_FILES['image']['tmp_name'],$image_path))
        {
          $model->profile_pic = $image_name;
        }


      }

      if($model->save())
      {
        ApplicationSessions::run()->write('member_pic', $model->profile_pic);
        $this->redirect(Yii::app()->createUrl('site/'));
      }
    }
    $this->redirect(Yii::app()->createUrl('site/'));
  }
  else
  {
    $this->redirect(Yii::app()->createUrl('site/index'));
  }
}

public function actionUpdateCoverPic()
{

  $member_id = ApplicationSessions::run()->read('member_id');

  if(!empty($member_id))
  {

    if(!is_dir("upload/member/cover_photo/"))
      mkdir("upload/member/cover_photo/" , 0777,true);

    $model = Member::model()->findByPk($member_id);
    if(!empty($model))
    {
      $model->updated_on = time();

      if(!empty($_FILES['Coverimage']['name']) )
      {
        $ext = explode(".",$_FILES['Coverimage']['name']);
        $image_name = time().".".$ext[1];
        $image_path = Yii::app()->basePath . '/../upload/member/cover_photo/'.$image_name;

        if(move_uploaded_file($_FILES['Coverimage']['tmp_name'],$image_path))
        {
          $model->cover_photo = $image_name;
        }
      }

      if($model->save())
      {
        ApplicationSessions::run()->write('cover_photo', $model->cover_photo);
        $this->redirect(Yii::app()->createUrl('site/'));
      }
    }
    $this->redirect(Yii::app()->createUrl('site/'));
  }
  else
  {
    $this->redirect(Yii::app()->createUrl('site/index'));
  }
}
/*show user Recent Post or stories start */

public function actionShowStories()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($member_id))
    {
      if(!empty($_REQUEST['friend_id']))
      {
        $friend_id = $_REQUEST['friend_id'];
        $limit = $_REQUEST['friend_id'];

        $condition = 'active_status="S" and status="1" and member_id='.$friend_id;

        $post = Post::model()->findAll(array('condition'=>$condition,'order'=>'post_id desc','limit'=>$limit));

        $member_post   = Post::model()->findAll(array('condition'=>'active_status="S" and member_id='.$friend_id,'order'=>'post_id desc'));
        $buddies_data  = Friends::model()->findAll(array('condition'=>'(from_id='.$friend_id.' || to_id='.$friend_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));

        $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$friend_id,'order'=>'user_activity_id desc'));

        //members all post
          $post_by_friend = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$friend_id));


        //post with attachment

        $attachment_post_ids = PostAttachment::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and post_id IN ("'.$post_by_friend->post_id.'")'));

        if(!empty($attachment_post_ids->post_id))
        {
          $attachment_post = Post::model()->findAll(array('condition'=>'active_status="S" and status="1" and post_id IN ("'.$attachment_post_ids->post_id.'")'));
        }
        else
        {
          $attachment_post = '';
        }

        $is_friend = Friends::model()->find(array('condition'=>'(from_id="'.$member_id.'" or to_id="'.$member_id.'") and (from_id="'.$_REQUEST['friend_id'].'" or to_id="'.$_REQUEST['friend_id'].'")'));

        $is_block = BlockUser::model()->find(array('condition'=>'active_status="S" and status="1" and from_id="'.$member_id.'" and to_id="'.$friend_id.'"'));

        $follower = FollowUser::model()->findAll(array('condition'=>'active_status="S" and status="1" and to_id ='.$_REQUEST['friend_id']));

        //is_follow

        $is_follow_friend = FollowUser::model()->find(array('condition'=>'active_status="S" and status="1" and from_id='.$member_id.' and to_id ='.$_REQUEST['friend_id']));

        $recent_member = $this->stories();

        /*updated latest post view for firnd */
          $max_post = Post::model()->find(array('select'=>'max(post_id) as post_id','condition'=>$condition));
          $this->PostView($member_id,$friend_id,$max_post->post_id);
      }
      else
      {
        $member_post = '';
        $buddies_data = '';
        $sent_data = '';
        $receive_data = '';
        $analytics = '';
        $attachment_post = '';
        $is_friend = '';
        $is_block = '';
        $is_follow_friend ='';
        $follower ='';
        $recent_member ='';
      }

      $this->render('friend_time_line',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'member_data'=>$member_data,'attachment_post'=>$attachment_post,'is_friend'=>$is_friend,'is_block'=>$is_block,'is_follow_friend'=>$is_follow_friend,'follower'=>$follower,'recent_member'=>$recent_member));
    }


    else
    {
      $this->redirect(Yii::app()->createUrl('site/index'));
    }
}

/*show user Recent Post or stories end */

/*friend Time line start*/

public function actionFriendTimeLine()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 0;
  $limit  = (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
  $newCursor = $limit + $cursor;

  if(!empty($member_id))
  {
    $loc_data = array();
    $friend_id ='';

    if(!empty($_REQUEST['friend_id']))
    {
      $friend_id = $_REQUEST['friend_id'];
    }
    else if(!empty($_REQUEST['username']))
    {
      $frnd_data = Member::model()->find(array('condition'=>'active_status="S" and status="1" and username="'.$_REQUEST['username'].'"'));
      if(!empty($frnd_data))
      {
        $friend_id = $frnd_data->member_id;
      }
    }

    $member_data = Member::model()->findByPk($friend_id);

    if(!empty($member_id))
    {
      if(!empty($member_data))
      {
        if($member_id == $friend_id)
        {
          $this->redirect('profileView');
        }

        $loc_data = $this->UserFollowedLocation($friend_id);
        /*****/

        $condition = 'active_status="S" and status="1" ';
        //own post from activity
          $own_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$friend_id));

          if(!empty($own_post->post_id))
          {
            $post_id = $own_post->post_id;
          }
        ////reported post ids
            $reported_post = $this->ReportedPost($member_id);

        //Post shared
          $shared_post = PostShare::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and type="T" and from_id='.$friend_id));

          if(!empty($shared_post->post_id))
          {
            if(!empty($post_id))
            {
              $post_id .= ','.$shared_post->post_id;
            }
            else
            {
              $post_id .= $shared_post->post_id;
            }
          }
          if(!empty($post_id))
          {
            $condition .= ' and post_id IN ('.$post_id.')';
          }
          else
          {
            $condition .= ' and member_id='.$friend_id;
          }
          if(!empty($reported_post))
          {
            $condition .= ' and post_id NOT IN ('.$reported_post.')';
          }
        /*****/

        $post = Post::model()->findAll(array('condition'=>$condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'post_id desc'));

        $is_friend = Friends::model()->find(array('condition'=>'(from_id="'.$member_id.'" or to_id="'.$member_id.'") and (from_id="'.$friend_id.'" or to_id="'.$friend_id.'")'));

        $is_block = BlockUser::model()->find(array('condition'=>'active_status="S" and status="1" and from_id="'.$member_id.'" and to_id="'.$friend_id.'"'));
        $is_block_by_frnd = BlockUser::model()->find(array('condition'=>'active_status="S" and status="1" and from_id="'.$friend_id.'" and to_id="'.$member_id.'"'));

        $follower = FollowUser::model()->findAll(array('condition'=>'active_status="S" and status="1" and to_id ='.$friend_id));

        //is_follow

        $is_follow_friend = FollowUser::model()->find(array('condition'=>'active_status="S" and status="1" and from_id='.$member_id.' and to_id ='.$friend_id));


        $member_post   	= Post::model()->findAll(array('condition'=>'active_status="S" and member_id='.$friend_id,'order'=>'post_id desc'));

        $buddies_data  	= Friends::model()->findAll(array('condition'=>'(from_id='.$friend_id.' || to_id='.$friend_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));

        $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$friend_id,'order'=>'user_activity_id desc'));

        $recent_member 	= $this->stories();

        //members all post
          $post_by_friend = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$friend_id));

        //post with attachment

        $attachment_post_ids = PostAttachment::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and post_id IN ("'.$post_by_friend->post_id.'")'));

        if(!empty($attachment_post_ids->post_id))
        {
          $attachment_post = Post::model()->findAll(array('condition'=>'active_status="S" and status="1" and post_id IN ("'.$attachment_post_ids->post_id.'")'));
        }
        else
        {
          $attachment_post = '';
        }
        /*own post id for gallery start*/
          $own_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$friend_id));

          if(!empty($own_post->post_id))
          {
            $gallery_data = PostAttachment::model()->findAll(array('condition'=>'active_status="S" and  status="1" and post_id IN ('.$own_post->post_id.')','order'=>'post_attachment_id desc'));
          }
          else
          {
            $gallery_data = array();
          }
        /*own post id for gallery end*/

         $this->render('friend_time_line',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'member_data'=>$member_data,'attachment_post'=>$attachment_post,'is_friend'=>$is_friend,'is_block'=>$is_block,'is_follow_friend'=>$is_follow_friend,'follower'=>$follower,'recent_member'=>$recent_member,'loc_data'=>$loc_data,'gallery_data'=>$gallery_data,'is_block_by_frnd'=>$is_block_by_frnd,'newCursor'=>$newCursor));
      }
      else
      {
        $this->redirect(Yii::app()->createUrl('site/index'));
      }
    }
    else
    {
      $this->redirect(Yii::app()->createUrl('site/index'));
    }
  }
  else
  {
    $this->redirect(Yii::app()->createUrl('site/index'));
  }
}

/*friend Time line End*/

/*Send Connection Request start*/
public function actionSendfriendRequest()
{
  $member_id = ApplicationSessions::run()->read('member_id');

  $friends = Friends::model()->find(array('condition'=>'is_accepted="Y" and is_block="N" and (from_id="'.$member_id.'" or to_id="'.$member_id.'") and (from_id="'.$_REQUEST['friend_id'].'" or to_id="'.$_REQUEST['friend_id'].'")'));

    if(empty($friends))
    {
      $model = new Friends;

      $model->from_id 	= $member_id;
      $model->to_id 		= $_REQUEST['friend_id'];
      $model->is_accepted = "N";
      $model->is_deleted 	= "N";
      $model->is_block 	= "N";
      $model->added_on 	= time();
      $model->updated_on 	= time();
      // $model->message 	= $_REQUEST['message'];
      $model->save();

      //storeUserActivity
        $this->storeUserActivity($member_id,"Sent connection request to ".$model->to->first_name." ".$model->to->last_name,"SF","Friends",$model->friends_id,$_REQUEST['friend_id']);

      echo "200";
    }
    else
    {
      echo "304";
    }
}

/*Send Connection Request end*/
/*Blocked user show start*/
public function actionBlockedUserList()
{
  $member_id = ApplicationSessions::run()->read('member_id');

  if(!empty($member_id))
  {
    $bloced_usr_id = $this->BlockedUserList($member_id);
    if(!empty($bloced_usr_id))
    {
      $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$bloced_usr_id.')'));

      if(!empty($member_data))
      {
        $html .= '<table>';
          foreach($member_data as $valUsr)
          {
            $profile_pic = $this->getProfilePic($valUsr->member_id);
            $bloced_usr_detls = BlockUser::model()->find(array('condition'=>'active_status="S" and status="1" and from_id='.$member_id.' and to_id='.$valUsr->member_id));

            $html .= '<div id="'.$bloced_usr_detls->block_user_id.'">
                <tr>
                  <td>
                    <div class="pbl-ewih" >
                        <div class="left-tex">
                          <div class="flot-left dp-icon">
                            <img class="img-responsive" src="'.$profile_pic.'">
                          </div>
                          <div class="flot-left fon-12">
                            <strong>'.$valUsr->username .'</strong> <br/>'. base64_decode($valUsr->first_name).' '.base64_decode($valUsr->last_name).'
                          </div>
                        </div>
                      </div>
                  </td>
                  <td>
                    <button class="btn-info" onclick="unblockUserPopup('.$bloced_usr_detls->block_user_id.')"> Un Block </button>
                  </td>
                </tr>
              </div>';
          }
        $html .= '</table>';
      }
    }

  }

  echo $html;
}
/*Blocked user show end*/
/*Block User*/
  public function actionBlocUserIfnotFriends()
  {
    $member_id = ApplicationSessions::run()->read('member_id');

        $blockModel = new BlockUser;

        $blockModel->from_id = $member_id;
        $blockModel->to_id 	= $_REQUEST['friend_id'];
        $blockModel->added_on = time();
        $blockModel->updated_on = time();
        $blockModel->save();

        $resp_code = Yii::app()->params['SUCCESS'];
        $resp = array('code'=>$resp_code,'msg'=>'User Block Successfully ','data'=>null);

        $is_follow = FollowUser::model()->find(array('condition'=>'active_status="S" and status="1" and from_id='.$member_id.' and to_id ='.$_REQUEST['friend_id']));

          if(!empty($is_follow))
          {
            FollowUser::model()->deleteByPk($is_follow->follow_user_id);
          }

        //storeUserActivity
          $this->storeUserActivity($member_id,"Blocked user ".$blockModel->to_member->username  ,"BU","BlockUser",$blockModel->block_user_id);

        echo "200";
  }

/*Block User*/

/*UnBlock User*/
public function actionUnBlocUserIfnotFriends()
{
  BlockUser::model()->deleteByPk($_REQUEST['block_user_id']);

  echo "200";

}


public function actionAutocompleteTag()
{
  $last_tag_postion = strrpos($_REQUEST['term'],'#');

  $last_username_postion = strrpos($_REQUEST['term'],'@');


  //last occurance contains # then show tags

    if ($last_tag_postion > $last_username_postion)
    {
      $term =  strrchr($_REQUEST['term'],'#');
      $data = CHtml::listData(Tags::model()->findAll(array('select'=>'tags as tags','condition'=>'Tags like "%'.$term.'%"')),'tags_id','tags');
    }
    else
    {
      $term =  strrchr($_REQUEST['term'],'@');
      $term =  str_replace("@","",$term);
      $data = CHtml::listData(Member::model()->findAll(array('select'=>'username as username','condition'=>'username like "%'.$term.'%"')),'member_id','username');
    }
  Controller::autocompleteJson($data);
}


public function actionNotifications()
{
  $member_id = ApplicationSessions::run()->read('member_id');
    if(!empty($member_id))
    {
      $activitylog = UserActivity::model()->findAll(array('condition'=>'active_status="S" and status="1" and type NOT IN("PL,SP,CP,CR,L")  and member_id !='.$member_id.' and  to_id ='.$member_id,'order'=>'user_activity_id DESC'));

      $tot_activity_count = UserActivity::model()->count(array('condition'=>'active_status="S"  and status="1" and type NOT IN("PL,SP,CP,CR,L")  and member_id !='.$member_id.' and to_id ='.$member_id,'order'=>'user_activity_id DESC'));

      $tot_activity_count	= $tot_activity_count;


      if(!empty($activitylog))
      {
        $i=0;
        foreach($activitylog as $ua_val)
        {
          $details = $this->getNotificaionDetails($ua_val->to_id,$ua_val->type,$ua_val->module,$ua_val->parent_id);
          if(!empty($details))
          {
            $data[$i]['user_activity_id'] 	= $ua_val->user_activity_id;
            $data[$i]['member_id'] 			= (!empty($ua_val->to_id)) ? $ua_val->to_id : " ";
            $data[$i]['activity'] 			=  $details['parameter']['activity'];
            $data[$i]['link'] 				=  (!empty($details['parameter']['link']))?$details['parameter']['link']:"";
            $data[$i]['user_id'] 			=  (!empty($details['parameter']['member_id']))?$details['parameter']['member_id']:"";
            $data[$i]['friend_detail'] 		=  (!empty($details['parameter']['friend_detail']))?$details['parameter']['friend_detail']:"";
            $data[$i]['activity_date'] 		= (!empty($ua_val->activity_date)) ? Controller::dateConvert($ua_val->activity_date) : " ";
            $i++;
          }
        }
      }
      else
      {
        $data = array();
      }

      return $data;

    }

  }


  public function getNotificaionDetails($member_id,$type,$module,$parent_id)
  {
  $details_arr = array();
  if($type == "L")
  {
    $data = PostLike::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->member_id;
      $member_data['friend_name']	   		= base64_decode($data->member->first_name )." ".base64_decode($data->member->last_name);
      $member_data['friend_username']   	= $data->member->username;
      $member_data['friend_profile_pic']	=   MobileApiController::getProfilePic($data->member_id);

      $postTag =	$this->getPostTags($data->post_id,$member_id);

      $post['post_id'] 				= $data->post_id;
      $post['post_description'] 		= $data->post->post;
      $post['postTag'] 				= $postTag;
      $post['image_attachment'] 		= $this->getPostAttachment($data->post_id,'P');
      $post['video_attachment'] 		= $this->getPostAttachment($data->post_id,'V');

      $params_data['member_id'] 		= $data->member_id;
      $params_data['post_id']   		= $data->post_id;
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'liked your post';
      $params_data['link']  			= Yii::app()->params['SITE_URL'].'site/share?post_id='.$data->post_id;

      $details_arr['parameter'] = $params_data;

    }
  }
  if($type == "S")
  {
    $data = PostShare::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->from_id;
      $member_data['friend_name']	   		= base64_decode($data->from->first_name)." ".base64_decode($data->from->last_name);
      $member_data['friend_username']   	= $data->from->username;
      $member_data['friend_profile_pic']	=   MobileApiController::getProfilePic($data->from_id);

      $postTag =	$this->getPostTags($data->post_id,$member_id);

      $post['post_id'] 				= $data->post_id;
      $post['post_description'] 		= $data->post->post;
      $post['postTag'] 				= $postTag;
      $post['image_attachment'] 		= $this->getPostAttachment($data->post_id,'P');
      $post['video_attachment'] 		= $this->getPostAttachment($data->post_id,'V');

      $params_data['member_id'] 		= $data->from_id;
      $params_data['post_id']   		= $data->post_id;
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'shared your post';
      $params_data['link']  			= Yii::app()->params['SITE_URL'].'site/share?post_id='.$data->post_id;

      $details_arr['parameter'] 		= $params_data;

    }
  }
  if($type == "C")
  {
    $data = PostComment::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->member_id;
      $member_data['friend_name']	   		= base64_decode($data->member->first_name)." ".base64_decode($data->member->last_name);
      $member_data['friend_username']   	= $data->member->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->member_id);

      $postTag =	$this->getPostTags($data->post_id,$member_id);

      $post['post_id'] 				= $data->post_id;
      $post['post_description'] 		= $data->post->post;
      $post['postTag'] 				= $postTag;
      $post['image_attachment'] 		= $this->getPostAttachment($data->post_id,'P');
      $post['video_attachment'] 		= $this->getPostAttachment($data->post_id,'V');

      $comment['comment_id'] 			= $data->post_comment_id;
      $comment['comment'] 			= $data->comment;

      $params_data['member_id'] 		= $data->member_id;
      $params_data['post_id']   		= $data->post_id;
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'commented on your post';
      $params_data['comment']  		= $comment;
      $params_data['link']  			= Yii::app()->params['SITE_URL'].'site/share?post_id='.$data->post_id.'&type=C';

      $details_arr['parameter'] 		= $params_data;

    }
  }
  if($type == "RT")
  {
    $data = PostRetag::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->member_id;
      $member_data['friend_name']	   		= base64_decode($data->member->first_name)." ".base64_decode($data->member->last_name);
      $member_data['friend_username']   	= $data->member->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->member_id);

      $postTag =	$this->getPostTags($data->post_id,$member_id);

      $post['post_id'] 		  = $data->post_id;
      $post['post_description'] = $data->post->post;
      $post['postTag'] 		  = $postTag;
      $post['image_attachment'] = $this->getPostAttachment($data->post_id,'P');
      $post['video_attachment'] = $this->getPostAttachment($data->post_id,'V');

      $reTag = $this->reTagedPost($data->post_id,$member_id);

      $rt_tag_list ='';
      foreach($reTag as $rt_val)
      {
        $rt_tag_list .= $rt_val[tag].',';
      }


      $params_data['member_id'] 		= $data->member_id;
      $params_data['post_id']   		= $data->post_id;
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'tagged your post';
      $params_data['reTag']  			= $reTag;
      $params_data['link']  			= Yii::app()->params['SITE_URL'].'site/share?post_id='.$data->post_id;

      $details_arr['parameter'] = $params_data;

    }
  }
  if($type == "PL")
  {
    $data = ProfileLike::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->member_id;
      $member_data['friend_name']	   		= base64_decode($data->member->first_name)." ".base64_decode($data->member->last_name);
      $member_data['friend_username']   	= $data->member->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->member_id);

      $post['post_id'] 				= '';
      $post['post_description'] 		='';
      $post['postTag'] = '';
      $post['image_attachment'] 		= '';
      $post['video_attachment'] 		='';

      $params_data['member_id'] 		= $data->member_id;
      $params_data['post_id']   		= '';
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'liked your profile';
      $params_data['link']  	  =	Yii::app()->params['SITE_URL'].'site/friendTimeLine?friend_id='.$data->member_id;

      $details_arr['parameter'] 		= $params_data;

    }
  }
  if($type == "CL" || $type == "RL")
  {
    $data = PostLike::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->member_id;
      $member_data['friend_name']	   		= base64_decode($data->member->first_name)." ".base64_decode($data->member->last_name);
      $member_data['friend_username']   	= $data->member->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->member_id);

      $comment_data 	= PostComment::model()->findByPk($data->comment_id);
      $postTag 		=	$this->getPostTags($comment_data->post_id,$member_id);

      $post['post_id'] 				= $comment_data->post_id;
      $post['post_description'] 		=$comment_data->post->post;
      $post['postTag'] = $postTag;
      $post['image_attachment'] 		= $this->getPostAttachment($comment_data->post_id,'P');
      $post['video_attachment'] 		= $this->getPostAttachment($comment_data->post_id,'V');


      $comment['comment_id'] 			= $comment_data->post_comment_id;
      $comment['comment'] 			= $comment_data->comment;

      $params_data['member_id'] 		= $data->member_id;
      $params_data['post_id']   		= '';
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['comment']   		= $comment;

      if($type == "CL")
        $params_data['activity']  = 'liked your comment';
      else if($type == "RL")
        $params_data['activity']  = 'liked your reply';

      $params_data['link']  = Yii::app()->params['SITE_URL'].'site/share?post_id='.$comment_data->post_id;

      $details_arr['parameter'] = $params_data;

    }
  }
  else if($type == "CR")
  {
    $data 	= PostComment::model()->findByPk($parent_id);
    $data1 	= PostComment::model()->findByPk($data->parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->member_id;
      $member_data['friend_name']	   		= base64_decode($data->member->first_name)." ".base64_decode($data->member->last_name);
      $member_data['friend_username']   	= $data->member->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->member_id);

      $postTag 							=	$this->getPostTags($data->post_id,$member_id);
      $post['post_id'] 					= $data->post_id;
      $post['post_description'] 			=$data->post->post;
      $post['postTag'] 					= $postTag;
      $post['image_attachment'] 			= $this->getPostAttachment($data->post_id,'P');
      $post['video_attachment']			=$this->getPostAttachment($data->post_id,'V');


      $comment['comment_id'] 				= $data1->post_comment_id;
      $comment['comment'] 				= $data1->comment;

      $comment_reply['comment_id'] 		= $data->post_comment_id;
      $comment_reply['comment'] 			= $data->comment;


      $params_data['member_id'] 			= $data->member_id;
      $params_data['post_id']   			= $data->post_id;

      $params_data['friend_detail']    	= $member_data;
      $params_data['post_detail']   		= $post;
      $params_data['comment']    			= $comment;
      $params_data['comment_reply']    	= $comment_reply;

      $params_data['activity']   			= 'replied to your comment';
      $params_data['link']  				= Yii::app()->params['SITE_URL'].'site/share?post_id='.$data->post_id;

      $details_arr['parameter'] 	= $params_data;

    }
  }
  if($type == "SF")
  {
    $data = Friends::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->from_id;
      $member_data['friend_name']	   		= base64_decode($data->from->first_name)." ".base64_decode($data->from->last_name);
      $member_data['friend_username']   	= $data->from->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->from_id);

      $post['post_id'] 			= '';
      $post['post_description'] 	= '';
      $post['postTag'] 			= '';
      $post['image_attachment'] 	= '';
      $post['video_attachment'] 	= '';

      $params_data['member_id'] 		= $data->from;
      $params_data['post_id']   		= '';
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'sent you a connection request';
      $params_data['link']  	 		=	Yii::app()->params['SITE_URL'].'site/friendTimeLine?friend_id='.$data->from_id;

      $details_arr['parameter'] 		= $params_data;

    }
  }
  if($type == "AF")
  {
    $data = Friends::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->to_id;
      $member_data['friend_name']	   		= base64_decode($data->to->first_name)." ".base64_decode($data->to->last_name);
      $member_data['friend_username']   	= $data->to->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->to_id);

      $post['post_id'] 			= '';
      $post['post_description'] 	='';
      $post['postTag'] 			= '';
      $post['image_attachment'] 	= '';
      $post['video_attachment'] 	='';
      $params_data['member_id'] 		= $data->to_id;
      $params_data['post_id']   		= '';
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'accepted your connection request';
      $params_data['link']  	  		=	Yii::app()->params['SITE_URL'].'site/friendTimeLine?friend_id='.$data->to_id;

      $details_arr['parameter'] 		= $params_data;

    }
  }
  if($type == "TP")
  {
    $data = Post::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->member_id;
      $member_data['friend_name']	   		= base64_decode($data->member->first_name)." ".base64_decode($data->member->last_name);
      $member_data['friend_username']   	= $data->member->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->member_id);
      $postTag 							=	$this->getPostTags($data->post_id,$member_id);

      $post['post_id'] 				= $data->post_id;
      $post['post_description'] 		= $data->post;
      $post['postTag'] 				= $postTag;
      $post['image_attachment'] 		= $this->getPostAttachment($data->post_id,'P');
      $post['video_attachment'] 		= $this->getPostAttachment($data->post_id,'V');

      $params_data['member_id'] 		= $data->member_id;
      $params_data['post_id']   		= $data->post_id;
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'tagged you in a post ';
      $params_data['link']  			= Yii::app()->params['SITE_URL'].'site/share?post_id='.$data->post_id;

      $details_arr['parameter'] 		= $params_data;

    }
  }

  if($type == "FU")
  {
    $data = FollowUser::model()->findByPk($parent_id);

    if(!empty($data))
    {

      $member_data['friend_id']  			= $data->from_id;
      $member_data['friend_name']	   		= (!empty($data->from->first_name)) ?  base64_decode($data->from->first_name)." ".base64_decode($data->from->last_name) : "";
      $member_data['friend_username']   	= $data->from->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->from_id);

      $post['post_id'] 				= '';
      $post['post_description'] 		='';
      $post['postTag'] 				= '';
      $post['image_attachment'] 		= '';
      $post['video_attachment'] 		='';
      $params_data['member_id'] 		= $data->to_id;
      $params_data['post_id']   		= '';
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'started following you';
      $params_data['link']  	  		=  Yii::app()->params['SITE_URL'].'site/friendTimeLine?friend_id='.$data->from_id;
      $details_arr['parameter'] 		= $params_data;

    }
  }
  if($type == "TC")
  {
    $data = PostComment::model()->findByPk($parent_id);

    if(!empty($data))
    {
      $member_data['friend_id']  			= $data->member_id;
      $member_data['friend_name']	   		= base64_decode($data->member->first_name) ." ".base64_decode($data->member->last_name);
      $member_data['friend_username']   	= $data->member->username;
      $member_data['friend_profile_pic']	= MobileApiController::getProfilePic($data->member_id);

      $postTag =	$this->getPostTags($data->post_id,$member_id);

      $post['post_id'] 				= $data->post_id;
      $post['post_description'] 		= $data->post->post;
      $post['postTag'] 				= $postTag;
      $post['image_attachment'] 		= $this->getPostAttachment($data->post_id,'P');
      $post['video_attachment'] 		= $this->getPostAttachment($data->post_id,'V');

      $comment['comment_id'] 			= $data->post_comment_id;
      $comment['comment'] 			= $data->comment;
      $comment_type 					= ($data->type=="C") ? "comment" : "reply";
      $params_data['member_id'] 		= $data->member_id;
      $params_data['post_id']   		= $data->post_id;
      $params_data['friend_detail'] 	= $member_data;
      $params_data['post_detail']   	= $post;
      $params_data['activity']  		= 'tagged you in a '.$comment_type;
      $params_data['comment']  		= $comment;
      $params_data['link']  			= Yii::app()->params['SITE_URL'].'site/share?post_id='.$data->post_id;

      $details_arr['parameter'] 	= $params_data;

    }
  }
  return $details_arr;
}


//update Status of Notification view to Yes

  public function actionChangeStatus()
  {
    $member_id = ApplicationSessions::run()->read('member_id');

    $max_user_activity_id = UserActivity::model()->find(array('select'=>'max(user_activity_id) as user_activity_id','condition'=>'to_id ='.$member_id));

     UserActivity::model()->updateAll(array('is_view'=>'Y'),'to_id='.$member_id);

  }

  public function actionChangeActivitiSataus()
  {
    $member_id = ApplicationSessions::run()->read('member_id');
    UserActivity::model()->updateByPk($_REQUEST['activity_id'],array('is_view'=>'Y'));

    $notification_count = UserActivity::model()->count(array('condition'=>'active_status="S" and status="1" and to_id='.$member_id));
    ApplicationSessions::run()->write('notification_count', $notification_count);
    echo "200";
  }

/*page scroll*/
  public function actionAutoloadpost()
  {
    $item_per_page = 15;

    $page_number = filter_var($_REQUEST["page"], FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_HIGH);
    if(!is_numeric($page_number))
    {
      header('HTTP/1.1 500 Invalid page number!');
      exit();
    }
    $position = (($page_number-1) * $item_per_page);

    $sql="SELECT * FROM tbl_post ORDER BY post_id LIMIT ".$position.", ".$item_per_page;
    $connection	= Yii::app()->db;

    $command	=$connection->createCommand($sql);
    $command->bindParam("dd", $position, $item_per_page);
    $command->bindValue(":position",1,PDO::PARAM_INT);//Here I made the change to 'bindValue'
    $command->execute();
    $dataReader=$command->queryAll(); // execute a query SQL

    //output results from database
    foreach($dataReader as $val){ //fetch values
      echo '<li>'.$val['post_id'].') <strong>'.$val['post'].'</strong> : '.$val['title'].'</li><br/>';

    }

  }


/*Page scroll*/

/*Show post Shared on Social media */
  public function actionShare()
  {
    $post = Post::model()->findAll(array('condition'=>'active_status="S" and status="1" and post_id = '.$_REQUEST['post_id']));

    $member_post = '';
    $buddies_data = '';
    $sent_data = '';
    $receive_data = '';
    $analytics = '';
    $member_data = '';
    $attachment_post = '';
    $is_friend = '';
    $is_block = '';

    $this->render('shareOnsocialmedia',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'member_data'=>$member_data,'attachment_post'=>$attachment_post,'is_friend'=>$is_friend,'is_block'=>$is_block));
  }

/*Show post shared on social media*/


/*User Name is Availabel or not Start */
  public function actionUsernameCheck()
  {
      $model = Member::model()->findAll(array('condition'=>'username like "%'.$_REQUEST['username'].'%"'));

      if(empty($model))
      {
        echo "200";
      }
      else
      {
        echo "400";
      }
  }
/*User Name is Availabel or not end */

/*Eamil Id is Availabel or not Start */
  public function actionEmailCheck()
  {
      $model = Member::model()->findAll(array('condition'=>'email_id like "%'.$_REQUEST['email'].'%"'));

      if(empty($model))
      {
        echo "200";
      }
      else
      {
        echo "400";
      }
  }
/*Email Id is Availabel or not end */


// getLocationAdd function start for Registration
public function actionGetLocationAddRegisteration()
{
  $location = $_REQUEST['location'];
  $html = '';

  if(!empty($location))
  {
    $location_arr = explode("::",$location);
    $i = 0;

    foreach ($location_arr as $val)
    {
      if($i==0)
      {
        $class = 'd-ft-bor';
        $i++;
      }
      else
      {
        $class = '';
      }

      $html .= '<div class="clearfix pop-pad '.$class.'">
            <div class="pbl-ewih mar-none">
              <div class="left-tex">
                <div class="flot-left mt10">
                  <strong><span id="'.$_REQUEST['id'].'" onclick="FollowLocationRegiter(this);">'.$val.'</span></strong>
                </div>
              </div>
            </div>
          </div>';
    }

    echo "200::".$html;
  }
  else
  {
    echo "400::".$html;
  }
}


public function actionGetLocationAddRegisterationSocialLogin()
{
  $location = $_REQUEST['location'];
  $html = '';

  if(!empty($location))
  {
    $location_arr = explode("::",$location);
    $i = 0;

    foreach ($location_arr as $val)
    {
      if($i==0)
      {
        $class = 'd-ft-bor';
        $i++;
      }
      else
      {
        $class = '';
      }

      $html .= '<div class="clearfix pop-pad '.$class.'">
            <div class="pbl-ewih mar-none">
              <div class="left-tex">
                <div class="flot-left mt10">
                  <strong><span id="'.$_REQUEST['id'].'" onclick="FollowLocationSocial(this);">'.$val.'</span></strong>
                </div>
              </div>
            </div>
          </div>';
    }

    echo "200::".$html;
  }
  else
  {
    echo "400::".$html;
  }
}

// getLocationAdd function end for Registration


public function actionLocationAttachment()
{

  $member_id =  ApplicationSessions::run()->read('member_id');
  if(!empty($_FILES['location_attachment_files']['name']))
  {
    if(!is_dir("upload/location_attachment/"))
    {
      mkdir("upload/location_attachment/" , 0777,true);
    }
    $location_data = Location::model()->findByPk($_REQUEST['location_attachment_location_id']);
    $master_model = new LocationAttachmentsMaster;

    $master_model->location_id		=	$location_data->location_id;
    $master_model->latitude			=	(!empty($location_data->latitude))?$location_data->latitude:"";
    $master_model->longitude		=	(!empty($location_data->longitude))?$location_data->longitude:"";
    $master_model->member_id		=	$member_id;
    $master_model->added_on			=	time();
    $master_model->updated_on		=	time();
    if($master_model->save())
    {

      foreach($_FILES['location_attachment_files']['name'] as $key=>$val)
      {
        $tmpFilePath = $_FILES['location_attachment_files']['tmp_name'][$key];
        $caption_val  = (!empty($_REQUEST['caption_img'][$key]))?$_REQUEST['caption_img'][$key]:'';
        if ($tmpFilePath != "")
        {
          $image_path = Yii::app()->basePath . '/../upload/location_attachment/';

          $ext = explode(".",$_FILES['location_attachment_files']['name'][$key]);
          $image_name = time().".".$ext[1];

          $newFilePath = $image_path . $image_name;

          if(move_uploaded_file($tmpFilePath, $newFilePath))
          {
              $model = new LocationAttachments;
              $model->location_attachments_master_id		=	$master_model->location_attachments_master_id;
              $model->attachment 		= 	$image_name;
              $model->location_id		=	$location_data->location_id;
              $model->latitude		=	(!empty($location_data->latitude))?$location_data->latitude:"";
              $model->longitude		=	(!empty($location_data->longitude))?$location_data->longitude:"";
              $model->member_id		=	$member_id;
              $model->type		=	'I';
              $model->added_on	=	time();
              $model->updated_on	=	time();
              if(!empty($_REQUEST['caption_img'][$key]))
              {
                $model->caption= $caption_val;
              }


              $model->save();
          }
        }
      }
        echo '200';
    }
    else
    {
        echo '400';
    }
  }
  else
  {
      echo '400';
  }
}


public function actionReportlocationAttachment()
{
  $location_attachment = Post::model()->findByPk($_REQUEST['location_attachments_id']);
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($post_data))
  {

    $model = new PostSetting;
    $model->post_id 	= $_REQUEST['location_attachments_id'];
    $model->member_id 	= $member_id;
    $model->type 		= $_REQUEST['type'];
    if($_REQUEST['type']=='R')
    {
      $model->report_type = (!empty($_REQUEST['report_type'])) ? $_REQUEST['report_type'] : '';
    }
    $model->post_type =	'L';
    $model->added_on 	=  time();
    $model->updated_on  =  time();

    if($model->save())
    {
      echo "200";
    }
    else
    {
      echo "400";
    }
  }
  else
    {
      echo "400";
    }
}


public function actionLocationAttachmentViews()
{

  $member_id = ApplicationSessions::run()->read('member_id');

  if(!empty($member_id))
  {
    $condition = 'active_status="S" and status="1"';

    $abuse_post = PostSetting::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'member_id='.$member_id));

    $block_frnd = BlockUser::model()->find(array('select'=>'group_concat(to_id) as to_id','condition'=>'active_status="S" and status="1" and from_id='.$member_id));

    //following location
      $userLocation = Location::model()->find(array('select'=>'group_concat(latitude) as latitude ,group_concat(longitude) as longitude','condition'=>'active_status="S" and status="1" and member_id ='.$member_id));

    if(!empty($userLocation->latitude) || !empty($userLocation->longitude))
    {
      $location_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and latitude like "%'.$userLocation->latitude.'%" and longitude like "%'.$userLocation->longitude.'%"'));
    }

    //location attachment
    $location_data = Location::model()->find(array('condition'=>'active_status="S" and status="1" and member_id='.$member_id));
    if(!empty($location_data))
    {
      $location_attachment = LocationAttachmentsMaster::model()->findAll(array('condition'=>'active_status="S" and status="1" and latitude='.$location_data->latitude.' and longitude='.$location_data->longitude));
    }
    else
    {
      $location_attachment = '';
    }



  //user member_id who follows same location
        $is_userLocation_id = Location::model()->find(array('select'=>'group_concat(location_name) as location_name','condition'=>'active_status="S" and status="1" and member_id ='.$member_id));

        if(!empty($is_userLocation_id->location_name))
        {
          $userLocation_id = Location::model()->find(array('select'=>'group_concat(member_id) as member_id','condition'=>'active_status="S" and status="1" and location_name IN ("'.$is_userLocation_id->location_name.'")'));
        }


  //Contact list from phone

    $phone_contact_list = Contact::model()->find(array('select'=>'contact_ids','condition'=>'active_status="S" and status="1" and member_id='.$member_id));

      if(!empty($block_frnd->to_id))
      {
        $condition .= 'and member_id NOT IN('.$block_frnd->to_id.')';
      }

      if(!empty($abuse_post->post_id))
      {
        $condition .= ' and post_id NOT IN('.$abuse_post->post_id.')';
      }
      if(!empty($location_post->post_id))
      {
        $condition .= ' and post_id  IN('.$location_post->post_id.')';
      }
      if(!empty($phone_contact_list->contact_ids))
      {
        $condition .= ' or member_id  IN('.$phone_contact_list->contact_ids.')';
      }
      if(!empty($userLocation_id->member_id))
      {
        $condition .= ' or member_id  IN('.$userLocation_id->member_id.')';
      }

    $tag_follow = TagFollow::model()->find(array('select'=>'group_concat(tag_id) as tag_id','condition'=>'member_id='.$member_id));


    if(!empty($tag_follow->tag_id))
    {
      $tag_post_condition = '';
      $retag_post_condition = '';
      $post_share_condition = 'active_status="S" and status="1" and (type="T" or  type="L" or  type="F")';


      if(!empty($tag_follow->tag_id))
      {
        $tag_post_condition .= ' tags_id IN('.$tag_follow->tag_id.')';
      }
      else if(!empty($abuse_post->post_id))
      {
        $tag_post_condition .= '  and post_id IS NOT NULL and post_id NOT IN('.$abuse_post->post_id.')';
      }

      if(!empty($tag_follow->tag_id))
      {
        $retag_post_condition .= ' tag_id IN('.$tag_follow->tag_id.')';
      }
      else if(!empty($abuse_post->post_id))
      {
        $retag_post_condition .= '  and post_id IS NOT NULL and post_id NOT IN('.$abuse_post->post_id.')';
      }

      if(!empty($abuse_post->post_id))
      {
        $post_share_condition .= '  and post_id IS NOT NULL and post_id NOT IN('.$abuse_post->post_id.')';
      }

      $tag_post = PostTags::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>$tag_post_condition));

      $retag_post = PostRetag::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>$retag_post_condition));

      //Sharedpost

      $shared_post_ids = PostShare::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>$post_share_condition));

      $post_ids = '';

      if(!empty($tag_post->post_id))
      {
        $post_ids .= trim($tag_post->post_id,",");
      }

      if(!empty($retag_post->post_id))
      {
        $post_ids .= ",".trim($retag_post->post_id,",");
      }
      if(!empty($shared_post_ids->post_id))
      {
        // echo "<pre>";
      // print_r($shared_post_ids->post_id);
      // exit;
        $post_ids .= ",".trim($shared_post_ids->post_id,",");
      }

      $post_ids = array_unique(explode(",",trim($post_ids,",")));
      $post = Post::model()->findAll(array('condition'=>$condition.' and (member_id="'.$member_id.'" or post_id IN('.implode(",",$post_ids).'))','order'=>'post_id desc'));
    }
    else
    {
      $post = Post::model()->findAll(array('condition'=>'active_status="S"','order'=>'post_id desc','limit'=>10));
    }

  }
  else
  {
    $post = Post::model()->findAll(array('condition'=>'active_status="S"','order'=>'post_id desc','limit'=>10));
  }

  if(!empty($member_id))
  {
    $member_post   = Post::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'post_id desc'));
    $buddies_data  = Friends::model()->findAll(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));
    $sent_data	   = Friends::model()->findAll(array('condition'=>'from_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
    $receive_data  = Friends::model()->findAll(array('condition'=>'to_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
    $buddies_count = Friends::model()->count(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

    $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'user_activity_id desc'));
  }
  else
  {
    $member_post = '';
    $buddies_data = '';
    $sent_data = '';
    $receive_data = '';
    $analytics = '';
    $location_attachment = '';
  }
  $this->render('showLocationMedia',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'location_attachment'=>$location_attachment));
}


public function actionGetLocationMediaLikes()
{
  $location_attachments_id = $_REQUEST['location_attachments_id'];
  $html = '';

  if(!empty($location_attachments_id))
  {
    //
    $like_data = LocationMediaLikes::model()->findAll(array('condition'=>'location_attachments_master_id='.$location_attachments_id));

    if(!empty($like_data))
    {
      foreach($like_data as $val)
      {
        $member = Member::model()->find(array('condition'=>'member_id='.$val->member_id));

        if(empty($member->profile_pic))
        {
          $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
        }
        else if(!empty($member->profile_pic) && strpos($member->profile_pic,"http")===false)
        {
          $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$member->profile_pic;
        }
        else
        {
          $profile_pic = $member->profile_pic;
        }

        $html .= '<div class="flt-ion-tb" style="width:98%!important;margin-bottom:2px!important;">

            <div class="left-tex">
              <div class="flot-left dp-icon-tb">
                <img class="img-responsive" src="'.$profile_pic.'">
              </div>
              <div class="flot-left">
                <strong>'.$member->first_name.' '.$member->last_name.'</strong>
              </div>
            </div>
          </div>';
      }


    }
    else
    {
      $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Likes Found</strong>
              </div>
            </div>
          </div>';
    }
  }

  echo $html;
}


/*Location Media Likes*/
public function actionLocation_attachments_Like()
{
  $member_id = $_REQUEST['member_id'];
  $location_attachments_id = $_REQUEST['location_attachments_id'];
  $like_data = LocationMediaLikes::model()->find(array('condition'=>'location_attachments_master_id='.$location_attachments_id.' and member_id='.$member_id));

  if(!empty($like_data))
  {
    LocationMediaLikes::model()->deleteByPk($like_data->location_media_likes_id);

    $like = LocationAttachmentsMaster::model()->findByPk($_REQUEST['location_attachments_id']);
    $tot_like = $like->like_count - 1;

    if($tot_like < 0)
    {
      $tot_like = 0;
    }
    LocationAttachmentsMaster::model()->updateByPk($_REQUEST['location_attachments_id'],array('like_count'=>$tot_like));

  }
  else
  {
    $like = LocationAttachmentsMaster::model()->findByPk($_REQUEST['location_attachments_id']);
    $tot_like = $like->like_count + 1;


    LocationAttachmentsMaster::model()->updateByPk($_REQUEST['location_attachments_id'],array('like_count'=>$tot_like));



    $model = new LocationMediaLikes;
    $model->location_attachments_master_id = $location_attachments_id;
    $model->member_id = $member_id;
    $model->type = "L";
    $model->added_on = time();
    $model->updated_on = time();
    $model->save(false);

  }
    // echo $tot_like;
  $data = LocationAttachmentsMaster::model()->findByPk($location_attachments_id);
  echo $data->like_count;
}


/*Location Media Likes*/


public function actionReportLocationMedia()
{
  //'='+location_attachments_id+'&type='+type+'&report_type='+report_type,

  $location_attachments = LocationAttachmentsMaster::model()->findByPk($_REQUEST['location_attachments_id']);
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($location_attachments))
  {


    $model = new PostSetting;
    $model->post_id 	= $_REQUEST['location_attachments_id']; ///this location_attachments_master_id
    $model->member_id 	= $member_id;
    $model->type 		= $_REQUEST['type'];
    if($_REQUEST['type']=='R')
    {
      $model->report_type = (!empty($_REQUEST['report_type'])) ? $_REQUEST['report_type'] : '';
    }
    $model->post_type 	=  'L';
    $model->added_on 	=  time();
    $model->updated_on  =  time();

    if($model->save(false))
    {

      echo "200";
    }
    else
    {
      echo "400";

    }
  }
  else
    {
      echo "400";
    }
}


public function actionFollowUser()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $is_follow = FollowUser::model()->find(array('condition'=>'active_status="S" and status="1" and from_id='.$member_id.' and to_id ='.$_REQUEST['friend_id']));

  if(empty($is_follow))
  {
    $model = new  FollowUser;
    $model->from_id 	= $member_id;
    $model->to_id 		= $_REQUEST['friend_id'];
    $model->added_on 	= time();
    $model->updated_on 	= time();
    $model->save(false);

    $followUserCnt = $this->getFollowerAdmin($_REQUEST['friend_id']);
    echo "200::".$followUserCnt;
  }
  else
  {
    FollowUser::model()->deleteByPk($is_follow->follow_user_id);
    $followUserCnt = $this->getFollowerAdmin($_REQUEST['friend_id']);
    echo "200::".$followUserCnt;
  }
}

public function actionfollwingMemberList()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $is_follow = FollowUser::model()->findAll(array('condition'=>'active_status="S" and status="1" and from_id='.$member_id));

  if(!empty($is_follow))
    {
      foreach($is_follow as $val_toid)
      {
        $val =  $this->FriendShortInfo($val_toid->to_id);
        if(!empty($val))
        {
          $html .= $val;
        }
      }
    }
    else
    {
      $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No User Found</strong>
              </div>
            </div>
          </div>';
    }
  echo $html;
}

public function actionFollerMemberList()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $is_follow = FollowUser::model()->findAll(array('condition'=>'active_status="S" and status="1" and to_id='.$member_id));

  if(!empty($is_follow))
    {
      foreach($is_follow as $val_toid)
      {
        $val =  $this->FriendShortInfo($val_toid->from_id);
        if(!empty($val))
        {
          $html .= $val;
        }
      }
    }
    else
    {
      $html .= '<div class="flt-ion-tb" style="width:98%!important;">

            <div class="left-tex">
              <div class="flot-left">
                <strong>No Follwer</strong>
              </div>
            </div>
          </div>';
    }
  echo $html;
}


/*Stroires like Instagram or Facebook start*/

public function Stories()
{
  $days = 90;

  $member_id = ApplicationSessions::run()->read('member_id');
  /*friends */
  $friends = Friends::model()->find(array('select'=>'group_concat(from_id,",",to_id) as from_id ','condition'=>'active_status="S" and status="1" and  is_accepted="Y" and is_block="N" and (from_id="'.$member_id.'" or to_id="'.$member_id.'")'));

  /*FollowUser*/
  $follow_user_list = FollowUser::model()->find(array('select'=>'group_concat(to_id)as member_id','condition'=>'active_status="S" and status="1" and from_id='.$member_id));

  /*post from same location follwer */
  $post_share_member_ids ='';
    if(!empty($friends->from_id) )
    {
      $post_share_member_ids .= $friends->from_id;
    }
    if(!empty($follow_user_list->member_id))
    {
      if(empty($post_share_member_ids))
      {
        $post_share_member_ids .= $follow_user_list->member_id;
      }
      else
      {
        $post_share_member_ids .= ','.$follow_user_list->member_id;
      }

    }
    if( $post_share_member_ids[0] ==",")
    {
      $post_share_member_ids = substr($post_share_member_ids, 1);
    }

    /*cursor limit setting */

    if(!empty($_REQUEST['cursor']))
    {
      $cursor = $_REQUEST['cursor'];
    }
    else
    {
      $cursor =0;
    }
    if(!empty($_REQUEST['limit']))
    {
      $limit = $_REQUEST['limit'];
    }
    else
    {
      $limit = 20;
    }


    $user_location =  Location::model()->find(array('select'=>'group_concat(location_master_id) as location_master_id','condition'=>'active_status="S" and status="1" and member_id='.$member_id));

    $user_location =  Location::model()->find(array('select'=>'group_concat(location_master_id) as location_master_id','condition'=>'active_status="S" and status="1" and member_id='.$member_id));

    if(!empty($user_location->location_master_id))
    {
      $condition = 'location_master_id IN ('.$user_location->location_master_id.') and member_id !='.$member_id;

      $location_follwer = Location::model()->find(array('select'=>'group_concat(DISTINCT(member_id)) as member_id','condition'=>$condition,'order'=>'rand()'));

    }

    //same location  statrt
    if(!empty($location_follwer->member_id))
    {
      $condition = 'active_status="S" and status="1" and member_id !='.$member_id;

      $hide_user_list = Member::model()->findByPk($member_id);

        if(!empty($hide_user_list->hide_user_id))
          {
            $condition .=' and  member_id NOT IN ('.$hide_user_list->hide_user_id.')';
          }
        if(!empty($post_share_member_ids))
        {
          $condition .=' and  member_id NOT IN ('.$post_share_member_ids.')';
        }

      $user_who_post = Post::model()->findAll(array('condition'=>$condition.' and added_on >='.strtotime('-'.$days.' day'),'offset'=>$cursor,'limit'=>$limit,'group'=>'member_id','order'=>'post_id desc'));


      $i=0;
      foreach($user_who_post as $val)
      {
        $is_exists = Member::model()->findByPk($val->member_id);
        if(!empty($is_exists))
        {
            if($i < $limit)
            {
              $details = $this->GetBasicDeatilsOfFriend($member_id,$val->member_id);
              $data[$i] = $details;

              $i++;
            }
        }

      }
    return $data;
    }
    //same location end
    else
    {
      $condition = 'active_status = "S" and status="1" and member_id !='.$member_id;

          $hide_user_list = Member::model()->findByPk($member_id);
          if(!empty($hide_user_list->hide_user_id))
          {
            $condition .=' and member_id NOT IN ('.$hide_user_list->hide_user_id.')';
          }

          if(!empty($post_share_member_ids))
          {
            $condition .=' and  member_id NOT IN ('.$post_share_member_ids.')';
          }
        $member_data = Member::model()->findAll(array('condition'=>$condition,'limit'=>$limit,'order'=>'rand()'));

        $user_who_post = Post::model()->findAll(array('condition'=>$condition.' and added_on >='.strtotime('-'.$days.' day'),'offset'=>$cursor,'limit'=>$limit,'group'=>'member_id','order'=>'post_id desc'));



        if($user_who_post)
        {
          $i=0;
          foreach($user_who_post as $val)
          {
            if($i< $limit)
            {
              $details = $this->GetBasicDeatilsOfFriend($member_id,$val->member_id);
              $data[$i] = $details;

              $i++;
            }
          }

          // sort array as per post_id desc
          if(!empty($data))
            {
                $sortArray = array();

                foreach($data as $person)
                {
                  foreach($person as $key=>$value)
                  {
                    if(!isset($sortArray[$key]))
                    {
                      $sortArray[$key] = array();
                    }
                    $sortArray[$key][] = $value;
                  }
                }

                $orderby = "latest_post_id"; //change this to whatever key you want from the array

                array_multisort($sortArray[$orderby],SORT_DESC,$data);
            }
        }
      return $data;
    }

    return $data;
    //same location  end

    // $this->printBreak($data);

}


/*Stroires like Instagram or Facebook end*/

/*set user name & Location for social login 1st time start*/
  public function actionSetSocialuserNameLocation()
  {
    $member_id = ApplicationSessions::run()->read('member_id');

    if(!empty($member_id))
    {


      $model = Member::model()->findByPk($member_id);

      Member::model()->updateByPk($member_id,array('username'=>$_REQUEST['user_name_social']));

      /*Add location */

      if(!empty($_REQUEST['location_follow_social']))
      {
        foreach($_REQUEST['location_follow_social'] as $val_loc)
        {
          $location_model = new Location;
          $address = $this->getLocation($val_loc);
          $address_array = explode(',',$address);

          $location_master = LocationMaster::model()->find(array('condition'=>'latitude='.round($address_array[0],4).' and longitude='.round($address_array[1],4)));

              if(empty($location_master))
              {
                $location_master = new LocationMaster;
                $location_master->location_name = $val_loc;
                $location_master->latitude 		= $address_array[0];
                $location_master->longitude 	= $address_array[1];
                $location_master->added_on		= time();
                $location_master->updated_on	= time();

                $location_master->save();
              }



            $location_model->member_id 	  	= $member_id;
            $location_model->location_master_id = $location_master->location_master_id;
            $location_model->location_name 	= $val_loc;
            $location_model->latitude 		= $address_array[0];
            $location_model->longitude 		= $address_array[1];
            $location_model->added_on		= time();
            $location_model->updated_on		= time();
            $location_model->save();
        }

      }

      /*Add location */
    }
    echo "200";


  }

/*set user name & Location for social login 1st time end*/

/*Search autocomplete for user & tags statrt */

public function actionUserAutocomplete()
{
  $term = $_REQUEST['term'];
  $data_arr = array();

  /*user sugesstion */
    if($term[0] != '#')
    {
      $data = Member::model()->findAll(array('condition'=>' active_status="S" and status="1" and (first_name like "%'.$term.'%" or last_name like "%'.$term.'%" or username like "%'.$term.'%" )'));


          if(!empty($data))
          {

            foreach($data as $val)
            {
              $profile_pic = $this->getProfilePic($member->member_id);
              $data_arr[$val->member_id]['label'] = $val->username;
              $data_arr[$val->member_id]['desc'] = $profile_pic;
            }
          }

      Controller::autocompleteJsonUser($data_arr);

    }
    else
    {
        $data = Tags::model()->findAll(array('condition'=>'tags like"%'.$term.'%"'));

          if(!empty($data))
          {

            foreach($data as $val)
            {
              $data_arr[$val->tags_id]['label']  = $val->tags;
              $data_arr[$val->tags_id]['desc'] = $val->tags;
            }
          }
      Controller::autocompleteJsonUser($data_arr);
    }

  /*user sugesstion */
}

/*Search autocomplete for user & tags end */

public function actionAutocompleteRedirect()
{
  $term 	= $_POST['term'];
  $id 	= $_POST['id'];

  if($term=='Are We Missing Something ?')
  {
    $resp = Yii::app()->createUrl('site/');
  }
  else
  {
    $res="else";
    if (strpos($term,'#') !== false)
    {
      $resp = Yii::app()->createUrl('site/tagPost?id='.$id);
    }
    else
    {
      $resp = Yii::app()->createUrl('site/friendTimeLine?friend_id='.$id);
    }
  }

  echo $resp;
}

/*Tasg post */
public function actionTagPost()
{
  $member_id = ApplicationSessions::run()->read('member_id');

  if(!empty($member_id))
  {
    $tag_id = $_REQUEST['id'];

    $tagsPost_id = PostTags::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>' active_status="S" and status="1" and tags_id='.$tag_id));
    $tags = Tags::model()->findByPk($tag_id);
    if($tagsPost_id->post_id)
    {
      $post = Post::model()->findAll(array('condition'=>'active_status="S" and status="1" and post_id IN ('.$tagsPost_id->post_id.')'));

      if(!empty($post))
      {

        $member_post   = Post::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'post_id desc'));
        $buddies_data  = Friends::model()->findAll(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));
        $sent_data	   = Friends::model()->findAll(array('condition'=>'from_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
        $receive_data  = Friends::model()->findAll(array('condition'=>'to_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
        $buddies_count = Friends::model()->count(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

        $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'user_activity_id desc'));

        $recent_member = $this->stories();


        $this->render('tagPost',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'location_attachment'=>$location_attachment,'recent_member'=>$recent_member,'tags'=>$tags));
      }
      else
      {
        $this->redirect(Yii::app()->createUrl('site/index'));
      }
    }
    else
    {
      $this->redirect(Yii::app()->createUrl('site/index'));
    }

  }
  else
  {
    $this->redirect(Yii::app()->createUrl('site/index'));
  }
}
/*Tasg post */

/*Location gallery start */
public function actionLocationMedia()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $location_data= Location::model()->findByPk($_REQUEST['location_id']);
  $attachment_count = '';
  $media ='';

  if(!empty($location_data))
  {
    $cursor =  (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] - 1 : 10 ;
    $limit  =  (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;

    $loc_attachments = LocationAttachmentsMaster::model()->find(array('select'=>'group_concat(location_attachments_master_id) as location_attachments_master_id','condition'=>'active_status="S" and status="1" and location_id ='.$location_data->location_master_id));


    if(!empty($loc_attachments->location_attachments_master_id))
    {
      $blockUser_list = LocationMediaBlockUser::model()->find(array('select'=>'group_concat(to_id) as to_id','condition'=>'active_status="S" and status="1" and from_id ='.$member_id));

      $block_attachment = PostSetting::model()->find(array('select'=>'group_concat(location_attachments_id) as location_attachments_id','condition'=>' active_status="S" and status="1"  and type="G"  and member_id = '.$member_id));


      $condition = 'active_status="S" and status="1" and location_attachments_master_id IN ('.$loc_attachments->location_attachments_master_id.')';

      if(!empty($blockUser_list->to_id))
      {
        $condition .= ' and member_id NOT IN ('.$blockUser_list->to_id.')';
      }

      if(!empty($block_attachment->location_attachments_id))
      {
        $condition .= ' and location_attachments_id NOT IN ('.$block_attachment->location_attachments_id.')';
      }


      $attachment = LocationAttachments::model()->findAll(array('condition'=>$condition));

      $attachment_count = LocationAttachments::model()->count(array('condition'=>$condition));

      if(!empty($member_id))
      {
        // $member_post   = Post::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'post_id desc'));
        $buddies_data  = Friends::model()->findAll(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));
        $sent_data	   = Friends::model()->findAll(array('condition'=>'from_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
        $receive_data  = Friends::model()->findAll(array('condition'=>'to_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
        $buddies_count = Friends::model()->count(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

        $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'user_activity_id desc'));

        $recent_member = $this->stories();
      }
      else
      {
        $member_post = '';
        $buddies_data = '';
        $sent_data = '';
        $receive_data = '';
        $analytics = '';
        $location_attachment = '';
      }



    $this->render('locationMedia',array('attachment'=>$attachment,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'location_attachment'=>$location_attachment,'recent_member'=>$recent_member));

    }
    else
    {
      $this->redirect(Yii::app()->createUrl('site/index'));
    }

  }
  else
  {
    $this->redirect(Yii::app()->createUrl('site/index'));
  }
}
/*Location gallery end*/
/*Location follower list start*/
public function actionLocationFollwerList()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $member_id = 148;

  $location_data = Location::model()->findByPk($_REQUEST['location_id']);
  if(!empty($location_data))
  {
    $locationFollower = Location::model()->find(array('select'=>'group_concat(member_id) as member_id','condition'=>'active_status="S" and status="1" and location_master_id= '.$location_data->location_master_id));

    if(!empty($locationFollower->member_id))
    {
      $member_details = Member::model()->findAll(array('condition'=>' member_id IN('.$locationFollower->member_id.')'));

      if(!empty($member_details))
      {
        $i=0;
          foreach($member_details as $val)
          {
            $data[$i] = $this->GetBasicDeatilsOfFriend(member_id,$val->member_id);
            $i++;
          }

          foreach($data as  $infoVal)
          {

            $html .= '<a href="'.Yii::app()->createUrl('site/friendTimeLine?friend_id='.$infoVal['member_id']).'"><div class="flt-ion-tb" style="width:98%!important;margin:0px!important;">
                    <div class="left-tex">
                      <div class="flot-left dp-icon-tb">
                        <img class="img-responsive" src="'.$infoVal['profile_pic'].'">
                      </div>
                      <div class="flot-left">
                        <strong>'.$infoVal['name'].'</strong>
                        ('.$infoVal['username'].')
                      </div>
                    </div>
                  </div> </a>';
          }
      }
      else
      {
        $html='<table><tr><td>Location not followed by other users</td></tr></table>';
      }
    }
  }

  else
  {
    $html='<table><tr><td>Location not followed by other users</td></tr></table>';
  }

  echo $html;
}

/*Location follower list end*/

/*Location Media Like Start*/
public function actionLocationMediaLike()
{
  $member_id = $_REQUEST['member_id'];
  $location_attachments_id = $_REQUEST['location_attachments_id'];
  $like_data = LocationMediaLikes::model()->find(array('condition'=>'location_attachments_id='.$location_attachments_id.' and member_id='.$member_id));

  $post = Post::model()->findByPk($_REQUEST['post_id']);

  if(!empty($like_data))
  {
    LocationMediaLikes::model()->deleteByPk($like_data->location_media_likes_id);
  }
  else
  {
    $model = new LocationMediaLikes;
    $model->location_attachments_id = $location_attachments_id;
    $model->member_id = $member_id;
    $model->type = "L";
    $model->added_on = time();
    $model->updated_on = time();
    $model->save(false);

    //storeUserActivity
        // $this->storeUserActivity($_REQUEST['member_id']," Liked ".$post->member->username."'s post","L","PostLike",$model->post_like_id,$post->member_id);
  }

  $like_count = LocationMediaLikes::model()->count(array('condition'=>'location_attachments_id='.$location_attachments_id));
  echo $like_count;
}
/*Location Media Like End*/


/*Location Media Comment start*/
public function actionLocationMediaComment()
{
  $member_id = $_REQUEST['member_id'];
  $comment = $_REQUEST['comment'];
  $location_attachments_id = $_REQUEST['location_attachments_id'];

  $attachment = LocationAttachments::model()->findByPk($_REQUEST['location_attachments_id']);

    $model = new PostComment;
    $model->location_attachments_id = $location_attachments_id;
    $model->member_id = $member_id;
    $model->type = "C";
    $model->comment = $comment;
    $model->added_on = time();
    $model->updated_on = time();
    $model->save(false);

  $comment_count = PostComment::model()->count(array('condition'=>'location_attachments_id='.$location_attachments_id));
  echo '200::Comment Posted Successfully::'.$comment_count.'::'.$location_attachments_id;


}
/*Location Media Comment end*/

/*Fetch Location Media Comment start */
public function actionGetLocationMediaComment()
{
  $location_attachments_id = $_REQUEST['location_attachments_id'];
  $member_id = ApplicationSessions::run()->read('memer_id');
  $html = '';
  if(!empty($location_attachments_id))
  {
    $comment_data = PostComment::model()->findAll(array('condition'=>'type="C" and location_attachments_id='.$location_attachments_id));

    if(!empty($comment_data))
    {
      foreach($comment_data as $val)
      {
        $profile_pic = $this->getProfilePic($val->member_id);
        $comment = "'".$val->comment."'";

        $html .= '<div class="flt-ion-tb" style="width:98%!important;margin:0px!important;">

            <div class="left-tex">
              <div class="flot-left dp-icon-tb">
                <img class="img-responsive" src="'.$profile_pic.'">
              </div>
              <div class="flot-left">
                <strong>'.$val->member->first_name.' '.$val->member->last_name.'</strong>
                <br>
                '.$val->comment.'
                <br>';
                  if(!empty($member_id) && $member_id == $val->member_id)
                  {
                    $html .= '	<span onclick="deleteComment('.$val->location_attachments_id.')"> Delete</span>
              <span onclick="editComment('.$val->post_comment_id.','.$comment.')"> Edit </span>';
                  }

              $html .= '</div>

            </div>
          </div>';
      }

    }
  }
  echo $html;
}

/*Fetch Location Media Comment End */

/*Fetch All media files of user start */
public function actionAllmediaFilesOfUser()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $limit = (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 5;
  $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['limit'] : 0;
  $html='';
  if(!empty($member_id))
  {
    $condition 	= 'active_status="S" and status="1" and member_id='.$member_id;
    $own_post 	= Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>$condition));

    if(!empty($own_post->post_id))
    {
      $post	=  PostAttachment::model()->findAll(array('condition'=>'active_status="S" and status="1" and post_id IN('.$own_post->post_id.')' ,'order'=>'post_id DESC','offset'=>$cursor,'limit'=>$limit));

        foreach($post as $valPost)
        {
          if($valPost->type =="V")
          {
            $html	.= '
          <div class="menu-m-are clearfix mt10 mb10" id="attachment_'.$valPost->post_attachment_id.'">
            <div class="col-sm-12 ">
              <div class="col-sm-grid-2">
                <video class="img-responsive" controls>
                  <source src="'.Yii::app()->baseUrl.'/upload/location_attachment/'.$valPost->attachment.'" type="video/mp4">
                </video>
              </div>
            </div>
          </div>';
          }
          else
          {
            $html	.= '<div class="col-sm-grid-2">
                <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/location_attachment/'.$valPost->attachment.'" style="height:150px;width:150px;">
              </div>';
          }



        }

    }
  }

  echo $html;
}


/*Friend Details popup */
  public function actionFriendsDetails()
  {
    $member_id = ApplicationSessions::run()->read('member_id');
    $title = '';
    $memer_data 		= Member::model()->findByPk($_REQUEST['friend_id']);
    $post_count 		= Post::model()->count(array('condition'=>'active_status="S" and status="1" and member_id ='.$_REQUEST['friend_id']));
    $profileLike_count 	= $this->ProfileLikeCount($_REQUEST['friend_id']);
    $follow_user_count 	= $this->getFollowerAdmin($_REQUEST['friend_id']);
    $following_user_count = $this->getFollowingAdmin($_REQUEST['friend_id']);
    $connection_count 	= Friends::model()->count(array('condition'=>'active_status="S" and status="1" and (from_id='.$_REQUEST['friend_id'].' or to_id='.$_REQUEST['friend_id'].') and is_accepted="Y"'));
    $isIFriend 			= $this->isIFriend($_REQUEST['friend_id']);

    $html='';


    if(!empty($memer_data) && $member_id != $_REQUEST['friend_id'])
    {
      $title = $memer_data->username.' '.base64_decode($memer_data->first_name).' '.base64_decode($memer_data->last_name);

      $profile_pic 	= $this->getProfilePic($_REQUEST['friend_id']);
      $isIFollowUser 	= $this->isIFollowUser($_REQUEST['friend_id']);

      if($isIFollowUser == "Yes")
      {
        $follow_btn_status 		= "block"; /* followed*/
        $following_btn_status 	= "none";/* unfollowed*/
      }
      else
      {
        $follow_btn_status 		= "none";
        $following_btn_status 	= "block";
      }
      $html .= '
<div class="flt-ion-tb" style="width:100%!important;margin:0px!important; overflow: hidden; line-height: 25px;">
      <div class="row d-flex mx-auto">
          <div class="col-6 profilePicture py-3 pr-0 d-flex flex-row align-items-center justify-content-end">
              <a href=""><img class="img-responsive" style="border-radius: 50%;width:  100px;height: 100px;" src="'.$profile_pic.'"> </a>
          </div>
          <div class="col-6 my-auto pl-0">
          <div class="col-12 d-flex align-items-center justify-content-center pb-2">
            <a class="likesLink">
              <i class="fa fa-heart hr-color" aria-hidden="true"></i>
              <span class="profile_like_count pl-2 font-weight-bold">'.$profileLike_count.' Likes</span>
            </a>
          </div>
          <div class="col-12 d-flex align-items-center justify-content-center">
            <button class="btn customFollowButton py-1" type="button" name="button">
            <span class="text-bold" id="followingUserSpanPop" style="display:'.$following_btn_status.'">
                  <a data-toggle="modal" data-id="'.$member_id.'"';
                  if(!empty($member_id))
                  {
                    $html .= ' href="#" onclick="FollowUser('.$_REQUEST['friend_id'].',1);"';
                  }
                  else
                  {
                    $html .= ' href="#onload"';
                  }
                  $html .= '>
                    Follow
                  </a>

                </span>	<span class="text-bold" id="followUserSPanPop" style="display:'.$follow_btn_status.'">
                    <a data-toggle="modal" data-id="'.$member_id.'" ';
                    if(!empty($member_id))
                    {
                      $html	.= ' href="#" onclick="FollowUser('.$_REQUEST['friend_id'].',0);"';
                    }
                    else
                    {
                      $html	.= 'href="#onload"';
                    }
                    $html	.= '>
                      Unfollow
                    </a>
                  </span>
                  </button>
          </div>
          </div>
          <div class="col-12 username pl-4">
            <h3 class="font-weight-bold">@'.$title.'</h3>
              <a class="actualName" href=""><h4 class="font-weight-normal">'.base64_decode($memer_data->first_name).'
                '.base64_decode($memer_data->last_name).'</h4></a>
          </div>
          <div class="col-12 postsSection d-inline-flex">
            <div class="col-4 posts d-inline-flex flex-column justify-content-center align-items-center">
                <h5 class="font-weight-bold">Posts</h5>
                <p class="text-bold">'.$post_count.'<p>
            </div>
            <div class="col-4 followers d-inline-flex flex-column justify-content-center align-items-center">
              <h5 class="font-weight-bold">Followers</h5>
              <p class="text-bold">'.$follow_user_count.'<p>
            </div>
            <div class="col-4 following d-inline-flex flex-column justify-content-center align-items-center">
              <h5 class="font-weight-bold">Following</h5>
              <p class="text-bold">'.$following_user_count.'<p>
            </div>
          </div>
          <div class="bio text-left pt-2 pb-4">
            <h5>The brave Nazi deleted his tweet & twitter account,before the Nazi army could have started following him.</h5>
          </div>
          <div>
            <a class="socialMediaLink" href="https://facebook.com/profile.php?id=100016">facebook.com/profile.php?id=100016</a>
          </div>
      </div>
      </div>';
      // $html .= '<div class="flt-ion-tb aiman" style="width:100%!important;margin:0px!important; overflow: hidden; line-height: 25px; text-align:center;">
      // 			<div class="row">
      // 				<div class="col-sm-12">
      // 					<div style="width: 100px; height: 100px; overflow: hidden; border-radius: 100%; display: inline-block; margin: 15px 0;"><img class="img-responsive" src="'.$profile_pic.'"></div>
      // 			 	</div>
      // 				<div class="col-sm-12">
      // 					<a href="'.Yii::app()->createUrl('site/friendTimeLine?friend_id='.$_REQUEST['friend_id']).'" target="_blank" style="font-size:18px; color:#333;     font-weight: bold; color: #bd3c4e;">
      // 				 	'.base64_decode($memer_data->first_name).' '.base64_decode($memer_data->last_name).' </a>
      // 				</div>
      // 				<div class="col-sm-12">
      // 				 	'.base64_decode($memer_data->about_me).'
      // 				</div>
      // 				<div class="col-sm-12">
      // 				 	<strong>'.base64_decode($memer_data->job_title).'</strong>
      // 				</div>
      // 				<div class="col-sm-12">
      // 				 	<strong>'.$memer_data->other_social_media_acc.'</strong>
      // 				</div>
      //
      // 			</div>
      //
      // 			<div class="row">
      // 				<div class="col-sm-12">
      // 				    <div class="btns1">
      // 					<ul>
      // 						<!-------Friend Request start------>';
      // 						if(empty($isIFriend))
      // 						{
      // 						$html .='<li><span>
      // 									<a data-toggle="modal" data-id="'.$member_id.'"';
      // 										if(!empty($member_id))
      // 										{
      // 											$html .='href="#" onclick="SendfriendRequest('.$_REQUEST['friend_id'].');"';
      // 										}
      // 										else
      // 										{
      // 											$html .='href="#onload"';
      // 										}
      // 										$html .='>
      // 										<center><span class="text-bold" id="">Connect </span> </center>
      // 									</a>
      // 								</span></li>';
      // 							}
      // 							else
      // 							{
      // 								if($isIFriend->is_accepted == "Y")
      // 								{
      // 									$html .='<a data-toggle="modal" data-id="'.$member_id.'"';
      // 									if(!empty($member_id))
      // 									{
      // 										$html .='href="#"';
      // 									}
      // 									else
      // 									{
      // 										$html .='href="#onload"';
      // 									}
      // 									$html .='>
      // 									<span class="text-bold" id="">Friend </span>
      // 									<span>'.$connection_count.' </span>';
      // 								}
      // 							}
      //
      // 							$html .='<!-------Friend Request end------>
      // 										<!-------Profile like start-------->
      // 										<li><a data-toggle="modal" data-id="'.$member_id.'"';
      // 										if(!empty($member_id))
      // 										{
      // 											$html .= 'href="#" onclick="postProfileLike('.$member_id.','.$_REQUEST['friend_id'].');" ';
      // 										}
      // 										else
      // 										{
      // 											$html .= 'href="#onload"';
      // 										}
      // 										$html .='>
      // 												Likes
      // 											<span class="profile_like_count">'.$profileLike_count.'</span>
      // 										</a></li>
      // 										<!-------Profile like end-------->
      // 										<!-------Follow user start------->
      // 										<li>
      // 											<span>
      // 												<span class="text-bold" id="followingUserSpanPop" style="display:'.$following_btn_status.'">
      // 													<a data-toggle="modal" data-id="'.$member_id.'"';
      // 													if(!empty($member_id))
      // 													{
      // 														$html .= ' href="#" onclick="FollowUser('.$_REQUEST['friend_id'].',1);"';
      // 													}
      // 													else
      // 													{
      // 														$html .= ' href="#onload"';
      // 													}
      // 													$html .= '>
      // 														Following
      // 													</a>
      // 													<span id="followerCountPop">'.$follow_user_count.'</span>
      // 												</span>
      //
      //
      // 												<span class="text-bold" id="followUserSPanPop" style="display:'.$follow_btn_status.'">
      // 													<a data-toggle="modal" data-id="'.$member_id.'" ';
      // 													if(!empty($member_id))
      // 													{
      // 														$html	.= ' href="#" onclick="FollowUser('.$_REQUEST['friend_id'].',0);"';
      // 													}
      // 													else
      // 													{
      // 														$html	.= 'href="#onload"';
      // 													}
      // 													$html	.= '>
      // 														follower
      // 													</a>
      // 													<span id="followerCountPop">'.$follow_user_count.'</span>
      // 												</span>
      // 											</span>
      // 										</li>
      // 										<!-------Follow user end------->
      //
      // 							<li><span> '.$post_count.' Posts </span> </li>
      // 						</ul>
      // 					</div>
      // 				</div>
      // 			</div>
      // 		</div>';
    }

    else if($member_id == $_REQUEST['friend_id'])
    {
      $title = $memer_data->username;

      $profile_pic 	= $this->getProfilePic($_REQUEST['friend_id']);
      $isIFollowUser 	= $this->isIFollowUser($_REQUEST['friend_id']);

      if($isIFollowUser == "Yes")
      {
        $follow_btn_status 		= "block";
        $following_btn_status 	= "none";
      }
      else
      {
        $follow_btn_status 		= "none";
        $following_btn_status 	= "block";
      }

        $html .= '		<div class="flt-ion-tb bg-white border-0" style="width:98%!important;margin:0px!important;">
              <div class="row d-flex mx-auto">
                  <div class="col-6 profilePicture py-3 pr-0 d-flex flex-row align-items-center justify-content-end">
                      <a href=""><img class="img-responsive" style="border-radius: 50%;width:  100px;height: 100px;" src="'.$profile_pic.'"> </a>
                  </div>
                  <div class="col-6 d-flex align-items-center justify-content-center pl-0">
                    <a class="likesLink">
                      <i class="fa fa-heart hr-color" aria-hidden="true"></i>
                      <span class="profile_like_count pl-2 font-weight-bold">'.$profileLike_count.' Likes</span>
                    </a>
                  </div>
                  <div class="col-12 username pl-4">
                    <h3 class="font-weight-bold">@'.$title.'</h3>
                      <a class="actualName" href=""><h4 class="font-weight-normal">'.base64_decode($memer_data->first_name).'
                        '.base64_decode($memer_data->last_name).'</h4></a>
                  </div>
                  <div class="col-12 postsSection d-inline-flex">
                    <div class="col-4 posts d-inline-flex flex-column justify-content-center align-items-center">
                        <h5 class="font-weight-bold">Posts</h5>
                        <p class="text-bold">'.$post_count.'<p>
                    </div>
                    <div class="col-4 followers d-inline-flex flex-column justify-content-center align-items-center">
                      <h5 class="font-weight-bold">Followers</h5>
                      <p class="text-bold">'.$follow_user_count.'<p>
                    </div>
                    <div class="col-4 following d-inline-flex flex-column justify-content-center align-items-center">
                      <h5 class="font-weight-bold">Following</h5>
                      <p class="text-bold">'.$following_user_count.'<p>
                    </div>
                  </div>
                  <div class="bio text-left pt-2 pb-4">
                    <h5>The brave Nazi deleted his tweet & twitter account,before the Nazi army could have started following him.</h5>
                  </div>
                  <div>
                    <a class="socialMediaLink" href="https://facebook.com/profile.php?id=100016">facebook.com/profile.php?id=100016</a>
                  </div>
              </div>
              </div>';

    }
    else
    {
      $title 	 = 'Details not found';
      $html	.= '<div class="col-sm-grid-2">
                <h2> Details not found</h2>
              </div>';
    }

    echo $html.'::'.$title;
  }

/*Friend Details popup */

/*Fetch All media files of user end */


/*Fetch All comment of psot */

public function actionPostLikeDetails()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $recent_member = array();

  if(!empty($member_id))
  {
    $post_id = $_REQUEST['post_id'];
    $html = '';

    if(!empty($post_id))
    {
      $like_data = PostLike::model()->findAll(array('condition'=>'post_id='.$post_id));

      if(!empty($like_data))
      {
        $html .= ' <h2>Likes</h2> <br>';
        foreach($like_data as $val)
        {
          $member = Member::model()->find(array('condition'=>'member_id='.$val->member_id));

          if(empty($member->profile_pic))
          {
            $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
          }
          else if(!empty($member->profile_pic) && strpos($member->profile_pic,"http")===false)
          {
            $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$member->profile_pic;
          }
          else
          {
            $profile_pic = $member->profile_pic;
          }

          $html .= '<div class="flt-ion-tb" style="width:98%!important;margin-bottom:2px!important;">

              <div class="left-tex">
                <div class="flot-left dp-icon-tb">
                  <img class="img-responsive" src="'.$profile_pic.'">
                </div>
                <div class="flot-left">
                  <strong>'.$member->first_name.' '.$member->last_name.'</strong>
                </div>

              </div>
              <div class="right-tex">
                  '.Controller::get_timeago($val->added_on).'
                </div>
            </div>';
        }


      }
      else
      {
        $html .= '<div class="flt-ion-tb" style="width:98%!important;">

              <div class="left-tex">
                <div class="flot-left">
                  <strong>No Likes Found</strong>
                </div>
              </div>
            </div>';
      }
    }
  }
  if(!empty($member_id))
  {

    $buddies_data  = Friends::model()->findAll(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));
    $sent_data	   = Friends::model()->findAll(array('condition'=>'from_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
    $receive_data  = Friends::model()->findAll(array('condition'=>'to_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
    $buddies_count = Friends::model()->count(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

    $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'user_activity_id desc'));

    $recent_member = $this->stories();
  }
  else
  {
    $member_post = '';
    $buddies_data = '';
    $sent_data = '';
    $receive_data = '';
    $analytics = '';
    $location_attachment = '';
    $html .= '<div class="flt-ion-tb" style="width:98%!important;">

              <div class="left-tex">
                <div class="flot-left">
                  <strong>No Likes Found</strong>
                </div>
              </div>
            </div>';
  }



  $this->render('postLikesAndComment',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'location_attachment'=>$location_attachment,'recent_member'=>$recent_member,'html'=>$html));

}


public function actionPostCommentDetails()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $recent_member = array();

  if(!empty($member_id))
  {
    $post_id = $_REQUEST['post_id'];
    $html = '';

      if(!empty($post_id))
      {
      $comment_data = PostComment::model()->findAll(array('condition'=>'type="C" and post_id='.$post_id));

      if(!empty($comment_data))
      {
        $html .= ' <h2>Comments	</h2> <br>';
        foreach($comment_data as $val)
        {
          $member = Member::model()->find(array('condition'=>'member_id='.$val->member_id));

          if(empty($member->profile_pic))
          {
            $profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
          }
          else if(!empty($member->profile_pic) && strpos($member->profile_pic,"http")===false)
          {
            $profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$member->profile_pic;
          }
          else
          {
            $profile_pic = $member->profile_pic;
          }

          $child_comment = PostComment::model()->findAll(array('condition'=>'type="R" and parent_id='.$val->post_comment_id));

          if(!empty($child_comment))
          {
            foreach($child_comment as $val_child_comment)
            {
                $child_comment_member = Member::model()->find(array('condition'=>'member_id='.$val_child_comment->member_id));

                if(empty($child_comment_member->profile_pic))
                {
                  $child_comment_member_profile_pic = Yii::app()->theme->baseUrl."/images/profile-act.png";
                }
                else if(!empty($child_comment_member->profile_pic) && strpos($child_comment_member->profile_pic,"http")===false)
                {
                  $child_comment_member_profile_pic = Yii::app()->baseUrl."/upload/member/profile_pic/".$child_comment_member->profile_pic;
                }
                else
                {
                  $child_comment_member_profile_pic = $child_comment_member->profile_pic;
                }
              $child_comment_comment = "'".base64_decode($val_child_comment->comment)."'";
              $child_comment_data .= '
                <div class="flt-ion-tb" style="width:98%!important;margin:0px!important;">
                <div class="left-tex" style="margin-left:50px;">
                  <div class="flot-left dp-icon-tb">
                    <img class="img-responsive" src="'.$child_comment_member_profile_pic.'">
                  </div>
                      <div class="flot-left">
                        <strong>'.$child_comment_member->first_name.' '.$child_comment_member->last_name.'</strong>
                        <br>
                        '.base64_decode($val_child_comment->comment).'
                        <br>
                      <span onclick="CommentLike('.$val_child_comment->post_comment_id.')">	Like ('.$val_child_comment->comment_like_count.') </span> ';
                    if($val_child_comment->member_id == $member_id)
                    {
                        $child_comment_data .= '<span onclick="deleteComment('.$val_child_comment->post_comment_id.')"> Delete</span>
                      <span onclick="editComment('.$val_child_comment->post_comment_id.','.$child_comment_comment.')"> Edit </span>';
                    }


                    $child_comment_data .= '</div>
                  </div>
                  <div class="right-tex">
                    '.Controller::get_timeago($val_child_comment->added_on).'
                  </div>
                </div>';
            }
          }
          else
          {
            $child_comment_data = '';
          }
          $comment = "'".base64_decode($val->comment)."'";

          $html .= '<div class="flt-ion-tb" style="width:98%!important;margin:0px!important;">

              <div class="left-tex">
                <div class="flot-left dp-icon-tb">
                  <img class="img-responsive" src="'.$profile_pic.'">
                </div>
                <div class="flot-left">
                  <strong>'.$member->first_name.' '.$member->last_name.'</strong>
                  <br>
                  '.base64_decode($val->comment).'
                  <br>
                <span onclick="CommentLike('.$val->post_comment_id.')">	Like ('.$val->comment_like_count.') </span>
                <span onclick="commentReply('.$post_id.','.$val->post_comment_id.')">Reply </span>';
                if($val->member_id && $member_id)
                {
                    $html .='
                        <span onclick="deleteComment('.$val->post_comment_id.')"> Delete</span>
                        <span onclick="editComment('.$val->post_comment_id.','.$comment.')"> Edit </span>';
                }


              $html .='</div>
                <div class="right-tex">
                    '.Controller::get_timeago($val->added_on).'
                  </div>
              </div>
              '.$child_comment_data.'
            </div>';

          $child_comment_data = '';
        }


      }
      else
      {
        $html .= '<div class="flt-ion-tb" style="width:98%!important;">

              <div class="left-tex">
                <div class="flot-left">
                  <strong>No Comment Found</strong>
                </div>
              </div>
            </div>';
      }
    }
  }
  if(!empty($member_id))
  {

    $buddies_data  = Friends::model()->findAll(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and (is_block="N" || is_block="Y")'));
    $sent_data	   = Friends::model()->findAll(array('condition'=>'from_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
    $receive_data  = Friends::model()->findAll(array('condition'=>'to_id='.$member_id.' and is_accepted="N" and is_deleted="N" and is_block="N"'));
    $buddies_count = Friends::model()->count(array('condition'=>'(from_id='.$member_id.' || to_id='.$member_id.') and is_accepted="Y" and is_deleted="N" and is_block="N"'));

    $analytics		= UserActivity::model()->findAll(array('condition'=>'active_status="S" and member_id='.$member_id,'order'=>'user_activity_id desc'));

    $recent_member = $this->stories();
  }
  else
  {
    $member_post = '';
    $buddies_data = '';
    $sent_data = '';
    $receive_data = '';
    $analytics = '';
    $location_attachment = '';
    $html .= '<div class="flt-ion-tb" style="width:98%!important;">

              <div class="left-tex">
                <div class="flot-left">
                  <strong>No Likes Found</strong>
                </div>
              </div>
            </div>';
  }



  $this->render('postLikesAndComment',array('post'=>$post,'buddies_data'=>$buddies_data,'sent_data'=>$sent_data,'receive_data'=>$receive_data,'member_post'=>$member_post,'buddies_count'=>$buddies_count,'analytics'=>$analytics,'location_attachment'=>$location_attachment,'recent_member'=>$recent_member,'html'=>$html));

}
/*Fetch All comment of psot */


public function actionPostCommentTest()
{
  echo "<pre>";
  print_r($_REQUEST);
  exit;
}

public function printBreak($msg)
{
  echo "<pre>";
  print_r($msg);
  exit;
}


/*Emojiis*/
public function actionEmojis()
{
  $res = ChatMessage::model()->findByPk(270);
  $this->printBreak($res);
}
/*Emojiis*/

/*Show post details Start*/
  public function	actionPostDetails($member_id,$post_id)
  {
    $val = Post::model()->findByPk($post_id);
    $html='';
    $baseUrl = Yii::app()->theme->baseUrl;
    if(!empty($val))
    {
      if(empty($val->location_attachments_id))
      {
        $msg_about_post = '';

              $share_image 	= '';
              $like_count 	= PostLike::model()->count(array('condition'=>'post_id='.$val->post_id));
              $comment_count 	= PostComment::model()->count(array('condition'=>'type="C" and post_id='.$val->post_id));
              $share_count 	= PostShare::model()->count(array('condition'=>'post_id='.$val->post_id.' and type="S"'));
              $retag_count 	= PostRetag::model()->count(array('condition'=>'post_id='.$val->post_id));
              $attachment 	= PostAttachment::model()->findAll(array('condition'=>'post_id='.$val->post_id.' and active_status="S"'));
              $post_friends 	= PostFriends::model()->find(array('select'=>'group_concat(friend_id) as friend_id','condition'=>'post_id='.$val->post_id.' and active_status="S"'));

              $profile_pic = $this->getProfilePic($val->member_id);

              if($post_counter == count($post))
              {
                $lastclass = 'last-post';
              }

              $post_counter++;


              /*is user Like, Comment, Share, Retags post*/
              if(!empty($member_id))
              {


                $post_like 				= PostLike::model()->find(array('condition'=>'post_id="'.$val->post_id.'" and member_id="'.$member_id.'"'));

                $post_share 			= PostShare::model()->find(array('condition'=>'post_id="'.$val->post_id.'" and from_id="'.$member_id.'"'));

                $post_comment 			= PostComment::model()->find(array('condition'=>'post_id="'.$val->post_id.'" and member_id="'.$member_id.'"'));

                $post_retag				= PostRetag::model()->find(array('condition'=>'post_id="'.$val->post_id.'" and member_id="'.$member_id.'"'));

                $post_retag_data 	 	= PostRetag::model()->findAll(array('condition'=>'post_id="'.$val->post_id.'"'));

                //post like by user
                if(!empty($post_like))
                {
                  $msg_about_post .= 'You like this Post';
                }

                //post Shar by user
                if(!empty($post_share))
                {
                  $msg_about_post .= 'You  Share this Post';
                }

                //post Comment by user
                if(!empty($post_comment))
                {
                  $msg_about_post .= 'You Commented  on this Post';
                }

                //post retag by user
                if(!empty($post_retag))
                {
                  $msg_about_post .= 'You retag this Post';
                }


              }

        $html .='<div class="menu-m-are clearfix mt10 mb10" id="post_'.$val->post_id.'">
            <div class="img-tex-box clearfix ">
              <div class="col-sm-12">
                <div class="left-tex" onclick="friendDetails('.$val->member_id.')">
                  <div class="flot-left dp-icon1 mt10">
                    <img src="'.$profile_pic.'">
                  </div>
                  <div class="flot-left mt10">
                    <div class="user_name">'; (!empty($val->member->first_name))? base64_decode($val->member->first_name).' '. base64_decode($val->member->last_name): " ".'</div> '.(!empty($val->member->username))?'('.$val->member->username.')':" ".'
                  </div>
                </div>
                <div class="right-tex text-center mt10">
                '. Controller::get_timeago($val->added_on).'<br/>
                  <div class="couponcode">
                    <img class="img-responsive" src="'.$baseUrl.'/images/dot-icon.png">
                     <span class="coupontooltip">
                      <div class="coupontooltip-arrow <?php echo $lastclass;?>"></div>';
                      if($member_id == $val->member_id)
                      {
                        $html .='<a href="#" class="tooltiptext" onclick="deletePost('.$val->post_id.');">Delte post</a>';
                      }
                  $html .='
                        <a href="#" class="tooltiptext" onclick="savePost('.$val->post_id.',S)"> Save Post</a>
                        <a href="#" class="tooltiptext" onclick="savePost('.$val->post_id.',H)"> Hide Post</a>
                        <a href="#" class="tooltiptext" onclick="ReportPost('.$val->post_id.',R)"> Report Post</a>
                     </span>

                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-12 mb10 ">
            <div class="post-tex" >';
            $post_text = preg_replace('/#(\w+)/', ' <span class="braun-color text-bold " onclick="isTagFollow(this);">#$1</span>', base64_decode($val->post));

        $html .= $post_text .'</div>';

        if(!empty($val->location))
          {
            $location = $val->location;

            $html .='	<div class="col-md-12">
                  <div class="col-sm-1" style="padding:0;"><img class="img-responsive" src="'.$baseUrl.'/images/location_ping.png"></div>
                    <div class="col-sm-10">'.$location.'</div>
                  </div>';
          }

      $html .='	<div class="post-tex">';
        if(!empty($post_friends->friend_id))
        {
          $html .= $this->getTaggedFriend($post_friends->friend_id);
        }

        $html .='</div>
          </div>';
          if(!empty($attachment))
          {
            $html .='<div class="col-sm-12 ">';

            if(count($attachment)==1)
              {
                if($attachment[0]->type=="V")
                {
                  $html .='<div class="col-sm-grid-2">
                        <div style="width:100%; height:300px; overflow:hidden;">
                          <video class="img-responsive" controls>
                            <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[0]->attachment.'" type="video/mp4">
                          </video>
                        </div>
                      </div>';
                }

                else
                {
                  $share_image = "http://iresolveservices.com/tagswag/upload/post_attachment/".$attachment[0]->attachment;

                  $html .='<div class="col-sm-grid-2">
                        <div style="width:100%; height:300px; overflow:hidden;">
                          <img src="'.Yii::app()->baseUrl .'/upload/post_attachment/'.$attachment[0]->attachment.'" style="width:100%; height:100%;">
                        </div>
                      </div>';
                }
              }
            else if(count($attachment)==2)
              {
                if($attachment[0]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid-2">
                          <video class="img-responsive" controls>
                            <source src="'.Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[0]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
                if($attachment[1]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment'.$attachment[1]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid-2">
                          <video class="img-responsive" controls>
                            <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.  $attachment[1]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
              }
            else if(count($attachment)==3)
              {
                if($attachment[0]->type=="P")
                {
                  $html .='<div class="col-sm-grid ">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid ">
                          <video class="img-responsive" controls>
                            <source src="'.Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[0]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
                if($attachment[1]->type=="P")
                {
                  $html .='<div class="col-sm-grid ">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment'.$attachment[1]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid ">
                          <video class="img-responsive" controls>
                            <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.  $attachment[1]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
                if($attachment[2]->type=="P")
                {
                  $html .='<div class="col-sm-grid ">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment'.$attachment[2]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid ">
                          <video class="img-responsive" controls>
                            <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.  $attachment[2]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
              }
            else if(count($attachment)==4)
              {
                if($attachment[0]->type=="P")
                {
                  $html .='<div class="col-sm-grid ">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid ">
                          <video class="img-responsive" controls>
                            <source src="'.Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[0]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
                if($attachment[1]->type=="P")
                {
                  $html .='<div class="col-sm-grid ">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment'.$attachment[1]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid ">
                          <video class="img-responsive" controls>
                            <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.  $attachment[1]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
                if($attachment[2]->type=="P")
                {
                  $html .='<div class="col-sm-grid ">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment'.$attachment[2]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid ">
                          <video class="img-responsive" controls>
                            <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.  $attachment[2]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
                if($attachment[3]->type=="P")
                {
                  $html .='<div class="col-sm-grid ">
                          <img class="img-responsive" src="'.Yii::app()->baseUrl.'/upload/post_attachment'.$attachment[3]->attachment.'">
                        </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid ">
                          <video class="img-responsive" controls>
                            <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.  $attachment[3]->attachment.'" type="video/mp4">
                          </video>
                        </div>';
                }
              }

              $html .='</div>';
          }
        $html .='<div class="col-sm-12 mt10">
            <div class="left-tex">
              <a data-toggle="modal" data-id="'.$val->post_id.'" '; if(!empty($member_id)){ $html .='href="#" onclick="postLike(this);"'; }else{ $html .= 'href="#onload"'; } $html .= '>

                <div class="flot-left icon-with">
                  <img class="img-responsive" src="'.$baseUrl.'/images/mipmap-mdpi/like_icon.png">
                </div>

                <div class="flot-left  mr10">
                  <a data-toggle="modal" data-id="'.$val->post_id.'" '; if(!empty($member_id)){ $html .=' href="#" onclick="getLike(this);"'; }else{ $html .=' href="#onload" '; } $html .=' >

                  <span class="post_like_count_'.$val->post_id.'">
                          '.$like_count.'</span> Likes</a>

                </div>
              </a>
            </div>
            <div class="left-tex">
              <div class="flot-left icon-with" onclick="showCommentarea('.$val->post_id.')">
                <img class="img-responsive" src="'. $baseUrl.'/images/mipmap-mdpi/comments_icon.png">

              </div>
                <a data-toggle="modal" data-id="'.$val->post_id.'" ';
                if(!empty($member_id)){ $html .=' href="#" onclick="postComment(this);" '; }
                else{ $html .=' href="#onload" ';	}

                $html .=' >

                  <div class="flot-left mr10">
                    <span class="post_comment_count_'. $val->post_id.'">'.$comment_count.'</span> Comments
                  </div>
              </a>

            </div>
            <div class="left-tex po-reti">
              <div class="couponcode">

              </div>
            </div>



            <div style="display:none" id="commentArea_'. $val->post_id.'">
                <div id="commentShow_'. $val->post_id.'">

              </div>
              <form id="frm_comment" method="POST" style="padding:15px;">
                <div class="input-group stylish-input-group">

                  <input type="text" class="form-control" autocomplete="off" spellcheck="false"  placeholder="Comment" name="comment" id="comment_'. $val->post_id.'" required>
                  <span class="input-group-addon bg-none">
                    <button id="btn_comment" type="submit" class="bg-none-brd">
                      <i class="fa fa-search" aria-hidden="true"></i>
                    </button>
                  </span>
                  <input type="hidden" name="member_id" value="'.$member_id.'">
                  <input type="hidden" id="comment_post_id" name="post_id" value="'.$val->post_id.'">

                </div>
              </form>

            </div>
          </div>';
      }
      echo $html;
    }
  }

/*Show post details End*/

/*Email verification start*/
  public function actionVerifyEmailAcc($id)
  {

    $member_id = base64_decode($id);
    $member_data = Member::model()->findByPk($member_id);
      if(!empty($member_data))
      {
        Member::model()->updateByPk($member_id,array('is_email_verify'=>'Y'));
        $msg = "200";
      }
      else
      {
        $msg = "404";
      }
    $this->render('index',array('msg'=>$msg));
  }
/*Email verification end*/

/*Add new location from greed start */
public function actionLocationGridView()
{
  $member_id = ApplicationSessions::run()->read('member_id');

  $location_data = array();
  /*lat long start*/
    $ip  = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    $url = "http://freegeoip.net/json/$ip";
    $ch  = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $data_location = curl_exec($ch);
    curl_close($ch);

    if ($data_location) {
      $location = json_decode($data_location);

      $lat = $location->latitude;
      $lon = $location->longitude;
    }
  /*lat long end*/

  if(!empty($member_id))
  {
    $tot_location = Location::model()->find(array('select'=>'count(distinct(location_master_id)) as location_master_id','condition'=>'active_status="S" and status="1"'));

    $i=0;
    $data = array();
    $data_set = array();
    if(!empty($data_location))
    {
      $condition = 'active_status="S" and status="1" and member_id !='.$_REQUEST['member_id'];

       if(!empty($location->latitude) && !empty($location->longitude))
      {
        $lat = $location->latitude;
        $lng = $location->longitude;

        $location_data = Yii::app()->db->createCommand()
          ->select('location_master_id,location_name,latitude,longitude,( 3959 * acos( cos( radians('.$lat.') ) * cos( radians( latitude ) ) * cos( radians( longitude) - radians('.$lng.') ) + sin( radians('.$lat.') ) * sin( radians( latitude ) ) ) ) AS distance')
          ->from('tbl_location_master')
          ->order('distance')
          ->queryAll();
      }

    }
    else
    {
      $location_data = LocationMaster::model()->findAll(array('condition'=>'active_status="S" and status="1"','order'=>'location_master_id'));
    }

  }
  $this->render('addNewlocation',array('location_data'=>$location_data));

}
/*Add new location from greed end*/

/*Add location start*/
public function actionAddLocationGred()
{
  $location_mstr_data = LocationMaster::model()->findByPk($_REQUEST['location_master_id']);

  if(!empty($location_mstr_data))
  {
    $location_data = Location::model()->find(array('condition'=>'member_id="'.$_REQUEST['member_id'].'" and latitude="'.$location_mstr_data->latitude.'" and longitude="'.$lng.'" and location_name = "'.$location_mstr_data->location_name.'"'));

    if(empty($location_data))
    {
      $model = new Location;
    }
    else
    {
      $model = $location_data;
    }

    $model->member_id 			= $_REQUEST['member_id'];
    $model->location_master_id 	= $_REQUEST['location_master_id'];
    $model->latitude 			= $location_mstr_data->latitude;
    $model->longitude 			= $location_mstr_data->latitude;
    $model->location_name 		= $location_mstr_data->location_name;
    $model->added_on			= time();
    $model->updated_on			= time();

    if($model->save())
    {
        echo "200";
    }
    else
    {
      echo "400";
    }

  }
  else
  {
    echo "400";
  }
}
/*Add location end*/

/*Remove location start*/
  public function actionRemoveLocation()
  {
    $location_data = Location::model()->deleteAll(array('condition'=>'member_id='.$_REQUEST['member_id'].' and location_master_id='.$_REQUEST['location_master_id']));
    echo "200";
  }
/*Remove location end*/


/* Show post To user on TimeLine Start*/
  public function ShowPost($post_id)
  {
    $action 	= Yii::app()->controller->action->id;
    $val 		= Post::model()->findByPk($post_id);
    $html		= '';
    $member_id 	= ApplicationSessions::run()->read('member_id');
    $baseUrl 	= Yii::app()->theme->baseUrl;
    $share_image ='A';
    if(!empty($val))
    {
      $msg_about_post = '';
      $share_image 	= 'A';

      $attachment 	= PostAttachment::model()->findAll(array('condition'=>'post_id='.$val->post_id.' and active_status="S"'));
      $post_friends 	= PostFriends::model()->find(array('select'=>'group_concat(friend_id) as friend_id','condition'=>'post_id='.$val->post_id.' and active_status="S"'));

      $like_count		 	= $this->PostLikeCount($post_id);
      $comment_count		= $this->PostCommentCount($post_id);
      $share_count 		= $this->PostShareCount($post_id);
      $retag_count 		= $this->PostRetagCountPost($post_id);

      $profile_pic = $this->getProfilePic($val->member_id);

      if($post_counter == count($post))
      {
        $lastclass = 'last-post';
      }

      $post_counter++;

      $post_text = $this->postedText($val->post);

      $is_shared_with_me = array();
      $is_shared_by_me = array();

      /*is user Like, Comment, Share, Retags post*/
      if(!empty($member_id))
      {
        $post_like 				= PostLike::model()->find(array('condition'=>'post_id="'.$val->post_id.'" and member_id="'.$member_id.'"'));
        $post_share 			= PostShare::model()->find(array('condition'=>'post_id="'.$val->post_id.'" and from_id="'.$member_id.'"'));
        $post_comment 			= PostComment::model()->find(array('condition'=>'post_id="'.$val->post_id.'" and member_id="'.$member_id.'"'));
        $post_retag				= PostRetag::model()->find(array('condition'=>'post_id="'.$val->post_id.'" and member_id="'.$member_id.'"'));
        $post_retag_data 	 	= PostRetag::model()->findAll(array('condition'=>'post_id="'.$val->post_id.'"'));

        //post like by user
          if(!empty($post_like))
          {
            $msg_about_post .= 'You like this Post';
          }
        //post Shar by user
          if(!empty($post_share))
          {
            $msg_about_post .= 'You Share this Post';
          }
        //post Comment by user
          if(!empty($post_comment))
          {
            $msg_about_post .= 'You Commented on this Post';
          }
        //post retag by user
          if(!empty($post_retag))
          {
            $msg_about_post .= 'You retag this Post';
          }
          if(!empty($val->location))
          {
            $location = $val->location;
          }

      /*Is post shared with me start*/
        $is_shared_with_me = PostShare::model()->findAll(array('condition'=>'active_status="S" and status="1" and type="F" and post_id ='.$val->post_id.' and to_id='.$member_id));

      /*Shared by me on my time line*/
        $is_shared_by_me = PostShare::model()->find(array('condition'=>'active_status="S" and status="1" and type="T" and post_id ='.$val->post_id.' and from_id='.$member_id));

      }

      if($action == 'friendTimeLine' && !empty($_REQUEST['friend_id']))
      {
        $is_shared_by_frnd = PostShare::model()->find(array('condition'=>'active_status="S" and status="1" and type="T" and post_id ='.$val->post_id.' and from_id='.$_REQUEST['friend_id']));
      }

      $html_share ='';
      if(!empty($is_shared_by_me))
      {
        $profile_pic_shared = $this->getProfilePic($is_shared_by_me->from_id);
        $sharetime_ago =Controller::get_timeago($is_shared_by_me->added_on);
          $html_share ='<div class="pbl-ewih">
                      <div class="left-tex">
                        <div class="flot-left dp-icon"  onclick="friendDetails('.$is_shared_by_me->from_id.')">
                          <img class="img-responsive" src="'.$profile_pic_shared.'">
                        </div>
                        <a href="'.Yii::app()->createUrl('site/friendTimeLine?friend_id='.$is_shared_by_me->from_id).'" tagrget="_blank">
                          <div class="flot-left fon-12">
                            <strong>'.$is_shared_by_me->from->username.' </strong> shared this post.
                          </div>
                        </a>
                      </div>
                      <div class="flot-right fon-12">'.$sharetime_ago.'</div>
                    </div>';
      }

      if(!empty($is_shared_by_frnd))
      {
        $profile_pic_shared = $this->getProfilePic($is_shared_by_frnd->from_id);
        $sharetime_ago =Controller::get_timeago($is_shared_by_frnd->added_on);
          $html_share ='<div class="pbl-ewih">
                      <div class="left-tex">
                        <div class="flot-left dp-icon"  onclick="friendDetails('.$is_shared_by_frnd->from_id.')">
                          <img class="img-responsive" src="'.$profile_pic_shared.'">
                        </div>
                        <a href="'.Yii::app()->createUrl('site/friendTimeLine?friend_id='.$is_shared_by_frnd->from_id).'" tagrget="_blank">
                          <div class="flot-left fon-12">
                            <strong>'.$is_shared_by_frnd->from->username.' </strong> shared this post.
                          </div>
                        </a>
                      </div>
                      <div class="flot-right fon-12">'.$sharetime_ago.'</div>
                    </div>';
      }

      if(!empty($action == 'savedPost'))
      {
        $saved_time = $this->savePost($val->post_id,$member_id);
        $html_share ='<div class="pbl-ewih">
                <div class="left-tex">	Saved On	'.$saved_time.'</div>
              </div>';
      }
      $time_ago =Controller::get_timeago($val->added_on);
      $html .= '<div class="menu-m-are clearfix mb-3" id="post_'. $val->post_id.'"> '.$html_share.'
              <div class="img-tex-box border-0">
              <div class="col-12 row mx-auto">
                <div class="col-6 row d-flex mx-auto p-0">
                  <div class="col-12 col-sm-3 d-flex justify-content-center justify-content-sm-end py-3 px-0 pr-2">
                    <a class="dp-icon1" href="#" onclick="friendDetails('.$val->member_id.')">
                        <img class="img-fluid" style="border-radius: 50%;" src="'.$profile_pic.'">
                    </a>
                  </div>
                  <div class="col-12 col-sm-9 d-flex align-items-center">
                    <a href="'.Yii::app()->createUrl('site/friendTimeLine?friend_id='.$val->member_id).'" tagrget="_blank">';
                      if(!empty($val->member->username))
                      {
                        $html .='<div class="user_name">@'.$val->member->username."</div>";
                      }
                      if(!empty($val->member->first_name))
                      {
                        $html .= '<h4 class="pl-2 m-0">'.base64_decode($val->member->first_name)." ". base64_decode($val->member->last_name).'</h4>';
                      }
                $html .='</a>
                  </div>
                </div>
                <div class="col-6 time-instance text-right d-flex align-items-start justify-content-end py-3">
                  <h4>'.$time_ago.'</h4>
                </div>
              </div>
              </div>

                <div class="col-sm-12">';
                if(!empty($val->location) && $action != 'showReportedPostOfUser')
                  {
                    $html .='<div class="row">
                        <a href="'.Yii::app()->createUrl('site/locationPost?location_mstr_id='.$val->location_mstr_id).'" tagrget="_blank">
                          <div class="col-sm-1" style="padding:0;"><img class="img-responsive" style="margin-left: 5px;" src="'.$baseUrl.'/images/location_ping.png"></div>
                          <div class="col-sm-10" style="padding:0;line-height:25px">'.$location.'</div>
                        </a>
                        </div>';
                  }
                  else if($action == 'showReportedPostOfUser')
                  {
                    $html .='<div class="row">
                          <div class="col-sm-1" style="padding:0;"><img class="img-responsive" style="margin-left: 5px;" src="'.$baseUrl.'/images/location_ping.png"></div>
                          <div class="col-sm-10" style="padding:0; line-height:25px">'.$location.'</div>
                        </div>';
                  }

                $html .='<div class="post-tex" ><h5>'.$post_text.'</h5></div>';


                  if(!empty($post_friends->friend_id))
                    {
                      $html .= '<div class="post-tex"><h5>'.$this->getTaggedFriend($post_friends->friend_id).'</h5></div>';
                    }

          $html .='</div>';
          if(!empty($attachment))
          {
        $html .='<div class="col-sm-12 ">
              <section>';

              if(count($attachment)==1)
              {
                if($attachment[0]->type=="V")
                {
                  $html .='<div class="col-sm-grid-2">
                        <div style="width:100%; height:300px; overflow:hidden;">
                          <video class="_autoplay_vid" controls controlsList="nodownload" style="height:100%; width:100%;">
                            <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[0]->attachment.'" type="video/mp4" style="width:100%; height:100%">
                          </video>
                        </div>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid-2">
                        <div style="width:100%; height:300px; overflow:hidden;">
                          <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[0]->caption).'">
                              <img  src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'" style="width:100%; height:100%">
                              <center><span>'.base64_decode($attachment[0]->caption).'</span></center>
                          </a>
                        </div>
                      </div>';
                }
              }
              else if(count($attachment)==2)
              {
                if($attachment[0]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[0]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'">
                            <center><span>'.base64_decode($attachment[0]->caption).'</span></center>
                        </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid-2">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[0]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }
                if($attachment[1]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[1]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[1]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[1]->attachment.'">
                            <center><span>'.base64_decode($attachment[1]->caption).'</span></center>
                          </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid-2">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[1]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }
              }
              else if(count($attachment)==3)
              {
                if($attachment[0]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[0]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'">
                            <center><span>'.base64_decode($attachment[0]->caption).'</span></center>
                          </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[0]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }
                if($attachment[1]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[1]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[1]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[1]->attachment.'">
                            <center><span>'.base64_decode($attachment[1]->caption).'</span></center>
                          </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[1]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }
                if($attachment[2]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[2]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[2]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[2]->attachment.'">
                            <center><span>'.base64_decode($attachment[2]->caption).'</span></center>
                          </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[2]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }


              }
              else if(count($attachment)==4)
              {
                if($attachment[0]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[0]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[0]->attachment.'">
                            <center><span>'.base64_decode($attachment[0]->caption).'</span></center>
                          </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[0]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }
                if($attachment[1]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[1]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[1]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[1]->attachment.'">
                            <center><span>'.base64_decode($attachment[1]->caption).'</span></center>
                          </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[1]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }
                if($attachment[2]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[2]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[2]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[2]->attachment.'">
                            <center><span>'.base64_decode($attachment[2]->caption).'</span></center>
                          </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[2]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }
                if($attachment[3]->type=="P")
                {
                  $html .='<div class="col-sm-grid-2">
                        <a class="example-image-link" href="'.Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[3]->attachment.'" data-lightbox="example-'.$val->post_id.'" data-title="'.base64_decode($attachment[3]->caption).'">
                            <img class="img-responsive" src="'. Yii::app()->baseUrl.'/upload/post_attachment/'.$attachment[3]->attachment.'">
                            <center><span>'.base64_decode($attachment[3]->caption).'</span></center>
                          </a>
                      </div>';
                }
                else
                {
                  $html .='<div class="col-sm-grid">
                        <video width="700" height="250" class="img-responsive _autoplay_vid" controls controlsList="nodownload">
                          <source src="'. Yii::app()->baseUrl.'/upload/post_attachment/'. $attachment[3]->attachment.'" type="video/mp4">
                        </video>
                      </div>';
                }
              }
            $html .='</section>
            </div>';
          }

        if($action != 'showReportedPostOfUser')
        {


          $html .='<div class="col-sm-12 mt10">

                <div class="pull-right">
                  <div class="left-tex">
                    <a data-toggle="modal" data-id="'.$val->post_id.'" ';
                    if(!empty($member_id))
                    {
                      $html .=' href="#" onclick="postLike(this)"';
                    }
                    else
                    {
                      $html .='href="#onload"';
                    }

                    $html .='>
                      <div class="flot-left icon-with1">
                        <!--<img class="img-responsive" src="'.$baseUrl.'/images/mipmap-mdpi/like_icon.png">-->
                        <i class="fa fa-heart-o"></i>
                      </div>

                    <div class="flot-left  mr10">
                      <a data-toggle="modal" data-id="'.$val->post_id.'" ';
                      if(!empty($member_id)){ $html .=' href="#" onclick="getLike(this);"'; }
                      else{   $html .='href="#onload"';}

                    $html .='>
                      <h5 class="post_like_count_'.$val->post_id.' text-black mt-0">
                      '.$like_count.'</h5>';
                        // if($like_count > 1)
                        // {
                        // 	$html .=' Likes</a>';
                        // }else {
                        // 		$html .=' Like</a>';
                        // }
                  $html .=
                  '</div>
                    </a>
                  </div>';

              $html .='
              <div class="left-tex">
                <div class="flot-left icon-with1" onclick="showCommentarea('.$val->post_id.')">
                  <!--<img class="img-responsive" src="'.$baseUrl.'/images/mipmap-mdpi/comments_icon.png">-->
                  <i class="fa fa-comment-o"></i>
                </div>
                <a data-toggle="modal" data-id="'.$val->post_id.'"';
                if(!empty($member_id)){ $html .= 'href="#" onclick="postComment(this);"'; }
                else{ $html .= 'href="#onload"'; }
                $html .='>

                  <div class="flot-left mr10">
                    <h5 class="post_comment_count_'.$val->post_id.' mt-0 text-black">'.$comment_count.'</h5>';
                    // if($comment_count > 1)
                    // {
                    // 	$html .=' Comments';
                    // }
                    // else{
                    // 	$html .=' Comment';
                    // }

                    $html .='
                  </div>
                </a>

              </div>';
          $html .= '<div class="left-tex po-reti">
                <div class="couponcode">
                  <a data-id="'.$val->post_id.'"';

                  if(!empty($member_id)){ $html .= 'href="#" onclick="getSharePost(this)"'; }else{ $html .=' href="#onload" ';}

                  $html .='><span id="post_share_count_'.$val->post_id.'" >
                    <div class="flot-left icon-with1">

                      <!--<img class="img-responsive" src="'. $baseUrl.'/images/mipmap-mdpi/share_icon.png">-->
                      <i class="fa fa-share"></i>

                    </div>

                    <h5 class="flot-left mr10 mt-0 text-black">
                      '. $share_count.'</span>';
                        // if($share_count > 1)
                        // {
                        // 	$html .=' Shares';
                        // }
                        // else
                        // {
                        // 	$html .=' Share';
                        // }

                    $html .='</h5>
                  </a>
                  <span class="coupontooltip ">
                     <div class="coupontooltip-arrow"></div>
                       <a data-toggle="modal" ';
                       if(empty($member_id)){ $html .='href="#onload"' ;}else{ $html .=' href="#"onclick="sharePostOnTimeLine('. $val->post_id.');"';  }$html .='class="social-icon-div">Share On TimeLine</a>

                     <div class="coupontooltip-arrow"></div>
                       <a data-toggle="modal" ';
                       if(empty($member_id)){ $html .='href="#onload"' ;}else{ $html .=' href="#" onclick="sharePostPopup('.$val->post_id.');"';  }
                       $html .='class="social-icon-div">Share With Friends</a>
                     <div class="coupontooltip-arrow"></div>
                      <!-- Go to www.addthis.com/dashboard to customize your tools -->
                      <div class="addthis_inline_share_toolbox"></div>
                    </span>

                </div>
              </div>
            </div>';




          $html .='</div>';

          $html .='<div style="display:none" id="commentArea_'.$val->post_id.'">
                      <div id="commentShow_'.$val->post_id.'" style="padding:0 10px 0 10px; margin-bottom:15px;">

                </div>
                <div class="input-group stylish-input-group" style="padding:15px 15px 0 15px;">
                  <input type="text" class="form-control" autocomplete="off" spellcheck="false"  placeholder="Comment" name="comment" id="comment_'.$val->post_id.'" required>
                  <span class="input-group-addon bg-none">
                    <button type="button" class="bg-none-brd" onclick="AddpostComment('.$val->post_id.','.$member_id.')">
                      Submit
                    </button>
                  </span>
                  <input type="hidden" name="member_id" value="'.$member_id.'">
                  <input type="hidden" id="comment_post_id'.$val->post_id.'" name="post_id" value="'.$val->post_id.'">
                </div>
              </div>';
        }
          $html .='</div>';
    }
    return $html;
  }
/* Show post To user on TimeLine End*/

/*getFollowerLikeList start*/
public function actionGetFollowerList()
{
  $html ='<div class="pulb-tex">Follwer List</div>';
  $follow_user_list = FollowUser::model()->find(array('select'=>'group_concat(from_id)as from_id','condition'=>'active_status="S" and status="1" and to_id='.$_REQUEST['member_id']));

  if(!empty($follow_user_list->from_id))
  {
    $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$follow_user_list->from_id.')'));

    if(!empty($member_data))
    {
      foreach($member_data as $valUsr)
      {
        $html .= $this->MemberShortInfo($valUsr);
      }
    }
  }

  echo $html;
}
/*getFollowerLikeList end*/

/*getFollowingLikeList start*/
public function actionGetFollowingList()
{
  $html ='<div class="pulb-tex">Follwing List</div>';
  $follow_user_list = FollowUser::model()->find(array('select'=>'group_concat(to_id)as to_id','condition'=>'active_status="S" and status="1" and from_id='.$_REQUEST['member_id']));

  if(!empty($follow_user_list->to_id))
  {
    $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$follow_user_list->to_id.')'));

    if(!empty($member_data))
    {
      foreach($member_data as $valUsr)
      {
        $html .= $this->MemberShortInfo($valUsr);
      }
    }
  }

  echo $html;
}
/*getFollowingLikeList end*/

/*Profile liked by user start*/
public function actionGetProfileLikeList()
{
  $userLike_profile = ProfileLike::model()->find(array('select'=>'group_concat(member_id) as member_id','condition'=>'active_status="S" and status="1" and friend_id='.$_REQUEST['member_id']));
  $html ='<div class="pulb-tex">ProfileLike Liked By user</div>';
  if(!empty($userLike_profile->member_id))
  {
    $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$userLike_profile->member_id.')'));

    if(!empty($member_data))
    {
      foreach($member_data as $valUsr)
      {
        $html .= $this->MemberShortInfo($valUsr);
      }
    }
  }

  echo $html;
}
public function actionGetProfileLikeListUserName()
{
  $userLike_profile = ProfileLike::model()->find(array('select'=>'group_concat(member_id) as member_id','condition'=>'active_status="S" and status="1" and friend_id='.$_REQUEST['member_id']));
  if(!empty($userLike_profile->member_id))
  {
    $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$userLike_profile->member_id.')'));

    if(!empty($member_data))
    {
      foreach($member_data as $valUsr)
      {
        $html .= "<span>".$valUsr->username."</span>";
      }
    }
  }

  echo $html;
}
/*Profile liked by user end*/

/*Member short info start*/
public function MemberShortInfo($valUsr)
{
  $profile_pic = $this->getProfilePic($valUsr->member_id);
  $html = '';
    $html .='<div class="pbl-ewih" onclick="friendDetails('.$valUsr->member_id.')">
          <div class="left-tex">
            <div class="flot-left dp-icon">
              <img class="img-responsive" src="'.$profile_pic.'">
            </div>
            <div class="flot-left fon-12">
              <strong>'.$valUsr->username .'</strong> <br/>'. base64_decode($valUsr->first_name).' '.base64_decode($valUsr->last_name).'
            </div>
          </div>
        </div>';

  return $html;
}
/*Member short info end*/

/*Posted tags start */
  public function actionTagsPost()
  {
    $member_id = ApplicationSessions::run()->read('member_id');
    $tags 	= 	Tags::model()->find(array('condition'=>'active_status="S" and status="1" and tags = "'.$_REQUEST['tag'].'"'));
    $html = '';
    $cursor =  (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] - 1 : 0 ;
    $limit  =  (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 50;
    $newCursor = $limit + $cursor;
    if(!empty($tags))
    {
        $is_tagFollow = TagFollow::model()->find(array('condition'=>'active_status="S" and status="1" and tag_id="'.$tag_list->tags_id.'" and member_id='.$member_id));

        $post_tags = PostTags::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and tags_id='.$tags->tags_id));

        if(!empty($post_tags->post_id))
        {
          $condition = 'active_status="S" and status="1" and post_id IN ('.$post_tags->post_id.')';

          //blocked user ids
            $bloced_usr_id = $this->BlockedUserList($member_id);
            if(!empty($bloced_usr_id))
            {
               $condition .= ' and member_id NOT IN ('.$bloced_usr_id.')';
            }

          //blocked user ids
            $blokcedby_othr_usr_id = $this->BlockedByOtherUserList($member_id);
              if(!empty($blokcedby_othr_usr_id))
              {
                 $condition .= ' and member_id NOT IN ('.$blokcedby_othr_usr_id.')';
              }

          //reported post ids
            $reported_post = $this->ReportedPost($member_id);
              if(!empty($reported_post))
              {
                $condition .= ' and post_id NOT IN ('.$reported_post.')';
              }

          $post = Post::model()->findAll(array('condition'=>$condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'post_id desc'));

        }
    }
    else
    {
      $post 			= array();
      $is_tagFollow 	= 	array();
    }

    $this->render('tagPost',array('post'=>$post,'is_tagFollow'=>$is_tagFollow,'tags'=>$tags,'newCursor'=>$newCursor));
  }
/*Posted tags end */

/*location post start*/
  public function actionLocationPost()
  {
    $member_id = ApplicationSessions::run()->read('member_id');
    $location_mstr 	= 	LocationMaster::model()->find(array('condition'=>'active_status="S" and status="1" and location_master_id = "'.$_REQUEST['location_mstr_id'].'"'));
    $html = '';

    $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 0;
    $limit  = (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 50;
    $newCursor = $limit + $cursor;

    if(!empty($location_mstr))
    {
        $is_location_follow = Location::model()->find(array('condition'=>'active_status="S" and status="1" and location_master_id="'.$location_mstr->location_master_id.'" and member_id='.$member_id));

        $location_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and location_mstr_id='.$location_mstr->location_master_id));

        $location_followersId =  $this->LoctionFollowerIds($_REQUEST['location_mstr_id']);

        if(!empty($location_followersId))
        {
          $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$location_followersId.')'));

          if(!empty($member_data))
          {
            foreach($member_data as $valMem)
            {
              $location_followers .= $this->MemberShortInfo($valMem);
            }
          }
          else
          {
            $location_followers = array();
          }
        }

        if(!empty($location_post->post_id))
        {
          $condition = 'active_status="S" and status="1" and post_id IN ('.$location_post->post_id.')';

          //blocked user ids
            $bloced_usr_id = $this->BlockedUserList($member_id);
            if(!empty($bloced_usr_id))
              {
                 $condition .= ' and member_id NOT IN ('.$bloced_usr_id.')';
              }

          //blocked user ids
            $blokcedby_othr_usr_id = $this->BlockedByOtherUserList($member_id);
              if(!empty($blokcedby_othr_usr_id))
              {
                 $condition .= ' and member_id NOT IN ('.$blokcedby_othr_usr_id.')';
              }

          //reported post ids
            $reported_post = $this->ReportedPost($member_id);
              if(!empty($reported_post))
              {
                $condition .= ' and post_id NOT IN ('.$reported_post.')';
              }

          $post = Post::model()->findAll(array('condition'=>$condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'post_id desc'));

        }
    }
    else
    {
      $post 			= array();
      $is_location_follow 	= 	array();
      $location_followers = 	array();
    }

    $this->render('locationPost',array('post'=>$post,'is_location_follow'=>$is_location_follow,'location_followers'=>$location_followers,'newCursor'=>$newCursor));
  }
/*location post end*/

/*TimeLine Reload Post start*/
  public function actionLoadPostToTimeLine()
  {
    $member_id = ApplicationSessions::run()->read('member_id');

    $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 50;
    $limit  = 50;
    $html = '';
    $newCursor = $limit+$cursor;
    if(!empty($member_id))
    {
      /*************************/
        $condition = 'active_status="S" and status="1" and (type="PC" or type="S") ';
      //get Frnds & follower
        $frnd_nd_follower = $this->getFriendFollowerIds($member_id);

        if(!empty($frnd_nd_follower))
        {
          $member_ids = Member::model()->find(array('select'=>'group_concat(member_id) as member_id','condition'=>'active_status="S" and status="1" and acc_suspend="N" and member_id IN ('.$frnd_nd_follower.')'));

           $condition .= ' and (member_id IN ('.$frnd_nd_follower.') or to_id='.$member_id.')';
        }
      //get followedLocation user ids
        $location_followers = $this->followedSameLocationUserIds($member_id);

      //blocked user ids
        $bloced_usr_id = $this->BlockedUserList($member_id);

          if(!empty($bloced_usr_id))
          {
             $condition .= ' and member_id NOT IN ('.$bloced_usr_id.')';
          }

      //blocked user ids
        $blokcedby_othr_usr_id = $this->BlockedByOtherUserList($member_id);

          if(!empty($blokcedby_othr_usr_id))
          {
             $condition .= ' and member_id NOT IN ('.$blokcedby_othr_usr_id.')';
          }

      //reported post ids
        $reported_post = $this->ReportedPost($member_id);

          if(!empty($reported_post))
          {
            $condition .= ' and post_id NOT IN ('.$reported_post.')';
          }

      //getfrom post ids from user activity tbl
        // $post_ids = UserActivity::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>$condition));
        $post_ids 	= $this->SequenceOfactivity($condition);

        if(!empty($post_ids))
        {
          $locn_mstr_id 	= $this->followedLocationMstrId($member_id);
          $post_id 		= $post_ids;

          //Post shared by user on timeline
            $shared_post_to_frnd = PostShare::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and type="T" and to_id='.$member_id));

            if(!empty($shared_post_to_frnd->post_id))
            {
              if(!empty($post_id))
              {
                $post_id .= ','.$shared_post_to_frnd->post_id;
              }
              else
              {
                $post_id .= $shared_post_to_frnd->post_id;
              }
            }

            $psot_condition = 'active_status="S" and status="1" and post_id IN ('.$post_id.')';

            //post from location
              $post 	= Post::model()->findAll(array('condition'=>$psot_condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'FIELD(post_id,'.$post_id.')'));


            /*************************/

            if(!empty($post))
            {
              foreach($post as $val)
              {
                $post_data = $this->ShowPost($val->post_id);

                if(!empty($post_data))
                {
                  $html .= $post_data;
                }
              }
            }
        }
    }
    echo "200::".$html."::".$newCursor;

  }
/*TimeLine Reload Post end*/

/*TimeLine Reload Post start*/
  public function actionLoadTagPost()
  {


    $member_id = ApplicationSessions::run()->read('member_id');
    $tags 	= 	Tags::model()->find(array('condition'=>'active_status="S" and status="1" and tags = "'.$_REQUEST['tag'].'"'));
    $html = '';
    $cursor =  (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] - 1 : 10 ;
    $limit  =  (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
    $newCursor = $limit + $cursor;
    if(!empty($tags))
    {
      $is_tagFollow = TagFollow::model()->find(array('condition'=>'active_status="S" and status="1" and tag_id="'.$tag_list->tags_id.'" and member_id='.$member_id));

      $post_tags = PostTags::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and tags_id='.$tags->tags_id));

      if(!empty($post_tags->post_id))
      {
        $condition = 'active_status="S" and status="1" and post_id IN ('.$post_tags->post_id.')';
        //blocked user ids
          $bloced_usr_id = $this->BlockedUserList($member_id);
          if(!empty($bloced_usr_id))
          {
             $condition .= ' and member_id NOT IN ('.$bloced_usr_id.')';
          }

        //blocked user ids
          $blokcedby_othr_usr_id = $this->BlockedByOtherUserList($member_id);
            if(!empty($blokcedby_othr_usr_id))
            {
               $condition .= ' and member_id NOT IN ('.$blokcedby_othr_usr_id.')';
            }
        //reported post ids
          $reported_post = $this->ReportedPost($member_id);
            if(!empty($reported_post))
            {
              $condition .= ' and post_id NOT IN ('.$reported_post.')';
            }

        $post = Post::model()->findAll(array('condition'=>$condition,'limit'=>$limit,'offset'=>$cursor,'order'=>'post_id desc'));

        if(!empty($post))
        {
          foreach($post as $val)
          {
            $post_data = $this->ShowPost($val->post_id);

            if(!empty($post_data))
            {
              $html .= $post_data;
            }
          }
        }
      }
    }

    echo "200::".$html."::".$newCursor;
  }
/*TimeLine Reload Post end*/

/*TimeLine Reload Post start*/
  public function actionLoadLocationPost()
  {
    $member_id = ApplicationSessions::run()->read('member_id');
    $location_mstr 	= 	LocationMaster::model()->find(array('condition'=>'active_status="S" and status="1" and location_master_id = "'.$_REQUEST['location_mstr_id'].'"'));
    $html = '';

    $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 0;
    $limit  = (!empty($_REQUEST['limit']))  ? $_REQUEST['limit']  : 10;
    $newCursor = $limit + $cursor;

    if(!empty($location_mstr))
    {
      $is_location_follow = Location::model()->find(array('condition'=>'active_status="S" and status="1" and location_master_id="'.$location_mstr->location_master_id.'" and member_id='.$member_id));

      $location_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and location_mstr_id='.$location_mstr->location_master_id));

      $location_followersId =  $this->LoctionFollowerIds($_REQUEST['location_mstr_id']);

      if(!empty($location_followersId))
      {
        $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$location_followersId.')'));

        if(!empty($member_data))
        {
          foreach($member_data as $valMem)
          {
            $location_followers .= $this->MemberShortInfo($valMem);
          }
        }
        else
        {
          $location_followers = array();
        }
      }

      if(!empty($location_post->post_id))
      {
        $condition = 'active_status="S" and status="1" and post_id IN ('.$location_post->post_id.')';

        //blocked user ids
          $bloced_usr_id = $this->BlockedUserList($member_id);
          if(!empty($bloced_usr_id))
            {
               $condition .= ' and member_id NOT IN ('.$bloced_usr_id.')';
            }

        //blocked user ids
          $blokcedby_othr_usr_id = $this->BlockedByOtherUserList($member_id);
            if(!empty($blokcedby_othr_usr_id))
            {
               $condition .= ' and member_id NOT IN ('.$blokcedby_othr_usr_id.')';
            }

        //reported post ids
          $reported_post = $this->ReportedPost($member_id);
            if(!empty($reported_post))
            {
              $condition .= ' and post_id NOT IN ('.$reported_post.')';
            }

        $post = Post::model()->findAll(array('condition'=>$condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'post_id desc'));

          if(!empty($post))
          {
            foreach($post as $val)
            {
              $post_data = $this->ShowPost($val->post_id);

              if(!empty($post_data))
              {
                $html .= $post_data;
              }
            }
          }
      }
    }
    echo "200::".$html."::".$newCursor;
  }
/*TimeLine Reload Post end*/

/*TimeLine Reload Post start*/
public function actionLoadPostToFrndTimeLine()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 0;
  $limit  = (!empty($_REQUEST['limit'])) ? $_REQUEST['limit']   : 50;
  $newCursor = $limit + $cursor;
  $html = '';

    $loc_data = array();
    if(!empty($member_id))
    {
      $friend_id = $_REQUEST['friend_id'];

      $member_data = Member::model()->findByPk($friend_id);
      if(!empty($member_data))
      {

        $loc_data = $this->UserFollowedLocation($friend_id);

        /*****/

        $condition = 'active_status="S" and status="1" ';
        //own post from activity
          $own_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$friend_id));

          if(!empty($own_post->post_id))
          {
            $post_id = $own_post->post_id;
          }
        ////reported post ids
            $reported_post = $this->ReportedPost($member_id);

        //Post shared
          $shared_post = PostShare::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and type="T" and from_id='.$friend_id));

          if(!empty($shared_post->post_id))
          {
            if(!empty($post_id))
            {
              $post_id .= ','.$shared_post->post_id;
            }
            else
            {
              $post_id .= $shared_post->post_id;
            }
          }

          if(!empty($post_id))
          {
            $condition .= ' and post_id IN ('.$post_id.')';
          }
          else
          {
            $condition .= ' and member_id='.$friend_id;
          }
          if(!empty($reported_post))
          {
            $condition .= ' and post_id NOT IN ('.$reported_post.')';
          }
        /*****/

        $post = Post::model()->findAll(array('condition'=>$condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'post_id desc'));

        if(!empty($post))
        {
          foreach($post as $val)
          {
            $post_data = $this->ShowPost($val->post_id);

            if(!empty($post_data))
            {
              $html .= $post_data;
            }
          }
        }
      }

    }

  echo "200::".$html."::".$newCursor;
}

/*TimeLine Reload Post end*/

public function actionLoadPostToProfileTimeLine()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 0;
  $limit  = (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 50;
  $newCursor = $limit + $cursor;
  $html = '';

  if(!empty($member_id))
  {
    $cursor = (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] : 0;
    $limit  = (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 50;

    if(!empty($member_id))
    {
      $loc_data =  Controller::UserJoinedLocation($member_id);
      // $cnt_location++;
    }
    /*************************/

      $condition = 'active_status="S" and status="1" ';
        //own post from activity
          $own_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$member_id));

          if(!empty($own_post->post_id))
          {
            $post_id = $own_post->post_id;
          }
        ////reported post ids
            $reported_post = $this->ReportedPost($member_id);

        //Post shared
          $shared_post = PostShare::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and type="T" and from_id='.$member_id));

          if(!empty($shared_post->post_id))
          {
            if(!empty($post_id))
            {
              $post_id .= ','.$shared_post->post_id;
            }
            else
            {
              $post_id .= $shared_post->post_id;
            }
          }
          if(!empty($post_id))
          {
            $condition .= ' and post_id IN ('.$post_id.')';
          }
          else
          {
            $condition .= ' and member_id='.$member_id;
          }
          if(!empty($reported_post))
          {
            $condition .= ' and post_id NOT IN ('.$reported_post.')';
          }

          $post = Post::model()->findAll(array('condition'=>$condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'post_id desc'));
    /*************************/
      if(!empty($post))
      {
        foreach($post as $val)
        {
          $post_data = $this->ShowPost($val->post_id);

          if(!empty($post_data))
          {
            $html .= $post_data;
          }
        }
      }

  }

  echo "200::".$html."::".$newCursor;
}

/*Show Report post by other user start*/
  public function actionShowReportedPostOfUser()
  {
    $member_id = ApplicationSessions::run()->read('member_id');
    if(!empty($member_id))
    {
      $post_ids = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and member_id='.$member_id));

      $post_data = array();
      if(!empty($post_ids->post_id))
      {
        $post_warning = PostSetting::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and post_type="P" and report_type IS NOT NULL and type="R" and is_warning="Y" and is_warning_view="N" and post_id IN ('.$post_ids->post_id.')'));

        if(!empty($post_warning->post_id))
        {
          $post = Post::model()->findAll(array('condition'=>'active_status="S" and status="1" and post_id IN ('.$post_warning->post_id.')','order'=>'post_id desc'));

          PostSetting::model()->updateAll(array('is_warning_view'=>'Y','updated_on'=>time()),'active_status="S" and status="1" and post_type="P" and report_type IS NOT NULL and type="R" and  is_warning="Y" and post_id IN ('.$post_warning->post_id.')');

          Member::model()->updateByPk($member_id,array('is_warning_view'=>'Y'));
        }
      }
    }

    $this->render('warningPost',array('post'=>$post));
  }
/*Show Report post by other user end*/

/*Warning 2 and Warning 3 start */
  public function actionShowWarningPage()
  {
    $member_id = ApplicationSessions::run()->read('member_id');
    if(!empty($member_id))
    {
      $this->layout = 'commingSoon';
      $member_data = Member::model()->findByPk($member_id);
      $this->render('showWarningPage',array('member_data'=>$member_data));
    }

  }
/*Warning 2 and Warning 3 end */

/*Terms and condition start*/
public function actionShowPages()
{
  $this->layout = 'page';
  $page_data = Page::model()->findByPk($_REQUEST['page_id']);
  $this->render('pages',array('pages_data'=>$page_data));
}
/*Terms and condition end*/

/*connectionRequest start */
public function actionConnectionList()
{
  $member_id = ApplicationSessions::run()->read('member_id');
    if(!empty($member_id))
    {
      $friends_data = array();
      $connection_data = array();
      $friends = Friends::model()->find(array('select'=>'group_concat(friends_id) as friends_id ','condition'=>'active_status="S" and status="1" and is_accepted="Y" and is_block="N" and (from_id="'.$member_id.'" or to_id="'.$member_id.'")'));

      if(!empty($friends->friends_id))
      {
        $friends_data = Friends::model()->findAll(array('condition'=>'active_status="S" and status="1" and friends_id IN ('.$friends->friends_id.')'));
      }

      $connection_rquest = Friends::model()->find(array('select'=>'group_concat(friends_id) as friends_id ','condition'=>'active_status="S" and status="1" and  is_block="N" and (from_id="'.$member_id.'" or to_id="'.$member_id.'")'));

      if(!empty($connection_rquest->friends_id))
      {
        $connection_data = Friends::model()->findAll(array('condition'=>'active_status="S" and status="1" and friends_id IN ('.$connection_rquest->friends_id.')'));
      }

      $this->render('connectionList',array('friends'=>$friends_data,'connection_rquest'=>$connection_data));
    }
}
/*connectionRequest end */

/*friend short info start*/
public function FriendShortInfo($member_id)
{
  $html = '';
  $valUsr = Member::model()->findByPk($member_id);

  if(!empty($valUsr))
  {
    $profile_pic = $this->getProfilePic($valUsr->member_id);
    $html .='<div class="pbl-ewih" onclick="friendDetails('.$valUsr->member_id.')">
                <div class="left-tex">
                  <div class="flot-left dp-icon">
                    <img class="img-responsive" src="'.$profile_pic.'">
                  </div>
                  <div class="flot-left fon-12">
                    <strong>'.$valUsr->username .'</strong> <br/>'. base64_decode($valUsr->first_name).' '.base64_decode($valUsr->last_name).'
                  </div>
                </div>
              </div>';
  }

  return $html;
}
/*friend short info end*/

/*friend short info start*/
public function FriendRequestShortInfo($member_id,$type,$friends_id)
{
  $html = '';
  $valUsr = Member::model()->findByPk($member_id);
  $frndship_data = Friends::model()->findByPk($friends_id);
  if(!empty($valUsr))
  {
    $profile_pic = $this->getProfilePic($valUsr->member_id);
    $html .='<div class="pbl-ewih" id="'.$friends_id.'">
          <div class="left-tex">
            <div class="flot-left dp-icon">
              <img class="img-responsive" src="'.$profile_pic.'">
            </div>
            <div class="flot-left fon-12">
              <strong>'.$valUsr->username .'</strong> <br/>'. base64_decode($valUsr->first_name).' '.base64_decode($valUsr->last_name).'
            </div>

          </div>';
        if(!empty($frndship_data) && $frndship_data->is_accepted == "Y")
        {
          $html .='<div class="flot-right fon-12">
                    <button type="button" onclick="unfriend('.$friends_id.')">Unfriend </button>
                   </div>';
        }
        else
        {
          if($type == "S")
            {
              $html .='<div class="flot-right fon-12">
                    <button type="button" onclick="unfriend('.$friends_id.')">Delete Request </button>
                   </div>';
            }
          else
            {
              $html .='<div class="flot-right fon-12">
                    <button type="button" onclick="acceptfriendRequest('.$friends_id.')">Accept Request </button>
                    <button type="button" onclick="unfriend('.$friends_id.')">Delete Request </button>
                   </div>';
            }
        }


    $html .='</div>';
  }

  return $html;
}
/*friend short info end*/

/*show saved post start */
  public function actionSavedPost()
  {
    $member_id = ApplicationSessions::run()->read('member_id');
    $post_ids = array();
    if(!empty($member_id))
    {
      $saved_post = PostSetting::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and type="S" and post_type="P" and member_id='.$member_id));

      if(!empty($saved_post->post_id))
      {
        $post_ids = explode(',',$saved_post->post_id);
      }
      $this->render('savedPost',array('post'=>$post_ids));
    }
  }
/*show saved post end */

/*unsavePost start*/
public function actionUnsavePost()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($member_id))
  {
    PostSetting::model()->deleteAll(array('condition'=>'active_status="S" and status="1" and type="S" and post_type="P" and post_id='.$_REQUEST['post_id'].' and member_id='.$member_id));
  }
  echo "200";
}
/*unsavePost end*/

/*message test start*/
public function actionMessage()
{

  $message = ChatMessage::model()->findAll(array('condition'=>'active_status="S" and status="1"'));

  echo "<pre>";
  print_r($message);
  exit;
}
/*message test end*/

/*error page start*/
  public function actionErrorPage()
  {
    $msg = "Friend not found";
    $this->render('error',array('message'=>$msg,'code'=>'404'));
  }
/*error page end*/

/*showFriendlistToSharePost start*/
public function actionShowFriendlistToSharePost()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $html = '';
  if(!empty($member_id))
  {
    $friend_list = Friends::model()->find(array('select'=>'group_concat(from_id,",",to_id) as from_id ','condition'=>'active_status="S" and status="1" and  is_accepted="Y" and is_block="N" and (from_id="'.$member_id.'" or to_id="'.$member_id.'")'));

    $res ='';

    if(!empty($friend_list->from_id))
    {
      $result = $friend_list->from_id;

      $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id !='.$member_id.' and member_id IN ('.$friend_list->from_id.')'));

      if(!empty($member_data))
      {
        foreach($member_data as $valUsr)
        {
          $profile_pic = $this->getProfilePic($valUsr->member_id);

          $html .='<div class="pbl-ewih"  onclick="sharePostFriendTimeLine('.$valUsr->member_id.','.$_REQUEST['post_id'].');">
                      <div class="left-tex">
                        <div class="flot-left dp-icon">
                          <img class="img-responsive" src="'.$profile_pic.'">
                        </div>
                        <div class="flot-left fon-12">
                          <strong>'.$valUsr->username .'</strong> <br/>'. base64_decode($valUsr->first_name).' '.base64_decode($valUsr->last_name).'
                        </div>
                      </div>
                    </div>';
        }
      }
    }
  }

  echo $html;
}
/*showFriendlistToSharePost end*/

/*Chnage password start*/

public function actionUpdatePassword()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $html = '';

  if(!empty($member_id))
  {
    $new_pwd 		= $_REQUEST['newpassword'];
    $new_cnf_pwd 	= $_REQUEST['newpassword_cnf'];

    if($new_pwd == $new_cnf_pwd)
    {
      Member::model()->updateByPk($member_id,array('password'=>md5($new_pwd)));
      echo "200";
    }
    else
    {
      echo "404";
    }
  }
}
/*Chnage password end*/



/*Update notification count start*/
  public function actionUpdateNotificationCount()
  {
    $member_id = ApplicationSessions::run()->read('member_id');

    if(!empty($member_id))
    {
      UserActivity::model()->updateAll(array('is_view'=>'Y'),'to_id='.$member_id);
    }

    echo "200";
  }
/*Update notification count end*/

/*location psot show new Page start*/
  Public function actionLocationPostTimeline()
  {

    if(!empty($_REQUEST['location_mstr_cnf_id']) && $_REQUEST['location_mstr_cnf_id'] != $_REQUEST['location_mstr_id'])
    {
      $this->redirect('locationPostTimeline?location_mstr_id='.$_REQUEST['location_mstr_cnf_id']);
    }
    $member_id = ApplicationSessions::run()->read('member_id');
    if(!empty($member_id))
    {
      $loc_data .=  Controller::UserJoinedLocation($member_id);
      // $cnt_location++;
    }
    $location_mstr 	= 	LocationMaster::model()->find(array('condition'=>'active_status="S" and status="1" and location_master_id = "'.$_REQUEST['location_mstr_id'].'"'));
    $html = '';

    $cursor 	= (!empty($_REQUEST['cursor'])) ? $_REQUEST['cursor'] 	: 0;
    $limit  	= (!empty($_REQUEST['limit'])) ? $_REQUEST['limit'] 	: 50;
    $newCursor 	= $limit + $cursor;

    if(!empty($location_mstr))
    {
      $is_location_follow = Location::model()->find(array('condition'=>'active_status="S" and status="1" and location_master_id="'.$location_mstr->location_master_id.'" and member_id='.$member_id));

      $location_post = Post::model()->find(array('select'=>'group_concat(post_id) as post_id','condition'=>'active_status="S" and status="1" and location_mstr_id='.$location_mstr->location_master_id));

      $location_followersId =  $this->LoctionFollowerIds($_REQUEST['location_mstr_id']);

      if(!empty($location_followersId))
      {
        $member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$location_followersId.')'));

        if(!empty($member_data))
        {
          foreach($member_data as $valMem)
          {
            $location_followers .= $this->MemberShortInfo($valMem);
          }
        }
        else
        {
          $location_followers = array();
        }
      }

      if(!empty($location_post->post_id))
      {
        $condition = 'active_status="S" and status="1" and post_id IN ('.$location_post->post_id.')';

        //blocked user ids
          $bloced_usr_id = $this->BlockedUserList($member_id);
          if(!empty($bloced_usr_id))
            {
               $condition .= ' and member_id NOT IN ('.$bloced_usr_id.')';
            }

        //blocked user ids
          $blokcedby_othr_usr_id = $this->BlockedByOtherUserList($member_id);
            if(!empty($blokcedby_othr_usr_id))
            {
               $condition .= ' and member_id NOT IN ('.$blokcedby_othr_usr_id.')';
            }

        //reported post ids
          $reported_post = $this->ReportedPost($member_id);
            if(!empty($reported_post))
            {
              $condition .= ' and post_id NOT IN ('.$reported_post.')';
            }

        $post = Post::model()->findAll(array('condition'=>$condition,'offset'=>$cursor,'limit'=>$limit,'order'=>'post_id desc'));

      }
    }
    else
    {
      $post 				= array();
      $is_location_follow = array();
      $location_followers = array();
    }

    $this->render('locationPostNew',array('post'=>$post,'is_location_follow'=>$is_location_follow,'location_followers'=>$location_followers,'newCursor'=>$newCursor,'loc_data'=>$loc_data));
  }
/*location psot show new Page end*/


/*Friend request status start*/
public function actionFriendRequestStatus()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $html = '';
  if(!empty($member_id))
  {
    $request_status = Friends::model()->find(array('condition'=>'active_status="S" and status="1" and (from_id='.$member_id.' or to_id='.$member_id.') and (from_id='.$_REQUEST['friend_id'].' or to_id='.$_REQUEST['friend_id'].')'));

    $friend_data  = Member::model()->findByPk($_REQUEST['friend_id']);

    $profile_pic =$this->getProfilePic($_REQUEST['friend_id']);
    if(!empty($request_status))
    {
      if($request_status->is_accepted == "Y")
      {
        $html = '<div id="'.$request_status->friends_id.'">
              <div class="pbl-ewih" >
                <div class="left-tex">
                  <div class="flot-left dp-icon">
                    <img class="img-responsive" src="'.$profile_pic.'">
                  </div>
                  <div class="flot-left fon-12">
                    <strong>'.$friend_data->username .'</strong> <br/>'. base64_decode($friend_data->first_name).' '.base64_decode($friend_data->last_name).'
                  </div>
                  <div class="flot-left fon-12">
                    <button type="button" onclick="unfriend('.$request_status->friends_id.')"> Unfriend</button>
                  </div>
                </div>
              </div>
             </div>';
      }
      else
      {
        $html = '<div id="'.$request_status->friends_id.'">
              <div class="pbl-ewih" >
                <div class="left-tex">
                  <div class="flot-left dp-icon">
                    <img class="img-responsive" src="'.$profile_pic.'">
                  </div>
                  <div class="flot-left fon-12">
                    <strong>'.$friend_data->username .'</strong> <br/>'. base64_decode($friend_data->first_name).' '.base64_decode($friend_data->last_name).'
                  </div>
                  <div class="flot-left fon-12">
                    <button type="button" onclick="acceptfriendRequest('.$request_status->friends_id.')"> Accept Request</button>
                    <button type="button" onclick="unfriend('.$request_status->friends_id.')"> Delete Request</button>
                  </div>
                </div>
              </div>
             </div>';
      }
    }
    else
    {
      $html = '<div id="">
            <div class="pbl-ewih" >
              <div class="left-tex">

              </div>
            </div>
           </div>';
    }
  }

  echo $html;
}
/*Friend request status end*/
/*********************************************/
  ////////////Implementing New Desigen from 1st Augest 2018
/********************************************/
public function actionEditProfile()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  if(!empty($_POST['uname']))
  {

    $model =  Member::model()->findByPk($member_id);
    $model->username 		= $_REQUEST['uname'];
    $model->first_name 		= (!empty($_REQUEST['first_name'])) 	? base64_encode($_REQUEST['first_name']) : " ";
    $model->last_name 		= (!empty($_REQUEST['last_name'])) 	? base64_encode($_REQUEST['last_name']) : " ";
    $model->about_me 		= (!empty($_POST['bio'])) 	? base64_encode($_POST['bio']) : " ";
    $model->job_title 		= (!empty($_POST['weblink'])) 	? base64_encode($_POST['weblink']) : " ";
    $model->full_name 		= $_REQUEST['first_name'].' '.$_REQUEST['last_name'];
    $model->email_id 		= $_REQUEST['email'];
    $model->mobile_no 		= $_REQUEST['contact'];
    $model->dob 			= $_REQUEST['birthday'];
    $model->updated_on 		= time();


  ///profile pic File obj start

    if(!is_dir("upload/member/profile_pic/"))
      mkdir("upload/member/profile_pic/" , 0777,true);

    if(!empty($_FILES['profilepic']['name']) )
      {
        $ext = explode(".",$_FILES['profilepic']['name']);
        $image_name = time().".".$ext[1];
        $image_path = Yii::app()->basePath . '/../upload/member/profile_pic/'.$image_name;

        if(move_uploaded_file($_FILES['profilepic']['tmp_name'],$image_path))
        {
          $model->profile_pic = $image_name;
        }
      }
  ///profile pic File obj end

  ///cover pic File obj start
  if(!is_dir("upload/member/cover_photo/"))
    mkdir("upload/member/cover_photo/" , 0777,true);

      if(!empty($_FILES['coverpic']['name']) )
      {
        $ext = explode(".",$_FILES['coverpic']['name']);
        $image_name = time().".".$ext[1];
        $image_path = Yii::app()->basePath . '/../upload/member/cover_photo/'.$image_name;

        if(move_uploaded_file($_FILES['coverpic']['tmp_name'],$image_path))
        {
          $model->cover_photo = $image_name;
        }
      }
  ///cover pic File obj end

    if($model->save())
    {
      if(!empty($model->device_token))
      {
        $this->sendMessage('Profile Updated SUCCESSFULLY',$model->device_token);
      }
      //storeUserActivity
        $this->storeUserActivity($member_id,"Edited profile","EP","Member",$member_id);

        ApplicationSessions::run()->write('member_id', $model->member_id);
        ApplicationSessions::run()->write('member_email', $model->email_id);
        ApplicationSessions::run()->write('member_name', $model->first_name." ".$model->last_name);
        ApplicationSessions::run()->write('first_name',$model->first_name);
        ApplicationSessions::run()->write('last_name', $model->last_name);
        ApplicationSessions::run()->write('member_username', $model->username);
        ApplicationSessions::run()->write('member_pic', $model->profile_pic);
        ApplicationSessions::run()->write('cover_photo', $model->cover_photo);
        ApplicationSessions::run()->write('about_me', $model->about_me);
    }

  }
  $model =  Member::model()->findByPk($member_id);
  $this->render('editProfile',array('member_data'=>$model));
}

public function actionCreateNewPost()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $user_followedLocation 	= (!empty($member_id)) ? Location::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id='.$member_id)) : "";
  if(!empty($_REQUEST['post_description']))
  {

    $member_id = ApplicationSessions::run()->read('member_id');
    $model = new Post;
    $model->member_id 	= $member_id;
    $model->post 		= base64_encode($_REQUEST['post_description']);
    $model->title	 	= base64_encode($_REQUEST['title']);
    $model->location  	= $_REQUEST['tag_location'];
    $model->added_on 	= time();
    $model->updated_on 	= time();
    if($model->save())
    {
      /*if any iamge attachment*/
        if(!empty($_FILES['createPostimages']['name']))
        {
          if(!is_dir("upload/post_attachment/"))
          {
            mkdir("upload/post_attachment/" , 0777,true);
          }

          foreach($_FILES['createPostimages']['name'] as $key=>$val)
          {
            $tmpFilePath = $_FILES['createPostimages']['tmp_name'][$key];
            if ($tmpFilePath != "")
            {
              $image_path = Yii::app()->basePath . '/../upload/post_attachment/';
              $ext = explode(".",$_FILES['createPostimages']['name'][$key]);
              $image_name = time().".".$ext[1];

              $newFilePath = $image_path . $image_name;

              if(move_uploaded_file($tmpFilePath, $newFilePath))
              {
                  $post_Attach_img_model = new PostAttachment;
                  $post_Attach_img_model->attachment 	= $image_name;
                  $post_Attach_img_model->post_id		= $model->post_id;
                  $post_Attach_img_model->type		= "P";
                  $post_Attach_img_model->save();
              }
            }
          }
        }
        //if any video attachment
        if(!empty($_FILES['createPostVideo']['name']))
        {
          if(!is_dir("upload/post_attachment/"))
          {
            mkdir("upload/post_attachment/" , 0777,true);
          }

          foreach($_FILES['createPostVideo']['name'] as $key=>$val)
          {

            $tmpFilePath = $_FILES['createPostVideo']['tmp_name'][$key];

            if ($tmpFilePath != "")
            {
              $image_path = Yii::app()->basePath . '/../upload/post_attachment/';
              $ext = explode(".",$_FILES['createPostVideo']['name'][$key]);
              $image_name = rand().time().".".$ext[1];

              $newFilePath = $image_path . $image_name;
              $caption_val  = $_REQUEST['caption_video'][$key];
              if(move_uploaded_file($tmpFilePath, $newFilePath))
              {
                  $post_Attach_img_model = new PostAttachment;
                  $post_Attach_img_model->attachment 	= $image_name;
                  $post_Attach_img_model->post_id		= $model->post_id;
                  $post_Attach_img_model->type		= "V";
                  $post_Attach_img_model->save(false);

              }
            }
          }
        }

      $this->redirect(Yii::app()->homeUrl);
    }

  }
  $this->render('createNewPost',array('user_followedLocation'=>$user_followedLocation));
}
public function actionHastagsListing()
{
  //changes done by panakj on 1-aug-2018

  $member_id = ApplicationSessions::run()->read('member_id');

  if(!empty($member_id))
  {
    $tagFollow 	= 	TagFollow::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id='.$member_id));
    $this->render('hastagsListing',array('tagFollow'=>$tagFollow));
  }
  else
  {
    $this->redirect(Yii::app()->homeUrl);
  }

}

/*Blocked user show start*/
public function actionBlockedUser()
{
  $member_id 		= ApplicationSessions::run()->read('member_id');
  $memer_data 	= Member::model()->findByPk($member_id);
  $bloced_usr_id 	= $this->BlockedUserList($member_id);
  $blocked_member_data='';
  if(!empty($bloced_usr_id))
  {
    $blocked_member_data = Member::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id IN ('.$bloced_usr_id.')'));
  }
  $this->render('blockedUser',array('memer_data'=>$memer_data,'blocked_member_data'=>$blocked_member_data));
}
/*Blocked user show end*/
public function actionJoinLocations()
{
  $this->render('joinLocations');
}

public function actionAlljoinedlocation()
{
  $member_id = ApplicationSessions::run()->read('member_id');

  $folowed_location = Location::model()->findAll(array('condition'=>'member_id='.$member_id));
  $this->render('allJoinedLocation',array('folowed_location'=>$folowed_location));
}

public function actionFollowUnFollowlocation()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $is_i_follow = Location::model()->find(array('condition'=>'location_name="'.$_REQUEST['location_name'].'" and member_id='.$member_id));

  if(!empty($is_i_follow))
  {
    Location::model()->deleteByPk($is_i_follow->location_id);

    echo "F";
  }
  else
  {
    $location_master = LocationMaster::model()->find(array('condition'=>'location_name="'.$_REQUEST['location_name'].'"'));
    $model = new Location;
    $model->member_id 			= $member_id;
    $model->location_master_id 	= $location_master->location_master_id;
    $model->latitude 			= $location_master->latitude;
    $model->longitude 			= $location_master->longitude;
    $model->location_name 		= $_REQUEST['location_name'];
    $model->added_on			= time();
    $model->updated_on			= time();
    $model->save(false);

    echo "U";
  }

}

public function actionBlockunblockUser()
{
    $member_id = ApplicationSessions::run()->read('member_id');
    $is_blocked =  BlockUser::model()->find(array('condition'=>'from_id='.$member_id.' and to_id='.$_REQUEST['frnd_id']));
    if(!empty($is_blocked))
    {
      BlockUser::model()->deleteAll(array('condition'=>'from_id='.$member_id.' and to_id='.$_REQUEST['frnd_id']));
      echo "B";
    }
    else
    {
      $model = new BlockUser;
      $model->from_id = $member_id;
      $model->to_id = $_REQUEST['frnd_id'];
      $model->added_on = time();
      $model->updated_on = time();
      $model->save();

      echo "U";
    }
}
public function actionFollowingList()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $is_follow = array();
  if(!empty($member_id))
  {
    $is_follow = FollowUser::model()->findAll(array('condition'=>'active_status="S" and status="1" and location_id IS NULL	 and to_id='.$member_id));
  }
  $this->render('followingList',array('is_follow'=>$is_follow));
}


public function actionFollowerList()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $is_follow = array();
  if(!empty($member_id))
  {
    $is_follow = FollowUser::model()->findAll(array('condition'=>'active_status="S" and status="1" and location_id IS NULL	 and from_id='.$member_id));
  }
  $this->render('followerList',array('is_follow'=>$is_follow));
}

public function actionProfileDetails()
{
  $member_id = ApplicationSessions::run()->read('member_id');
  $like_given_by_me 	= ProfileLike::model()->findAll(array('condition'=>'active_status="S" and status="1" and friend_id='.$member_id));
  $like_get_by_me 	= ProfileLike::model()->findAll(array('condition'=>'active_status="S" and status="1" and member_id='.$member_id));
  $this->render('profileLike',array('like_given_by_me'=>$like_given_by_me,'like_get_by_me'=>$like_get_by_me));
}

public function actionTotalProfileliked()
{
  $liked_profile = ProfileLike::model()->count(array('condition'=>'active_status="S" and status="1" and friend_id='.$_REQUEST['member_id']));
  echo $liked_profile;
}

public function PrintExit($data)
{
  echo "<pre>";
  print_r($data);
  exit;
}
}
