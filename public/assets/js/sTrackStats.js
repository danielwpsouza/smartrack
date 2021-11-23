(function( $ ) {
	"use strict";
	
	var _ST = $.fn.smarTrack = function(){},
		args = sTrackStatsArgs;

	$.extend(true, _ST, {
		


		/**
		 * Save click actions
		 */
		strack_click_action: function(){
			$('body').find('.strack_bnr').on('click', function(e){
				var elm = $(this),
					scaleX = elm[0].getBoundingClientRect().width / elm.width(),
					scaleY = elm[0].getBoundingClientRect().height / elm.height(),
					xPos = e.pageX - elm.offset().left,
					yPos = e.pageY - elm.offset().top;
			
				xPos = xPos / scaleX,
				yPos = yPos / scaleY;
				console.log($(this).data('bid'));
				
				console.log(xPos+','+yPos);
				$(this).smarTrack.track_event_data({
					'event_type': 'click',
					'position':[xPos, yPos], 
					'id_1': $(this).data('bid'),
					'id_2': $(this).data('aid')
				});
			});
		},
		
		
		/**
		 * WPPAS BANNER LINK CLICKS
		 *
		 *
		 */
		wppas_strack_click: function(){
			$('.b_container').on('click', function(e){
				var elm = $(this),
					scaleX = elm[0].getBoundingClientRect().width / elm.width(),
					scaleY = elm[0].getBoundingClientRect().height / elm.height(),
					xPos = e.pageX - elm.offset().left,
					yPos = e.pageY - elm.offset().top;
			
				xPos = xPos / scaleX,
				yPos = yPos / scaleY;
				console.log($(this).data('bid'));
				
				console.log(xPos+','+yPos);
				$(this).smarTrack.track_event_data({
					'event_type': 'click',
					'position':[xPos, yPos], 
					'id_1': $(this).data('bid'),
					'id_2': $(this).data('zn')
				});
			});
		},
	 	/*strack_click: function(){
			$('a').on('click', function(){
				var pv_id = sTrackStatsArgs.id, //$(this).data('strack_st'),
					//ev_id = sTrackEvent.id, //$(this).data('strack_ev'),
					banner_id = $(this).data('bid');
					//content_id = sTrackStatsArgs.content_id; //$(this).data('strack_pid');
				
				if(typeof pv_id != 'undefined'){
					//console.log(pv_id);
					$.ajax({
					   type: "POST",
					   url: args.ajaxurl,
					   data: "action=strack_update_click&strack_st="+pv_id+"&banner_id="+banner_id+"&track_id="+args.track_id
					}).done(function( msg ){
						console.log('done');
						console.log(msg);
					});
				}
			});
		},*/
		
		
		
		
		/**
		 * TRACK PAGE VIEW DATA
		 *
		 */
		track_pview_data: function(data){
			if( typeof args.id != 'undefined' && args.id != ''){	
				var ref = encodeURIComponent(document.referrer),
					screen_w = screen.width,
					screen_h = screen.height,
					reso = window.innerWidth+'x'+window.innerHeight
			
				//console.log(data['e'][0]+' '+data['e'][1]);
				$.ajax({
				   type: "POST",
				   url: args.ajaxurl,
				   data: "action=save_pview_data&id="+args.id+"&track_id="+args.track_id+"&ref="+ref+"&screen_w="+screen_w+"&screen_h="+screen_h+"&reso="+reso
				}).done(function( msg ){
					console.log('done');
					console.log(msg);
				});
			}
		},
		
		
		
		/**
		 * TRACK EVENT DATA
		 *
		 */
		track_event_data: function(data){
			
			if( typeof args.id != 'undefined' && args.id != ''){
				console.log(args.id);
				var ids = {
						'id_1': typeof data['id_1'] != 'undefined' ? data['id_1'] : '',
						'id_2': typeof data['id_2'] != 'undefined' ? data['id_2'] : '',
						'id_3': typeof data['id_3'] != 'undefined' ? data['id_3'] : ''
					},
					event_type = typeof data['event_type'] != 'undefined' ? data['event_type'] : '',
					pos = typeof data['position'] != 'undefined' ? JSON.stringify(data['position']) : '';
				console.log(ids);	
				console.log(pos);	

				// Set timeout to make sure database row gets created first.
				setTimeout(function() {
					$.ajax({
					type: "POST",
					url: args.ajaxurl,
					data: "action=save_event_data&id="+args.id+"&ids="+JSON.stringify(ids)+"&event_type="+event_type+"&track_id="+args.track_id+"&pos="+pos
					}).done(function( msg ){
						//console.log('done');
						console.log(msg);
					});
				}, 500);
			}
		},
		
		
		
		
		/**
		 * smarTrack Filters
		 *
		 */
		filters: function(){
			
			
			// Load filter options
			$('#strack_group').on('change', function(){
				var group = $(this).val();
					
				$('#strack_group_id').hide();
				$('#strack_apply_filter').hide();
				$('.strack.filters').find('.loading').show();
				
				$.ajax({
				   type: "POST",
				   url: args.ajaxurl,
				   data: "action=load_filter_options&group="+group
				}).done(function( msg ){
					//console.log('done');
					//console.log(msg);
					$('#strack_group_id').show();
					$('.strack.filters').find('.loading').hide();
					$('#strack_group_id').html(msg);
				});
			});
			
			// Select filter option
			$('#strack_group_id').on('change', function(){
				var group = $('#strack_group').val(),
					group_id = $(this).val(),
					uri = $('#strack_uri').val();
				
				$('#strack_apply_filter').hide();
				$('.strack.filters').find('.loading').show();
				
				$.ajax({
				   type: "POST",
				   url: args.ajaxurl,
				   data: "action=select_filter_option&group="+group+"&group_id="+group_id+"&uri="+encodeURIComponent(uri)
				}).done(function( msg ){
					//console.log('done');
					//console.log(msg);
					
					$('#strack_apply_filter').show();
					$('.strack.filters').find('.loading').hide();
					$('#strack_apply_filter').attr('href', msg);
				});	
			});
		}
		
	});
	
	
	console.log(sTrackStatsArgs);
	
	if( typeof sTrackStatsArgs != 'undefined' && typeof sTrackStatsArgs.id != 'undefined' && parseInt( sTrackStatsArgs.id ) > 0 ){
		/*console.log(document);*/
		/*var all_links = document.getElementsByTagName( "a" );
		for (var i = 0; i < all_links.length; i++) {
			console.log(all_links[i].href);
			console.log(all_links[i].hostname);
			console.log(location.hostname);
			console.log(document.referrer);
		}*/

		// Add delay to make sure all objects are loaded
		setTimeout(function() {
			$('.b_container').each(function(i, obj) {
				//console.log($(this).attr("href"));
				var _href = $(obj).attr("href");
				
				if( typeof _href != 'undefined' ){
					console.log($(this).attr("href").indexOf('?'));
					var at = _href.indexOf('?') < 0 ? '?' : '&';
					$(obj).attr("href", _href + at+'strack='+sTrackStatsArgs.id);
				}
			});

			$('body').find('.strack_cli').each(function(i, obj) {
				var _href = $(obj).attr("href");
				
				if( typeof _href != 'undefined' ){
					console.log($(this).attr("href").indexOf('?'));
					var at = _href.indexOf('?') < 0 ? '?' : '&';
					$(obj).attr("href", _href + at+'strack='+sTrackStatsArgs.id);
				}
			});


			$(this).smarTrack.track_pview_data();
			$(this).smarTrack.strack_click_action();
			$(this).smarTrack.wppas_strack_click();

		}, 500);
		

		
		
	}
	
	
	
	/*jQuery(document).ready(function($){
		$(this).smarTrack.get_tracking_data();
	});*/
	
}( jQuery ));




