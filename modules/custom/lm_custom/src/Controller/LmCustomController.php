<?php

namespace Drupal\lm_custom\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\lm_custom\Entity\Health;
use Drupal\lm_custom\Entity\Work;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
/* use PhpParser\Node\Expr\Cast\Unset_; */
use Drupal\lm_custom\Entity\Agreement;
use Drupal\lm_custom\Entity\Company;
//use Drupal\Core\CronInterface;

/**
 * Provides route responses for the Example module.
 */
class LmCustomController extends ControllerBase {
  public function viewDepartments() {
		
		if(\Drupal::currentUser()->isAnonymous()){
			var_dump(\Drupal::currentUser()->isAnonymous());die;
      return new RedirectResponse(Url::fromRoute('user.login')->toString());
    }
    
    // Deny any page caching on the current request.
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    $role = $user->get('roles')->getValue()[0]['target_id'];
    $companies = [];
    if($role == 'su admin'){
      $cid = $user->get('field_company')->getString();
      $company = Company::load($cid);
      $cp_name = $company->get('company_name')->getString();

      $companies[$cid] = ['cid' => $cid, 'cp_name' => $cp_name];

      $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('field_company',$cid)
      ->execute();
    }
    elseif($role == 'administrator'){
      $companies_array = Company::loadMultiple();

      foreach($companies_array as $company){
        $cp_name = $company->get('company_name')->getString();
        $cid = $company->Id();
        $companies[$cid] = ['cid' => $cid, 'cp_name' => $cp_name];
      }

      $term_ids = \Drupal::entityQuery('taxonomy_term')
      ->execute();
    }
	
    $terms = Term::loadMultiple($term_ids);
    $departments = [];
    foreach ($terms as $term){
      $tid = $term->id();
      $cp_id = $term->get('field_company')->getString();
      
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$tid)
        ->execute();

      $works = Work::loadMultiple($work_ids);

      $workdays = [];
      $workhours = [];
      $work_start_time = [];
      $sick_start_time = [];

      foreach ($works as $work){
        $workdays[] = $work->get('workdays')->getString();
        $workhours[] = $work->get('workhours')->getString();
        $work_start_time[] = date("H:i", strtotime($work->get('work_start_time')->getString()));
        $sick_start_time[] = date("H:i", strtotime($work->get('sick_start_time')->getString()));
      }

      $departments[$tid] = [
        'term_name' => $term->getName(),
        'wid' => array_values($work_ids),
        'workdays' => $workdays,
        'workhours' => $workhours,
        'work_start_time' => $work_start_time,
        'sick_start_time' => $sick_start_time,
        'cid' => $cp_id
      ];
    }

