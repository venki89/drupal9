(function ($, Drupal) {

  "use strict";
  // All the JavaScript for this file.

  /*Drupal.behaviors.departments_page = {
    attach: function (context, settings) {*/
    $(document).ready(function(){
      var base_url = $('#base-url').val();
      console.log('base_url',base_url);

      //Add department
      $('#save-dpt-btn').click(function(e) {
        $('.loading-gif').show();
        var url =  base_url + '/add-department';
        var dpt_name = $('#dpt-name').val().trim();
        var cp_id = $('#dpt-cp').val();
        var works = [{}];
        $('.work-table-value').each(function(k,v){
            var workday = $('.work-day', this).text();
            var hour = $('.input-hour', this).val()+'.'+$('.input-minutes', this).val();
            var wk_st = $('.input-wk-st', this).val();
            var sk_st = $('.input-sk-st', this).val();

            works[k] = {
                'day' : workday,
                'hour' : hour,
                'wk_st' : wk_st,
                'sk_st' : sk_st
            };
        });
        var data = {
            dpt_name : dpt_name,
            cp_id : cp_id,
            works : works
        };
        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
            //var new_dpt = '<div><span>'+dpt_name+'</span><img class="edit-icon edit-department" src="'+base_url+'/modules/custom/lm_custom/images/edit_icon.png" width="12" data-term-id="'+response.tid+'"><img class="delete-icon delete-department" src="'+base_url+'/modules/custom/lm_custom/images/delete_icon.png" width="12" data-term-id="'+response.tid+'"></div>';
            //$('#departments-list').append(new_dpt);
            //$('#dpt-name').val('');
          //Auto refresh page
            $('#page-wrapper').fadeOut("slow").load(base_url+'/departments#page-wrapper').fadeIn("slow");
            setTimeout(function(){
              $('.toolbar-bar')[1].remove();
              alert('Department added: '+dpt_name);
            },2000);
          }
          else {
            alert('Cannot add department');
          }
        });
      });

      //Add member
      $('#add-member-form form').on('submit', function(e){
        e.preventDefault();
        var url =  base_url + '/add-member';
        var member_name = $('#member-name').val();
        var member_email = $('#member-email').val();
        var member_id_number = $('#member-id-number').val();
        var member_cp = $('#member-cp').val();
        var member_dpt = $('#member-dpt').val();
        var member_role = $('#member-role').val();
        var is_within_law = parseInt($('input[name=120-law]:checked',this).val());        
        if(is_within_law){
          var receive_all = [];
          var receive_warning = [];
          $('.receive-mail input[name=receive-all]:checked', this).each(function(){
            receive_all.push($(this).data('role'));
          });
          $('.receive-mail input[name=receive-warning]:checked', this).each(function(){
            receive_warning.push($(this).data('role'));
          });
        }
        if(receive_all){
          receive_all = receive_all.join();
        }
        if(receive_warning){
          receive_warning = receive_warning.join();
        }
        var is_special_hours = parseInt($('input[name=toggle-work]:checked', this).val());
        var special_hours = [{}];
        if(is_special_hours){
          $('.work-table-value', this).each(function(k,v){
            var workday = $('.work-day', this).text();
            var hour = $('.input-hour', this).val()+'.'+$('.input-minutes', this).val();
            var wk_st = $('.input-wk-st', this).val();
            var sk_st = $('.input-sk-st', this).val();

            special_hours[k] = {
                'day' : workday,
                'hour' : hour,
                'wk_st' : wk_st,
                'sk_st' : sk_st
            };
          });
        }

        var data = {
            member_name : member_name,
            member_email : member_email,
            member_id_number : member_id_number,
            member_cp : member_cp,
            member_dpt : member_dpt,
            member_role : member_role,
            is_within_law : is_within_law,
            receive_all : receive_all,
            receive_warning : receive_warning,
            special_hours : special_hours
        };
        if(member_dpt.length == 0){
					alert('Please add department');
				}
				else{
					$('.loading-gif').show();
					$.post(url,data,function(response){
						if(response.result == 'OK') {
							$('.loading-gif').hide();
							$('#add-member-form form')[0].reset();
							//Auto refresh page
							$('#page-wrapper').fadeOut("slow").load(base_url+'/members#page-wrapper').fadeIn("slow");
							setTimeout(function(){
								$('.toolbar-bar')[1].remove();
								alert('Member added: '+response.username);
							},2000);

						}
						else {
							alert('Cannot add member. Same ID number already exist.');
						}
					});
				}
      });


      //Switch Tab menu in health page
      $('.tab-menu').on('click', 'button', function(){
        $('button.active').removeClass('active');
        $(this).addClass('active');
        if($(this).hasClass('add-data')){
          $('.health-container .col').addClass('lm-hidden');
          $('.health-status-wrapper').toggleClass('lm-hidden');
        }
        else if($(this).hasClass('add-sickdays')){
          $('.health-container .col').addClass('lm-hidden');
          $('.add-sick-days-wrapper').removeClass('lm-hidden');
        }
        else if($(this).hasClass('remove-sickdays')){
          $('.health-container .col').addClass('lm-hidden');
          $('.remove-sick-days-wrapper').removeClass('lm-hidden');
        }
        else if($(this).hasClass('add-agreement')){
          $('.health-container .col').addClass('lm-hidden');
          $('#special-agreement-wrapper').removeClass('lm-hidden');
        }

      });


      //Switch active buttons on health status and Add sick/healthy
      $("#health-status-div").on("click", "button", function() {
        console.log('this',$(this));
          var health_status = $(this).attr('data-health-status');

          if($(this).hasClass('active')){
            alert("You can't click the active element");
          }
          else{
            $('button.active').removeClass('active');
            $(this).addClass('active');

            var url = base_url + '/add-health';
            var data = {
                health_status : health_status
            }

            $.post(url,data,function(response){
              if(response.result == 'OK') {
                $('.loading-gif').hide();
                /*var msg = '<p>You are now resgistered as sick. An email has been sent to your nearest chief/supervisor.</p>';
                openDialog(msg);*/
                if(health_status == 'sick'){
                  $('#sick-notification').removeClass('lm-hidden');
                  $('#sick-notification .modal-day').text(response.today);
                  $('#sick-notification .modal-date').text(response.current_date);
                  $('#sick-notification .modal-time').text(response.current_time);
                }
                else{
                  $('#health-notification').removeClass('lm-hidden');
                  $('#health-notification .modal-day').text(response.today);
                  $('#health-notification .modal-date').text(response.current_date);
                  $('#health-notification .modal-time').text(response.current_time);
                  if(response.prev_day && (response.prev_day != '' || response.prev_day != null)){
                    $('#health-notification .regd-sick-day').text(response.prev_day);
                    $('#health-notification .regd-sick-date').text(response.prev_date);
                  }
                  else{
                    $('#health-notification .regd-sick-day').text(response.today);
                    $('#health-notification .regd-sick-date').text(response.current_date);
                  }
                  $('#health-notification .regd-health-day').text(response.today);
                  $('#health-notification .regd-health-date').text(response.current_date);
                  $('#health-notification .regd-sickdays').text(response.sickdays);
                }
              }
              else if(response.result == 'weekend'){
                alert('Saturday and sunday not allowed.')
              }
              else {
                alert('Sick date already added.');
              }
            });
          }

      });

      function openDialog(msg) {
        $('#dialog-message').dialog({
          dialogClass: 'no-title',
          draggable: false,
          modal: true,
          width: 400,
          height: "auto",
          open: function() {
            console.log('this',$(this));
            $(this).html(msg);
          },
          buttons: {
            Ok: function() {
              $( this ).dialog( "close" );
            }
          }
        });
      }

      function showFlash(msg){
        $('#dialog-message').dialog({
          dialogClass: 'no-title',
          draggable: false,
          modal: true,
          width: 400,
          height: "auto",
          open: function() {
            console.log('this',$(this));
            $(this).html(msg);
          }
        });

        setTimeout(function() {
          $("#dialog-message").dialog( "close" );
        }, 3000);
      }


      //Get day after selecting input date
      $('input[type="date"]').on('change', function(){
        var input_val = $(this).val();
        var dt = new Date(input_val);
        var weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        var weekday = weekdays[dt.getDay()];
        $(this).prev('span').text(weekday);
        console.log('wkday',weekday);
      });


      //Adding sickday to the health entity
      $('#sickday-send-btn').on('click',function(e) {
        console.log('e',e);
        var sickday_dt = $('#s-day-date').val();
        var sickday_time = $('#s-day-time').val();
        var sd_day = $('.single-day-wrapper .s-day').text();
        var health_status = 'sick';

        var url = base_url + '/add-single-sickday';
        var data = {
            sickday_dt : sickday_dt,
            sickday_time : sickday_time,
            health_status : health_status,
            sd_day : sd_day
        }

        $('.loading-gif').show();

        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
            $('#add-sickday-notification').removeClass('lm-hidden');
            $('#add-sickday-notification .modal-day').text(response.today);
            $('#add-sickday-notification .modal-date').text(response.current_date);
            $('#add-sickday-notification .modal-time').text(response.current_time);
            $('#add-sickday-notification .regd-sick-day-from').text(response.start_day);
            $('#add-sickday-notification .regd-sick-date-from').text(response.start_date);
            $('#add-sickday-notification .regd-sick-day-to').text(response.start_day);
            $('#add-sickday-notification .regd-sick-date-to').text(response.start_date);
            $('#add-sickday-notification .regd-sickdays').text(response.sickdays);
          }
          else {
            alert('Sickday already added.');
          }
        });

      });

      //Adding sickdays to the health entity
      $('#period-send-btn').on('click',function(e) {
        var sick_start_dt = $('#sick-days-start').val();
        var sick_start_time = $('#sick-time-start').val();
        var sick_end_dt = $('#sick-days-end').val();
        var sd_day = $('#period-wrapper .s-day').first().text();
        var health_status = 'sick';

        var url = base_url + '/add-period-sickdays';
        var data = {
            sick_start_dt : sick_start_dt,
            sick_start_time : sick_start_time,
            sick_end_dt : sick_end_dt,
            sd_day : sd_day,
            health_status : health_status
        }

        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
            $('#add-sickday-notification').removeClass('lm-hidden');
            $('#add-sickday-notification .modal-day').text(response.today);
            $('#add-sickday-notification .modal-date').text(response.current_date);
            $('#add-sickday-notification .modal-time').text(response.current_time);
            $('#add-sickday-notification .regd-sick-day-from').text(response.start_day);
            $('#add-sickday-notification .regd-sick-date-from').text(response.start_date);
            $('#add-sickday-notification .regd-sick-day-to').text(response.end_day);
            $('#add-sickday-notification .regd-sick-date-to').text(response.end_date);
            $('#add-sickday-notification .regd-sickdays').text(response.sickdays);
          }
          else {
            alert('Sickdays already added.');
          }
        });

      });


      //Removing sickday from the health entity
      $('#sickday-rm-btn').on('click',function(e) {
        var sickday_dt = $('#rm-s-day-date').val();
        var health_status = 'sick';

        var url = base_url + '/remove-single-sickday';
        var data = {
            sickday_dt : sickday_dt,
            health_status : health_status,
        }

        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
            $('#remove-sickday-notification').removeClass('lm-hidden');
            $('#remove-sickday-notification .modal-day').text(response.today);
            $('#remove-sickday-notification .modal-date').text(response.current_date);
            $('#remove-sickday-notification .modal-time').text(response.current_time);
            $('#remove-sickday-notification .regd-sick-day-from').text(response.start_day);
            $('#remove-sickday-notification .regd-sick-date-from').text(response.start_date);
            $('#remove-sickday-notification .regd-sick-day-to').text(response.start_day);
            $('#remove-sickday-notification .regd-sick-date-to').text(response.start_date);
            $('#remove-sickday-notification .regd-sickdays').text(response.sickdays);
          }
          else {
            alert('Sickday already deleted.');
          }
        });

      });


      //Removing sickdays from the health entity
      $('#period-rm-btn').on('click',function(e) {
        var sick_start_dt = $('#rm-sick-days-start').val();
        var sick_end_dt = $('#rm-sick-days-end').val();

        var url = base_url + '/remove-period-sickdays';
        var data = {
            sick_start_dt : sick_start_dt,
            sick_end_dt : sick_end_dt,
        }

        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
            $('#remove-sickday-notification').removeClass('lm-hidden');
            $('#remove-sickday-notification .modal-day').text(response.today);
            $('#remove-sickday-notification .modal-date').text(response.current_date);
            $('#remove-sickday-notification .modal-time').text(response.current_time);
            $('#remove-sickday-notification .regd-sick-day-from').text(response.start_day);
            $('#remove-sickday-notification .regd-sick-date-from').text(response.start_date);
            $('#remove-sickday-notification .regd-sick-day-to').text(response.end_day);
            $('#remove-sickday-notification .regd-sick-date-to').text(response.end_date);
            $('#remove-sickday-notification .regd-sickdays').text(response.sickdays);
          }
          else {
            alert('Sickdays already deleted.');
          }
        });

      });


      //Edit department
      $('.edit-department').on('click', function(e){
        $('#save-dpt-btn').hide();
        $('#edit-dpt-btn').show();
        var tid = $(this).data('term-id');
        var cid = $(this).data('cid');
        var work = $(this).data('work');
        var company = JSON.parse($('#companies').val());
        var tname = $(this).prev().text();
        console.log(company);
        console.log('work',work);
        $('#edit-dpt-btn').attr('data-tid',tid);
        $('#dpt-name').val(tname);
        $('#dpt-cp').val(cid);
        $('.work-table-value').each(function(k,v){
          var current = $(this);
          var wid = work.wid[k];
          var day = work.workdays[k];
          var hour = work.workhours[k].split('.')[0];
          var minutes = work.workhours[k].split('.')[1];
          var wk_st = work.work_start_time[k];
          var sk_st = work.sick_start_time[k];

          $('.work-day', this).attr('data-wid',wid);
          $('.work-day', this).text(day);
          $('.input-hour', this).val(hour);
          $('.input-minutes', this).val(minutes);
          $('.input-wk-st', this).val(wk_st);
          $('.input-sk-st', this).val(sk_st);
        });
        calculateHours();
      });

    /*  function openEditDialog(tid,tname) {
        $('#dialog-message').dialog({
          dialogClass: 'no-title',
          draggable: false,
          modal: true,
          width: 400,
          height: "auto",
          open: function() {
            console.log('this',$(this));
            $(this).html('<input id="edit-term" type="text" value="'+tname+'">');
          },
          buttons: {
            Save: function() {
              editDepartment(tid);
            },
            Ok: function() {
              $( this ).dialog( "close" );
            }
          }
        });
      }*/

      $('#edit-dpt-btn').on('click', function(){
        var tid = $(this).data('tid');
        var dpt_name = $('#dpt-name').val().trim();
        var cp_id = $('#dpt-cp').val();
        var url = base_url + '/edit-department';
        var works = [{}];
        $('.work-table-value').each(function(k,v){
            var wid = $('.work-day', this).data('wid');
            var workday = $('.work-day', this).text();
            var hour = $('.input-hour', this).val()+'.'+$('.input-minutes', this).val();
            var wk_st = $('.input-wk-st', this).val();
            var sk_st = $('.input-sk-st', this).val();

            works[k] = {
                'wid' : wid,
                'day' : workday,
                'hour' : hour,
                'wk_st' : wk_st,
                'sk_st' : sk_st
            };
        });
        var data = {
            dpt_name : dpt_name,
            cp_id : cp_id,
            tid : tid,
            works : works
        };

        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
          //Auto refresh page
            $('#page-wrapper').fadeOut("slow").load(base_url+'/departments#page-wrapper').fadeIn("slow");
            setTimeout(function(){
              $('.toolbar-bar')[1].remove();
              alert('Department edited successfully: '+dpt_name);
            },2000);
          }
          else {
            alert('Cannot Edit');
          }
        });

      });


      //Delete department
      $('.delete-department').on('click', function(e){
        var tid = $(this).data('term-id');
        var url = base_url + '/delete-department';
        var data = {
            tid : tid
        }
        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
          //Auto refresh page
            $('#page-wrapper').fadeOut("slow").load(base_url+'/departments#page-wrapper').fadeIn("slow");
            setTimeout(function(){
              $('.toolbar-bar')[1].remove();
              alert('Department deleted successfully');
            },2000);
          }
          else {
            alert('Cannot delete department. It is assigned to users');
          }
        });
      });

      //Show edit members form with data
      $('.edit-member').on('click', function(e){
        $('#edit-member-form form')[0].reset();
        var user_id = $(this).data('user-id');
        $('#edit-member-form').data('user-id',user_id);
        var member = $(this).data('member');
        var departments = $(this).data('dpt');
        var cid = departments[member.department[0]]['cid'];
        //var role = member.role.charAt(0).toUpperCase() + member.role.slice(1);
        $('#edit-member-name').val(member.name);
        $('#edit-member-email').val(member.email);
        $('#edit-member-id-number').val(member.id_number);
        
        //show departments belong to the user
        $('#edit-member-dpt option').hide(); 
        $('#edit-member-dpt').val(member.department[0]);
        $('#edit-member-dpt .dp-opt-'+cid).show();
        
        //show company belongs to the user
        $('#edit-member-cp option').hide();
        $('#edit-member-cp').val(cid);
        $('#edit-member-cp .cp-opt-'+cid).show();
        
        $('#edit-member-role').val(member.role);
        console.log('m',member);
        if(member.receive_email && member.receive_email[user_id]){
          $('.edit-member-form #within-law').prop("checked", true);
          $('.edit-member-form .receive-mail').show();
          if(member.receive_email[user_id]['receive_all']){
            var roles = member.receive_email[user_id]['receive_all'];
            if(roles.indexOf('administrator') > -1 || roles.indexOf('su admin') > -1){
              $('.edit-member-form #admin-receive-all').prop("checked", true);
            }
            if(roles.indexOf('chief') > -1){
              $('.edit-member-form #chief-receive-all').prop("checked", true);
            }
          }
          if(member.receive_email[user_id]['receive_warning']){
            var roles = member.receive_email[user_id]['receive_warning'];
            if(roles.indexOf('administrator') > -1 || roles.indexOf('su admin') > -1){
              $('.edit-member-form #admin-receive-warning').prop("checked", true);
            }
            if(roles.indexOf('chief') > -1){
              $('.edit-member-form #chief-receive-warning').prop("checked", true);
            }
          }
        }
        else{
          $('.edit-member-form #without-law').prop("checked", true);
          $('.edit-member-form .receive-mail input').prop("checked", false);
          $('.edit-member-form .receive-mail').hide();
        }

        $('.edit-member-form #dpt-name').text(departments[member.department[0]]['dpt_name']);

        if(member.special_hours && member.special_hours[user_id]){
          $('.edit-member-form #input-spl-hours').prop('checked',true);

          $('.edit-member-form .work-table-value').each(function(k,v){
            var wid = member.special_hours[user_id].wid[k];
            $('.work-day', this).attr('data-wid',wid);
            $('.input-hour', this).val(member.special_hours[user_id]['workhours'][k].split('.')[0]);
            $('.input-minutes', this).val(member.special_hours[user_id]['workhours'][k].split('.')[1]);
            $('.input-wk-st', this).val(member.special_hours[user_id]['work_start_time'][k]);
            $('.input-sk-st', this).val(member.special_hours[user_id]['sick_start_time'][k]);
          });
          if($('.edit-member-form #input-spl-hours').is(':checked')){
            $('.w-hours-table input').prop('disabled', false);
            $('.w-hours-table').css('opacity', '1');
          }
          else{
            $('.w-hours-table input').prop('disabled', true);
            $('.w-hours-table').css('opacity', '0.7');
          }
        }
        else{
          $('.edit-member-form #input-dpt-name').prop('checked',true);
          $('.w-hours-table input').prop('disabled', true);
          $('.w-hours-table').css('opacity', '0.7');

          $('.edit-member-form .work-table-value').each(function(k,v){
            $('.input-hour', this).val(member.works[member.department[0]]['workhours'][k].split('.')[0]);
            $('.input-minutes', this).val(member.works[member.department[0]]['workhours'][k].split('.')[1]);
            $('.input-wk-st', this).val(member.works[member.department[0]]['work_start_time'][k]);
            $('.input-sk-st', this).val(member.works[member.department[0]]['sick_start_time'][k]);
          });

        }
        var isEditForm = true;
        calculateHours(isEditForm);

        $('#add-member-form').hide();
        $('#edit-member-form').show();
      });

      //Disable input when department checked in members page
      if($('#input-dpt-name').is(':checked')){
        $('.w-hours-table input').prop('disabled', true);
        $('.w-hours-table').css('opacity', '0.7');
      }
      $('#add-member-form input[name=toggle-work]').on('change',function(){
        if($('#add-member-form #input-dpt-name').is(':checked')){
          $('#add-member-form .w-hours-table input').prop('disabled', true);
          $('#add-member-form .w-hours-table').css('opacity', '0.7');
        }
        else{
          $('#add-member-form .w-hours-table input').prop('disabled', false);
          $('#add-member-form .w-hours-table').css('opacity', '1');
        }
      });

      $('.edit-member-form input[name=toggle-work]').on('change',function(){
        if($('.edit-member-form #input-dpt-name').is(':checked')){
          $('.edit-member-form .w-hours-table input').prop('disabled', true);
          $('.edit-member-form .w-hours-table').css('opacity', '0.7');
        }
        else{
          $('.edit-member-form .w-hours-table input').prop('disabled', false);
          $('.edit-member-form .w-hours-table').css('opacity', '1');
        }
      });

      //set dpt value on selecting the department names
      $('#member-dpt').on('change', function(){
        var dpt_id = $(this).val();
        var works = JSON.parse($('#works').val());
        console.log('wor',works);
        $('#dpt-name').text(works[dpt_id]['term_name']);
        $('.work-table-value').each(function(k,v){
          $('.input-hour', this).val(works[dpt_id]['workhours'][k].split('.')[0]);
          $('.input-minutes', this).val(works[dpt_id]['workhours'][k].split('.')[1]);
          $('.input-wk-st', this).val(works[dpt_id]['work_start_time'][k]);
          $('.input-sk-st', this).val(works[dpt_id]['sick_start_time'][k]);
        });
      });
      $('#edit-member-dpt').on('change',function(){
        var dpt_id = $(this).val();
        var works = JSON.parse($('#works').val());
        console.log('w',works);
        $('.edit-member-form #dpt-name').text(works[dpt_id]['term_name']);
        $('.edit-member-form .work-table-value').each(function(k,v){
          $('.input-hour', this).val(works[dpt_id]['workhours'][k].split('.')[0]);
          $('.input-minutes', this).val(works[dpt_id]['workhours'][k].split('.')[1]);
          $('.input-wk-st', this).val(works[dpt_id]['work_start_time'][k]);
          $('.input-sk-st', this).val(works[dpt_id]['sick_start_time'][k]);
        });
      });

      //Cancel edit form
      $('.cancel-edit').on('click', function(e){
        e.preventDefault();
        $('#add-member-form').show();
        $('#edit-member-form').hide();
      });

      //Edit members form - Update edited members data
      $('#edit-member-form form').on('submit',function(e){
        e.preventDefault();
        $('.loading-gif').show();
        var url =  base_url + '/edit-member';

        var user_id = $('#edit-member-form').data('user-id');
        var member_name = $('#edit-member-name').val();
        var member_email = $('#edit-member-email').val();
        var member_id_number = $('#edit-member-id-number').val();
        var member_cp = $('#edit-member-cp').val();
        var member_dpt = $('#edit-member-dpt').val();
        var member_role = $('#edit-member-role').val();
        var is_within_law = parseInt($('#edit-member-form input[name=120-law]:checked',this).val());
        var is_special_hours = parseInt($('#edit-member-form input[name=toggle-work]:checked',this).val());
        console.log('is',is_special_hours);
        if(is_within_law){
          var receive_all = [];
          var receive_warning = [];
          $('#edit-member-form .receive-mail input[name=receive-all]:checked',this).each(function(){
            receive_all.push($(this).data('role'));
          });
          $('#edit-member-form .receive-mail input[name=receive-warning]:checked',this).each(function(){
            receive_warning.push($(this).data('role'));
          });
        }
        if(receive_all){
          receive_all = receive_all.join();
        }
        if(receive_warning){
          receive_warning = receive_warning.join();
        }

        var special_hours = [{}];
        if(is_special_hours){
          $('#edit-member-form .work-table-value').each(function(k,v){
            var wid = $('.work-day', this).data('wid');
            var workday = $('.work-day', this).text();
            var hour = $('.input-hour', this).val()+'.'+$('.input-minutes', this).val();
            var wk_st = $('.input-wk-st', this).val();
            var sk_st = $('.input-sk-st', this).val();

            special_hours[k] = {
                'wid' : wid,
                'day' : workday,
                'hour' : hour,
                'wk_st' : wk_st,
                'sk_st' : sk_st
            };
          });
        }

        var data = {
            user_id : user_id,
            member_name : member_name,
            member_email : member_email,
            member_id_number : member_id_number,
            member_cp : member_cp,
            member_dpt : member_dpt,
            member_role : member_role,
            is_within_law : is_within_law,
            receive_all : receive_all,
            receive_warning : receive_warning,
            special_hours : special_hours
        };

        console.log('data',data);
        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
            $('#edit-member-form form')[0].reset();
            $('#edit-member-form').data('user-id','');

            //Auto refresh page
            $('#page-wrapper').fadeOut("slow").load(base_url+'/members#page-wrapper').fadeIn("slow");
            setTimeout(function(){
              $('.toolbar-bar')[1].remove();
              alert('Member details updated: '+response.username);
            },2000);
          }
          else {
            alert('Cannot edit member.');
          }
        });
      });

      //Delete Members
      $('.delete-member').on('click',function(e){
        var user_id = $(this).data('user-id');
        var url =  base_url + '/delete-member';
        var data = {
            user_id : user_id
        }

        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
          //Auto refresh page
            $('#page-wrapper').fadeOut("slow").load(base_url+'/members#page-wrapper').fadeIn("slow");
            setTimeout(function(){
              $('.toolbar-bar')[1].remove();
              alert('Member deleted successfully: '+response.username);
            },2000);
          }
          else {
            alert('Cannot delete member. Sick data exists.');
          }
        });
      });

      //User password update
      $('#user-settings-page form').on('submit', function(e){
        e.preventDefault();
        var user_id = $('#user-id').val();
        var email = $('#user-email').val();
        var new_password = $('#new-password').val();
        var confirm_password = $('#confirm-password').val();
        var url =  base_url + '/change-password';

        if(new_password == confirm_password){
          $('.loading-gif').show();
          var data = {
              user_id : user_id,
              email : email,
              new_password : new_password
          }

          $.post(url,data,function(response){
            if(response.result == 'OK') {
              $('.loading-gif').hide();
              var msg = '<p>Saved</p>';
              showFlash(msg);
            }
            else {
              alert('Cannot delete member. Sick data exists.');
            }
          });
        }
        else{
          alert('Password does not match. Please re-enter.');
        }
      });

      //Expand sick stats
      $('.expand').on('click',function(e){
        var user_id = $(this).data('user-id');
        var member = $(this).data('member');
        $('#user-info').data('user-id',user_id);
        $('#user-name').text(member.username);
        $('#sick-status div').text(member.status);
        $('#id-number div').text(member.id_number);
        $('#department div').text(member.department);
        $('#stats-sickdays div').text(member.sickdays);
        $('#stats-last-sick div').text(member.last_sick_date);
        $('#stats-work-days div').text(member.total_working_days);

        $('#dashboard-page').hide();
        $('#statistics-page').show();
        /*var data = {
            user_id : user_id,
        }
        var url = base_url + '/statistics';

        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
            location.href = base_url + '/statistics';
          }
        });*/

      });

      $('#ex-ok-btn').on('click', function(e){
        var user_id = $(this).data('uid');
        var stats_begin = $('#ex-from').val();
        var stats_end = $('#ex-to').val();
        var url =  base_url + '/get-stats-data';
        var data = {
            user_id : user_id,
            stats_begin : stats_begin,
            stats_end : stats_end
        }
        $.post(url,data,function(response){
          if(response.result == 'OK') {
            $('.loading-gif').hide();
            $('#stats-last-sick > div').text(response.data.last_sick_date);
            $('#stats-sickdays > div').text(response.data.sickdays+' days');
          }
          else {
            alert('Cannot get sick data.');
          }
        });
      });


      //Sync all button events in departments page
      $('#sync-hours').on('change', function(e){
        if($(this).prop('checked')){
          var sync_hour_val = $('.input-hour').first().val();
          var sync_minutes_val = $('.input-minutes').first().val();
          $('.input-hour').val(sync_hour_val);
          $('.input-minutes').val(sync_minutes_val);
          calculateHours();
        }
      });
      $('#edit-member-form #sync-hours').on('change', function(e){
        if($(this).prop('checked')){
          var sync_hour_val = $('#edit-member-form .input-hour').first().val();
          var sync_minutes_val = $('#edit-member-form .input-minutes').first().val();
          $('#edit-member-form .input-hour').val(sync_hour_val);
          $('#edit-member-form .input-minutes').val(sync_minutes_val);
          calculateHours(true);
        }
      });
      $('#sync-work-start').on('change', function(e){
        if($(this).prop('checked')){
          var wk_st_val = $('.input-wk-st').first().val();
          $('.input-wk-st').val(wk_st_val);
        }
      });

      $('#edit-member-form #sync-work-start').on('change', function(e){
        if($(this).prop('checked')){
          var wk_st_val = $('#edit-member-form .input-wk-st').first().val();
          $('#edit-member-form .input-wk-st').val(wk_st_val);
        }
      });

      $('#sync-sick-start').on('change', function(e){
        if($(this).prop('checked')){
          var sk_st_val = $('.input-sk-st').first().val();
          $('.input-sk-st').val(sk_st_val);
        }
      });

      $('#edit-member-form #sync-sick-start').on('change', function(e){
        if($(this).prop('checked')){
          var sk_st_val = $('#edit-member-form .input-sk-st').first().val();
          $('#edit-member-form .input-sk-st').val(sk_st_val);
        }
      });
      //Calculate total work days per week
      $('input[class*=input-]').on('keyup',function(){
        calculateHours();
      });

      function calculateHours(isEditForm){
        var sum_hours = 0;
        var sum_minutes = 0;
        $('.input-hour').each(function() {
          sum_hours += Number($(this).val());
        });
        $('.input-minutes').each(function() {
          sum_minutes += Number($(this).val());
        });
        var totalHours = sum_hours + parseInt(sum_minutes/60);
        var totalMinutes = sum_minutes%60;
        if(isEditForm){
          $('.edit-member-form #total-hour').text(totalHours);
          $('.edit-member-form #total-minutes').text(totalMinutes);
        }
        else{
          $('#total-hour').text(totalHours);
          $('#total-minutes').text(totalMinutes);
        }
      }

      //Hide/show receive mail radio buttons
      $('.law').on('change',function(){
        if($('#without-law').is(':checked')){
            $('.receive-mail').hide();
        }
        else{
            $('.receive-mail').show();
        }
      });
      $('.edit-member-form .law').on('change',function(){
        if($('.edit-member-form #without-law').is(':checked')){
            $('.edit-member-form .receive-mail').hide();
        }
        else{
            $('.edit-member-form .receive-mail').show();
        }
      });

     //Hide show single day and period
     $('.sickday-choice input[name=add-sick-day]').on('change',function(){
       if($('.sickday-choice #period').is(':checked')){
         $('.add-sick-days-wrapper #period-wrapper').show();
       }
       else{
         $('.add-sick-days-wrapper #period-wrapper').hide();
       }
     });
     $('.rm-sickday-choice input[name=rm-sick-day]').on('change',function(){
       if($('.rm-sickday-choice #rm-period').is(':checked')){
         $('.remove-sick-days-wrapper #rm-period-wrapper').show();
       }
       else{
         $('.remove-sick-days-wrapper #rm-period-wrapper').hide();
       }
     });


     //Hide show period in special agreement
     $('#special-ag-users input[name=ag-sd-select]').on('change',function(){
       if($('#special-ag-users #ag-period').is(':checked')){
         $('#special-ag-users .ag-period-wrapper').removeClass('lm-hidden');
       }
       else{
         $('#special-ag-users .ag-period-wrapper').addClass('lm-hidden');
       }
     })

     //Add more in special agreement
     $('#sd-plus').on('click',function(){
       var html = $('.ag-sd-wrapper div').first().clone();
       $('.ag-sd-wrapper div').first().after(html);
     });

     $('#pd-plus').on('click',function(){
       var html = $('.ag-period-wrapper div').first().clone();
       $('.ag-period-wrapper div').first().after(html);
     });

     //Search user names or ids
    /* $('#search-user').on('keyup',function(){
       var search_value = $(this).val().toLowerCase();
       var mObj = JSON.parse($('#members').val());
       var memberNames = [];
       $.each(mObj,function(k,v){
         memberNames.push(v.name);
       });
       $(memberNames).filter(function(){
         $(this).toggle($(this).text().toLowerCase().indexOf(search_value) > -1)
       });

     });*/
     if($('#members').val() && $('body').find('#search-user').length){
       var mObj = JSON.parse($('#members').val());
       console.log('m',mObj);
       var memberNames = [];
       var names = [];
       var id = [];
       $.each(mObj,function(k,v){
           names.push(v.name);
           id.push(v.id_number);
       });
       memberNames.push(names);
       memberNames.push(id);

       autocomplete(document.getElementById("search-user"), memberNames);
     }

    /* if($('#companies').val() && $('body').find('#search-company').length){
      var mObj = JSON.parse($('#companies').val());
      console.log('m',mObj);
      var companyNames = [];
      var names = [];
      $.each(mObj,function(k,v){
          names.push(v.company_name);
      });
      companyNames.push(names);

      autocomplete(document.getElementById("search-company"), companyNames);
    }*/

     function autocomplete(inp, array) {
       /*the autocomplete function takes two arguments,
       the text field element and an array of possible autocompleted values:*/
       var currentFocus;
       /*execute a function when someone writes in the text field:*/
       inp.addEventListener("input", function(e) {
           var a, b, i, val = this.value;
           /*close any already open lists of autocompleted values*/
           closeAllLists();
           if (!val) { return false;}
           currentFocus = -1;
           /*create a DIV element that will contain the items (values):*/
           a = document.createElement("DIV");
           a.setAttribute("id", this.id + "autocomplete-list");
           a.setAttribute("class", "autocomplete-items");
           /*append the DIV element as a child of the autocomplete container:*/
           this.parentNode.appendChild(a);
           /*for each item in the array...*/
           var arr = [];
           if(val.match(/[a-z]/i)){
             arr = array[0];
           }
           else{
             arr = array[1];
           }
           console.log('arr',array);
           for (i = 0; i < arr.length; i++) {
             /*check if the item starts with the same letters as the text field value:*/
             if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
               /*create a DIV element for each matching element:*/
               b = document.createElement("DIV");
               /*make the matching letters bold:*/
               b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
               b.innerHTML += arr[i].substr(val.length);
               /*insert a input field that will hold the current array item's value:*/
               b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
               /*execute a function when someone clicks on the item value (DIV element):*/
               b.addEventListener("click", function(e) {
                   /*insert the value for the autocomplete text field:*/
                   inp.value = this.getElementsByTagName("input")[0].value;
                   /*close the list of autocompleted values,
                   (or any other open lists of autocompleted values:*/
                   closeAllLists();
               });
               a.appendChild(b);
             }
           }
       });
       /*execute a function presses a key on the keyboard:*/
       inp.addEventListener("keydown", function(e) {
           var x = document.getElementById(this.id + "autocomplete-list");
           if (x) x = x.getElementsByTagName("div");
           if (e.keyCode == 40) {
             /*If the arrow DOWN key is pressed,
             increase the currentFocus variable:*/
             currentFocus++;
             /*and and make the current item more visible:*/
             addActive(x);
           } else if (e.keyCode == 38) { //up
             /*If the arrow UP key is pressed,
             decrease the currentFocus variable:*/
             currentFocus--;
             /*and and make the current item more visible:*/
             addActive(x);
           } else if (e.keyCode == 13) {
             /*If the ENTER key is pressed, prevent the form from being submitted,*/
             e.preventDefault();
             if (currentFocus > -1) {
               /*and simulate a click on the "active" item:*/
               if (x) x[currentFocus].click();
             }
           }
       });
       function addActive(x) {
         /*a function to classify an item as "active":*/
         if (!x) return false;
         /*start by removing the "active" class on all items:*/
         removeActive(x);
         if (currentFocus >= x.length) currentFocus = 0;
         if (currentFocus < 0) currentFocus = (x.length - 1);
         /*add class "autocomplete-active":*/
         x[currentFocus].classList.add("autocomplete-active");
       }
       function removeActive(x) {
         /*a function to remove the "active" class from all autocomplete items:*/
         for (var i = 0; i < x.length; i++) {
           x[i].classList.remove("autocomplete-active");
         }
       }
       function closeAllLists(elmnt) {
         /*close all autocomplete lists in the document,
         except the one passed as an argument:*/
         var x = document.getElementsByClassName("autocomplete-items");
         for (var i = 0; i < x.length; i++) {
           if (elmnt != x[i] && elmnt != inp) {
             x[i].parentNode.removeChild(x[i]);
           }
         }
       }
       /*execute a function when someone clicks in the document:*/
       document.addEventListener("click", function (e) {
           closeAllLists(e.target);
       });
     }

     $('#search-userautocomplete-list strong').on('click',function(){
        var input = $(this).val();
        console.log('input',input);
     });

     //Add single day special agreement
     $('#ag-sd-send').on('click',function(e){

       var user = $('#search-user').val();
       var date_wrapper = $('.ag-sd-date');
       var ag_date = [], ag_day = [], ag_from_time = [], ag_to_time = [], ag_sick_time = [];
       var dt = new Date();
       var current_time = dt.getHours() +':'+dt.getMinutes();

       for(var x=0; x<date_wrapper.length; x++){
         ag_date.push($($('.ag-sd-date')[x]).val());
         ag_day.push($($('.ag-sd-date')[x]).prev().text());
         ag_from_time.push($($('.ag-sd-from')[x]).val());
         ag_to_time.push($($('.ag-sd-to')[x]).val());
         ag_sick_time.push($($('.ag-sd-start-time')[x]).val());
       }
       var url =  base_url + '/add-single-agreement';
       var data = {
           user : user,
           ag_date : ag_date,
           ag_day : ag_day,
           ag_from_time : ag_from_time,
           ag_to_time : ag_to_time,
           ag_sick_time : ag_sick_time,
       };

       $.post(url,data,function(response){
         if(response.result == 'OK') {
           $('.loading-gif').hide();
         //Auto refresh page
           $('#page-wrapper').fadeOut("slow").load(base_url+'/health#page-wrapper').fadeIn("slow");
           setTimeout(function(){
             $('.toolbar-bar')[1].remove();
             $('.tab-menu .add-agreement').trigger('click');

             $('#add-agreement-notification').removeClass('lm-hidden');
             $('#add-agreement-notification .modal-day').text(ag_day);
             $('#add-agreement-notification .modal-date').text(ag_date);
             $('#add-agreement-notification .modal-time').text(current_time);
             $('#add-agreement-notification .regd-sick-day-from').text(ag_day);
             $('#add-agreement-notification .regd-sick-date-from').text(ag_date);
             $('#add-agreement-notification .regd-sick-day-to').text(ag_day);
             $('#add-agreement-notification .regd-sick-date-to').text(ag_date);
             $('#add-agreement-notification .regd-sickdays').text('1');
           },3000);
           $('.tab-menu .add-agreement').trigger('click');

         }
         else {
           alert('Agreement cannot be added.');
         }
       });

     });

     $('#ag-period-send').on('click',function(e){
       var user = $('#search-user').val();
       var date_wrapper = $('.ag-period-dt-from');
       var ag_date_from = [], ag_date_to = [], ag_day_from = [], ag_from_time = [], ag_to_time = [], ag_sick_time = [];

       for(var x=0; x<date_wrapper.length; x++){
         ag_date_from.push($($('.ag-period-dt-from')[x]).val());
         ag_date_to.push($($('.ag-period-dt-to')[x]).val());
         ag_from_time.push($($('.ag-period-from')[x]).val());
         ag_to_time.push($($('.ag-period-to')[x]).val());
         ag_sick_time.push($($('.ag-period-start-time')[x]).val());
       }
       var url =  base_url + '/add-period-agreement';
       var data = {
           user : user,
           ag_date_from : ag_date_from,
           ag_date_to : ag_date_to,
           ag_from_time : ag_from_time,
           ag_to_time : ag_to_time,
           ag_sick_time : ag_sick_time,
       };

       $.post(url,data,function(response){
         if(response.result == 'OK') {
           $('.loading-gif').hide();
         //Auto refresh page
           $('#page-wrapper').fadeOut("slow").load(base_url+'/health#page-wrapper').fadeIn("slow");
           setTimeout(function(){
             $('.toolbar-bar')[1].remove();
             $('.tab-menu .add-agreement').trigger('click');

             $('#add-agreement-notification').removeClass('lm-hidden');
             $('#add-agreement-notification .modal-day').text(response.today);
             $('#add-agreement-notification .modal-date').text(response.current_date);
             $('#add-agreement-notification .modal-time').text(response.current_time);
             $('#add-agreement-notification .regd-sick-day-from').text(response.start_day);
             $('#add-agreement-notification .regd-sick-date-from').text(response.start_date);
             $('#add-agreement-notification .regd-sick-day-to').text(response.end_day);
             $('#add-agreement-notification .regd-sick-date-to').text(response.end_date);
             $('#add-agreement-notification .regd-sickdays').text(response.sickdays);

           },3000);
           $('.tab-menu .add-agreement').trigger('click');

         }
         else {
           alert('Agreement period cannot be added.');
         }
       });

     });


     //Expand event for special agreement
     $('.ag-expand').on('click',function(){
       var ag = $(this).data('ag');
       var ag_uid = $(this).data('ag-uid');
       $('#ag-modal').removeClass('lm-hidden');
       $('tr[class*="ag-user-"]').addClass('lm-hidden');
       $('.ag-user-'+ag_uid).removeClass('lm-hidden');
       $('tr[class*="ag-data-user-"]').addClass('lm-hidden');
       $('.ag-data-user-'+ag_uid).removeClass('lm-hidden');
     });

     //Edit/Save Special Agreement
     $('.edit-ag').on('click',function(){
       var currentTD = $(this).parents('tr').find('td');
       if (this.src.indexOf('edit_icon.png') > -1) {
           $.each(currentTD, function () {
               $(this).prop('contenteditable', true)
               $(this).css('background','white');
           });
           $(this).toggleClass('edit-ag save-ag');
           this.src = this.src.replace('edit_icon','save_icon');
       } else {
          $.each(currentTD, function () {
               $(this).prop('contenteditable', false)
               $(this).css('background','unset');
           });
          $(this).toggleClass('save-ag edit-ag');
          this.src = this.src.replace('save_icon','edit_icon');

          var currentTr = $(this).parents('tr');
          var ag_id = $(this).data('ag-id');
          var ag_date_from = $('.ag-date-from',currentTr).html();
          var ag_date_to = $('.ag-date-to',currentTr).html();
          var ag_time_from = $('.ag-time-from',currentTr).html();
          var ag_time_to = $('.ag-time-to',currentTr).html();

          var url =  base_url + '/edit-period-agreement';
          var data = {
              ag_id : ag_id,
              ag_date_from : ag_date_from,
              ag_date_to : ag_date_to,
              ag_time_from : ag_time_from,
              ag_time_to : ag_time_to
          };
          console.log('data',data);

          $.post(url,data,function(response){
            if(response.result == 'OK') {
              $('.loading-gif').hide();
              alert('Agreement period saved');
            }
            else {
              alert('Agreement period cannot be saved.');
            }
          });

       }
     });

     //Detele special agreement
     $('.delete-ag').on('click',function(){
       var ag_id = $(this).data('ag-id');

       var url =  base_url + '/delete-period-agreement';
       var data = {
           ag_id : ag_id,
       };

       $.post(url,data,function(response){
         if(response.result == 'OK') {
           $('.loading-gif').hide();
         //Auto refresh page
           $('#page-wrapper').fadeOut("slow").load(base_url+'/health#page-wrapper').fadeIn("slow");
           setTimeout(function(){
             $('.toolbar-bar')[1].remove();
             $('.tab-menu .add-agreement').trigger('click');
             alert('Agreement deleted');
           },2000);

         }
         else {
           alert('Agreement cannot be deleted.');
         }
       });

     });

     //Expand Agreement in dashboard page
     /*$('.dash-ag-expand').on('click',function(){
       var ag = $(this).data('ag');
       var ag_uid = $(this).data('ag-uid');
       $('#ag-modal').removeClass('lm-hidden');
       $('tr[class*="ag-user-"]').addClass('lm-hidden');
       $('.ag-user-'+ag_uid).removeClass('lm-hidden');
       $('tr[class*="ag-data-user-"]').addClass('lm-hidden');
       $('.ag-data-user-'+ag_uid).removeClass('lm-hidden');
     });*/

     //Close Agreement modal
     $('.close-btn').on('click',function(){
       $(this).parents('#ag-modal').addClass('lm-hidden');
     });

     //Filter users table in dashboard page
     $("#search-user").on("keyup", function() {
       var value = $(this).val().toLowerCase();
       $("#user-table tbody tr").filter(function() {
         $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
       });
     });

     //Filter companies table in companies page
     $("#search-company").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      $("#companies-table tbody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
      });
    });


     if($('body').hasClass('path-statistics')){
       renderChart();
     }

     function renderChart(){
       var monthly_stats = JSON.parse($('#monthly-stats').val());
       console.log(monthly_stats);
       var statsObj = {};
       var statsData = [];
       $.each(monthly_stats, function(k,v){
         statsObj[v.month+'-'+v.year] = v.sickdays;
       });

       statsData = Object.keys(statsObj).map(function(data){
         return [data,statsObj[data]];
       });

       console.log('sD',statsData);

       $('#chart').barChart({vertical : false,
         height: 200,
         bars : [
           {
             name : 'Dataset 1',
             values : statsData
           }
         ],
         hiddenBars : [],
         milestones : [],
         colors : [
           "#f44336", "#e91e63", "#9c27b0", "#673ab7", "#3f51b5",
           "#2196f3", "#03a9f4", "#00bcd4", "#009688", "#4caf50",
           "#8bc34a", "#cddc39", "#ffeb3b", "#ffc107", "#ff9800",
           "#ff5722", "#795548", "#9e9e9e", "#607d8b", "#263238"
         ],
           barColors : {},
           dateFormat : 'MM',
           barGap : 5,
           totalSumHeight : 25,
           defaultWidth : 40,
           defaultColumnWidth : 65
       });

       $('.bar-title').each(function(){
         var titleVal = $(this).text().split('-')[0];
         $(this).text(titleVal);
       });

       $('.bar-value-sum').each(function(){
         var barVal = $(this).text() <= '1' ? $(this).text() + ' day' : $(this).text() + ' days';
         if($(this).text() == '0'){
           $(this).text('');
         }
         else{
           $(this).text(barVal);
         }

       });
     }


     //Download as excel

     var tableToExcel = (function () {
       var uri = 'data:application/vnd.ms-excel;base64,',
           template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>{table}</table></body></html>',
           base64 = function (s) {
               return window.btoa(unescape(encodeURIComponent(s)))
           }, format = function (s, c) {
               return s.replace(/{(\w+)}/g, function (m, p) {
                   return c[p];
               })
           }
       return function (table, name, filename) {
           if (!table.nodeType) table = document.getElementById(table)
           var ctx = {
               worksheet: name || 'Worksheet',
               table: table.innerHTML
           }

           document.getElementById("dlink").href = uri + base64(format(template, ctx));
           document.getElementById("dlink").download = filename;
           document.getElementById("dlink").traget = "_blank";
           document.getElementById("dlink").click();

       }
		})();

		function download(){
			 $(document).find('tfoot').remove();
			 var member = JSON.parse($('#stats-member').val());
			 var table_elem = '<table id="stats-table" class="lm-hidden"><tr><td>ID number</td><td>Username</td>'+
			 '<td>Department</td><td>Sick days</td><td>Last Sick Date</td>'+
			 '<td>Health Status</td></tr>'+
			 '<tr><td>'+member.id_number+'</td><td>'+member.name+'</td><td>'+member.departments+'</td>'+
			 '<td>'+member.sickdays+'</td><td>'+member.last_sick_date+'</td><td>'+member.ag_user+'</td>';
			 if(member.health_status){
				 table_elem += '<td>'+member.health_status+'</td>';
			 }
			 else{
				 table_elem += '<td>Healthy</td>';
			 }
			 table_elem += '</tr></table>';
			 $('#statistics-page').append(table_elem);
			 var name = 'Sickdays-report';
			 tableToExcel('stats-table', 'Sheet 1', name+'.xls')
			 //setTimeout("window.location.reload()",0.0000001);

		}

		if($('body').hasClass('path-statistics')){
		 var btn = document.getElementById("ex-download");
		 if(btn){
			 btn.addEventListener("click",download);
		 }
		}

		//Sortable tables
		$('.tablesorter').each(function(){
		 var tableId = $(this).attr('id');
		 $('#'+tableId).tablesorter({
			 // options here
			 theme            : 'default',
		 });
		});

		//Close notification
		$('.close-modal').on('click',function(){
		 $(this).parents('.modal').addClass('lm-hidden');
		});


		//Print div
		function printDiv(){

		 var divToPrint=document.getElementById('ag-modal');

		 var newWin=window.open('','Print-Window');

		 newWin.document.open();

		 newWin.document.write('<html><body onload="window.print()">'+divToPrint.innerHTML+'</body></html>');

		 newWin.document.close();

		 setTimeout(function(){newWin.close();},10);

		}

		//Add popup on clicking add company
		$('#add-company-pop').click(function(e){
		 $('#company-modal').removeClass('lm-hidden');
		 $('#edit-cp-btn').addClass('lm-hidden');
		 $('#add-cp-btn').removeClass('lm-hidden');
		});

		//Close Add company popup
		$('#company-modal .close-btn').click(function(){
		 $('#company-modal').addClass('lm-hidden');
		 $('#company-modal form')[0].reset();
		});

		 //Add company
		$('#company-modal form').on('submit', function(e){
			e.preventDefault();
			$('.loading-gif').show();
			var url =  base_url + '/add-company';
			var cp_name = $('#cp-name').val();
			var cp_email = $('#cp-email').val();
			var cp_phone = $('#cp-phone').val();
			var cp_address = $('#cp-address').val();
			var cp_card = $('#cp-card').val();
			var cid = $('#edit-cp-btn').data('cid');

			var data = {
				cp_name : cp_name,
				cp_email : cp_email,
				cp_phone : cp_phone,
				cp_address : cp_address,
				cp_card : cp_card,
				cid : cid,
			};

			$.post(url,data,function(response){
				if(response.result == 'OK') {
				$('.loading-gif').hide();
				$('#company-modal form')[0].reset();
				$('#edit-cp-btn').attr('data-cid', '');
				//Auto refresh page
				$('#page-wrapper').fadeOut("slow").load(base_url+'/companies#page-wrapper').fadeIn("slow");
				setTimeout(function(){
					$('.toolbar-bar')[1].remove();
					if(cid == null || cid == 'undefined'){
								alert('Company added: '+cp_name);
							}
							else{
								alert('Company Edited: '+cp_name);
							}
				},2000);

				}
				else {
						if(cid == null || cid == 'undefined'){
							alert('Cannot add company. Please check the details you entered');
						}
						else{
							alert('Cannot edit company. Please check the details you entered');
						}	
				}
			});
		});
		
		//Edit Company popup
		$('.edit-company-pop').click(function(){
			var companies = $(this).data('cp');
			console.log(companies.cp_email);
			$('#cp-name').val(companies.cp_name);
			$('#cp-email').val(companies.cp_email);
			$('#cp-phone').val(companies.cp_phone);
			$('#cp-address').val(companies.cp_address);
			$('#cp-card').val(companies.credit_card);
			$('#company-modal').removeClass('lm-hidden');
			$('#add-cp-btn').addClass('lm-hidden');
			$('#edit-cp-btn').removeClass('lm-hidden');
			$('#edit-cp-btn').attr('data-cid', $(this).data('cid'));
		});
	
	
		//Delete company
		$('.delete-company').click(function(){
			$('.loading-gif').show();
			var url =  base_url + '/delete-company';
			var cid = $(this).data('cid');
			var cp_name = $(this).data('cp-name');
			
			data = {
					cid : cid
			}
			
			$.post(url,data,function(response){
				if(response.result == 'OK') {
				$('.loading-gif').hide();
				//Auto refresh page
				$('#page-wrapper').fadeOut("slow").load(base_url+'/companies#page-wrapper').fadeIn("slow");
				setTimeout(function(){
					$('.toolbar-bar')[1].remove();
						alert('Company deleted: '+cp_name);
				},2000);

				}
				else {
						alert('Cannot delete company. Please check');	
				}
			});
			
		});
		
		//Show or hide departments on switching Company
		$('#member-cp').on('change',function(){
			var cid = $(this).val();
			$('#member-dpt').attr('multiple','multiple');
			$('#member-dpt option').hide();
			$('#member-dpt .dp-opt-'+cid).show();
		}); 
	

  }); //document ready function
 /*   }
  }*/

})(jQuery, Drupal);
