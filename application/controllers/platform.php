<?php defined ('BASEPATH') OR exit ('No direct script access allowed');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2016 OA Wu Design
 * @link        http://www.ioa.tw/
 */

class Platform extends Site_controller {

  public function __construct () {
    parent::__construct ();
    $this->load->library ('fb');
  }
  public function mail () {
    if (($id = OAInput::get ('id')) && is_numeric ($id) && ($mail = Mail::find ('one', array ('select' => 'id, count_open', 'conditions' => array ('id = ?', $id)))) && ($mail->count_open += 1))
      Mail::transaction (function () use ($mail) { return $mail->save (); });
      
    if (User::current () && User::current ()->is_login ()) 
      return redirect_message (func_get_args (), array ('_flash_info' => ''));
    return redirect (forward_static_call_array (array ('Fb', 'loginUrl'), array_merge (array ('platform', 'fb_sign_in'), func_get_args ())));
  }
  public function login () {
    if (User::current () && User::current ()->is_login ()) return redirect_message (array ('admin'), array ());
    else $this->load_view ();
  }
  public function fb_sign_in () {
    if (!(Fb::login () && ($me = Fb::me ()) && ((isset ($me['name']) && ($name = $me['name'])) && (isset ($me['email']) && ($email = $me['email'])) && (isset ($me['id']) && ($id = $me['id'])))))
      return redirect_message (array (), array ('_flash_danger' => 'Facebook 登入錯誤，請通知程式設計人員!(1)'));

    if (!($user = User::find ('one', array ('conditions' => array ('uid = ?', $id)))))
      if (!User::transaction (function () use (&$user, $id, $name, $email) {
        return verifyCreateOrm ($user = User::create (array_intersect_key (array ('uid' => $id, 'name' => $name, 'email' => $email, 'token' => token ($id)), User::table ()->columns)))
         // &&
               // verifyCreateOrm ($role = UserRole::create (array_intersect_key (array ('user_id' => $user->id, 'name' => 'member'), UserRole::table ()->columns))) &&
               // verifyCreateOrm ($role = UserRole::create (array_intersect_key (array ('user_id' => $user->id, 'name' => 'keyword'), UserRole::table ()->columns)))
               ;
      }))
        return redirect_message (array (), array ('_flash_danger' => 'Facebook 登入錯誤，請通知程式設計人員!(2)'));

    $user->name = $name;
    // $user->email = $email;
    $user->login_count += 1;
    $user->logined_at = date ('Y-m-d H:i:s');

    if (!User::transaction (function () use ($user) { return $user->save (); }))
      return redirect_message (array (), array ('_flash_danger' => 'Facebook 登入錯誤，請通知程式設計人員!(3)'));

    Session::setData ('user_id', $user->id);
    return redirect_message (func_get_args (), array ('_flash_info' => '使用 Facebook 登入成功!'));
  }

  public function logout () {
    Session::setData ('user_id', 0);
    return redirect_message ('login', array ('_flash_info' => '登出成功!'));
  }
}
