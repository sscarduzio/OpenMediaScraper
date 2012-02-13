$(document).ready(function() {
	
	var ctrlDown = false;
	var appleDown = false;
	var ctrlKey = 17, appleKey = 91, vCode = 86;
	
	$(document).keydown(function(e) {
		console.log(e.keyCode);
		if (e.keyCode == ctrlKey)
			ctrlDown = true;
		if (e.keyCode == appleKey)
			appleDown = true;
	}).keyup(function(e) {
		if (e.keyCode == ctrlKey)
			ctrlDown = false;
		if (e.keyCode == appleKey)
			appleDown = false;
	});

	var textarea = $('#omsTextarea'); 
	var tnail = $('#omsThumbnail');
	var description = $('#omsDescription');
	var title = $('#omsTitle');
	var link = $('.omsLink');
	var source = $('#omsSource');
	
	textarea.focus();
	
	textarea.bind('keyup', function(event){
		content = textarea.val();
		
		// Prevent ctrl-* combinations perform a new query
		if(appleDown || ctrlDown){
			// Ctrl-v still triggers the event
			if(event.keyCode!=vCode){
				return;
			}
		}

		re = /^((http|https)(:\/\/)|www.*\.).*\ $/;
		if( content.length > 10 && re.test(content)){
			$('#down').css('visibility', 'visible').slideDown();
			$('#omsReset').show();
			$('#omsReset').css('visibility', 'visible');
			// DO everithing: unhide #oms and Json query..
			var imgs;
			obj =  {page : content};
			$.ajax({
				  url: "/BACKEND/OpenMediaScraper.php",
				  dataType: 'json',
				  type: 'POST',
				  data: obj,
				  success: function(data) {
				if(data['status']){
					resetMediaBox();
					tnail.show();
					return;
				}
				imgs = data['imgPool'];
				title.text(decodeURIComponent(data['title']));
				source.text(decodeURIComponent(data['url']));
				link.attr("href", data['url']);
				tmp = decodeURIComponent(data['description']);
				if(tmp && tmp.length > 1){
					description.text(tmp);
				}
				else{
					description.text("");
				}
					
				if(imgs && imgs.length > 0){
					tnail.attr("src",imgs[0]);
					tnail.ready(function(){
						tnail.aeImageResize({ height: 250, width: 200 });
					});
					tnail.show();
					tnail.css('visibility', 'visible');
				}
				else{
					tnail.hide();
				}
				window.changeImage = function (add){
					var i = tnail.attr("rel");
					poolSize = imgs.length;
					
					// Handle counter increment
					if(add === true){
						if(i <= poolSize){
							i++;
						}
						else{
							alert('no more images');
						}
					}
					else {
						if(i>0){
							i--;
						}
					}
					tnail.attr("rel", i);
					tnail.ready(function(){
						tnail.aeImageResize({ height: 250, width: 200 });
					});
					tnail.attr("src",imgs[i]);
					tnail.show();
					tnail.css('visibility', 'visible');
				}}
				
			});
		}
		
	});
	
	function resetMediaBox(){
		tnail.attr("rel", "0");
		$('#down').hide();
		$('#omsReset').hide();
		textarea.val("");
		textarea.focus();
		tnail.attr('src', 'img/spinner.gif')
		title.text('Loading title...')
		description.text('Loading description...');
		
	}
	$("#omsReset").click(function(event){
		event.preventDefault();
		resetMediaBox();
	});
	$('#next.omsButton').click(function(event){
		event.preventDefault();
		window.changeImage(true);
	});
	$('#prev.omsButton').click(function(event){
		event.preventDefault();
		window.changeImage(false);
	});	
	
});

/*
 * 
 * So we need storage-cache. NoSQL is good, tephlon is good :) That's because
 * one connection with N objects will not work. Client will wait until the ajax
 * string is completed and then it will parse the JSon string
 * 
 * That's why it's better to implement ajax long polling.
 */