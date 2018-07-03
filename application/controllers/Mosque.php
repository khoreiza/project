<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Mosque extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
		
		$session = $this->session->userdata('userbapekis');
        if(!$session){
            $url = current_url();
            $this->session->set_userdata('last_page_open',$url);
            redirect('user/login');
        }
		
        $this->load->model(array('muser','mfiles_upload','mmosque','mcalendar','mactivity_step'));
    }
    /*Method for page (public)
     */
    public function index()
    {
        $arr['start_date'] = date('Y-m-d');        
        
        /*$data['rooms'] = $this->mmosque->get_room_availability($arr,'','');
        $data['list_of_rooms'] = $this->load->view('meeting/_list_of_rooms',$data,TRUE);
        
        $data['requests'] = $this->mmosque->get_request_for_me();
        $data['my_reqs'] = $this->mmosque->get_my_request();
        $data['list_of_requests'] = $this->load->view('meeting/_list_of_requests',$data,TRUE);
        $data['list_of_groups'] = $this->mfiles_upload->get_distinct_col("pic_group","asc","meeting_room");

        $data['my_meeting_as_invitee'] = $this->mmosque->get_my_meeting_as_invitee();
        $data['my_meeting_as_invitor'] = $this->mmosque->get_my_meeting_as_invitor();
        $data['html_invitee'] = $this->load->view('meeting/_list_of_meeting_as_invitee',$data,TRUE);
        $data['html_invitor'] = $this->load->view('meeting/_list_of_meeting_as_invitor',$data,TRUE);


        $this->muser->insert_user_access_log("Access Meeting Room");*/
        $data['title'] = "Mosque - Bapekis";
        $header['page_name'] = "Meeting Room";


        $data['header'] = $this->load->view('admin/shared/first/header','',TRUE);   
        $data['footer'] = $this->load->view('admin/shared/first/footer','',TRUE);
        $data['content'] = $this->load->view('mosque/index',$data,TRUE);

        $this->load->view('admin/shared/front',$data);
    }

    

    public function change_mosque_data(){
        $group_req = $this->input->get('group_req');
        $arr['start_date'] = DateTime::createFromFormat('d M y', $this->input->get('date'))->format('Y-m-d');

        $data['mosques'] = $this->mmosque->get_room_availability($arr,'',$group_req);

        $json['status'] = 1;
        $json['list_of_mosque'] = $this->load->view('mosque/component/index/_list_of_mosque',$data,TRUE);

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($json));
    }






    public function add_mosque(){
        $id = $this->input->get('id');

        $data['users'] = $this->mfiles_upload->get_db('full_name','asc','user','','','');
        

        if($id){
            $data['room'] = $this->mfiles_upload->get_db('id','asc','meeting_room',array('id' => $id),'*','1')[0];
        }

        
        $data['popup_content'] = $this->load->view('mosque/component/index/_input_mosque', $data,TRUE);
        $data['popup_title'] = "Form Input Mosque"; 
        $data['popup_width'] = "680px";

        $json['status'] = 1;
        $json['html'] = $this->load->view('admin/shared/component/_popup_page', $data,TRUE);
        
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($json));
    }


    public function store_mosque(){
        $id = $this->uri->segment(3);
        $user = $this->session->userdata('userbapekis');
        $arr = array('name','region','location','note');
        
        $mosque = $this->mfiles_upload->get_form_element($arr);

        $mosque['updated_by'] = $user['id'];
        $mosque['updated_at'] = date("Y-m-d H:i");

        if(!$id){
            $mosque['created_at'] = date('Y-m-d H:i:s');
            $mosque['created_by'] = $user['id'];

            $mosque_id = $this->mfiles_upload->insert_db($mosque,'mosque');
        }
        else{
            $this->mfiles_upload->update_db($mosque,$id,'mosque');
            $mosque_id = $id;
        }

        if(isset($_FILES['photo']) && !($_FILES['photo']['error'] == UPLOAD_ERR_NO_FILE)){
            /*Upload */ 
            $path = "mosque/".$mosque_id."/photo/";
            $file = $this->mfiles_upload->upload_file("photo",$path,'mosque', 'mosque_photo',$mosque_id,true);

            if($file){ $this->mfiles_upload->make_photo_thumb($file['file_name'],$file['full_url'],$path,400,'_thumbnail.jpg');}

        }
        
        redirect('mosque');
    }
    




















    public function add_booking(){
        $data['start_time'] = $this->input->get('start');
        $data['room_id'] = $this->input->get('room_id');
        $data['type'] = $this->input->get('type'); // either calendar or meeting_request
        $data['room'] = $this->mfiles_upload->get_db('id','asc','meeting_room',array('id' => $data['room_id']),'*','1');
        if($data['room']){
            $data['room'] = $data['room'][0];
            if($data['room_id']){
                $data['room_name'] = $data['room']->name;    
            }
        }
        $data['start_date'] = $this->input->get('date');
        $data['agenda_id'] = $this->input->get('agenda_id');
        if($data['agenda_id']){
            // if edit
            $join_agenda[0]['table'] = "user";
            $join_agenda[0]['in'] = $data['type'].".created_by = user.id";
            $join_agenda[1]['table'] = "meeting_room";
            $join_agenda[1]['in'] = $data['type'].".meeting_room_id = meeting_room.id";
            $id = $data['type'].".id";
            $data['agenda'] = $this->mfiles_upload->get_db_join('start','asc',$data['type'],array("$id" => $data['agenda_id']),'*, '.$id.' as agenda_id','','',$join_agenda)[0];

            if($data['type']=='calendar'){
                $join_calendar[0]['table'] = "meeting_attendance";
                $join_calendar[0]['in'] = "meeting_attendance.meeting_id = calendar.id";
                $join_calendar[1]['table'] = "user";
                $join_calendar[1]['in'] = "meeting_attendance.user = user.id";
                $data['agenda_invitees'] = $this->mfiles_upload->get_db_join('start','asc',$data['type'],array("meeting_attendance.user <>"=>0, "$id" => $data['agenda_id']),'*, '.$id.' as agenda_id','','',$join_calendar);
                $join_cal[0]['table'] = "meeting_attendance";
                $join_cal[0]['in'] = "meeting_attendance.meeting_id = calendar.id";
                $data['custom_invitees'] = $this->mfiles_upload->get_db_join('start','asc',$data['type'],array("meeting_attendance.user"=>0, "$id" => $data['agenda_id']),'*, meeting_attendance.id as meeting_attendance_id, '.$id.' as agenda_id','','',$join_cal);
            }

            $data['attachment'] = $this->mfiles_upload->get_files_upload_by_ownership_id('meeting','attachment',$data['agenda_id']);
        }

        $json['status'] = 1;
        $json['html'] = $this->load->view('meeting/component/_input_booking', $data,TRUE);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($json));
    }

    public function show_agenda(){
        $data['type'] = $this->input->get('type');
        $data['agenda_id'] = $this->input->get('agenda_id');

        $join_agenda[0]['table'] = "meeting_room";
        $join_agenda[0]['in'] = $data['type'].".meeting_room_id = meeting_room.id";
        $id = $data['type'].".id";
        $data['agenda'] = $this->mfiles_upload->get_db_join('start','asc',$data['type'],array("$id" => $data['agenda_id']),'*, '.$id.' as agenda_id','','',$join_agenda)[0];
        $data['allowed_user'] = $this->mfiles_upload->get_db('id','asc','meeting_attendance',array("meeting_id" => $data['agenda_id']),'user','');
        $data['list_allowed_user'] = array();
        if($data['allowed_user']){
            foreach ($data['allowed_user'] as $key => $value) {
                array_push($data['list_allowed_user'],$value->user);
            }
        }
        array_push($data['list_allowed_user'],$data['agenda']->created_by);

        
        $data['popup_content'] =  $this->load->view('meeting/component/index/_show_agenda', $data,TRUE);
        $data['popup_title'] = "Agenda Detail"; 
        $data['popup_width'] = "600px";

        $json['status'] = 1;
        $json['html'] = $this->load->view('shared/component/_popup_broventh', $data,TRUE);


        
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($json));
    }

    public function agenda(){
        $id = $this->uri->segment(3);
        $user = $this->session->userdata('userbapekis');
        $data['meeting_id'] = $id;

        $data['allowed_user'] = $this->mfiles_upload->get_db('id','asc','meeting_attendance',array("meeting_id" => $id),'user','');
        $data['list_allowed_user'] = array();
        if($data['allowed_user']){
            foreach ($data['allowed_user'] as $key => $value) {
                array_push($data['list_allowed_user'],$value->user);
            }
        }

        if(is_user_role($user,"SYSTEM ADMINISTRATOR") || in_array($user['id'], $data['list_allowed_user'])){
            $join_agenda[0]['table'] = "meeting_room";
            $join_agenda[0]['in'] = "calendar.meeting_room_id = meeting_room.id";
            $join_agenda[1]['table'] = "user";
            $join_agenda[1]['in'] = "calendar.created_by = user.id";
            $data['agenda'] = $this->mfiles_upload->get_db_join('start','asc','calendar',array("calendar.id" => $id),'calendar.*,user.full_name,user.nik,user.profile_picture, calendar.id as agenda_id, meeting_room.name','','',$join_agenda)[0];


            /******** MEETING INFORMATION ********/
            $join_attdc[1]['table'] = "user";
            $join_attdc[1]['in'] = "meeting_attendance.user = user.id";
            $data['attendances'] = $this->mfiles_upload->get_db_join('full_name','asc','meeting_attendance',array("meeting_id" => $id, 'user <>'=>0),'*, meeting_attendance.id as meeting_attendance_id, user.id as user_id','','',$join_attdc);
            
            /******** MEETING CUSTOM INVITEES ********/
            $data['custom_invitees'] = $this->mfiles_upload->get_db('id','asc','meeting_attendance',array("user"=>0,"meeting_id" => $id),'*, meeting_attendance.id as meeting_attendance_id','');
            /******** END OF CUSTOM INVITEES ********/

            $data['attachments'] = $this->mfiles_upload->get_files_upload_by_ownership_id('meeting','attachment',$id);
            $data['general_info_div'] = $this->load->view('meeting/component/agenda/_general_info',$data,TRUE);
            /******** END OF MEETING INFORMATION ********/

            

            /********* MEETING MOM *********/
            $join_mom[1]['table'] = "user";
            $join_mom[1]['in'] = "minutes_of_meeting.updated_by = user.id";
            $data['moms'] = $this->mfiles_upload->get_db_join('full_name','asc','minutes_of_meeting',array("meeting_id" => $id),'*, minutes_of_meeting.id as mom_id, minutes_of_meeting.status as mom_status','','',$join_mom);
            if($data['moms']){
                // 1. get data mom
                $data['mom'] = $data['moms'][0];
                // 2. get data attachment
                $data['mom_attachment'] = $this->mfiles_upload->get_files_upload_by_ownership_id('mom','attachment',$data['mom']->mom_id);
            }
            $data['mom_div'] = $this->load->view('meeting/component/agenda/_mom',$data,TRUE);
            /********* MEETING MOM *********/

            /********* ACTIVITY STEPS *********/
            if(isset($data['mom'])){
                $data['mom_act'] = $this->mfiles_upload->get_db('id','asc','activity_steps',array("source_type" => 'mom', "source_id"=>$data['mom']->mom_id),'','');
                if($data['mom_act']){
                    $data['mom_act'] = $data['mom_act'][0];
                    // 1. get data activity steps
                    $join_act[0]['table'] = "user";
                    $join_act[0]['in'] = "activity_steps.user_pic = user.id";
                    $data['list_activity_steps'] = $this->mfiles_upload->get_db_join('full_name','asc','activity_steps',array("parent_id" => $data['mom_act']->id),'*, user.id as user_id','','',$join_act);
                    //$data['list_activity_steps'] = $this->mfiles_upload->get_db('id','asc','activity_steps',array("parent_id"=>$data['mom_act']->id),'','');
                }
            }
            $data['activity_steps_div'] = $this->load->view('meeting/component/agenda/_activity_steps',$data,TRUE);
            /********* END ACTIVITY STEPS *********/


            /******* Comment Section ********/
            $data['id'] = $id;
            $data['kind'] = "id"; $data['ownership'] = "calendar";
            $join[0] = array('table' => 'user', 'in' => "user.id = comment.user_id");
            $arr_where = array('ownership_type' => $data['ownership'], 'ownership_id' => $data['id']);
            $data['comments'] = $this->mfiles_upload->get_db_join('comment.id','desc','comment',$arr_where,'*, comment.id as comment_id','','',$join);
            $data['comment_list'] = $this->load->view('comment/theme/broventh/_list',$data,TRUE);
            $data['comment_view'] = $this->load->view('comment/theme/broventh/_form',$data,TRUE);
            /****** End of Comment ******/

            $data['title'] = $data['agenda']->title." - CBIC";
            $this->muser->insert_user_access_log("Access Meeting Room, See ".$data['agenda']->title." Detail Page");

            $data['header'] = $this->load->view('shared/header/header-submenu','',TRUE);   
            $data['footer'] = $this->load->view('shared/footer','',TRUE);
            $data['content'] = $this->load->view('meeting/agenda',$data,TRUE);

            $this->load->view('front',$data);
        }else{
            header('Location: '. base_url().'meeting');
            die();
        }
    }

    public function search_availability_room(){
        $room_id = $this->input->get('room_id');
        $group_req = $this->input->get('group_req');

        $arr['start_time'] = $this->input->get('start_time');
        $arr['end_time'] = $this->input->get('end_time');
        $arr['start_date'] = DateTime::createFromFormat('d M y', $this->input->get('start_date'))->format('Y-m-d');
        if($this->input->get('end_date')){
            $arr['end_date'] = DateTime::createFromFormat('d M y', $this->input->post('end_date'))->format('Y-m-d');
        }

        $data['arr_time']['start_time'] = strtotime($arr['start_date']." ".$arr['start_time']);
        $data['arr_time']['end_time'] = strtotime($arr['start_date']." ".$arr['end_time']);
        $data['rooms'] = $this->mmosque->get_room_availability($arr,$room_id,$group_req);

        $data['agenda_id'] = $this->input->get('agenda_id');

        $json['status'] = 1;
        $json['html'] = $this->load->view('meeting/component/_list_availability', $data,TRUE);
        
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($json));
    }

    

    public function store_booking(){
        $id = $this->uri->segment(3);
        $user = $this->session->userdata('userbapekis');
        $meeting_types = $this->input->post('meeting_type'); // this one is to differentiate calendar from request and calendar only
        $type = $this->input->post('type');

        if( $meeting_types!='meeting_request' || !$id ){

            $start_date = DateTime::createFromFormat('d M y', $this->input->post('start_date'))->format('Y-m-d');
            $end_date = $start_date;
            
            if($this->input->post('end_date')){
                $end_date = DateTime::createFromFormat('d M y', $this->input->post('end_date'))->format('Y-m-d');
            }

            $start_time = $this->input->post('start_time');
            $end_time = $this->input->post('end_time');


            $calendar['start'] = $start_date." ".$start_time;
            
            $calendar['end'] = $end_date." ".$end_time;

        }

        $calendar['title'] = $this->input->post('agenda');
        $calendar['description'] = $this->input->post('description');
        $calendar['meeting_room_id'] = $this->input->post('room_id');

        $room = $this->mfiles_upload->get_db('name','asc','meeting_room',array('id' => $calendar['meeting_room_id']),'','')[0];

        $calendar_id = NULL;
        $meeting_type = '';

        if(!$id){
            // if new
            $calendar['created_at'] = date('Y-m-d H:i:s');
            $calendar['created_by'] = $this->session->userdata('userbapekis')['id'];

            if(!$room->need_request){
                $meeting_type = 'meeting';
                $calendar['use_booking'] = 1;
                $calendar['user_allowed'] = strtoupper($user['group']).";";
                if($calendar['title']){
                    $calendar_id = $this->mfiles_upload->insert_db($calendar,'calendar');
                    $this->muser->insert_user_access_log("Access Meeting Room, Book ".$room->name." for ".$calendar['title']);
                }
            }else{
                $meeting_type = 'meeting_room_request';
                $calendar['status'] = "Pending";
                if($calendar['title']){
                    $calendar_id = $this->mfiles_upload->insert_db($calendar,'meeting_room_request');
                    $this->muser->insert_user_access_log("Access Meeting Room, Request ".$room->name." for ".$calendar['title']);
                }
            }
        }
        else{
            // if edit
            if($type=='calendar'){
                $meeting_type = 'meeting';
                $this->mfiles_upload->update_db($calendar,$id,'calendar');
                $this->muser->insert_user_access_log("Access Meeting Room, Edit Book ".$room->name." for ".$calendar['title']);
            }
            else{
                $meeting_type = 'meeting_room_request';
                $this->mfiles_upload->update_db($calendar,$id,'meeting_room_request');
                $this->muser->insert_user_access_log("Access Meeting Room, Edit Request ".$room->name." for ".$calendar['title']);
            }
            $calendar_id = $id;
            $this->mfiles_upload->delete_db_where(array('meeting_id'=>$calendar_id),'meeting_attendance');
        }

        /********************************* MEETING INVITEES ***********************************/
        $list_invitess = $this->input->post('invitees');
        if($list_invitess){
            foreach($list_invitess as $ivt){
                $meeting_attendance = array('user' => $ivt, 'meeting_id' => $calendar_id, 'is_present' => 0 );
                $this->mfiles_upload->insert_db($meeting_attendance,'meeting_attendance');
            }
        }

        $list_custom_invitee = $this->input->post('custom_invitee');
        $list_custom_invitee_unit = $this->input->post('custom_invitee_unit');
        $list_custom_invitee_phone = $this->input->post('custom_invitee_phone');
        if($list_custom_invitee){
            foreach($list_custom_invitee as $k=>$ivt){
                $meeting_attendance = array('custom_invitee_phone'=>$list_custom_invitee_phone[$k],'custom_invitee_unit'=>$list_custom_invitee_unit[$k],'custom_invitee' => $ivt, 'meeting_id' => $calendar_id, 'is_present' => 0, 'user' => 0 );
                $this->mfiles_upload->insert_db($meeting_attendance,'meeting_attendance');
            }
        }
        /********************************* END OF CULTURE mEETING INVITEES ***********************************/


        $upload_path = 'meeting/'.$meeting_type."/".$calendar_id."/";
        /*** Attachment ***/
        if(isset($_FILES['attachment']) && !($_FILES['attachment']['error'] == UPLOAD_ERR_NO_FILE)){
            /*Upload */ 
            $file = $this->mfiles_upload->upload_files("attachment",$upload_path,$meeting_type,'attachment',$calendar_id,true,false);
        }
        
        $json['status'] = 1;
        $this->output->set_content_type('application/json')
                         ->set_output(json_encode($json));
    }

    public function proceed_request(){
        $id = $this->input->get('id');
        $status = $this->input->get('status');
        $user = $this->session->userdata('userbapekis');
        $join[0]['table'] = "user";
        $join[0]['in'] = "meeting_room_request.created_by = user.id";
        $request_data = $this->mfiles_upload->get_db_join('id','asc','meeting_room_request',array('meeting_room_request.id' => $id),'meeting_room_request.*, user.group','','',$join)[0];

        if($id){
            $request['approved_at'] = date("Y-m-d H:i");
            $request['approved_by'] = $user['id'];
            if($status == "Rejected"){
                $request['status'] = "Rejected";
            }elseif($status == "Approved"){
                //Make Calendar
                $calendar['start'] = $request_data->start;
                $calendar['end'] = $request_data->end;
                $calendar['use_booking'] = 1;
                $calendar['title'] = $request_data->title;
                $calendar['description'] = $request_data->description;
                $calendar['meeting_room_id'] = $request_data->meeting_room_id;
                $calendar['created_at'] = date('Y-m-d H:i:s');
                $calendar['created_by'] = $request_data->created_by;
                $calendar['user_allowed'] = $request_data->group;
                $calendar_id = $this->mfiles_upload->insert_db($calendar,'calendar');

                //Update Request Data
                $request['status'] = "Approved";
                $request['calendar_id'] = $calendar_id;
            }


            $this->mfiles_upload->update_db($request,$id,'meeting_room_request');
            $json['status'] = 1;
        }
        else
        {
             $json['status'] = 0;
        }
        $this->output->set_content_type('application/json')
                         ->set_output(json_encode($json));
    }

    public function delete_booking(){
        $id = $this->input->get('id');
        $type = $this->input->get('type');
        if($id){
            if($type == "calendar"){ 
                $this->mcalendar->delete_calendar($id); 
                $this->mfiles_upload->delete_db_where(array("meeting_id" => $id),'meeting_attendance');
                $this->mfiles_upload->delete_with_files_ownership($id,'calendar','');
            }
            else { 
                $this->mfiles_upload->delete_db_where(array("id" => $id),$type); 
            }
            $json['status'] = 1;
        }
        else
        {
             $json['status'] = 0;
        }
        $this->output->set_content_type('application/json')
                         ->set_output(json_encode($json));
    }

    public function change_date(){
        $group_req = $this->input->get('group_req');
        $arr['start_date'] = DateTime::createFromFormat('d M y', $this->input->get('date'))->format('Y-m-d');        
        $data['rooms'] = $this->mmosque->get_room_availability($arr,'',$group_req);

        $json['status'] = 1;
        $json['date_title'] = date('l, j M y',strtotime($arr['start_date']));
        $json['html'] = $this->load->view('meeting/_list_of_rooms',$data,TRUE);
        
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($json));
    }

    

    public function store_attendance(){
        $list_id = $this->input->post('id');
        $list_attend = $this->input->post('attend');
        foreach($list_id as $k => $id){
            $attendance = array('is_present'=>$list_attend[$k]);
            $this->mfiles_upload->update_db($attendance,$id,'meeting_attendance');
        }
        $json['status'] = 1;
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($json));
    }

    public function input_mom(){
        $data['meeting_id'] = $this->input->get('meeting_id');
        // get mom
        $data['moms'] = $this->mfiles_upload->get_db('id','asc','minutes_of_meeting',array('meeting_id'=>$data['meeting_id']),'',1);
        $data['parent_activity'] = '';
        if($data['moms']){
            // edit
            // 1. get data mom
            $data['mom'] = $data['moms'][0];

            // 2. get data attachment
            $data['attachment'] = $this->mfiles_upload->get_files_upload_by_ownership_id('mom','attachment',$data['mom']->id);

            // 3. get data MOM Activity
            $data['parent_activity'] = $this->mfiles_upload->get_db('id','asc','activity_steps',array('source_type' => 'mom', 'source_id' => $data['mom']->id),'*, activity_steps.id as act_step_id',1);
            // parent_id => big parent id
            // id => activity steps of mom

            // 4. get data subactivity
            if($data['parent_activity']){
                $data['parent_activity'] = $data['parent_activity'][0];
                $join_prog[0]['table'] = "progress";
                $join_prog[0]['in'] = "progress.action_step_id = activity_steps.id";
                $data['sub_activity_list'] = $this->mfiles_upload->get_db_join('activity_steps.id','asc','activity_steps',array("parent_id"=>$data['parent_activity']->id),'activity_steps.*, progress.*, activity_steps.id as act_step_id, progress.id as progress_id, progress.progress as progress','','',$join_prog);
            }
        }
        $data['pic'] = $this->mfiles_upload->get_pic_employee();
        $join_act[0]['table'] = "user";
        $join_act[0]['in'] = "activity_steps.user_pic = user.id";
        $data['list_invitees'] = $this->mfiles_upload->get_db('user','asc','meeting_attendance',array('meeting_id' => $data['meeting_id']),'user','');
        $arr_invt = array();
        if($data['list_invitees']){
            foreach($data['list_invitees'] as $ivt){
                array_push($arr_invt,$ivt->user);
            }
        }
        // lookup all available activities related invitees
        $data['list_activities'] = $this->mfiles_upload->get_db_join('user.full_name','asc','activity_steps',array("user_pic"=>$arr_invt),'*, activity_steps.id as act_step_id','','',$join_act);

        $data['html'] = $this->load->view('meeting/component/_input_mom', $data,TRUE);
        $data['status'] = 1;
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    public function store_mom(){
        $id = $this->input->post('id'); // id of minutes of meeting
        $parent_act = $this->input->post('parent_act');
        $user = $this->session->userdata('userbapekis');
        $arr_mom = array('title','background','content','result','status','meeting_id','approved_by');
        $mom = $this->mfiles_upload->get_form_element($arr_mom);
        
        $arr_sub_act = array('activity_sub','user_pic_sub','deadline_sub','status_sub','progress_sub');
        $sub_act = $this->mfiles_upload->get_form_element($arr_sub_act);
        //$list_activities = $this->mfiles_upload->get_form_element($arr2);
        if(!$id){
            // if new
            // 1. insert mom
            $mom['created_date'] = date('Y-m-d H:i:s');
            $mom['created_by'] = $this->session->userdata('userbapekis')['id'];
            $mom['updated_date'] = $mom['created_date'];
            $mom['updated_by'] = $mom['created_by'];
            $mom_id = $this->mfiles_upload->insert_db($mom,'minutes_of_meeting');

            // 2.create new MOM activity step
            if($parent_act!==0){
                if($sub_act['activity_sub']){
                    $mom_act = array(
                        'activity' => 'Minutes of Meeting '.$mom['created_date'],
                        'user_pic' => $user['id'], 
                        'deadline' => '',
                        'status' => 'Not Started',
                        'source_type' => 'mom',
                        'source_id' => $mom_id,
                        'parent_id' => $parent_act
                    );
                    $mom_act_id = $this->mfiles_upload->insert_db($mom_act, 'activity_steps');

                    // 3. insert many progress under activity step
                    // store_parent_sub_activity($parent_id, $source_type, $source_id)
                    $this->mactivity_step->store_parent_sub_activity($mom_act_id, 'mom', 0);

                    // 4. update MOM Activity step with newest date
                    $deadline_sub = $this->input->post('deadline_sub');
                    $newest_date = $deadline_sub[0];
                    foreach($deadline_sub as $v){
                        if(strtotime($v) >= strtotime($newest_date)){
                            $newest_date = $v;
                        }
                    }
                    $this->mfiles_upload->update_db(array('deadline'=>date('Y-m-d',strtotime($newest_date))),$mom_act_id,'activity_steps');
                }
            }
        }
        else{
            // if edit
            // 1. update mom
            $mom['updated_date'] = date('Y-m-d H:i:s');
            $mom['updated_by'] = $this->session->userdata('userbapekis')['id'];
            $mom_id = $this->mfiles_upload->update_db($mom,$id,'minutes_of_meeting');

            // 2. update MOM activity
            $mom_activity_id = $this->input->post('mom_activity_id');
            $mom_parent_activity_id = $this->input->post('mom_parent_activity_id');
            $mom_act_arr = array('parent_id'=>$parent_act);
            if($parent_act!=0){
                if($mom_activity_id){
                    // edit MOM Activity
                    $mom_id = $this->mfiles_upload->update_db($mom_act_arr,$mom_activity_id,'activity_steps');                
                }else{
                    $mom_activity = array(
                        'activity' => 'Minutes of Meeting '.$mom['updated_date'],
                        'user_pic' => $user['id'], 
                        'deadline' => '',
                        'status' => 'Not Started',
                        'source_type' => 'mom',
                        'source_id' => $mom_id,
                        'parent_id' => $parent_act
                    );
                    $mom_activity_id = $this->mfiles_upload->insert_db($mom_activity, 'activity_steps');
                }
                // 3. update sub list activity and progress
                $this->mactivity_step->store_parent_sub_activity($mom_activity_id, 'mom', 0);

                // 4. update MOM Activity step with newest date
                $deadline_sub = $this->input->post('deadline_sub');
                $newest_date = $deadline_sub[0];
                foreach($deadline_sub as $v){
                    if(strtotime($v) >= strtotime($newest_date)){
                        $newest_date = $v;
                    }
                }
                $this->mfiles_upload->update_db(array('deadline'=>date('Y-m-d',strtotime($newest_date))),$mom_activity_id,'activity_steps');
            }else{
                $this->mactivity_step->deleteActivitySteps($mom_activity_id);
            }
        }
        /*** Attachment ***/
        $upload_path = 'mom/'.$mom_id.'/';
        if(isset($_FILES['attachment']) && !($_FILES['attachment']['error'] == UPLOAD_ERR_NO_FILE)){
            $file = $this->mfiles_upload->upload_files("attachment",$upload_path,'mom','attachment',$mom_id,true,false);
        }
        $json['status'] = 1;
        $json['redirect_url'] = base_url().'meeting/agenda/'.$mom['meeting_id'];
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($json));
    }

    


    public function detail_mom(){
        $id = $this->uri->segment(3); // id of minutes of meeting
        // get mom
        $join_mom[0]['table'] = "user";
        $join_mom[0]['in'] = "minutes_of_meeting.updated_by = user.id";
        $join_mom[1]['table'] = "calendar";
        $join_mom[1]['in'] = "minutes_of_meeting.meeting_id = calendar.id";
        $join_mom[2]['table'] = "meeting_room";
        $join_mom[2]['in'] = "meeting_room.id = calendar.meeting_room_id";
        $select_mom = 'minutes_of_meeting.*, minutes_of_meeting.id as mom_id, minutes_of_meeting.status as mom_status, calendar.title as meeting_title, meeting_room.name as room_name, calendar.start';
        $data['moms'] = $this->mfiles_upload->get_db_join('full_name','asc','minutes_of_meeting',array("minutes_of_meeting.id" => $id),$select_mom,'','',$join_mom);
        if($data['moms']){
            // edit
            // 1. get data mom
            $data['mom'] = $data['moms'][0];

            // 2. get data attachment
            $data['attachment'] = $this->mfiles_upload->get_files_upload_by_ownership_id('mom','attachment',$data['mom']->mom_id);

            // 3. get data MOM Activity
            $data['parent_activity'] = $this->mfiles_upload->get_db('id','asc','activity_steps',array('source_type' => 'mom', 'source_id' => $data['mom']->mom_id),'*, activity_steps.id as act_step_id',1);

            // 4. get data subactivity
            if($data['parent_activity']){
                
                $data['mom_act'] = $this->mfiles_upload->get_db('id','asc','activity_steps',array("source_type" => 'mom', "source_id"=>$data['mom']->mom_id),'','');
                if($data['mom_act']){
                    $data['mom_act'] = $data['mom_act'][0];
                    // 1. get data activity steps
                    $join_act[0]['table'] = "user";
                    $join_act[0]['in'] = "activity_steps.user_pic = user.id";
                    $data['list_activity_steps'] = $this->mfiles_upload->get_db_join('full_name','asc','activity_steps',array("parent_id" => $data['mom_act']->id),'*, user.id as user_id','','',$join_act);
                    //$data['list_activity_steps'] = $this->mfiles_upload->get_db('id','asc','activity_steps',array("parent_id"=>$data['mom_act']->id),'','');
                }

                /*$data['parent_activity'] = $data['parent_activity'][0];
                $join_prog[0]['table'] = "progress";
                $join_prog[0]['in'] = "progress.action_step_id = activity_steps.id";
                $data['sub_activity_list'] = $this->mfiles_upload->get_db_join('activity_steps.id','asc','activity_steps',array("parent_id"=>$data['parent_activity']->id),'activity_steps.*, progress.*, activity_steps.id as act_step_id, progress.id as progress_id, progress.progress as progress','','',$join_prog);
                */
            }
        }
        

        //$this->muser->insert_user_access_log("Access Meeting Room");
        $data['title'] = "MoM Detial - CBIC";

        $data['header'] = $this->load->view('shared/header/header-submenu','',TRUE);   
        $data['footer'] = $this->load->view('shared/footer','',TRUE);
        $data['content'] = $this->load->view('meeting/mom',$data,TRUE);

        $this->load->view('front',$data);
    }







    public function show_mom(){
        $id = $this->input->post('id'); // id of minutes of meeting
        // get mom
        $join_mom[0]['table'] = "user";
        $join_mom[0]['in'] = "minutes_of_meeting.updated_by = user.id";
        $join_mom[1]['table'] = "calendar";
        $join_mom[1]['in'] = "minutes_of_meeting.meeting_id = calendar.id";
        $join_mom[2]['table'] = "meeting_room";
        $join_mom[2]['in'] = "meeting_room.id = calendar.meeting_room_id";
        $select_mom = 'minutes_of_meeting.*, minutes_of_meeting.id as mom_id, minutes_of_meeting.status as mom_status, calendar.title as meeting_title, meeting_room.name as room_name, calendar.start, calendar.id as meeting_id';
        $data['moms'] = $this->mfiles_upload->get_db_join('full_name','asc','minutes_of_meeting',array("minutes_of_meeting.id" => $id),$select_mom,'','',$join_mom);
        if($data['moms']){
            // edit
            // 1. get data mom
            $data['mom'] = $data['moms'][0];

            // 2. get data attachment
            $data['attachment'] = $this->mfiles_upload->get_files_upload_by_ownership_id('mom','attachment',$data['mom']->mom_id);

            // 3. get data MOM Activity
            $data['parent_activity'] = $this->mfiles_upload->get_db('id','asc','activity_steps',array('source_type' => 'mom', 'source_id' => $data['mom']->mom_id),'*, activity_steps.id as act_step_id',1);

            // 4. get data subactivity
            if($data['parent_activity']){
                $data['parent_activity'] = $data['parent_activity'][0];
                $join_prog[0]['table'] = "progress";
                $join_prog[0]['in'] = "progress.action_step_id = activity_steps.id";
                $data['sub_activity_list'] = $this->mfiles_upload->get_db_join('activity_steps.id','asc','activity_steps',array("parent_id"=>$data['parent_activity']->id),'activity_steps.*, progress.*, activity_steps.id as act_step_id, progress.id as progress_id, progress.progress as progress','','',$join_prog);
            }
        }
        

        $json['status'] = 1;

        $data['popup_content'] = $this->load->view('meeting/component/agenda/_show_mom', $data,TRUE);
        $data['popup_title'] = "Meeting MoM"; 
        $data['popup_width'] = "900px";

        $data['status'] = 1;
        $data['html'] = $this->load->view('shared/component/_popup_broventh', $data,TRUE);

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    public function store_attendance_presence(){
        // get_db($order,$how,$db,$arr_where,$select,$limit)
        $data['meeting_id'] = $this->input->post('meeting_id');
        $data['attendances'] = $this->mfiles_upload->get_db('id','asc','meeting_attendance',array("meeting_id" => $data['meeting_id']),'','');
        if($data['attendances']){
            foreach($data['attendances'] as $att){
                $is_present = $this->input->post('attendance_'.$att->id);
                $attendance = array( 'is_present' => $is_present );
                $this->mfiles_upload->update_db_where($attendance, array('id'=>$att->id),'meeting_attendance');
            }
        }
        $data['status'] = 1;
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    public function print_mom(){
        $mom_array = array();
        $id = $this->input->post('id'); // id of meeting (not mom)
        $join_agenda[0]['table'] = 'minutes_of_meeting';
        $join_agenda[0]['in'] = 'minutes_of_meeting.meeting_id = calendar.id';
        $join_agenda[1]['table'] = 'user';
        $join_agenda[1]['in'] = 'calendar.created_by = user.id';
        $join_agenda[2]['table'] = 'meeting_room';
        $join_agenda[2]['in'] = 'meeting_room.id = calendar.meeting_room_id';
        $data['meeting'] = $this->mfiles_upload->get_db_join('minutes_of_meeting.id','asc','calendar',array("calendar.id" => $id),'calendar.*,user.full_name,user.nik,user.profile_picture, calendar.id as agenda_id, meeting_room.name as meeting_room_name','','',$join_agenda);
        if($data['meeting']){
            // 1. get agenda
            $data['meeting'] = $data['meeting'][0]; //print_r($data['meeting']);
            $mom_array['calendar_title'] = $data['meeting']->title;
            $mom_array['calendar_location'] = $data['meeting']->meeting_room_name;
            $mom_array['calendar_description'] = strip_tags($data['meeting']->description);
            $mom_array['calendar_date'] = date('d-M-Y',strtotime($data['meeting']->start));
            $mom_array['calendar_day'] = date('D',strtotime($data['meeting']->start));
            $mom_array['calendar_time'] = date('H:i A',strtotime($data['meeting']->start));
            $mom_array['calendar_created_by_fullname'] = $data['meeting']->full_name;

            // 2. get minutes of meeting
            $join_mom[0]['table'] = "user up";
            $join_mom[0]['in'] = "minutes_of_meeting.updated_by = up.id";
            $join_mom[1]['table'] = "user ap";
            $join_mom[1]['in'] = "minutes_of_meeting.approved_by = ap.id";
             $data['moms'] = $this->mfiles_upload->get_db_join('minutes_of_meeting.id','asc','minutes_of_meeting',array("meeting_id" => $id),'*, minutes_of_meeting.id as mom_id, minutes_of_meeting.status as mom_status, up.full_name as up_full_name, ap.full_name as ap_full_name','','',$join_mom);
            if($data['moms']){
                // get data mom
                $data['mom'] = $data['moms'][0];
                $mom_array['mom_background'] = strip_tags($data['mom']->background);
                $mom_array['mom_content'] = strip_tags($data['mom']->content);
                $mom_array['mom_updated_by_fullname'] = $data['mom']->up_full_name;
                $mom_array['mom_updated_date'] = date('d-M-Y',strtotime($data['mom']->updated_date));
                $mom_array['mom_approved_by_fullname'] = $data['mom']->ap_full_name;

                // 3. get activity steps
                $final_act = array();
                $join_act[0]['table'] = "user";
                $join_act[0]['in'] = "activity_steps.user_pic = user.id";
                $data['mom_acts'] = $this->mfiles_upload->get_db_join('activity_steps.id','asc','activity_steps',array("source_type" => 'mom','source_id'=>$data['mom']->mom_id),'*, activity_steps.id as act_step_id, user.id as user_id','','',$join_act);
                if($data['mom_acts']){
                    $data['mom_act'] = $data['mom_acts'][0];
                    $join_act[0]['table'] = "user";
                    $join_act[0]['in'] = "activity_steps.user_pic = user.id";
                    $data['mom_acts'] = $this->mfiles_upload->get_db_join('activity_steps.id','asc','activity_steps',array("parent_id" => $data['mom_act']->act_step_id),'*','','',$join_act);

                    if($data['mom_acts']){
                        foreach($data['mom_acts'] as $ma){
                            array_push($final_act,$ma);
                        }
                    }

                    $i = 1;
                    $mom_array['activity_steps_no'] = array();
                    foreach($final_act as $fa){
                        $mom_array['activity_steps_no']['activity_steps_no#'.$i] = $i;
                        $mom_array['activity_steps_no']['activity_steps_activity#'.$i] = $fa->activity;
                        $mom_array['activity_steps_no']['activity_steps_user_pic#'.$i] = $fa->full_name;
                        $mom_array['activity_steps_no']['activity_steps_deadline#'.$i] = date('d-M-Y',strtotime($fa->deadline));
                        $i++;
                    }
                    $mom_array['activity_steps_no']['number'] = $i-1;
                }else{
                    $mom_array['activity_steps_no'] = array();
                    $mom_array['activity_steps_no']['number'] = 0;
                }

            }
        }
        $data['url'] = $this->mfiles_upload->print_template('minutes_of_meeting','mom','',$id,$mom_array);
        $data['status'] = 1;
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    public function print_list_attendance(){
        $mom_array = array();
        $id = $this->input->post('id'); // id of meeting (not mom)
        $join_agenda[0]['table'] = 'user';
        $join_agenda[0]['in'] = 'calendar.created_by = user.id';
        $join_agenda[1]['table'] = 'meeting_room';
        $join_agenda[1]['in'] = 'meeting_room.id = calendar.meeting_room_id';
        $data['meeting'] = $this->mfiles_upload->get_db_join('user.id','asc','calendar',array("calendar.id" => $id),'calendar.*,user.full_name,user.nik,user.profile_picture, calendar.id as agenda_id, meeting_room.name as meeting_room_name','','',$join_agenda);
        if($data['meeting']){
            // 1. get agenda
            $data['meeting'] = $data['meeting'][0]; //print_r($data['meeting']);
            $mom_array['calendar_title'] = $data['meeting']->title;
            $mom_array['calendar_location'] = $data['meeting']->meeting_room_name;
            $mom_array['calendar_description'] = strip_tags($data['meeting']->description);
            $mom_array['calendar_date'] = date('d-M-Y',strtotime($data['meeting']->start));
            $mom_array['calendar_day'] = date('D',strtotime($data['meeting']->start));
            $mom_array['calendar_time'] = date('H:i A',strtotime($data['meeting']->start));
            $mom_array['calendar_created_by_fullname'] = $data['meeting']->full_name;

            // 2. get list attendance
            $final_act = array();
            $join_att[0]['table'] = "user";
            $join_att[0]['in'] = "meeting_attendance.user = user.id";
            $data['mom_attendants'] = $this->mfiles_upload->get_db_join('meeting_attendance.id','asc','meeting_attendance',array("meeting_id" => $data['meeting']->id),'meeting_attendance.*, user.*, meeting_attendance.user as meeting_attendance_user','','',$join_att);
            $data['mom_custom_invitee'] = $this->mfiles_upload->get_db('id','asc','meeting_attendance',array('user'=>0,"meeting_id" => $data['meeting']->id),'','');
            $i = 1; $mom_array['meeting_attendance_no'] = array(); $mom_array['meeting_attendance_no']['number'] = 0;
            if($data['mom_attendants']){
                foreach($data['mom_attendants'] as $fa){
                    $mom_array['meeting_attendance_no']['meeting_attendance_no#'.$i] = $i;
                    $mom_array['meeting_attendance_no']['meeting_attendance_group#'.$i] = $fa->group;
                    $mom_array['meeting_attendance_no']['meeting_attendance_full_name#'.$i] = $fa->full_name;
                    $mom_array['meeting_attendance_no']['meeting_attendance_jabatan#'.$i] = $fa->jabatan;
                    $mom_array['meeting_attendance_no']['meeting_attendance_phone_number#'.$i] = $fa->phone_number;
                    $i++;
                }
                $mom_array['meeting_attendance_no']['number'] = $i-1;
            }
            if($data['mom_custom_invitee']){
                foreach($data['mom_custom_invitee'] as $fa){
                    $mom_array['meeting_attendance_no']['meeting_attendance_no#'.$i] = $i;
                    $mom_array['meeting_attendance_no']['meeting_attendance_group#'.$i] = $fa->custom_invitee_unit;
                    $mom_array['meeting_attendance_no']['meeting_attendance_full_name#'.$i] = $fa->custom_invitee;
                    $mom_array['meeting_attendance_no']['meeting_attendance_jabatan#'.$i] = '-';
                    $mom_array['meeting_attendance_no']['meeting_attendance_phone_number#'.$i] = $fa->custom_invitee_phone;
                    $i++;
                }
                $mom_array['meeting_attendance_no']['number'] = $i-1;
            }            
        }
        $data['url'] = $this->mfiles_upload->print_template('list_of_attendance','mom','',$id,$mom_array);
        $data['status'] = 1;
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    public function delete_custom_invitee(){
        $id = $this->input->post('id');
        $this->mfiles_upload->delete_db_where(array('id'=>$id),'meeting_attendance');
        $data['status'] = 1;
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }
}