    $template = 'departments_page';
    return [
      '#theme' => $template,
      '#cache' => [
        'max-age' => 0
      ],
      '#base_url' => $base_url,
      '#departments' => $departments,
      '#companies' => $companies,
      '#attached' => [
        'library' => [
          'lm_custom/departments-page'
        ]
      ]
    ];
  }

  public function addDepartment() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $dpt_name = $drupal_request->request->get('dpt_name');
    $cp_id = $drupal_request->request->get('cp_id');
    $works = $drupal_request->request->get('works');

    $term =Term::create([
      'name' => $dpt_name,
      'vid' => 'departments',
      'field_company' => $cp_id,
    ]);
    $term->save();

    /* $work_days = [
      'monday' => 'Monday',
      'tuesday' => 'Tuesday',
      'wednesday' => 'Wednesday',
      'thursday' => 'Thursday',
      'friday' => 'Friday'
    ];

    foreach ($work_days as $work_day){
      $work = Work::create([
        'workdays' => $work_day,
        'workhours' => '7.30',
        'work_start_time' => '9:00 AM',
        'sick_start_time' => '10:00 AM',
        'department_id' => $term->id(),
      ]);
      $work->save();
    } */

    foreach ($works as $work){
      $work = Work::create([
        'workdays' => $work['day'],
        'workhours' => $work['hour'],
        'work_start_time' => $work['wk_st'],
        'sick_start_time' => $work['sk_st'],
        'department_id' => $term->id(),
      ]);
      $work->save();
    }


    return new JsonResponse([
      'result' => 'OK',
      'tid' => $term->id(),
    ]);
  }

  public function editDepartment() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $dpt_name = $drupal_request->request->get('dpt_name');
    $tid = $drupal_request->request->get('tid');
    $cp_id = $drupal_request->request->get('cp_id');
    $works = $drupal_request->request->get('works');

    $term = Term::load($tid);
    $term->setName($dpt_name);
    $term->set('field_company', [['target_id' => $cp_id ]]);
    $term->save();

    foreach ($works as $work){
      $work_entity = Work::load($work['wid']);
      $work_entity->set('workdays', [['value' => $work['day']]]);
      $work_entity->set('workhours', [['value' => $work['hour']]]);
      $work_entity->set('work_start_time', [['value' => $work['wk_st']]]);
      $work_entity->set('sick_start_time', [['value' => $work['sk_st']]]);
      $work_entity->save();
    }

    return new JsonResponse([
      'result' => 'OK',
    ]);
  }

  public function deleteDepartment() {
    $drupal_request = \Drupal::request();
    $tid = $drupal_request->request->get('tid');

    $term = Term::load($tid);
    $uids = \Drupal::entityQuery('user')
    ->condition('field_department',$tid)
    ->execute();
    if($uids){
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }
    else{
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$tid)
        ->execute();

      $works = Work::loadMultiple($work_ids);
      foreach ($works as $work){
        $work->delete();
      }
      
      $term->delete();

      return new JsonResponse([
        'result' => 'OK',
      ]);
    }
  }

  public function viewMembers() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    global $base_url;
    $current_uid = \Drupal::currentUser()->id();
    $current_user = User::load($current_uid);
    $role = $current_user->get('roles')->getValue()[0]['target_id'];
    $cp_id = $current_user->get('field_company')->getString();
    if($role == 'administrator'){
      $user_ids = \Drupal::entityQuery('user')
        ->condition('uid',0,'<>')
        ->condition('roles', 'administrator', '<>')
        ->execute();
    }
    elseif(strpos($role, 'su admin') !== false){
      $user_ids = \Drupal::entityQuery('user')
        ->condition('uid',[0, 1],'NOT IN')
        ->condition('field_company',$cp_id)
        ->execute();
    }
    elseif($role == 'chief'){
      $current_user_dpt = $current_user->get('field_department')->getValue();
      $current_dpts = [];
      foreach ($current_user_dpt as $dpt){
        $current_dpts[] = $dpt['target_id'];
      }
      $user_ids = \Drupal::entityQuery('user')
        ->condition('field_department',$current_dpts,'IN')
        ->condition('field_company',$cp_id)
        ->execute();
    }

    $users = User::loadMultiple($user_ids);
    $members = [];
    $companies = [];

    $companies_array = Company::loadMultiple();

    foreach($companies_array as $company){
      $company_id = $company->Id();
      $company_name = $company->get('company_name')->getString();
      $companies[$company_id] = [
        'cp_id' => $company_id,
        'cp_name' => $company_name,
      ];
    }

    if($users){
        foreach ($users as $user){
          $uid = $user->id();
          $user_roles = $user->getRoles(TRUE);

          $email = $user->getEmail();
          $id_number = $user->get('field_id_number')->getString();
          $dpt_ids = $user->get('field_department')->getValue();
          $dpt_array = [];
          foreach ($dpt_ids as $dpt_id){
            $dpt_array[] = $dpt_id['target_id'];
          }

          $is_within_law = $user->get('field_within_law')->getString();
          $receive_email = [];
          if($is_within_law){
            $receive_all_mail = $user->get('field_receive_all_mail')->getString();
            $receive_warning_mail = $user->get('field_receive_warning_mail')->getString();
            $receive_email[$uid] = [
              'is_within_law' => $is_within_law,
              'receive_all' => $receive_all_mail,
              'receive_warning' => $receive_warning_mail
            ];
          }

          $is_special_hours = (int)$user->get('field_special_hours')->getString();

          $special_hours = [];
          $works = [];
          if($is_special_hours){
            $special_hour_ids = \Drupal::entityQuery('lm_custom_work')
              ->condition('user_id', $uid)
              ->execute();
            $special_hour_entities = Work::loadMultiple($special_hour_ids);
            $workdays = [];
            $workhours = [];
            $work_start_time = [];
            $sick_start_time = [];

            foreach ($special_hour_entities as $special_hour){
              $workdays[] = $special_hour->get('workdays')->getString();
              $workhours[] = $special_hour->get('workhours')->getString();
              $work_start_time[] = date("H:i", strtotime($special_hour->get('work_start_time')->getString()));
              $sick_start_time[] = date("H:i", strtotime($special_hour->get('sick_start_time')->getString()));
            }
            $special_hours[$uid] = [
              'wid' => array_values($special_hour_ids),
              'workdays' => $workdays,
              'workhours' => $workhours,
              'work_start_time' => $work_start_time,
              'sick_start_time' => $sick_start_time
            ];
          }

          $terms = Term::loadMultiple($dpt_array);
          foreach ($terms as $term){
            $tid = $term->id();
            $work_ids = \Drupal::entityQuery('lm_custom_work')
            ->condition('department_id',$tid)
            ->execute();

            $works_entity = Work::loadMultiple($work_ids);

            $workdays = [];
            $workhours = [];
            $work_start_time = [];
            $sick_start_time = [];

            foreach ($works_entity as $work){
              $workdays[] = $work->get('workdays')->getString();
              $workhours[] = $work->get('workhours')->getString();
              $work_start_time[] = date("H:i", strtotime($work->get('work_start_time')->getString()));
              $sick_start_time[] = date("H:i", strtotime($work->get('sick_start_time')->getString()));
            }

            $works[$tid] = [
              'term_name' => $term->getName(),
              'wid' => array_values($work_ids),
              'workdays' => $workdays,
              'workhours' => $workhours,
              'work_start_time' => $work_start_time,
              'sick_start_time' => $sick_start_time
            ];
          }

          $member_role = '';
          if(in_array('su admin', $user_roles)){
            $member_role = 'su admin';
          }
          elseif (in_array('chief', $user_roles)){
            $member_role = 'chief';
          }
          elseif (in_array('staff', $user_roles)){
            $member_role = 'staff';
          }


          $members[$uid] = [
            'uid' => $uid,
            'name' => $user->getAccountName(),
            'role' => $member_role,
            'email'=> $email,
            'id_number' => $id_number,
            'department' => $dpt_array,
            'receive_email' => $receive_email,
            'special_hours' => $special_hours,
            'works' => $works,
          ];
        }
    }

    $roles = user_role_names(TRUE);
    unset($roles['authenticated']);
    unset($roles['administrator']);

	if($role == 'administrator'){
		$term_ids = \Drupal::entityQuery('taxonomy_term')		
		->execute();
	}
	elseif($role == 'su admin'){
		$term_ids = \Drupal::entityQuery('taxonomy_term')
		->condition('field_company', $cp_id)
		->execute();
	}
	
	$departments = [];
	
	if($term_ids){
		$terms = Term::loadMultiple($term_ids);
		foreach ($terms as $term){
		  $tid = $term->id();
		  $cp_id = $term->get('field_company')->getString();
		  $departments[$tid] = [
			'dpt_name' => $term->getName(), 
			'cid' => $cp_id 
			];
		}
	}
    $all_works = self::getWorks();
	

    $template = 'members_page';

    return [
      '#theme' => $template,
      '#cache' => [
        'max-age' => 0
      ],
      '#base_url' => $base_url,
      '#members' => $members,
      '#roles' => $roles,
      '#departments' => $departments,
      '#companies' => $companies,
      '#works' => $all_works,
      '#attached' => [
        'library' => [
          'lm_custom/departments-page'
        ]
      ]
    ];
  }

  public function addMember() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $member_name = $drupal_request->request->get('member_name');
    $member_email = $drupal_request->request->get('member_email');
    $member_id_number = $drupal_request->request->get('member_id_number');
    $member_cp = $drupal_request->request->get('member_cp');
    $member_dpt = $drupal_request->request->get('member_dpt');
    $member_role = $drupal_request->request->get('member_role');
    $is_within_law = $drupal_request->request->get('is_within_law');
    $receive_all = $drupal_request->request->get('receive_all');
    $receive_warning = $drupal_request->request->get('receive_warning');
    $special_hours = $drupal_request->request->get('special_hours');

    $duplicateUser = \Drupal::entityQuery('user')
      ->condition('field_id_number', $member_id_number)
      ->condition('field_company', $member_cp)
      ->execute();

    if($duplicateUser){
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }
    else{
      $user = User::create([

        'name' => $member_name,

        'mail' => $member_email,

        'pass' => 'member',

        'status' => 1,

        'roles' => [strtolower($member_role)],

        'user_picture' => [],

        'field_id_number' => $member_id_number,

        'field_company' => $member_cp,

        'field_department' => $member_dpt,

        'timezone'=> 'Europe/Berlin'

      ]);

      if($is_within_law){
        $user->set('field_within_law', [['value' => $is_within_law]]);
        if($receive_all){
          $user->set('field_receive_all_mail', [['value' => $receive_all]]);
        }
        if($receive_warning){
          $user->set('field_receive_warning_mail', [['value' => $receive_warning]]);
        }
      }

      if($special_hours){
        $user->set('field_special_hours', [['value' => 1]]);
        $user->save();

        foreach ($special_hours as $work){
          $work = Work::create([
            'workdays' => $work['day'],
            'workhours' => $work['hour'],
            'work_start_time' => $work['wk_st'],
            'sick_start_time' => $work['sk_st'],
            'user_id' => $user->id(),
          ]);
          $work->save();
        }
      }
      else{
        $user->set('field_special_hours', [['value' => 0]]);
        $user->save();
      }

      return new JsonResponse([
        'result' => 'OK',
        'username' => $user->getAccountName(),
      ]);
    }
  }


  public function editMember() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $user_id = (int)$drupal_request->request->get('user_id');
    $member_name = $drupal_request->request->get('member_name');
    $member_email = $drupal_request->request->get('member_email');
    $member_id_number = $drupal_request->request->get('member_id_number');
    $member_cp = $drupal_request->request->get('member_cp');
    $member_dpt = $drupal_request->request->get('member_dpt');
    $member_role = $drupal_request->request->get('member_role');
    $is_within_law = $drupal_request->request->get('is_within_law');
    $receive_all = $drupal_request->request->get('receive_all');
    $receive_warning = $drupal_request->request->get('receive_warning');
    $special_hours = $drupal_request->request->get('special_hours');

    if($user_id){
      $user = User::load($user_id);
      $user->setUsername($member_name);
      $user->setEmail($member_email);
      $user->set('field_id_number',[['value' => $member_id_number]]);
      $user->set('roles', [['target_id' => $member_role]]);

      if($is_within_law){
        $user->set('field_within_law', [['value' => $is_within_law]]);
        if($receive_all){
          $user->set('field_receive_all_mail', [['value' => $receive_all]]);
        }
        if($receive_warning){
          $user->set('field_receive_warning_mail', [['value' => $receive_warning]]);
        }
      }

      if($special_hours){
        $user->set('field_special_hours', [['value' => 1]]);

        $special_hours_ids = \Drupal::entityQuery('lm_custom_work')
          ->condition('user_id', $user_id)
          ->execute();

        if($special_hours_ids){
          $special_hours_entity = Work::loadMultiple($special_hours_ids);
          foreach ($special_hours_entity as $work){
            $work_entity = Work::load($work['wid']);
            //$work_entity->set('workdays', [['value' => $work['day']]]);
            $work_entity->set('workhours', [['value' => $work['hour']]]);
            $work_entity->set('work_start_time', [['value' => $work['wk_st']]]);
            $work_entity->set('sick_start_time', [['value' => $work['sk_st']]]);
            $work_entity->save();
          }
        }
        else{
          $user->set('field_special_hours', [['value' => 1]]);

          foreach ($special_hours as $work){
            $work = Work::create([
              'workdays' => $work['day'],
              'workhours' => $work['hour'],
              'work_start_time' => $work['wk_st'],
              'sick_start_time' => $work['sk_st'],
              'user_id' => $user_id,
            ]);
            $work->save();
          }
        }
      }
      else{
        $special_hours_ids = \Drupal::entityQuery('lm_custom_work')
          ->condition('user_id', $user_id)
          ->execute();

        if($special_hours_ids){
          $special_hours = Work::loadMultiple($special_hours_ids);
          foreach ($special_hours as $work){
            $work->delete();
          }
        }
        $user->set('field_special_hours', [['value' => 0]]);
      }

      $user->set('field_company', [['target_id' => $member_cp]]);

      foreach ($member_dpt as $dpt) {
        $user->set('field_department',[['target_id' => $dpt]]);
      }
      $user->save();

      return new JsonResponse([
        'result' => 'OK',
        'username' => $user->getAccountName(),
      ]);
    }
    else{
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }
  }

  public function deleteMember() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $uid = $drupal_request->request->get('user_id');

    $username = '';

    $health_data = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id',$uid)
      ->execute();

    /*$health_entity = Health::loadMultiple($health_data);
    foreach ($health_entity as $health){
      $health->delete();
    } */
    if($health_data){
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }
    else{
      $user = User::load($uid);
      $username = $user->getAccountName();
      $user_role = $user->get('roles')->getValue[0]['target_id'];
	
      $is_special_hours = (int)$user->get('field_special_hours')->getString();
	  $cid = $user->get('field_company')->getString();
	  
      if($is_special_hours){
        $special_hours_ids = \Drupal::entityQuery('lm_custom_work')
          ->condition('user_id', $uid)
          ->execute();

        $special_hours = Work::loadMultiple($special_hours_ids);
        foreach ($special_hours as $work){
          $work->delete();
        }

      }
      
      if($user_role == 'su admin' && $cid){
		  $company = Company::load($cid);
		  $company->delete();
	  }
	  
	  $user->delete();

      return new JsonResponse([
        'result' => 'OK',
        'username' => $username
      ]);
    }
  }


  public function viewHealth() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $uid = \Drupal::currentUser()->id();
    $time_stamp = strtotime(date("Y-m-d"));
    //$date = date("Y-m-d",$time);

    $user = User::load($uid);
    $roles = $user->get('roles')->getValue()[0]['target_id'];
    $user_ids = [];
    if($roles == 'administrator'){
      $user_ids = \Drupal::entityQuery('user')
        ->condition('uid',['0','1'],'NOT IN')
        ->execute();
    }
    elseif(strpos($roles, 'su admin') !== false){
      $cp_id = $user->get('field_company')->getString();
      $user_ids = \Drupal::entityQuery('user')
        ->condition('uid',['0', '1'],'NOT IN')
        ->condition('field_company',$cp_id)
        ->execute();
    }
    elseif($roles == 'chief'){
      $current_user_dpt = $user->get('field_department')->getValue();
      $current_dpts = [];
      foreach ($current_user_dpt as $dpt){
        $current_dpts[] = $dpt['target_id'];
      }
      $user_ids = \Drupal::entityQuery('user')
        ->condition('field_department',$current_dpts,'IN')
        ->execute();
    }
    else{
      $user_ids[] = $uid;
    }

    $users = User::loadMultiple($user_ids);
    $members = [];
    $ag_user = [];
    foreach ($users as $user_entity){
      $user_id = $user_entity->id();
      $user_name = $user_entity->getAccountName();
      $user_roles = $user_entity->getRoles(TRUE);
      $id_number = $user_entity->get('field_id_number')->getString();
      $agreement_ids = \Drupal::entityQuery('lm_custom_agreement')
        ->condition('user_id',$user_id)
        ->condition('ag_status','enabled')
        ->execute();

      if($agreement_ids){
        foreach ($agreement_ids as $ag_id){
          $agreement = Agreement::load($ag_id);
          $ag_user[$ag_id] = [
            'ag_day_from' => $agreement->get('day_from')->getString(),
            'ag_day_to' => $agreement->get('day_to')->getString(),
            'ag_time_from' => $agreement->get('time_from')->getString(),
            'ag_time_to' => $agreement->get('time_to')->getString()
          ];
        }

      }

      $member_role = '';
      if(in_array('administrator', $user_roles)){
        $member_role = 'administrator';
      }
      elseif (in_array('su admin', $user_roles)){
        $member_role = 'su admin';
      }
      elseif (in_array('chief', $user_roles)){
        $member_role = 'chief';
      }
      elseif (in_array('staff', $user_roles)){
        $member_role = 'staff';
      }

      $members[$user_id] = [
        'uid' => $user_id,
        'name' => $user_name,
        'role' => $member_role,
        'id_number' => $id_number,
        'ag_user' => $ag_user
      ];
    }

    $health_entity = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id', $uid)
      ->condition('sickdates', $time_stamp)
      ->condition('approval_status', 'approved')
      ->condition('status','sick')
      ->execute();

    $health_status = '';
    if($health_entity){
      $health_status = 'sick';
    }

    $template = 'health_page';

    return [
      '#theme' => $template,
      '#cache' => [
        'max-age' => 0
      ],
      '#base_url' => $base_url,
      '#health_status' => $health_status,
      '#roles' => $roles,
      '#members' => $members,
      '#attached' => [
        'library' => [
          'lm_custom/departments-page',
        ],
      ],
    ];
  }

  public function addHealth() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $current_status = $drupal_request->request->get('health_status');
    $uid = \Drupal::currentUser()->id();
    $current_time = date("H:i");
    $time_stamp = strtotime(date("Y-m-d"));
    $prev_day_time = strtotime(date("Y-m-d").' -1 day');
    //$date = date("Y-m-d",$time);

    $prev_day_health_id = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id', $uid)
      ->condition('sickdates', $prev_day_time)
      ->condition('approval_status', 'approved')
      ->execute();

    $prev_day_health_id = implode('', $prev_day_health_id);

    if($prev_day_health_id){
      $prev_day_health = Health::load($prev_day_health_id);
      $prev_day_status = $prev_day_health->get('status')->getString();
    }

    $health_id = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id', $uid)
      ->condition('approval_status', 'approved')
      ->condition('sickdates', $time_stamp)
      ->execute();

    $health_id = implode('', $health_id);

    if($health_id){
      $health_entity = Health::load($health_id);
      $health_status = $health_entity->get('status')->getString();
      $hits = $health_entity->get('health_hits')->getString();
      $sicktime = $health_entity->get('sicktime')->getString();
    }

    $user = User::load($uid);
    $role = $user->get('roles')->getValue()[0]['target_id'];
    $is_special_hours = (int)$user->get('field_special_hours')->getString();
    $dpt_ids = $user->get('field_department')->getString();
    $dpt_id = explode(',', $dpt_ids)[0];

    $agreement_ids = \Drupal::entityQuery('lm_custom_agreement')
      ->condition('user_id',$uid)
      ->condition('ag_status','enabled')
      ->execute();

    $ag_sick_start_time = '';
    $ag_time_from = '';
    $ag_time_to = '';
    $ag_id = '';
    $inAgreement = false;
    if($agreement_ids){
      foreach ($agreement_ids as $agreement_id){
        $agreement = Agreement::load($agreement_id);
        $dt_from = $agreement->get('day_from')->getString();
        $dt_to = $agreement->get('day_to')->getString();
        if($dt_to){
          $start_date = new \DateTime($dt_from);
          $end_date = new \DateTime($dt_to);
          $end_date->modify('+1 day');

          $daterange = new \DatePeriod($start_date, new \DateInterval('P1D'), $end_date);

          foreach ($daterange as $date){
            if(date('Y-m-d') == $date->format("Y-m-d")){
              $inAgreement = true;
              $ag_id = $agreement_id;
              $ag_sick_start_time = $agreement->get('sick_start_time')->getString();
              $ag_time_from = $agreement->get('time_from')->getString();
              $ag_time_to = $agreement->get('time_to')->getString();
            }
          }
        }
        else{
          if(date('Y-m-d') == $dt_from){
            $inAgreement = true;
            $ag_id = $agreement_id;
            $ag_sick_start_time = $agreement->get('sick_start_time')->getString();
            $ag_time_from = $agreement->get('time_from')->getString();
            $ag_time_to = $agreement->get('time_to')->getString();
          }
        }
      }
    }

    if($is_special_hours){
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('user_id',$uid)
        ->execute();
    }
    else{
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$dpt_id)
        ->execute();
    }
    $works_entity = Work::loadMultiple($work_ids);

    $workday = '';
    $workhour = '';
    $work_start_time = '';
    $sick_start_time = '';

    foreach ($works_entity as $work){
      $workday = $work->get('workdays')->getString();

      if(date('l') == $workday){
        $work_start_time = date("H:i", strtotime($work->get('work_start_time')->getString()));
        $sick_start_time = date("H:i", strtotime($work->get('sick_start_time')->getString()));
        $workhour = $work->get('workhours')->getString();
      }
    }
    $hour = explode('.', $workhour)[0];
    $minutes = explode('.', $workhour)[1];
    $work_end_time = date('H:i', strtotime($work_start_time.' +'.$hour.' hours '.$minutes.' minutes'));

    if($inAgreement){
      $sick_start_time = $ag_sick_start_time;
    }

    $isFullTimeSick = false;
    $sickhours = '';
    if(strtotime($current_time) < strtotime($sick_start_time)){
      $isFullTimeSick = true;
      $sickhours = $workhour;
    }
    else{
      $isFullTimeSick = false;
      if($inAgreement){
        $startTime1 = new \DateTime($work_start_time);
        $endTime1 = new \DateTime($ag_time_from);
        $duration1 = $startTime1->diff($endTime1); //$duration is a DateInterval object
        $before_hr = $duration1->format("%H:%I:%S");

        $startTime2 = new \DateTime($ag_time_to);
        $endTime2 = new \DateTime($work_end_time);
        $duration2 = $startTime2->diff($endTime2);
        $after_hr = $duration2->format("%H:%I:%S");

        $secs = strtotime($after_hr) - strtotime('00:00:00');
        $sickhours = date('H:i',strtotime($before_hr)+$secs);
      }
      else{
        $diff = strtotime($work_end_time) - strtotime($current_time);
        if($diff > 0){
          $sickhours = date('H.i',$diff);//part time sick hours
        }
        else{
          $sickhours = '';
        }
      }
    }
    /*  $isSickDateExist = false;
    foreach ($health_entity as $entity){
      $sickdate_stamp = $entity->get('sickdates')->getString();
      $sickdate_date = date("Y-m-d",$sickdate_stamp);
      if($date == $sickdate_date){
        $isSickDateExist = true;
        break;
      }
    } */
    $sickdays = '1 day';
    if($prev_day_status == 'sick' && $current_status == 'healthy' && $isFullTimeSick){
      $prev_date = date('Y-m-d',$prev_day_time);
      $prev_day = date('l',$prev_day_time);
      $sickdays = '2 days';
    }
    elseif ($prev_day_status == 'sick' && $current_status == 'healthy' && !$isFullTimeSick){
      $prev_date = date('Y-m-d',$prev_day_time);
      $prev_day = date('l',$prev_day_time);
      $sickdays = '1 day';
    }

    //mail
    $chief_id = \Drupal::entityQuery('user')
      ->condition('roles','chief')
      ->condition('field_department',$dpt_id)
      ->execute();

    if($chief_id){
      foreach ($chief_id as $cid){
        $chief = User::load($cid);
        $chief_mail = $chief->getEmail();
      }
    }
    else{
      $admin_id = \Drupal::entityQuery('user')
        ->condition('roles','su admin')
        ->execute();

      if($admin_id){
        foreach ($admin_id as $aid){
          $admin = User::load($aid);
          $admin_mail = $admin->getEmail();
        }
      }
    }

    $username = \Drupal::currentUser()->getAccountName();
    $message = 'The user status of "' .$username . '" is '.$current_status;
    $subject = 'User status: '.$current_status.' added';
    if($chief_mail){
      $to = $chief_mail;
    }
    else{
      $to = $admin_mail;
    }

    if(date('l') !== 'Saturday' && date('l') !== 'Sunday'){
      //if($isSickDateExist){
      if($health_id){
        if($prev_day_health_id && $prev_day_status == 'sick' && $hits == 3){
          return new JsonResponse([
            'result' => 'Not OK',
          ]);
        }
        elseif($prev_day_health_id && $prev_day_status == 'healthy' && $hits == 2){
          return new JsonResponse([
            'result' => 'Not OK',
          ]);
        }
        else{
          if($sickhours > 0 && (($hits < 3 && $prev_day_status == 'sick') || ($hits < 2 && $prev_day_status == 'healthy') || ($hits < 2 && $prev_day_status == ''))){
            $health_entity->set('status', [[ 'value' => $current_status ]]);
            $health_entity->set('sicktime', [[ 'value' => $current_time ]]);
            $health_entity->set('health_hits', [[ 'value' => $hits+1 ]]);

            if($current_status == 'sick'){
              if($isFullTimeSick){
                $health_entity->set('full_time_sickhours', [[ 'value' => $sickhours ]]);
              }
              else{
                $health_entity->set('part_time_sickhours', [[ 'value' => $sickhours ]]);
              }
            }
            else{
              if(($prev_day_status == 'sick' && !$isFullTimeSick && $hits == 2) || ($prev_day_status == 'healthy' && !$isFullTimeSick && $hits == 1)){
                $sickhours = date('H.i',strtotime($current_time) - strtotime($sicktime));
                $health_entity->set('part_time_sickhours', [[ 'value' => $sickhours ]]);
              }
            }
            $health_entity->save();

            if($role == 'staff'){
              $email = self::sendMail($username, $message, $to, $subject);
            }

            return new JsonResponse([
              'result' => 'OK',
              'current_time' => $current_time,
              'current_date' => date('Y-m-d'),
              'today' => date('l'),
              'prev_day' => $prev_day,
              'prev_date' => $prev_date,
              'sickdays' => $sickdays
            ]);
          }
          else{
            return new JsonResponse([
              'result' => 'Not OK',
            ]);
          }
        }
      }
      else{
        if($current_status == 'sick'){
          $health = Health::create([
            'status' => $current_status,
            'sickdates' => $time_stamp,
            'sicktime' => $current_time,
            'approval_status' => 'approved',
            'health_hits' => 1,
            'user_id' => $uid,
          ]);
          if($isFullTimeSick){
            $health->set('full_time_sickhours', [[ 'value' => $sickhours ]]);
          }
          else{
            $health->set('part_time_sickhours', [[ 'value' => $sickhours ]]);
          }
          if($inAgreement){
            $health->set('agreement_id', $ag_id);
          }
          $health->save();
        }
        else{
          $health = Health::create([
            'status' => $current_status,
            'sickdates' => $time_stamp,
            'sicktime' => $current_time,
            'approval_status' => 'approved',
            'health_hits' => 1,
            'user_id' => $uid,
          ]);
          $health->save();
        }

        if($role == 'staff'){
          $email = self::sendMail($username, $message, $to, $subject);
        }

        return new JsonResponse([
          'result' => 'OK',
          'current_time' => $current_time,
          'current_date' => date('Y-m-d'),
          'today' => date('l'),
          'prev_day' => $prev_day,
          'prev_date' => $prev_date,
          'sickdays' => $sickdays
        ]);
      }
    }
    else{
      return new JsonResponse([
        'result' => 'weekend',
      ]);
    }
  }

  public function addSingleSickDay() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $drupal_request = \Drupal::request();
    $sickday_dt = $drupal_request->request->get('sickday_dt');
    $sickday_time = $drupal_request->request->get('sickday_time');
    $health_status = $drupal_request->request->get('health_status');
    $sd_day = $drupal_request->request->get('sd_day');
    $uid = \Drupal::currentUser()->id();


    /* $dates = [];
    foreach($daterange as $date){
      array_push($dates, ['day' => $date->format('l'), 'date'=>$date->format('Y-m-d')]);

    }


    foreach($dates as $key => $date){
      $time_stamp = strtotime($date['date']);
      if($dates[$key]['day'] == 'Friday' && $dates[$key+3]['day'] == 'Monday'){
        $health = Health::create([
          'status' => $health_status,
          'sickdates' => $time_stamp,
          'user_id' => $uid,
        ]);
        $health->save();
      }
    } */

    $user = User::load($uid);
    $role = $user->get('roles')->getValue()[0]['target_id'];
    $is_special_hours = (int)$user->get('field_special_hours')->getString();
    $dpt_ids = $user->get('field_department')->getString();
    $dpt_id = explode(',', $dpt_ids)[0];

    if($is_special_hours){
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('user_id',$uid)
        ->execute();
    }
    else{
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$dpt_id)
        ->execute();
    }

    $works_entity = Work::loadMultiple($work_ids);

    $workday = '';
    $workhour = '';
    $work_start_time = '';
    $sick_start_time = '';

    foreach ($works_entity as $work){
      $workday = $work->get('workdays')->getString();
      if($sd_day == $workday){
        $sick_start_time = date("H:i", strtotime($work->get('sick_start_time')->getString()));
        $work_start_time = date("H:i", strtotime($work->get('work_start_time')->getString()));
        $workhour = $work->get('workhours')->getString();
      }
    }

    $hour = explode('.', $workhour)[0];
    $minutes = explode('.', $workhour)[1];
    $work_end_time = date('H:i', strtotime($work_start_time.' +'.$hour.' hours '.$minutes.' minutes'));

    $isFullTimeSick = false;
    $sickhours = '';
    if(strtotime($sickday_time) < strtotime($sick_start_time)){
      $isFullTimeSick = true;
      $sickhours = $workhour;
    }
    else{
      $isFullTimeSick = false;
      $diff = strtotime($work_end_time) - strtotime($sickday_time);
      if($diff > 0){
        $sickhours = date('H.i',$diff);//part time sick hours
      }
      else{
        $sickhours = '';
      }
    }


    //mail
    $chief_id = \Drupal::entityQuery('user')
      ->condition('roles','chief')
      ->condition('field_department',$dpt_id)
      ->execute();

    if($chief_id){
      foreach ($chief_id as $cid){
        $chief = User::load($cid);
        $chief_mail = $chief->getEmail();
      }
    }
    else{
      $admin_id = \Drupal::entityQuery('user')
        ->condition('roles','su admin')
        ->execute();

      if($admin_id){
        foreach ($admin_id as $aid){
          $admin = User::load($aid);
          $admin_mail = $admin->getEmail();
        }
      }
    }

    $username = \Drupal::currentUser()->getAccountName();
    $message = 'User "' .$username . '" has requested to add forgotten sickdays. Please approve the request using this link: '.$base_url.'/approve/'.$uid;
    $subject = 'Request to add forgotten sickdays';
    if($chief_mail){
      $to = $chief_mail;
    }
    else{
      $to = $admin_mail;
    }

    if($role == 'staff'){
      $email = self::sendMail($username, $message, $to, $subject);
    }

    $health_id = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id', $uid)
      ->condition('approval_status', 'approved')
      ->condition('sickdates', strtotime($sickday_dt))
      ->execute();

    $health_id = implode('', $health_id);

    if($health_id){
      $health_entity = Health::load($health_id);
      $health_entity->set('status', [[ 'value' => $health_status ]]);
      $health_entity->set('sicktime', [[ 'value' => $sickday_time ]]);

      if($isFullTimeSick){
        $health_entity->set('full_time_sickhours', [[ 'value' => $sickhours ]]);
        $health_entity->set('part_time_sickhours', [[ 'value' => '' ]]);
      }
      else{
        $health_entity->set('part_time_sickhours', [[ 'value' => $sickhours ]]);
        $health_entity->set('full_time_sickhours', [[ 'value' => '' ]]);
      }

      if($role == 'staff'){
        $health_entity->set('approval_status', 'unapproved');
      }
      else{
        $health_entity->set('approval_status', 'approved');
      }

      $health_entity->save();
    }
    else{
      $health = Health::create([
        'status' => $health_status,
        'sickdates' => strtotime($sickday_dt),
        'sicktime' => $sickday_time,
        'user_id' => $uid,
      ]);
      if($isFullTimeSick){
        $health->set('full_time_sickhours', [[ 'value' => $sickhours ]]);
      }
      else{
        $health->set('part_time_sickhours', [[ 'value' => $sickhours ]]);
      }

      if($role == 'staff'){
        $health->set('approval_status', 'unapproved');
      }
      else{
        $health->set('approval_status', 'approved');
      }

      $health->save();
    }


    $start_day = date('l',strtotime($sickday_dt));
    $current_date = date('Y-m-d');
    $current_day = date('l',strtotime($current_date));
    $current_time = date('H:i');

    return new JsonResponse([
      'result' => 'OK',
      'sickdays' => 1,
      'start_date' => $sickday_dt,
      'start_day' => $start_day,
      'today' => $current_day,
      'current_date' => $current_date,
      'current_time' => $current_time,
    ]);

  }


  public function addPeriodSickdays() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $drupal_request = \Drupal::request();
    $start_dt = $drupal_request->request->get('sick_start_dt');
    $sickday_time = $drupal_request->request->get('sick_start_time');
    $end_dt = $drupal_request->request->get('sick_end_dt');
    $sd_day = $drupal_request->request->get('sd_day');
    $health_status = $drupal_request->request->get('health_status');
    $uid = \Drupal::currentUser()->id();

    $start_date = new \DateTime($start_dt);
    $end_date = new \DateTime($end_dt);
    $end_date->modify('+1 day');

    $daterange = new \DatePeriod($start_date, new \DateInterval('P1D'), $end_date);

    /* $dates = [];
     foreach($daterange as $date){
     array_push($dates, ['day' => $date->format('l'), 'date'=>$date->format('Y-m-d')]);

     }


     foreach($dates as $key => $date){
     $time_stamp = strtotime($date['date']);
     if($dates[$key]['day'] == 'Friday' && $dates[$key+3]['day'] == 'Monday'){
     $health = Health::create([
     'status' => $health_status,
     'sickdates' => $time_stamp,
     'user_id' => $uid,
     ]);
     $health->save();
     }
     } */

    $user = User::load($uid);
    $role = $user->get('roles')->getValue()[0]['target_id'];
    $is_special_hours = (int)$user->get('field_special_hours')->getString();
    $dpt_ids = $user->get('field_department')->getString();
    $dpt_id = explode(',', $dpt_ids)[0];

    if($is_special_hours){
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('user_id',$uid)
        ->execute();
    }
    else{
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$dpt_id)
        ->execute();
    }
    $works_entity = Work::loadMultiple($work_ids);

    $workday = '';
    $workhour = '';
    $work_start_time = '';
    $sick_start_time = '';

    foreach ($works_entity as $work){
      $workday = $work->get('workdays')->getString();

      if($sd_day == $workday){
        $sick_start_time = date("H:i", strtotime($work->get('sick_start_time')->getString()));
        $work_start_time = date("H:i", strtotime($work->get('work_start_time')->getString()));
        $workhour = $work->get('workhours')->getString();
      }
    }

    $hour = explode('.', $workhour)[0];
    $minutes = explode('.', $workhour)[1];
    $work_end_time = date('H:i', strtotime($work_start_time.' +'.$hour.' hours '.$minutes.' minutes'));

    $isFullTimeSick = false;
    $sickhours = '';
    if(strtotime($sickday_time) < strtotime($sick_start_time)){
      $isFullTimeSick = true;
      $sickhours = $workhour;
    }
    else{
      $isFullTimeSick = false;
      $diff = strtotime($work_end_time) - strtotime($sickday_time);
      if($diff > 0){
        $sickhours = date('H.i',$diff);//part time sick hours
      }
      else{
        $sickhours = '';
      }
    }

    //mail
    if($role == 'staff'){
      $chief_id = \Drupal::entityQuery('user')
      ->condition('roles','chief')
      ->condition('field_department',$dpt_id)
      ->execute();

      if($chief_id){
        foreach ($chief_id as $cid){
          $chief = User::load($cid);
          $chief_mail = $chief->getEmail();
        }
      }
      else{
        $admin_id = \Drupal::entityQuery('user')
        ->condition('roles','su admin')
        ->execute();

        if($admin_id){
          foreach ($admin_id as $aid){
            $admin = User::load($aid);
            $admin_mail = $admin->getEmail();
          }
        }
      }

      $username = \Drupal::currentUser()->getAccountName();
      $message = 'User "' .$username . '" has requested to add forgotten sickdays. Please approve the request using this link: '.$base_url.'/approve/'.$uid;
      $subject = 'Request to add forgotten sickdays';
      if($chief_mail){
        $to = $chief_mail;
      }
      else{
        $to = $admin_mail;
      }

      $email = self::sendMail($username, $message, $to, $subject);
    }

    foreach($daterange as $date){
      $time_stamp = strtotime($date->format("Y-m-d"));
      $health_id = \Drupal::entityQuery('lm_custom_health')
        ->condition('user_id', $uid)
        ->condition('approval_status', 'approved')
        ->condition('sickdates', $time_stamp)
        ->execute();

      $health_id = implode('', $health_id);

      if($health_id){
        $health_entity = Health::load($health_id);
        $health_entity->set('status', [[ 'value' => $health_status ]]);
        //$health_entity->set('sickdates', [[ 'value' => $time_stamp ]]);

        if($time_stamp == strtotime($start_dt)){
          $health_entity->set('sicktime', [[ 'value' => $sickday_time ]]);

          if($isFullTimeSick){
            $health_entity->set('full_time_sickhours', [[ 'value' => $sickhours ]]);
            $health_entity->set('part_time_sickhours', [[ 'value' => '' ]]);
          }
          else{
            $health_entity->set('part_time_sickhours', [[ 'value' => $sickhours ]]);
            $health_entity->set('full_time_sickhours', [[ 'value' => '' ]]);
          }
        }
        else{
          $health_entity->set('full_time_sickhours', [[ 'value' => $workhour ]]);
          $health_entity->set('part_time_sickhours', [[ 'value' => '' ]]);
        }

        if($role == 'staff'){
          $health_entity->set('approval_status', 'unapproved');
        }
        else{
          $health_entity->set('approval_status', 'approved');
        }

        $health_entity->save();
      }
      else{
        $health = Health::create([
          'status' => $health_status,
          'sickdates' => $time_stamp,
          'approval_status' => 'unapproved',
          'user_id' => $uid,
        ]);

        if($time_stamp == strtotime($start_dt)){
          $health->set('sicktime', [[ 'value' => $sickday_time ]]);

          if($isFullTimeSick){
            $health->set('full_time_sickhours', [[ 'value' => $sickhours ]]);
          }
          else{
            $health->set('part_time_sickhours', [[ 'value' => $sickhours ]]);
          }
        }
        else{
          $health->set('full_time_sickhours', [[ 'value' => $workhour ]]);
        }

        if($role == 'staff'){
          $health->set('approval_status', 'unapproved');
        }
        else{
          $health->set('approval_status', 'approved');
        }

        $health->save();
      }
    }


    $end = strtotime($end_dt);
    $begin = strtotime($start_dt);
    $datediff = $end - $begin;

    $sickdays = round($datediff / (60 * 60 * 24));
    $start_day = date('l',strtotime($start_dt));
    $end_day = date('l',strtotime($end_dt));
    $current_date = date('Y-m-d');
    $current_day = date('l',strtotime($current_date));
    $current_time = date('H:i');

    //Adding sickdates for end_date
    /* $end_dt_health_id = \Drupal::entityTypeManager()->getStorage('lm_custom_health')
      ->loadByProperties([
        'user_id' => $uid,
        'sickdates' => strtotime($end_dt),
      ]);

    if(!$end_dt_health_id){
      $end_dt_health = Health::create([
        'status' => $health_status,
        'sickdates' => strtotime($end_dt),
        'full_time_sickhours' => $workhour,
        'user_id' => $uid,
      ]);
      $end_dt_health->save();
    } */

    return new JsonResponse([
      'result' => 'OK',
      'sickdays' => $sickdays,
      'start_date' => $start_dt,
      'start_day' => $start_day,
      'end_date' => $end_dt,
      'end_day' => $end_day,
      'today' => $current_day,
      'current_date' => $current_date,
      'current_time' => $current_time,
    ]);

  }


  public function removeSingleSickDay() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $drupal_request = \Drupal::request();
    $sickday_dt = $drupal_request->request->get('sickday_dt');
    $health_status = $drupal_request->request->get('health_status');
    $uid = \Drupal::currentUser()->id();

    $user = User::load($uid);
    $role = $user->get('roles')->getValue()[0]['target_id'];
    $dpt_ids = $user->get('field_department')->getString();
    $dpt_id = explode(',', $dpt_ids)[0];

    $health_id = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id', $uid)
      ->condition('sickdates', strtotime($sickday_dt))
      ->condition('approval_status', 'approved')
      ->condition('status',$health_status)
      ->execute();

    $health_id = implode('', $health_id);

    $start_day = date('l',strtotime($sickday_dt));
    $current_date = date('Y-m-d');
    $current_day = date('l',strtotime($current_date));
    $current_time = date('H:i');

    if($health_id){
      $health_entity = Health::load($health_id);
      if($role == 'staff'){
        $health_entity->set('approval_status', 'remove');
      }
      else{
        $health_entity->set('status', 'healthy');
        $health_entity->set('full_time_sickhours', [[ 'value' => '' ]]);
      }
      $health_entity->save();

      if($role == 'staff'){
        $chief_id = \Drupal::entityQuery('user')
        ->condition('roles','chief')
        ->condition('field_department',$dpt_id)
        ->execute();

        if($chief_id){
          foreach ($chief_id as $cid){
            $chief = User::load($cid);
            $chief_mail = $chief->getEmail();
          }
        }
        else{
          $admin_id = \Drupal::entityQuery('user')
          ->condition('roles','su admin')
          ->execute();

          if($admin_id){
            foreach ($admin_id as $aid){
              $admin = User::load($aid);
              $admin_mail = $admin->getEmail();
            }
          }
        }

        $username = \Drupal::currentUser()->getAccountName();
        $message = 'User "' .$username . '" has requested to remove forgotten sickdays. Please approve the request using this link: '.$base_url.'/remove/'.$uid;
        $subject = 'Request to remove forgotten sickdays';
        if($chief_mail){
          $to = $chief_mail;
        }
        else{
          $to = $admin_mail;
        }

        $email = self::sendMail($username, $message, $to, $subject);
      }


      return new JsonResponse([
        'result' => 'OK',
        'sickdays' => 1,
        'start_date' => $sickday_dt,
        'start_day' => $start_day,
        'today' => $current_day,
        'current_date' => $current_date,
        'current_time' => $current_time,
      ]);
    }
    else{
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }

  }


  public function removePeriodSickdays() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $drupal_request = \Drupal::request();
    $start_dt = $drupal_request->request->get('sick_start_dt');
    $end_dt = $drupal_request->request->get('sick_end_dt');
    $uid = \Drupal::currentUser()->id();

    $user = User::load($uid);
    $role = $user->get('roles')->getValue()[0]['target_id'];
    $dpt_ids = $user->get('field_department')->getString();
    $dpt_id = explode(',', $dpt_ids)[0];

    $begin_stamp = strtotime($start_dt);
    $end_stamp = strtotime($end_dt.' 23:59:59');

    $health_ids = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id', $uid)
      ->condition('sickdates',[$begin_stamp,$end_stamp],'BETWEEN')
      ->condition('approval_status', 'approved')
      ->condition('status','sick')
      ->execute();

    $end = strtotime($end_dt);
    $begin = strtotime($start_dt);
    $datediff = $end - $begin;

    $sickdays = round($datediff / (60 * 60 * 24));
    $start_day = date('l',strtotime($start_dt));
    $end_day = date('l',strtotime($end_dt));
    $current_date = date('Y-m-d');
    $current_day = date('l',strtotime($current_date));
    $current_time = date('H:i');

    if($health_ids){
      foreach ($health_ids as $health_id){
        $health_entity = Health::load($health_id);
        if($role == 'staff'){
          $health_entity->set('approval_status', 'remove');
        }
        else{
          $health_entity->set('status', 'healthy');
          $health_entity->set('full_time_sickhours', [[ 'value' => '' ]]);
        }
        $health_entity->save();
      }

      if($role == 'staff'){
        $chief_id = \Drupal::entityQuery('user')
          ->condition('roles','chief')
          ->condition('field_department',$dpt_id)
          ->execute();

        if($chief_id){
          foreach ($chief_id as $cid){
            $chief = User::load($cid);
            $chief_mail = $chief->getEmail();
          }
        }
        else{
          $admin_id = \Drupal::entityQuery('user')
          ->condition('roles','su admin')
          ->execute();

          if($admin_id){
            foreach ($admin_id as $aid){
              $admin = User::load($aid);
              $admin_mail = $admin->getEmail();
            }
          }
        }

        $username = \Drupal::currentUser()->getAccountName();
        $message = 'User "' .$username . '" has requested to remove forgotten sickdays. Please approve the request using this link: '.$base_url.'/remove/'.$uid;
        $subject = 'Request to remove forgotten sickdays';
        if($chief_mail){
          $to = $chief_mail;
        }
        else{
          $to = $admin_mail;
        }

        $email = self::sendMail($username, $message, $to, $subject);
      }

      return new JsonResponse([
        'result' => 'OK',
        'sickdays' => $sickdays,
        'start_date' => $start_dt,
        'start_day' => $start_day,
        'end_date' => $end_dt,
        'end_day' => $end_day,
        'today' => $current_day,
        'current_date' => $current_date,
        'current_time' => $current_time,
      ]);
    }
    else{
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }
  }

  public function approve($user_id){
    \Drupal::service('page_cache_kill_switch')->trigger();

    if(\Drupal::currentUser()->isAnonymous()){
      return new RedirectResponse(Url::fromRoute('user.login')->toString());
    }
    else{
      $curr_user_roles = \Drupal::currentUser()->getRoles(TRUE);
      if(in_array('su admin', $curr_user_roles) || in_array('chief', $curr_user_roles)){
        $health_ids = \Drupal::entityQuery('lm_custom_health')
          ->condition('user_id', $user_id)
          ->condition('approval_status','unapproved')
          ->execute();

        if($health_ids){
          $healths = Health::loadMultiple($health_ids);
          foreach ($healths as $health){
            $health->set('approval_status', 'approved');
            $health->save();
          }
          $message = 'Approved successfully';
          \Drupal::messenger()->addMessage($message);
          $url = Url::fromUserInput('/');
          return new RedirectResponse($url->toString());
        }
        else{
          $message = 'No approval status exists';
          \Drupal::messenger()->addMessage($message);
          $url = Url::fromUserInput('/');
          return new RedirectResponse($url->toString());
        }
      }
      else{
        $message = 'User role should be higher level to approve';
        \Drupal::messenger()->addMessage($message);
        $url = Url::fromUserInput('/');
        return new RedirectResponse($url->toString());
      }
    }
  }


  public function remove($user_id){
    \Drupal::service('page_cache_kill_switch')->trigger();

    if(\Drupal::currentUser()->isAnonymous()){
      return new RedirectResponse(Url::fromRoute('user.login')->toString());
    }
    else{
      $curr_user_roles = \Drupal::currentUser()->getRoles(TRUE);
      if(in_array('su admin', $curr_user_roles) || in_array('chief', $curr_user_roles)){
        $health_ids = \Drupal::entityQuery('lm_custom_health')
          ->condition('user_id', $user_id)
          ->condition('approval_status','remove')
          ->execute();

        if($health_ids){
          $healths = Health::loadMultiple($health_ids);
          foreach ($healths as $health){
            $health->set('status', 'healthy');
            $health->set('approval_status', 'approved');
            $health->set('full_time_sickhours', [[ 'value' => '' ]]);
            $health->save();
          }
          $message = 'Approved successfully';
          \Drupal::messenger()->addMessage($message);
          $url = Url::fromUserInput('/');
          return new RedirectResponse($url->toString());
        }
        else{
          $message = 'No approval status exists';
          \Drupal::messenger()->addMessage($message);
          $url = Url::fromUserInput('/');
          return new RedirectResponse($url->toString());
        }
      }
      else{
        $message = 'User role should be higher level to approve';
        \Drupal::messenger()->addMessage($message);
        $url = Url::fromUserInput('/');
        return new RedirectResponse($url->toString());
      }
    }
  }


  public function addSingleAgreement() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $ag_user = $drupal_request->request->get('user');
    $ag_date = $drupal_request->request->get('ag_date');
    $ag_from_time = $drupal_request->request->get('ag_from_time');
    $ag_to_time = $drupal_request->request->get('ag_to_time');
    $ag_day = $drupal_request->request->get('ag_day');
    $ag_sick_time = $drupal_request->request->get('ag_sick_time');
    $uid = \Drupal::currentUser()->id();


    /* $dates = [];
     foreach($daterange as $date){
     array_push($dates, ['day' => $date->format('l'), 'date'=>$date->format('Y-m-d')]);

     }


     foreach($dates as $key => $date){
     $time_stamp = strtotime($date['date']);
     if($dates[$key]['day'] == 'Friday' && $dates[$key+3]['day'] == 'Monday'){
     $health = Health::create([
     'status' => $health_status,
     'sickdates' => $time_stamp,
     'user_id' => $uid,
     ]);
     $health->save();
     }
     } */
    if(preg_match("/[a-z]/i", $ag_user)){
      $user_id = \Drupal::entityQuery('user')
        ->condition('name',$ag_user)
        ->execute();
    }
    else{
      $user_id = \Drupal::entityQuery('user')
        ->condition('field_id_number',$ag_user)
        ->execute();
    }

    if($ag_user){
      foreach ($ag_date as $key => $date){
        $agreement_id = \Drupal::entityQuery('lm_custom_agreement')
          ->condition('user_id',$ag_user)
          ->condition('day_from',$ag_date[$key])
          ->execute();

        if(!$agreement_id){
          $agreement = Agreement::create([
            'day_from' => $ag_date[$key],
            'time_from' => $ag_from_time[$key],
            'time_to' => $ag_to_time[$key],
            'sick_start_time' => $ag_sick_time,
            'period' => 0,
            'ag_status' => 'disabled',
            'user_id' => $user_id
          ]);
          $agreement->save();
        }
        else {
          return new JsonResponse([
            'result' => 'Not OK',
          ]);
        }
      }

      return new JsonResponse([
        'result' => 'OK',
      ]);
    }
    else {
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }

  }

  public function addPeriodAgreement() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $ag_user = $drupal_request->request->get('user');
    $ag_date_from = $drupal_request->request->get('ag_date_from');
    $ag_date_to = $drupal_request->request->get('ag_date_to');
    $ag_from_time = $drupal_request->request->get('ag_from_time');
    $ag_to_time = $drupal_request->request->get('ag_to_time');
    $ag_sick_time = $drupal_request->request->get('ag_sick_time');
    $uid = \Drupal::currentUser()->id();

    if(preg_match("/[a-z]/i", $ag_user)){
      $user_id = \Drupal::entityQuery('user')
      ->condition('name',$ag_user)
      ->execute();
    }
    else{
      $user_id = \Drupal::entityQuery('user')
      ->condition('field_id_number',$ag_user)
      ->execute();
    }

    $end = strtotime($ag_date_to);
    $begin = strtotime($ag_date_from);
    $datediff = $end - $begin;

    $sickdays = round($datediff / (60 * 60 * 24));
    $start_day = date('l',strtotime($ag_date_from));
    $end_day = date('l',strtotime($ag_date_to));
    $current_date = date('Y-m-d');
    $current_day = date('l',strtotime($current_date));
    $current_time = date('H:i');

    if($ag_user){
      foreach ($ag_date_from as $key => $date){

        $agreement_id = \Drupal::entityQuery('lm_custom_agreement')
          ->condition('user_id',$ag_user)
          ->condition('day_from',$ag_date_from[$key])
          ->execute();

        if(!$agreement_id){
          $agreement = Agreement::create([
            'day_from' => $ag_date_from[$key],
            'day_to' => $ag_date_to[$key],
            'time_from' => $ag_from_time[$key],
            'time_to' => $ag_to_time[$key],
            'sick_start_time' => $ag_sick_time,
            'period' => 1,
            'ag_status' => 'enabled',
            'user_id' => $user_id
          ]);
          $agreement->save();
        }
        else {
          return new JsonResponse([
            'result' => 'Not OK',
          ]);
        }
      }

      return new JsonResponse([
        'result' => 'OK',
        'sickdays' => $sickdays,
        'start_date' => $ag_date_from,
        'start_day' => $start_day,
        'end_date' => $ag_date_to,
        'end_day' => $end_day,
        'today' => $current_day,
        'current_date' => $current_date,
        'current_time' => $current_time,
      ]);
    }
    else {
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }


  }

  public function editPeriodAgreement() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $ag_id = $drupal_request->request->get('ag_id');
    $ag_date_from = $drupal_request->request->get('ag_date_from');
    $ag_date_to = $drupal_request->request->get('ag_date_to');
    $ag_from_time = $drupal_request->request->get('ag_time_from');
    $ag_to_time = $drupal_request->request->get('ag_time_to');

    if($ag_id){
      $agreement = Agreement::load($ag_id);
      $agreement->set('day_from', $ag_date_from);
      $agreement->set('day_to', $ag_date_to);
      $agreement->set('time_from', $ag_from_time);
      $agreement->set('time_to', $ag_to_time);
      $agreement->save();

      return new JsonResponse([
        'result' => 'OK',
      ]);
    }
    else {
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }
  }

  public function deletePeriodAgreement() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $ag_id = $drupal_request->request->get('ag_id');

    if($ag_id){
      $agreement = Agreement::load($ag_id);
      $agreement->set('ag_status', 'disabled');
      $agreement->save();

      return new JsonResponse([
        'result' => 'OK',
      ]);
    }
    else {
      return new JsonResponse([
        'result' => 'Not OK',
      ]);
    }
  }


  public function viewDashboard() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    $roles = $user->get('roles')->getValue()[0]['target_id'];
    $time_stamp = strtotime(date("Y-m-d"));
	
		$cid = \Drupal::request()->query->get('cid');
		
    if(strpos($roles, 'administrator') !== false){
			if($cid){
				$user_ids = \Drupal::entityQuery('user')
					->condition('uid',['0','1'],'NOT IN')
					->condition('field_company',$cid)
					->execute();
			}
			else{
				$user_ids = \Drupal::entityQuery('user')
					->condition('uid',['0','1'],'NOT IN')
					->execute();
			}
    }
    elseif(strpos($roles, 'su admin') !== false){
      $cp_id = $user->get('field_company')->getString();
      $user_ids = \Drupal::entityQuery('user')
        ->condition('uid',['0', '1'],'NOT IN')
        ->condition('field_company',$cp_id)
        ->execute();
    }
    elseif(strpos($roles, 'chief') !== false){
			$cp_id = $user->get('field_company')->getString();
      $chief_dpt_ids = $user->get('field_department')->getString();
      $chief_dpt_array = explode(', ', $chief_dpt_ids);
      $user_ids = \Drupal::entityQuery('user')
        ->condition('uid',['0','1'],'NOT IN')
        ->condition('field_department',$chief_dpt_array, 'IN')
        ->condition('roles','su admin','<>')
        ->condition('field_company',$cp_id)
        ->execute();
    }
    else{
      $user_ids[] = $uid;
    }

    $users = User::loadMultiple($user_ids);

    $members = [];
    $ag_user = [];
    foreach ($users as $user_entity){
      $user_id = $user_entity->id();
      if($user_id){
        $role = $user_entity->getRoles(TRUE)[0];
        $is_special_hours = (int)$user_entity->get('field_special_hours')->getString();
        $isWithinLaw = $user_entity->get('field_within_law')->getString();

        $health_ids = \Drupal::entityQuery('lm_custom_health')
          ->condition('user_id',$user_id)
          ->condition('approval_status', 'approved')
          ->condition('status','sick')
          ->execute();

        $fullTimeSick = [];
        $partTimeSick = [];
        if($health_ids){
          foreach ($health_ids as $health_id){
            $health = Health::load($health_id);
            $fullSick = $health->get('full_time_sickhours')->getString();
            $partSick = $health->get('part_time_sickhours')->getString();
            if($fullSick){
              $fullTimeSick[] = $fullSick;
            }
            elseif($partSick){
              $partTimeSick[] = $partSick;
            }
          }
        }
        /* $sick_dates =  count($health_ids);
        $last_sick_date = '';
        if($health_ids){
          $last_health_id = end($health_ids);
          $health_entity = Health::load($last_health_id);
          $last_sick = $health_entity->get('sickdates')->getString();
          $last_sick_date = date("d F Y",$last_sick);
        } */

        /* $now = time();
        $current_year = date('Y');
        $start_date = strtotime($current_year.'-01-01');
        $datediff = $now - $start_date;
        $total_days = round($datediff / (60 * 60 * 24));
        $total_working_days = $total_days - $sick_dates; */

        $today_health_id = \Drupal::entityQuery('lm_custom_health')
          ->condition('user_id', $user_id)
          ->condition('sickdates', $time_stamp)
          ->condition('approval_status', 'approved')
          ->execute();

        $today_health_id = implode('', $today_health_id);
        $health_status = '';
        if($today_health_id){
          $today_health = Health::load($today_health_id);
          $health_status = $today_health->get('status')->getString();
        }

        $id_number = $user_entity->get('field_id_number')->getString();
        $dpt_ids = $user_entity->get('field_department')->getString();
        $dpt_array = explode(', ', $dpt_ids);

        if($is_special_hours){
          $work_ids = \Drupal::entityQuery('lm_custom_work')
            ->condition('user_id',$user_id)
            ->execute();
        }
        else{
          $work_ids = \Drupal::entityQuery('lm_custom_work')
            ->condition('department_id',$dpt_ids[0])
            ->execute();
        }

        foreach ($work_ids as $work_id){
          $work = Work::load($work_id);
          $workhours = $work->get('workhours')->getString();
          break;
        }


        $sickDays = count($fullTimeSick);
        $partTimeHours = self::addHours($partTimeSick);
        $fullDays = round((int)$partTimeHours/(int)$workhours);
        $sickDays = (int)$sickDays + (int)$fullDays;

        $terms = Term::loadMultiple($dpt_array);
        $term_names = [];
        foreach ($terms as $term){
          $term_names[] = $term->get('name')->getString();
        }

        $dpt_count = count($term_names);
        //$term_names = implode(', ', $term_names);
        if($dpt_count > 1){
          $term_names = $term_names[0] . ' +' . ($dpt_count-1);
        }
        else{
          $term_names = implode(', ', $term_names);
        }

        $agreement_ids = \Drupal::entityQuery('lm_custom_agreement')
          ->condition('user_id',$user_id)
          ->condition('ag_status','enabled')
          ->execute();

        if($agreement_ids){
          foreach ($agreement_ids as $ag_id){
            $agreement = Agreement::load($ag_id);
            $ag_user[$ag_id] = [
              'ag_day_from' => $agreement->get('day_from')->getString(),
              'ag_day_to' => $agreement->get('day_to')->getString(),
              'ag_time_from' => $agreement->get('time_from')->getString(),
              'ag_time_to' => $agreement->get('time_to')->getString()
            ];
          }
        }

        $members[$user_id] = [
          'user_id' => $user_id,
          'name' => $user_entity->getAccountName(),
          'role' => $role,
          'is_within_law' => $isWithinLaw,
          'id_number' => $id_number,
          'department' => $term_names,
          'status' => $health_status ? ucfirst($health_status) : 'Healthy',
          'sickdays' => $sickDays,
          'ag_user' => $ag_user,
        ];
      }
    }


    /* }
    elseif(strpos($roles, 'chief') !== false){
      $chief_dpt_ids = $user->get('field_department')->getString();
      $chief_dpt_array = explode(', ', $chief_dpt_ids);
      $user_ids = \Drupal::entityQuery('user')
        ->condition('uid',1,'<>')
        ->condition('field_department',$chief_dpt_array, 'IN')
        ->execute();

      $users = User::loadMultiple($user_ids);
      $members = [];

      foreach ($users as $user_entity){
        $user_id = $user_entity->id();

        $health_ids = \Drupal::entityQuery('lm_custom_health')
        ->condition('user_id',$user_id)
        ->execute();
        $sick_dates =  count($health_ids);
        $last_sick_date = '';
        if($health_ids){
          $last_health_id = end($health_ids);
          $health_entity = Health::load($last_health_id);
          $last_sick = $health_entity->get('sickdates')->getString();
          $last_sick_date = date("d F Y",$last_sick);
        }

        $now = time();
        $current_year = date('Y');
        $start_date = strtotime($current_year.'-01-01');
        $datediff = $now - $start_date;
        $total_days = round($datediff / (60 * 60 * 24));
        $total_working_days = $total_days - $sick_dates;

        $health_status = \Drupal::entityTypeManager()->getStorage('lm_custom_health')
        ->loadByProperties([
          'user_id' => $user_id,
          'sickdates' => $time_stamp
        ]);
        $id_number = $user_entity->get('field_id_number')->getString();
        $dpt_ids = $user_entity->get('field_department')->getString();
        $dpt_array = explode(', ', $dpt_ids);
        $terms = Term::loadMultiple($dpt_array);
        $term_names = [];

        foreach ($terms as $term){
          $term_names[] = $term->get('name')->getString();
        }
        $dpt_count = count($term_names);
        //$term_names = implode(', ', $term_names);
        if($dpt_count > 1){
          $term_names = $term_names[0] . ' +' . ($dpt_count-1);
        }
        else{
          $term_names = implode(', ', $term_names);
        }
        $members[$user_id] = [
          'user_id' => $user_id,
          'username' => $user_entity->getAccountName(),
          'roles' => $user_entity->getRoles(),
          'id_number' => $id_number,
          'department' => $term_names,
          'status' => $health_status ? 'Sick' : 'Healthy',
          'sickdates' => $sick_dates,
          'last_sick_date' => $last_sick_date,
          'total_working_days' => $total_working_days,
        ];
      }

    }
    else{
      $health_ids = \Drupal::entityQuery('lm_custom_health')
        ->condition('user_id',$uid)
        ->execute();
      $sick_dates =  count($health_ids);
      $last_sick_date = '';
      if($health_ids){
        $last_health_id = end($health_ids);
        $health_entity = Health::load($last_health_id);
        $last_sick = $health_entity->get('sickdates')->getString();
        $last_sick_date = date("d F Y",$last_sick);
      }

      $now = time();
      $current_year = date('Y');
      $start_date = strtotime($current_year.'-01-01');
      $datediff = $now - $start_date;
      $total_days = round($datediff / (60 * 60 * 24));
      $total_working_days = $total_days - $sick_dates;

      $health_status = \Drupal::entityTypeManager()->getStorage('lm_custom_health')
      ->loadByProperties([
        'user_id' => $uid,
        'sickdates' => $time_stamp
      ]);
      $id_number = $user_entity->get('field_id_number')->getString();
      $dpt_ids = $user->get('field_department')->getString();
      $dpt_array = explode(', ', $dpt_ids);
      $terms = Term::loadMultiple($dpt_array);
      $term_names = [];
      foreach ($terms as $term){
        $term_names[] = $term->get('name')->getString();
      }
      $dpt_count = count($term_names);
      //$term_names = implode(', ', $term_names);
      if($dpt_count > 1){
        $term_names = $term_names[0] . ' +' . ($dpt_count-1);
      }
      else{
        $term_names = implode(', ', $term_names);
      }
      $members[$uid] = [
        'user_id' => $uid,
        'username' => $user->getAccountName(),
        'roles' => $user->getRoles(),
        'id_number' => $id_number,
        'department' => $term_names,
        'status' => $health_status ? 'Sick' : 'Healthy',
        'sickdates' => $sick_dates,
        'last_sick_date' => $last_sick_date,
        'total_working_days' => $total_working_days,
      ];
    } */

    $template = 'dashboard_page';
    return [
      '#theme' => $template,
      '#cache' => [
        'max-age' => 0
      ],
      '#base_url' => $base_url,
      '#members' => $members,
      '#attached' => [
        'library' => [
          'lm_custom/departments-page',
        ],
      ],
    ];
  }

  public function viewCompanies() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    $roles = $user->get('roles')->getValue()[0]['target_id'];
    $user_company = $user->get('field_company')->getString();

    if(strpos($roles, 'administrator') !== false){
      $company_ids = \Drupal::entityQuery('lm_custom_company')
      ->execute();
    }
    elseif( strpos($roles, 'su admin') !== false){
		$company_ids = \Drupal::entityQuery('lm_custom_company')
		->condition('uid', $uid)
		->execute();
	}

    $all_companies = Company::loadMultiple($company_ids);
    $companies = [];
    
    foreach($all_companies as $company){
		$cid = $company->Id();
		$user_ids = \Drupal::entityQuery('user')
		->condition('field_company', $cid)
		->condition('roles', 'su admin', '<>')
		->execute();
		$cp_members = 0;	
		if($user_ids){
			$cp_members = count($user_ids);
		}
		$cp_name = $company->get('company_name')->getString();
		$cp_email = $company->get('company_email')->getString();
		$cp_address = $company->get('company_address')->getString();
		$cp_phone = $company->get('company_phone')->getString();
		$credit_card = $company->get('credit_card')->getString();
		$cp_since = $company->get('created')->getString();
		$cp_since = date('d-m-Y', $cp_since);

		$companies[$cid] = [
		 'cp_name' => $cp_name,
		 'cp_email' => $cp_email,
		 'cp_members' => $cp_members,
		 'cp_phone' => $cp_phone,
		 'cp_address' => $cp_address,
		 'credit_card' => $credit_card,
		 'cp_since' => $cp_since
		];		
    }
    

    $template = 'companies_page';
    return [
      '#theme' => $template,
      '#cache' => [
        'max-age' => 0
      ],
      '#base_url' => $base_url,
      '#companies' => $companies,
      '#attached' => [
        'library' => [
          'lm_custom/departments-page',
        ],
      ],
    ];
  }

  public function addCompany(){
    global $base_url;

    \Drupal::service('page_cache_kill_switch')->trigger();
    $uid = \Drupal::currentUser()->id();

    $drupal_request = \Drupal::request();
    $cp_name = $drupal_request->request->get('cp_name');
    $cp_email = $drupal_request->request->get('cp_email');
    $cp_phone = $drupal_request->request->get('cp_phone');
    $cp_address = $drupal_request->request->get('cp_address');
    $cp_card = $drupal_request->request->get('cp_card');
    $cid = $drupal_request->request->get('cid');

		if($cid){
			$company = Company::load($cid);
			$company->set('company_name', $cp_name);
			$company->set('company_email', $cp_email);
			$company->set('company_phone', $cp_phone);
			$company->set('company_address', $cp_address);
			$company->set('credit_card', $cp_card);
			$company->save();
		}
		else{
			$company = Company::create([
				'company_name' => $cp_name,
				'company_email' => $cp_email,
				'company_phone' => $cp_phone,
				'company_address' => $cp_address,
				'credit_card' => $cp_card,
			]);
			$company->save();
			
			$username = $cp_name;
			$to = $cp_email;
			$subject = 'Company registration';
			$message = 'Your company "'. $username. '" is ready';
			
			$email = self::sendMail($username,$message,$to,$subject);
		}
		
    return new JsonResponse([
      'result' => 'OK',
      'cp_name' => $cp_name,
    ]);

  }
  
  public function deleteCompany(){
	\Drupal::service('page_cache_kill_switch')->trigger();
	$drupal_request = \Drupal::request();
    $cid = $drupal_request->request->get('cid');
    
    $user_ids = \Drupal::entityQuery('user')
    ->condition('field_company', $cid)
    ->execute();
    
    $users = User::loadMultiple($user_ids);
    
    foreach($users as $user){
		$user_dpts = $user->get('field_department')->getString();
		
		$departments = Term::loadMultiple($user_dpts);
		
		foreach($departments as $department){
			$department->deleteCompany();
		}
		
		$user->deleteCompany();
	}	
    
    $company = Company::load($cid);
    $company->delete();
	
	return new JsonResponse([
      'result' => 'OK'
    ]);
  }

  public function userSettings(){
    global $base_url;

    \Drupal::service('page_cache_kill_switch')->trigger();

    $uid = \Drupal::currentUser()->id();

    $template = 'user_settings_page';

    return [
      '#theme' => $template,
      '#cache' => [
        'max-age' => 0
      ],
      '#base_url' => $base_url,
      '#user_id' => $uid,
      '#attached' => [
        'library' => [
          'lm_custom/departments-page',
        ],
      ],
    ];
  }

  public function changePassword(){
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $user_id = $drupal_request->request->get('user_id');
    $email = $drupal_request->request->get('email');
    $new_password = $drupal_request->request->get('new_password');

    $user = User::load($user_id);
    $user->setEmail($email);
    $user->setPassword($new_password);
    $user->save();

    return new JsonResponse([
      'result' => 'OK',
    ]);

  }

  public function viewStatistics($user_id) {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $member = [];
    $curr_uid = \Drupal::currentUser()->id();
    $curr_user = User::load($curr_uid);
    $roles = $curr_user->get('roles')->getValue()[0]['target_id'];


    $user = User::load($user_id);
    $username = $user->getAccountName();
    $id_number = $user->get('field_id_number')->getString();
    $is_special_hours = (int)$user->get('field_special_hours')->getString();
    $dpt_ids = $user->get('field_department')->getString();
    $dpt_ids = explode(',', $dpt_ids);

    $terms = Term::loadMultiple($dpt_ids);
    $dpt_names = [];
    foreach ($terms as $term){
      $dpt_names[] = $term->getName();
    }
    $departments = implode(', ', $dpt_names);

    $agreement_ids = \Drupal::entityQuery('lm_custom_agreement')
      ->condition('user_id',$user_id)
      ->condition('ag_status','enabled')
      ->execute();

    $ag_user = [];
    if($agreement_ids){
      foreach ($agreement_ids as $ag_id){
        $agreement = Agreement::load($ag_id);
        $ag_user[$ag_id] = [
          'ag_day_from' => $agreement->get('day_from')->getString(),
          'ag_day_to' => $agreement->get('day_to')->getString(),
          'ag_time_from' => $agreement->get('time_from')->getString(),
          'ag_time_to' => $agreement->get('time_to')->getString()
        ];
      }
    }

    $today = [];
    $curr_date = strtotime(date('Y-m-d'));
    $today = [
      'date' => date('Y-m-d'),
      'day' => date('l')
    ];


    $lastYear = [];
    $lastYearDate = strtotime(date('Y-m-d').' -1 year');
    $lastYear = [
      'date' => date("Y-m-d", $lastYearDate),
      'day' => date("l", $lastYearDate),
    ];

    $health_ids = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id',$user_id)
      ->condition('sickdates',[$lastYearDate,$curr_date],'BETWEEN')
      ->condition('status','sick')
      ->condition('approval_status', 'approved')
      ->sort('sickdates', 'DESC')
      ->execute();

    $health_ids = array_values($health_ids);
    $health_entity = Health::load($health_ids[0]);

    $last_sick_date = '';
    $fullTimeSick = [];
    $partTimeSick = [];
    if($health_ids){
      $last_sick_date = date('d.m.Y',$health_entity->get('sickdates')->getString());
      foreach ($health_ids as $health_id){
        $health = Health::load($health_id);
        $fullSick = $health->get('full_time_sickhours')->getString();
        $partSick = $health->get('part_time_sickhours')->getString();
        if($fullSick){
          $fullTimeSick[] = $fullSick;
        }
        elseif($partSick){
          $partTimeSick[] = $partSick;
        }
      }
    }

    $sickDays = count($fullTimeSick);
    $partTimeHours = self::addHours($partTimeSick);

    if($is_special_hours){
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('user_id',$user_id)
        ->execute();
    }
    else{
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$dpt_ids[0])
        ->execute();
    }
    $workhours = '';
    foreach ($work_ids as $work_id){
      $work = Work::load($work_id);
      $workhours = $work->get('workhours')->getString();
      break;
    }

    $fullDays = round((int)$partTimeHours/(int)$workhours);
    $sickDays = (int)$sickDays + (int)$fullDays;

    $monthly_stats = self::getMonthlyStats($user_id);

    $member = [
        'user_id' => $user_id,
        'name' => ucwords($username),
        'id_number' => $id_number,
        'departments' => $departments,
        'ag_user' => $ag_user,
        'today' => $today,
        'last_year' => $lastYear,
        'last_sick_date' => $last_sick_date,
        'sickdays' => $sickDays,
        'health_status' => $health_status,
    ];


    $time_stamp = strtotime(date("Y-m-d"));
    //$date = date("Y-m-d",$time);

    $health_entity = \Drupal::entityTypeManager()->getStorage('lm_custom_health')
      ->loadByProperties([
        'user_id' => $user_id,
        'sickdates' => $time_stamp,
        'status' => 'sick'
      ]);

    $health_status = '';
    if($health_entity){
      $health_status = 'sick';
    }


    $template = 'statistics_page';

    return [
      '#theme' => $template,
      '#cache' => [
        'max-age' => 0
      ],
      '#base_url' => $base_url,
      '#member' => $member,
      '#roles' => $roles,
      '#monthly_stats' => $monthly_stats,
      '#attached' => [
        'library' => [
          'lm_custom/departments-page',
        ],
      ],
    ];
  }

  public function viewFrontPage() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    global $base_url;
    $uid = \Drupal::currentUser()->id();
    $current_user = User::load($uid);
    $current_username = ucwords($current_user->getAccountName());
    $curr_user_dpt = $current_user->get('field_department')->getString();
    $curr_user_dpt = explode(',', $curr_user_dpt);

    $members = [];
    $today_stamp = strtotime(date('Y-m-d'));

    foreach ($curr_user_dpt as $dpt_id){
      $user_ids = \Drupal::entityQuery('user')
        ->condition('uid','0','<>')
        ->condition('field_department',$dpt_id)
        ->execute();

      foreach ($user_ids as $user_id){
        $user = User::load($user_id);
        $role = $user->getRoles(TRUE)[0];
        $name = ucwords($user->getAccountName());
        $id_number = $user->get('field_id_number')->getString();
        $dpt_ids = $user->get('field_department')->getString();

        $dpt_ids = explode(',', $dpt_ids);
        $department = '';
        $dpt_names = [];
        if($dpt_ids){
          foreach ($dpt_ids as $tid){
            $term = Term::load($tid);
            if($term){
              $dpt_names[] = $term->getName();
            }
          }
          $department = implode(', ', $dpt_names);
        }
        $health_ids = \Drupal::entityQuery('lm_custom_health')
          ->condition('status','sick')
          ->condition('user_id',$user_id)
          ->condition('sickdates',$today_stamp)
          ->condition('approval_status', 'approved')
          ->execute();

        $health_id = implode('', $health_ids);

        if($health_id){
          $health = Health::load($health_id);
          $health_status = $health->get('status')->getString();

          $members[$user_id] = [
            'user_id' => $user_id,
            'name' => $name,
            'role' => $role,
            'id_number' => $id_number,
            'department' => $department,
            'status' => ucwords($health_status)
          ];
        }
      }
    }

    $template = 'front_page';

    return [
      '#theme' => $template,
      '#cache' => [
        'max-age' => 0
      ],
      '#base_url' => $base_url,
      '#current_user' => $current_username,
      '#members' => $members,
      '#attached' => [
        'library' => [
          'lm_custom/departments-page',
        ],
      ],
    ];

  }

  public function getMonthlyStats($user_id, $date=[]) {
    $user = User::load($user_id);
    $is_special_hours = (int)$user->get('field_special_hours')->getString();
    $dpt_ids = $user->get('field_department')->getString();
    $dpt_ids = explode(',', $dpt_ids);

    if($is_special_hours){
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('user_id',$user_id)
        ->execute();
    }
    else{
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$dpt_ids[0])
        ->execute();
    }
    $workhours = '';
    foreach ($work_ids as $work_id){
      $work = Work::load($work_id);
      $workhours = $work->get('workhours')->getString();
      break;
    }

    if($date){
      $d1 = new \DateTime($date[0]);
      $d2 = new \DateTime($date[1]);
      $st_Date = date('Y-m-01',strtotime($date[0]));
    }
    else{
      $curr_date = date('Y-m-d');
      $st_Date = date('Y-m-d', strtotime(date('Y-m-01').' -1 year'));

      $d1 = new \DateTime($st_Date);
      $d2 = new \DateTime($curr_date);
    }

    $no_of_months = $d1->diff($d2)->m + ($d1->diff($d2)->y*12);
    $endDate = date('Y-m-t 23:59:59',strtotime($st_Date));

    $months = [];
    if($no_of_months){
      for($x=0; $x<$no_of_months; $x++){
        if($nextMonth){
          $st_Date = date('Y-m-d',$nextMonth);
          $endDate = date('Y-m-t 23:59:59',$nextMonth);
        }
        else{
          $months[] = [
            'start-date' => $st_Date,
            'end-date' => $endDate
          ];

        }
        $nextMonth = strtotime($st_Date. ' next month');
        $stDt = date('Y-m-d',$nextMonth);
        $endDt = date('Y-m-t 23:59:59', strtotime($stDt));
        $months[] = [
          'start-date' => $stDt,
          'end-date' => $endDt
        ];
      }
    }
    else{
      $months[] = [
        'start-date' => $st_Date,
        'end-date' => $endDate
      ];
    }

    $monthly_stats = [];

    foreach($months as $key => $month){
      $m = date('M',strtotime($month['start-date']));
      $y = date('Y',strtotime($month['start-date']));
      $i = date('Y-m',strtotime($month['start-date']));
      $start_mo = strtotime($month['start-date']);
      $end_mo = strtotime($month['end-date']);

      $health_ids = \Drupal::entityQuery('lm_custom_health')
        ->condition('user_id',$user_id)
        ->condition('sickdates',[$start_mo,$end_mo],'BETWEEN')
        ->condition('approval_status', 'approved')
        ->condition('status','sick')
        ->execute();



      $fullTimeSick = [];
      $partTimeSick = [];
      if($health_ids){
        foreach ($health_ids as $health_id){
          $health = Health::load($health_id);
          $fullSick = $health->get('full_time_sickhours')->getString();
          $partSick = $health->get('part_time_sickhours')->getString();
          if($fullSick){
            $fullTimeSick[] = $fullSick;
          }
          elseif($partSick){
            $partTimeSick[] = $partSick;
          }
        }
      }

      $sickDays = count($fullTimeSick);
      $partTimeHours = self::addHours($partTimeSick);


      $fullDays = round((int)$partTimeHours/(int)$workhours);
      $sickDays = (int)$sickDays + (int)$fullDays;

      $monthly_stats[$i] = [
        'month' => $m,
        'year' => $y,
        'sickdays' => $sickDays,
      ];

    }

    return $monthly_stats;

  }

  public function getStatsData(){
    \Drupal::service('page_cache_kill_switch')->trigger();

    $drupal_request = \Drupal::request();
    $user_id = $drupal_request->request->get('user_id');
    $stats_begin = $drupal_request->request->get('stats_begin');
    $stats_end = $drupal_request->request->get('stats_end');
    $start_date = strtotime($stats_begin);
    $end_date = strtotime($stats_end);
    $data = [];

    $user = User::load($user_id);
    $is_special_hours = (int)$user->get('field_special_hours')->getString();
    $dpt_ids = $user->get('field_department')->getString();
    $dpt_ids = explode(',', $dpt_ids);

    $health_ids = \Drupal::entityQuery('lm_custom_health')
      ->condition('user_id',$user_id)
      ->condition('sickdates',[$start_date, $end_date], 'BETWEEN')
      ->condition('approval_status', 'approved')
      ->condition('status','sick')
      ->sort('sickdates', 'DESC')
      ->execute();


    $sick_dates =  count($health_ids);

    $health_ids = array_values($health_ids);
    $health_entity = Health::load($health_ids[0]);

    $last_sick_date = 0;
    $fullTimeSick = [];
    $partTimeSick = [];
    if($health_ids){
      $last_sick_date = date('d.m.Y',$health_entity->get('sickdates')->getString());
      foreach ($health_ids as $health_id){
        $health = Health::load($health_id);
        $fullSick = $health->get('full_time_sickhours')->getString();
        $partSick = $health->get('part_time_sickhours')->getString();
        if($fullSick){
          $fullTimeSick[] = $fullSick;
        }
        elseif($partSick){
          $partTimeSick[] = $partSick;
        }
      }
    }

    $sickDays = count($fullTimeSick);
    $partTimeHours = self::addHours($partTimeSick);

    if($is_special_hours){
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('user_id',$user_id)
        ->execute();
    }
    else{
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$dpt_ids[0])
        ->execute();
    }

    $workhours = '';
    foreach ($work_ids as $work_id){
      $work = Work::load($work_id);
      $workhours = $work->get('workhours')->getString();
      break;
    }

    $fullDays = round((int)$partTimeHours/(int)$workhours);
    $sickDays = (int)$sickDays + (int)$fullDays;

    $date = [$stats_begin, $stats_end];
    $monthly_report = self::getMonthlyStats($user_id, $date);


    $data = [
      'sickdays' => $sickDays,
      'last_sick_date' => $last_sick_date,
      'monthly_report' => $monthly_report
    ];


    return new JsonResponse([
      'result' => 'OK',
      'data' => $data,
    ]);

  }

  public  function addHours($times) {
    $minutes = 0;

    foreach ($times as $time) {
      list($hour, $minute) = (strpos(':',$time) != false) ? explode(':', $time) : explode('.', $time);
      $minutes += $hour * 60;
      $minutes += $minute;
    }

    $hours = floor($minutes / 60);
    $minutes -= $hours * 60;

    // returns the time already formatted
    return sprintf('%02d:%02d', $hours, $minutes);
  }

  public function getWorks() {
    $terms = Term::loadMultiple();
    $works = [];
    foreach ($terms as $term){
      $tid = $term->id();
      $work_ids = \Drupal::entityQuery('lm_custom_work')
        ->condition('department_id',$tid)
        ->execute();

      $works_entity = Work::loadMultiple($work_ids);

      $workdays = [];
      $workhours = [];
      $work_start_time = [];
      $sick_start_time = [];

      foreach ($works_entity as $work){
        $workdays[] = $work->get('workdays')->getString();
        $workhours[] = $work->get('workhours')->getString();
        $work_start_time[] = date("H:i", strtotime($work->get('work_start_time')->getString()));
        $sick_start_time[] = date("H:i", strtotime($work->get('sick_start_time')->getString()));
      }

      $works[$tid] = [
        'term_name' => $term->getName(),
        'wid' => array_values($work_ids),
        'workdays' => $workdays,
        'workhours' => $workhours,
        'work_start_time' => $work_start_time,
        'sick_start_time' => $sick_start_time
      ];
    }
    return $works;
  }

  public function sendMail($user,$message,$to,$subject) {
    $params = [];
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'lm_custom';
    $key = 'sick_insert';
    //$to = \Drupal::currentUser()->getEmail();
    $params['message'] = $message;
    $params['user'] = $user;
    $params['subject'] = $subject;
    $langcode = \Drupal::currentUser()->getPreferredLangcode() || 'en';
    $send = true;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== true) {
      $message = t('There was a problem sending your email notification to @email.', array('@email' => $to));
      \Drupal::messenger()->addError($message);
      \Drupal::logger('mail-log')->error($message);
      return false;
    }
    else{
      $message = t('An email notification has been sent to @email ', array('@email' => $to));
      \Drupal::messenger()->addMessage($message);
      \Drupal::logger('mail-log')->notice($message);
      return true;
    }
  }

}
