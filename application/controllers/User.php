<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {

    public function login(){

        $data['title'] = "Mandiri Bapekis Login Page";

        //$component['banner'] = $this->load->view('user/component/login/banner','',TRUE);
        $component = "";

        $data['content'] = $this->load->view('user/login',$component,TRUE);

        $data['menu'] = $this->load->view('template/menu','',TRUE);
        $data['header'] = $this->load->view('template/header','',TRUE);
        $data['footer'] = $this->load->view('template/footer','',TRUE);

        $this->load->view('front',$data);
    }

    /**************************/
    /*                        */
    /*  Method for Action     */
    /*                        */
    /**************************/
    
    public function login_action(){
        $params['username'] = $this->input->post('username');
        $params['password'] = md5($this->input->post('password'));
        if($this->check_login($params['username'],$params['password'])){
            $user = $this->muser->get_user_id_by_username($params['username']);
            $data = array(
                'id' => $user->id,
                'username' => $user->username,
                'nik' => $user->nik,
                'full_name' => $user->full_name,
                'panggilan' => $user->panggilan,
                'is_logged_in' => true,
                'role' => $user->role,
                'position' => $user->position,
                'jabatan' => $user->jabatan,
                'group' => strtoupper($user->group),
                'dept' => $user->department,
                'directorate' => $user->directorate,
                'profile_picture' => $user->profile_picture,
                'is_employee' => $user->is_employee,
            );
            $this->session->set_userdata('userdb',$data);
            $this->muser->insert_user_login_log($data['id']);
            
            $last_page = $this->session->userdata('last_page_open');
            if($last_page){
                $this->session->unset_userdata('last_page_open'); redirect($last_page);
            }
            else{
                if($user->redirect_page_id){
                    $page_redirect = $this->mfiles_upload->get_db('name','asc','page',array('id' => $user->redirect_page_id),'','');
                    if($page_redirect) redirect($page_redirect[0]->url);
                }
                else{
                   redirect(''); 
                }
            }
        }else{
            $params['type_login']="failed";
            $this->login($params);
        }
    }
    
    private function check_login($username, $password){
         if(empty($username) || empty($password)){
             return false;
         }else{
             if($this->muser->verify($username, $password)){return true;}
             else{return false;}
         }
    }
}