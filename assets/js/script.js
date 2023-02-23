window.$ = jQuery.noConflict();
$(document).ready(function($) {
	$.noConflict();
	$("#fetch_image").click(function(){func_loading(1);func_openbrain_api_image();});
	$("#fetch_content").click(function(){func_loading(1);func_openbrain_api_content();});
	$(document).on("click", '#open_brain_result_image .box .add_image', function() {func_loading(1);func_openbrain_image_save(this);});
	$(document).on("click", '#open_brain_result_content .add_content', function() {func_loading(1);func_openbrain_content_save(this);});
	$("#open_brain_result_content .suggest_title").click(function(){func_loading(1);func_openbrain_api_title();});
});

function func_loading(status) 
{
    if(status){jQuery("#wpwrap" ).append( jQuery( `
    	<div id="loading">
    		<span class="logo plugin_icon_orange"></span>
    		<span class="text">Let me, I do it!</span>
		</div>
	` ) );}
    else{jQuery( "#wpwrap #loading" ).remove();}
}
function func_openbrain_image_save(data)
{
	var box_id = jQuery(data).attr("for");
	var img_url = jQuery(`#${box_id} img`).attr("src");
	var img_title = jQuery(`#${box_id} img`).attr("title");
	var body = {"src":img_url,"title":img_title};
	var settings = 
	{
		"url": `../wp-json/open-brain/v1/image`,
		"method": "POST",
		"data": JSON.stringify(body),
	};
	jQuery.ajax(settings).success(function (response) {
		func_loading(0); 
	  	if(response['status'])
	  	{
	  		jQuery(`#${box_id} input`).val("Added");
	  		jQuery(`#${box_id} input`).removeClass("button-primary");
	  		jQuery(`#${box_id} input`).attr('disabled','disabled');
	  		jQuery(`#${box_id} .box_button`).append(`<a class="button" target="_blank" href="${response['location']}">View</a>`);
	  	}
	});
}
function func_openbrain_content_save(data)
{
	var content = jQuery(`#open_brain_result_content .content`).html();
	var status = jQuery(`#open_brain_content_status`).val();
	var type = jQuery(`#open_brain_content_type`).val();
	var title = jQuery(`#open_brain_content_title`).val();
	var prompt = jQuery(`#open_brain_content_prompt`).val();
	var body = {"content":content,"status":status,"type":type,title:title,prompt:prompt};
	var settings = 
	{
		"url": `../wp-json/open-brain/v1/content`,
		"method": "POST",
		"data": JSON.stringify(body),
	};
	jQuery.ajax(settings).success(function (response) {
		func_loading(0); 
	  	if(response['status'])
	  	{
	  		jQuery(`.add_content`).val("Saved");
	  		jQuery(`.add_content`).removeClass("button-primary");
	  		jQuery(`.add_content`).attr('disabled','disabled');
			jQuery("#open_brain_result_content .view_content").removeClass('d-none');
	  		jQuery('.view_content').attr("href",`./post.php?post=${response['location']}&action=edit`);
	  	}
	});
}
function func_openbrain_api_image()
{
	var prompt = jQuery("#open_brain_image_prompt").val();
	var number = jQuery("#open_brain_image_number").val();
	number = parseInt(number);
	var size = jQuery("#open_brain_image_size").val();
	var body = {prompt:prompt,size:`${size}`,n:number};
	func_openbrain_api("images_generations",body);
}
function func_openbrain_api_content()
{
	var prompt = jQuery("#open_brain_content_prompt").val();
	var token = jQuery("#open_brain_content_token").val();
	token = parseInt(token);
	var temperature = jQuery("#open_brain_content_temperatures").val();
	temperature = parseInt(temperature);
	var body = {prompt:prompt,max_tokens:token,model: "text-davinci-003",temperature: temperature};
	jQuery(`#open_brain_result_content .add_content`).val("Save");
	jQuery(`#open_brain_result_content .add_content`).addClass("button-primary");
	jQuery(`#open_brain_result_content .add_content`).removeAttr('disabled');
	jQuery("#open_brain_result_content .box_button").addClass('d-none');
	jQuery("#open_brain_result_content .content").addClass('d-none');
	jQuery('#open_brain_result_content .view_content').attr("href",`#`);
	func_openbrain_api("completions",body);
}
function func_openbrain_api_title()
{
	var prompt = jQuery("#open_brain_result_content .content").html();
	prompt = prompt.replace(/(<([^>]+)>)/ig,"");
	var temperature = jQuery("#open_brain_content_temperatures").val();
	temperature = parseInt(temperature);
	var body = {prompt:prompt,max_tokens:30,model: "text-davinci-003",temperature: temperature};
	func_openbrain_api("completions_title",body);
}
function func_openbrain_api(type,body)
{
	var settings = 
	{
		"url": `../wp-json/open-brain/v1/api?action=${type}`,
		"method": "POST",
		"data": JSON.stringify(body),
	};
	jQuery.ajax(settings).success(function (response) {
		console.log(response);
		console.log('ok');
		func_loading(0); 
	  	if(type == "images_generations"){func_open_brain_image(response,body)}
	  	else if(type == "completions"){func_open_brain_content(response,body)}
	  	else if(type == "completions_title"){func_open_brain_content_title(response,body)}
	});
	jQuery.ajax(settings).error(function (response) {
		console.log(response);
		console.log('err');

		func_loading(0); 
	});
}
function func_open_brain_image(response,body)
{
	jQuery("#open_brain_result_image").html("");
	var image_title = body['prompt'];
	let counter = 0;
	for (variable of response['result']['data'])
	{
		counter++;
		let x = counter + Math.floor((Math.random() * 100) + 1);
		var img = `
			<div class="box" id="box_${x}">
				<div>
					<img title="${image_title}" src="${variable['url']}"/>
				</div>
				<div class="text-center box_button">
					<input type="button" class="button button-primary add_image" for="box_${x}" value="Add to gallery"/>
				</div>
			</div>
			`;
		jQuery("#open_brain_result_image").append(img);
	}
}
function func_open_brain_content(response,body)
{
	jQuery("#open_brain_result_content .content").html("");
	jQuery("#open_brain_result_content .content").removeClass('d-none');
	jQuery("#open_brain_result_content .box_button").removeClass('d-none');
	jQuery("#open_brain_result_content .view_content").addClass('d-none');
	let counter = 0;
	for (variable of response['result']['choices'])
	{
		counter++;
		let x = counter + Math.floor((Math.random() * 100) + 1);
		var content = `
			<div class="box" id="box_${x}">
				${variable['text']}
			</div>
			`;
		jQuery("#open_brain_result_content .content").append(content);
	}
}
function func_open_brain_content_title(response,body)
{
	let counter = 0;
	var title = response['result']['choices'][0]['text'];
	// title = title.replace(/(<([^>]+)>)/ig,"");
	jQuery("#open_brain_content_title").val(title);
}